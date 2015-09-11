<?php

namespace Sysgear\StructuredData\Restorer;

use Sysgear\StructuredData\Importer\ImporterInterface;
use Sysgear\StructuredData\Node;
use Closure;

abstract class AbstractRestorer implements RestorerInterface
{
    /**
     * @var \Sysgear\StructuredData\Node
     */
    protected $node;

    /**
     * @var \Closure
     */
    protected $logger;

    /**
     * Store options that should be persistent when restoring data recursive.
     *
     * @var array
     */
    protected $persistentOptions = array();

    /**
     * Construct data collector.
     *
     * @param array $options
     */
    public function __construct(array $options = array())
    {
        foreach ($options as $key => $value) {
            $this->setOption($key, $value);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setOption($key, $value)
    {
        $this->persistentOptions[$key] = $value;
        $this->_setOption($key, $value);
    }

    /**
     * Set option.
     *
     * @param string $key
     * @param mixed $value
     */
    protected function _setOption($key, $value)
    {
        switch ($key) {
            case 'logger':
                $this->logger = ($value instanceof Closure) ? $value : null;
                break;
        }
    }
}