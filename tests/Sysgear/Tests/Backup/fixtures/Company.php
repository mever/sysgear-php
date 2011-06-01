<?php

namespace Sysgear\Tests\Backup;

use Sysgear\StructuredData\Collector\BackupCollector;
use Sysgear\StructuredData\Restorer\BackupRestorer;
use Sysgear\Backup\BackupableInterface;

class Company implements BackupableInterface
{
    public $id;

    /**
     * Test protected field.
     *
     * @var string
     */
    protected $name;

    public $locale;

    public $functions = array();

    protected $employees = array();

    public function __construct($id = null, $name = null, Locale $locale = null)
    {
        $this->id = $id;
        $this->name = $name;
        $this->locale = $locale;
    }

    public function getName()
    {
        return $this->name;
    }

    public function addEmployee(User $employee)
    {
        $this->employees[] = $employee;
    }

    public function getEmployee($index)
    {
        return $this->employees[$index];
    }

    /**
     * {@inheritDoc}
     */
    public function collectStructedData(BackupCollector $backupDataCollector, array $options = array())
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
}