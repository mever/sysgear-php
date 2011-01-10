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