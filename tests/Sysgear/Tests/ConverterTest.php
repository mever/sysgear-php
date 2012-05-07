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

class ConverterTest extends \PHPUnit_Framework_TestCase
{
    public function testContructor_default_timezones()
    {
        $converter = new Converter();

        $ref = '03-05-2012 15:16:17';
        $converter->formatValue($ref, Datatype::DATETIME);
        $this->assertEquals('2012-05-03T17:16:17+02:00', $ref);
    }

    public function testProcessRecord()
    {
        $record = array('abc', '123', '03-05-2012 15:16:17');
        $types = array(Datatype::STRING, Datatype::INT, Datatype::DATETIME);

        $converter = new Converter();
        $converter->processRecord($record, $types);

        $timezone = new \DateTimeZone('Zulu');
        $expectedRecord = array('abc', 123, new \DateTime('03-05-2012 15:16:17', $timezone));
        $this->assertEquals($expectedRecord, $record);
    }

    public function testProcessRecord_otherTimezone()
    {
        $record = array('abc', '123', '03-05-2012 15:16:17');
        $types = array(Datatype::STRING, Datatype::INT, Datatype::DATETIME);

        $timezone = new \DateTimeZone('Europe/Amsterdam');
        $converter = new Converter();
        $converter->setTimezoneSrc($timezone);
        $converter->processRecord($record, $types);

        $expectedRecord = array('abc', 123, new \DateTime('03-05-2012 15:16:17', $timezone));
        $this->assertEquals($expectedRecord, $record);
    }

    public function testFormatValue_datetimeObj_as_datetime()
    {
        $converter = new Converter();
        $converter->setTimezoneDest(new \DateTimeZone('America/Los_Angeles'));

        $value = new \DateTime('2012-05-07 05:28:00', new \DateTimeZone('Zulu'));
        $converter->formatValue($value, Datatype::DATETIME);
        $this->assertEquals('2012-05-06T22:28:00-07:00', $value);
    }

    public function testFormatValue_datetimeObj_as_date()
    {
        $converter = new Converter();
        $converter->setTimezoneDest(new \DateTimeZone('America/Los_Angeles'));

        $value = new \DateTime('2012-05-07 05:28:00', new \DateTimeZone('Zulu'));
        $converter->formatValue($value, Datatype::DATE);
        $this->assertEquals('2012-05-07', $value);
    }

    public function testFormatValue_datetimeObj_as_time()
    {
        $converter = new Converter();
        $converter->setTimezoneDest(new \DateTimeZone('America/Los_Angeles'));

        $value = new \DateTime('2012-05-07 05:28:00', new \DateTimeZone('Zulu'));
        $converter->formatValue($value, Datatype::TIME);
        $this->assertEquals('05:28:00', $value);
    }

    public function testFormatRecords_datetime_as_datetime()
    {
        $converter = new Converter();
        $converter->setTimezoneSrc(new \DateTimeZone('Europe/Amsterdam'));
        $converter->setTimezoneDest(new \DateTimeZone('Zulu'));
        $records = array(
                array('abc', '2011-01-1 16:13:00', 123, null),
                array('d7a', '2011-09-29 16:2:00', 489, null),
                array('3d7', '2011-9-01 0:12:13', null, 3246),
                array('d7a', '', 489, null),
                array('3d7', null, null, 3246)
        );

        $converter->formatRecords($records, array(1 => Datatype::DATETIME));
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
        $converter = new Converter();
        $converter->setTimezoneSrc(new \DateTimeZone('Europe/Amsterdam'));
        $converter->setTimezoneDest(new \DateTimeZone('Zulu'));
        $records = array(
                array('abc', '2011-01-1 16:13:00', 123, null),
                array('d7a', '2011-09-29 16:2:00', 489, null),
                array('3d7', '2011-9-01 0:12:13', null, 3246),
                array('d7a', '', 489, null),
                array('3d7', null, null, 3246)
        );

        $converter->formatRecords($records, array(1 => Datatype::DATE));
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
        $converter = new Converter();
        $converter->setTimezoneSrc(new \DateTimeZone('Europe/Amsterdam'));
        $converter->setTimezoneDest(new \DateTimeZone('Zulu'));
        $records = array(
                array('abc', '2011-01-1 16:13:00', 123, null),
                array('d7a', '2011-09-29 16:2:00', 489, null),
                array('3d7', '2011-9-01 0:12:13', null, 3246),
                array('d7a', '', 489, null),
                array('3d7', null, null, 3246)
        );

        $converter->formatRecords($records, array(1 => Datatype::TIME));
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
        $converter = new Converter();
        $converter->setTimezoneSrc(new \DateTimeZone('Europe/Amsterdam'));
        $converter->setTimezoneDest(new \DateTimeZone('Zulu'));
        $records = array(
                array('abc', '0001-02-03', 123, null),
                array('d7a', '2011-09-29', 489, null),
                array('3d7', '2011-09-29', null, 3246),
                array('3d7', '', null, 3246),
                array('3d7', null, null, 3246)
        );

        // timzone data is not used for date
        $converter->formatRecords($records, array(1 => Datatype::DATETIME));
    }

    public function testFormatRecords_date_as_date()
    {
        $converter = new Converter();
        $converter->setTimezoneSrc(new \DateTimeZone('Europe/Amsterdam'));
        $converter->setTimezoneDest(new \DateTimeZone('Zulu'));
        $records = array(
        array('abc', '0001-02-03', 123, null),
        array('d7a', '2011-09-29', 489, null),
        array('3d7', '2011-09-29', null, 3246),
        array('3d7', '', null, 3246),
        array('3d7', null, null, 3246)
        );

        // timzone data is not used for date
        $converter->formatRecords($records, array(1 => Datatype::DATE));
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
        $converter = new Converter();
        $converter->setTimezoneSrc(new \DateTimeZone('Europe/Amsterdam'));
        $converter->setTimezoneDest(new \DateTimeZone('Zulu'));
        $records = array(
                array('abc', '0001-02-03', 123, null),
                array('d7a', '2011-09-29', 489, null),
                array('3d7', '2011-09-29', null, 3246),
                array('3d7', '', null, 3246),
                array('3d7', null, null, 3246)
        );

        // timzone data is not used for date
        $converter->formatRecords($records, array(1 => Datatype::TIME));
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Trying to format, something that looks like a time, as datetime
     */
    public function testFormatRecords_time_as_datetime()
    {
        $converter = new Converter();
        $converter->setTimezoneSrc(new \DateTimeZone('Europe/Amsterdam'));
        $converter->setTimezoneDest(new \DateTimeZone('Zulu'));
        $records = array(
                array('abc', '23:13:00', 123, null),
                array('d7a', '16:21:46', 489, null),
                array('3d7', '01:12', null, 3246),
                array('d7a', '', 489, null),
                array('3d7', null, null, 3246)
        );

        $converter->formatRecords($records, array(1 => Datatype::DATETIME));
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Trying to format, something that looks like a time, as date
     */
    public function testFormatRecords_time_as_date()
    {
        $converter = new Converter();
        $converter->setTimezoneSrc(new \DateTimeZone('Europe/Amsterdam'));
        $converter->setTimezoneDest(new \DateTimeZone('Zulu'));
        $records = array(
                array('abc', '23:13:00', 123, null),
                array('d7a', '16:21:46', 489, null),
                array('3d7', '01:12', null, 3246),
                array('d7a', '', 489, null),
                array('3d7', null, null, 3246)
        );

        $converter->formatRecords($records, array(1 => Datatype::DATE));
    }

    public function testFormatRecords_time_as_time()
    {
        $converter = new Converter();
        $converter->setTimezoneSrc(new \DateTimeZone('Europe/Amsterdam'));
        $converter->setTimezoneDest(new \DateTimeZone('Zulu'));
        $records = array(
                array('abc', '23:13:00', 123, null),
                array('d7a', '16:21:46', 489, null),
                array('3d7', '01:12', null, 3246),
                array('d7a', '', 489, null),
                array('3d7', null, null, 3246)
        );

        $converter->formatRecords($records, array(1 => Datatype::TIME));
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
        $converter = new Converter();
        $converter->setTimezoneSrc(new \DateTimeZone('Europe/Amsterdam'));
        $converter->setTimezoneDest(new \DateTimeZone('Zulu'));

        $date = new \DateTime('2012-05-03 00:00:00');
        $str = $converter->formatDate($date, Datatype::DATETIME);
        $this->assertEquals('2012-05-02T22:00:00+00:00', $str);
    }

    public function testFormatDate_datetime_format()
    {
        $converter = new Converter();
        $converter->setTimezoneSrc(new \DateTimeZone('Europe/Amsterdam'));
        $converter->setTimezoneDest(new \DateTimeZone('Zulu'));
        $converter->formatDatetime = 'm#d$Y iHs';

        $date = new \DateTime('2012-05-03 00:00:00');
        $str = $converter->formatDate($date, Datatype::DATETIME);
        $this->assertEquals('05#02$2012 002200', $str);
    }

    public function testFormatDate_date()
    {
        $converter = new Converter();
        $converter->setTimezoneSrc(new \DateTimeZone('Europe/Amsterdam'));
        $converter->setTimezoneDest(new \DateTimeZone('Zulu'));

        $date = new \DateTime('2012-05-03 00:00:00');
        $str = $converter->formatDate($date, Datatype::DATE);
        $this->assertEquals('2012-05-02', $str);    // changing the timezone is cause this
    }

    public function testFormatDate_date_format()
    {
        $converter = new Converter();
        $converter->setTimezoneSrc(new \DateTimeZone('Europe/Amsterdam'));
        $converter->setTimezoneDest(new \DateTimeZone('Zulu'));
        $converter->formatDate = 'm#d$Y';

        $date = new \DateTime('2012-05-03 00:00:00');
        $str = $converter->formatDate($date, Datatype::DATE);
        $this->assertEquals('05#02$2012', $str);    // changing the timezone is cause this
    }

    public function testFormatDate_time()
    {
        $converter = new Converter();
        $converter->setTimezoneSrc(new \DateTimeZone('Europe/Amsterdam'));
        $converter->setTimezoneDest(new \DateTimeZone('Zulu'));

        $date = new \DateTime('2012-05-03 00:00:00');
        $str = $converter->formatDate($date, Datatype::TIME);
        $this->assertEquals('22:00:00', $str);
    }

    public function testFormatDate_time_format()
    {
        $converter = new Converter();
        $converter->setTimezoneSrc(new \DateTimeZone('Europe/Amsterdam'));
        $converter->setTimezoneDest(new \DateTimeZone('Zulu'));
        $converter->formatTime = 'iHs';

        $date = new \DateTime('2012-05-03 00:00:00');
        $str = $converter->formatDate($date, Datatype::TIME);
        $this->assertEquals('002200', $str);
    }
}