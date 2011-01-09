<?php

namespace Sysgear\StructuredData\Restorer;

use Sysgear\Backup\BackupableInterface;

class BackupableRestorer extends ObjectRestorer
{
    /**
     * (non-PHPdoc)
     * @see Sysgear\StructuredData\Restorer.RestorerInterface::toObject()
     */
    public function toObject($object, $name = null)
    {
        if (! ($object instanceof BackupableInterface)) {
            throw new RestorerException("Given object does not implement BackupableInterface.");
        }
        return parent::toObject($object, $name);
    }
}