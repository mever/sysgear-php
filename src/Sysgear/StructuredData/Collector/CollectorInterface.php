<?php

namespace Sysgear\Data\Collector;

/**
 * Responsible for collecting data.
 * 
 * @author (c) Martijn Evers <martijn4evers@gmail.com>
 */
interface CollectorInterface
{
    /**
     * Scan object for data.
     * 
     * @param mixed $object
     * @param string $name
     */
    public function scanObject($object, $name = null);

    /**
     * Return the DOM document.
     * 
     * @return \DOMDocument
     */
    public function getDomDocument();
}