<?php

namespace Sysgear\Symfony\Bundles\ServiceBundle;

use Sysgear\Symfony\Bundles\ServiceBundle\Service;

interface ProtocolInterface
{
    /**
     * Handle request.
     * 
     * @return \Symfony\Components\HttpKernel\Response
     */
    public function handle();

    /**
     * Add a service object.
     * 
     * @param \Sysgear\Symfony\Bundles\ServiceBundle\Service $service
     * @param boolean $default
     * @return \Sysgear\Symfony\Bundles\ServiceBundle\Protocol\Jsonrpc
     */
    public function addService(Service $service, $default);
}