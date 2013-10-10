<?php

namespace Sysgear\StructuredData\Importer;

class ImporterException extends \Exception
{
    public static function couldNotDetermineNodeType($nodeType, $sequence)
    {
        return new self("Could not determine node type '{$nodeType}' in sequence: {$sequence}.");
    }
}