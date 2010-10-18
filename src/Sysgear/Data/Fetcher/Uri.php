<?php

namespace Sysgear\Data\Fetcher;

abstract class Uri implements \Sysgear\Data\IFetcher
{
    protected $schema = 'uri';
    protected $host = '127.0.0.1';
    protected $path = '';

    /**
     * Return the base URL.
     *
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->schema . '://' . $this->host . $this->path;
    }

    /**
     * Return the URI.
     *
     * @return string
     */
    public function getUri()
    {
        return $this->schema . '://' . $this->host . $this->path;
    }
}