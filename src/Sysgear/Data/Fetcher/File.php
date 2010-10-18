<?php

namespace Sysgear\Data\Fetcher;

class File extends Uri
{
    protected $schema = 'file';
    protected $content = '';

    public function __construct($filePath)
    {
        $this->path = $filePath;
        $this->content = file_get_contents($filePath, FILE_USE_INCLUDE_PATH);
    }

    /**
     * Return the fetched data.
     *
     * @return string
     */
    public function getData()
    {
        return $this->content;
    }
}