<?php

namespace Sysgear\StructuredData\Importer;

use Sysgear\StructuredData\Node;

abstract class AbstractImporter implements ImporterInterface
{
    /**
     * @var \Sysgear\StructuredData\Node
     */
    protected $node;

    /**
     * While importing an in-memory node tree is build. To determine the
     * type of nodes to use, a field is used with one of
     * the {@see self::NOTE_TYPE_*} constants to indicate the node type. This
     * fields specifies which field to use for this purpose in the import.
     *
     * @var string
     */
    protected $metaTypeField = 'meta-type';

    /**
     * Construct a new importer.
     */
    public function __construct()
    {
        $this->node = new Node('empty');
    }

    /**
     * (non-PHPdoc)
     * @see Sysgear\StructuredData\Importer.ImporterInterface::getNode()
     */
    public function getNode()
    {
        return $this->node;
    }
}