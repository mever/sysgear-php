<?php

namespace Sysgear;

class Operator
{
    const LIKE = 0;
    const EQUAL = 1;
    const NOT_EQUAL = 2;
    const NUM_GREATER_THAN = 3;
    const NUM_GREATER_OR_EQUAL_THAN = 4;
    const NUM_LESS_THAN = 5;
    const NUM_LESS_OR_EQUAL_THAN = 6;
    const STR_START_WITH = 7;
    const STR_END_WITH = 8;

    public static function toSqlComparison($operator)
    {
        switch ($operator) {
            case self::LIKE: return 'LIKE';
            case self::EQUAL: return '=';
            case self::NOT_EQUAL: return '<>';
            case self::NUM_GREATER_OR_EQUAL_THAN: return '>=';
            case self::NUM_GREATER_THAN: return '>';
            case self::NUM_LESS_OR_EQUAL_THAN: return '<=';
            case self::NUM_LESS_THAN: return '<';
            case self::STR_END_WITH: return 'LIKE';
            case self::STR_START_WITH: return 'LIKE';
        }
    }

    /**
     * Compare two values with each other.
     *
     * TODO: unit test
     *
     * @param mixed $value1
     * @param integer $operator
     * @param mixed $value2
     */
    public static function compare($value1, $operator, $value2)
    {
        switch ($operator) {
            case self::LIKE:
                return (false !== strpos($value1, $value2));

            case self::EQUAL:
                return ($value1 === $value2);

            case self::NOT_EQUAL:
                return ($value1 !== $value2);

            case self::NUM_GREATER_OR_EQUAL_THAN:
                return ($value1 >= $value2);

            case self::NUM_GREATER_THAN:
                return ($value1 > $value2);

            case self::NUM_LESS_OR_EQUAL_THAN:
                return ($value1 <= $value2);

            case self::NUM_LESS_THAN:
                return ($value1 < $value2);

            case self::STR_END_WITH:
                return (0 === strpos($value1, $value2));

            case self::STR_START_WITH:
                return ((strlen($value1) - strlen($value2)) === strpos($value1, $value2));
        }
    }
}