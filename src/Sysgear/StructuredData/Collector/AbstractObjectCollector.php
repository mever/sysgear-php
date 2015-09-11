<?php

namespace Sysgear\StructuredData\Collector;

use Sysgear\Util;

abstract class AbstractObjectCollector extends AbstractCollector
{
    /**
     * Each object which is collected is put on this list. That
     * way we prevent infinite loops in recursive collections.
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
     * Use this option to skip subclasses which implement certain interfaces. It can
     * be used to skip collecting from sub-classes that implement an interface. E.g.
     * the Doctrine proxy wrapper.
     *
     * @var string[]
     */
    protected $skipInterfaces = array(
        'Doctrine\Common\Persistence\Proxy',
        'Doctrine\ORM\Proxy\Proxy'
    );

    /**
     * Set option.
     *
     * @param string $key
     * @param mixed $value
     */
    protected function _setOption($key, $value)
    {
        switch ($key) {
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
     * @return boolean
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
     * Return the node name which represents this $class.
     *
     * @param object|string $class May be a class name
     * @return string
     */
    protected function getNodeName($class)
    {
        if (is_object($class)) {
            $class = $this->getClass($class);
        }

        return Util::getShortClassName($class);
    }

    /**
     * Return the class name of the node to collect.
     *
     * @param object|string $object Class name or class instance.
     * @return string
     */
    protected function getClass($object)
    {
        $class = (is_object($object) ? get_class($object) : $object);
        if ($this->skipInterfaces) {
            while (false !== $class) {
                if (array_intersect(class_implements($class), $this->skipInterfaces)) {
                    $class = get_parent_class($class);

                } else {
                    break;
                }
            }
        }

        return $class;
    }

    /**
     * Works its way up upon the inheritance tree from sub to super class and returns
     * the name of the first class implementing the given $interface.
     *
     * @param object|class $object Object or class name
     * @param string $interface
     * @return string
     */
    protected function getFirstClassNameImplementing($object, $interface)
    {
        // Fetches the oldest parent name which implements the backupable interface.
        $previousClass = $class = (is_object($object) ? get_class($object) : $object);
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