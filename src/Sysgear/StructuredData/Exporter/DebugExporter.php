<?php

namespace Sysgear\StructuredData\Exporter;

use Sysgear\StructuredData\NodeInterface;
use Sysgear\StructuredData\NodeCollection;
use Sysgear\StructuredData\NodeProperty;
use Sysgear\StructuredData\Node;

class DebugExporter extends AbstractExporter
{
    const IDENT = ".";

    protected $lineCount = 1;

    protected $visited = array();

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        echo "\n\n";
        $this->visited = array();
        $this->printNode($this->node);
        return '';
    }

    protected function printNodeProperty(NodeInterface $node, $propertyName, $level = 0)
    {
        if ($node instanceof Node) {
            $this->printNode($node, $level + 1, $propertyName);

        } elseif ($node instanceof NodeProperty) {
            $this->printProperty($node, $level + 1, $propertyName);

        } else {
            $this->printCollection($node, $level + 1, $propertyName);
        }
    }

    public function printNode(Node $node, $level = 0, $propertyName = '')
    {
        $hash = spl_object_hash($node);
        $idents = str_repeat(self::IDENT, $level);
        if (null !== @$this->visited[$hash]) {
            $this->prnt("R{$idents}{$propertyName}" . $this->visited[$hash], true);
            return;
        }

        $mtype = json_encode($node->getMetadata());
        $line = "N{$idents}{$propertyName}{{$node->getName()}}: {$mtype}";
        $this->visited[$hash] = "[{$this->lineCount}]";
        $this->prnt($line);
        foreach ($node->getProperties() as $name => $prop) {
            $this->printNodeProperty($prop, $name, $level);
        }
    }

    public function printCollection(NodeCollection $collection, $level = 0, $propertyName = '')
    {
        $idents = str_repeat(self::IDENT, $level);
        $this->prnt("C{$idents}{$propertyName}{{$collection->getType()}}: ");
        foreach ($collection as $key => $item) {
            $this->printNodeProperty($item, $key, $level);
        }
    }

    public function printProperty(NodeProperty $property, $level = 0, $propertyName = '')
    {
        $idents = str_repeat(self::IDENT, $level);
        $this->prnt("P{$idents}{$propertyName}{{$property->getType()}}: {$property->getValue()}");
    }

    protected function prnt($line, $isRef = false)
    {
        $this->lineCount++;
        echo $line . "\n";
    }
}