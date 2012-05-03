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

use Sysgear\Formatter;
use Sysgear\Datatype;

class FormatterTest extends \PHPUnit_Framework_TestCase
{
    public function testFormatRecords_datetime_as_datetime()
    {
        $formatter = new Formatter();
        $formatter->srcTimezone = 'Europe/Amsterdam';
        $formatter->dstTimezone = 'Zulu';
        $records = array(
                array('abc', '2011-01-1 16:13:00', 123, null),
                array('d7a', '2011-09-29 16:2:00', 489, null),
                array('3d7', '2011-9-01 0:12:13', null, 3246),
                array('d7a', '', 489, null),
                array('3d7', null, null, 3246)
        );

        $formatter->formatRecords($records, array(1 => Datatype::DATETIME));
        $this->assertSame(array(
            array('abc', '2011-01-01T15:13:00+00:00', 123, null),
            array('d7a', '2011-09-29T14:02:00+00:00', 489, null),
            array('3d7', '2011-08-31T22:12:13+00:00', null, 3246),
            array('d7a', null, 489, null),
            array('3d7', null, null, 3246)
        ), $records);
    }

    public function testFormatRecords_datetime_as_date()
    {
        $formatter = new Formatter();
        $formatter->srcTimezone = 'Europe/Amsterdam';
        $formatter->dstTimezone = 'Zulu';
        $records = array(
                array('abc', '2011-01-1 16:13:00', 123, null),
                array('d7a', '2011-09-29 16:2:00', 489, null),
                array('3d7', '2011-9-01 0:12:13', null, 3246),
                array('d7a', '', 489, null),
                array('3d7', null, null, 3246)
        );

        $formatter->formatRecords($records, array(1 => Datatype::DATE));
        $this->assertSame(array(
            array('abc', '2011-01-01', 123, null),
            array('d7a', '2011-09-29', 489, null),
            array('3d7', '2011-09-01', null, 3246),
            array('d7a', null, 489, null),
            array('3d7', null, null, 3246)
        ), $records);
    }

    public function testFormatRecords_datetime_as_time()
    {
        $formatter = new Formatter();
        $formatter->srcTimezone = 'Europe/Amsterdam';
        $formatter->dstTimezone = 'Zulu';
        $records = array(
                array('abc', '2011-01-1 16:13:00', 123, null),
                array('d7a', '2011-09-29 16:2:00', 489, null),
                array('3d7', '2011-9-01 0:12:13', null, 3246),
                array('d7a', '', 489, null),
                array('3d7', null, null, 3246)
        );

        $formatter->formatRecords($records, array(1 => Datatype::TIME));
        $this->assertSame(array(
            array('abc', '15:13:00', 123, null),
            array('d7a', '14:02:00', 489, null),
            array('3d7', '22:12:13', null, 3246),
            array('d7a', null, 489, null),
            array('3d7', null, null, 3246)
        ), $records);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Trying to format, something that looks like a date, as datetime
     */
    public function testFormatRecords_date_as_datetime()
    {
        $formatter = new Formatter();
        $formatter->srcTimezone = 'Europe/Amsterdam';
        $formatter->dstTimezone = 'Zulu';
        $records = array(
                array('abc', '0001-02-03', 123, null),
                array('d7a', '2011-09-29', 489, null),
                array('3d7', '2011-09-29', null, 3246),
                array('3d7', '', null, 3246),
                array('3d7', null, null, 3246)
        );

        // timzone data is not used for date
        $formatter->formatRecords($records, array(1 => Datatype::DATETIME));
    }

    public function testFormatRecords_date_as_date()
    {
        $formatter = new Formatter();
        $formatter->srcTimezone = 'Europe/Amsterdam';
        $formatter->dstTimezone = 'Zulu';
        $records = array(
        array('abc', '0001-02-03', 123, null),
        array('d7a', '2011-09-29', 489, null),
        array('3d7', '2011-09-29', null, 3246),
        array('3d7', '', null, 3246),
        array('3d7', null, null, 3246)
        );

        // timzone data is not used for date
        $formatter->formatRecords($records, array(1 => Datatype::DATE));
        $this->assertSame(array(
        array('abc', '0001-02-03', 123, null),
        array('d7a', '2011-09-29', 489, null),
        array('3d7', '2011-09-29', null, 3246),
        array('3d7', null, null, 3246),
        array('3d7', null, null, 3246)
        ), $records);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Trying to format, something that looks like a date, as time
     */
    public function testFormatRecords_date_as_time()
    {
        $formatter = new Formatter();
        $formatter->srcTimezone = 'Europe/Amsterdam';
        $formatter->dstTimezone = 'Zulu';
        $records = array(
                array('abc', '0001-02-03', 123, null),
                array('d7a', '2011-09-29', 489, null),
                array('3d7', '2011-09-29', null, 3246),
                array('3d7', '', null, 3246),
                array('3d7', null, null, 3246)
        );

        // timzone data is not used for date
        $formatter->formatRecords($records, array(1 => Datatype::TIME));
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Trying to format, something that looks like a time, as datetime
     */
    public function testFormatRecords_time_as_datetime()
    {
        $formatter = new Formatter();
        $formatter->srcTimezone = 'Europe/Amsterdam';
        $formatter->dstTimezone = 'Zulu';
        $records = array(
                array('abc', '23:13:00', 123, null),
                array('d7a', '16:21:46', 489, null),
                array('3d7', '01:12', null, 3246),
                array('d7a', '', 489, null),
                array('3d7', null, null, 3246)
        );

        $formatter->formatRecords($records, array(1 => Datatype::DATETIME));
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Trying to format, something that looks like a time, as date
     */
    public function testFormatRecords_time_as_date()
    {
        $formatter = new Formatter();
        $formatter->srcTimezone = 'Europe/Amsterdam';
        $formatter->dstTimezone = 'Zulu';
        $records = array(
                array('abc', '23:13:00', 123, null),
                array('d7a', '16:21:46', 489, null),
                array('3d7', '01:12', null, 3246),
                array('d7a', '', 489, null),
                array('3d7', null, null, 3246)
        );

        $formatter->formatRecords($records, array(1 => Datatype::DATE));
    }

    public function testFormatRecords_time_as_time()
    {
        $formatter = new Formatter();
        $formatter->srcTimezone = 'Europe/Amsterdam';
        $formatter->dstTimezone = 'Zulu';
        $records = array(
                array('abc', '23:13:00', 123, null),
                array('d7a', '16:21:46', 489, null),
                array('3d7', '01:12', null, 3246),
                array('d7a', '', 489, null),
                array('3d7', null, null, 3246)
        );

        $formatter->formatRecords($records, array(1 => Datatype::TIME));
        $this->assertSame(array(
                array('abc', '23:13:00', 123, null),
                array('d7a', '16:21:46', 489, null),
                array('3d7', '01:12:00', null, 3246),
                array('d7a', null, 489, null),
                array('3d7', null, null, 3246)
        ), $records);
    }

    public function testFormatDate_datetime()
    {
        $formatter = new Formatter();
        $formatter->srcTimezone = 'Europe/Amsterdam';
        $formatter->dstTimezone = 'Zulu';

        $date = new \DateTime('2012-05-03 00:00:00');
        $str = $formatter->formatDate($date, Datatype::DATETIME);
        $this->assertEquals('2012-05-02T22:00:00+00:00', $str);
    }

    public function testFormatDate_datetime_format()
    {
        $formatter = new Formatter();
        $formatter->srcTimezone = 'Europe/Amsterdam';
        $formatter->dstTimezone = 'Zulu';
        $formatter->formatDatetime = 'm#d$Y iHs';

        $date = new \DateTime('2012-05-03 00:00:00');
        $str = $formatter->formatDate($date, Datatype::DATETIME);
        $this->assertEquals('05#02$2012 002200', $str);
    }

    public function testFormatDate_date()
    {
        $formatter = new Formatter();
        $formatter->srcTimezone = 'Europe/Amsterdam';
        $formatter->dstTimezone = 'Zulu';

        $date = new \DateTime('2012-05-03 00:00:00');
        $str = $formatter->formatDate($date, Datatype::DATE);
        $this->assertEquals('2012-05-02', $str);    // changing the timezone is cause this
    }

    public function testFormatDate_date_format()
    {
        $formatter = new Formatter();
        $formatter->srcTimezone = 'Europe/Amsterdam';
        $formatter->dstTimezone = 'Zulu';
        $formatter->formatDate = 'm#d$Y';

        $date = new \DateTime('2012-05-03 00:00:00');
        $str = $formatter->formatDate($date, Datatype::DATE);
        $this->assertEquals('05#02$2012', $str);    // changing the timezone is cause this
    }

    public function testFormatDate_time()
    {
        $formatter = new Formatter();
        $formatter->srcTimezone = 'Europe/Amsterdam';
        $formatter->dstTimezone = 'Zulu';

        $date = new \DateTime('2012-05-03 00:00:00');
        $str = $formatter->formatDate($date, Datatype::TIME);
        $this->assertEquals('22:00:00', $str);
    }

    public function testFormatDate_time_format()
    {
        $formatter = new Formatter();
        $formatter->srcTimezone = 'Europe/Amsterdam';
        $formatter->dstTimezone = 'Zulu';
        $formatter->formatTime = 'iHs';

        $date = new \DateTime('2012-05-03 00:00:00');
        $str = $formatter->formatDate($date, Datatype::TIME);
        $this->assertEquals('002200', $str);
    }
}