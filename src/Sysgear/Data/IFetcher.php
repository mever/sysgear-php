<?php

namespace Sysgear\Data;

interface IFetcher
{
    /**
     * Return the fetched data.
     *
     * @return string
     */
    public function getData();

    /**
     * Return the base URL.
     *
     * @return string
     */
    public function getBaseUrl();

    /**
     * Return the URI.
     *
     * @return string
     */
    public function getUri();
}