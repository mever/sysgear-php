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

    /**
     * Return the node name which represents this $object.
     * 
     * @param object $object
     * @return string
     */
    protected function getNodeName($object)
    {
        $fullClassname = get_class($object);
        $pos = strrpos($fullClassname, '\\');
        return (false === $pos) ? $fullClassname : substr($fullClassname, $pos + 1);
    }
}