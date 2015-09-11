<?php

namespace Sysgear\Tests\Backup;

use Sysgear\StructuredData\Collector\BackupCollector;
use Sysgear\StructuredData\Restorer\BackupRestorer;
use Sysgear\Backup\BackupableInterface;

class Locale implements BackupableInterface
{
	public $id;

	public $language;

	public function __construct($id = null, Language $language = null)
	{
	    $this->id = $id;
	    $this->language = $language;
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
        $backupDataRestorer->toObject($this);
    }
}