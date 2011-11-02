<?php

namespace Sysgear\StructuredData\Collector;

use Sysgear\StructuredData\Exporter\ExporterInterface;

abstract class AbstractCollector implements CollectorInterface
{
    /**
     * @var \Sysgear\StructuredData\NodeInterface
     */
    protected $node;

    protected $persistentOptions = array();

    /**
     * The descent level of the object graph.
     *
     * 0 is infinite descending
     * 1 is only properties
     * n is descending into the graph for n nodes
     *
     * @var integer
     */
    protected $descentLevel = 0;

    // disable object cloning
    private function __clone() {}

    /**
     * Construct data collector.
     *
     * @param array $options General options
     */
    public function __construct(array $options = array())
    {
        foreach ($options as $key => $value) {
            $this->setOption($key, $value);
        }
    }

    /**
     * Set general option.
     *
     * @param string $key
     * @param mixed $value
     */
    public function setOption($key, $value)
    {
        $this->persistentOptions[$key] = $value;
        if (property_exists($this, $key)) {
            $this->_setOption($key, $value);
        }
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
            case 'descentLevel':
                $this->descentLevel = (int) $value;
                break;
        }
    }

    /**
     * Return collected node.
     *
     * @return \Sysgear\StructuredData\NodeInterface
     */
    public function getNode()
    {
        return $this->node;
    }
}