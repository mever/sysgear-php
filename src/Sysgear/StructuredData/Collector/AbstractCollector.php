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
     * Construct abstract data collector.
     */
    public function __construct()
    {
        $this->document = new \DOMDocument('1.0', 'utf8');
    }

    /**
     * Write structured data exporter.
     * 
     * @param \Sysgear\StructuredData\Exporter\ExporterInterface $exporter
     * @return \Sysgear\StructuredData\Collector\CollectorInterface
     */
    public function writeExport(ExporterInterface $exporter)
    {
        $exporter->setDom($this->document);
        return $this;
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