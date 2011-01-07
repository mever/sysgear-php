<?php

namespace Sysgear\Backup;

use Sysgear\StructuredData\Collector\CollectorInterface;
use Sysgear\StructuredData\Restorer\RestorerInterface;

/**
 * Classes implmenting this interface can be backupped by
 * providing a StructuredData\Collector and StructuredData\Restorer.
 * 
 * @author (c) Martijn Evers <martijn4evers@gmail.com>
 */
interface BackupableInterface
{
    /**
     * Collect data to backup using a collector.
     * 
     * @param \Sysgear\StructuredData\Collector\CollectorInterface $dataCollector
     */
    public function backup(CollectorInterface $dataCollector);

    /**
     * Restore data from backup using a restorer.
     * 
     * @param Sysgear\StructuredData\Restorer\RestorerInterface $dataRestorer
     */
    // TODO: public function restore(RestorerInterface $dataRestorer);

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