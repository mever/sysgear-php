<?php

namespace Sysgear\StructuredData\Exporter;

use Sysgear\StructuredData\Collector\CollectorInterface;
use Sysgear\StructuredData\Node;

interface ExporterInterface
{
    const NODE_TYPE_OBJECT = 'object';
    const NODE_TYPE_PROPERTY = 'property';
    const NODE_TYPE_COLLECTION = 'collection';

    /**
     * Set node to export.
     *
     * @param Node $node
     */
    public function setNode(Node $node);

	/**
     * Return the export as string.
     *
     * @return string
     */
    public function __toString();
}