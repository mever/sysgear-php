<?php

namespace Sysgear\StructuredData\Importer;

class ImporterException extends \Exception
{
    public static function couldNotDetermineNodeType($nodeType)
    {
        return new self('could not determine node type');
    }
}