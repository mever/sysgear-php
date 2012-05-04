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
use Sysgear\Converter\BuildCaster;

class BuildCasterTest extends \PHPUnit_Framework_TestCase
{
    public function testCast_noCastMethods_noDefaultTypes()
    {
        $builder = new BuildCaster();
        $this->assertEquals('2012-05-03 15:55:12', $builder->cast(Datatype::DATETIME, '2012-05-03 15:55:12'));
    }

    public function testCast_noCastMethods()
    {
        $builder = new BuildCaster();
        $builder->useDefaultTypes();
        $this->assertEquals('2012-05-03 15:55:12', $builder->cast(999, '2012-05-03 15:55:12'));
    }

    public function testCast_castInt()
    {
        $builder = new BuildCaster();
        $builder->useDefaultTypes();

        $this->assertTrue(123 === $builder->cast(Datatype::INT, '123'));
    }

    public function testCast_castInt_float()
    {
        $builder = new BuildCaster();
        $builder->useDefaultTypes();

        $this->assertTrue(123 === $builder->cast(Datatype::INT, '123.45'));
    }

    public function testCast_castNumber()
    {
        $builder = new BuildCaster();
        $builder->useDefaultTypes();

        $this->assertTrue((float) 123 === $builder->cast(Datatype::NUMBER, '123.00'));
        $this->assertTrue((float) 123 === $builder->cast(Datatype::NUMBER, '123'));
        $this->assertTrue((int) 123 !== $builder->cast(Datatype::NUMBER, '123'));
    }

    public function testCast_castFloat()
    {
        $builder = new BuildCaster();
        $builder->useDefaultTypes();

        $this->assertTrue(123.45 === $builder->cast(Datatype::FLOAT, '123.45'));
    }

    public function testCast_castDatatime()
    {
        $builder = new BuildCaster();
        $builder->useDefaultTypes();
        $date = new \DateTime('2012-05-03 16:46:24', new \DateTimeZone('Zulu'));
        $this->assertEquals($date, $builder->cast(Datatype::DATETIME, '2012-05-03 16:46:24'));
    }

    public function testCast_castDatatime_default_zulu()
    {
        $builder = new BuildCaster();
        $builder->add(Datatype::DATETIME, 'return new \\DateTime($v, $tz)');

        $date = new \DateTime('2012-05-03 16:46:24', new \DateTimeZone('Zulu'));
        $this->assertEquals($date, $builder->cast(Datatype::DATETIME, '2012-05-03 16:46:24'));
    }

    public function testCast_castDatatime_EuropeAmsterdam()
    {
        $builder = new BuildCaster();
        $builder->add(Datatype::DATETIME, 'return new \\DateTime($v, $tz)');
        $builder->setTimezone(new \DateTimeZone('Europe/Amsterdam'));

        $date = new \DateTime('2012-05-03 16:46:24');
        $this->assertEquals($date, $builder->cast(Datatype::DATETIME, '2012-05-03 16:46:24'));
    }

    public function testCast_castDatatime_twoStatments()
    {
        $builder = new BuildCaster();
        $builder->add(Datatype::DATETIME, '$date = new \\DateTime($v); return $date');

        $date = new \DateTime('2012-05-03 16:46:24');
        $this->assertEquals($date, $builder->cast(Datatype::DATETIME, '2012-05-03 16:46:24'));
    }
}