<?php

namespace Sysgear\Symfony\Bundles\ServiceBundle;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Request;
use Sysgear\Symfony\Bundles\ServiceBundle\ProtocolInterface;
use Sysgear\Symfony\Bundles\ServiceBundle\Service;

class ServiceManager
{
	/**
	 * @var \Symfony\Component\DependencyInjection\ContainerInterface
	 */
	protected $container;
	
	/**
	 * @var \Symfony\Component\HttpKernel\Log\LoggerInterface
	 */
	protected $logger;
	
	/**
	 * 
	 * @param \Symfony\Component\DependencyInjection\ContainerInterface $container A ContainerInterface instance
	 * @param \Symfony\Component\HttpKernel\Log\LoggerInterface $logger
	 */
	public function __construct(ContainerInterface $container, $logger = null)
	{
		$this->container = $container;
		$this->logger = $logger;
	}

	/**
	 * Return a protocol adapter.
	 * 
	 * @param string $protocolName
	 * @return \Sysgear\Symfony\Bundles\ServiceBundle\ProtocolInterface
	 */
	public function getProtocol($protocolName)
	{
		$protocol = $this->container->get('sysgear.service_protocol.' . $protocolName);
		if (! $protocol instanceof ProtocolInterface) {
			throw new \InvalidArgumentException(sprintf('Unable to find protocol "%s".', $protocolName));
		}
		
		if (null !== $this->logger) {
			$this->logger->info(sprintf('Using protocol "%s"', $protocolName));
		}
		
		return $protocol;
	}

	/**
	 * Find a service class.
	 * 
	 * @param string $service
	 * @return \Sysgear\Symfony\Bundles\ServiceBundle\Service
	 */
	public function findService($service)
	{
		list($bundle, $service) = explode(':', $service);
		$class = null;
		$logs = array();
		foreach (array_keys($this->container->getParameter('kernel.bundle_dirs')) as $namespace) {
			$try = $namespace.'\\'.$bundle.'\\Services\\'.$service.'Service';
			if (!class_exists($try)) {
				if (null !== $this->logger) {
					$logs[] = sprintf('Failed finding service "%s:%s" from namespace "%s" (%s)', $bundle, $service, $namespace, $try);
				}
			} else {
				if (!in_array($namespace.'\\'.$bundle.'\\'.$bundle, array_map(function ($bundle) { return get_class($bundle); }, $this->container->get('kernel')->getBundles()))) {
					throw new \LogicException(sprintf('To use the "%s" service, you first need to enable the Bundle "%s" in your Kernel class.', $try, $namespace.'\\'.$bundle));
				}

				$class = $try;

				break;
			}
		}

		if (null === $class) {
			if (null !== $this->logger) {
				foreach ($logs as $log) {
					$this->logger->info($log);
				}
			}

			throw new \InvalidArgumentException(sprintf('Unable to find service "%s:%s".', $bundle, $service));
		}
		
		if (null !== $this->logger) {
			$this->logger->info(sprintf('Using service "%s"', $class));
		}
		
		$serviceClass = new $class($this->container);
		
		if (! $serviceClass instanceof Service) {
			throw new \InvalidArgumentException(sprintf('Service "%s:%s" is not type service.', $bundle, $service));
		}
		
		return $serviceClass;
	}
}