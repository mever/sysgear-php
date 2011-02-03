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
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
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
        list($bundleName, $serviceName) = explode(':', $service);
        $class = null;
        $logs = array();
        foreach ($this->container->get('kernel')->getBundles() as $bundle) {
            if ($bundleName !== $bundle->getName()) {
                continue;
            }
            $ns = $bundle->getNamespace();
            $try = $ns.'\\Service\\'.$serviceName.'Service';
            if (! class_exists($try)) {
                if (null !== $this->logger) {
                    $logs[] = sprintf('Failed finding service "%s" from namespace "%s" (%s)',
                        $service, $ns, $try);
                }
            } else {
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