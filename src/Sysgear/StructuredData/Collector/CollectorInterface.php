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
     * @param mixed[] $options Map of global options.
     */
    public function __construct(array $options = array());

    /**
     * Collect data from object.
     *
     * @param \StdClass $object
     * @param mixed[] $options Map of instance options
     * @return \Sysgear\StructuredData\Collector\CollectorInterface
     */
    public function fromObject($object, array $options = array());
}