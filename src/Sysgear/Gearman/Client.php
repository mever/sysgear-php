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

    public function doBackground(Job $job)
    {
        $jobHandle = $this->gearmanClient->doBackground($job->getName(), serialize($job));
        if (\GEARMAN_SUCCESS !== $this->gearmanClient->returnCode()) {
            throw new \RuntimeException('Gearman return unsuccessful code: ' . $this->gearmanClient->returnCode());
        }
    }
}