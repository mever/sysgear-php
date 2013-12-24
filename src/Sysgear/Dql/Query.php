<?php

/*
 * This file is part of the Sysgear package.
*
* (c) Martijn Evers <mevers47@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Sysgear\Dql;

use Doctrine\ORM\EntityManager,
    Doctrine\ORM\QueryBuilder;

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
    protected $selects;

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
     * @var array
     */
    protected $joins = array();

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

        $this->selects = $select;
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

        // first build where clause, as it can determine
        // the select and joins involved.
        if (null !== $this->filters && ! $this->filters->isEmpty()) {
            $queryBuilder->where($this->toWhereClause($this->filters));
        }

        // if we have joins make sure we can iterate, thus distinct
        $queryBuilder->select((0 === count($this->joins) ? '' : 'DISTINCT ') . '_');

        foreach ($this->joins as $spec) {
            $queryBuilder->innerJoin($spec['join'], $spec['alias']);
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
        $selectAliases = array();
        $joins =& $this->joins;
        foreach ($this->selects as $field) {
            list($alias) = explode('.', $field, 2);
            $selectAliases[] = $alias;
        }

        $compiler = function($type, $filter) use ($that, $connection, $selectAliases, &$joins) {
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

                // get left operand
                $left = $that->normalizeField($filter->getField());

                // add join if a field in the filter traverses a relation
                $pos = strpos($left, '.');
                if (false !== $pos && ! in_array(substr($left, 0, $pos), $selectAliases, true)) {
                    $field = substr($left, 0, $pos);
                    $joins[] = array('join' => '_.' . $field, 'alias' => $field);
                }

                // build complete comparison expresssion
                return "{$left} {$sqlOperator} {$right}";
            }
        };

        return $filters->compileString($compiler);
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
            $field = $this->normalizeField($field);
        }

        return $fields;
    }

    /**
     * Normalize field.
     *
     * @param string $field
     * @return string
     */
    public function normalizeField($field) {
        if (preg_match('/^([a-z][a-z0-9_]*\\.)?[a-z][a-z_0-9]*$/i', $field, $matches)) {
            if (1 === count($matches)) {
                return "_.{$field}";
            }
        } else {
            self::assertField($field);
        }

        return $field;
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