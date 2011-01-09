<?php

namespace Sysgear\StructuredData\Exporter;

class ExporterException extends \Exception
{
    public static function noDataToExport()
    {
        return new self('No data to export.');
    }
}