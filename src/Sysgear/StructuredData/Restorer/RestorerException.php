<?php

namespace Sysgear\StructuredData\Restorer;

class RestorerException extends \Exception
{
    public static function canNotFindClass($className = 'N/A')
    {
        return new self("Can not find class '{$className}'.");
    }
}