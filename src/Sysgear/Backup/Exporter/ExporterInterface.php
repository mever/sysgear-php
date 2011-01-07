<?php

namespace Sysgear\Backup\Exporter;

use Sysgear\Data\Collector\CollectorInterface;

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
     * @param \Sysgear\Data\Collector\CollectorInterface $dataCollector
     * @return \Sysgear\Backup\Exporter\ExporterInterface
     */
    public function readDataCollector(CollectorInterface $dataCollector);
}