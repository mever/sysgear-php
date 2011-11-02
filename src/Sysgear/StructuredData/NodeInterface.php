<?php

namespace Sysgear\StructuredData;

interface NodeInterface
{
    /**
     * Create a new node instance.
     *
     * @param string $id
     * @param string $name
     */
    public function __construct($id, $name = 'node');

    /**
     * Return the unique node id.
     *
     * @return string
     */
    public function getId();

    /**
     * Set name name.
     *
     * @param string $name
     * @return \Sysgear\StructuredData\NodeInterface
     */
    public function setName($name);

    /**
     * Return the descriptive node name.
     *
     * @return string
     */
    public function getName();
}