<?php

namespace Sysgear\StructuredData\Exporter;

use Sysgear\StructuredData\Node;

abstract class AbstractExporter implements ExporterInterface
{
    /**
     * @var \Sysgear\StructuredData\Node
     */
    protected $node;

    /**
     * (non-PHPdoc)
     * @see Sysgear\StructuredData\Exporter.ExporterInterface::setNode()
     */
    public function setNode(Node $node)
    {
        $this->node = $node;
        return $this;
    }
}