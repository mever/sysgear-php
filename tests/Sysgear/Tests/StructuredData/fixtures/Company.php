<?php

namespace Sysgear\Tests\StructuredData;

use Sysgear\Tests\Backup\BackupableEntity;

class Company extends BackupableEntity
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
}