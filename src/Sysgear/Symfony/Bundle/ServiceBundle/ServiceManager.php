<?php

namespace Sysgear\Symfony\Bundle\ServiceBundle;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Request;
use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Sysgear\Symfony\Bundle\ServiceBundle\ProtocolInterface;
use Sysgear\Symfony\Bundle\ServiceBundle\Service;

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
     * @var ProtocolInterface[]
     */
    protected $protocols = array();

    /**
     * @param ContainerInterface $container
     * @param LoggerInterface $logger
     */
    public function __construct(ContainerInterface $container, LoggerInterface $logger = null)
    {
        $this->container = $container;
        $this->logger = $logger;
    }

    /**
     * Add protocol.
     *
     * @param ProtocolInterface $protocol
     */
    public function addProtocol(ProtocolInterface $protocol)
    {
        $this->protocols[] = $protocol;
    }

    /**
     * Return a protocol adapter.
     *
     * TODO: Support mutiple protocols and select by HTTP context.
     *
     * @return ProtocolInterface
     */
    public function getProtocol()
    {
        $protocol = reset($this->protocols);

        $protocol->enableDebugging($this->container->getParameter('sysgear.service.debug'));
        return $protocol;
    }

    /**
     * Find a service class.
     *
     * @param string $service
     * @return \Sysgear\Symfony\Bundle\ServiceBundle\Service
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

            throw new \InvalidArgumentException(sprintf('Unable to find service "%s".', $service));
        }

        if (null !== $this->logger) {
            $this->logger->info(sprintf('Using service "%s"', $class));
        }

        $serviceClass = new $class($this->container);

        if (! $serviceClass instanceof Service) {
            throw new \InvalidArgumentException(sprintf('Service "%s" is not type service.', $service));
        }

        return $serviceClass;
    }
}