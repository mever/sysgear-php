<?php

namespace Sysgear\Symfony\Test;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Doctrine\ORM\EntityManager;

class Doctrine
{
    public static function loadFixtures(Application $app, $path)
    {
        Util::executeCommand($app, "doctrine:data:load --fixtures={$path}");
    }
}