<?php

namespace Sysgear\StructuredData\Restorer;

use Sysgear\StructuredData\Collector\BackupCollector;
use Sysgear\StructuredData\NodeProperty;
use Sysgear\StructuredData\NodeCollection;
use Sysgear\StructuredData\NodeInterface;
use Sysgear\StructuredData\Node;
use Sysgear\Backup\Exception;
use Sysgear\Backup\BackupableInterface;
use Sysgear\Merger\MergerException;
use Sysgear\Merger\MergerInterface;
use Closure;

/**
 * Concrete restorer for the Sysgear backup tool.
 *
 * @author (c) Martijn Evers <martijn4evers@gmail.com>
 */
class BackupRestorer extends AbstractRestorer
{
    /**
     * Assume nodes as complete. If the merger notifies a merge
     * error (by returning null) try to fetch the corresponding node
     * and add the corresponding fields, overwriting the old ones.
     *
     * @var integer
     */
    const MERGE_ASSUME_COMPLETE = 0;

    /**
     * Assume nodes as incomplete. First check if all mandatory fields
     * are available. Merge if complete or find an existing node in the system
     * to change.
     *
     * @var integer
     */
    const MERGE_ASSUME_INCOMPLETE = 1;

    /**
     * How to merge nodes if a merger instance is given.
     *
     * @var integer
     */
    protected $mergeMode = self::MERGE_ASSUME_COMPLETE;

    /**
     * @var \Sysgear\Merger\MergerInterface
     */
    protected $merger;

    /**
     * Name to use for this node.
     *
     * @var string
     */
    protected $name;

    /**
     * Keep track of all posible reference candidates.
     *
     * @var array
     */
    protected $referenceCandidates = array();

    /**
     * Keep track to the number of descent levels.
     *
     * @var integer
     */
    protected $descentLevel = 0;

    /**
     * Set option.
     *
     * @param string $key
     * @param mixed $value
     */
    protected function _setOption($key, $value)
    {
        switch ($key) {
        case 'merger':
            $this->merger = ($value instanceof MergerInterface) ? $value : null;
            break;

        case 'mergeMode':
            $this->mergeMode = (int) $value;
            break;

        default:
            parent::_setOption($key, $value);
        }
    }

    /**
     * (non-PHPdoc)
     * @see Sysgear\StructuredData\Restorer.RestorerInterface::restore()
     */
    public function restore(Node $node, $object = null)
    {
        // set node & descent level
        $this->node = $node;
        $this->descentLevel = 0;

        // create object to restore
        if (null === $object) {
            $object = $this->createObject($node);
        }

        // start restoration
        if ($object instanceof BackupableInterface) {
            $object->restoreStructedData($this->getRestorer($node));
        }

        // flush merger
        if (null !== $this->merger) {
            $this->merger->flush();
        }

        return $object;
    }

    /**
     * (non-PHPdoc)
     * @see Sysgear\StructuredData\Restorer.RestorerInterface::toObject()
     */
    public function toObject($object)
    {
        if (! ($object instanceof BackupableInterface)) {
            throw Exception::classIsNotABackable($object);
        }

        if (null !== $this->logger) {
            $logger = $this->logger;
            $className = get_class($object);
            $logger($this->descentLevel, "restoring object: {$className}");
        }

        // add this object as possible reference and restore properties
        $remainingProperties = array();
        $this->createReferenceCandidate($object);
        $refClass = new \ReflectionClass($object);
        foreach ($this->node->getProperties() as $name => $node) {

            $logLine = isset($logger) ? $name : null;
            $property = $refClass->getProperty($name);
            $value = $this->getPropertyValue($node, $logLine);
            if ($property->isPublic()) {
                $property->setValue($object, $value);
            } else {
                $remainingProperties[$name] = $value;
            }
        }

        if (isset($logger)) {
            if (null === $this->merger) {
                $logger($this->descentLevel, "# no merger given");
            } else {
                $logger($this->descentLevel, "# start merge");
            }
        }

        // merge object with given merger
        if (null !== $this->merger) {
            $this->dispatchMerge($object);
        }

        // return properties which could not be set here (ie. private)
        return $remainingProperties;
    }

    /**
     * Return the properly casted value.
     *
     * @param NodeInterface $propertyNode
     * @param string $logLine
     * @return mixed
     */
    protected function getPropertyValue(NodeInterface $propertyNode, &$logLine = null)
    {
        // found reference?
        $objHash = spl_object_hash($propertyNode);
        if (array_key_exists($objHash, $this->referenceCandidates)) {
            if (null !== $logLine) {
                $logLine = "[reference] {$logLine}";
            }

            return $this->referenceCandidates[$objHash];
        }

        if ($propertyNode instanceof NodeCollection) {
            $type = 'collection';
        } elseif ($propertyNode instanceof Node) {
            $type = 'node';
        } else {
            $type = 'primitive';
        }

        if (null !== $logLine) {
            $logger = $this->logger;
            $logger($this->descentLevel, "* [{$type}] $logLine");
        }

        // restore property and return the restored value
        switch ($type) {
            case 'collection':
                return $this->restoreCollection($propertyNode);

            case 'node':
                return $this->restoreNode($propertyNode);

            default:
                return $this->restoreProperty($propertyNode);
        }
    }

    protected function restoreCollection(NodeCollection $collection)
    {
        $arr = array();
        foreach ($collection as $node) {
            $arr[] = $this->getPropertyValue($node);
        }
        return $arr;
    }

    protected function restoreProperty(NodeProperty $property)
    {
        $value = $property->getValue();
        $type = $property->getType();
        switch ($type) {
        case "": break;    // skip type-casting if no type is given
        default:
            settype($value, $type);
        }

        return $value;
    }

    protected function restoreNode(Node $node)
    {
        $object = $this->createObject($node);
        if ($object instanceof BackupableInterface) {
            $object->restoreStructedData($this->getRestorer($node));
        }

        return $object;
    }

    /**
     * Dispatch merge operation.
     *
     * @param object $object
     * @return object Merged object.
     */
    protected function dispatchMerge($object)
    {
        switch ($this->mergeMode) {
        case self::MERGE_ASSUME_COMPLETE:
            $mergedObject = $this->_mergeAssumeComplete($object, $this->node);
            break;

        case self::MERGE_ASSUME_INCOMPLETE:
            $mergedObject = $this->_mergeAssumeIncomplete($object, $this->node);
            break;
        }

        $this->createReferenceCandidate($mergedObject, $this->node);
        return $mergedObject;
    }

    /**
     * Assume nodes as incomplete. First check if all mandatory fields
     * are available. Merge if complete or find an existing node in the system
     * to change.
     *
     * @param \stdClass $object
     * @param Node $node
     * @return object
     */
    protected function _mergeAssumeIncomplete($object, Node $node)
    {
        // assume incomplete, first check for missing properties
        $logger = $this->logger;
        $props = array_keys($node->getProperties());
        $incl = array_diff($this->merger->getMandatoryProperties($object), $props);
        if (count($incl) > 0) {

            if (null !== $logger) {
                $logger($this->descentLevel, "# object incomplete, missing: " . join(', ', $incl));
            }

            // find complete object first
            $completeObject = $this->merger->find($object);
            if (null === $completeObject) {
                throw new RestorerException("Couldn't find an object in the system to overwrite.");
            }

            // collect missing property nodes from complete object
            // TODO: Find out if the "onlyImplementor" option can cause trouble! This is
            //       needed to prevent the collector from collecting Doctrine proxy objects.
            $backupCollector = new BackupCollector(array('descentLevel' => 1, 'onlyImplementor' => true));
            $completeObject->collectStructedData($backupCollector, array('onlyInclude' => $incl));

            // restore incomplete object
            if (null !== $logger) {
                $logger($this->descentLevel, "# merger found matching object in storage: {$this->merger->getObjectId($object)}");
            }
            $restorer = new self();
            $restorer->node = $backupCollector->getNode();
            $object->restoreStructedData($restorer);
            if (null !== $logger) {
                $statusLine = ("# reconstruct incomplete object with matching object: successful, ");
            }

        } elseif (null !== $logger) {
            $statusLine = "# ";
        }

        // merge now completed object
        $mergedObject = $this->merger->merge($object);
        if (null === $mergedObject) {
            // TODO: just log and skip merge or throw exception.
//            $this->log('merge failed. Skip merge!');

        } elseif (null !== $logger) {
            $logger($this->descentLevel, $statusLine . 'merge succeeded');
        }

        return $mergedObject;
    }

    /**
     * Assume nodes as complete. If the merger notifies a merge
     * error (by returning null) try to find the corresponding stored node
     * and set the corresponding fields from the stored node on the merged node.
     *
     * @param \stdClass $object
     * @param Node $thisNode
     * @return object
     */
    protected function _mergeAssumeComplete($object, Node $node)
    {
        // try to merge the object
        $mergedObject = $this->merger->merge($object);
        if (null === $mergedObject) {

            // TODO: option? switch to assume incomplete mode or throw exception
            $restorer = $this->getRestorer($node);
            $restorer->merger = $this->merger;
            $restorer->mergeMode = self::MERGE_ASSUME_INCOMPLETE;
            $restorer->toObject($object);
        }

        return $mergedObject;
    }

    /**
     * Get restorer for new object to restore.
     *
     * @param Node $node
     * @return \Sysgear\StructuredData\Restorer\BackupRestorer
     */
    protected function getRestorer(Node $node)
    {
        $restorer = new self($this->persistentOptions);
        $restorer->referenceCandidates =& $this->referenceCandidates;
        $restorer->descentLevel = $this->descentLevel + 1;
        $restorer->node = $node;

        if ($this->mergeMode === self::MERGE_ASSUME_COMPLETE && $this->descentLevel > 0) {
            $restorer->merger = null;
        }

        return $restorer;
    }

    /**
     * Create reference candidate.
     *
     * @param object $object
     * @param Node $node
     */
    protected function createReferenceCandidate($object, Node $node = null)
    {
        $hash = (null === $node) ? $this->getHash() : spl_object_hash($node);
        if (! empty($hash)) {
//            echo "create ref: {$hash}\n";
            $this->referenceCandidates[$hash] = $object;
        }
    }

    /**
     * Create object to restore.
     *
     * @param Node $node
     * @return object
     */
    protected function createObject(Node $node)
    {
        $class = $node->getMeta('class');
        if (null === $class) {
            // TODO: throw exception
        }
        return new $class();
    }

    /**
     * Return node hash to uniquely identify the node.
     *
     * @return string
     */
    protected function getHash()
    {
        // TODO: reuse node hash
        return spl_object_hash($this->node);
    }
}