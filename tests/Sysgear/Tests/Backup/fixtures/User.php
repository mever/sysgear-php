<?php

namespace Sysgear\Tests\Backup;

use Sysgear\StructuredData\Collector\BackupCollector;
use Sysgear\StructuredData\Restorer\BackupRestorer;
use Sysgear\Backup\BackupableInterface;

class User implements BackupableInterface
{
    protected $id;

    private $name;

    protected $password;

    protected $employer;

    protected $roles = array();

    protected $locale;

    public function __construct($id = null, $name = null, $password = null, Company $employer = null, Locale $locale = null)
    {
        $this->id = $id;
        $this->name = $name;
        $this->password = $password;
        $this->employer = $employer;
        $this->locale = $locale;
    }

    public function getName()
    {
        return $this->name;
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
        $remaining = $backupDataRestorer->toObject($this);
        foreach ($remaining as $name => $value) {
            $this->{$name} = $value;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getPrimaryPropertyName()
    {
        return 'id';
    }
}