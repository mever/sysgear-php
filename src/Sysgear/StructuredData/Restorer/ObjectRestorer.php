<?php

namespace Sysgear\StructuredData\Restorer;

/**
 * Restorer for data from objects.
 * 
 * @author (c) Martijn Evers <martijn4evers@gmail.com>
 */
class ObjectRestorer extends AbstractRestorer
{
    /**
     * (non-PHPdoc)
     * @see Sysgear\StructuredData\Restorer.RestorerInterface::toObject()
     */
    public function toObject($object, $name = null)
    {
        if (! is_object($object)) {
            throw new RestorerException("Given parameter is not an object.");
        }

        if (null === $name) {
            $fullClassname = get_class($object);
            $pos = strrpos($fullClassname, '\\');
            $name = (false === $pos) ? $fullClassname : substr($fullClassname, $pos + 1);
        }
    }
}