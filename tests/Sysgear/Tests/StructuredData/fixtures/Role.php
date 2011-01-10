<?php

namespace Sysgear\Tests\StructuredData;

class Role
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
}