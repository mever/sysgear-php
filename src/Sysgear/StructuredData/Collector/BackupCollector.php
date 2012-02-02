<?php

namespace Sysgear\StructuredData\Collector;

use Sysgear\Backup\BackupableInterface;
use Sysgear\Backup\InventoryManager;
use Sysgear\StructuredData\NodePath;
use Sysgear\StructuredData\NodeCollection;
use Sysgear\StructuredData\NodeProperty;
use Sysgear\StructuredData\Node;
use Sysgear\Util;

/**
 * Collector for backup data from backupable objects.
 *
 * @author (c) Martijn Evers <martijn4evers@gmail.com>
 */
class BackupCollector extends AbstractObjectCollector
{
    /**
     * When true only collect data from the first
     * implementor of the backupable interface, start search from super- to subclass.
     *
     * @var boolean
     */
    protected $onlyImplementor = false;

    /**
     * When true use the class name from the first implementor of the
     * backupable interface, start search from super- to subclass.
     *
     * @var boolean
     */
    protected $implClassName = false;

    /**
     * List of composite fields to merge.
     *
     * @var array
     */
    protected $merge;

    /**
     * Is merging in process.
     *
     * @var boolean
     */
    protected $merging = false;

    /**
     * @var \Sysgear\Backup\InventoryManager
     */
    protected $inventoryManager;

    /**
     * @var \Sysgear\StructuredData\NodePath
     */
    protected $currentPath;

    /**
     * Set option.
     *
     * @param string $key
     * @param mixed $value
     */
    protected function _setOption($key, $value)
    {
        switch ($key) {
            case 'onlyImplementor':
                $this->onlyImplementor = (boolean) $value;
                break;

            case 'implClassName':
                $this->implClassName = (boolean) $value;
                break;

            case 'merge':
                $this->merge = (array) $value;
                break;

            case 'inventoryManager':
                $this->inventoryManager = ($value instanceof InventoryManager) ? $value : null;
                break;

            default:
                parent::_setOption($key, $value);
        }
    }

    /**
     * (non-PHPdoc)
     * @see Sysgear\StructuredData\Collector.CollectorInterface::fromObject()
     */
    public function fromObject($object, array $options = array())
    {
        // check interface
        if (! ($object instanceof BackupableInterface)) {
            throw new CollectorException("Given object does not implement BackupableInterface.");
        }

        // determine node name
        $name = (null === $this->node) ?
            Util::getShortClassName($this->getClassName($object)) : $this->node->getName();

        // setup path (first time setup)
        if (null !== $this->inventoryManager && null === $this->currentPath) {
            $this->currentPath = new NodePath();
            $this->currentPath->add(NodePath::NODE, $name);
        }

        // setup options
        foreach ($options as $key => $value) {
            $this->_setOption($key, $value);
        }

        // create node
        if (null === $this->node) {
            $objHash = spl_object_hash($object);

            // create new node
            $this->node = new Node('object', $name);
            $this->node->setMetadata('class', $this->getClassName($object));
            $this->addedObjects[$objHash] = $this->node;

            // merge mode enabled, include only fields that can be used to restore this backup
            if ($this->merging && is_array(@$options['mergeFields'])) {
                $this->onlyInclude = $options['mergeFields'];
                $this->followCompositeNodes = true;
                $this->doNotDescent = array();

            } elseif (null !== $this->merge) {
                $this->node->setMetadata('merge', json_encode($this->merge));
            }

            // collect data to populate the node with
            $refClass = new \ReflectionClass(($this->onlyImplementor) ?
                $this->getFirstClassnameImplementing($object, '\\Sysgear\\Backup\\BackupableInterface') : $object);

            foreach ($refClass->getProperties() as $property) {
                $property->setAccessible(true);
                if ($this->filterProperty($property)) {

                    $name = $property->getName();
                    $value = $property->getValue($object);

                    // add scalar (value) node property
                    if (is_scalar($value)) {

                        // check inventory manager
                        if (null !== $this->currentPath) {
                            $propertyPath = new NodePath($this->currentPath);
                            $propertyPath->add(NodePath::VALUE, $name);
                            if (! $this->inventoryManager->isAllowed($propertyPath, $value)) {
                                continue;
                            }
                        }

                        $this->node->setProperty($name, new NodeProperty(gettype($value), $value));
                    } else

                    // add composite (node) property
                    if ($this->followCompositeNodes) {
                        $this->addCompositeProperty($name, $value);
                    }
                }
            }
        }

        return $this->node;
    }

    /**
     * Return true if property can be collected, else return false.
     *
     * @param \ReflectionProperty $property
     */
    protected function filterProperty(\ReflectionProperty $property)
    {
        if (! parent::filterProperty($property)) {
            return false;
        }

        if ($this->onlyImplementor) {
            $className = $this->getFirstClassnameImplementing(
                $property->getDeclaringClass()->getName(),
                '\\Sysgear\\Backup\\BackupableInterface');

            if ($property->getDeclaringClass()->getName() !== $className) {
                return false;
            }
        }

        return true;
    }

    /**
     * Add composite property node.
     *
     * @param string $name
     * @param mixed $value
     */
    protected function addCompositeProperty($name, $value)
    {
        $propertyPath = (null === $this->currentPath) ? null : new NodePath($this->currentPath);

        if (1 === $this->descentLevel) {
            $doNotDescent = true;
        } else {
            $doNotDescent = in_array($name, $this->doNotDescent, true);
            $this->descentLevel -= 1;
        }

        // scan BackupableInterface implmentation
        if ($value instanceof BackupableInterface) {

            // check path
            if (null !== $propertyPath) {
                $propertyPath->add(NodePath::NODE, $name);

                // check with inventory manager
                $node = ($this->inventoryManager->isAllowed($propertyPath)) ?
                    $this->createChildNode($value, $doNotDescent, $propertyPath, $name) : null;

            } else {
                $node = $this->createChildNode($value, $doNotDescent, null, $name);
            }

            if (null !== $node) {
                $this->node->setProperty($name, $node);
            }
        }

        // scan collection
        elseif (is_array($value) || ($value instanceof \IteratorAggregate)) {

            $collection = new NodeCollection();
            if (! is_array($value)) {
                $collection->setMetadata('class', get_class($value));
            }

            if (null !== $propertyPath) {
                $propertyPath->add(NodePath::COLLECTION, $name);
            }

            $count = 0;
            $elemPath = null;
            foreach ($value as $elem) {

                // collect array element objects implementing the BackupableInterface
                if ($elem instanceof BackupableInterface) {

                    if (null !== $propertyPath) {
                        $elemPath = clone $propertyPath;
                        $elemPath->add(NodePath::NODE, Util::getShortClassName($this->getClassName($elem)), $count);
                        $count++;

                        if (! $this->inventoryManager->isAllowed($elemPath)) {
                            continue;
                        }
                    }

                    $node = $this->createChildNode($elem, $doNotDescent, $elemPath, $name);
                    if (null !== $node) {
                        $collection->add($node);
                    }
                }
            }

            $this->node->setProperty($name, $collection);
        }
    }

    /**
     * Create child node from backupable.
     *
     * @param \Sysgear\Backup\BackupableInterface $backupable
     * @param boolean $doNotDescent
     * @param NodePath $path preseeding path
     * @param string $name
     * @return \Sysgear\StructuredData\NodeInterface
     */
    protected function createChildNode(BackupableInterface $backupable,
        $doNotDescent, NodePath $path = null, $name = null)
    {
        // make a copy of this collector to allow recursive collecting
        $collector = new self($this->persistentOptions);
        $collector->addedObjects =& $this->addedObjects;
        $collector->currentPath = $path;

        // prevent infinite loops...
        $objHash = spl_object_hash($backupable);
        if (array_key_exists($objHash, $this->addedObjects)) {
            return $this->addedObjects[$objHash];

        } elseif ($doNotDescent) {
            $collector->followCompositeNodes = false;
        }

        if (null !== $this->merge && in_array($name, $this->merge)) {
            $collector->merging = true;
        }

        $backupable->collectStructedData($collector);
        return $collector->getNode();
    }

    /**
     * Return the class name of $backupable
     *
     * TODO: fix getClassName and getNodeName
     *
     * @param \Sysgear\Backup\BackupableInterface $backupable
     * @return string Fully qualified class name
     */
    protected function getClassName(BackupableInterface $backupable)
    {
        if (null !== $this->className) {
            return $this->className;
        }

        if ($this->implClassName || $this->onlyImplementor) {
            return $this->getFirstClassnameImplementing($backupable,
                '\\Sysgear\\Backup\\BackupableInterface');

        } else {
            return get_class($backupable);
        }
    }
}