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
     * (non-PHPdoc)
     * @see Sysgear\StructuredData\Collector.CollectorInterface::fromObject()
     */
    public function fromObject($object, $name = null)
    {
        if (! ($object instanceof BackupableInterface)) {
            throw new CollectorException("Given object does not implement BackupableInterface.");
        }

        if (null === $name) {
            $fullClassname = get_class($object);
            $pos = strrpos($fullClassname, '\\');
            $name = (false === $pos) ? $fullClassname : substr($fullClassname, $pos + 1);
        }

        // Add this object to the list of excluded objects to
        // prevent infinite recursive collecting.
        $this->excludedObjects[] = $object;

        $this->element = $this->document->createElement($name);
        $this->element->setAttribute('type', get_class($object));
        $refClass = new \ReflectionClass($object);
        if ($this->reference) {
            
            $ppn = $object->getPrimaryPropertyName();
            $this->element->setAttribute('primaryProperty', $ppn);
            $this->element->setAttribute('reference',
                $refClass->getProperty($ppn)->getValue($object));
        } else {
            foreach ($refClass->getProperties() as $property) {
    
                if ($this->filterProperty($property)) {
    
                    $property->setAccessible(true);
                    $name = $property->getName();
                    $value = $property->getValue($object);
    
                    // Scan scalar or composite property.
                    if (is_scalar($value)) {
                        $this->scanScalarProperty($object, $name, $value);
                    } elseif ($this->recursiveScan) {
                        $this->scanCompositeProperty($object, $name, $value);
                    }
                }
            }
        }
        $this->document->appendChild($this->element);
    }

    /**
     * Scan scalar object property.
     * 
     * @param \Sysgear\Backup\BackupableInterface $backupable
     * @param string $name
     * @param scalar $value
     */
    protected function scanScalarProperty($backupable, $name, $value)
    {
        $property = $this->document->createElement($name);
        $this->element->appendChild($property);
        $property->setAttribute('type', gettype($value));
        $property->setAttribute('value', $value);
    }

    /**
     * Scan composite object property.
     * 
     * @param \Sysgear\Backup\BackupableInterface $backupable
     * @param string $name
     * @param mixed $value
     */
    protected function scanCompositeProperty($backupable, $name, $value)
    {
        // Scan BackupableInterface implmentation
        if ($value instanceof BackupableInterface) {
            $this->addBackupableChild($backupable, $value);
        }

        // Scan sub-collection.
        if (is_array($value) || ($value instanceof \IteratorAggregate)) {

            $collection = $this->document->createElement($name);
            $this->element->appendChild($collection);
            if (is_array($value)) {
                $collection->setAttribute('type', 'array');
            } else {
                $collection->setAttribute('class', get_class($value));
            }

            foreach ($value as $elem) {

                // Collect array element objects implementing the BackupableInterface.
                if ($elem instanceof BackupableInterface) {
                    $this->addBackupableChild($backupable, $elem, $collection);
                }
            }
        }
    }

    /**
     * Add child node to this collection.
     * 
     * @param \Sysgear\Backup\BackupableInterface $parentBackupable
     * @param \Sysgear\Backup\BackupableInterface $backupable
     * @param \DOMNode $node
     */
    protected function addBackupableChild(BackupableInterface $parentBackupable, BackupableInterface $backupable, \DOMNode $node = null)
    {
        if (null === $node) {
            $node = $this->element;
        }

        // Prevent infinite loops...
        if (in_array($backupable, $this->excludedObjects, true)) {

            $this->createReference($backupable, $node);
        } else {

            // Make a copy of this collector to allow recursive collecting.
            $collector = clone $this;
            $backupable->collectStructedData($collector);
            $node->appendChild($collector->getDomElement());
        }
    }

    /**
     * Create a reference to an already collected object.
     * 
     * @param \BackupableInterface $backupable
     * @param \DOMNode $node
     */
    protected function createReference(BackupableInterface $backupable, \DOMNode $node)
    {
        $collector = clone $this;
        $collector->recursiveScan = false;
        $collector->reference = true;
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