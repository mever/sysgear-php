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

use Sysgear\Cursor;

class CursorTest extends \PHPUnit_Framework_TestCase
{
    public function testContruct()
    {
        $pointer = 0;
        $records = array(
            array(11, 12, 13),
            array(21, 22, 23),
            array(31, 32, 33)
        );

        $callback = function() use ($records, &$pointer) {
            return $records[$pointer++];
        };

        $cursor = new Cursor($callback);

        $refProp = new \ReflectionProperty($cursor, 'callback');
        $refProp->setAccessible(true);

        $this->assertSame($callback, $refProp->getValue($cursor));
        $this->assertNull($cursor->count());
    }

    public function testContruct_count()
    {
        $cursor = new Cursor(function() {}, 3);
        $this->assertEquals(3, $cursor->count());
    }

    public function testGetNext()
    {
        $records = array(
            array(11, 12, 13),
            array(21, 22, 23),
            array(31, 32, 33)
        );

        $cursor = new Cursor(function($pointer) use ($records) {
            return $records[$pointer];
        });

        $this->assertEquals(array(11, 12, 13), $cursor->getNext());
        $this->assertEquals(array(21, 22, 23), $cursor->getNext());
        $this->assertEquals(array(31, 32, 33), $cursor->getNext());
    }

    public function testGetNext_withCount()
    {
        $records = array(
            array(11, 12, 13),
            array(21, 22, 23),
            array(31, 32, 33)
        );

        $cursor = new Cursor(function($pointer) use ($records) {
            return $records[$pointer];
        }, 2);

        $this->assertEquals(array(11, 12, 13), $cursor->getNext());
        $this->assertEquals(array(21, 22, 23), $cursor->getNext());
        $this->assertEquals(array(31, 32, 33), $cursor->getNext());
    }

    public function testGetNext_traversable_endByReturningNull()
    {
        $records = array(
            array(11, 12, 13),
            array(21, 22, 23),
            array(31, 32, 33),
            null
        );

        $cursor = new Cursor(function($pointer) use ($records) {
            return $records[$pointer];
        });

        $actualRecords = array();
        foreach ($cursor as $record) {
            $actualRecords[] = $record;
        }

        unset($records[3]);
        $this->assertEquals($records, $actualRecords);
    }

    public function testGetNext_traversable_endBySpecifingCount()
    {
        $records = array(
            array(11, 12, 13),
            array(21, 22, 23),
            array(31, 32, 33)
        );

        $cursor = new Cursor(function($pointer) use ($records) {
            return $records[$pointer];
        }, 3);

        $actualRecords = array();
        foreach ($cursor as $record) {
            $actualRecords[] = $record;
        }

        $this->assertEquals($records, $actualRecords);
    }

    public function testNext()
    {
        $records = array(
            array(11, 12, 13),
            array(21, 22, 23),
            array(31, 32, 33)
        );

        $cursor = new Cursor(function($pointer) use ($records) {
            return $records[$pointer];
        });

        $this->assertEquals(array(11, 12, 13), $cursor->getNext());
        $cursor->next();
        $this->assertEquals(array(31, 32, 33), $cursor->getNext());
    }

    public function testIterator()
    {
        $records = array(
            array(11, 12, 13),
            array(21, 22, 23),
            array(31, 32, 33)
        );

        $pointer = 0;
        $cursor = new Cursor(function() use ($records, &$pointer) {
            return @$records[$pointer++];
        });

        $array = iterator_to_array($cursor);
        $this->assertSame($records, $array);
    }
}