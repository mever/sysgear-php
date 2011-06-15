<?php

namespace Sysgear\StructuredData\Collector;

use Sysgear\Backup\BackupableInterface;

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
     * Preventing the Doctrine2 proxy objects from being collected.
     *
     * @var boolean
     */
    public $ignoreDoctrineProxies = true;

    /**
     * This object is a reference.
     *
     * @var boolean
     */
    protected $reference = false;

    /**
     * Name to use for this node.
     *
     * @var string
     */
    protected $name;

    /**
     * Do not descent into the properties of these relations.
     *
     * @var string[]
     */
    protected $doNotDescent = array();

    /**
     * Array of properties to ignore.
     *
     * @var string[]
     */
    protected $ignore = array();

    /**
     * Array of properties. When set
     * only those properties will be collected.
     *
     * @var string[] | null
     */
    protected $onlyInclude;

    /**
     * Name of class to collect.
     *
     * @var string
     */
    protected $className;

    /**
     * Restore state, no remaining properties or other
     * business that shouldn't be cloned.
     */
    public function __clone()
    {
        $this->ignore = array();
        $this->doNotDescent = array();
        $this->onlyInclude = null;
        $this->className = null;
    }

    /**
     * Set option.
     *
     * @param string $key
     * @param mixed $value
     */
    public function setOption($key, $value)
    {
        switch ($key) {
        case 'onlyImplementor':
            $this->onlyImplementor = (boolean) $value;
            break;

        default:
            parent::setOption($key, $value);
            break;
        }
    }

    /**
     * (non-PHPdoc)
     * @see Sysgear\StructuredData\Collector.CollectorInterface::fromObject()
     */
    public function fromObject($object, array $options = array())
    {
        if (! ($object instanceof BackupableInterface)) {
            throw new CollectorException("Given object does not implement BackupableInterface.");
        }

        // apply instance options. These apply only
        // to this instance of the collector.
        foreach ($options as $key => $value) {
            switch ($key) {
            case "doNotDescent":
                $this->doNotDescent = (array) $value;
                break;

            case "ignore":
                $this->ignore = (array) $value;
                break;

            case "onlyInclude":
                $this->onlyInclude = (null === $value) ? null : (array) $value;
                break;

            case "className":
                $this->className = (string) $value;
                break;
            }
        }

        // Add this object to the list of excluded objects to
        // prevent infinite recursive collecting.
        $this->addedObjects[] = $object;

        $className = $this->className ?: $this->getClassName($object);
        $name = $this->name ?: $this->getNodeName($object);
        $objHash = spl_object_hash($object);
        $this->element = $this->document->createElement($name);
        $this->element->setAttribute('type', 'object');
        $this->element->setAttribute('class', $className);
        $refClass = new \ReflectionClass($object);
        if ($this->reference) {

            // Create reference.
            $this->element->setAttribute('ref', $objHash);
        } else {
            $this->element->setAttribute('id', $objHash);
            foreach ($refClass->getProperties() as $property) {

                // Exclude properties.
                if ($this->filterProperty($property)) {

                    $property->setAccessible(true);
                    $name = $property->getName();

                    if (null !== $this->onlyInclude && (! in_array($name, $this->onlyInclude, true))) {
                        continue;
                    }

                    if (in_array($name, $this->ignore, true) || ($this->onlyImplementor
                      && $property->getDeclaringClass()->getName() !== $className)) {
                        continue;
                    }

                    // Scan scalar or composite property.
                    $value = $property->getValue($object);
                    if (is_scalar($value)) {
                        $this->addScalarNode($name, $value);
                    } elseif ($this->followCompositeNodes) {
                        $this->addCompositeNode($name, $value);
                    }
                }
            }
        }
        $this->document->appendChild($this->element);
    }

    /**
     * Add scalar property node.
     *
     * @param string $name
     * @param scalar $value
     */
    protected function addScalarNode($name, $value)
    {
        $property = $this->document->createElement($name);
        $this->element->appendChild($property);
        $property->setAttribute('type', gettype($value));
        $property->setAttribute('value', $value);
    }

    /**
     * Add composite property node.
     *
     * @param string $name
     * @param mixed $value
     */
    protected function addCompositeNode($name, $value)
    {
        if (1 === $this->descentLevel) {
            $doNotDescent = true;
        } else {
            $doNotDescent = in_array($name, $this->doNotDescent, true);
            $this->descentLevel -= 1;
        }

        // Scan BackupableInterface implmentation
        if ($value instanceof BackupableInterface) {
            $this->element->appendChild($this->createNode($name, $value, $doNotDescent));
        }

        // Scan sub-collection.
        if (is_array($value) || ($value instanceof \IteratorAggregate)) {

            $collection = $this->document->createElement($name);
            $collection->setAttribute('type', 'array');
            $this->element->appendChild($collection);
            if (! is_array($value)) {
                $collection->setAttribute('class', get_class($value));
                $collection->setAttribute('id', spl_object_hash($value));
            }

            foreach ($value as $elem) {

                // Collect array element objects implementing the BackupableInterface.
                if ($elem instanceof BackupableInterface) {
                    $node = $this->createNode($this->getNodeName($elem), $elem, $doNotDescent);
                    $collection->appendChild($node);
                }
            }
        }
    }

    /**
     * Create child node from backupable.
     *
     * @param string $name
     * @param \Sysgear\Backup\BackupableInterface $backupable
     * @param boolean $doNotDescent
     * @return \DOMNode
     */
    protected function createNode($name, BackupableInterface $backupable, $doNotDescent)
    {
        // Make a copy of this collector to allow recursive collecting.
        $collector = clone $this;
        $collector->addedObjects =& $this->addedObjects;
        $collector->name = $name;

        // Prevent infinite loops...
        if (in_array($backupable, $this->addedObjects, true)) {
            $collector->followCompositeNodes = false;
            $collector->reference = true;

        } elseif ($doNotDescent) {
            $collector->followCompositeNodes = false;
        }

        $backupable->collectStructedData($collector);
        return $collector->getDomElement();
    }

    /**
     * Return the node name which represents this $object.
     *
     * @param \Sysgear\Backup\BackupableInterface $backupable
     * @return string
     */
    protected function getNodeName($backupable)
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

            // Fetches the oldest parent name which implements the backupable interface.
            $previousClass = $class = get_class($backupable);
            while (false !== $class) {
                $refClass = new \ReflectionClass($class);
                if (! $refClass->implementsInterface('\\Sysgear\\Backup\\BackupableInterface')) {
                    break;
                }
                $previousClass = $class;
                $class = get_parent_class($class);
            }
            return $previousClass;

        } else {
            return get_class($backupable);
        }
    }
}