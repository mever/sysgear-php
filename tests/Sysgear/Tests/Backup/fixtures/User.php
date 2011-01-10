<?php

namespace Sysgear\Tests\Backup;

use Sysgear\StructuredData\Collector\BackupCollector;
use Sysgear\StructuredData\Restorer\BackupRestorer;
use Sysgear\Backup\BackupableInterface;

class User implements BackupableInterface
{
    protected $id;

    protected $name;

    protected $password;

    protected $employer;

    protected $roles = array();

    protected $locale;

    public function __construct($id, $name, $password, Company $employer, Locale $locale = null)
    {
        $this->id = $id;
        $this->name = $name;
        $this->password = $password;
        $this->employer = $employer;
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