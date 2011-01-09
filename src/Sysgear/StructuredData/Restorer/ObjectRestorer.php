<?php

namespace Sysgear\StructuredData\Restorer;

/**
 * Restorer for data from objects.
 * 
 * @author (c) Martijn Evers <martijn4evers@gmail.com>
 */
class ObjectRestorer extends AbstractRestorer
{
    /**
     * Namespaces.
     * 
     * @var string
     */
    const NS_PROPERTY = 'P';
    const NS_METADATA = 'M';
    
    /**
     * (non-PHPdoc)
     * @see Sysgear\StructuredData\Restorer.RestorerInterface::toObject()
     */
    public function toObject($object, $name = null)
    {
        if (! is_object($object)) {
            throw new RestorerException("Given parameter is not an object.");
        }

        if (null === $name) {
            $fullClassname = get_class($object);
            $pos = strrpos($fullClassname, '\\');
            $name = (false === $pos) ? $fullClassname : substr($fullClassname, $pos + 1);
        }

        $className = null;
        $refClass = new \ReflectionClass($object);
        $thisNode = $this->document->firstChild;

        // Restore object attributes.
        foreach ($thisNode->attributes as $value => $attributeNode) {

            $ns = substr($value, 0, 1);
            $name = substr($value, 1);

            switch ($ns) {
            case self::NS_METADATA:
                $className = $name;
                break;

            case self::NS_PROPERTY:
                $property = $refClass->getProperty($name);
                $property->setAccessible(true);
                $property->setValue($object, $this->toScalarFromString($attributeNode->nodeValue));
                break;
            }
        }

        // Restore object graph.
        if (null === $className) {
            throw RestorerException::canNotFindClass();
        }
        $this->restoreCompositeProperties($object, $className, $thisNode);
    }

    /**
     * Restore composite properties for this $object.
     * 
     * @param \StdClass $refClass
     * @param string $className
     * @param \DOMNode $node
     */
    protected function restoreCompositeProperties($object, $className, \DOMNode $node)
    {
        foreach ($node->childNodes as $childNode) {

            if ($childNode instanceof \DOMElement) {

//                $object->{$childNode->nodeName}[] = 
//                $refClass = new \ReflectionClass($object);
//                $property = $refClass->getProperty($childNode->nodeName);
//                $property->setAccessible(true);
//                $collection = $property->getValue($object);
                
                $object->{$childNode->nodeName}[] = 'a';
                
//                $restorer = clone $this;
//                
//                $object$this->createChildObject($childNode);
//                var_dump($childNode);
//                var_dump($childNode->nodeName);
            }
        }
    }

    protected function createChildObject(\DOMNode $node)
    {
        
    }

    /**
     * Cast string to scalar value before restoring.
     * 
     * @param string $value
     * @return mixed
     */
    protected function toScalarFromString($value)
    {
        return unserialize($value);
    }
}