<?php

namespace Sysgear\Backup;

use Sysgear\StructuredData\Collector\CollectorInterface;
use Sysgear\StructuredData\Restorer\RestorerInterface;
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
     * Return a propery name of the implementing class
     * which can be used to uniquely identify this instance.
     * 
     * For example; if the implementing class is used as active-record
     * the primary key of the record can be used as backup reference.
     * 
     * @return string
     */
    // TODO: public function getBackupReference();
}