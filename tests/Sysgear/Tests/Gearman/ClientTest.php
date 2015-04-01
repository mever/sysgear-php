<?php

/*
 * This file is part of the Sysgear package.
 *
 * (c) Martijn Evers <martijn4evers@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace {
    if (!extension_loaded('gearman')) {
        define('GEARMAN_SUCCESS', 1);
        define('GEARMAN_SERVER_ERROR', 18);
    }
}

namespace Sysgear\Tests\Gearman {

    use Sysgear\Gearman\Client,
        Sysgear\Test\TestCase;

    class ClientTest extends TestCase
    {
        const C = '\Sysgear\Gearman\Client';
        const C_J = '\Sysgear\Gearman\Job';
        const C_C = '\GearmanClient';

        public function testConstruct()
        {
            $c = $this->mock(self::C_C);
            $instance = new Client($c);
            $this->assertSame($c, $this->getProp($instance, 'gearmanClient'));
        }

        public function testDoBackground_success()
        {
            $j = $this->mock(self::C_J, array('getName'));
            $j->expects($this->once())->method('getName')->will($this->returnValue('test-job'));
            $sj = $this->getSerializableMock($j, null, self::C_J);

            $test = $this;
            $g = $this->mock(self::C_C, array('doBackground', 'returnCode'));
            $g->expects($this->once())->method('returnCode')->will($this->returnValue(\GEARMAN_SUCCESS));
            $g->expects($this->once())->method('doBackground')->with($this->identicalTo('test-job'))
                ->will($this->returnCallback(function($name, $serializedJob) use ($test, $sj) {
                    $test->assertEquals($sj, unserialize($serializedJob));
                }));

            $c = $this->mock(self::C);
            $this->setProp($c, 'gearmanClient', $g);

            $c->doBackground($sj);
        }

        /**
         * @expectedException \RuntimeException
         */
        public function testDoBackground_failed()
        {
            $j = $this->mock(self::C_J, array('getName'));
            $j->expects($this->once())->method('getName')->will($this->returnValue('test-job'));
            $sj = $this->getSerializableMock($j, null, self::C_J);

            $test = $this;
            $g = $this->mock(self::C_C, array('doBackground', 'returnCode'));
            $g->expects($this->exactly(2))->method('returnCode')->will($this->returnValue(\GEARMAN_SERVER_ERROR));
            $g->expects($this->once())->method('doBackground')->with($this->identicalTo('test-job'))
                ->will($this->returnCallback(function($name, $serializedJob) use ($test, $sj) {
                    $test->assertEquals($sj, unserialize($serializedJob));
                }));

            $c = $this->mock(self::C);
            $this->setProp($c, 'gearmanClient', $g);

            $c->doBackground($sj);
        }

        public function testDoBackground_additionalParameters()
        {
            $j = $this->mock(self::C_J, array('getName', 'hasParameter', 'setParameter'));
            $j->expects($this->once())->method('getName')->will($this->returnValue('test-job'));
            $j->expects($this->at(0))->method('hasParameter')->with('aaa')->will($this->returnValue(false));
            $j->expects($this->at(1))->method('setParameter')->with('aaa', 111);
            $j->expects($this->at(2))->method('hasParameter')->with('bbb')->will($this->returnValue(true));
            $sj = $this->getSerializableMock($j, null, self::C_J);

            $test = $this;
            $g = $this->mock(self::C_C, array('doBackground', 'returnCode'));
            $g->expects($this->once())->method('returnCode')->will($this->returnValue(\GEARMAN_SUCCESS));
            $g->expects($this->once())->method('doBackground')->with($this->identicalTo('test-job'))
                ->will($this->returnCallback(function($name, $serializedJob) use ($test, $sj) {
                    $test->assertEquals($sj, unserialize($serializedJob));
                }));

            $additionalJobParameters = array('aaa' => 111, 'bbb' => 222);

            $c = $this->mock(self::C);
            $this->setProp($c, 'additionalJobParameters', $additionalJobParameters);
            $this->setProp($c, 'gearmanClient', $g);

            $c->doBackground($sj);
        }
    }
}