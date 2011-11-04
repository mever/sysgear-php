<?php

namespace Sysgear\StructuredData;

class NodeProperty extends NodeInterface
{
    protected $value;

    /**
     * Create a new node instance.
     *
     * @param string $id Buildin type: int, string, etc...
     * @param mixed $value Property value
     */
    public function __construct($id, $value = null)
    {
        parent::__construct($id);
        if (null !== $value) {
            $this->setValue($value);
        }
    }

    public function setValue($value)
    {
        $this->value = $value;
    }

    public function getValue()
    {
        return $this->value;
    }
}