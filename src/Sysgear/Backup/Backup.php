<?php

namespace Sysgear\Backup;

use Sysgear\Data\Collector\CollectorInterface;
use Sysgear\Backup\Exporter\ExporterInterface;
use Sysgear\Backup\Importer\ImporterInterface;

/**
 * Universal tool to backup about anything.
 * 
 * @author (c) Martijn Evers <martijn4evers@gmail.com>
 */
class Backup
{
    /**
     * @var \Sysgear\Data\Collector\CollectorInterface;
     */
    protected $dataCollector;

    /**
     * @var \Sysgear\Backup\Exporter\ExporterInterface
     */
    protected $exporter;

    /**
     * @var \Sysgear\Backup\Importer\ImporterInterface
     */
    protected $importer;

    public function __construct(CollectorInterface $collector, ExporterInterface $exporter, ImporterInterface $importer)
    {
        $this->dataCollector = $collector;
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