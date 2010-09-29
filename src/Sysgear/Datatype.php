<?php

namespace Sysgear;

use Doctrine\ORM\EntityManager;
use Zend\Json\Json;

class Datatype
{
    const INT           = 0;
    const STRING        = 1;
    const DATE          = 2;
    const TIME          = 3;
    const DATETIME      = 4;
    const FLOAT         = 5;
    const JSON          = 6;
    const BOOL          = 7;
    const NUMBER        = 8;
    const ARR           = 9;        // Enumerative javascript array
    const XML           = 10;
    const MAP           = 11;       // Associative javascript array
    const ENTITY        = 12;

    /**
     * Return a mysql datatype as string.
     * 
     * @param int $dt
     * @param int $length
     * @return string
     */
    public static function toMysql($dt, $length = 255)
    {
        if (null === $dt) {
            $dt = self::STRING;
        }
        switch($dt) {
            case self::INT:    return 'INT';
            case self::NUMBER: return 'BIGINT';
            case self::STRING: return "VARCHAR({$length})";
            default:           return 'TEXT';
        }
    }

    /**
     * Return a oracle bind type (int constant).
     * 
     * @param int $dt
     * @return int
     */
    public static function toOracleBind($dt)
    {
        switch ($dt)
        {
            case self::BOOL:
            case self::INT:
            case self::NUMBER:
            case self::FLOAT:
                return SQLT_INT;
                break;
                
            case self::STRING:
                return SQLT_CHR;
                break;
                
            default:
                throw new \Exception('This datatype is not supported!');
                break;
        }
    }

    /**
     * Typecast value to string for storage.
     * 
     * @param int $datatype
     * @param mixed $value
     * @return string
     */
    public static function typecastSet($datatype, $value)
    {
        switch($datatype) {
            case self::JSON:    return (is_array($value)) ? Json::encode($value) : $value;
            case self::MAP:     return (is_array($value)) ? Json::encode($value) : $value;
            case self::INT:     return (int) $value;
            case self::FLOAT:   return (float) $value;
            case self::NUMBER:  return (float) $value;
            default:            return $value;
        }
    }

    /**
     * Typecast value from storage.
     * 
     * @param int $datatype
     * @param string $value
     * @return mixed
     */
    public static function typecastGet($datatype, $value)
    {
        switch($datatype) {
            case self::JSON:    return (is_string($value)) ? Json::decode($value) : $value;
            case self::MAP:     return (is_string($value)) ? Json::decode($value) : $value;
            case self::INT:     return (int) $value;
            case self::FLOAT:   return (float) $value;
            case self::NUMBER:  return (float) $value;
            default:            return $value;
        }
    }
}