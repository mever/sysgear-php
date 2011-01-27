<?php

namespace Sysgear\Gearman;

use Zend\Json\Json;

class Client
{
    /**
     * @var \GearmanClient
     */
    protected $gearmanClient;

    public function __construct()
    {
        $this->gearmanClient = new \GearmanClient();
        $this->gearmanClient->addServer();
    }

    public function doBackground($name, Job $job)
    {
        $jobHandle = $this->gearmanClient->doBackground($name, serialize($job));
        if (\GEARMAN_SUCCESS !== $this->gearmanClient->returnCode()) {
            throw new \Exception('Gearman return unsuccessful code: ' . $this->gearmanClient->returnCode());
        }
    }
}