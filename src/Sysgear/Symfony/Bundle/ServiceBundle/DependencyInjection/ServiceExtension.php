<?php

namespace Sysgear\Symfony\Bundle\ServiceBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Extension\Extension;
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
    protected $resources = array(
        'services' => 'services.xml',
    );

    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load($this->resources['services']);
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
