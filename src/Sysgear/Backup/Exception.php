<?php

namespace Sysgear\Backup;

class Exception extends \Exception
{
    public static function noEntityManager()
    {
        return new self("No entity manager was given");
    }

    public static function classIsNotABackable($class)
    {
        if (is_object($class)) {
            $class = get_class($class);
        }

        return new self("Class '{$class}' does not implement Sysgear\Backup\BackupableInterface.");
    }
}