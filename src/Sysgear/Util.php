<?php

namespace Realtime;

class Util
{

    /**
     * Sort a two dimensional array on field.
     * 
     * @param array $original
     * @param string|int $field
     * @param boolean $descending
     */
    public static function sortArrayByField($original, $field, $descending = false)
    {
        $sortArr = array();
        foreach ($original as $key => $value) {
            $sortArr[$key] = $value[$field];
        }
        if ($descending) {
            arsort($sortArr);
        } else {
            asort($sortArr);
        }
        $resultArr = array();
        foreach ($sortArr as $key => $value) {
            $resultArr[$key] = $original[$key];
        }
        return $resultArr;
    }

    /**
     * Normalize a directory path to always end with a slash.
     * All slashes are forward.
     * 
     * @param string $dirPath
     * @return $string
     */
    public static function normalizeDirPath($dirPath)
    {
        $dirPath .= ('/' === substr($dirPath, - 1)) ? '' : '/';
        return $dirPath;
    }
}