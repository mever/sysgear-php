<?php

namespace Sysgear\Gearman;

class Job
{
    private $name;
    private $parameters = array();

    public function __construct($name, array $parameters = array())
    {
        $this->name = $name;
        $this->parameters = $parameters;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setParameter($name, $value)
    {
        $this->parameters[$name] = $value;
    }

    public function hasParameter($name)
    {
        return array_key_exists($name, $this->parameters);
    }

    public function getParameter($name, $default = null)
    {
        if (array_key_exists($name, $this->parameters)) {
            return $this->parameters[$name];
        } else {
            if (func_num_args() === 1) {
                throw new \InvalidArgumentException("No parameter named '{$name}' found");
            }

            return $default;
        }
    }

    public function getParameters()
    {
        return $this->parameters;
    }
}