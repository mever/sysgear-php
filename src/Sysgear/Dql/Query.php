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
    Doctrine\ORM\Query\Expr\OrderBy;

use Sysgear\Filter\Collection,
    Sysgear\Filter\Expression,
    Sysgear\Operator;

class Query
{
    /**
     * @var string
     */
    public $entityClass;

    /**
     * @var \Sysgear\Filter\Collection
     */
    public $filters;

    /**
     * @var array
     */
    protected $selects = array();

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    public $entityManager;

    /**
     * @var array
     */
    public $orderBy;

    /**
     * @var array
     */
    public $limit;

    /**
     * Create rest query tool.
     *
     * @param EntityManager $entityManager
     * @param string $entityClass
     */
    public function __construct(EntityManager $entityManager, $entityClass = null) {
        $this->entityManager = $entityManager;
        $this->entityClass = $entityClass;
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
     * Build query.
     *
     * @param boolean $countQuery
     * @return \Doctrine\ORM\Query
     */
    public function build($countQuery = false)
    {
        if (null === $this->entityClass) {
            throw new \RuntimeException("No entity class given.");
        }

        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder->from($this->entityClass, '_');

        // first build where clause, as it can determine
        // the select and joins involved.
        $joins = array();
        if (null !== $this->filters && ! $this->filters->isEmpty()) {
            $queryBuilder->where($this->toWhereClause($this->filters, $joins));
        }

        foreach ($joins as $spec) {
            $queryBuilder->innerJoin($spec['join'], $spec['alias']);
        }

        if ($countQuery) {
            $queryBuilder->select('COUNT(_.id)');

        } else {
            // if we have joins make sure we can iterate, thus distinct
            $queryBuilder->select((0 === count($joins) ? '' : 'DISTINCT ') . '_');

            if ($this->orderBy) {
                $queryBuilder->addOrderBy($this->buildOrderByClause($this->orderBy));
            }
        }

        if (null !== $this->limit) {
            $queryBuilder->setFirstResult($this->limit[0]);
            $queryBuilder->setMaxResults($this->limit[1]);
        }

        return $queryBuilder->getQuery();
    }

    /**
     * Build SQL ORDER BY clause to sort the data set.
     *
     * @param array[] $sorting Array of sort array's. Each sort is an array where
     *                         the first element should be the field name as string and the second element
     *                         the sort direction as boolean. Use true for ascending or false for descending.
     *
     * @return \Doctrine\ORM\Query\Expr\OrderBy
     */
    protected function buildOrderByClause(array $sorting = array())
    {
        $orderBy = new OrderBy();
        foreach ($sorting as $sort) {
            $field = $this->normalizeField($sort[0]);
            $orderBy->add($field, ($sort[1]) ? 'ASC' : 'DESC');
        }

        return $orderBy;
    }

    /**
     * Build SQL WHERE clause to filter data set.
     *
     * @param Collection $filters
     * @param array $joins
     * @return string
     */
    private function toWhereClause(Collection $filters, &$joins)
    {
        $that = $this;
        $connection = $this->entityManager->getConnection();
        $selectAliases = array();
        foreach ($this->selects as $field) {
            list($alias) = explode('.', $field, 2);
            $selectAliases[] = $alias;
        }

        /**
         * @param string $type
         * @param Collection|Expression $filter
         * @return array|string
         */
        $compiler = function($type, $filter) use ($that, $connection, $selectAliases, &$joins) {
            if ($type === Collection::COMPILE_COL) {

                // return left, operator and right parts
                $type = strtoupper($filter->getType());
                return array('(', " {$type} ", ')');
            } else {

                $value = $filter->getValue();
                if  (null === $value) {
                    $sqlOperator = 'IS';
                    $right = 'NULL';
                }

                elseif (is_array($value)) {
                    $inClause = array();
                    foreach ($value as $val) {
                        $inClause[] = $connection->quote($val);
                    }
                    $sqlOperator = 'IN';
                    $right = '(' . join(',', $inClause) . ')';

                }

                else {

                    $operator = $filter->getOperator();
                    $sqlOperator = Operator::toSqlComparison($operator);

                    // build left comparison expression
                    switch ($operator) {
                        case Operator::STR_START_WITH: $right = $connection->quote($value . '%'); break;
                        case Operator::STR_END_WITH: $right = $connection->quote('%' . $value); break;
                        case Operator::LIKE: $right = $connection->quote('%' . $value . '%'); break;
                        default: $right = $connection->quote($value);
                    }
                }

                // get left operand
                $left = $that->normalizeField($filter->getField());

                // add join if a field in the filter traverses a relation
                if ('_.' !== substr($left, 0, 2)) {
                    $pos = strpos($left, '.');
                    if (false !== $pos && !in_array(substr($left, 0, $pos), $selectAliases, true)) {
                        $field = substr($left, 0, $pos);
                        $joins[] = array('join' => '_.' . $field, 'alias' => $field);
                    }
                }

                // build complete comparison expression
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