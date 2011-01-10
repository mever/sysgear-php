<?php

namespace Sysgear\Backup;

use Sysgear\StructuredData\Collector\BackupCollector;
use Sysgear\StructuredData\Restorer\BackupRestorer;
use Sysgear\StructuredData\Exporter\ExporterInterface;
use Sysgear\StructuredData\Importer\ImporterInterface;

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
     * @var \Sysgear\StructuredData\Exporter\ExporterInterface
     */
    protected $exporter;

    /**
     * @var \Sysgear\StructuredData\Importer\ImporterInterface
     */
    protected $importer;

    /**
     * Create backup utility.
     * 
     * @param \Sysgear\StructuredData\Exporter\ExporterInterface $exporter
     * @param \Sysgear\StructuredData\Importer\ImporterInterface $importer
     */
    public function __construct(ExporterInterface $exporter, ImporterInterface $importer)
    {
        $this->exporter = $exporter;
        $this->importer = $importer;
    }

    /**
     * Backup collection of stuctured data from $object.
     * 
     * @param \Sysgear\Backup\BackupableInterface $object
     * @param \Sysgear\StructuredData\Exporter\ExporterInterface $exporter
     * @return \Sysgear\StructuredData\Exporter\ExporterInterface
     */
    public function backup(BackupableInterface $object, ExporterInterface $exporter = null)
    {
        $collector = new BackupCollector();
        $object->collectStructedData($collector);

        $exporter = $this->getExporter($exporter);
        $collector->writeExport($exporter);
        return $exporter;
    }

    /**
     * Restore collection of structed data to $object.
     * 
     * @param \Sysgear\Backup\BackupableInterface $object
     * @param \Sysgear\StructuredData\Importer\ImporterInterface $importer
     * @return \Sysgear\Backup\BackupableInterface
     */
    public function restore(BackupableInterface $object, ImporterInterface $importer = null)
    {
        $restorer = new BackupRestorer();
        $restorer->readImport($this->getImporter($importer));

        $object->restoreStructedData($restorer);
        return $object;
    }

    /**
     * Return exporter.
     * 
     * @param \Sysgear\StructuredData\Exporter\ExporterInterface $exporter
     */
    protected function getExporter(ExporterInterface $exporter = null)
    {
        if (null === $exporter) {
            $exporter = $this->exporter;
        }
        return $exporter;
    }

    /**
     * Return importer.
     * 
     * @param \Sysgear\StructuredData\Importer\ImporterInterface $importer
     */
    protected function getImporter(ImporterInterface $importer = null)
    {
        if (null === $importer) {
            $importer = $this->importer;
        }
        return $importer;
    }
}