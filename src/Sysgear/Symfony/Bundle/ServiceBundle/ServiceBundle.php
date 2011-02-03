<?php

namespace Sysgear\Symfony\Bundle\ServiceBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle as BaseBundle;

class ServiceBundle extends BaseBundle
{
    /**
     * {@inheritdoc}
     */
    public function getNamespace()
    {
        return __NAMESPACE__;
    }

    /**
     * {@inheritdoc}
     */
    public function getPath()
    {
        return strtr(__DIR__, '\\', '/');
    }
}
