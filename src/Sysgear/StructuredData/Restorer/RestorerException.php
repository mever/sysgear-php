<?php

namespace Sysgear\StructuredData\Restorer;

class RestorerException extends \Exception
{
    public static function canNotFindClass()
    {
        return new self("Can not find class name.");
    }
}