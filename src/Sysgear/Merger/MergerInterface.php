<?php

namespace Sysgear\Merger;

/**
 * Merge object with another system.
 *
 * @author (c) Martijn Evers <martijn4evers@gmail.com>
 */
interface MergerInterface
{
    /**
     * Merges the state of $object and returns the managed copy of the object.
     *
     * @param object $object The object to merge.
     * @return object|null The managed copy of the object or null if merge failed.
     */
    public function merge($object);

    /**
     * Find a similar object which already exist in the target system.
     *
     * @param object $object Similar object to search for in the target system.
     * @return object|null
     */
    public function find($object);

    /**
     * Returns all mandatory properties according the target system.
     *
     * @param object $object
     */
    public function getMandatoryProperties($object);

    /**
     * Apply changes to the target system.
     */
    public function flush();
}