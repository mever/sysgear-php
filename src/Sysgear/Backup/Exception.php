<?php

namespace Sysgear\Backup;

class Exception extends \Exception
{
    public static function noEntityManager()
    {
        return new self("No entity manager was given");
    }
}