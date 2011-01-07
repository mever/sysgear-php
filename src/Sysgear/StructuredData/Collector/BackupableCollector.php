<?php

namespace Sysgear\StructuredData\Collector;

use Sysgear\Backup\BackupableInterface;

class BackupableCollector extends ObjectCollector
{
    /**
     * Scan composite object property.
     * 
     * @param \StdClass $object
     * @param string $name
     * @param mixed $value
     */
    protected function scanCompositeProperty($object, $name, $value)
    {
        // Scan BackupableInterface implmentation
        if ($value instanceof BackupableInterface) {
            $this->addBackupableChild($object, $value);
        }

        // Scan array and array like.
        if ($this->recursiveScan
        && (is_array($value) || ($value instanceof \IteratorAggregate))) {

            $collection = $this->document->createElement($name);
            $this->element->appendChild($collection);

            foreach ($value as $elem) {

                // Collect array element objects implementing the BackupableInterface.
                if ($elem instanceof BackupableInterface) {
                    $this->addBackupableChild($object, $elem, $collection);
                }
            }
        }
    }

    /**
     * Add child node to this collection.
     * 
     * @param \StdClass $parentObj
     * @param \Sysgear\Backup\BackupableInterface $backupable
     * @param \DOMNode $node
     */
    protected function addBackupableChild($parentObj, $backupable, \DOMNode $node = null)
    {
        // Make a copy of this collector to allow recursive collecting.
        $collector = clone $this;
        $collector->excludedObjects[] = $parentObj;
        if (in_array($backupable, $this->excludedObjects, true)) {
            $collector->recursiveScan = false;
        }
        $backupable->backup($collector);
        
        if (null === $node) {
            $node = $this->element;
        }
        $node->appendChild($collector->getDomElement());
    }
}