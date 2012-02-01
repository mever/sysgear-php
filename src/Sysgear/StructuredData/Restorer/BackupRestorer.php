<?php

namespace Sysgear\StructuredData\Restorer;

use Sysgear\StructuredData\Collector\BackupCollector;
use Sysgear\StructuredData\NodeProperty;
use Sysgear\StructuredData\NodeCollection;
use Sysgear\StructuredData\NodeInterface;
use Sysgear\StructuredData\Node;
use Sysgear\Backup\Exception;
use Sysgear\Backup\BackupableInterface;
use Closure;

/**
 * Concrete restorer for the Sysgear backup tool.
 *
 * @author (c) Martijn Evers <mevers47@gmail.com>
 */
class BackupRestorer extends AbstractRestorer
{
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
     * (non-PHPdoc)
     * @see Sysgear\StructuredData\Restorer.RestorerInterface::restore()
     */
    public function restore(Node $node, $object = null)
    {
        $this->node = $node;
        if (null === $object) {
            $object = $this->createObject($node);
        }

        // start restoration
        if ($object instanceof BackupableInterface) {
            $object->restoreStructedData($this->getRestorer($node));
        }

        return $object;
    }

    /**
     * Restore data to object.
     *
     * @param \StdClass $object
     * @return array Return a list of remaining properties, those that could not
     *               be set because the restorer was not able to access the
     *               modifiers of the $object.
     */
    public function toObject($object)
    {
        if (! ($object instanceof BackupableInterface)) {
            throw Exception::classIsNotABackable($object);
        }

        // add this object as possible reference and restore properties
        $remainingProperties = array();
        $this->createReferenceCandidate($object);
        $refClass = new \ReflectionClass($object);
        foreach ($this->node->getProperties() as $name => $node) {

            $property = $refClass->getProperty($name);
            $value = $this->getPropertyValue($node);
            if ($property->isPublic()) {
                $property->setValue($object, $value);
            } else {
                $remainingProperties[$name] = $value;
            }
        }

        // return properties which could not be set here (ie. private)
        return $remainingProperties;
    }

    /**
     * Return the properly casted value.
     *
     * @param NodeInterface $propertyNode
     * @return mixed
     */
    protected function getPropertyValue(NodeInterface $propertyNode)
    {
        // found reference?
        $objHash = spl_object_hash($propertyNode);
        if (array_key_exists($objHash, $this->referenceCandidates)) {
            return $this->referenceCandidates[$objHash];
        }

        if ($propertyNode instanceof NodeCollection) {
            $type = 'collection';
        } elseif ($propertyNode instanceof Node) {
            $type = 'node';
        } else {
            $type = 'primitive';
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
     * Get restorer for new object to restore.
     *
     * @param Node $node
     * @return \Sysgear\StructuredData\Restorer\BackupRestorer
     */
    protected function getRestorer(Node $node)
    {
        $restorer = new self($this->persistentOptions);
        $restorer->referenceCandidates =& $this->referenceCandidates;
        $restorer->node = $node;
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

        $refClass = new \ReflectionClass($class);
        if (\PHP_VERSION_ID > 50400) {
            return $refClass->newInstanceWithoutConstructor();

        } else {
            $properties = $refClass->getProperties();
            $defaults = $refClass->getDefaultProperties();

            $serealized = "O:" . strlen($class) . ":\"$class\":".count($properties) .':{';
            foreach ($properties as $property){
                $name = $property->getName();
                if($property->isProtected()){
                    $name = chr(0) . '*' .chr(0) .$name;
                } elseif($property->isPrivate()){
                    $name = chr(0)  . $class.  chr(0).$name;
                }
                $serealized .= serialize($name);
                if(array_key_exists($property->getName(),$defaults) ){
                    $serealized .= serialize($defaults[$property->getName()]);
                } else {
                    $serealized .= serialize(null);
                }
            }
            $serealized .="}";
            return unserialize($serealized);
        }
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