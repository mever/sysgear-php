<?php

namespace Sysgear\Symfony\Bundle\ServiceBundle;

use Sysgear\Symfony\Bundle\ServiceBundle\Service;

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
     * @param \Sysgear\Symfony\Bundle\ServiceBundle\Service $service
     * @param boolean $default
     * @return \Sysgear\Symfony\Bundle\ServiceBundle\Protocol\Jsonrpc
     */
    public function addService(Service $service, $default);

    /**
     * Return debug information if enabled.
     *
     * @param boolean $flag
     */
    public function enableDebugging($flag);
}