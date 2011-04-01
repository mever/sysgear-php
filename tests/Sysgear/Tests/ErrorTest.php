<?php

/*
 * This file is part of the Sysgear package.
 *
 * (c) Martijn Evers <martijn4evers@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sysgear\Tests;

class ErrorTest extends \PHPUnit_Framework_TestCase
{
    public function testGetComponentCode()
    {
        $error = new ComponentError();
        $this->assertEquals(1, $error->getComponentCode());
    }

    public function testReturnedCodes()
    {
        $error = new ComponentError();
        $codes = $error->getCodes();
        $this->assertEquals(array(1, 2, 3), array_keys($codes));
        $this->assertEquals(array('FIRST_ERROR', 'SECOND_ERROR',
        	'THIRD_ERROR'), array_values($codes));
    }

    public function testReturnedDuplicateCodes()
    {
        $error = new ComponentErrorDuplicateCodes();
        try {
            $codes = $error->getCodes();
            $this->fail("Should throw exception.");
        } catch (\Exception $e) {
            $this->assertEquals("Your error object does not contain ".
            	"unique codes. See:\n * THIRD_ERROR", $e->getMessage());
        }
    }

    public function testReturnedWrongCodes()
    {
        $error = new ComponentErrorNonIntegerCodes();
        try {
            $codes = $error->getCodes();
            $this->fail("Should throw exception.");
        } catch (\Exception $e) {
            $this->assertEquals("Your error object has a none-integer ".
            	"code 'SECOND_ERROR'.", $e->getMessage());
        }
    }

    public function testReturnedLowerThanOneCodes()
    {
        $error = new ComponentErrorLowerThanOneCodes();
        try {
            $codes = $error->getCodes();
            $this->fail("Should throw exception.");
        } catch (\Exception $e) {
            $this->assertEquals("Error code 'SECOND_ERROR' has to ".
            	"be an integer higher than zero.", $e->getMessage());
        }
    }
}

class ComponentError extends \Sysgear\Error
{
    const FIRST_ERROR = 1;
    const SECOND_ERROR = 2;
    const THIRD_ERROR = 3;

    public function getComponentCode()
    {
        return 1;
    }
}

class ComponentErrorDuplicateCodes extends \Sysgear\Error
{
    const FIRST_ERROR = 1;
    const SECOND_ERROR = 2;
    const THIRD_ERROR = 2;

    public function getComponentCode()
    {
        return 2;
    }
}

class ComponentErrorNonIntegerCodes extends \Sysgear\Error
{
    const FIRST_ERROR = 1;
    const SECOND_ERROR = 'two';
    const THIRD_ERROR = 3;

    public function getComponentCode()
    {
        return 3;
    }
}

class ComponentErrorLowerThanOneCodes extends \Sysgear\Error
{
    const FIRST_ERROR = 1;
    const SECOND_ERROR = 0;

    public function getComponentCode()
    {
        return 4;
    }
}