<?php

namespace Sysgear\Tests\StructuredData;

class Language extends BackupableEntity
{
	public $id;

	public $iso639;

	public function __construct($id, $iso639)
	{
	    $this->id = $id;
	    $this->iso639 = $iso639;
	}
}