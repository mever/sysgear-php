<?php

namespace Sysgear;

interface Comparable
{
    /**
     * Compares this object to another and tell if they are equal.
     *
     * @param Comparable $object
     * @return bool
     */
    public function equals(Comparable $object);
}