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
        $this->assertEquals('2012-05-03 15:55:12', $builder->cast('2012-05-03 15:55:12', Datatype::DATETIME));
    }

    public function testCast_noCastMethods()
    {
        $builder = new BuildCaster();
        $builder->useDefaultTypes();
        $this->assertEquals('2012-05-03 15:55:12', $builder->cast('2012-05-03 15:55:12', 999));
    }

    public function testCast_castString()
    {
        $builder = new BuildCaster();
        $builder->useDefaultTypes();

        $this->assertSame('15', $builder->cast(0xF, Datatype::STRING));
        $this->assertSame('123', $builder->cast(123, Datatype::STRING));
        $this->assertSame('0', $builder->cast('0', Datatype::STRING));
        $this->assertSame('', $builder->cast('', Datatype::STRING));
        $this->assertNull($builder->cast(null, Datatype::STRING));
    }

    public function testCast_castInt()
    {
        $builder = new BuildCaster();
        $builder->useDefaultTypes();

        $this->assertSame(123, $builder->cast('123', Datatype::INT));
        $this->assertSame(0, $builder->cast('0', Datatype::INT));
        $this->assertNull($builder->cast('', Datatype::INT));
        $this->assertNull($builder->cast(null, Datatype::INT));
    }

    public function testCast_castInt_float()
    {
        $builder = new BuildCaster();
        $builder->useDefaultTypes();

        $this->assertSame(123, $builder->cast('123.45', Datatype::INT));
        $this->assertSame(0, $builder->cast('0.0', Datatype::INT));
        $this->assertNull($builder->cast('', Datatype::INT));
        $this->assertNull($builder->cast(null, Datatype::INT));
    }

    public function testCast_castNumber()
    {
        $builder = new BuildCaster();
        $builder->useDefaultTypes();

        $this->assertSame((float) 123, $builder->cast('123.00', Datatype::NUMBER));
        $this->assertSame((float) 123, $builder->cast('123', Datatype::NUMBER));
        $this->assertSame((float) 0, $builder->cast('0', Datatype::NUMBER));
        $this->assertNull($builder->cast('', Datatype::NUMBER));
        $this->assertNull($builder->cast(null, Datatype::NUMBER));
    }

    public function testCast_castFloat()
    {
        $builder = new BuildCaster();
        $builder->useDefaultTypes();

        $this->assertSame(123.45, $builder->cast('123.45', Datatype::FLOAT));
        $this->assertSame(0.0, $builder->cast('0.0', Datatype::FLOAT));
        $this->assertSame(0.0, $builder->cast('0', Datatype::FLOAT));
        $this->assertNull($builder->cast('', Datatype::FLOAT));
        $this->assertNull($builder->cast(null, Datatype::FLOAT));
    }

    public function testCast_castDatatime()
    {
        $builder = new BuildCaster();
        $builder->useDefaultTypes();
        $date = new \DateTime('2012-05-03 16:46:24', new \DateTimeZone('UTC'));
        $this->assertEquals($date, $builder->cast('2012-05-03 16:46:24', Datatype::DATETIME));
        $this->assertNull($builder->cast('', Datatype::DATETIME));
        $this->assertNull($builder->cast(null, Datatype::DATETIME));
    }

    public function testCast_castDatatime_default_zulu()
    {
        $builder = new BuildCaster();
        $builder->set(Datatype::DATETIME, 'return new \\DateTime($v, $tz)');

        $date = new \DateTime('2012-05-03 16:46:24', new \DateTimeZone('UTC'));
        $this->assertEquals($date, $builder->cast('2012-05-03 16:46:24', Datatype::DATETIME));
    }

    public function testCast_castDatatime_EuropeAmsterdam()
    {
        $builder = new BuildCaster();
        $builder->set(Datatype::DATETIME, 'return new \\DateTime($v, $tz)');
        $builder->setTimezone(new \DateTimeZone('Europe/Amsterdam'));

        $date = new \DateTime('2012-05-03 16:46:24');
        $this->assertEquals($date, $builder->cast('2012-05-03 16:46:24', Datatype::DATETIME));
    }

    public function testCast_castDate()
    {
        $builder = new BuildCaster();
        $builder->useDefaultTypes();

        $date = new \DateTime('2012-05-07 00:00:00', new \DateTimeZone('UTC'));
        $this->assertEquals($date, $builder->cast('07-05-2012', Datatype::DATE));
        $this->assertNull($builder->cast('', Datatype::DATE));
        $this->assertNull($builder->cast(null, Datatype::DATE));
    }

    public function testCast_castDatatime_twoStatments()
    {
        $builder = new BuildCaster();
        $builder->set(Datatype::DATETIME, '$date = new \\DateTime($v); return $date');

        $date = new \DateTime('2012-05-03 16:46:24');
        $this->assertEquals($date, $builder->cast('2012-05-03 16:46:24', Datatype::DATETIME));
    }

    public function testCast_castTime()
    {
        $builder = new BuildCaster();
        $builder->useDefaultTypes();

        $date = new \DateTime('01-01-1970 16:06:45', new \DateTimeZone('UTC'));
        $this->assertEquals($date, $builder->cast('16:06:45', Datatype::TIME));
        $this->assertNull($builder->cast('', Datatype::TIME));
        $this->assertNull($builder->cast(null, Datatype::TIME));
    }

    public function testSerialize()
    {
        $builder = new BuildCaster();
        $this->assertEquals('C:29:"Sysgear\Converter\BuildCaster":22:{a:2:{i:0;a:0:{}i:1;N;}}', serialize($builder));
    }

    public function testSerialize_withTypeCaster()
    {
        $builder = new BuildCaster();
        $builder->set(Datatype::INT, 'return (int) $v');

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