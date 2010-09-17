<?php

namespace Sysgear;

interface Comparable
{
    /**
     * Compares this object to $other and tell if they are equal.
     * 
     * @param $other
     * @return bool
     */
    public function equals($other);
}