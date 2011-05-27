<?php

namespace Sysgear\Tests\StructuredData;

class Role extends BackupableEntity
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
}