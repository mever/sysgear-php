<?php

namespace Sysgear\StructuredData\Restorer;

use Sysgear\StructuredData\Importer\ImporterInterface;

abstract class AbstractRestorer implements RestorerInterface
{
    /**
     * @var \DOMDocument
     */
    protected $document;

    /**
     * Read structured data import to restorer.
     * 
     * @param \Sysgear\StructuredData\Importer\ImporterInterface $importer
     * @return \Sysgear\StructuredData\Restorer\RestorerInterface
     */
    public function readImport(ImporterInterface $importer)
    {
        $this->document = $importer->getDom();
        return $this;
    }
}