<?php

namespace Sysgear\StructuredData\Restorer;

use Sysgear\StructuredData\Importer\ImporterInterface;

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
     * Set the DOM for restorer.
     * 
     * @param \DOMDocument $domDocument
     * @return \Sysgear\StructuredData\Restorer\RestorerInterface
     */
    public function setDom(\DOMDocument $domDocument);
}