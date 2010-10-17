<?php

namespace Sysgear\Gearman;

use Application\AccountBundle\Entity\Session;
use Zend\Json\Json;

class Client extends \GearmanClient
{
    public static function thread($job, $data)
    {
        $workload = array(
            'job'      => $job,
            'job_data' => $data,
            'session'  => array(    // $_SESSION depends on latest data created by $this->getTicket().
                'id'            => Session::getCurrentSid(),
                'data'          => $_SESSION));
        $gmclient = new self();
        $gmclient->addServer();
        $job_handle = $gmclient->doBackground("job", Json::encode($workload));
        if (GEARMAN_SUCCESS !== $gmclient->returnCode()) {
            throw new \Exception('Gearman return unsuccessful code: '.$gmclient->returnCode());
        }
    }
}