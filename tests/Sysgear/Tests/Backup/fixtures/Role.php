<?php

namespace Sysgear\Tests\Backup;

use Sysgear\StructuredData\Collector\BackupCollector;
use Sysgear\StructuredData\Restorer\BackupRestorer;
use Sysgear\Backup\BackupableInterface;

class Role implements BackupableInterface
{
    public $id;

    public $name;

    public $members = array();

    public $company;

    public function __construct($id = null, $name = null, Company $company = null)
    {
        $this->id = $id;
        $this->name = $name;
        $this->company = $company;
    }

    /**
     * {@inheritDoc}
     */
    public function collectStructuredData(BackupCollector $backupDataCollector, array $options = array())
    {
        $backupDataCollector->fromObject($this);
    }

    /**
     * {@inheritDoc}
     */
    public function restoreStructuredData(BackupRestorer $backupDataRestorer)
    {
        $backupDataRestorer->toObject($this);
    }
}