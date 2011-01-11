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
}