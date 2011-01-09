<?php

namespace Sysgear\StructuredData\Importer;

use Sysgear\StructuredData\Restorer\RestorerInterface;

class XmlImporter implements ImporterInterface
{
    /**
     * @var \DOMDocument
     */
    protected $document;

	/**
	 * {@inheritDoc}
     */
    public function writeDataCollector(RestorerInterface $dataRestorer)
    {
        $dataRestorer->setDomDocument($this->document);
        return $this;
    }

	/**
     * {@inheritDoc}
     */
    public function fromString($string)
    {
        if (null === $this->document) {
            $this->document = new \DOMDocument();
        }
        $this->document->loadXML($string);
        return $this;
    }
}