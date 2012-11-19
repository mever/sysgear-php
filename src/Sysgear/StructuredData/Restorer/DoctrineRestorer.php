<?php

namespace Sysgear\StructuredData\Restorer;

use Sysgear\StructuredData\NodeInterface,
    Sysgear\StructuredData\NodeCollection,
    Sysgear\StructuredData\NodeProperty,
    Sysgear\StructuredData\Node,
    Sysgear\StructuredData\NodePath,
    Sysgear\Util;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\DBAL\Types\Type;

class DoctrineRestorer extends AbstractRestorer
{
    /**
     * @var \Doctrine\ORM\EntityManager
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
     * (non-PHPdoc)
     * @see Sysgear\StructuredData\Restorer.RestorerInterface::restore()
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
        return $this->restoreNode($node);
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
        $oid = spl_object_hash($node);
        if (array_key_exists($oid, $this->list)) {
            return $this->list[$oid];
        }

        if ($this->isInsert($node)) {
            $deferredProperties = array();
            $partialEntity = $this->buildEntity($node, $deferredProperties);
            $entity = $this->entityManager->merge($partialEntity);
            $this->list[$oid] = $entity;

            foreach ($deferredProperties as $name => $prop) {
                $this->setProperty($entity, $name, $prop[0], $prop[1]);
            }

            $this->entityManager->flush();

        } else {
            $entity = $this->findEntity($node);
            $this->list[$oid] = $entity;
        }

        return $entity;
    }

    /**
     * Return true if the given node should be inserted.
     *
     * @param Node $node
     * @return boolean
     */
    protected function isInsert(Node $node)
    {
        return $node->getMeta('$$insert', false);
    }

    /**
     * Accept a node and build an entity without id fields with it.
     *
     * @param Node $node
     * @param array $deferredProperties
     * @return object Entity
     */
    protected function buildEntity(Node $node, array &$deferredProperties)
    {
        $entity = Util::createInstanceWithoutConstructor($this->getClass($node));
        $metadata = $this->entityManager->getClassMetadata($this->getClass($node));
        $idFields = $metadata->isIdGeneratorIdentity() ? $metadata->getIdentifierFieldNames() : array();

        $mergeFields = json_decode($node->getMeta('merge', '[]'));
        foreach ($node->getProperties() as $name => $prop) {
            if (! in_array($name, $idFields, true)) {
                $propInsert = (! in_array($name, $mergeFields, true));

                // updating the inverse side
                if ($prop instanceof NodeCollection && $metadata->isAssociationInverseSide($name)) {
                    $targetField = $metadata->getAssociationMappedByTargetField($name);
                    foreach ($prop as $child) {
                        $child->setProperty($targetField, $node);
                    }
                }

                // add deferred properties to the deferred list
                if ($this->isDeferredProperty($metadata, $name)) {
                    $deferredProperties[$name] = array($prop, $propInsert);

                } else {

                    // set all other properties directly
                    $this->setProperty($entity, $name, $prop, $propInsert);
                }
            }
        }

        return $entity;
    }

    /**
     * Accept metadata and a property name and return if the property
     * can be deferred.
     *
     * @param ClassMetadata $metadata
     * @param string $propertyName
     * @return boolean
     */
    protected function isDeferredProperty(ClassMetadata $metadata, $propertyName)
    {
        $defer = false;
        if ($metadata->hasAssociation($propertyName)) {
            $mapping = $metadata->getAssociationMapping($propertyName);
            $defer = ! $mapping['isOwningSide'];
        }

        return $defer;
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
        $reflObject = new \ReflectionObject($entity);
        $prop = $this->getProperty($reflObject, $name);
        if (null !== $prop) {
            $prop->setAccessible(true);
            $prop->setValue($entity, $this->getValue($valueNode, $insert));
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
     * @return object Found entity.
     */
    protected function findEntity(Node $node)
    {
        $mergeFields = $node->getMeta('merge-fields');
        if (null !== $mergeFields) {
            $mergeFields = json_decode($mergeFields, true);
        }

        if (! is_array($mergeFields)) {
            return;
        }

        $criteria = array();
        foreach ($mergeFields as $field) {
            $value = $node->getProperty($field);
            if ($value instanceof NodeProperty) {
                $criteria[$field] = $value->getValue();

            } else {
                throw new \RuntimeException("This is not supported!");
            }
        }

        return $this->entityManager->getRepository($this->getClass($node))->findOneBy($criteria);
    }

    /**
     * Get property of reflection class. Include inherited properties.
     *
     * @param \ReflectionClass $reflClass
     * @param string $name
     * @return \ReflectionProperty
     */
    protected function getProperty(\ReflectionClass $reflClass, $name)
    {
        if ($reflClass->hasProperty($name)) {
            return $reflClass->getProperty($name);

        } else {
            $reflParent = $reflClass->getParentClass();
            if ($reflParent) {
                return $this->getProperty($reflParent, $name);
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
}