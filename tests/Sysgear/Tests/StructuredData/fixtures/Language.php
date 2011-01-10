<?php

namespace Sysgear\Tests\StructuredData;

class Language
{
	public $id;

	public $iso639;

	public function __construct($id, $iso639)
	{
	    $this->id = $id;
	    $this->iso639 = $iso639;
	}
}