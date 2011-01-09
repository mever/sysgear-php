<?php

namespace Sysgear\StructuredData\Exporter;

use Sysgear\StructuredData\Collector\CollectorInterface;

class XmlExporter implements ExporterInterface
{
    /**
     * @var \Sysgear\StructuredData\Collector\CollectorInterface;
     */
    protected $dataCollector;

    /**
     * Pretty print XML output.
     * 
     * @var boolean
     */
    protected $formatOutput = true;

    /**
     * {@inheritDoc}
     */
    public function readDataCollector(CollectorInterface $dataCollector)
    {
        $this->dataCollector = $dataCollector;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function toString()
    {
        if (null === $this->dataCollector) {
            throw ExporterException::noDataToExport();
        }
        $doc = $this->dataCollector->getDomDocument();
        $doc->formatOutput = $this->formatOutput;
        return $doc->saveXML();
    }
}