<?php

namespace Sysgear\StructuredData\Collector;

use Sysgear\Backup\BackupableInterface;
use Sysgear\StructuredData\NodeCollection;
use Sysgear\StructuredData\NodeRef;
use Sysgear\StructuredData\Node;

/**
 * Collector for backup data from backupable objects.
 *
 * @author (c) Martijn Evers <martijn4evers@gmail.com>
 */
class BackupCollector extends AbstractObjectCollector
{
    /**
     * When true only collect data from the first
     * implementor of the backupable interface, start search from parent to subclass.
     *
     * @var boolean
     */
    public $onlyImplementor = false;

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

        $this->addedObjects[] = $object;
        foreach ($options as $key => $value) {
            $this->_setOption($key, $value);
        }

        $name = $this->getNodeName($object);
        $className = $this->getClassName($object);
        $objHash = spl_object_hash($object);

        if ($this->reference) {
            $this->node = new NodeRef($objHash, $name);

        } else {

            $this->node = new Node($objHash, $name);
            $this->node->setMetadata('type', 'object');
            $this->node->setMetadata('class', $className);

            $refClass = new \ReflectionClass($object);
            foreach ($refClass->getProperties() as $property) {
                $property->setAccessible(true);
                if ($this->filterProperty($property)) {

                    $name = $property->getName();
                    $value = $property->getValue($object);
                    if (is_scalar($value)) {
                        $this->node->setProperty($name, array(
                            'type' => gettype($value),
                            'value' => $value));

                    } elseif ($this->followCompositeNodes) {
                        $this->addCompositeProperty($name, $value);
                    }
                }
            }
        }
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
            $object = $property->getDeclaringClass();
            $className = $this->getFirstClassnameImplementing($object,
                '\\Sysgear\\Backup\\BackupableInterface');

            if ($property->getDeclaringClass()->getName() === $className) {
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
        if (1 === $this->descentLevel) {
            $doNotDescent = true;
        } else {
            $doNotDescent = in_array($name, $this->doNotDescent, true);
            $this->descentLevel -= 1;
        }

        // scan BackupableInterface implmentation
        if ($value instanceof BackupableInterface) {
            $this->node->setProperty($name, $this->createChildNode($value, $doNotDescent));
        }

        // scan sub-collection
        if (is_array($value) || ($value instanceof \IteratorAggregate)) {

            $collection = new NodeCollection();
            if (! is_array($value)) {
                $collection->setMetadata('class', get_class($value));
                $collection->setMetadata('id', spl_object_hash($value));
            }

            foreach ($value as $elem) {

                // collect array element objects implementing the BackupableInterface
                if ($elem instanceof BackupableInterface) {
                    $node = $this->createChildNode($elem, $doNotDescent, $name);
                    $node->setName($name);
                    $collection->add($node);
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
     * @return \Sysgear\StructuredData\NodeInterface
     */
    protected function createChildNode(BackupableInterface $backupable, $doNotDescent)
    {
        // make a copy of this collector to allow recursive collecting
        $collector = new self($this->persistentOptions);
        $collector->addedObjects =& $this->addedObjects;

        // prevent infinite loops...
        if (in_array($backupable, $this->addedObjects, true)) {
            $collector->followCompositeNodes = false;
            $collector->reference = true;

        } elseif ($doNotDescent) {
            $collector->followCompositeNodes = false;
        }

        $backupable->collectStructedData($collector);
        return $collector->getNode();
    }

    /**
     * Return the node name which represents this $object.
     *
     * @param \Sysgear\Backup\BackupableInterface $backupable
     * @return string
     */
    protected function getNodeName(BackupableInterface $backupable)
    {
        return parent::getNodeName($this->getClassName($backupable));
    }

    /**
     * Return the class name of $backupable
     *
     * @param \Sysgear\Backup\BackupableInterface $backupable
     * @return string Fully qualified class name
     */
    protected function getClassName(BackupableInterface $backupable)
    {
        if (null !== $this->className) {
            return $this->className;
        }

        if ($this->onlyImplementor) {
            return $this->getFirstClassnameImplementing($backupable,
                '\\Sysgear\\Backup\\BackupableInterface');

        } else {
            return get_class($backupable);
        }
    }
}