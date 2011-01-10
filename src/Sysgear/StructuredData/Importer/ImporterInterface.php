<?php

namespace Sysgear\StructuredData\Importer;

use Sysgear\StructuredData\Restorer\RestorerInterface;

interface ImporterInterface
{
    /**
     * Get DOM from importer.
     * 
     * @return \DOMDocument
     */
    public function getDom();

	/**
     * Import the importer from string.
     * 
     * @param string $string
     * @return \Sysgear\StructuredData\Importer\ImporterInterface
     */
    public function fromString($string);
}