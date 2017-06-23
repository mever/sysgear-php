<?php

namespace Sysgear\StructuredData\Exporter;

use Sysgear\StructuredData\NodeCollection;
use Sysgear\StructuredData\NodeProperty;
use Sysgear\StructuredData\Node;

class XmlExporter extends AbstractExporter
{
    /**
     * @var \DOMDocument
     */
    protected $document;

    protected $formatOutput = false;

    protected $references = array();

    /**
     * Flag if output should be pretty-print.
     *
     * @param boolean $formatOutput
     * @return \Sysgear\StructuredData\Exporter\XmlExporter
     */
    public function formatOutput($formatOutput)
    {
        $this->formatOutput = (boolean) $formatOutput;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        if (null === $this->node) {
            throw ExporterException::noDataToExport();
        }

        $this->document = new \DOMDocument('1.0', 'UTF-8');
        $this->document->formatOutput = $this->formatOutput;
        $elem = $this->compiler($this->node);
        $elem->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xlink', 'http://www.w3.org/1999/xlink');
        $this->document->appendChild($elem);

        return rtrim($this->document->saveXML());
    }

    /**
     * Compile node to DOM element.
     *
     * @param Node $node Node to compile
     * @param string $sequence XPointer child sequence.
     * @param string $name Optional element name
     * @return \DOMElement
     * @throws \Exception
     */
    protected function compiler(Node $node, $sequence = '/1', $name = null)
    {
        $doc = $this->document;
        $hash = spl_object_hash($node);
        $elem = $doc->createElement($name ?: $node->getName());

        // check reference
        if (array_key_exists($hash, $this->references)) {
            $elem->setAttribute('xlink:href', "#element({$this->references[$hash]})");
            return $elem;
        }

        // create node
        $this->references[$hash] = $sequence;
        $elem->setAttribute('type', $node->getType());
        if ('' !== $this->metaTypeField) {
            $elem->setAttribute($this->metaTypeField, 'object');
        }

        foreach ($node->getMetadata() as $key => $meta) {
            $elem->setAttribute($key, $meta);
        }

        $childCount = 0;
        foreach ($node->getProperties() as $key => $n) {

            $childCount++;

            // set collection
            if ($n instanceof NodeCollection) {

                // set collection meta data
                $colElem = $doc->createElement($key);
                $colElem->setAttribute('type', $n->getType());
                if ('' !== $this->metaTypeField) {
                    $colElem->setAttribute($this->metaTypeField, 'collection');
                }
                foreach ($n->getMetadata() as $k => $meta) {
                    $colElem->setAttribute($k, $meta);
                }

                // set collection elements
                $pos = 0;
                foreach ($n as $e) {
                    $pos++;
                    if ($e instanceof Node) {
                        $colElem->appendChild($this->compiler($e, "{$sequence}/{$childCount}/{$pos}"));
                    } elseif ($e instanceof NodeProperty) {
                        $colElem->appendChild($this->compileProperty('item', $e));
                    } else {
                        throw new \Exception("NodeCollection in NodeCollection not implemented yet");
                    }
                }

                $elem->appendChild($colElem);
            }

            // set node
            elseif ($n instanceof Node) {
                $elem->appendChild($this->compiler($n, "{$sequence}/{$childCount}", $key));

            }

            // set primitive
            else {
                $elem->appendChild($this->compileProperty($key, $n));
            }
        }

        return $elem;
    }

    /**
     * @param string $name
     * @param NodeProperty $node
     * @return \DOMElement
     */
    protected function compileProperty($name, NodeProperty $node) {
        $propElem = $this->document->createElement($name);
        $propElem->setAttribute('type', $node->getType());
        $propElem->setAttribute('value', $node->getValue());
        if ('' !== $this->metaTypeField) {
            $propElem->setAttribute($this->metaTypeField, 'property');
        }

        return $propElem;
    }
}