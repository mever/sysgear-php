<?php

/*
 * This file is part of the Sysgear package.
*
* (c) Martijn Evers <mevers47@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Sysgear\Rest;

use Doctrine\ORM\EntityManager;
use Sysgear\Filter\Collection;

class Query
{
    /**
     * @var string
     */
    protected $entityClass;

    /**
     * @var \Sysgear\Filter\Collection
     */
    protected $filters;

    /**
     * @var array
     */
    protected $select;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $entityManager;

    /**
     * @var array
     */
    protected $orderBy;

    /**
     * @var array
     */
    protected $limit;

    /**
     * Create rest query tool.
     *
     * @param EntityManager $entityManager
     * @param string $entityClass
     */
    public function __construct(EntityManager $entityManager, $entityClass) {
        $this->entityManager = $entityManager;
        $this->entityClass = $entityClass;
    }

    /**
     * Set filters as where clause.
     *
     * @param Collection $filters
     */
    public function where(Collection $filters) {
        $this->filters = $filters;
    }

    /**
     * Set select clause.
     *
     * @param array $select
     */
    public function select(array $select = null) {
        if (null !== $select) {
            $select = $this->normalizeFields($select);
        }

        $this->select = $select;
    }

    /**
     * Set order by clause.
     *
     * @param array $orderBy
     */
    public function orderBy(array $orderBy = null) {
        $this->orderBy = $orderBy;
    }

    /**
     * Set limit clause.
     *
     * @param array $limit
     */
    public function limit(array $limit = null) {
        $this->limit = $limit;
    }

    /**
     * Executes the query and returns an IterableResult that can be used to incrementally
     * iterate over the result.
     */
    public function iterate()
    {
        return $this->build()->iterate();
    }

    /**
     * Build query.
     *
     * @return \Doctrine\ORM\Query
     */
    protected function build()
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder->from($this->entityClass, '_');
        if (null === $this->select) {
            $queryBuilder->select('_');

        } else {
            $aliases = array();
            foreach ($this->select as $field) {
                list($alias) = explode('.', $field, 2);
                $aliases[$alias] = $alias;
            }

            $queryBuilder->select(join(',', $aliases));
        }

        if (null !== $this->filters && ! $this->filters->isEmpty()) {
            $queryBuilder->where($this->toWhereClause($this->filters));
        }

        if ($this->orderBy) {
            $queryBuilder->add('orderBy', $this->buildOrderByClause($this->orderBy));
        }

        if (null !== $this->limit) {
            $queryBuilder->setFirstResult($this->limit[0]);
            $queryBuilder->setMaxResults($this->limit[1]);
        }

        return $queryBuilder->getQuery();
    }

    /**
     * Build SQL ORDER BY clause to sort the dataset.
     *
     * @param array[] $sorting Array of sort array's. Each sort is an array where
     *                         the first element should be the field name as string and the second element
     *                         the sort direction as boolean. Use true for ascending or false for descending.
     * @return string
     */
    protected function buildOrderByClause(array $sorting = array())
    {
        $ordering = array();
        foreach ($sorting as $sort) {
            $ordering[] = "_.{$sort[0]} " . (($sort[1]) ? 'ASC' : 'DESC');
        }

        return join(', ', $ordering);
    }

    /**
     * Build SQL WHERE clause to filter dataset.
     *
     * @return string
     */
    protected function toWhereClause(Collection $filters)
    {
        $that = $this;
        $connection = $this->entityManager->getConnection();
        $compiler = function($type, $filter) use ($that, $connection) {

            if ($type === Collection::COMPILE_COL) {

                // return left, operator and right parts
                $type = strtoupper($filter->getType());
                return array('(', " {$type} ", ')');
            } else {

                $operator = $filter->getOperator();
                $sqlOperator = \Sysgear\Operator::toSqlComparison($operator);
                $value = $filter->getValue();

                if (is_array($value)) {
                    $inClause = array();
                    foreach ($value as $val) {
                        $inClause[] = $connection->quote($val);
                    }
                    $sqlOperator = 'IN';
                    $right = '(' . join(',', $inClause) . ')';

                } else {

                    // build left comparison expression
                    switch ($operator) {
                        case \Sysgear\Operator::STR_START_WITH: $right = $connection->quote($value . '%'); break;
                        case \Sysgear\Operator::STR_END_WITH: $right = $connection->quote('%' . $value); break;
                        case \Sysgear\Operator::LIKE: $right = $connection->quote('%' . $value . '%'); break;
                        default: $right = $connection->quote($value);
                    }
                }

                // build complete comparison expresssion
                $left = $that->normalizeFields(array($filter->getField()));
                return reset($left) . $sqlOperator . ' ' . $right;
            }
        };

        return $filters->compileString($compiler);
    }

    /**
     * Build partial object expressions.
     *
     * @return string
     */
    protected function buildPartialSelect()
    {
        $partialFieldSets = array();
        foreach ($this->select as $field) {
            $parts = explode('.', $field, 2);
            if (isset($fields[$parts[0]])) {
                $partialFieldSets[$parts[0]] = array($parts[1]);

            } else {
                $partialFieldSets[$parts[0]][] = $parts[1];
            }
        }

        $partialObjectExpressions = array();
        foreach ($partialFieldSets as $alias => $fields) {
            $partialObjectExpressions[] = "partial {$alias}.{" . join(',', $fields) . '}';
        }

        return join(',', $partialObjectExpressions);
    }

    /**
     * Normalize fields.
     *
     * @param string[] $fields
     * @return string[]
     */
    public function normalizeFields($fields)
    {
        foreach ($fields as &$field) {
            if (preg_match('/^([a-z][a-z0-9_]*\\.)?[a-z][a-z_0-9]*$/i', $field, $matches)) {
                if (1 === count($matches)) {
                    $field = "_.{$field}";
                }
            } else {
                self::assertField($field);
            }
        }

        return $fields;
    }

    /**
     * Assert field.
     *
     * @param string $field
     * @throws \RuntimeException
     */
    public static function assertField($field) {
        if (! is_string($field) || 1 !== preg_match('/^([a-z][a-z_0-9]*\\.)?[a-z][a-z_0-9]*$/i', $field)) {
            throw new \RuntimeException("Supplied field is invalid!");
        }
    }
}