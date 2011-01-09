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
     * (non-PHPdoc)
     * @see Sysgear\StructuredData\Collector.CollectorInterface::scanObject()
     */
    public function scanObject($object, $name = null)
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

                // Scan scalar.
                if (is_scalar($value)) {
                    $this->element->setAttribute($name, $value);
                } else {
                    $this->scanCompositeProperty($object, $name, $value);
                }
            }
        }
        $this->document->appendChild($this->element);
    }

    /**
     * Scan composite object property.
     * 
     * @param \StdClass $object
     * @param string $name
     * @param mixed $value
     */
    protected function scanCompositeProperty($object, $name, $value)
    {
        // TODO: Scan none-scalar values.
    }

    /**
     * Return true if property can be collected, else return false.
     * 
     * @param \ReflectionProperty $property
     */
    protected function filterProperty(\ReflectionProperty $property)
    {
        // TODO: Allow configuration of the properties to filter. 
        return ('_' !== substr($property->getName(), 0, 1));
    }
}