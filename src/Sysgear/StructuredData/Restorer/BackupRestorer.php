<?php

namespace Sysgear\StructuredData\Restorer;

use Sysgear\StructuredData\Restorer\AbstractRestorer;
use Sysgear\Backup\BackupableInterface;

class BackupRestorer extends AbstractRestorer
{
    /**
     * (non-PHPdoc)
     * @see Sysgear\StructuredData\Restorer.RestorerInterface::toObject()
     */
    public function toObject($object, $name = null)
    {
        if (! ($object instanceof BackupableInterface)) {
            throw new RestorerException("Given object does not implement BackupableInterface.");
        }

        $name = $this->getNodeName($object);
        $thisNode = $this->document->getElementsByTagName($name)->item(0);
        $refClass = new \ReflectionClass($object);
        foreach ($thisNode->childNodes as $propertyNode) {

            if ($propertyNode instanceof \DOMElement) {

                $this->setProperty($refClass, $propertyNode, $object);
            }
        }
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
        if ($this->isScalar($propertyNode)) {

            $property = $refClass->getProperty($propertyNode->nodeName);
            if ($property->isPublic()) {

                $property->setValue($object, $this->getPropertyValue($propertyNode));
            }
        }
    }

    /**
     * Set public property type direct.
     * 
     * @param \DOMNode $propertyNode
     * @return mixed
     */
    protected function getPropertyValue(\DOMNode $propertyNode)
    {
        $value = $propertyNode->getAttribute('value');
        $type = $propertyNode->getAttribute('type');
        switch ($type) {
        default:
            settype($value, $type);
        }
        return $value;
    }

    /**
     * Check if given property node is a scalar type.
     * 
     * @param \DOMNode $propertyNode
     * @return boolean
     */
    protected function isScalar(\DOMNode $propertyNode)
    {
        switch ($propertyNode->getAttribute('type')) {
        case 'array':
        case 'object':
            return false;
        }
        return true;
    }
}