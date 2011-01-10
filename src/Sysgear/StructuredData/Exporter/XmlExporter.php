<?php

namespace Sysgear\StructuredData\Exporter;

class XmlExporter extends AbstractExporter
{
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
     * (non-PHPdoc)
     * @see Sysgear\StructuredData\Exporter.ExporterInterface::toString()
     */
    public function toString()
    {
        if (null === $this->document) {
            throw ExporterException::noDataToExport();
        }
        return rtrim($this->document->saveXML());
    }
}