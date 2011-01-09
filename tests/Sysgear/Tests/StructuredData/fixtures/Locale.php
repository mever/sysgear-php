<?php

namespace Sysgear\Tests\StructuredData;

use Sysgear\StructuredData\Collector\CollectorInterface;
use Sysgear\StructuredData\Restorer\RestorerInterface;
use Sysgear\Backup\BackupableInterface;

class Locale implements BackupableInterface
{
	public $id;

	public $language;

	public function __construct($id, Language $language)
	{
	    $this->id = $id;
	    $this->language = $language;
	}

    /**
     * {@inheritDoc}
     */
    public function collectStructedData(CollectorInterface $collector)
    {
        $collector->fromObject($this, 'locale');
    }

    /**
     * {@inheritDoc}
     */
    public function restoreStructedData(RestorerInterface $restorer)
    {
        $restorer->toObject($this, 'locale');
    }

    /**
     * {@inheritDoc}
     */
    public function getPrimaryPropertyName()
    {
        return 'id';
    }
}