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
}