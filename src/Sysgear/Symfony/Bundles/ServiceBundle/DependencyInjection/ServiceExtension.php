<?php

namespace Sysgear\Symfony\Bundles\ServiceBundle\DependencyInjection;

use Symfony\Components\DependencyInjection\Loader\LoaderExtension;
use Symfony\Components\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Components\DependencyInjection\BuilderConfiguration;

/**
 * ServiceExtension.
 *
 * @package    Sysgear
 * @subpackage Symfony_Bundles_ServiceBundle
 * @author     Martijn Evers <martijn4evers@gmail.com>
 */
class ServiceExtension extends LoaderExtension
{
    protected $resources = array(
        'services' => 'services.xml',
    );
    
    /**
     * Loads the logger configuration.
     *
     * Usage example:
     *
     *      <service:jsonrpc priority="info" path="/path/to/some.log" />
     *
     * @param array                $config        A configuration array
     * @param BuilderConfiguration $configuration A BuilderConfiguration instance
     *
     * @return BuilderConfiguration A BuilderConfiguration instance
     */
    public function configLoad($config, BuilderConfiguration $configuration)
    {
        if (! $configuration->hasDefinition('service')) {
            $loader = new XmlFileLoader(__DIR__.'/../Resources/config');
            $configuration->merge($loader->load($this->resources['services']));
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
        return 'service';
    }
}
