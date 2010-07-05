<?php

namespace Sysgear\Symfony\Bundles\ServiceBundle;

use Symfony\Components\DependencyInjection\ContainerInterface;

class Service
{
	/**
	 * @var $container \Symfony\Components\DependencyInjection\ContainerInterface
	 */
	protected $container;
	
	/**
	 * 
	 * @param \Symfony\Components\DependencyInjection\ContainerInterface $container A ContainerInterface instance
	 */
	public function __construct(ContainerInterface $container)
	{
		$this->container = $container;
	}
	
	/**
	 * Return the name of this service.
	 * 
	 * @return string
	 */
	public function getName()
	{
		$class = get_class($this);
		return strtolower(substr($class, strrpos($class, '\\') + 1, -7));
	}
}