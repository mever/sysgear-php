<?php

namespace Sysgear\Gearman;

class Job
{
    protected $name;
    protected $parameters = array();

    public function __construct($name, array $parameters = array())
    {
        $this->name = $name;
        $this->parameters = $parameters;
    }

    public function setParameter($key, $value)
    {
        $this->parameters[$key] = $value;
    }

    public function getParameter($key)
    {
        return $this->parameters[$key];
    }

    public function getParameters()
    {
        return $this->parameters;
    }
}