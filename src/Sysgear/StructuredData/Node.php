<?php

namespace Sysgear\StructuredData;

class Node implements NodeInterface
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
     * Set property.
     *
     * @param string $name
     * @param map|Node|NodeCollection $value
     */
    public function setProperty($name, $value)
    {
        if (is_array($value) || $value instanceof NodeInterface || $value instanceof NodeCollection) {
            $this->properties[$name] = $value;

        } else {
            $type = 'Sysgear\\StructuredData\\NodeInterface or Sysgear\\StructuredData\\NodeCollection';
            throw new \InvalidArgumentException("Second argument must be of type: map, {$type}");
        }
    }

    /**
     * Return the unique node id.
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name name.
     *
     * @param string $name
     * @return \Sysgear\StructuredData\Node
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