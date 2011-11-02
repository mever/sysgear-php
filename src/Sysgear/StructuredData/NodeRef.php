<?php

namespace Sysgear\StructuredData;

class NodeRef implements NodeInterface
{
    /**
     * The unique identification of each node.
     *
     * @var string
     */
    protected $id;

    /**
     * A descriptive name for this node.
     *
     * @var string
     */
    protected $name;

    /**
     * Create a new node instance.
     *
     * @param string $id
     * @param string $name
     * @param boolean $isReference
     */
    public function __construct($id, $name = 'node')
    {
        $this->id = $id;
        $this->name = $name;
    }

    /**
     * (non-PHPdoc)
     * @see Sysgear\StructuredData.NodeInterface::getId()
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * (non-PHPdoc)
     * @see Sysgear\StructuredData.NodeInterface::setName()
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * (non-PHPdoc)
     * @see Sysgear\StructuredData.NodeInterface::getName()
     */
    public function getName()
    {
        return $this->name;
    }
}