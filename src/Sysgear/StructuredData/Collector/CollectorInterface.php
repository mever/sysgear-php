<?php

namespace Sysgear\StructuredData\Collector;

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
     * @param string $name
     */
    public function fromObject($object, $name = null);

    /**
     * Return the DOM document.
     * 
     * @return \DOMDocument
     */
    public function getDomDocument();
}