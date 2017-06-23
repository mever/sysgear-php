<?php

namespace Sysgear\StructuredData\Importer;

use Sysgear\StructuredData\NodeCollection;
use Sysgear\StructuredData\NodeProperty;
use Sysgear\StructuredData\Node;

class XmlImporter extends AbstractImporter
{
    /**
     * @var \DOMDocument
     */
    protected $document;

    /**
     * Holds a reference to each in-memory node.
     *
     * @var array
     */
    protected $references = array();

    /**
     * @return \DOMDocument
     */
    protected function getDocument()
    {
        if (null === $this->document) {
            $this->document = new \DOMDocument('1.0', 'UTF-8');
        }

        return $this->document;
    }

    /**
     * {@inheritdoc}
     */
    public function fromString($string)
    {
        $doc = $this->getDocument();
        $doc->loadXML($string);

        foreach ($doc->childNodes as $child) {
            $this->node = $this->compile($child);
        }

        return $this->node;
    }

    /**
     * Compile a DOM node to a internal in-memory node.
     *
     * @param \DOMElement $domNode
     * @param string $sequence XPointer child sequence.
     * @return \Sysgear\StructuredData\NodeInterface
     * @throws ImporterException
     */
    protected function compile(\DOMElement $domNode, $sequence = '/1')
    {
        // check if reference
        $href = $domNode->getAttributeNS('http://www.w3.org/1999/xlink', 'href');
        if ('' !== $href) {
            $ref = @$this->references[$href];
            if (null === $ref) {
                throw new \RuntimeException("Node '{$sequence}' points to not existing node: {$href}");
            }

            return $ref;
        }

        // collect node attributes
        $type = null;
        $value = null;
        $metadata = array();
        $nodeType = null;
        foreach ($domNode->attributes as $attribute) {
            switch ($attribute->nodeName) {
                case 'type':
                    $type = $attribute->nodeValue;
                    break;

                case 'value':
                    $value = $attribute->nodeValue;
                    break;

                case $this->metaTypeField:
                    $nodeType = $attribute->nodeValue;
                    break;

                default:
                    $metadata[$attribute->nodeName] = $attribute->nodeValue;
            }
        }

        // create node
        $childCount = 1;
        switch ($nodeType) {
            case self::NODE_TYPE_OBJECT:
                $node = new Node($type, $domNode->nodeName);
                $this->references["#element({$sequence})"] = $node;
                foreach ($domNode->childNodes as $child) {
                    if (\XML_ELEMENT_NODE === $child->nodeType) {
                        $node->setProperty($child->nodeName, $this->compile($child, $sequence . "/{$childCount}"));
                        $childCount++;
                    }
                }

                foreach ($metadata as $name => $data) {
                    $node->setMetadata($name, $data);
                }
                break;

            case self::NODE_TYPE_COLLECTION:
                $node = new NodeCollection(array(), $type);
                $this->references["#element({$sequence})"] = $node;
                foreach ($domNode->childNodes as $child) {
                    if (\XML_ELEMENT_NODE === $child->nodeType) {
                        $node->add($this->compile($child, $sequence . "/{$childCount}"));
                        $childCount++;
                    }
                }
                foreach ($metadata as $name => $data) {
                    $node->setMetadata($name, $data);
                }
                break;

            case self::NODE_TYPE_PROPERTY:
                $node = new NodeProperty($type, $value);
                $this->references["#element({$sequence})"] = $node;
                break;

            default:
                throw ImporterException::couldNotDetermineNodeType($nodeType, $sequence);
        }

        return $node;
    }
}