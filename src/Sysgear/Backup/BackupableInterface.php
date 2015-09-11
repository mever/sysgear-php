<?php

namespace Sysgear\Backup;

use Sysgear\StructuredData\Collector\BackupCollector,
    Sysgear\StructuredData\Restorer\BackupRestorer;

/**
 * Implementations of this interface can be backed up if it is passed
 * a BackupCollector and restored when passed a BackupRestorer.
 *
 * @author (c) Martijn Evers <martijn4evers@gmail.com>
 */
interface BackupableInterface
{
    /**
     * Collect structured data using a backup collector.
     *
     * @param BackupCollector $backupDataCollector
     * @param mixed[] $options Map of options for the collector instance.
     */
    public function collectStructuredData(BackupCollector $backupDataCollector, array $options = array());

    /**
     * Restore structured data using a backup restorer.
     *
     * @param BackupRestorer $backupDataRestorer
     */
    public function restoreStructuredData(BackupRestorer $backupDataRestorer);
}