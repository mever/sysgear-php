<?php

namespace Sysgear\StructuredData\Restorer;

use Sysgear\Backup\Exception;
use Sysgear\Backup\BackupableInterface;
use Sysgear\Merger\MergerException;
use Sysgear\Merger\MergerInterface;

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
     * Expect nodes as complete.
     *
     * @var integer
     */
    const MERGE_EXPECT_COMPLETE = 2;

    /**
     * How to merge nodes if a merger instance is given.
     *
     * @var integer
     */
    protected $mergeMode = self::MERGE_ASSUME_COMPLETE;

    /**
     * @var \Sysgear\StructuredData\Restorer\Backup\MergerInterface
     */
    protected $merger;

    /**
     * Name to use for this node.
     *
     * @var string
     */
    protected $name;

    /**
     * @var \DOMElement
     */
    protected $element;

    /**
     * Keep track of all posible reference candidates.
     *
     * @var array
     */
    protected $referenceCandidates = array('array' => array(), 'object' => array());

    /**
     * Keep track of all properties which can not be restored.
     *
     * @var array
     */
    protected $remainingProperties = array();

    /**
     * Restore state, no remaining properties or other
     * business that shouldn't be cloned.
     */
    public function __clone()
    {
        $this->remainingProperties = array();
    }

    /**
     * Set option.
     *
     * @param string $key
     * @param mixed $value
     */
    public function setOption($key, $value)
    {
        switch ($key) {
        case 'merger':
            $this->merger = ($value instanceof MergerInterface) ? $value : null;
            break;

        case 'mergeMode':
            $this->mergeMode = (int) $value;
            break;

        default:
            parent::setOption($key, $value);
        }
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

        if (null === $this->element) {
            foreach ($this->document->childNodes as $node) {
                if ($node instanceof \DOMElement) {
                    $this->element = $node;
                    break;
                }
            }
        }

        // Add this object as reference and restore properties.
        $this->createReferenceCandidate($object);
        $refClass = new \ReflectionClass($object);
        foreach ($this->element->childNodes as $propertyNode) {

            if ($propertyNode instanceof \DOMElement) {
                $this->setProperty($refClass, $propertyNode, $object);
            }
        }

        // Return properties which could not be set here (ie. private).
        return $this->remainingProperties;
    }

    /**
     * Set property of this object.
     *
     * @param \ReflectionClass $refClass
     * @param \DOMNode $propertyNode
     * @param object $object
     */
    protected function setProperty(\ReflectionClass $refClass, \DOMNode $propertyNode, $object)
    {
        $name = $propertyNode->nodeName;
        $value = $this->getPropertyValue($propertyNode);
        $property = $refClass->getProperty($name);

        if ($property->isPublic()) {
            $property->setValue($object, $value);
        } else {
            $this->remainingProperties[$name] = $value;
        }
    }

    /**
     * Return the properly casted value.
     *
     * @param \DOMElement $propertyNode
     * @return mixed
     */
    protected function getPropertyValue(\DOMElement $propertyNode)
    {
        $type = $propertyNode->getAttribute('type');
        switch ($type) {
        case 'array':
            return $this->castArray($propertyNode);
        case 'object':
            return $this->castObject($propertyNode);
        default:
            return $this->castScalar($propertyNode, $type);
        }
    }

    /**
     * Cast property to array.
     *
     * @param \DOMElement $propertyNode
     * @return array
     */
    protected function castArray(\DOMElement $propertyNode)
    {
        $collection = array();
        foreach ($propertyNode->childNodes as $child) {
            if ($child instanceof \DOMElement) {
                $collection[] = $this->getPropertyValue($child);
            }
        }
        return $collection;
    }

    /**
     * Cast property to scalar.
     *
     * @param \DOMNode $propertyNode
     * @param string $type
     * @return mixed
     */
    protected function castScalar(\DOMElement $propertyNode, $type)
    {
        $value = $propertyNode->getAttribute('value');
        switch ($type) {
        default:
            settype($value, $type);
        }
        return $value;
    }

    /**
     * Cast property to backupable.
     *
     * @param \DOMElement $propertyNode
     * @return \stdClass
     */
    protected function castObject(\DOMElement $propertyNode)
    {
        // Found reference, so return it.
        if ($propertyNode->hasAttribute('ref')) {
            return $this->referenceCandidates['object'][$propertyNode->getAttribute('ref')];
        }

        // Create new object to restore.
        $class = $propertyNode->getAttribute('class');
        $object = new $class();

        // Merge entity with 3rd-party system.
        if (null !== $this->merger) {

            $object = $this->dispatchMerge($object, $propertyNode);
        } else {

            // Check if object is backupable.
            if ($object instanceof BackupableInterface) {
                $object->restoreStructedData($this->createRestorer($propertyNode));
            } else {
                throw Exception::classIsNotABackable($class);
            }
        }

        return $object;
    }

    /**
     * Dispatch merge operation.
     *
     * @param object $object
     * @param \DOMElement $propertyNode
     * @return object Merged object.
     */
    protected function dispatchMerge($object, $propertyNode)
    {
        switch ($this->mergeMode) {
        case self::MERGE_ASSUME_COMPLETE:
            return $this->_mergeAssumeComplete($object, $propertyNode);

        case self::MERGE_ASSUME_INCOMPLETE:
            return $this->_mergeAssumeIncomplete($object, $propertyNode);
        }
    }

    /**
     * Assume nodes as incomplete. First check if all mandatory fields
     * are available. Merge if complete or find an existing node in the system
     * to change.
     *
     * TODO: IMPLEMENT THIS MERGE MODE.
     *
     * @param \stdClass $object
     * @param \DOMElement $thisNode
     * @return object
     */
    protected function _mergeAssumeIncomplete($object, \DOMElement $thisNode)
    {
        // If possible, begin by descending into the tree.
        if ($object instanceof BackupableInterface) {
            $object->restoreStructedData($this->createRestorer($thisNode));
        }

        // Check fields.

        $mergedObject = $this->merger->merge($object);
        if (null === $mergedObject) {

            // Pick a complete object and fill it with changes.
            $mergedObject = $this->merger->find($object);
            if ($mergedObject instanceof BackupableInterface) {
                $mergedObject->restoreStructedData($this->createRestorer($thisNode));
            }
        }

        return $mergedObject;
    }

    /**
     * Assume nodes as complete. If the merger notifies a merge
     * error (by returning null) try to fetch the corresponding node
     * and add the corresponding fields, overwriting the old ones.
     *
     * @param \stdClass $object
     * @param \DOMElement $thisNode
     * @return object
     */
    protected function _mergeAssumeComplete($object, \DOMElement $thisNode)
    {
        // If possible, begin by descending into the tree.
        if ($object instanceof BackupableInterface) {
            $object->restoreStructedData($this->createRestorer($thisNode));
        }

        // Try to merge the object.
        $mergedObject = $this->merger->merge($object);
        if (null === $mergedObject) {

            // Pick an object from the system and overwrite fields.
            $mergedObject = $this->merger->find($object);
            if (null === $mergedObject) {
                throw new RestorerException("Couldn't find an object in the system to overwrite.");
            }

            if ($mergedObject instanceof BackupableInterface) {
                $mergedObject->restoreStructedData($this->createRestorer($thisNode));
            }
        }

        return $mergedObject;
    }

    /**
     * Clone restorer for new object to restore.
     *
     * @param \DOMElement $propertyNode
     * @return \Sysgear\StructuredData\Restorer\BackupRestorer
     */
    protected function createRestorer(\DOMElement $propertyNode)
    {
        $restorer = clone $this;
        $restorer->name = $propertyNode->nodeName;
        $restorer->element = $propertyNode;
        $restorer->referenceCandidates =& $this->referenceCandidates;
        return $restorer;
    }

    /**
     * Create reference.
     *
     * @param \DOMelement $node
     * @param object $object
     * @throws RestorerException
     */
    protected function createReferenceCandidate($object)
    {
        $this->referenceCandidates['object'][$this->element->getAttribute('id')] = $object;
    }
}