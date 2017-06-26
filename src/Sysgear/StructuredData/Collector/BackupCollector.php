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
    const MERGE_FLAG = 1;
    const MERGE_ONLY = 2;

    /**
     * When true only collect data from the implementer of the
     * backupable interface. E.i. only collect properties declared in the
     * class implementing the backupable interface. Also derive the node name
     * from this class.
     *
     * If multiple classes in the inheritance chain implement the interface, the
     * last parent implementing the interface is picked. Start searching the last
     * subclass and work up the parent chain.
     *
     * @var boolean
     */
    protected $onlyImplementer = false;

    /**
     * List of composite fields to merge. First element is
     * always the merge mode: self::MERGE_*
     *
     * @var array
     */
    protected $merge;

    /**
     * Is merging in process.
     *
     * @var integer
     */
    protected $merging;

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
            case 'onlyImplementer':
                $this->onlyImplementer = (boolean) $value;
                break;

            case 'merge':
                $merge = (array) $value;
                array_unshift($merge, self::MERGE_FLAG);
                $this->merge = $merge;
                break;

            case 'mergeOnly':
                $merge = (array) $value;
                array_unshift($merge, self::MERGE_ONLY);
                $this->merge = $merge;
                break;

            case 'inventoryManager':
                $this->inventoryManager = ($value instanceof InventoryManager) ? $value : null;
                break;

            default:
                parent::_setOption($key, $value);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function fromObject($object, array $options = array())
    {
        // check interface
        if (! ($object instanceof BackupableInterface)) {
            throw new CollectorException("Given object does not implement BackupableInterface.");
        }

        // determine node name
        $name = (null === $this->node) ? $this->getNodeName($object) : $this->node->getName();

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
            $this->node->setMetadata('class', $this->getClass($object));
            $this->addedObjects[$objHash] = $this->node;

            if (is_array(@$options['mergeFields'])) {
                $this->node->setMetadata('merge-fields', json_encode($options['mergeFields']));
            }

            // merge mode enabled, include only fields that can be used to restore this backup
            if (null !== $this->merging && is_array(@$options['mergeFields'])) {

                if (self::MERGE_ONLY === $this->merging) {
                    $this->onlyInclude = $options['mergeFields'];
                    $this->followCompositeNodes = true;
                    $this->doNotDescent = array();
                }

            } elseif (null !== $this->merge) {
                $this->node->setMetadata('merge', json_encode($this->merge));
            }

            // collect data to populate the node with
            $refClass = new \ReflectionClass($this->getClass($object));
            foreach ($this->getProperties($refClass) as $property) {
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
                    }

                    // add composite (node) property
                    elseif ($this->followCompositeNodes) {
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
     * @return boolean
     */
    protected function filterProperty(\ReflectionProperty $property)
    {
        if (! parent::filterProperty($property)) {
            return false;
        }

        if ($this->onlyImplementer) {
            $className = $this->getClass($property->getDeclaringClass()->getName());
            if ($property->getDeclaringClass()->getName() !== $className) {
                return false;
            }
        }

        if ($this->skipInterfaces) {
            $interfaces = $property->getDeclaringClass()->getInterfaceNames();
            if (array_intersect($interfaces, $this->skipInterfaces)) {
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

        // scan BackupableInterface implementation
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
            foreach ($value as $key => $val) {

                // collect array element objects implementing the BackupableInterface
                if ($val instanceof BackupableInterface) {

                    if (null !== $propertyPath) {
                        $elemPath = clone $propertyPath;
                        $elemPath->add(NodePath::NODE, $this->getNodeName($val), $count);
                        $count++;

                        if (! $this->inventoryManager->isAllowed($elemPath)) {
                            continue;
                        }
                    }

                    $node = $this->createChildNode($val, $doNotDescent, $elemPath, $name);
                    if (null !== $node && $node instanceof Node) {
                        $node->setMetadata('key', (is_integer($key) ? 'i' : 's') . ';' . $key);
                        $collection->add($node);
                        if (null !== $this->merge) {
                            $node->setMetadata('merge', json_encode($this->merge));
                        }
                    }

                } elseif (is_scalar($val)) {
                    $collection->add(new NodeProperty(gettype($val), $val));
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
     * @param NodePath $path preceding path
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

        if (null !== $this->merge && in_array($name, $this->merge, true)) {
            $collector->merging = $this->merge[0];
        }

        $backupable->collectStructuredData($collector);
        return $collector->getNode();
    }

    /**
     * Return the class of $object
     *
     * @param object|string $object Class name or class instance.
     * @return string Fully qualified class name
     */
    protected function getClass($object)
    {
        if ($this->onlyImplementer) {
            return $this->getFirstClassNameImplementing($object,
                '\\Sysgear\\Backup\\BackupableInterface');

        } else {
            return parent::getClass($object);
        }
    }

    /**
     * Get properties of reflection class. Include inherited properties.
     *
     * @param \ReflectionClass $rClass
     * @return \ReflectionProperty[]
     */
    protected function getProperties(\ReflectionClass $rClass)
    {
        $properties = array();
        $rParent = $rClass->getParentClass();
        if ($rParent) {
            foreach ($this->getProperties($rParent) as $name => $prop) {
                $properties[$name] = $prop;
            }
        }

        foreach ($rClass->getProperties() as $prop) {
            $properties[$prop->getName()] = $prop;
        }

        return $properties;
    }
}