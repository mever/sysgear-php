<?php

namespace Sysgear\Symfony\Bundles\ServiceBundle\Protocol;

use Zend\Json\Server\Server;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Sysgear\Symfony\Bundles\ServiceBundle\ProtocolInterface;
use Sysgear\Symfony\Bundles\ServiceBundle\Service;
use Zend\Json\Json;

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
        $this->adapter->setAutoEmitResponse(false);
        
        $this->request = $container->get('request');
        $this->adapter->setTarget($this->request->getPathInfo());
    }

    /**
     * Handle request.
     * 
     * @return \Symfony\Components\HttpKernel\Response
     */
    public function handle()
    {
        $response = $this->container->get('response');
        if ('GET' === $this->request->getMethod()) {
            $response->setContent($this->adapter->getServiceMap());
        } else {
            $postData = Json::decode(file_get_contents('php://input'), Json::TYPE_ARRAY);
            $notBatch = array_key_exists('jsonrpc', $postData);
            if ($notBatch) {
                $this->adapter->getRequest()->setOptions($postData);
                $this->adapter->handle();
                $response->setContent($this->adapter->getResponse()->toJson());
            } else {
                $req = $this->adapter->getRequest();
                $res = $this->adapter->getResponse();
                $responses = array();
                foreach ($postData as $options) {
                    $req->setOptions($options);
                    $this->adapter->handle();
                    $responses[] = $res->toJson();
                }
                $response->setContent('['.join(',', $responses).']');
            }
        }
        $response->setStatusCode(200);
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * Add a service object.
     * 
     * @param \Sysgear\Symfony\Bundles\ServiceBundle\Service $service
     * @param boolean $default
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

    /**
     * Indicate fault response
     *
     * @param $exception
     * @return \Symfony\Components\HttpKernel\Response
     */
    public function fault($exception)
    {
        $response = $this->container->get('response');
        $this->adapter->fault($exception->getMessage(), $exception->getCode(), $exception);
        $response->setContent($this->adapter->getResponse()->toJson());
        $response->setStatusCode(200);
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
}