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
     * @return \Sysgear\StructuredData\Node
     */
    public function fromObject($object, array $options = array());

    /**
     * Set persistent option.
     *
     * @param string $key
     * @param mixed $value
     */
    public function setOption($key, $value);

    /**
     * Return collected node.
     *
     * @return \Sysgear\StructuredData\Node
     */
    public function getNode();
}