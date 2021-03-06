<?php

namespace Sysgear\Merger;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

/**
 * Merge object with doctrine.
 *
 * @author (c) Martijn Evers <martijn4evers@gmail.com>
 */
class DoctrineMerger implements MergerInterface
{
    /**
     * Fetch missing relations by fetching a
     * entity by identity (a.k.a. primary key). Matching the identity field(s)
     * with the field(s) of the same name in the node. Using the identity
     * value(s) of that node to fetch the entity.
     *
     * @var integer
     */
    const FETCH_BY_IDENTITY = 0;

    /**
     * Search a single entity, by matching all node
     * attributes, to complete the node.
     *
     * @var integer
     */
    const FETCH_BY_ATTRIBUTES = 1;

    /**
     * @var EntityManager
     */
    public $entityManager;

    /**
     * Create a doctrine merger.
     *
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    public function merge($object)
    {
        // This one is very important, remove it and expect hours of debug fun.
        // Search Doctrine docs for the "merge" operation.
        $this->entityManager->clear();

        try {
            return $this->entityManager->merge($object);

        } catch (\InvalidArgumentException $e) {

            $msg = $e->getMessage();
            if ("The given entity has no identity." === $msg) {
                return null;
            } else {
                throw new MergerException($msg, $e->getCode(), $e);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function find($object)
    {
        $class = get_class($object);

        $this->entityManager->clear();
        $id = $this->entityManager->getClassMetadata($class)->getIdentifierValues($object);

        return (0 === count($id)) ? null : $this->entityManager->find($class, $id);
    }

    /**
     * {@inheritdoc}
     */
    public function getObjectId($object)
    {
        $class = get_class($object);
        return $this->entityManager->getClassMetadata($class)->getIdentifierValues($object);
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        $this->entityManager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getMandatoryProperties($object)
    {
        $props = array();
        $metadata = $this->entityManager->getClassMetadata(get_class($object));

        foreach ($metadata->getColumnNames() as $columnName) {
            $fieldName = $metadata->getFieldName($columnName);
            if (! $metadata->isNullable($fieldName)) {
                $props[] = $fieldName;
            }
        }

        foreach ($metadata->getAssociationMappings() as $map) {
            if ($this->isAssociationMandatory($map)) {
                $props[] = $map['fieldName'];
            }
        }

        return $props;
    }

    /**
     * Return if an association is mandatory. Or null if not decided.
     *
     * TODO: Handle case in which there are more "joinColumns" (or is this not relevant?)
     *
     * @param array $map
     * @return boolean | null
     */
    protected function isAssociationMandatory($map)
    {
        $joinColumn = array();

        if (array_key_exists("joinTable", $map) && array_key_exists("joinColumns", $map["joinTable"])) {
            $joinColumn = $map["joinTable"]["joinColumns"][0];
        }

        if (array_key_exists("joinColumns", $map)) {
            $joinColumn = $map["joinColumns"][0];
        }

        if (array_key_exists("nullable", $joinColumn)) {
            return ! $joinColumn["nullable"];
        }

        return null;
    }
}