<?php

namespace Sysgear\Symfony\Bundles\ServiceBundle;

use Symfony\Framework\Bundle\Bundle as BaseBundle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
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
        ContainerBuilder::registerExtension(new ServiceExtension());
    }
}
