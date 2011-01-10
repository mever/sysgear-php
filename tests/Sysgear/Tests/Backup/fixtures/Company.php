<?php

namespace Sysgear\Tests\Backup;

use Sysgear\StructuredData\Collector\BackupCollector;
use Sysgear\StructuredData\Restorer\BackupRestorer;
use Sysgear\Backup\BackupableInterface;

class Company implements BackupableInterface
{
    public $id;
 
    public $name;

    public $locale;

    public $functions = array();

    public $employees = array();

    public function __construct($id = null, $name = null, Locale $locale = null)
    {
        $this->id = $id;
        $this->name = $name;
        $this->locale = $locale;
    }

    /**
     * {@inheritDoc}
     */
    public function collectStructedData(BackupCollector $backupDataCollector)
    {
        $backupDataCollector->fromObject($this);
    }

    /**
     * {@inheritDoc}
     */
    public function restoreStructedData(BackupRestorer $backupDataRestorer)
    {
        $backupDataRestorer->toObject($this);
    }

    /**
     * {@inheritDoc}
     */
    public function getPrimaryPropertyName()
    {
        return 'id';
    }
}