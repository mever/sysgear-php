<?php

namespace Sysgear\Tests\Backup;

use Sysgear\StructuredData\Collector\CollectorInterface;
use Sysgear\StructuredData\Restorer\RestorerInterface;
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
    public function collectStructedData(CollectorInterface $collector)
    {
        $collector->fromObject($this, 'user');
    }

    /**
     * {@inheritDoc}
     */
    public function restoreStructedData(RestorerInterface $restorer)
    {
        $restorer->toObject($this, 'user');
    }

    /**
     * {@inheritDoc}
     */
    public function getPrimaryPropertyName()
    {
        return 'id';
    }
}