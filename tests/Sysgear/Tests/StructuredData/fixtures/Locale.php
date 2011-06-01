<?php

namespace Sysgear\Tests\StructuredData;

use Sysgear\Tests\Backup\BackupableEntity;

class Locale extends BackupableEntity
{
	public $id;

	public $language;

	public function __construct($id = null, Language $language = null)
	{
	    $this->id = $id;
	    $this->language = $language;
	}
}