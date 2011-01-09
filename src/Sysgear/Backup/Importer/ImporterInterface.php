<?php

namespace Sysgear\Backup\Importer;

use Sysgear\StructuredData\Restorer\RestorerInterface;

interface ImporterInterface
{
    /**
     * Write structed data to a restorer.
     * 
     * @param \Sysgear\StructuredData\Restorer\RestorerInterface $dataRestorer
     */
    public function writeDataCollector(RestorerInterface $dataRestorer);
}