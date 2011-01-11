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
     * (non-PHPdoc)
     * @see Sysgear\StructuredData\Restorer.RestorerInterface::toObject()
     */
    public function toObject($object, $name = null)
    {
        if (! ($object instanceof BackupableInterface)) {
            throw new RestorerException("Given object does not implement BackupableInterface.");
        }

        if (null === $this->element) {
            foreach ($this->document->childNodes as $node) {
                if (XML_ELEMENT_NODE === $node->nodeType) {
                    $this->element = $node;
                    break;
                }
            }
        }

        $this->createReferenceCandidate($object);
        $refClass = new \ReflectionClass($object);
        foreach ($this->element->childNodes as $propertyNode) {

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
     * Cast property to backupable.
     * 
     * @param \DOMElement $propertyNode
     * @return \Sysgear\Backup\BackupableInterface
     */
    protected function castObject(\DOMElement $propertyNode)
    {
        // Found reference, so return it.
        if ($propertyNode->hasAttribute('ref')) {
            return $this->referenceCandidates['object'][$propertyNode->getAttribute('ref')];
        }

        // Create clone restorer for new object.
        $restorer = clone $this;
        $restorer->name = $propertyNode->nodeName;
        $restorer->element = $propertyNode;
        $restorer->referenceCandidates =& $this->referenceCandidates;

        // Create object, restore and return it.
        $class = $propertyNode->getAttribute('class');
        $backupable = new $class();
        if (! ($backupable instanceof BackupableInterface)) {
            throw new RestorerException("Can not restore class: '{$class}' or class does not implement backupable.");
        }
        $backupable->restoreStructedData($restorer);
        return $backupable;
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
    protected function createReferenceCandidate($object)
    {
        $this->referenceCandidates['object'][$this->element->getAttribute('id')] = $object;
    }
}