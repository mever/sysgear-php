<?php

namespace Sysgear\StructuredData\Collector;

use Sysgear\StructuredData\Exporter\ExporterInterface;

/**
 * Responsible for collecting data.
 * 
 * @author (c) Martijn Evers <martijn4evers@gmail.com>
 */
interface CollectorInterface
{
    /**
     * Collect data from object.
     * 
     * @param \StdClass $object
     * @param string $name Name used for $object in the collection.
     * 					   When no name is chosen, the class name of $object is used.
     * @return \Sysgear\StructuredData\Collector\CollectorInterface
     */
    public function fromObject($object, $name = null);

    /**
     * Write structured data collector to exporter.
     * 
     * @param \Sysgear\StructuredData\Exporter\ExporterInterface $exporter
     * @return \Sysgear\StructuredData\Collector\CollectorInterface
     */
    public function writeExport(ExporterInterface $exporter);
}