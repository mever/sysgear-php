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
     * Returns metadata about the implementation of this class.
     * 
     * *id* (required for relations)
     * A scalar used to identify and reference the instance of
     * the implmenting class. Can be used to refer an other (or same) instance of the
     * implementing class. Used to establish relations between instances.
     * E.g. If implementor is an active-record, this id should be the primary key value.
     * 
     * If omitted use the object hashes to prevent circular references when collecting.
     *  
     * *name* (optional)
     * Name of this backup entry.
     * 
     * @return array
     */
    public function getBackupMetadata();
}