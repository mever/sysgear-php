<?php

namespace Sysgear\Error;

interface SymfonyBundleInterface
{
    /**
     * Return the sysgear error object.
     *
     * @return \Sysgear\Error
     */
    public function getErrorObject();
}