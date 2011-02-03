<?php

namespace Sysgear\Symfony\Bundles\ServiceBundle;

use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
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
    protected $serviceManager;
    protected $router;
    protected $logger;

    public function __construct(ServiceManager $serviceManager,
        RouterInterface $router, LoggerInterface $logger = null)
    {
        $this->serviceManager = $serviceManager;
        $this->router = $router;
        $this->logger = $logger;
    }

    /**
     * Resolve request.
     * 
     * @param \Symfony\Component\EventDispatcher\Event $event
     * @return \Symfony\Components\HttpKernel\Response
     */
    public function handle(Event $event)
    {
        $request = $event->get('request');

        if (HttpKernelInterface::MASTER_REQUEST === $event->get('request_type')) {
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
                $this->logger->info(sprintf('Matched route "%s" (parameters: %s)',
                  $parameters['_route'], str_replace("\n", '', var_export($parameters, true))));
            }

            $request->attributes->replace($parameters);
            return $this->resolvService($event, $request);
        } elseif (null !== $this->logger) {
            $this->logger->err(sprintf('No route found for %s', $request->getPathInfo()));
        }
    }

    /**
     * Resolve service request.
     * 
     * @param \Symfony\Component\EventDispatcher\Event $event
     * @param \Symfony\Component\HttpKernel\Request $request
     * @return \Symfony\Components\HttpKernel\Response
     */
    protected function resolvService(Event $event, Request $request)
    {
        // TODO: Implement optional checks to see if it is a service request.
        if (! $serviceName = $request->attributes->get('_service')) {
            return;
        }

        $sm = $this->serviceManager;
        $service = null;
        $protocol = $sm->getProtocol('jsonrpc');
        try {
            $service = $sm->findService($serviceName);
        } catch (\Exception $e) {
            $response = $protocol->fault($e);
        }
        if (null !== $service) {
            $protocol->addService($service, true);
            $response = $protocol->handle();
        }

        $event->setProcessed();
        return $response;
    }
}
