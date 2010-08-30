<?php

namespace Sysgear\Symfony\Bundles\ServiceBundle\Protocol;

use Zend\Json\Server\Server;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Sysgear\Symfony\Bundles\ServiceBundle\ProtocolInterface;
use Sysgear\Symfony\Bundles\ServiceBundle\Service;

class Jsonrpc implements ProtocolInterface
{
	protected $container;
	protected $adapter;
	protected $request;
	protected $service;

	public function __construct(ContainerInterface $container, Server $adapter)
	{
		$this->container = $container;
		$this->adapter = $adapter;
		
		$this->request = $container->getRequestService();
		$this->adapter->setTarget($this->request->getPathInfo());
	}

	/**
	 * Handle request.
	 * 
	 * @return \Symfony\Components\HttpKernel\Response
	 */
	public function handle()
	{
			$response = $this->container->getResponseService();
			$response->setContent((string)$this->adapter->handle());
//			$response->setContent($this->adapter->getServiceMap());
			$response->setStatusCode(200);
			$response->headers->set('Content-Type', 'application/json');
			return $response;
	}

	/**
	 * Add a service object.
	 * 
	 * @param \Sysgear\Symfony\Bundles\ServiceBundle\Service $service
	 * @return \Sysgear\Symfony\Bundles\ServiceBundle\ServiceAdapter\Jsonrpc
	 */
	public function addService(Service $service, $default = false)
	{
		$namespace = '';
		if (! $default) {
			$class = get_class($service);
			$namespace = strtolower(substr($class, strrpos($class, '\\') + 1, -7));
		}

		$this->adapter->setClass($service, $namespace);
		$this->service = $service;
		return $this;
	}
}