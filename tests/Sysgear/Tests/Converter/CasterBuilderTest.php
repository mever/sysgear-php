<?php

/*
 * This file is part of the Sysgear package.
 *
 * (c) Martijn Evers <mevers47@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sysgear\Tests\Converter;

use Sysgear\Datatype;
use Sysgear\Converter\CasterBuilder;

class CasterBuilderTest extends \PHPUnit_Framework_TestCase
{
    public function testCast_noCastMethods_noDefaultTypes()
    {
        $builder = new CasterBuilder();
        $this->assertEquals('2012-05-03 15:55:12', $builder->cast(Datatype::DATETIME, '2012-05-03 15:55:12'));
    }

    public function testCast_noCastMethods()
    {
        $builder = new CasterBuilder();
        $builder->useDefaultTypes();
        $this->assertEquals('2012-05-03 15:55:12', $builder->cast(999, '2012-05-03 15:55:12'));
    }

    public function testCast_castInt()
    {
        $builder = new CasterBuilder();
        $builder->useDefaultTypes();

        $this->assertTrue(123 === $builder->cast(Datatype::INT, '123'));
    }

    public function testCast_castInt_float()
    {
        $builder = new CasterBuilder();
        $builder->useDefaultTypes();

        $this->assertTrue(123 === $builder->cast(Datatype::INT, '123.45'));
    }

    public function testCast_castNumber()
    {
        $builder = new CasterBuilder();
        $builder->useDefaultTypes();

        $this->assertTrue((float) 123 === $builder->cast(Datatype::NUMBER, '123.00'));
        $this->assertTrue((float) 123 === $builder->cast(Datatype::NUMBER, '123'));
        $this->assertTrue((int) 123 !== $builder->cast(Datatype::NUMBER, '123'));
    }

    public function testCast_castFloat()
    {
        $builder = new CasterBuilder();
        $builder->useDefaultTypes();

        $this->assertTrue(123.45 === $builder->cast(Datatype::FLOAT, '123.45'));
    }

    public function testCast_castDatatime()
    {
        $builder = new CasterBuilder();
        $builder->add(Datatype::DATETIME, 'new \\DateTime($v)');

        $date = new \DateTime('2012-05-03 16:46:24');
        $this->assertEquals($date, $builder->cast(Datatype::DATETIME, '2012-05-03 16:46:24'));
    }
}