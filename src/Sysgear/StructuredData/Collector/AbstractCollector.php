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
     * The descent level of the object graph.
     *
     * 0 is infinite descending
     * 1 is only properties
     * n is descending into the graph for n nodes
     *
     * @var integer
     */
    protected $descentLevel = 0;

    /**
     * Construct data collector.
     *
     * @param array $options
     */
    public function __construct(array $options = array())
    {
        $this->document = new \DOMDocument('1.0', 'UTF-8');
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
        switch ($key) {
        case "descentLevel":
            $this->descentLevel = (integer) $value;
            break;
        }
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
}