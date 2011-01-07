<?php

namespace Sysgear\Backup;

use Sysgear\Data\Collector\CollectorInterface;

interface BackupableInterface
{
    public function backup(CollectorInterface $dataCollector);
}