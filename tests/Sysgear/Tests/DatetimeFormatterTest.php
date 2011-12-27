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

use Sysgear\DatetimeFormatter;
use Sysgear\Datatype;

class DatetimeFormatterTest extends \PHPUnit_Framework_TestCase
{
    public function testCastRecords_date_defaultFormatting_to_zulu()
    {
        $formatter = new DatetimeFormatter();
        $formatter->srcTimezone = 'Europe/Amsterdam';
        $formatter->dstTimezone = 'Zulu';
        $records = array(
                array('abc', '0001-02-03', 123, null),
                array('d7a', '2011-09-29', 489, null),
                array('3d7', '2011-9-29', null, 3246),
                array('3d7', '', null, 3246),
                array('3d7', null, null, 3246)
        );

        // timzone data is not used for date
        $formatter->castRecords($records, array(1 => Datatype::DATE));
        $this->assertSame(array(
                array('abc', '0001-02-03', 123, null),
                array('d7a', '2011-09-29', 489, null),
                array('3d7', '2011-09-29', null, 3246),
                array('3d7', '', null, 3246),
                array('3d7', '', null, 3246)
        ), $records);
    }
    public function testCastRecords_date_customFormatting_to_zulu()
    {
        $formatter = new DatetimeFormatter();
        $formatter->srcTimezone = 'Europe/Amsterdam';
        $formatter->dstTimezone = 'Zulu';
        $records = array(
                array('abc', '0001-02-03', 123, null),
                array('d7a', '2011-09-29', 489, null),
                array('3d7', '2011-9-29', null, 3246)
        );

        // timzone data is not used for date
        $formatter->castRecords($records, array(1 => Datatype::DATE), null, array('date' => 'm-Y-d'));
        $this->assertSame(array(
                array('abc', '02-0001-03', 123, null),
                array('d7a', '09-2011-29', 489, null),
                array('3d7', '09-2011-29', null, 3246)
        ), $records);
    }

    public function testCastRecords_time_defaultFormatting_to_zulu()
    {
        $formatter = new DatetimeFormatter();
        $formatter->srcTimezone = 'Europe/Amsterdam';
        $formatter->dstTimezone = 'Zulu';
        $records = array(
                array('abc', '16:13:00', 123, null),
                array('d7a', '16:2:00', 489, null),
                array('3d7', '0:12:13', null, 3246)
        );

        $formatter->castRecords($records, array(1 => Datatype::TIME));
        $dst = ('1' === date('I'));

        if ($dst) {
            $this->assertSame(array(
                    array('abc', '14:13:00', 123, null),
                    array('d7a', '14:02:00', 489, null),
                    array('3d7', '22:12:13', null, 3246)
            ), $records);
        } else {
            $this->assertSame(array(
                    array('abc', '15:13:00', 123, null),
                    array('d7a', '15:02:00', 489, null),
                    array('3d7', '23:12:13', null, 3246)
            ), $records);
        }
    }

    public function testCastRecords_time_customFormatting_to_zulu()
    {
        $formatter = new DatetimeFormatter();
        $formatter->srcTimezone = 'Europe/Amsterdam';
        $formatter->dstTimezone = 'Zulu';
        $records = array(
                array('abc', '16:13:00', 123, null),
                array('d7a', '16:2:00', 489, null),
                array('3d7', '0:12:13', null, 3246)
        );

        $formatter->castRecords($records, array(1 => Datatype::TIME), null, array('time' => 'i:H:s'));
        $dst = ('1' === date('I'));

        if ($dst) {
            $this->assertSame(array(
                    array('abc', '14:13:00', 123, null),
                    array('d7a', '14:02:00', 489, null),
                    array('3d7', '22:12:13', null, 3246)
            ), $records);
        } else {
            $this->assertSame(array(
                    array('abc', '13:15:00', 123, null),
                    array('d7a', '02:15:00', 489, null),
                    array('3d7', '12:23:13', null, 3246)
            ), $records);
        }
    }

    public function testCastRecords_datetime_defaultFormatting_to_zulu()
    {
        $formatter = new DatetimeFormatter();
        $formatter->srcTimezone = 'Europe/Amsterdam';
        $formatter->dstTimezone = 'Zulu';
        $records = array(
                array('abc', '2011-01-1 16:13:00', 123, null),
                array('d7a', '2011-09-29 16:2:00', 489, null),
                array('3d7', '2011-9-01 0:12:13', null, 3246)
        );

        $formatter->castRecords($records, array(1 => Datatype::DATETIME));
        $dst = ('1' === date('I'));

        if ($dst) {
            $this->assertSame(array(
                    array('abc', '2011-01-01T16:13:00+00:00', 123, null),
                    array('d7a', '2011-09-29T16:02:00+00:00', 489, null),
                    array('3d7', '2011-08-31T23:12:13+00:00', null, 3246)
            ), $records);
        } else {
            $this->assertSame(array(
                    array('abc', '2011-01-01T15:13:00+00:00', 123, null),
                    array('d7a', '2011-09-29T14:02:00+00:00', 489, null),
                    array('3d7', '2011-08-31T22:12:13+00:00', null, 3246)
            ), $records);
        }
    }

    public function testCastRecords_datetime_customFormatting_to_zulu()
    {
        $formatter = new DatetimeFormatter();
        $formatter->srcTimezone = 'Europe/Amsterdam';
        $formatter->dstTimezone = 'Zulu';
        $records = array(
                array('abc', '2011-01-1 16:13:00', 123, null),
                array('d7a', '2011-09-29 16:2:00', 489, null),
                array('3d7', '2011-9-01 0:12:13', null, 3246)
        );

        $formatter->castRecords($records, array(1 => Datatype::DATETIME), null, array('datetime' => 'm-Y-d i:H:s'));
        $dst = ('1' === date('I'));

        if ($dst) {
            $this->assertSame(array(
                    array('abc', '01-2011-01 13:16:00', 123, null),
                    array('d7a', '09-2011-29 02:16:00', 489, null),
                    array('3d7', '08-2011-31 12:23:13', null, 3246)
            ), $records);
        } else {
            $this->assertSame(array(
                    array('abc', '01-2011-01 13:15:00', 123, null),
                    array('d7a', '09-2011-29 02:14:00', 489, null),
                    array('3d7', '08-2011-31 12:22:13', null, 3246)
            ), $records);
        }
    }
}