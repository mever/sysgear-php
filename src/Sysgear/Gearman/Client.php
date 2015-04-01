<?php

namespace Sysgear\Gearman;

class Client
{
    /**
     * @var \GearmanClient
     */
    private $gearmanClient;

    /**
     * @var array
     */
    private $additionalJobParameters = array();

    public function __construct(\GearmanClient $gearmanClient)
    {
        $this->gearmanClient = $gearmanClient;
    }

    public function setParameter($name, $value)
    {
        $this->additionalJobParameters[$name] = $value;
    }

    public function doBackground(Job $job)
    {
        // pre-seed job with additional parameters (do not overwrite existing ones)
        foreach ($this->additionalJobParameters as $name => $value) {
            if (! $job->hasParameter($name)) {
                $job->setParameter($name, $value);
            }
        }

        $this->gearmanClient->doBackground($job->getName(), serialize($job));
        if (\GEARMAN_SUCCESS !== $this->gearmanClient->returnCode()) {
            throw new \RuntimeException('Gearman return unsuccessful code: ' . $this->gearmanClient->returnCode());
        }
    }
}