<?php

namespace Sysgear\StructuredData;

class Node extends NodeInterface
{
    /**
     * Node metadata.
     *
     * @var map
     */
    protected $metadata = array();

    /**
     * Node properties.
     *
     * @var map
     */
    protected $properties = array();

    /**
     * A descriptive name for this node.
     *
     * @var string
     */
    protected $name;

    /**
     * Create a new node instance.
     *
     * @param string $type
     * @param string $name
     */
    public function __construct($type, $name = 'node')
    {
        parent::__construct($type);
        $this->name = $name;
    }

    /**
     * Set metadata.
     *
     * @param string $name
     * @param string $value
     */
    public function setMetadata($name, $value)
    {
        $this->metadata[$name] = $value;
    }

    /**
     * Set name name.
     *
     * @param string $name
     * @return \Sysgear\StructuredData\NodeInterface
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Return the descriptive node name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set property.
     *
     * @param string $name
     * @param NodeInterface $value
     */
    public function setProperty($name, NodeInterface $value)
    {
        $this->properties[$name] = $value;
    }

    /**
     * Return node metadata.
     *
     * @return map
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * Return node properties.
     *
     * @return map
     */
    public function getProperties()
    {
        return $this->properties;
    }
}