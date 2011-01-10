<?php

namespace Sysgear\Tests\StructuredData;

class Locale
{
	public $id;

	public $language;

	public function __construct($id, Language $language)
	{
	    $this->id = $id;
	    $this->language = $language;
	}
}