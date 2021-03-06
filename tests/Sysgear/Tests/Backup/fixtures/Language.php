<?php

namespace Sysgear\Tests\Backup;

use Sysgear\StructuredData\Collector\BackupCollector;
use Sysgear\StructuredData\Restorer\BackupRestorer;
use Sysgear\Backup\BackupableInterface;

class Language implements BackupableInterface
{
	public $id;

	public $iso639;

	public function __construct($id = null, $iso639 = null)
	{
	    $this->id = $id;
	    $this->iso639 = $iso639;
	}

    /**
     * {@inheritDoc}
     */
    public function collectStructuredData(BackupCollector $backupDataCollector, array $options = array())
    {
        $backupDataCollector->fromObject($this);
    }

    /**
     * {@inheritDoc}
     */
    public function restoreStructuredData(BackupRestorer $backupDataRestorer)
    {
        $remaining = $backupDataRestorer->toObject($this);
        foreach ($remaining as $name => $value) {
            $this->{$name} = $value;
        }
    }
}