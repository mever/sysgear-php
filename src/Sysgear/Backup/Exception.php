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

    public static function invalidElement(array $missingFields)
    {
        return new self("The given element is not valid and can't be used. It ".
        	"is missing these field(s): '" . join(", '", $missingFields) . "'");
    }
}