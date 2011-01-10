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
     * (non-PHPdoc)
     * @see Sysgear\StructuredData\Restorer.RestorerInterface::setDom()
     */
    public function setDom(\DOMDocument $domDocument)
    {
        $this->document = $domDocument;
        return $this;
    }
}