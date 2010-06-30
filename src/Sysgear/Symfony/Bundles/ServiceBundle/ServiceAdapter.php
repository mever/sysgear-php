<?php

namespace Sysgear\Symfony\Bundles\ServiceBundle;

use Symfony\Components\HttpKernel\Request;
use Symfony\Components\DependencyInjection\ContainerInterface;

abstract class ServiceAdapter
{
	protected $container;
	protected $service;
	
	/**
	 * Set request object
	 *
	 * @param  \StdObject $service
	 * @return \Sysgear\Symfony\Bundles\ServiceBundle\ServiceAdapter
	 */
	public function setService($service)
	{
		$this->service = $service;
		return $this;
	}
	
	/**
	 * Get request object
	 *
	 * @return \StdObject
	 */
	public function getService()
	{
		return $this->service;
	}
	
	/**
	 * Set container object
	 *
	 * @param Symfony\Components\DependencyInjection\ContainerInterface $container A ContainerInterface instance
	 * @return \Sysgear\Symfony\Bundles\ServiceBundle\ServiceAdapter
	 */
	public function setContainer(ContainerInterface $container)
	{
		$this->container = $container;
		return $this;
	}
	
	/**
	 * Get container object
	 *
	 * @return Symfony\Components\DependencyInjection\ContainerInterface A ContainerInterface instance
	 */
	public function getContainer()
	{
		return $this->container;
	}
	
	/**
	 * Handle request.
	 * 
	 * @return \Symfony\Components\HttpKernel\Response
	 */
	abstract public function handle();
}