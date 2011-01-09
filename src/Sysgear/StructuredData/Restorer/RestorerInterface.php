<?php

namespace Sysgear\StructuredData\Restorer;

/**
 * Responsible for restoring data.
 * 
 * @author (c) Martijn Evers <martijn4evers@gmail.com>
 */
interface RestorerInterface
{
    /**
     * Restore data to object.
     * 
     * @param \StdClass $object
     * @param string $name Name used for $object in the collection.
     * 					   When no name is chosen, the class name of $object is used.
     */
    public function toObject($object, $name = null);

    /**
     * Sets the DOM document which uses this restorer to restore the object.
     * 
     * @param \DOMDocument $domDocument
     */
    public function setDomDocument(\DOMDocument $domDocument);
}