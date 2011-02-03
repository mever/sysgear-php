<?php

namespace Sysgear\Symfony\Bundle\ServiceBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;

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

    /**
     *
     * @param array                $config        A configuration array
     * @param BuilderConfiguration $configuration A BuilderConfiguration instance
     *
     * @return BuilderConfiguration A BuilderConfiguration instance
     */
    public function configLoad($config, ContainerBuilder $configuration)
    {
        if (! $configuration->hasDefinition('sysgear.service')) {
            $loader = new XmlFileLoader($configuration, __DIR__.'/../Resources/config');
            $loader->load($this->resources['services']);
        }

        return $configuration;
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
        return 'sysgear.service';
    }
}
