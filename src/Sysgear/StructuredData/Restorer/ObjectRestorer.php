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

class ObjectRestorer extends AbstractRestorer
{
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

        // add this object as possible reference and restore properties
        $remainingProperties = array();
        $this->createReferenceCandidate($object);
        $refClass = new \ReflectionClass($object);
        foreach ($this->node->getProperties() as $name => $node) {

            $property = $refClass->getProperty($name);
            $property->setAccessible(true);
            $property->setValue($object, $this->getPropertyValue($node));
        }

        return $object;
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
        foreach ($collection as $elem) {

            $key = $elem->getMeta('key');
            if (null === $key) {
                $arr[] = $this->getPropertyValue($elem);
            } else {
                $isEnum = ('i' === $key[0]);
                $key = substr($key, 2);
                $val = ($isEnum) ? (integer) $key : (string) $key;
                $arr[$val] = $this->getPropertyValue($elem);
            }
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
        return $this->getRestorer()->restore($node);
    }

    /**
     * Get restorer for new object to restore.
     *
     * @return \Sysgear\StructuredData\Restorer\BackupRestorer
     */
    protected function getRestorer()
    {
        $restorer = new static($this->persistentOptions);
        $restorer->referenceCandidates =& $this->referenceCandidates;
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
        $oid = (null === $node) ? $this->getHash() : spl_object_hash($node);
        if (! empty($oid)) {
            $this->referenceCandidates[$oid] = $object;
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