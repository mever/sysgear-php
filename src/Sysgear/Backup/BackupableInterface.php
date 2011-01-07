<?php

namespace Sysgear\Backup;

use Sysgear\StructuredData\Collector\CollectorInterface;

interface BackupableInterface
{
    public function backup(CollectorInterface $dataCollector);
}