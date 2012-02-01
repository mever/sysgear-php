<?php

namespace Sysgear\StructuredData\Restorer;

use Sysgear\StructuredData\NodeCollection;
use Sysgear\StructuredData\NodeProperty;
use Sysgear\StructuredData\Node;
use Sysgear\StructuredData\NodePath;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Developers notes:
 * - this class is designed from the domain perspective, we use associations and properties.
 * - current only the insert merge mode is supported.
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

//         $this->entityManager->getConnection()->beginTransaction();
        $this->restoreNode($node);
//         $this->entityManager->getConnection()->rollback();

        die();
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

        // gather column data
        $record = array();
        $relations = array();
        $metadata = $this->getMetadata($node);
        foreach ($node->getProperties() as $fieldName => $property) {

            // attribute data
            if ($property instanceof NodeProperty) {
                $record[$metadata->getColumnName($fieldName)] = $property->getValue();
                continue;
            }

            // owning associations
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
                        $foreignRecord = $this->createRecord($property);
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
                    $relations[$fieldName] = array('node' => $property);
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
            switch ($rel['type']) {
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
                            $this->createRecord($relatedNode);
                        }
                    } else {
                        $this->createRecord($node);
                    }
            }
        }
    }

    /**
     * Process final record.
     *
     * @param array $record
     * @param string $joinTableName
     * @throws \RuntimeException
     */
    protected function processRecord(array &$record, ClassMetadata $metadata = null, $joinTableName = null)
    {
        // get table name
        if (null === $metadata) {
            $tableName = $joinTableName;
            $isIdGeneratorIdentity = false;
        } else {
            $tableName = $metadata->getTableName();
            $isIdGeneratorIdentity = $metadata->isIdGeneratorIdentity();
        }

        // clear identity fields
        if ($isIdGeneratorIdentity) {
            foreach ($metadata->getIdentifier() as $field) {
                $record[$field] = null;
            }
        }

        // insert record
        // TODO: support more merge modes
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

        echo $tableName . " - "; var_dump($record);

        return $record;
    }

    /**
     * Return FQ-class name of entity to restore.
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