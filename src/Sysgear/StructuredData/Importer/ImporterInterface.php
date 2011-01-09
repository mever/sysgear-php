<?php

namespace Sysgear\StructuredData\Importer;

use Sysgear\StructuredData\Restorer\RestorerInterface;

interface ImporterInterface
{
    /**
     * Write structed data to a restorer.
     * 
     * @param \Sysgear\StructuredData\Restorer\RestorerInterface $dataRestorer
     * @return \Sysgear\StructuredData\Importer\ImporterInterface
     */
    public function writeDataCollector(RestorerInterface $dataRestorer);

	/**
     * Import the importer from string.
     * 
     * @param string $string
     * @return \Sysgear\StructuredData\Importer\ImporterInterface
     */
    public function fromString($string);
}