<?php

namespace Sysgear\Gearman;

class Job
{
    protected $name;
    protected $parameters = array();

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function setParameter($key, $value)
    {
        $this->parameters[$key] = $value;
    }

    public function getParameter($key)
    {
        return $this->parameters[$key];
    }
}