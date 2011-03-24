<?php

namespace Sysgear\Symfony\Bundle\ServiceBundle\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;

/**
 * ServiceExtension.
 *
 * @package    Sysgear
 * @subpackage Symfony_Bundle_ServiceBundle
 * @author     Martijn Evers <martijn4evers@gmail.com>
 */
class ServiceExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        foreach ($configs as $config) {
            $this->doConfigLoad($config, $container);
        }
    }

    /**
     * Loads the service configuration.
     *
     * @param array            $config    An array of configuration settings
     * @param ContainerBuilder $container A ContainerBuilder instance
     */
    protected function doConfigLoad(array $config, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        if (! $container->hasDefinition('pdf')) {
            $loader->load('config.xml');
        }

        foreach ($config as $key => $value) {
            switch ($key) {
                case 'debug':
                    $container->setParameter('sysgear.service.debug', (boolean) $value);
                    break;
            }
        }
    }

    /**
     * Returns the base path for the XSD files.
     *
     * @return string The XSD base path
     */
    public function getXsdValidationBasePath()
    {
        return __DIR__.'/../Resources/config/';
    }

    public function getNamespace()
    {
        return 'http://www.sysgear.eu/schema/dic/service';
    }

    public function getAlias()
    {
        return 'service';
    }
}
