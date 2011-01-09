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
     * @param string $name Name used for $object in the collection.
     * 					   When no name is chosen, the class name of $object is used.
     */
    public function fromObject($object, $name = null);

    /**
     * Return the DOM document representation of the collected data.
     * 
     * @return \DOMDocument
     */
    public function getDomDocument();
}