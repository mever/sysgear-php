<?php

namespace Sysgear\Tests\Backup;

use Sysgear\StructuredData\Collector\BackupCollector;
use Sysgear\StructuredData\Restorer\BackupRestorer;
use Sysgear\Backup\BackupableInterface;

class User implements BackupableInterface
{
    protected $id;

    /**
     * Test private field.
     *
     * @var string
     */
    private $name;

    protected $password;

    protected $employer;

    protected $roles = array();

    protected $sessions = array();

    protected $locale;

    public function __construct($id = null, $name = null, $password = null, Company $employer = null, Locale $locale = null)
    {
        $this->id = $id;
        $this->name = $name;
        $this->password = $password;
        $this->employer = $employer;
        if (null !== $employer) {
            $employer->addEmployee($this);
        }
        $this->locale = $locale;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getEmployer()
    {
        return $this->employer;
    }

    public function getRoles()
    {
        return $this->roles;
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
        $remaining = $backupDataRestorer->toObject($this);
        foreach ($remaining as $name => $value) {
            $this->{$name} = $value;
        }
    }
}