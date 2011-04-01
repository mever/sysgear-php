<?php

namespace Sysgear;

interface ErrorInterface
{
    /**
     * Return component specific code.
     *
     * Any application can split parts of it in seperate components. E.g.:
     * a login system, user management, blog and forum. Each component may
     * define a seperate namespace for error codes. Each component implementing
     * its own namespace must define a class implementing this interface and
     * choose a unique (within the total application) integer above zero. This
     * chosen integer needs to be retuned by this overwitten method.
     *
     * @return integer
     */
    public function getComponentCode();

    /**
     * Return list of component specific (sub) error codes.
     *
     * Codes returned by this method are component specific, and only need to be
     * unique within the component code namespace.
     *
     * The layout of the returned array is as following:
     *
     * The array is an enumerative list of string values. The array element
     * key represent the code value and MUST be an integer higher than 0. Each string value
     * should describe the code's meaning, this can be derived from the
     * constant name holding the code in the error class.
     *
     * When it becomes a problem we can assert changed code
     * meanings (stored as array keys) based on mismatches between the
     * current code and previous code stored by the system doing assertions
     *
     * @return string[]
     */
    public function getCodes();
}