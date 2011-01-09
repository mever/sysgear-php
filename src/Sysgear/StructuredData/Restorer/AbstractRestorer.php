<?php

namespace Sysgear\StructuredData\Restorer;

abstract class AbstractRestorer implements RestorerInterface
{
    /**
     * @var \DOMDocument
     */
    protected $document;

    /**
     * Sets the DOM document which uses this restorer to restore the object.
     * 
     * @param \DOMDocument $domDocument
     */
    public function setDomDocument(\DOMDocument $document)
    {
        $this->document = $document;
        return $this;
    }
}