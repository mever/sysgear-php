<?php

namespace Sysgear\Filter;

abstract class Filter implements \Serializable
{
    /**
     * Return filter given an array as input.
     *
     * @param array $filter
     * @return \Sysgear\Filter\Collection|\Sysgear\Filter\Expression
     */
    public static function fromArray(array $filter)
    {
        if (! $filter) {
            return new Collection();
        }

        if (array_key_exists('C', $filter)) {
            $collection = array();
            foreach ($filter['C'] as $item) {
                $collection[] = self::fromArray($item);
            }
            return new Collection($collection, array_key_exists('T', $filter) ? $filter['T'] : 'and');
        } else {
            return new Expression($filter['F'], $filter['V'], array_key_exists('O', $filter) ?
                $filter['O'] : \Sysgear\Operator::EQUAL);
        }
    }

    /**
     * Compile string.
     *
     * Accepts function to compile string from filter. First argument is
     * ether self::COMPILE_COL or COMPILE_EXP. The second is the filter currently
     * evaluated, \Sysgear\Filter\Collection or \Sysgear\Filter\Expression respectively.
     *
     * @param Closure $compiler
     * @return string
     */
    public function compileString(\Closure $compiler)
    {
        return $this->stringBuilder($this, $compiler);
    }

    /**
     * Recursive string builder.
     *
     * @param \Sysgear\Filter\Filter $filter
     * @param Closure $compiler
     * @return string
     */
    public function stringBuilder(self $filter, \Closure $compiler)
    {
        if ($filter instanceof Collection) {
            $parts = array();
            foreach ($filter as $filterElem) {
                $parts[] = $this->stringBuilder($filterElem, $compiler);
            }
            list($left, $oper, $right) = $compiler(Collection::COMPILE_COL, $filter);
            return $left . implode($oper, $parts) . $right;
        } else {

            // if an expression contains an array value, treat it if
            // it is a OR collection of those values.
            $arrValue = $filter->getValue();
            if (is_array($arrValue)) {

                $values = array();
                foreach ($arrValue as $val) {
                    $values[] = new Expression($filter->getField(), $val);
                }
                $col = new Collection($values, 'or');
                return $this->stringBuilder($col, $compiler);
            }

            return $compiler(Collection::COMPILE_EXP, $filter);
        }
    }
}