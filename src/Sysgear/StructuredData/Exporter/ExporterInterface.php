<?php

namespace Sysgear\StructuredData\Exporter;

use Sysgear\StructuredData\Collector\CollectorInterface;

interface ExporterInterface
{
    /**
     * Read a structed data collector so it can be exported.
     * 
     * @param \Sysgear\StructuredData\Collector\CollectorInterface $dataCollector
     * @return \Sysgear\StructuredData\Exporter\ExporterInterface
     */
    public function readDataCollector(CollectorInterface $dataCollector);

	/**
     * Return the exporter as string.
     * 
     * @return string
     */
    public function toString();
}