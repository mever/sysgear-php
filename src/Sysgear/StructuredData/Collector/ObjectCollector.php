<?php

namespace Sysgear\StructuredData\Collector;

/**
 * Collector for data from objects.
 * 
 * @author (c) Martijn Evers <martijn4evers@gmail.com>
 */
class ObjectCollector extends AbstractCollector
{
    /**
     * Each object which is collected is put on this list. That
     * way we prevent infinit loops in recursive collections.
     * 
     * @var array
     */
    protected $excludedObjects = array();

    /**
     * (non-PHPdoc)
     * @see Sysgear\StructuredData\Collector.CollectorInterface::fromObject()
     */
    public function fromObject($object, $name = null)
    {
        if (! is_object($object)) {
            throw new CollectorException("Given parameter is not an object.");
        }

        if (null === $name) {
            $fullClassname = get_class($object);
            $pos = strrpos($fullClassname, '\\');
            $name = (false === $pos) ? $fullClassname : substr($fullClassname, $pos + 1);
        }

        $this->element = $this->document->createElement($name);
        $refClass = new \ReflectionClass($object);
        foreach ($refClass->getProperties() as $property) {

            if ($this->filterProperty($property)) {

                $property->setAccessible(true);
                $name = $property->getName();
                $value = $property->getValue($object);

                // Scan scalar or composite property.
                if (is_scalar($value)) {
                    $this->scanScalarProperty($object, $name, $value);
                } else {
                    $this->scanCompositeProperty($object, $name, $value);
                }
            }
        }
        $this->document->appendChild($this->element);
    }

    /**
     * Scan scalar object property.
     * 
     * @param \StdClass $object
     * @param string $name
     * @param scalar $value
     */
    public function scanScalarProperty($object, $name, $value)
    {
        $this->element->setAttribute($name, $value);
    }

    /**
     * Scan composite object property.
     * 
     * @param \StdClass $object
     * @param string $name
     * @param composite $value
     */
    protected function scanCompositeProperty($object, $name, $value)
    {
        // TODO: Scan composite values and add them to the $excludedObjects list.
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