<?php

namespace Sysgear\Symfony\Bundles\ServiceBundle;

use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Components\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;

use Sysgear\Symfony\Bundles\ServiceBundle\Service;
use Sysgear\Symfony\Bundles\ServiceBundle\ServiceManager;

/**
 * RequestListener listen to the core.load_controller and finds web services
 * to execute based on the request parameters.
 *
 * @package    Sysgear
 * @subpackage Symfony_ServiceBundle
 * @author     Martijn Evers <martijn4evers@gmail.com>
 */
class RequestListener
{
    protected $container;
    protected $router;
    protected $logger;

    public function __construct(ContainerInterface $container, RouterInterface $router, LoggerInterface $logger = null)
    {
        $this->container = $container;
        $this->router = $router;
        $this->logger = $logger;
    }

    /**
     * Registers a core.request listener.
     *
     * @param Symfony\Components\EventDispatcher\EventDispatcher $dispatcher An EventDispatcher instance
     */
    public function register(EventDispatcher $dispatcher)
    {
        $dispatcher->connect('core.request', array($this, 'resolve'));
    }

    /**
     * Resolve request.
     * 
     * @param Symfony\Components\EventDispatcher\Event $event
     */
    public function resolve(Event $event)
    {
        $request = $event->getParameter('request');

        if (HttpKernelInterface::MASTER_REQUEST === $event->getParameter('request_type')) {
            // set the context even if the parsing does not need to be done
            // to have correct link generation
            $this->router->setContext(array(
                'base_url'  => $request->getBaseUrl(),
                'method'    => $request->getMethod(),
                'host'      => $request->getHost(),
                'is_secure' => $request->isSecure(),
            ));
        }

        if ($request->attributes->has('_controller')) {
            return;
        }

        if (false !== $parameters = $this->router->match($request->getPathInfo())) {
            if (null !== $this->logger) {
                $this->logger->info(sprintf('Matched route "%s" (parameters: %s)', $parameters['_route'], str_replace("\n", '', var_export($parameters, true))));
            }

            $request->attributes->replace($parameters);
            $this->resolvService($event, $request);
        } elseif (null !== $this->logger) {
            $this->logger->err(sprintf('No route found for %s', $request->getPathInfo()));
        }
    }

    /**
     * Resolve service request.
     * 
     * @param \Symfony\Components\EventDispatcher\Event $event
     * @param \Symfony\Components\HttpKernel\Request $request
     * @param array $parameters
     */
    protected function resolvService(Event $event, Request $request)
    {
    	// TODO: Implement optional checks to see if it is a service request.
    	if (!$service = $request->attributes->get('_service')) {
    		return;
    	}

    	$serviceManager = $this->container->getSysgear_ServiceManagerService();
    	$protocol = $serviceManager->getProtocol();
    	$protocol->addService($serviceManager->findService($service), true);
    	$response = $protocol->handle();

    	$event->setReturnValue($response);
    	$event->setProcessed(true);
    }
}
