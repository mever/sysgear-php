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
    protected $addedObjects = array();

    /**
     * In search of data, do we need to follow composite nodes?
     *
     * @var boolean
     */
    protected $followCompositeNodes = true;

    /**
     * Array of properties to ignore.
     *
     * @var string[]
     */
    protected $ignore = array();

    /**
     * Array of properties. When set
     * only those properties will be collected.
     *
     * @var string[] | null
     */
    protected $onlyInclude;

    /**
     * Do not descent into the properties of the collected node properties.
     *
     * @var string[]
     */
    protected $doNotDescent = array();

    /**
     * Name of class to collect.
     *
     * @var string
     */
    protected $className;

    /**
     * Set option.
     *
     * @param string $key
     * @param mixed $value
     */
    protected function _setOption($key, $value)
    {
        switch ($key) {
            case 'followNodes':
                $this->followNodes = (boolean) $value;
                break;

            case "doNotDescent":
                $this->doNotDescent = (array) $value;
                break;

            case "ignore":
                $this->ignore = (array) $value;
                break;

            case "onlyInclude":
                $this->onlyInclude = (null === $value) ? null : (array) $value;
                break;

            default:
                parent::_setOption($key, $value);
        }
    }

    /**
     * Return true if property can be collected, else return false.
     *
     * @param \ReflectionProperty $property
     */
    protected function filterProperty(\ReflectionProperty $property)
    {
        $name = $property->getName();

        // TODO: Allow configuration of the properties to filter.
        //       For now hard code none-underscore-prefixed properties.
        if ('_' === substr($name, 0, 1)) {
            return false;
        }

        if (null !== $this->onlyInclude && (! in_array($name, $this->onlyInclude, true))) {
            return false;
        }

        if (in_array($name, $this->ignore, true) ) {
            return false;
        }

        return true;
    }

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
     * Works its way up upon the inheritance tree from sub to super class and returns
     * the name of the first class implementing the given $interface.
     *
     * @param object|class $object Object or classname
     * @param string $interface
     */
    protected function getFirstClassnameImplementing($object, $interface)
    {
        // Fetches the oldest parent name which implements the backupable interface.
        $previousClass = $class = get_class($object);
        while (false !== $class) {
            $refClass = new \ReflectionClass($class);
            if (! $refClass->implementsInterface($interface)) {
                break;
            }
            $previousClass = $class;
            $class = get_parent_class($class);
        }
        return $previousClass;
    }
}