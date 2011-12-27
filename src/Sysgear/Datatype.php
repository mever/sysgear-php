<?php

namespace Sysgear;

use Doctrine\ORM\EntityManager;

class Datatype
{
    const INT           = 0;
    const STRING        = 1;
    const DATE          = 2;
    const TIME          = 3;
    const DATETIME      = 4;
    const FLOAT         = 5;
    const JSON          = 6;    // PHP object
    const BOOL          = 7;
    const NUMBER        = 8;
    const ARR           = 9;    // Enumerative PHP array
    const XML           = 10;
    const MAP           = 11;   // Associative PHP array
    const ENTITY        = 12;
    const PASSWORD      = 13;
    const EMAIL         = 14;

    /**
     * Show datatype as human readable string.
     *
     * @param integer $dt
     */
    public static function toDesc($dt)
    {
        $refClass = new \ReflectionClass(__CLASS__);
        foreach ($refClass->getConstants() as $name => $code) {
            if ($dt == $code) {
                return strtolower($name);
            }
        }
    }

    /**
     * Return doctrine BDAL datatype code.
     *
     * @param integer $datatypeCode
     * @return string
     */
    public static function toDoctrineDbal($datatypeCode)
    {
        switch ($datatypeCode) {
            case self::INT:
                return 'integer';

            case self::FLOAT:
            case self::NUMBER:
                return 'float';

            case self::DATE: return 'date';
            case self::TIME: return 'time';
            case self::DATETIME: return 'datetime';

            default:
                return 'string';
        }
    }

    /**
     * Return a mysql datatype as string.
     *
     * @param int $dt
     * @param int $length
     * @return string
     */
    public static function toMysql($dt, $length = 255)
    {
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
     * Convert Oracle datatype to sysgear datatype.
     *
     * @param string $oracleType
     * @return integer
     */
    public static function fromOracle($oracleType)
    {
        switch ($oracleType) {
        case 'NUMBER':  return self::NUMBER;
        case 'DATE':    return self::DATETIME;
        default:        return self::STRING;
        }
    }

    /**
     * Return a printable string.
     *
     * @param integer $datatype
     * @param mixed $value
     * @return string Printable string
     */
    public static function getPrintableString($datatype, $value)
    {
        switch($datatype) {
            case self::JSON:
            case self::MAP:
            case self::ARR:
                return (is_array($value) || is_object($value)) ?
                    \json_encode($value) : $value;

            case self::BOOL:
                return (('false' === $value) ? 0 : (boolean) $value) ? 'true' : 'false';

            default:
                if (is_bool($value)) { return $value ? 'true' : 'false'; }
                if (is_null($value)) { return 'null'; }
                if (is_numeric($value)) { return (string) $value; }

                $str = print_r($value, true);
                return "\"{$str}\"";
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
            case self::JSON:
                return (is_array($value) || is_object($value)) ? \json_encode($value) : $value;

            case self::MAP:
                return (is_array($value) || is_object($value)) ? \json_encode($value) : $value;

            case self::BOOL:    return ('false' === $value) ? 0 : (int) (boolean) $value;
            default:            return (string) $value;
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
        if (null === $datatype) {
            $datatype = -1;
        }

        switch($datatype) {
            case self::JSON:    return \json_decode($value);
            case self::MAP:     return (array) \json_decode($value);
            case self::ARR:     return (array) \json_decode($value);
            case self::INT:     return (int) $value;
            case self::FLOAT:   return (float) $value;
            case self::NUMBER:  return (float) $value;
            case self::STRING:  return (string) $value;
            case self::BOOL:
                return ('n' === strtolower($value) ||
                	'false' === strtolower($value)) ? false : (boolean) $value;

            default:
                throw new \InvalidArgumentException('first argument must be '.
                	'one of the constant datatype integers from this class');
        }
    }

    /**
     * Cast date, time and datetime fields in $records to
     * UTC date, time and datetime values.
     *
     * @param string $timezone Any timezone specified in the latest timezone
     * 	 database for the given time or datetimes. See: http://nl3.php.net/manual/en/timezones.php
     *
     * @param array $records Two dimensional array of rows and cells
     * @param array $datatypes Date types to cast indexed by column index
     */
    public static function castDatesInRecords($timezone, array &$records, array $datatypes)
    {
        $cast = function($cellValue, $dt) use ($timezone) {
            if (empty($cellValue)) {
                return null;
            }

            switch ($dt) {
                case Datatype::DATE:
                    $date = new \DateTime($cellValue, new \DateTimeZone($timezone));
                    return $date->format('Y-m-d');

                case Datatype::DATETIME:
                    $date = new \DateTime($cellValue, new \DateTimeZone($timezone));
                    $date->setTimezone(new \DateTimeZone('Zulu'));    // set datetime to UTC (aka Zulu)
                    return $date->format(\DATE_W3C);

                case Datatype::TIME:
                    $date = new \DateTime($cellValue, new \DateTimeZone($timezone));
                    $date->setTimezone(new \DateTimeZone('Zulu'));    // set datetime to UTC (aka Zulu)
                    return $date->format('H:i:s');

                default:
                    throw new \Exception("Can not cast (date, time or datetime) to UTC.");
            }
        };

        // perform cast
        foreach ($records as &$record) {
            foreach ($datatypes as $idx => $dt) {
                $record[$idx] = $cast($record[$idx], $dt);
            }
        }

        unset($record);
    }

    /**
     * Cast a date, time or datetime to UTC date, time or datetime value.
     *
     * @param string $timezone Any timezone specified in the latest timezone
     * 	 database for the given date, time or datetime. See: http://nl3.php.net/manual/en/timezones.php
     *
     * @param string $datatype One of the datatype constants of this class
     * @param string $value One of the values confirm the datatype given
     *
     * @throws \Exception
     * @return \DateTime Datetime object in Zulu timzone (UTC).
     */
    public static function castDate($timezone, $datatype, $value)
    {
        if (empty($value)) {
            return null;
        }

        switch ($datatype) {
            case Datatype::DATE:
                return new \DateTime($value, new \DateTimeZone('Zulu'));

            case Datatype::DATETIME:
                $date = new \DateTime($value, new \DateTimeZone($timezone));
                $date->setTimezone(new \DateTimeZone('Zulu'));    // set datetime to UTC (aka Zulu)
                return $date;

            case Datatype::TIME:
                $date = new \DateTime($value, new \DateTimeZone($timezone));
                $date->setTimezone(new \DateTimeZone('Zulu'));    // set datetime to UTC (aka Zulu)
                return $date;

            default:
                throw new \Exception("Can not cast (date, time or datetime) to UTC datetime.");
        }
    }

    /**
     * Return true if a given datatype is a date, time or datetime.
     *
     * @param integer $datatype Any datatype constant
     * @return boolean
     */
    public static function isDate($datatype)
    {
        return ($datatype > 1 && $datatype < 5);
    }

    /**
     * Return true if a given datatype is numeric.
     *
     * @param integer $datatype
     */
    public static function isNumber($datatype)
    {
        if (null === $datatype) {
            return false;
        }

        switch ($datatype) {
            case self::INT:
            case self::FLOAT:
            case self::NUMBER:
                return true;
        }

        return false;
    }
}