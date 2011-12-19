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
     * (non-PHPdoc)
     * @see Sysgear\StructuredData\Importer.ImporterInterface::fromString()
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
     */
    protected function compile(\DOMElement $domNode, $sequence = '/1')
    {
        // check if reference
        $href = $domNode->getAttributeNS('http://www.w3.org/1999/xlink', 'href');
        if ('' !== $href) {
            return $this->references[$href];
        }

        // collect node attributes
        $type = null;
        $value = null;
        $metaDatas = array();
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
                    $metaDatas[$attribute->nodeName] = $attribute->nodeValue;
            }
        }

        // create node
        $childCount = 1;
        switch ($nodeType) {
            case self::NODE_TYPE_OBJECT:
                $node = new Node($type, $domNode->nodeName);
                foreach ($domNode->childNodes as $child) {
                    if (\XML_ELEMENT_NODE === $child->nodeType) {
                        $node->setProperty($child->nodeName, $this->compile($child, $sequence . "/{$childCount}"));
                        $childCount++;
                    }
                }

                foreach ($metaDatas as $name => $data) {
                    $node->setMetadata($name, $data);
                }
                break;

            case self::NODE_TYPE_COLLECTION:
                $collection = array();
                foreach ($domNode->childNodes as $child) {
                    if (\XML_ELEMENT_NODE === $child->nodeType) {
                        $collection[] = $this->compile($child, $sequence . "/{$childCount}");
                        $childCount++;
                    }
                }
                $node = new NodeCollection($collection, $type);
                break;

            case self::NODE_TYPE_PROPERTY:
                $node = new NodeProperty($type, $value);
                break;

            default:
                throw ImporterException::couldNotDetermineNodeType($nodeType);
        }

        // add reference and return in-memory node
        $this->references["#element({$sequence})"] = $node;
        return $node;
    }
}