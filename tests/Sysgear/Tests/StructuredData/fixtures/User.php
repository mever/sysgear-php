<?php

namespace Sysgear\Tests\StructuredData;

class User
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
}