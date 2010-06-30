<?php

namespace Sysgear\Symfony\Bundles\ServiceBundle\ServiceAdapter;

use Zend\JSON\Server\Server;
use Sysgear\Symfony\Bundles\ServiceBundle\ServiceAdapter;

class Jsonrpc extends ServiceAdapter
{
	protected $adapter;
	
	/**
	 * Construct the JSON-RPC service.
	 * 
	 * @param \Zend\JSON\Server\Server $adapter
	 */
	public function __construct(Server $adapter)
	{
		$this->adapter = $adapter;
	}
	
	/**
	 * Handle request.
	 * 
	 * @return \Symfony\Components\HttpKernel\Response
	 */
	public function handle()
	{
		$this->adapter->setClass($this->service);
		$response = $this->container->getResponseService();
		$response->setContent((string)$this->adapter->handle());
		$response->setStatusCode(200);
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}
}