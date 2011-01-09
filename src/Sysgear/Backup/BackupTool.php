<?php

namespace Sysgear\Backup;

use Sysgear\StructuredData\Collector\BackupableCollector;
use Sysgear\StructuredData\Restorer\BackupableRestorer;
use Sysgear\Backup\Exporter\ExporterInterface;
use Sysgear\Backup\Importer\ImporterInterface;

/**
 * Universal tool to backup about anything.
 * 
 * * Uses a structured data collector to backup data and pass it to the exporter.
 * * Uses an importer to import a backup and restore it using a restorer.
 * 
 * @author (c) Martijn Evers <martijn4evers@gmail.com>
 */
class BackupTool
{
    /**
     * @var \Sysgear\Backup\Exporter\ExporterInterface
     */
    protected $exporter;

    /**
     * @var \Sysgear\Backup\Importer\ImporterInterface
     */
    protected $importer;

    /**
     * Create backup utility.
     * 
     * @param \Sysgear\Backup\Exporter\ExporterInterface $exporter
     * @param \Sysgear\Backup\Importer\ImporterInterface $importer
     */
    public function __construct(ExporterInterface $exporter, ImporterInterface $importer)
    {
        $this->exporter = $exporter;
        $this->importer = $importer;
    }

    /**
     * Backup collection of stuctured data from $object.
     * 
     * @param \Sysgear\Backup\BackupableInterface $collector
     * @param \Sysgear\Backup\Exporter\ExporterInterface $exporter
     * @return \Sysgear\Backup\Exporter\ExporterInterface
     */
    public function backup(BackupableInterface $object, ExporterInterface $exporter = null)
    {
        $collector = new BackupableCollector();
        $object->collectStructedData($collector);
        
        $exporter = $this->getExporter($exporter);
        $exporter->readDataCollector($collector);
        return $exporter;
    }

    /**
     * Restore collection of structed data to $object.
     * 
     * @param BackupableInterface $object
     */
    public function restore(BackupableInterface $object)
    {
        $restorer = new BackupableRestorer();
        $this->importer->writeDataCollector($restorer);
        $object->restoreStructedData($restorer);
        return $this;
    }

    /**
     * Return backup exporter.
     * 
     * @param \Sysgear\Backup\Exporter\ExporterInterface $exporter
     */
    protected function getExporter(ExporterInterface $exporter = null)
    {
        if (null === $exporter) {
            $exporter = $this->exporter;
        }
        return $exporter;
    }
}