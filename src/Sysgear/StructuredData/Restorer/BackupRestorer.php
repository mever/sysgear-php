<?php

namespace Sysgear\StructuredData\Restorer;

use Sysgear\StructuredData\Restorer\AbstractRestorer;
use Sysgear\Backup\BackupableInterface;

class BackupRestorer extends AbstractRestorer
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

        if (null === $name) {
            $fullClassname = get_class($object);
            $pos = strrpos($fullClassname, '\\');
            $name = (false === $pos) ? $fullClassname : substr($fullClassname, $pos + 1);
        }
        
    }
}