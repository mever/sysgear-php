<?php

namespace Sysgear\Symfony\Bundle\ServiceBundle;

use Symfony\Component\HttpKernel\Kernel;
use Sysgear\Error\SymfonyBundleInterface;

class ErrorBuilder
{
    const UNKNOWN = 1;

    /**
     * @var Kernel
     */
    protected $kernel;

    /**
     * Create an error builder instance.
     *
     * @param Kernel $kernel
     */
    public function __construct(Kernel $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * Build an error message from an exception.
     *
     * @param \Exception $e
     * @return array
     */
    public function fromException(\Exception $e)
    {
        $code = self::UNKNOWN;
        $bundleName = 'unknown';

        foreach ($this->kernel->getBundles() as $bundle) {

            if ($bundle instanceof SymfonyBundleInterface) {
                $ns = $bundle->getNamespace();
                if (substr(get_class($e), 0, strlen($ns)) === $ns) {
                    $bundleName = $bundle->getName();
                    $code = ($bundle->getErrorObject()->getComponentCode() * 10000) + $e->getCode();
                    break;
                }
            }
        }

        return array('code' => $code, 'message' => $e->getMessage(), 'bundle' => $bundleName);
    }
}