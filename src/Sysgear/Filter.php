<?php

namespace Sysgear;

class Filter implements \Serializable
{
    const COLLECTION = 'col';
    const EXPRESSION = 'exp';

    /**
     * @var array
     */
    protected $filter;

    /**
     * Build filter from string.
     *
     * @param unknown_type $filter
     * @throws \Exception
     */
    public static function fromString($filter)
    {
        if (! is_string($filter)) {
            throw new \Exception("Given parameter is not a string type.");
        }

        // TODO: Convert.
    }

    /**
     * Create new filter.
     *
     * @param array $filter
     */
    public function __construct(array $filter = array())
    {
        $this->filter = $filter;
    }

    /**
     * Compile string.
     *
     * Accepts function to compile string from filter. First argument is
     * ether COLLECTION or EXPRESSION. The second is the filter currently
     * evaluated.
     *
     * @param function $compiler
     * @return string
     */
    public function compileString($compiler)
    {
        return $this->stringBuilder($this->filter, $compiler);
    }

    protected function stringBuilder(array $filter, $compiler)
    {
        if ($this->isCol($filter)) {
            $parts = array();
            foreach ($filter['C'] as $f) {
                $parts[] = $this->stringBuilder($f, $compiler);
            }
            list($left, $oper, $right) = $compiler(self::COLLECTION, $filter);
            return $left . implode($oper, $parts) . $right;
        } else {
            return $compiler(self::EXPRESSION, $filter);
        }
    }

    /**
     * Return all filters as a key-value array (a.k.a. map).
     *
     * Each key represents the filter field and each associated value
     * as field value. The assumed operator for this array element is \Sysgear\Operator::EQUAL
     *
     * @return array
     */
    public function getFiltersMap()
    {
        $filters = array();
        $col = $this->getCollection();
        foreach ($col['C'] as $f) {
            if ($this->isExp($f)) {
                $filters[$f['F']] = $f['V'];
            }
        }
        return $filters;
    }

    /**
     * Return filter as JSON.
     *
     * @return string
     */
    public function __toString()
    {
        return json_encode($this->filter);
    }

    /**
     * Return filter as array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->filter;
    }

    /**
     * Return true if the filter is empty. That is no filter expressions.
     *
     * @return boolean
     */
    public function isEmpty()
    {
        if (0 === count($this->filter)
        || ($this->isCol($this->filter) && 0 === count($this->filter['C']))) {
            return true;
        } else {
            return false;
        }
    }

    public function serialize()
    {
        return serialize($this->filter);
    }

    public function unserialize($data)
    {
        $this->filter = unserialize($data);
    }

    /**
     * Return a collection of filter expressions.
     *
     * TODO: Make it recursive.
     * TODO: Use 'path' to select from somewhere in the tree.
     *
     * @param string $path Path in the filter tree to find a collection
     * @return array Filter collection outputted as $mode
     */
    protected function getCollection($path = null)
    {
        $f = $this->filter;
        if (! $this->isCol($f)) {

            $col = array('C' => array());
            if (array_key_exists('F', $f)) {
                $col['C'] = $f;
            }
        } else {
            $col = $f;
        }

        return $col;
    }

    protected function isExp(array $filter)
    {
        return array_key_exists('F', $filter);
    }

    protected function isCol(array $filter)
    {
        return array_key_exists('C', $filter);
    }
}