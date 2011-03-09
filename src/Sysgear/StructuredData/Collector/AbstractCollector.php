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
     * In search of data, do we need to recursively scan?
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
        switch ($key) {
        case "recursiveScan":
            $this->recursiveScan = (boolean) $value;
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