<?php

namespace Sysgear;

abstract class Error implements ErrorInterface
{
    public function getCodes()
    {
        $codes = array();
        $refClass = new \ReflectionClass($this);
        $constants = $refClass->getConstants();

        // Check if codes are unique.
        if (count($constants) !== count(array_unique($constants))) {
            throw new \Exception("Your error object does not contain unique codes. See:\n * ".
            	join("\n * ", array_keys(array_diff_key($constants, array_unique($constants)))));
        }

        // Collect and check error codes.
        foreach ($constants as $name => $code) {
            if (! is_integer($code)) {
                throw new \Exception("Your error object has a non-integer code '{$name}'.");
            }

            if ($code <= 0) {
                throw new \Exception("Error code '{$name}' has to be an integer greater than zero.");
            }
            $codes[$code] = $name;
        }
        return $codes;
    }
}