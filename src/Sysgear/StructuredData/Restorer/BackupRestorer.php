<?php

namespace Sysgear\StructuredData\Restorer;

use Sysgear\StructuredData\Collector\BackupCollector;
use Sysgear\StructuredData\NodeProperty;
use Sysgear\StructuredData\NodeCollection;
use Sysgear\StructuredData\NodeInterface;
use Sysgear\StructuredData\Node;
use Sysgear\Backup\Exception;
use Sysgear\Backup\BackupableInterface;
use Closure;

/**
 * Concrete restorer for the Sysgear backup tool.
 *
 * @author (c) Martijn Evers <mevers47@gmail.com>
 */
class BackupRestorer extends ObjectRestorer
{
    /**
     * Name to use for this node.
     *
     * @var string
     */
    protected $name;

    /**
     * (non-PHPdoc)
     * @see Sysgear\StructuredData\Restorer.RestorerInterface::restore()
     */
    public function restore(Node $node, $object = null)
    {
        $this->node = $node;
        if (null === $object) {
            $object = $this->createObject($node);
        }

        return $this->restoreNode($node);
    }

    /**
     * Restore data to object.
     *
     * @param \StdClass $object
     * @return array Return a list of remaining properties, those that could not
     *               be set because the restorer was not able to access the
     *               modifiers of the $object.
     */
    public function toObject($object)
    {
        if (! ($object instanceof BackupableInterface)) {
            throw Exception::classIsNotABackable($object);
        }

        // add this object as possible reference and restore properties
        $remainingProperties = array();
        $this->createReferenceCandidate($object);
        $refClass = new \ReflectionClass($object);
        foreach ($this->node->getProperties() as $name => $node) {

            $property = $refClass->getProperty($name);
            $value = $this->getPropertyValue($node);
            if ($property->isPublic()) {
                $property->setValue($object, $value);
            } else {
                $remainingProperties[$name] = $value;
            }
        }

        // return properties which could not be set here (ie. private)
        return $remainingProperties;
    }

    protected function restoreNode(Node $node)
    {
        $object = $this->createObject($node);
        if ($object instanceof BackupableInterface) {
            $restorer = $this->getRestorer();
            $restorer->node = $node;
            $object->restoreStructedData($restorer);
        }

        return $object;
    }
}