<?php

namespace Sysgear\StructuredData\Collector;

abstract class AbstractObjectCollector extends AbstractCollector
{
    /**
     * Each object which is collected is put on this list. That
     * way we prevent infinit loops in recursive collections.
     *
     * @var array
     */
    protected $excludedObjects = array();

    /**
     * Return the node name which represents this $object.
     *
     * @param object|string $object May be a class name
     * @return string
     */
    protected function getNodeName($object)
    {
        $fullClassname = is_string($object) ? $object : get_class($object);
        $pos = strrpos($fullClassname, '\\');
        return (false === $pos) ? $fullClassname : substr($fullClassname, $pos + 1);
    }

    /**
     * Return true if property can be collected, else return false.
     *
     * @param \ReflectionProperty $property
     */
    protected function filterProperty(\ReflectionProperty $property)
    {
        // TODO: Allow configuration of the properties to filter.
        //       For now hard code none-underscore-prefixed properties.
        return ('_' !== substr($property->getName(), 0, 1));
    }
}