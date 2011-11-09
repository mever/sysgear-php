<?php

namespace Sysgear\StructuredData\Exporter;

use Sysgear\StructuredData\Collector\CollectorInterface;
use Sysgear\StructuredData\Node;

interface ExporterInterface
{
    /**
     * Set node.
     *
     * @param Node $node
     */
    public function setNode(Node $node);

	/**
     * Return the exporter as string.
     *
     * @return string
     */
    public function __toString();
}