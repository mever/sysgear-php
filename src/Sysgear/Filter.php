<?php

namespace Sysgear;

class Filter
{
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
     * Return all filters as a key-value array (a.k.a. map).
     *
     * Each key represents the filter field and each associated value
     * as field value. The assumed operator for this array element is \Sysgear\Operator::EQUAL
     *
     * @return array
     */
    public function getFiltersArray()
    {
        $filters = array();
        foreach ($this->getCollection() as $f) {
            if ($this->isExp($f)) {
                $filters[$f['F']] = $f['V'];
            }
        }
        return $filters;
    }

    /**
     * Return a collection of filters.
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

            $col = array();
            if (array_key_exists('F', $f)) {
                $col[] = $f;
            }
        } else {
            $col = $f['C'];
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