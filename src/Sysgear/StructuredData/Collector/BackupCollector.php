<?php

namespace Sysgear\StructuredData\Collector;

use Sysgear\Backup\BackupableInterface;

/**
 * Collector for backup data from backupable objects.
 * 
 * @author (c) Martijn Evers <martijn4evers@gmail.com>
 */
class BackupCollector extends AbstractCollector
{
    /**
     * This object is a reference.
     * 
     * @var boolean
     */
    protected $reference = false;

    /**
     * Name to use for this node.
     * 
     * @var string
     */
    protected $name;

    /**
     * (non-PHPdoc)
     * @see Sysgear\StructuredData\Collector.CollectorInterface::fromObject()
     */
    public function fromObject($object, $name = null)
    {
        if (! ($object instanceof BackupableInterface)) {
            throw new CollectorException("Given object does not implement BackupableInterface.");
        }

        // Add this object to the list of excluded objects to
        // prevent infinite recursive collecting.
        $this->excludedObjects[] = $object;

        $name = $this->name ?: $this->getNodeName($object);
        $this->element = $this->document->createElement($name);
        $this->element->setAttribute('type', 'object');
        $this->element->setAttribute('class', get_class($object));
        $refClass = new \ReflectionClass($object);
        if ($this->reference) {
            
            // Create reference.
            $ppn = $object->getPrimaryPropertyName();
            $this->element->setAttribute('refName', $ppn);
            $this->element->setAttribute('refValue', $refClass->getProperty($ppn)->getValue($object));
        } else {
            foreach ($refClass->getProperties() as $property) {
    
                // Exclude properties. 
                if ($this->filterProperty($property)) {
    
                    $property->setAccessible(true);
                    $name = $property->getName();
                    $value = $property->getValue($object);
    
                    // Scan scalar or composite property.
                    if (is_scalar($value)) {
                        $this->addScalarNode($name, $value);
                    } elseif ($this->recursiveScan) {
                        $this->addCompositeNode($name, $value);
                    }
                }
            }
        }
        $this->document->appendChild($this->element);
    }

    /**
     * Add scalar property node.
     * 
     * @param string $name
     * @param scalar $value
     */
    protected function addScalarNode($name, $value)
    {
        $property = $this->document->createElement($name);
        $this->element->appendChild($property);
        $property->setAttribute('type', gettype($value));
        $property->setAttribute('value', $value);
    }

    /**
     * Add composite property node.
     * 
     * @param string $name
     * @param mixed $value
     */
    protected function addCompositeNode($name, $value)
    {
        // Scan BackupableInterface implmentation
        if ($value instanceof BackupableInterface) {
            $this->addBackupable($name, $value);
        }

        // Scan sub-collection.
        if (is_array($value) || ($value instanceof \IteratorAggregate)) {

            $collection = $this->document->createElement($name);
            $this->element->appendChild($collection);
            if (is_array($value)) {
                $collection->setAttribute('type', 'array');
            } else {
                $collection->setAttribute('type', 'object');
                $collection->setAttribute('class', get_class($value));
            }

            foreach ($value as $elem) {

                // Collect array element objects implementing the BackupableInterface.
                if ($elem instanceof BackupableInterface) {
                    $this->addBackupable($this->getNodeName($elem), $elem, $collection);
                }
            }
        }
    }

    /**
     * Add child node to this collection.
     * 
     * @param string $name
     * @param \Sysgear\Backup\BackupableInterface $backupable
     * @param \DOMNode $node
     */
    protected function addBackupable($name, BackupableInterface $backupable, \DOMNode $node = null)
    {
        if (null === $node) {
            $node = $this->element;
        }

        // Prevent infinite loops...
        if (in_array($backupable, $this->excludedObjects, true)) {

            $this->createReference($backupable, $node, $name);
        } else {

            // Make a copy of this collector to allow recursive collecting.
            $collector = clone $this;
            $collector->name = $name;
            $backupable->collectStructedData($collector);
            $element = $collector->getDomElement();
            $node->appendChild($element);
        }
    }

    /**
     * Create a reference to an already collected object.
     * 
     * @param \BackupableInterface $backupable
     * @param \DOMNode $node
     * @param string $name
     */
    protected function createReference(BackupableInterface $backupable, \DOMNode $node, $name)
    {
        $collector = clone $this;
        $collector->recursiveScan = false;
        $collector->reference = true;
        $collector->name = $name;
        $backupable->collectStructedData($collector);
        $node->appendChild($collector->getDomElement());
    }

    /**
     * Return true if property can be collected, else return false.
     * 
     * @param \ReflectionProperty $property
     */
    protected function filterProperty(\ReflectionProperty $property)
    {
        // TODO: Allow configuration of the properties to filter.
        //       For now hard code none-underscore-prefixed properties.
        return ('_' !== substr($property->getName(), 0, 1));
    }
}