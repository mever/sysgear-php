<?php

namespace Sysgear\StructuredData\Exporter;

use Sysgear\StructuredData\Collector\CollectorInterface;

class XmlExporter implements ExporterInterface
{
    /**
     * @var \DOMDocument
     */
    protected $document;

    /**
     * Flag if output should be pretty-print.
     * 
     * @param boolean $formatOutput
     * @return \Sysgear\StructuredData\Exporter\XmlExporter
     */
    public function formatOutput($formatOutput)
    {
        $this->document->formatOutput = (boolean) $formatOutput;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setDom(\DOMDocument $domDocument)
    {
        $this->document = $domDocument;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function toString()
    {
        if (null === $this->document) {
            throw ExporterException::noDataToExport();
        }
        return rtrim($this->document->saveXML());
    }
}