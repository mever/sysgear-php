<?php

namespace Sysgear\StructuredData;

use Sysgear\StructuredData\Collector\CollectorInterface;
use Sysgear\StructuredData\Restorer\RestorerInterface;

/**
 * Implementations of this interface can be used as a structed data collection.
 * 
 * @author (c) Martijn Evers <martijn4evers@gmail.com>
 */
interface CollectionInterface
{
    /**
     * Collect structed data using a collector.
     * 
     * @param \Sysgear\StructuredData\Collector\CollectorInterface $dataCollector
     */
    public function collectStructedData(CollectorInterface $dataCollector);

    /**
     * Restore structed data using a restorer.
     * 
     * @param Sysgear\StructuredData\Restorer\RestorerInterface $dataRestorer
     */
//    public function restoreStructedData(RestorerInterface $dataRestorer);
}