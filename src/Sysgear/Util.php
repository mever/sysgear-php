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

    /**
     * Creates a new class instance without invoking the constructor.
     *
     * @param string $class
     * @return object
     */
    public static function createInstanceWithoutConstructor($class)
    {
        $reflector = new \ReflectionClass($class);
        if (PHP_VERSION_ID >= 50400) {
            return $reflector->newInstanceWithoutConstructor();
        }

        $properties = $reflector->getProperties();
        $defaults = $reflector->getDefaultProperties();

        $serealized = "O:" . strlen($class) . ":\"$class\":".count($properties) .':{';
        foreach ($properties as $property) {
            $name = $property->getName();
            if ($property->isProtected()) {
                $name = chr(0) . '*' .chr(0) .$name;

            } elseif($property->isPrivate()) {
                $name = chr(0)  . $class.  chr(0).$name;
            }

            $serealized .= serialize($name);
            if(array_key_exists($property->getName(),$defaults) ){
                $serealized .= serialize($defaults[$property->getName()]);

            } else {
                $serealized .= serialize(null);
            }
        }

        $serealized .= "}";
        return unserialize($serealized);
    }
}