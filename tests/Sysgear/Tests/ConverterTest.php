<?php

/*
 * This file is part of the Sysgear package.
 *
 * (c) Martijn Evers <mevers47@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sysgear\Tests;

use Sysgear\Converter;
use Sysgear\Datatype;
use Sysgear\Test\TestCase;

class ConverterTest extends TestCase
{
    const C = 'Sysgear\Converter';

    public function testContructor_noCaster_noFormatter()
    {
        $converter = new Converter();
        $this->assertInstanceOf('Sysgear\Converter\DefaultCaster', $this->getProp($converter, 'srcCaster'));
        $this->assertNull($this->getProp($converter, 'dstCaster'));

        $formatter = $this->getProp($converter, 'formatter');
        $this->assertInstanceOf('Sysgear\Converter\FormatCollection', $formatter->getFormats());
    }

    public function testContructor_caster_noFormatter()
    {
        $caster = $this->getMock('Sysgear\Converter\CasterInterface');

        $converter = new Converter($caster);
        $this->assertSame($caster, $this->getProp($converter, 'srcCaster'));
        $this->assertNull($this->getProp($converter, 'dstCaster'));

        $formatter = $this->getProp($converter, 'formatter');
        $this->assertInstanceOf('Sysgear\Converter\FormatCollection', $formatter->getFormats());
    }

    public function testContructor_caster_formatter()
    {
        $formatter = $this->getMock('Sysgear\Converter\FormatterInterface');
        $caster = $this->getMock('Sysgear\Converter\CasterInterface');

        $converter = new Converter($caster, $formatter);
        $this->assertSame($formatter, $this->getProp($converter, 'formatter'));
        $this->assertSame($caster, $this->getProp($converter, 'srcCaster'));
        $this->assertNull($this->getProp($converter, 'dstCaster'));
    }

    public function testCast()
    {
        $int = 2;
        $caster = $this->mock('Sysgear\Converter\DefaultCaster', array('cast'));
        $caster->expects($this->once())->method('cast')->with(123.123, $int)->will($this->returnValue(321));

        $converter = $this->mock(self::C);
        $this->setProp($converter, 'srcCaster', $caster);

        $this->assertSame(321, $converter->cast(123.123, $int));
    }

    public function testProcess_noForeignCaster()
    {
        $procent = 4;

        $srcCaster = $this->mock('Sysgear\Converter\DefaultCaster', array('cast'));
        $srcCaster->expects($this->once())->method('cast')
            ->with('12.3456464541321313213245647', $procent)->will($this->returnValue('12.345646454132'));

        $formatter = $this->mock('Sysgear\Converter\DefaultFormatter', array('format'));
        $formatter->expects($this->once())->method('format')
            ->with(12.345646454132, $procent)->will($this->returnValue('12.34 %'));

        $converter = $this->mock(self::C, array('convert'));
        $this->setProp($converter, 'srcCaster', $srcCaster);
        $this->setProp($converter, 'formatter', $formatter);

        $this->assertSame('12.34 %', $converter->process('12.3456464541321313213245647', $procent));
    }

    public function testProcess_foreignCaster()
    {
        $procent = 4;

        $srcCaster = $this->mock('Sysgear\Converter\DefaultCaster', array('cast'));
        $srcCaster->expects($this->once())->method('cast')
        ->with('12.3456464541321313213245647', $procent)->will($this->returnValue('12.345646454132'));

        $dstCaster = $this->mock('Sysgear\Converter\DefaultCaster', array('cast'));
        $dstCaster->expects($this->once())->method('cast')
            ->with('12.345646454132', $procent)->will($this->returnValue('#12.345646454132 %'));

        $converter = $this->mock(self::C, array('convert'));
        $this->setProp($converter, 'srcCaster', $srcCaster);
        $this->setProp($converter, 'dstCaster', $dstCaster);

        $this->assertSame('#12.345646454132 %', $converter->process('12.3456464541321313213245647', $procent));
    }

    public function testConvert_noForeignCaster()
    {
        $datetime = 5;

        $caster = $this->mock('Sysgear\Converter\DefaultCaster', array('cast'));
        $caster->expects($this->once())->method('cast')
            ->with('Oracle Date', $datetime)->will($this->returnValue('PHP Date'));

        $converter = $this->mock(self::C);
        $this->setProp($converter, 'srcCaster', $caster);

        $this->assertSame('PHP Date', $converter->convert('Oracle Date', $datetime));
    }

    public function testConvert()
    {
        $datetime = 6;

        $casterA = $this->mock('Sysgear\Converter\DefaultCaster', array('cast'));
        $casterA->expects($this->once())->method('cast')
            ->with('Oracle Date', $datetime)->will($this->returnValue('PHP Date'));

        $casterB = $this->mock('Sysgear\Converter\DefaultCaster', array('cast'));
        $casterB->expects($this->once())->method('cast')
            ->with('PHP Date', $datetime)->will($this->returnValue('Mongo Date'));

        $converter = $this->mock(self::C);
        $this->setProp($converter, 'srcCaster', $casterA);
        $this->setProp($converter, 'dstCaster', $casterB);

        $this->assertSame('Mongo Date', $converter->convert('Oracle Date', $datetime));
    }

    public function testSerialize()
    {
        $converter = new Converter();
        $this->setProp($converter, 'srcCaster', 'caster A1');
        $this->setProp($converter, 'dstCaster', 'caster B1');
        $this->setProp($converter, 'formatter', 'formatter X1');

        $this->assertEquals('C:17:"Sysgear\Converter":74:{a:2:{s:9:"srcCaster";s:9:'.
            '"caster A1";s:9:"formatter";s:12:"formatter X1";}}', serialize($converter));
    }

    public function testUnserialize()
    {
        $converter = unserialize('C:17:"Sysgear\Converter":74:{a:2:{s:9:"srcCaster";s:9:'.
            '"caster A2";s:9:"formatter";s:12:"formatter X2";}}');

        $this->assertSame('caster A2', $this->getProp($converter, 'srcCaster'));
        $this->assertSame('formatter X2', $this->getProp($converter, 'formatter'));
        $this->assertNull($this->getProp($converter, 'dstCaster'));
    }
}