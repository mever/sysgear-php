<?php

namespace Sysgear\Tests\Backup;

use Sysgear\StructuredData\Collector\CollectorInterface;
use Sysgear\StructuredData\Restorer\RestorerInterface;
use Sysgear\Backup\BackupableInterface;

class Language implements BackupableInterface
{
	public $id;

	public $iso639;

	public function __construct($id, $iso639)
	{
	    $this->id = $id;
	    $this->iso639 = $iso639;
	}

    /**
     * {@inheritDoc}
     */
    public function collectStructedData(CollectorInterface $collector)
    {
        $collector->fromObject($this, 'language');
    }

    /**
     * {@inheritDoc}
     */
    public function restoreStructedData(RestorerInterface $restorer)
    {
        $restorer->toObject($this, 'language');
    }

    /**
     * {@inheritDoc}
     */
    public function getPrimaryPropertyName()
    {
        return 'id';
    }
}