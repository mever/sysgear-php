<?php

/*
 * This file is part of the Sysgear package.
 *
 * (c) Martijn Evers <martijn4evers@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sysgear\Tests\Gearman;

use Sysgear\Gearman\Job,
    Sysgear\Test\TestCase;

class JobTest extends TestCase
{
    const C = '\Sysgear\Gearman\Job';

    public function testConstruct()
    {
        $instance = new Job('test-job');
        $this->assertSame('test-job', $this->getProp($instance, 'name'));
        $this->assertSame(array(), $this->getProp($instance, 'parameters'));
    }

    public function testConstruct_withParameters()
    {
        $instance = new Job('test-job', array('a', 1));
        $this->assertSame('test-job', $this->getProp($instance, 'name'));
        $this->assertSame(array('a', 1), $this->getProp($instance, 'parameters'));
    }

    public function testHasParameter()
    {
        $job = $this->mock(self::C);
        $this->assertFalse($job->hasParameter('foo'));

        $this->setProp($job, 'parameters', array('bar' => null, 'baz' => 123));
        $this->assertFalse($job->hasParameter('foo'));
        $this->assertTrue($job->hasParameter('bar'));
        $this->assertTrue($job->hasParameter('baz'));
    }

    public function testGetParameter()
    {
        $job = $this->mock(self::C);
        $this->setProp($job, 'parameters', array('bar' => null, 'baz' => 123));
        $this->assertNull($job->GetParameter('foo', null));
        $this->assertNull($job->GetParameter('bar'));
        $this->assertSame(123, $job->GetParameter('baz'));
    }

    public function testGetParameter_notFound()
    {
        $job = $this->mock(self::C);
        $this->setProp($job, 'parameters', array('bar' => null, 'baz' => 123));
        $this->assertSame(321, $job->GetParameter('foo', 321));

        try {
            $this->assertNull($job->GetParameter('foo'));
            $this->fail("Must throw invalid argument exception");
        } catch (\InvalidArgumentException $e) {}
    }
}