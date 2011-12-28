<?php

namespace Sysgear\StructuredData\Importer;

class ImporterException extends \Exception
{
    public static function couldNotDetermineNodeType($nodeType)
    {
        return new self("Could not determine node type '{$nodeType}'.");
    }
}