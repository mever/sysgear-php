<?php

namespace Sysgear\Filter;

use Closure, Countable, IteratorAggregate, ArrayAccess;
use Doctrine\DBAL\Connection;

class Collection extends Filter implements Countable, IteratorAggregate, ArrayAccess
{
    const COMPILE_COL = 'col';
    const COMPILE_EXP = 'exp';

    protected $collection = array();
    protected $type;

    public function __construct(array $collection = array(), $type = 'and')
    {
        $this->collection = $collection;
        $this->setType($type);
    }

    /**
     * Build SQL WHERE clause to filter dataset.
     *
     * @param Connection $connection
     * @return string
     */
    public function toWhereClause(Connection $connection)
    {
        if ($this->isEmpty()) {
            return '';
        }

        $filter = $this;
        $compiler = function($type, $filter) use ($connection) {

            if ($type === self::COMPILE_COL) {

                // return left, operator and right parts
                $type = strtoupper($filter->getType());
                return array('(', " {$type} ", ')');
            } else {

                $operator = $filter->getOperator();
                $value = $filter->getValue();

                // build left comparison expression
                switch ($operator) {
                case \Sysgear\Operator::STR_START_WITH: $right = $connection->quote($value . '%'); break;
                case \Sysgear\Operator::STR_END_WITH: $right = $connection->quote('%' . $value); break;
                case \Sysgear\Operator::LIKE: $right = $connection->quote('%' . $value . '%'); break;
                default: $right = $connection->quote($value);
                }

                // build complete comparison expresssion
                $left = $connection->quoteIdentifier($filter->getField()) . ' ';
                return $left . \Sysgear\Operator::toSqlComparison($operator) . ' ' . $right;
            }
        };

        return 'WHERE ' . $this->compileString($compiler);
    }

    /**
     * Gets the PHP array representation of this filter collection.
     *
     * @return array The PHP array representation of this filter collection.
     */
    public function toArray()
    {
        $collection = array();
        foreach ($this->collection as $filter) {
            $collection[] = $filter->toArray();
        }
        return array('C' => $collection, 'T' => $this->type);
    }

    /**
     * Return an instance of Filter\Collection with only the
     * the filter collections from this collection.
     *
     * @return \Sysgear\Filter\Collection
     */
    public function getCollections()
    {
        $predicate = function($filter) {
            return ($filter instanceof Collection);
        };
        return $this->filter($predicate);
    }

    /**
     * Return an instance of Filter\Collection with only the
     * the filter expressions from this collection.
     *
     * @return \Sysgear\Filter\Collection
     */
    public function getExpressions()
    {
        $predicate = function($filter) {
            return ($filter instanceof Expression);
        };
        return $this->filter($predicate);
    }

    /**
     * Sets filter collection type.
     *
     * @param string $type Filter collection type.
     */
    public function setType($type)
    {
        switch ($type) {
            case 'and': case 'or': break;
            default: throw new \InvalidArgumentException('First argument must be "and" or "or".');
        }

        $this->type = $type;
        return $this;
    }

    /**
     * Return filter collection type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Sets the internal iterator to the first filter in the collection and
     * returns this filter.
     *
     * @return \Sysgear\Filter\Filter
     */
    public function first()
    {
        return reset($this->collection);
    }

    /**
     * Sets the internal iterator to the last filter in the collection and
     * returns this filter.
     *
     * @return \Sysgear\Filter\Filter
     */
    public function last()
    {
        return end($this->collection);
    }

    /**
     * Gets the current key/index at the current internal iterator position.
     *
     * @return \Sysgear\Filter\Filter
     */
    public function key()
    {
        return key($this->collection);
    }

    /**
     * Moves the internal iterator position to the next filter.
     *
     * @return \Sysgear\Filter\Filter
     */
    public function next()
    {
        return next($this->collection);
    }

    /**
     * Gets the filter of the collection at the current internal iterator position.
     *
     * @return \Sysgear\Filter\Filter
     */
    public function current()
    {
        return current($this->collection);
    }

    /**
     * Removes an filter with a specific key/index from the collection.
     *
     * @param mixed $key
     * @return \Sysgear\Filter\Filter The removed filter or NULL, if no filter exists for the given key.
     */
    public function remove($key)
    {
        if (isset($this->collection[$key])) {
            $removed = $this->collection[$key];
            unset($this->collection[$key]);

            return $removed;
        }

        return null;
    }

    /**
     * Removes the specified filter from the collection, if it is found.
     *
     * @param Filter $filter The filter to remove.
     * @return boolean TRUE if this collection contained the specified filter, FALSE otherwise.
     */
    public function removeFilter(Filter $filter)
    {
        $key = array_search($filter, $this->collection, true);

        if ($key !== false) {
            unset($this->collection[$key]);

            return true;
        }

        return false;
    }

    /**
     * ArrayAccess implementation of offsetExists()
     *
     * @see containsKey()
     */
    public function offsetExists($offset)
    {
        return $this->containsKey($offset);
    }

    /**
     * ArrayAccess implementation of offsetGet()
     *
     * @see get()
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * ArrayAccess implementation of offsetGet()
     *
     * @see add()
     * @see set()
     */
    public function offsetSet($offset, $value)
    {
        if ( ! isset($offset)) {
            return $this->add($value);
        }
        return $this->set($offset, $value);
    }

    /**
     * ArrayAccess implementation of offsetUnset()
     *
     * @see remove()
     */
    public function offsetUnset($offset)
    {
        return $this->remove($offset);
    }

    /**
     * Checks whether the collection contains a specific key/index.
     *
     * @param mixed $key The key to check for.
     * @return boolean TRUE if the given key/index exists, FALSE otherwise.
     */
    public function containsKey($key)
    {
        return isset($this->collection[$key]);
    }

    /**
     * Checks whether the given filter is contained in the collection. This means reference equality.
     *
     * @param Filter $filter
     * @return boolean TRUE if the given filter is contained in the collection,
     *          FALSE otherwise.
     */
    public function contains(Filter $filter)
    {
        return in_array($filter, $this->collection, true);
    }

    /**
     * Tests for the existance of an filter that satisfies the given predicate.
     *
     * @param Closure $p The predicate.
     * @return boolean TRUE if the predicate is TRUE for at least one filter, FALSE otherwise.
     */
    public function exists(Closure $p)
    {
        foreach ($this->collection as $key => $filter)
            if ($p($key, $filter)) return true;
        return false;
    }

    /**
     * Searches for a given filter and, if found, returns the corresponding key/index
     * of that filter.
     *
     * @param Filter $filter The filter to search for.
     * @return mixed The key/index of the filter or FALSE if the filter was not found.
     */
    public function indexOf(Filter $filter)
    {
        return array_search($filter, $this->collection, true);
    }

    /**
     * Gets the filter with the given key/index.
     *
     * @param mixed $key The key.
     * @return \Sysgear\Filter\Filter The filter or NULL, if no filter exists for the given key.
     */
    public function get($key)
    {
        if (isset($this->collection[$key])) {
            return $this->collection[$key];
        }
        return null;
    }

    /**
     * Gets all keys/indexes of the collection filters.
     *
     * @return array
     */
    public function getKeys()
    {
        return array_keys($this->collection);
    }

    /**
     * Gets all filters.
     *
     * @return array
     */
    public function getValues()
    {
        return array_values($this->collection);
    }

    /**
     * Returns the number of filters in the collection.
     *
     * Implementation of the Countable interface.
     *
     * @return integer The number of filters in the collection.
     */
    public function count()
    {
        return count($this->collection);
    }

    /**
     * Adds/sets an filter in the collection at the index / with the specified key.
     *
     * When the collection is a Map this is like put(key,value)/add(key,value).
     * When the collection is a List this is like add(position,value).
     *
     * @param mixed $key
     * @param mixed $value
     */
    public function set($key, $value)
    {
        $this->collection[$key] = $value;
    }

    /**
     * Adds an filter to the collection.
     *
     * @param mixed $value
     * @return boolean Always TRUE.
     */
    public function add($value)
    {
        $this->collection[] = $value;
        return true;
    }

    /**
     * Checks whether the collection is empty.
     *
     * Note: This is preferrable over count() == 0.
     *
     * @return boolean TRUE if the collection is empty, FALSE otherwise.
     */
    public function isEmpty()
    {
        return ! $this->collection;
    }

    /**
     * Gets an iterator for iterating over the filters in the collection.
     *
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->collection);
    }

    /**
     * Applies the given function to each filter in the collection and returns
     * a new collection with the filter returned by the function.
     *
     * @param Closure $func
     * @return Collection
     */
    public function map(Closure $func)
    {
        return new self(array_map($func, $this->collection), $this->type);
    }

    /**
     * Returns all the filters of this collection that satisfy the predicate p.
     * The order of the filters is preserved.
     *
     * @param Closure $p The predicate used for filtering.
     * @return Collection A collection with the results of the filter operation.
     */
    public function filter(Closure $p)
    {
        return new self(array_filter($this->collection, $p), $this->type);
    }

    /**
     * Applies the given predicate p to all filters of this collection,
     * returning true, if the predicate yields true for all filters.
     *
     * @param Closure $p The predicate.
     * @return boolean TRUE, if the predicate yields TRUE for all filters, FALSE otherwise.
     */
    public function forAll(Closure $p)
    {
        foreach ($this->collection as $key => $filter) {
            if ( ! $p($key, $filter)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns a string representation of this object.
     *
     * @return string
     */
    public function __toString()
    {
        return __CLASS__ . '@' . spl_object_hash($this);
    }

    /**
     * Clears the collection.
     */
    public function clear()
    {
        $this->collection = array();
    }

    /**
     * Extract a slice of $length filters starting at position $offset from the Collection.
     *
     * If $length is null it returns all filters from $offset to the end of the Collection.
     * Keys have to be preserved by this method. Calling this method will only return the
     * selected slice and NOT change the filters contained in the collection slice is called on.
     *
     * @param int $offset
     * @param int $length
     * @return array
     */
    public function slice($offset, $length = null)
    {
        return array_slice($this->collection, $offset, $length, true);
    }

    public function serialize()
    {
        return serialize(array('C' => $this->collection, 'T' => $this->type));
    }

    public function unserialize($data)
    {
        $data = unserialize($data);
        $this->collection = $data['C'];
        $this->type = $data['T'];
    }
}