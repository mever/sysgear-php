<?php

namespace Sysgear\Backup\Exporter;

use Sysgear\Data\Collector\CollectorInterface;

class XmlExporter implements ExporterInterface
{
    /**
     * @var \Sysgear\Data\Collector\CollectorInterface;
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
        $doc = $this->dataCollector->getDomDocument();
        $doc->formatOutput = $this->formatOutput;
        return $doc->saveXML();
    }
}