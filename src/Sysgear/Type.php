<?php

namespace Sysgear;

class Type
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
    const ARR           = 9;        // Enumerative javascript array OR a list of values
    const XML           = 10;
    const MAP           = 11;       // Associative javascript array

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
            case Realtime_DataType::BOOL:
            case Realtime_DataType::INT:
                return SQLT_INT;
                break;
                
            case Realtime_DataType::NUMBER:
            case Realtime_DataType::FLOAT:
            case Realtime_DataType::STRING:
                return SQLT_CHR;
                break;
                
            default:
                throw new Realtime_Db_Exception('This datatype is not supported!');
                break;
        }
    }
}