<?php

namespace Sysgear\Backup;

use Sysgear\StructuredData\Collector\BackupCollector;
use Sysgear\StructuredData\Restorer\BackupRestorer;

/**
 * Implementations of this interface can be backupped if it is passed
 * a BackupCollector and restored when passed a BackupRestorer.
 * 
 * @author (c) Martijn Evers <martijn4evers@gmail.com>
 */
interface BackupableInterface
{
	/**
     * Collect structed data using a backup collector.
     * 
     * @param \Sysgear\StructuredData\Collector\BackupCollector $backupDataCollector
     */
    public function collectStructedData(BackupCollector $backupDataCollector);

    /**
     * Restore structed data using a backup restorer.
     * 
     * @param Sysgear\StructuredData\Restorer\BackupRestorer $backupDataRestorer
     */
    public function restoreStructedData(BackupRestorer $backupDataRestorer);

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