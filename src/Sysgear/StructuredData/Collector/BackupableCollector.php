<?php

namespace Sysgear\StructuredData\Collector;

use Sysgear\Backup\BackupableInterface;

/**
 * Collector for data from backupable objects.
 * 
 * @author (c) Martijn Evers <martijn4evers@gmail.com>
 */
class BackupableCollector extends ObjectCollector
{
    /**
     * Scan scalar object property.
     * 
     * @param \Sysgear\Backup\BackupableInterface $backupable
     * @param string $name
     * @param scalar $value
     */
    public function scanScalarProperty($backupable, $name, $value)
    {
        if ($this->duplicate) {

            // It is not nessesairy to backup any more 
            if ($backupable->getPrimaryPropertyName() === $name) {
                $this->element->setAttribute($name, $value);
            }
        } else {
            $this->element->setAttribute($name, $value);
        }
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
        // Do not scan any further, this object has already been scanned.
        if ($this->duplicate) {
            return;
        }

        // Scan BackupableInterface implmentation
        if ($value instanceof BackupableInterface) {
            $this->addBackupableChild($backupable, $value);
        }

        // Scan array and array like.
        if (is_array($value) || ($value instanceof \IteratorAggregate)) {

            $collection = $this->document->createElement($name);
            $this->element->appendChild($collection);

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
    protected function addBackupableChild($parentBackupable, $backupable, \DOMNode $node = null)
    {
        // Make a copy of this collector to allow recursive collecting.
        $collector = clone $this;
        $collector->excludedObjects[] = $parentBackupable;

        // Prevent infinite loops...
        if (in_array($backupable, $this->excludedObjects, true)) {
            $collector->duplicate = true;
        }
        $backupable->collectStructedData($collector);
        
        if (null === $node) {
            $node = $this->element;
        }
        $node->appendChild($collector->getDomElement());
    }
}