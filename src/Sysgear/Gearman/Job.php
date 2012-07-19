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

    public function getName()
    {
        return $this->name;
    }

    public function setParameter($key, $value)
    {
        $this->parameters[$key] = $value;
    }

    public function getParameter($key, $default = null)
    {
        return (array_key_exists($key, $this->parameters)) ? $this->parameters[$key] : $default;
    }

    public function getParameters()
    {
        return $this->parameters;
    }
}