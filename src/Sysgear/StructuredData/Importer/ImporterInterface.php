<?php

namespace Sysgear\StructuredData\Importer;

use Sysgear\StructuredData\Restorer\RestorerInterface;

interface ImporterInterface
{
    const NODE_TYPE_OBJECT = 'object';
    const NODE_TYPE_PROPERTY = 'property';
    const NODE_TYPE_COLLECTION = 'collection';

    /**
     * Get the imported node.
     *
     * @param \Sysgear\StructuredData\Node $node
     */
    public function getNode();

	/**
     * Import from string.
     *
     * @param string $string
     * @return \Sysgear\StructuredData\Importer\ImporterInterface
     */
    public function fromString($string);
}