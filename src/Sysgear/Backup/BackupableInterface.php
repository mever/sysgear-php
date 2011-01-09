<?php

namespace Sysgear\Backup;

use Sysgear\StructuredData\CollectionInterface;

/**
 * Implementations of this interface can be backupped if it is passed
 * a StructuredData\Collector and restored when passed a StructuredData\Restorer.
 * 
 * @author (c) Martijn Evers <martijn4evers@gmail.com>
 */
interface BackupableInterface extends CollectionInterface
{
    /**
     * Return the primary property name of the implementing class
     * which can be used to uniquely identify this instance.
     * 
     * For example; if the implementing class is an active-record
     * the property which represents the primary key can be used.
     * 
     * @return string
     */
    public function getPrimaryPropertyName();
}