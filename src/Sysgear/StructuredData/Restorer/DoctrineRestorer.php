<?php

namespace Sysgear\StructuredData\Restorer;

use Sysgear\StructuredData\NodeInterface,
    Sysgear\StructuredData\NodeCollection,
    Sysgear\StructuredData\NodeProperty,
    Sysgear\StructuredData\Node,
    Sysgear\Util;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\DBAL\Types\Type;

class DoctrineRestorer extends AbstractRestorer
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var array
     */
    protected $list;

    /**
     * Set option.
     *
     * @param string $key
     * @param mixed $value
     */
    protected function _setOption($key, $value)
    {
        switch ($key) {
            case 'entityManager':
                $this->entityManager = ($value instanceof EntityManager) ? $value : null;
                break;

            default:
                parent::_setOption($key, $value);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function restore(Node $node, $entityManager = null)
    {
        $node->setMetadata('$$insert', true);

        // (re)place entity manager
        if ($entityManager instanceof EntityManager) {
            $this->entityManager = $entityManager;
        }

        // check entity manager
        if (null === $this->entityManager) {
            throw new \InvalidArgumentException("No entity manager given.");
        }

        $this->list = array();
        $this->restoreNode($node);
        $this->entityManager->flush();
        return $this->list;
    }

    /**
     * Restore entity with node.
     *
     * @param Node $node
     * @return object Restored entity.
     */
    protected function restoreNode(Node $node)
    {
        // node already merged
        $hash = spl_object_hash($node);
        if (array_key_exists($hash, $this->list)) {
            return $this->list[$hash];
        }

        $insert = function(Node $node) {
            $deferredProperties = array();
            $entity = $this->createEntity($node, $deferredProperties);
            $this->insertEntity($entity, $node);
            foreach ($deferredProperties as $name => $propDesc) {
                list($prop, $insert) = $propDesc;
                $this->setProperty($entity, $name, $prop, $insert);
            }

            return $entity;
        };

        if ($this->isInsert($node)) {
            return $insert($node);

        } else {
            $entity = $this->findEntity($node);
            if (null === $entity) {
                $this->logMissing($node);
                return $insert($node);
            } else {
                $this->list[$hash] = $entity;
                $this->logFound($node);
            }
        }

        return $entity;
    }

    /**
     * Return true if the given node should be inserted.
     *
     * @param Node $node
     * @return boolean
     */
    protected function isInsert(Node $node) {
        return $node->getMeta('$$insert', false);
    }

    /**
     * Accept a node and build an entity without id fields with it.
     *
     * @param Node $node
     * @param array $deferredProperties
     * @return object Entity
     */
    protected function createEntity(Node $node, array &$deferredProperties)
    {
        // create entity and put it on the list
        $className = $this->getClass($node);
        $entity = Util::createInstanceWithoutConstructor($className);
        $this->list[spl_object_hash($node)] = $entity;

        $metadata = $this->entityManager->getClassMetadata($className);
        $idFields = $metadata->isIdGeneratorIdentity() ? $metadata->getIdentifierFieldNames() : array();

        $mergeFields = json_decode($node->getMeta('merge', '[]'), true);
        foreach ($node->getProperties() as $name => $prop) {
            if (! in_array($name, $idFields, true)) {
                $insert = (! in_array($name, $mergeFields, true));
                $this->createEntityProperty($node, $metadata, $entity, $name, $prop, $deferredProperties, $insert);
            }
        }

        return $entity;
    }

    /**
     * Create an entity property. If the property is not mandatory and an association
     * push it on the $deferredProperties array.
     *
     * @param Node $node
     * @param ClassMetadata $metadata
     * @param object $entity
     * @param string $name
     * @param NodeInterface $prop
     * @param array $deferredProperties
     * @param boolean $insert
     */
    protected function createEntityProperty(Node $node, ClassMetadata $metadata, $entity, $name, NodeInterface $prop, array &$deferredProperties, $insert)
    {
        // set a primitive property (e.g.: string, int, datetime)
        if ($prop instanceof NodeProperty) {
            $this->setProperty($entity, $name, $prop, $insert);
            return;
        }

        if ($this->isPhpArray($prop)) {
            $this->setProperty($entity, $name, $prop);
            return;
        }

        // update inverse side of association properties
        if ($metadata->isAssociationInverseSide($name)) {
            $targetName = $metadata->getAssociationMappedByTargetField($name);
            if ($prop instanceof Node) {
                $prop->setProperty($targetName, $node);

            } elseif ($prop instanceof NodeCollection) {
                foreach ($prop as $child) {
                    $child->setProperty($targetName, $node);
                }
            }
        }

        // set a X-to-one or X-to-many association
        if ($this->isAssociationMandatory($metadata, $name)) {
            $this->setProperty($entity, $name, $prop, $insert);
        } else {
            $deferredProperties[$name] = array($prop, $insert);
        }
    }

    /**
     * Return true if given name is a mandatory assocciation. Or false when not.
     *
     * @param ClassMetadata $metadata
     * @param string $associationName
     * @throws \LogicException
     * @return boolean
     */
    protected function isAssociationMandatory(ClassMetadata $metadata, $associationName)
    {
        if ($metadata->hasAssociation($associationName)) {
            $mapping = $metadata->getAssociationMapping($associationName);
            $joinColumns = @$mapping['joinColumns'];
            $isOwning = (null !== $joinColumns);
            if ($isOwning) {
                foreach ($joinColumns as $column) {
                    if (! @$column['nullable']) {
                        return true;
                    }
                }
            }

        } else {
            throw new \LogicException("{$associationName} is not an association of {$metadata->getName()}");
        }

        return false;
    }

    /**
     * Set the property value of entity.
     *
     * @param object $entity
     * @param string $name
     * @param NodeInterface $valueNode
     * @param boolean $insert
     */
    protected function setProperty($entity, $name, NodeInterface $valueNode, $insert = false)
    {
        $rObject = new \ReflectionObject($entity);
        $rProp = $this->getInheritedReflectionProperty($rObject, $name);
        if (null !== $rProp) {
            $rProp->setAccessible(true);
            $rProp->setValue($entity, $this->getValue($valueNode, $insert));
        }
    }

    /**
     * Get the actual property value.
     *
     * @param NodeInterface $node
     * @param boolean $insert
     * @return mixed
     */
    protected function getValue(NodeInterface $node, $insert = false)
    {
        // we assume here an entity property of type array which is not a Doctrine association
        if ($this->isPhpArray($node)) {
            $array = [];
            /** @var NodeProperty $elem */
            foreach ($node as $elem) {
                $array[] = $elem->getValue();
            }
            return count($array) ? $array : null;
        }

        $value = null;
        if ($node instanceof NodeProperty) {
            $value = $node->getValue();

        } else {
            if ($node instanceof Node) {
                if ($insert) {
                    $node->setMetadata('$$insert', true);
                }

                $value = $this->restoreNode($node);

            } elseif ($node instanceof NodeCollection) {
                $value = array();
                foreach ($node as $elem) {

                    $meta = $node->getMetadata();
                    if (isset($meta['merge'])) {
                        $elem->setMetadata('merge', $meta['merge']);
                    }

                    if ($insert) {
                        $elem->setMetadata('$$insert', true);
                    }

                    $childNode = $this->restoreNode($elem);
                    $key = $elem->getMeta('key');
                    if (null === $key) {
                        $value[] = $childNode;

                    } else {
                        $isEnum = ('i' === $key[0]);
                        $key = substr($key, 2);
                        $val = ($isEnum) ? (integer) $key : (string) $key;
                        $value[$val] = $childNode;
                    }
                }
            }
        }

        return $value;
    }

    /**
     * Find a stored entity by node.
     *
     * @param Node $node
     * @throws \RuntimeException
     * @return object|null Found entity.
     */
    protected function findEntity(Node $node)
    {
        $mergeFields = $node->getMeta('merge-fields');
        if (null !== $mergeFields) {
            $mergeFields = json_decode($mergeFields, true);
        }

        if (! is_array($mergeFields)) {
            return null;
        }

        $criteria = array();
        foreach ($mergeFields as $field) {
            $value = $node->getProperty($field);
            if ($value instanceof NodeProperty) {
                $criteria[$field] = $value->getValue();

            } elseif ($value instanceof Node) {
                $criteria[$field] = $this->getIdentity($this->findEntity($value));

            } else {
                $criteria[$field] = null;
            }
        }

        return $this->entityManager->getRepository($this->getClass($node))->findOneBy($criteria);
    }

    /**
     * Get property of reflection class. Include inherited properties.
     *
     * @param \ReflectionClass $reflectionClass
     * @param string $name
     * @return \ReflectionProperty
     */
    protected function getInheritedReflectionProperty(\ReflectionClass $reflectionClass, $name)
    {
        if ($reflectionClass->hasProperty($name)) {
            return $reflectionClass->getProperty($name);

        } else {
            $reflectionParent = $reflectionClass->getParentClass();
            if ($reflectionParent) {
                return $this->getInheritedReflectionProperty($reflectionParent, $name);
            }
        }
    }

    /**
     * Return class name of entity to restore.
     *
     * @param Node $node
     * @return string
     */
    protected function getClass(Node $node)
    {
        $class = $node->getMeta('class');
        if (null === $class) {
            throw new \RuntimeException("Cannot restore node '{$node->getName()}', missing class metadata");
        }

        return $class;
    }

    protected function insertEntity($entity, Node $describeNode = null)
    {
        $this->entityManager->persist($entity);
        $this->entityManager->flush($entity);

        $logger = $this->logger;
        if (null !== $logger) {
            if (method_exists($entity, '__toString')) {
                $desc = $entity->__toString();

            } else {
                $desc = get_class($entity) . ': ';
                if (null !== $describeNode) {
                    $desc .= json_encode($this->getDescFields($describeNode));
                }
            }

            $logger("insert new entity {$desc}", 'debug');
        }
    }

    /**
     * Log a missing node.
     *
     * @param Node $node
     */
    protected function logMissing(Node $node)
    {
        $logger = $this->logger;
        if (null !== $logger) {
            $logger("could not find {$node->getMeta('class')}: " . json_encode($this->getDescFields($node, true)), 'warning');
        }
    }

    /**
     * Log a found node.
     *
     * @param Node $node
     */
    protected function logFound(Node $node)
    {
        $logger = $this->logger;
        if (null !== $logger) {
            $logger("find {$node->getMeta('class')}: " . json_encode($this->getDescFields($node, true)), 'debug');
        }
    }

    /**
     * Return an array with discriptive fields for the given node.
     *
     * @param Node $node
     * @return array
     */
    protected function getDescFields(Node $node, $onlyMergeField = false)
    {
        $mergeFields = $onlyMergeField ? json_decode($node->getMeta('merge-fields', '[]'), true) : null;

        $fields = array();
        foreach ($node->getProperties() as $name => $prop) {
            if ($prop instanceof NodeProperty && (null === $mergeFields || in_array($name, $mergeFields, true))) {
                $fields[$name] = $prop->getValue();
            }
        }

        return $fields;
    }

    /**
     * Get identity fields from entity.
     *
     * @param object $entity
     * @return array
     */
    protected function getIdentity($entity)
    {
        $identity = array();
        $reflectionClass = new \ReflectionClass($entity);
        $metadata = $this->entityManager->getClassMetadata(get_class($entity));
        foreach ($metadata->getIdentifierFieldNames() as $field) {
            $reflectionProperty = $reflectionClass->getProperty($field);
            $reflectionProperty->setAccessible(true);
            $identity[$field] = $reflectionProperty->getValue($entity);
        }

        return $identity;
    }

    /**
     * Return if given node represents a simple PHP array.
     *
     * @param NodeInterface $node
     * @return bool
     */
    protected function isPhpArray(NodeInterface $node) {
        return $node instanceof NodeCollection && !isset($node->getMetadata()['class']);
    }
}