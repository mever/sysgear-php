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
     * Construct data collector.
     *
     * @param array $options
     */
    public function __construct(array $options = array());

    /**
     * Collect data from object.
     *
     * @param \StdClass $object
     * @return \Sysgear\StructuredData\Collector\CollectorInterface
     */
    public function fromObject($object);

    /**
     * Get DOM from collector.
     *
     * @return \DOMDocument
     */
    public function getDom();
}