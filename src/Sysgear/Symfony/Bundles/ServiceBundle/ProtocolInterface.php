<?php

namespace Sysgear\Symfony\Bundles\ServiceBundle;

use Sysgear\Symfony\Bundles\ServiceBundle\Service;

interface ProtocolInterface
{
	/**
	 * Add a service object.
	 * 
	 * @param \Sysgear\Symfony\Bundles\ServiceBundle\Service $service
	 * @return \Sysgear\Symfony\Bundles\ServiceBundle\Protocol\Jsonrpc
	 */
	public function addService(Service $service, $default);
}