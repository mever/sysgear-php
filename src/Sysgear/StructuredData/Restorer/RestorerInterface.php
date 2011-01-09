<?php

namespace Sysgear\StructuredData\Restorer;

interface RestorerInterface
{
    /**
     * Restore data to object.
     * 
     * @param \StdClass $object
     */
    public function toObject($object);
}