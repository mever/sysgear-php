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
        $this->assertTrue(0 === $builder->cast(Datatype::INT, '0'));
        $this->assertNull($builder->cast(Datatype::INT, ''));
        $this->assertNull($builder->cast(Datatype::INT, null));
    }

    public function testCast_castInt_float()
    {
        $builder = new BuildCaster();
        $builder->useDefaultTypes();

        $this->assertTrue(123 === $builder->cast(Datatype::INT, '123.45'));
        $this->assertTrue(0 === $builder->cast(Datatype::INT, '0.0'));
        $this->assertNull($builder->cast(Datatype::INT, ''));
        $this->assertNull($builder->cast(Datatype::INT, null));
    }

    public function testCast_castNumber()
    {
        $builder = new BuildCaster();
        $builder->useDefaultTypes();

        $this->assertTrue((float) 123 === $builder->cast(Datatype::NUMBER, '123.00'));
        $this->assertTrue((float) 123 === $builder->cast(Datatype::NUMBER, '123'));
        $this->assertTrue((int) 123 !== $builder->cast(Datatype::NUMBER, '123'));
        $this->assertTrue(0.0 === $builder->cast(Datatype::NUMBER, '0'));
        $this->assertNull($builder->cast(Datatype::NUMBER, ''));
        $this->assertNull($builder->cast(Datatype::NUMBER, null));
    }

    public function testCast_castFloat()
    {
        $builder = new BuildCaster();
        $builder->useDefaultTypes();

        $this->assertTrue(123.45 === $builder->cast(Datatype::FLOAT, '123.45'));
        $this->assertTrue(0.0 === $builder->cast(Datatype::FLOAT, '0.0'));
        $this->assertTrue(0.0 === $builder->cast(Datatype::FLOAT, '0'));
        $this->assertNull($builder->cast(Datatype::FLOAT, ''));
        $this->assertNull($builder->cast(Datatype::FLOAT, null));
    }

    public function testCast_castDatatime()
    {
        $builder = new BuildCaster();
        $builder->useDefaultTypes();
        $date = new \DateTime('2012-05-03 16:46:24', new \DateTimeZone('UTC'));
        $this->assertEquals($date, $builder->cast(Datatype::DATETIME, '2012-05-03 16:46:24'));
        $this->assertNull($builder->cast(Datatype::DATETIME, ''));
        $this->assertNull($builder->cast(Datatype::DATETIME, null));
    }

    public function testCast_castDatatime_default_zulu()
    {
        $builder = new BuildCaster();
        $builder->add(Datatype::DATETIME, 'return new \\DateTime($v, $tz)');

        $date = new \DateTime('2012-05-03 16:46:24', new \DateTimeZone('UTC'));
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

    public function testCast_castDate()
    {
        $builder = new BuildCaster();
        $builder->useDefaultTypes();
        $this->assertEquals('2012-05-07', $builder->cast(Datatype::DATE, '07-05-2012'));
        $this->assertNull($builder->cast(Datatype::DATE, ''));
        $this->assertNull($builder->cast(Datatype::DATE, null));
    }

    public function testCast_castDatatime_twoStatments()
    {
        $builder = new BuildCaster();
        $builder->add(Datatype::DATETIME, '$date = new \\DateTime($v); return $date');

        $date = new \DateTime('2012-05-03 16:46:24');
        $this->assertEquals($date, $builder->cast(Datatype::DATETIME, '2012-05-03 16:46:24'));
    }

    public function testCast_castTime()
    {
        $builder = new BuildCaster();
        $builder->useDefaultTypes();
        $this->assertEquals('16:06:45', $builder->cast(Datatype::TIME, '16:06:45'));
        $this->assertNull($builder->cast(Datatype::TIME, ''));
        $this->assertNull($builder->cast(Datatype::TIME, null));
    }

    public function testSerialize()
    {
        $builder = new BuildCaster();
        $this->assertEquals('C:29:"Sysgear\Converter\BuildCaster":22:{a:2:{i:0;a:0:{}i:1;N;}}', serialize($builder));
    }

    public function testSerialize_withTypeCaster()
    {
        $builder = new BuildCaster();
        $builder->add(Datatype::INT, 'return (int) $v');

        $this->assertEquals('C:29:"Sysgear\Converter\BuildCaster":49:{a:2:{i:0;a:1:'.
            '{i:0;s:15:"return (int) $v";}i:1;N;}}', serialize($builder));
    }

    public function testSerialize_withTimezone()
    {
        $builder = new BuildCaster();
        $builder->setTimezone(new \DateTimeZone('Europe/Amsterdam'));
        $this->assertEquals('C:29:"Sysgear\Converter\BuildCaster":44:{a:2:{i:0;a:0:{}i:1;s:16:"Europe/Amsterdam";}}', serialize($builder));
    }
}