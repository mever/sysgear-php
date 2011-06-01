<?php

/*
 * This file is part of the Sysgear package.
 *
 * (c) Martijn Evers <martijn4evers@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sysgear\Tests\Backup;

use Sysgear\Backup\BackupableInterface;
use Sysgear\StructuredData\Collector\BackupCollector;
use Sysgear\StructuredData\Restorer\BackupRestorer;

class BackupableEntity implements BackupableInterface
{
    /**
     * {@inheritDoc}
     */
    public function collectStructedData(BackupCollector $backupDataCollector, array $options = array())
    {
        $options['className'] = get_class($this);
        $backupDataCollector->fromObject($this, $options);
    }

    /**
     * {@inheritDoc}
     */
    public function restoreStructedData(BackupRestorer $backupDataRestorer)
    {
        $remaining = $backupDataRestorer->toObject($this);
        foreach ($remaining as $name => $value) {
            $this->{$name} = $value;
        }
    }
}