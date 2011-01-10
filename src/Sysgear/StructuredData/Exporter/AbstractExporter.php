<?php

namespace Sysgear\StructuredData\Exporter;

abstract class AbstractExporter implements ExporterInterface
{
    /**
     * @var \DOMDocument
     */
    protected $document;

    /**
     * (non-PHPdoc)
     * @see Sysgear\StructuredData\Exporter.ExporterInterface::setDom()
     */
    public function setDom(\DOMDocument $domDocument)
    {
        $this->document = $domDocument;
        return $this;
    }
}