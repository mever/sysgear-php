<?php

namespace Sysgear\Symfony\Bundle\ServiceBundle;

use Sysgear\Symfony\Bundle\ServiceBundle\Service;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface ProtocolInterface
{
    /**
     * Handle request.
     *
     * @param Request $request
     * @return Response
     */
    public function handle(Request $request);

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