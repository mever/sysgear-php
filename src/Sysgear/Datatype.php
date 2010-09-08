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
                return SQLT_INT;
                break;
                
            case self::NUMBER:
            case self::FLOAT:
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
     * @param \Doctrine\ORM\EntityManager $entityManager
     * @return string
     */
    public static function typecastSet($datatype, $value, EntityManager $entityManager = null)
    {
        switch($datatype) {
            case self::JSON:    return (is_array($value)) ? Json::encode($value) : $value;
            case self::MAP:     return (is_array($value)) ? Json::encode($value) : $value;
            case self::ENTITY:
                if (null === $entityManager) {
                    throw new \Exception('Can not convert value, no entity manager provided');
                } else {
                    // TODO: Save like this so we can convert back: <ENTITY_NAME>:<PRIMARY_KEY>
                }
            default:            return $value;
        }
    }

    /**
     * Typecast value from storage.
     * 
     * @param int $datatype
     * @param string $value
     * @param \Doctrine\ORM\EntityManager $entityManager
     * @return mixed
     */
    public static function typecastGet($datatype, $value, EntityManager $entityManager = null)
    {
        switch($datatype) {
            case self::JSON:    return (is_string($value)) ? Json::decode($value) : $value;
            case self::MAP:     return (is_string($value)) ? Json::decode($value) : $value;
            case self::ENTITY:
                if (null === $entityManager) {
                    throw new \Exception('Can not convert value, no entity manager provided');
                } else {
                }
            default:            return $value;
        }
    }
}