<?php

namespace Sysgear\StructuredData\Collector;

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
     * In search of data to backup, do we need to recursively
     * scan for backupables?
     * 
     * @var boolean 
     */
    protected $recursiveScan = true;

    /**
     * To prevent the collector from infinitely scan recursive structures
     * collectors can be marked as duplicate. A duplicated collector is
     * a collector of an already collected object.
     * 
     * @var boolean
     */
    protected $duplicate = false;

    /**
     * Construct abstract data collector.
     */
    public function __construct()
    {
        $this->document = new \DOMDocument('1.0', 'utf8');
    }

    /**
     * Return the DOM document.
     * 
     * @return \DOMDocument
     */
    public function getDomDocument()
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