<?php

namespace Sysgear\Symfony\Bundles\ServiceBundle;

use Sysgear\Symfony\Bundles\ServiceBundle\Service;

interface ServiceAdapterInterface
{
	/**
	 * Add a service object.
	 * 
	 * @param \Sysgear\Symfony\Bundles\ServiceBundle\Service $service
	 * @return \Sysgear\Symfony\Bundles\ServiceBundle\ServiceAdapter\Jsonrpc
	 */
	public function addService(Service $service, $default);
}