<?php

namespace Sysgear\Symfony\Bundles\ServiceBundle;

use Symfony\Framework\Bundle\Bundle as BaseBundle;
use Symfony\Components\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Components\DependencyInjection\Loader\Loader;
use Sysgear\Symfony\Bundles\ServiceBundle\DependencyInjection\ServiceExtension;

class ServiceBundle extends BaseBundle
{
    /**
     * Customizes the Container instance.
     *
     * @param \Symfony\Components\DependencyInjection\ParameterBag\ParameterBagInterface $parameterBag A ParameterBagInterface instance
     *
     * @return \Symfony\Components\DependencyInjection\BuilderConfiguration A BuilderConfiguration instance
     */
    public function buildContainer(ParameterBagInterface $parameterBag)
    {
        Loader::registerExtension(new ServiceExtension());
    }
}
