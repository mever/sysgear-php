<?php

namespace Sysgear\StructuredData\Importer;

interface ImporterInterface
{
    const NODE_TYPE_OBJECT = 'object';
    const NODE_TYPE_PROPERTY = 'property';
    const NODE_TYPE_COLLECTION = 'collection';

    /**
     * Get the imported node.
     *
     * @return \Sysgear\StructuredData\Node $node
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