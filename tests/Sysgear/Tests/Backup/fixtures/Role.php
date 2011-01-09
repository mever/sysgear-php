<?php

namespace Sysgear\Tests\Backup;

use Sysgear\StructuredData\Collector\CollectorInterface;
use Sysgear\StructuredData\Restorer\RestorerInterface;
use Sysgear\Backup\BackupableInterface;

class Role implements BackupableInterface
{
    public $id;

    public $name;

    public $members = array();

    public $company;

    public function __construct($id, $name, Company $company)
    {
        $this->id = $id;
        $this->name = $name;
        $this->company = $company;
    }

    /**
     * {@inheritDoc}
     */
    public function collectStructedData(CollectorInterface $collector)
    {
        $collector->fromObject($this, 'role');
    }

    /**
     * {@inheritDoc}
     */
    public function restoreStructedData(RestorerInterface $restorer)
    {
        $restorer->toObject($this, 'role');
    }

    /**
     * {@inheritDoc}
     */
    public function getPrimaryPropertyName()
    {
        return 'id';
    }
}