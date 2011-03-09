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
     * e.g. preventing the Doctrine2 proxy objects from being collected.
     *
     * @var boolean
     */
    public $onlyImplementor = false;

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
     * Map of properties not recursively scan (follow).
     *
     * @var string[]
     */
    protected $doNotFollow = array();

    /**
     * Map of properties to ignore.
     *
     * @var string[]
     */
    protected $ignore = array();

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

    public function fromBackupable(BackupableInterface $backupable, array $options = array())
    {
        foreach ($options as $key => $value) {
            switch ($key) {
            case "doNotFollow":
                $this->doNotFollow = (array) $value;
                break;

            case "ignore":
                $this->ignore = (array) $value;
                break;
            }
        }

        $this->fromObject($backupable);
    }

    /**
     * (non-PHPdoc)
     * @see Sysgear\StructuredData\Collector.CollectorInterface::fromObject()
     */
    public function fromObject($object)
    {
        if (! ($object instanceof BackupableInterface)) {
            throw new CollectorException("Given object does not implement BackupableInterface.");
        }

        // Add this object to the list of excluded objects to
        // prevent infinite recursive collecting.
        $this->excludedObjects[] = $object;

        $name = $this->name ?: $this->getNodeName($object);
        $objHash = spl_object_hash($object);
        $this->element = $this->document->createElement($name);
        $this->element->setAttribute('type', 'object');
        $this->element->setAttribute('class', $this->getClassName($object));
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
                    if (in_array($name, $this->ignore, true) || ($this->onlyImplementor
                      && $property->getDeclaringClass()->getName() !== $this->getClassName($object))) {
                        continue;
                    }

                    $value = $property->getValue($object);

                    // Scan scalar or composite property.
                    if (is_scalar($value)) {
                        $this->addScalarNode($name, $value);
                    } elseif ($this->recursiveScan) {

                        if (in_array($name, $this->doNotFollow, true)) {
                            $this->excludedObjects[] = $value;
                        }

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
        // Scan BackupableInterface implmentation
        if ($value instanceof BackupableInterface) {
            $this->addBackupable($name, $value);
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
                    $this->addBackupable($this->getNodeName($elem), $elem, $collection);
                }
            }
        }
    }

    /**
     * Add child node to this collection.
     *
     * @param string $name
     * @param \Sysgear\Backup\BackupableInterface $backupable
     * @param \DOMNode $node
     */
    protected function addBackupable($name, BackupableInterface $backupable, \DOMNode $node = null)
    {
        if (null === $node) {
            $node = $this->element;
        }

        // Prevent infinite loops...
        if (in_array($backupable, $this->excludedObjects, true)) {

            $this->createReference($backupable, $node, $name);
        } else {

            // Make a copy of this collector to allow recursive collecting.
            $collector = clone $this;
            $collector->name = $name;
            $backupable->collectStructedData($collector);
            $element = $collector->getDomElement();
            $node->appendChild($element);
        }
    }

    /**
     * Create a reference to an already collected object.
     *
     * @param \BackupableInterface $backupable
     * @param \DOMNode $node
     * @param string $name
     */
    protected function createReference(BackupableInterface $backupable, \DOMNode $node, $name)
    {
        $collector = clone $this;
        $collector->recursiveScan = false;
        $collector->reference = true;
        $collector->name = $name;
        $backupable->collectStructedData($collector);
        
        $element = $collector->getDomElement();
        if (in_array($name, $this->doNotFollow, true)) {
            $element->setAttribute('id', $element->getAttribute('ref'));
            $element->removeAttribute('ref');
        }
        $node->appendChild($element);
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