<?php

namespace Sysgear\StructuredData\Exporter;

use Sysgear\StructuredData\Node;

abstract class AbstractExporter implements ExporterInterface
{
    /**
     * @var \Sysgear\StructuredData\Node
     */
    protected $node;

    /**
     * While importing an exported in-memory node tree may be reconstructed. To
     * determine the type of nodes to use, a field is used with one of
     * the {@see self::NOTE_TYPE_*} constants to indicate the node type. This
     * fields specifies which field to use for this purpose.
     *
     * This field will not be exported when it contains an empty string.
     *
     * @var string
     */
    protected $metaTypeField = 'meta-type';

    /**
     * Set a custom meta-type field.
     *
     * @param string $field
     */
    public function setMetaTypeField($field)
    {
        if (empty($field)) {
            $this->metaTypeField = '';
        } else {
            $this->metaTypeField = $field;
        }
    }

    /**
     * (non-PHPdoc)
     * @see Sysgear\StructuredData\Exporter.ExporterInterface::setNode()
     */
    public function setNode(Node $node)
    {
        $this->node = $node;
        return $this;
    }
}