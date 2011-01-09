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
     * Namespaces.
     * 
     * @var string
     */
    const NS_PROPERTY = 'P';
    const NS_METADATA = 'M';

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

        // Add this object to the list of excluded objects to
        // prevent infinite recursive collecting.
        $this->excludedObjects[] = $object;

        $this->element = $this->document->createElement($name);
        $this->element->setAttribute(self::NS_METADATA . 'class', get_class($object));
        $refClass = new \ReflectionClass($object);
        foreach ($refClass->getProperties() as $property) {

            if ($this->filterProperty($property)) {

                $property->setAccessible(true);
                $name = $property->getName();
                $value = $property->getValue($object);

                // Scan scalar or composite property.
                if (is_scalar($value)) {
                    $this->scanScalarProperty($object, self::NS_PROPERTY . $name, $this->toStringFromScalar($value));
                } elseif ($this->recursiveScan) {
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
    protected function scanScalarProperty($object, $name, $value)
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
        // Scan object.
        if (is_object($value)) {
            $this->addChildObject($value);
        }

        // Scan sub-collection.
        if (is_array($value) || ($value instanceof \IteratorAggregate)) {

            $collection = $this->document->createElement($name);
            $this->element->appendChild($collection);

            foreach ($value as $elem) {

                // Collect array element objects.
                if (is_object($elem)) {
                    $this->addChildObject($elem, $collection);
                }
            }
        }
    }

    /**
     * Add child node to this collection.
     * 
     * @param object $object
     * @param \DOMNode $node
     */
    protected function addChildObject($object, \DOMNode $node = null)
    {
        if (null === $node) {
            $node = $this->element;
        }

        // Prevent infinite loops...
        if (in_array($object, $this->excludedObjects, true)) {

            $this->createReference($object, $node);
        } else {

            // Make a copy of this collector to allow recursive collecting.
            $collector = clone $this;
            $collector->fromObject($object);
            $node->appendChild($collector->getDomElement());
        }
    }

    /**
     * Create a reference to an already collected object.
     * 
     * @param object $object
     * @param \DOMNode $node
     */
    protected function createReference($object, \DOMNode $node)
    {
        $collector = clone $this;
        $collector->recursiveScan = false;
        $collector->fromObject($object);
        $node->appendChild($collector->getDomElement());
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

    /**
     * Cast scalar to string value before storing.
     * 
     * @param mixed $value
     * @return string
     */
    protected function toStringFromScalar($value)
    {
        return serialize($value);
    }
}