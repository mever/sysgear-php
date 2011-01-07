<?php

namespace Sysgear\Backup;

use Sysgear\StructuredData\Collector\CollectorInterface;
use Sysgear\StructuredData\Restorer\RestorerInterface;
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
class Backup
{
    /**
     * @var \Sysgear\StructuredData\Collector\CollectorInterface;
     */
    protected $dataCollector;

    /**
     * @var \Sysgear\StructuredData\Restorer\RestorerInterface;
     */
    protected $dataRestorer;

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
     * @param \Sysgear\StructuredData\Collector\CollectorInterface $collector
     * @param \Sysgear\StructuredData\Restorer\RestorerInterface $restorer
     * @param \Sysgear\Backup\Exporter\ExporterInterface $exporter
     * @param \Sysgear\Backup\Importer\ImporterInterface $importer
     */
    public function __construct(CollectorInterface $collector, RestorerInterface $restorer,
        ExporterInterface $exporter, ImporterInterface $importer)
    {
        $this->dataCollector = $collector;
        $this->dataRestorer = $restorer;
        $this->exporter = $exporter;
        $this->importer = $importer;
    }

    /**
     * Return backup as string.
     * 
     * @return string
     */
    public function exportToString()
    {
        return $this->exporter->readDataCollector($this->dataCollector)->toString();
    }
}