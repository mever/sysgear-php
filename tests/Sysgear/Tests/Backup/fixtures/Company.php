<?php

namespace Sysgear\Tests\Backup;

use Sysgear\StructuredData\Collector\CollectorInterface;
use Sysgear\StructuredData\Restorer\RestorerInterface;
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
    public function collectStructedData(CollectorInterface $collector)
    {
        $collector->fromObject($this, 'company');
    }

    /**
     * {@inheritDoc}
     */
    public function restoreStructedData(RestorerInterface $restorer)
    {
        $restorer->toObject($this, 'company');
    }

    /**
     * {@inheritDoc}
     */
    public function getPrimaryPropertyName()
    {
        return 'id';
    }
}