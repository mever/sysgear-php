<?php

namespace Sysgear\Backup\Importer;

use Sysgear\StructuredData\Restorer\RestorerInterface;

class XmlImporter implements ImporterInterface
{
	/**
	 * {@inheritDoc}
     */
    public function writeDataCollector(RestorerInterface $dataRestorer)
    {
    }
}