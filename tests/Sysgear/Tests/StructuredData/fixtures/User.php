<?php

namespace Sysgear\Tests\StructuredData;

class User extends BackupableEntity
{
    public $id;

    public $name;

    public $password;

    public $employer;

    public $roles = array();

    public $locale;

    public function __construct($id = null, $name = null, $password = null,
        Company $employer = null, Locale $locale = null)
    {
        $this->id = $id;
        $this->name = $name;
        $this->password = $password;
        $this->employer = $employer;
        $this->locale = $locale;
    }
}