<?php

namespace Sysgear\StructuredData\Restorer;

use Sysgear\StructuredData\NodeInterface;
use Sysgear\StructuredData\NodeCollection;
use Sysgear\StructuredData\NodeProperty;
use Sysgear\StructuredData\Node;
use Sysgear\StructuredData\NodePath;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\DBAL\Types\Type;

/**
 * Developers notes:
 * - this class is designed from the domain perspective, we follow associations and properties.
 * - current only the insert merge mode is supported and the default.
 */
class DoctrineRestorer extends AbstractRestorer
{
    /**
     * Merge modes.
     *
     * @var integer
     */
    const MM_PRIMARY = 1;  // update entities that match by identity (PK) and insert otherwise
    const MM_MATCH = 2;    // update entities that match the match table and insert otherwise
    const MM_INSERT = 3;   // insert all entities

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $entityManager;

    /**
     * Determines how to merge the diffrent entities into Doctrine.
     *
     * @var integer
     */
    protected $mergeMode;

    /**
     * Array of node paths to update.
     *
     * @var \Sysgear\StructuredData\NodePath[]
     */
    protected $matchList;

    /**
     * List of inserted records.
     *
     * @var array
     */
    protected $recordList = array();

    /**
     * Set option.
     *
     * @param string $key
     * @param mixed $value
     */
    protected function _setOption($key, $value)
    {
        switch ($key) {
            case 'mergeMode':
                $this->mergeMode = (is_integer($value)) ? $value : self::MM_INSERT;
                break;

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
        $this->node = $node;

        // check merge mode
        if (null === $this->mergeMode) {
            $this->mergeMode = self::MM_INSERT;
        }

        // (re)place entity manager
        if ($entityManager instanceof EntityManager) {
            $this->entityManager = $entityManager;
        }

        // check entity manager
        if (null === $this->entityManager) {
            throw new \InvalidArgumentException("No entity manager given.");
        }

        $this->restoreNode($node);
    }

    /**
     * Restores a node.
     *
     * @param Node $node
     * @throws \RuntimeException
     */
    protected function restoreNode(Node $node)
    {
        switch ($this->mergeMode) {
            case self::MM_INSERT:
                $this->recordList = array();
                $this->createRecord($node);
                break;

            default:
                throw new \LogicException("Other merge modes are not supported yet");
        }
    }

    /**
     * Find a record for $nodeToMerge.
     *
     * @return array Record
     */
    protected function findRecord(Node $nodeToMerge)
    {
        // prevent recursion
        $oid = '#' . spl_object_hash($nodeToMerge);
        if (array_key_exists($oid, $this->recordList)) {
            return $this->recordList[$oid];
        }

        $metadata = $this->getMetadata($nodeToMerge);
        $conn = $this->entityManager->getConnection();

        $whereClause = array();
        foreach ($nodeToMerge->getProperties() as $name => $prop) {

            $mergeFields = $nodeToMerge->getMeta('merge-fields');
            if (null === $mergeFields || in_array($name, json_decode($mergeFields))) {
                if ($prop instanceof Node) {

                    $record = $this->findRecord($prop);
                    $mapping = $metadata->getAssociationMapping($name);
                    foreach ($mapping['joinColumns'] as $joinColumn) {
                        $whereClause[] = $conn->quoteIdentifier($joinColumn['name']) . ' = ' .
                            $conn->quote(@$record[$joinColumn['referencedColumnName']]);
                    }

                } elseif ($prop instanceof NodeProperty) {
                    $whereClause[] = $conn->quoteIdentifier($name) . ' = ' . $conn->quote($prop->getValue());
                }
            }
        }

        if (0 === count($whereClause)) {
            throw new \RuntimeException("No merge criteria is given");
        }

        // build SQL statement to fetch a record to merge with
        $tableName = $conn->quoteIdentifier($metadata->getTableName());
        $sql = "SELECT * FROM {$tableName} WHERE " . join('AND ', $whereClause);

        $records = $conn->fetchAll($sql);
        if (1 === count($records)) {
            $this->recordList[$oid] = $records[0];
            return $records[0];
        } elseif (count($records) > 1) {
            throw new \RuntimeException("Mutiple records to merge found with: '{$sql}'");
        }
    }

    /**
     * Create a value from any type of node
     * based on Doctrine's field mapping.
     *
     * @param array $fieldMapping
     * @param NodeInterface $node
     * @return string
     */
    protected function createCompositeValue(array $fieldMapping, NodeInterface $node)
    {
        $value = null;
        $objRestorer = new ObjectRestorer();
        if ($node instanceof Node) {
            $value = $objRestorer->restore($node);

        } elseif ($node instanceof NodeCollection) {
            $value = array();
            foreach ($node as $elem) {
                $objRestorer = new ObjectRestorer();
                $key = $elem->getMeta('key');
                if (null === $key) {
                    $value[] = $objRestorer->restore($elem);
                } else {
                    $isEnum = ('i' === $key[0]);
                    $key = substr($key, 2);
                    $val = ($isEnum) ? (integer) $key : (string) $key;
                    $value[$val] = $objRestorer->restore($elem);
                }
            }
        }

        $platform = $this->entityManager->getConnection()->getDatabasePlatform();
        return Type::getType($fieldMapping['type'])->convertToDatabaseValue($value, $platform);
    }

    /**
     * Recursively create records, start with $node.
     *
     * @param Node $node
     * @return array record
     */
    protected function createRecord(Node $node)
    {
        // prevent recursion
        $oid = '#' . spl_object_hash($node);
        if (array_key_exists($oid, $this->recordList)) {
            return $this->recordList[$oid];
        }

        // get merge list
        $merge = json_decode($node->getMeta('merge', '[]'));

        // gather column data
        $record = array();
        $relations = array();
        $metadata = $this->getMetadata($node);
        foreach ($node->getProperties() as $fieldName => $property) {

            // attribute data
            if ($metadata->hasField($fieldName)) {
                if ($property instanceof NodeProperty) {
                    $record[$metadata->getColumnName($fieldName)] = $property->getValue();

                } else {
                    $fieldMapping = $metadata->getFieldMapping($fieldName);
                    $record[$fieldMapping['columnName']] = $this->createCompositeValue($fieldMapping, $property);
                }
                continue;
            }

            // owning relations
            $mapping = $metadata->getAssociationMapping($fieldName);
            if ($mapping['isOwningSide']) {

                // associations using a join table
                if (array_key_exists('joinTable', $mapping)) {
                    $relations[$fieldName] = array(
                        'type' => 'joinTable', 'node' => $property, 'joinTable' => $mapping['joinTable']);
                }

                // foreign relations
                elseif (1 === count($mapping['joinColumns'])) {
                    $foreignColumn = $mapping['joinColumns'][0];
                    if ($property instanceof Node) {

                        $foreignRecord = (in_array($fieldName, $merge, true)) ? $this->findRecord($property) : null;
                        $foreignRecord = (null === $foreignRecord) ? $this->createRecord($property) : $foreignRecord;
                        $record[$foreignColumn['name']] = @$foreignRecord[$foreignColumn['referencedColumnName']];

                    } else {
                        throw new \RuntimeException("Trying to find a foreign key for '{$property->getType()}' failed");
                    }
                }

                else {
                    throw new \RuntimeException("Composite foreign keys are not implemented yet");
                }
            }

            // NOT owning associations
            else {

                if (array_key_exists('joinTable', $mapping)) {
                    $relations[$fieldName] = array(
                        'type' => 'inverseJoinTable', 'node' => $property, 'mappedBy' => $mapping['mappedBy']);

                } else {
                    $relations[$fieldName] = array(
                        'node' => $property, 'mappedBy' => $mapping['mappedBy'], 'from' => $node);
                }
            }
        }

        // processes a record
        $this->processRecord($record, $metadata);
        $this->recordList[$oid] = $record;
        $this->processDependentRelations($record, $relations);
        return $record;
    }

    /**
     * Resolve relations dependant on $record.
     *
     * @param array $record
     * @param array $relations
     */
    protected function processDependentRelations($record, array $relations)
    {
        foreach ($relations as $rel) {
            $node = $rel['node'];
            switch (@$rel['type']) {
                case 'joinTable':
                    foreach ($node as $n) {
                        $foreignRecord = $this->createRecord($n);
                        $jc = $rel['joinTable']['joinColumns'][0];
                        $ijc = $rel['joinTable']['inverseJoinColumns'][0];

                        $joinRecord = array();
                        $joinRecord[$jc['name']] = @$record[@$jc['referencedColumnName']];
                        $joinRecord[$ijc['name']] = @$foreignRecord[@$ijc['referencedColumnName']];

                        $sha1 = '@' . sha1(serialize($joinRecord));
                        if (! array_key_exists($sha1, $this->recordList)) {
                            $this->recordList[$sha1] = true;
                            $this->processRecord($joinRecord, null, $rel['joinTable']['name']);
                        }
                    }
                    break;

                case 'inverseJoinTable':
                    // get join table mapping data from a foreign node across the join table
                    $mapping = $this->getMetadata($node->first())->getAssociationMapping($rel['mappedBy']);
                    foreach ($node as $n) {
                        $foreignRecord = $this->createRecord($n);
                        $jc = $mapping['joinTable']['joinColumns'][0];
                        $ijc = $mapping['joinTable']['inverseJoinColumns'][0];

                        $joinRecord = array();
                        $joinRecord[$jc['name']] = @$foreignRecord[@$ijc['referencedColumnName']];
                        $joinRecord[$ijc['name']] = @$record[@$jc['referencedColumnName']];

                        $sha1 = '@' . sha1(serialize($joinRecord));
                        if (! array_key_exists($sha1, $this->recordList)) {
                            $this->recordList[$sha1] = true;
                            $this->processRecord($joinRecord, null, $mapping['joinTable']['name']);
                        }
                    }
                    break;

                default:
                    if ($node instanceof NodeCollection) {
                        foreach ($node as $relatedNode) {
                            $relatedNode->setProperty($rel['mappedBy'], $rel['from']);
                            $this->createRecord($relatedNode);
                        }
                    } else {
                        $relatedNode->setProperty($rel['mappedBy'], $rel['from']);
                        $this->createRecord($node);
                    }
            }
        }
    }

    /**
     * Process final record.
     *
     * @param array $record
     * @param ClassMetadata $metadata
     * @param string $joinTableName
     * @throws \RuntimeException
     */
    protected function processRecord(array &$record, ClassMetadata $metadata = null, $joinTableName = null)
    {
        // get table name
        if (null === $metadata) {
            $tableName = $joinTableName;
        } else {
            $tableName = $metadata->getTableName();
        }

        // perform data merge
        switch ($this->mergeMode) {
            case self::MM_INSERT:
                $this->modeInsert($tableName, $record, $metadata);
                break;
        }
    }

    /**
     * Insert mode. Given $record is inserted.
     *
     * @param string $tableName
     * @param array $record
     * @param ClassMetadata $metadata
     * @throws \RuntimeException
     */
    protected function modeInsert($tableName, &$record, ClassMetadata $metadata = null)
    {
        if (null === $metadata) {
            $isIdGeneratorIdentity = false;
        } else {
            $isIdGeneratorIdentity = $metadata->isIdGeneratorIdentity();
        }

        // clear identity fields
        if ($isIdGeneratorIdentity) {
            foreach ($metadata->getIdentifier() as $field) {
                $record[$field] = null;
            }
        }

        // insert record
        $conn = $this->entityManager->getConnection();
        try {
            $conn->insert($tableName, $record);
        } catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage() . " for table '{$tableName}'",  null, $e);
        }

        // set generator identity
        if ($isIdGeneratorIdentity) {
            $id = $metadata->getIdentifier();
            if (1 === count($id)) {
                $record[$id[0]] = $conn->lastInsertId();
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

    /**
     * Return Doctrine metadata.
     *
     * @param Node $node
     * @return \Doctrine\ORM\Mapping\ClassMetadata
     */
    protected function getMetadata(Node $node)
    {
        return $this->entityManager->getClassMetadata($this->getClass($node));
    }
}