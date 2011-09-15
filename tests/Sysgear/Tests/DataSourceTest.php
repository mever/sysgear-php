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

use Sysgear\DataSource;

class DataSourceTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $ds = new DataSource('test://ABC');
        $this->assertEquals('test', $ds->getProtocol());
        $this->assertEquals('ABC', $ds->getDataUnit());
        $this->assertNull($ds->getContext());
        $this->assertEquals(array('C' => array(), 'T' => 'and'),
            $ds->getFilters()->toArray());
    }

    public function testFilters()
    {
        $ds = new DataSource('test://ABC/not a filter');
        $this->assertNotNull($ds->getFilters());
        $this->assertEquals(array('C' => array(), 'T' => 'and'),
            $ds->getFilters()->toArray());
    }

    public function testContext()
    {
        $context = $this->getMock('Serializable');

        $ds = new DataSource('test://ABC');
        $ds->setContext($context);

        $this->assertEquals($context, $ds->getContext());
    }
}