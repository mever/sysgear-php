<?php

namespace Sysgear;

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
    public static function normalizeDir($dirPath)
    {
        $dirPath .= ('/' === substr($dirPath, -1)) ? '' : '/';
        return $dirPath;
    }

    /**
     * Return the number of seconds between two DateTime objects.
     *
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @return int
     */
    public static function getDateDiff($startDate, $endDate)
    {
        return strtotime($endDate->format('c')) - strtotime($startDate->format('c'));
    }

    /**
     * Return the short class name for the object or class.
     *
     * @param string|object $object Classname or object
     * @return string Short class name
     */
    public static function getShortClassName($object)
    {
        $fullClassname = is_string($object) ? $object : get_class($object);
        $pos = strrpos($fullClassname, '\\');
        return (false === $pos) ? $fullClassname : substr($fullClassname, $pos + 1);
    }
}