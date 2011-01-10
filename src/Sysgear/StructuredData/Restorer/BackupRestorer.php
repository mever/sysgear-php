<?php

namespace Sysgear\StructuredData\Restorer;

use Sysgear\StructuredData\Restorer\AbstractRestorer;
use Sysgear\Backup\BackupableInterface;

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
    protected $referenceCandidates = array('array' => array(), 'object' => array());

    /**
     * Keep track of all properties which can not be restored.
     * 
     * @var array
     */
    protected $remainingProperties = array();

    /**
     * (non-PHPdoc)
     * @see Sysgear\StructuredData\Restorer.RestorerInterface::toObject()
     */
    public function toObject($object, $name = null)
    {
        if (! ($object instanceof BackupableInterface)) {
            throw new RestorerException("Given object does not implement BackupableInterface.");
        }

        $name = $this->name ?: $this->getNodeName($object);
        $thisNode = $this->document->getElementsByTagName($name)->item(0);
        $this->createReferenceCandidate($thisNode, $object);
        $refClass = new \ReflectionClass($object);
        foreach ($thisNode->childNodes as $propertyNode) {

            if ($propertyNode instanceof \DOMElement) {

                $this->setProperty($refClass, $propertyNode, $object);
            }
        }

        // Return remaining properties.
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
     * Cast property to object.
     * 
     * @param \DOMElement $propertyNode
     * @return object
     */
    protected function castObject(\DOMElement $propertyNode)
    {
        $class = $propertyNode->getAttribute('class');
        if ($propertyNode->hasAttribute('refValue')) {

            // Found reference, return it.
            $prop = $class . '::' . $propertyNode->getAttribute('refName') .
                '=' . $propertyNode->getAttribute('refValue');
            return $this->referenceCandidates['object'][$prop];
        }

        $restorer = clone $this;
        $restorer->name = $propertyNode->nodeName;

        $object = new $class();
        if (! ($object instanceof BackupableInterface)) {
            throw RestorerException::canNotFindClass($class);
        }
        $object->restoreStructedData($restorer);
        return $object;
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
     * Create reference.
     * 
     * @param \DOMelement $node
     * @param object $object
     * @throws RestorerException
     */
    protected function createReferenceCandidate(\DOMelement $node, $object)
    {
        $ppn = $object->getPrimaryPropertyName();
        $class = $node->getAttribute('class');
        $nodeList = $node->getElementsByTagName($ppn);
        if (0 === $nodeList->length) {
            throw new RestorerException("{$class} does not have primary property named: '{$ppn}'" . "\n{$propertyNode->nodeName}");
        }

        // Create reference.
        $prop = $class . '::' . $ppn . '=' . $nodeList->item(0)->getAttribute('value');
        $this->referenceCandidates['object'][$prop] = $object;
    }
}