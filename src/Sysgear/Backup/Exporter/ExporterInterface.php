<?php

namespace Sysgear\Backup\Exporter;

use Sysgear\StructuredData\Collector\CollectorInterface;

interface ExporterInterface
{
    /**
     * Return the export as string.
     * 
     * @return string
     */
    public function toString();

    /**
     * Return the export as string.
     * 
     * @param \Sysgear\StructuredData\Collector\CollectorInterface $dataCollector
     * @return \Sysgear\Backup\Exporter\ExporterInterface
     */
    public function readDataCollector(CollectorInterface $dataCollector);
}