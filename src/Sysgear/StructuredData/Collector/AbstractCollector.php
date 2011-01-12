<?php

namespace Sysgear\StructuredData\Collector;

use Sysgear\StructuredData\Exporter\ExporterInterface;

abstract class AbstractCollector implements CollectorInterface
{
    /**
     * @var \DOMDocument
     */
    protected $document;

    /**
     * @var \DOMElement
     */
    protected $element;

    /**
     * Each object which is collected is put on this list. That
     * way we prevent infinit loops in recursive collections.
     * 
     * @var array
     */
    protected $excludedObjects = array();

    /**
     * In search of data to backup, do we need to recursively
     * scan for backupables?
     * 
     * @var boolean 
     */
    protected $recursiveScan = true;

    /**
     * Construct data collector.
     * 
     * @param array $options
     */
    public function __construct(array $options = array())
    {
        $this->document = new \DOMDocument('1.0', 'utf8');
        foreach ($options as $key => $value) {
            $this->setOption($key, $value);
        }
    }

    /**
     * Set option.
     * 
     * @param string $key
     * @param mixed $value
     */
    public function setOption($key, $value)
    {
    }

	/**
	 * (non-PHPdoc)
	 * @see Sysgear\StructuredData\Collector.CollectorInterface::getDom()
	 */
    public function getDom()
    {
        return $this->document;
    }

    /**
     * Return the DOM element.
     * 
     * @return \DOMDocument
     */
    public function getDomElement()
    {
        return $this->element;
    }

    /**
     * Return the node name which represents this $object.
     * 
     * @param mixed $object May be a class name
     * @return string
     */
    protected function getNodeName($object)
    {
        $fullClassname = is_string($object) ? $object : get_class($object);
        $pos = strrpos($fullClassname, '\\');
        return (false === $pos) ? $fullClassname : substr($fullClassname, $pos + 1);
    }
}