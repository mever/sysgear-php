<?php

namespace Sysgear\StructuredData\Exporter;

use Sysgear\StructuredData\Collector\CollectorInterface;

interface ExporterInterface
{
    /**
     * Set the DOM to export.
     * 
     * @param \DOMDocument $domDocument
     * @return \Sysgear\StructuredData\Exporter\ExporterInterface
     */
    public function setDom(\DOMDocument $domDocument);

	/**
     * Return the exporter as string.
     * 
     * @return string
     */
    public function __toString();
}