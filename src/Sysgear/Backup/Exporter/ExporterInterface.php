<?php

namespace Sysgear\Backup\Exporter;

use Sysgear\StructuredData\Collector\CollectorInterface;

interface ExporterInterface
{
    /**
     * Read a structed data collector so it can be exported.
     * 
     * @param \Sysgear\StructuredData\Collector\CollectorInterface $dataCollector
     * @return \Sysgear\Backup\Exporter\ExporterInterface
     */
    public function readDataCollector(CollectorInterface $dataCollector);

	/**
     * Return the export as string.
     * 
     * @return string
     */
    public function toString();
}