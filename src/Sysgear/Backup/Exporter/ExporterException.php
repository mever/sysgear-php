<?php

namespace Sysgear\Backup\Exporter;

class ExporterException extends \Exception
{
    public static function noDataToExport()
    {
        return new self('No data to export.');
    }
}