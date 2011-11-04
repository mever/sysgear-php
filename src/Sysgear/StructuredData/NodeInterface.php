<?php

namespace Sysgear\StructuredData;

abstract class NodeInterface
{
    /**
     * The node type.
     *
     * @var string
     */
    protected $type;

    /**
     * Create a new node instance.
     *
     * @param string $type
     */
    public function __construct($type)
    {
        $this->type = $type;
    }

    /**
     * Return the node type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }
}