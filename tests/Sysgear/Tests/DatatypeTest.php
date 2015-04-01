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

use Sysgear\Datatype;

class DatatypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Return if we're currently in DST.
     *
     * @param $timezone string
     * @return bool
     */
    public function isDst($timezone)
    {
        $date = new \DateTime('now', new \DateTimeZone($timezone));
        return ('1' === $date->format('I'));
    }

    /*
     * Test static method: toDesc
     */

    public function testToDesc()
    {
        $names = array('int', 'string', 'date', 'time', 'datetime', 'float', 'json', 'bool',
            'number', 'arr', 'xml', 'map', 'entity', 'password', 'email');

        for ($i=0; $i<count($names); $i++) {
            $this->assertSame($names[$i], Datatype::toDesc($i));
        }
    }



    /*
     * Test static method: ToDoctrineDbal
     */

    public function testToDoctrineDbal_int() {
        $this->assertSame('integer', Datatype::toDoctrineDbal(Datatype::INT));
    }

    public function testToDoctrineDbal_float() {
        $this->assertSame('float', Datatype::toDoctrineDbal(Datatype::FLOAT));
    }

    public function testToDoctrineDbal_number() {
        $this->assertSame('float', Datatype::toDoctrineDbal(Datatype::NUMBER));
    }

    public function testToDoctrineDbal_date() {
        $this->assertSame('date', Datatype::toDoctrineDbal(Datatype::DATE));
    }

    public function testToDoctrineDbal_time() {
        $this->assertSame('time', Datatype::toDoctrineDbal(Datatype::TIME));
    }

    public function testToDoctrineDbal_datetime() {
        $this->assertSame('datetime', Datatype::toDoctrineDbal(Datatype::DATETIME));
    }

    public function testToDoctrineDbal_default() {
        $this->assertSame('string', Datatype::toDoctrineDbal(-1));
    }



    /*
     * Test static method: toMysql
     */

    public function testToMysql_int() {
        $this->assertSame('INT', Datatype::toMysql(Datatype::INT));
    }

    public function testToMysql_number() {
        $this->assertSame('BIGINT', Datatype::toMysql(Datatype::NUMBER));
    }

    public function testToMysql_string()
    {
        $this->assertSame('VARCHAR(255)', Datatype::toMysql(Datatype::STRING));
        $this->assertSame('VARCHAR(80)', Datatype::toMysql(Datatype::STRING, 80));
    }

    public function testToMysql_default() {
        $this->assertSame('TEXT', Datatype::toMysql(-1));
    }



    /*
     * Test static method: getPrintableString
     */

    public function testGetPrintableString_json()
    {
        $obj2 = new \stdClass();
        $obj2->b = 'a';
        $obj2->abc = null;

        $obj = new \stdClass();
        $obj->a = 1;
        $obj->c = array(1, 'abc', true, '\n');
        $obj->obj = $obj2;

        $this->assertSame('{"a":1,"c":[1,"abc",true,"\\\\n"],"obj":{"b":"a","abc":null}}',
            Datatype::getPrintableString(Datatype::JSON, $obj));
    }

    public function testGetPrintableString_bool()
    {
        $this->assertSame('true', Datatype::getPrintableString(Datatype::BOOL, 1));
        $this->assertSame('true', Datatype::getPrintableString(Datatype::BOOL, 'true'));
        $this->assertSame('true', Datatype::getPrintableString(Datatype::BOOL, 'random string'));
        $this->assertSame('true', Datatype::getPrintableString(Datatype::BOOL, true));

        $this->assertSame('false', Datatype::getPrintableString(Datatype::BOOL, 0));
        $this->assertSame('false', Datatype::getPrintableString(Datatype::BOOL, 'false'));
        $this->assertSame('false', Datatype::getPrintableString(Datatype::BOOL, ''));
        $this->assertSame('false', Datatype::getPrintableString(Datatype::BOOL, false));
        $this->assertSame('false', Datatype::getPrintableString(Datatype::BOOL, null));
    }

    public function testGetPrintableString_default()
    {
        $this->assertSame('1', Datatype::getPrintableString(-1, 1));
        $this->assertSame('"true"', Datatype::getPrintableString(-1, 'true'));
        $this->assertSame('"random string"', Datatype::getPrintableString(-1, 'random string'));
        $this->assertSame('true', Datatype::getPrintableString(-1, true));
        $this->assertSame('0', Datatype::getPrintableString(-1, 0));
        $this->assertSame('"false"', Datatype::getPrintableString(-1, 'false'));
        $this->assertSame('""', Datatype::getPrintableString(-1, ''));
        $this->assertSame('false', Datatype::getPrintableString(-1, false));
        $this->assertSame('null', Datatype::getPrintableString(-1, null));
    }



    /*
     * Test static method: typecastSet
     */

    public function testTypecastSet_json()
    {
        $obj2 = new \stdClass();
        $obj2->b = 'a';
        $obj2->abc = null;

        $obj = new \stdClass();
        $obj->a = 1;
        $obj->c = array(1, 'abc', true, '\n');
        $obj->obj = $obj2;

        $this->assertSame('{"a":1,"c":[1,"abc",true,"\\\\n"],"obj":{"b":"a","abc":null}}',
            Datatype::typecastSet(Datatype::JSON, $obj));
    }

    public function testTypecastSet_map()
    {
        $obj = new \stdClass();
        $obj->a = 1;
        $obj->b = 'a';
        $obj->abc = null;

        $this->assertSame('{"a":1,"b":"a","abc":null}',
            Datatype::typecastSet(Datatype::MAP, $obj));
    }

    public function testTypecastSet_bool()
    {
        $this->assertSame(1, Datatype::typecastSet(Datatype::BOOL, 1));
        $this->assertSame(1, Datatype::typecastSet(Datatype::BOOL, 'true'));
        $this->assertSame(1, Datatype::typecastSet(Datatype::BOOL, 'random string'));
        $this->assertSame(1, Datatype::typecastSet(Datatype::BOOL, true));

        $this->assertSame(0, Datatype::typecastSet(Datatype::BOOL, 0));
        $this->assertSame(0, Datatype::typecastSet(Datatype::BOOL, 'false'));
        $this->assertSame(0, Datatype::typecastSet(Datatype::BOOL, ''));
        $this->assertSame(0, Datatype::typecastSet(Datatype::BOOL, false));
        $this->assertSame(0, Datatype::typecastSet(Datatype::BOOL, null));
    }



    /*
     * Test static method: typecastGet
     */

    public function testTypecastGet_json()
    {
        $obj2 = new \stdClass();
        $obj2->b = 'a';
        $obj2->abc = null;

        $obj = new \stdClass();
        $obj->a = 1;
        $obj->c = array(1, 'abc', true, '\n');
        $obj->obj = $obj2;

        $this->assertEquals($obj, Datatype::typecastGet(Datatype::JSON,
        	'{"a":1,"c":[1,"abc",true,"\\\\n"],"obj":{"b":"a","abc":null}}'));
    }

    public function testTypecastGet_map() {
        $arr = array('a' => 1, 'b' => 'a', 'abc' => null);
        $this->assertSame($arr, Datatype::typecastGet(Datatype::MAP,
            '{"a":1,"b":"a","abc":null}'));
    }

    public function testTypecastGet_arr() {
        $arr = array('a', 1, 4, null, true, false, 'blaat');
        $this->assertSame($arr, Datatype::typecastGet(Datatype::ARR,
            '["a", 1, 4, null, true, false, "blaat"]'));
    }

    public function testTypecastGet_string() {
        $this->assertSame('1', Datatype::typecastGet(Datatype::STRING, 1));
        $this->assertSame('1', Datatype::typecastGet(Datatype::STRING, true));
        $this->assertSame('', Datatype::typecastGet(Datatype::STRING, false));
        $this->assertSame('0', Datatype::typecastGet(Datatype::STRING, 0));
        $this->assertSame('abc', Datatype::typecastGet(Datatype::STRING, 'abc'));
    }

    public function testTypecastGet_int() {
        $this->assertSame(1234, Datatype::typecastGet(Datatype::INT, '1234'));
        $this->assertSame(1234, Datatype::typecastGet(Datatype::INT, '1234.32'));
        $this->assertSame(0, Datatype::typecastGet(Datatype::INT, ''));
    }

    public function testTypecastGet_float() {
        $this->assertSame(1234.32, Datatype::typecastGet(Datatype::FLOAT, '1234.32'));
        $this->assertSame(0.0, Datatype::typecastGet(Datatype::FLOAT, ''));
    }

    public function testTypecastGet_number() {
        $this->assertSame(1234.32, Datatype::typecastGet(Datatype::NUMBER, '1234.32'));
        $this->assertSame(0.0, Datatype::typecastGet(Datatype::NUMBER, ''));
    }

    public function testTypecastGet_bool()
    {
        // y and true are case insensitive
        $this->assertSame(true, Datatype::typecastGet(Datatype::BOOL, '1'));
        $this->assertSame(true, Datatype::typecastGet(Datatype::BOOL, 'y'));
        $this->assertSame(true, Datatype::typecastGet(Datatype::BOOL, 'trUe'));
        $this->assertSame(true, Datatype::typecastGet(Datatype::BOOL, 'random string'));

        // n and false are case insensitive
        $this->assertSame(false, Datatype::typecastGet(Datatype::BOOL, '0'));
        $this->assertSame(false, Datatype::typecastGet(Datatype::BOOL, 'n'));
        $this->assertSame(false, Datatype::typecastGet(Datatype::BOOL, 'N'));
        $this->assertSame(false, Datatype::typecastGet(Datatype::BOOL, 'fAlse'));
        $this->assertSame(false, Datatype::typecastGet(Datatype::BOOL, ''));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testTypecastGet_unknown() {
        $this->assertNull(Datatype::typecastGet(999, 'test'));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testTypecastGet_nullDatatype() {
        $this->assertNull(Datatype::typecastGet(null, 'test'));
    }



    /*
     * Test static method: castDatesInRecords
     */

    public function testCastDatesInRecords_empty()
    {
        $records = array(
            array('abc', '0001-02-03', 123, null),
            array('d7a', '', 489, null),
            array('3d7', null, null, 3246)
        );

        Datatype::castDatesInRecords('Europe/Amsterdam', $records, array(1 => Datatype::DATE));
        $this->assertSame(array(
            array('abc', '0001-02-03', 123, null),
            array('d7a', null, 489, null),
            array('3d7', null, null, 3246)
        ), $records);
    }

    public function testCastDatesInRecords_date()
    {
        $records = array(
            array('abc', '0001-02-03', 123, null),
            array('d7a', '2011-09-29', 489, null),
            array('3d7', '2011-9-29', null, 3246)
        );

        // timzone data is not used for date
        Datatype::castDatesInRecords('Europe/Amsterdam', $records, array(1 => Datatype::DATE));
        $this->assertSame(array(
            array('abc', '0001-02-03', 123, null),
            array('d7a', '2011-09-29', 489, null),
            array('3d7', '2011-09-29', null, 3246)
        ), $records);
    }

    public function testCastDatesInRecords_time()
    {
        $records = array(
            array('abc', '16:13:00', 123, null),
            array('d7a', '16:2:00', 489, null),
            array('3d7', '0:12:13', null, 3246)
        );

        Datatype::castDatesInRecords('Europe/Amsterdam', $records, array(1 => Datatype::TIME));

        if ($this->isDst('Europe/Amsterdam')) {
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

    public function testCastDatesInRecords_datetime()
    {
        $records = array(
            array('abc', '2011-01-1 16:13:00', 123, null),
            array('d7a', '2011-09-29 16:2:00', 489, null),
            array('3d7', '2011-9-01 0:12:13', null, 3246)
        );

        Datatype::castDatesInRecords('Europe/Amsterdam', $records, array(1 => Datatype::DATETIME));
        $this->assertSame(array(
            array('abc', '2011-01-01T15:13:00+00:00', 123, null),
            array('d7a', '2011-09-29T14:02:00+00:00', 489, null),
            array('3d7', '2011-08-31T22:12:13+00:00', null, 3246)
        ), $records);
    }



    /*
     * Test static method: castDate
     */

    public function testCastDate_empty()
    {
        $this->assertSame('0001-02-03', Datatype::castDate(-1, Datatype::DATE, '0001-02-03')->format('Y-m-d'));
        $this->assertSame(null, Datatype::castDate(-1, Datatype::DATE, ''));
        $this->assertSame(null, Datatype::castDate(-1, Datatype::DATE, null));
    }

    public function testCastDate_date()
    {
        // timzone data is not used for date
        $this->assertSame('0001-02-03', Datatype::castDate(-1, Datatype::DATE, '0001-02-03')->format('Y-m-d'));
        $this->assertSame('2011-09-29', Datatype::castDate(-1, Datatype::DATE, '2011-09-29')->format('Y-m-d'));
        $this->assertSame('2011-09-29', Datatype::castDate(-1, Datatype::DATE, '2011-9-29')->format('Y-m-d'));
    }

    public function testCastDate_time()
    {
        $time = Datatype::castDate('Europe/Amsterdam', Datatype::TIME, '16:13:00')->format('H:i:s');
        if ('15:13:00' !== $time) {

            // when DST is aplicable
            $this->assertSame('14:13:00', $time);
        }
    }

    public function testCastDate_datetime()
    {
        $date = Datatype::castDate('Europe/Amsterdam', Datatype::DATETIME, '2011-01-01 16:13:00');
        $this->assertSame('15:13:00', $date->format('H:i:s'));

        $date = Datatype::castDate('Europe/Amsterdam', Datatype::DATETIME, '2011-09-01 16:13:00');
        $this->assertSame('14:13:00', $date->format('H:i:s'));
    }



    /*
     * Test static method: isDate
     */

    public function testIsDate()
    {
        $refClass = new \ReflectionClass('Sysgear\Datatype');
        $constants = $refClass->getConstants();
        $asserts = array_fill(0, count($constants), false);

        $asserts[Datatype::DATE] = true;
        $asserts[Datatype::TIME] = true;
        $asserts[Datatype::DATETIME] = true;

        $idx = 0;
        foreach ($constants as $code) {
            $this->assertSame($asserts[$idx++], Datatype::isDate($code));
        }

        $this->assertFalse(Datatype::isDate(null));
    }



    /*
     * Test static method: isNumber
     */

    public function testIsNumber()
    {
        $refClass = new \ReflectionClass('Sysgear\Datatype');
        $constants = $refClass->getConstants();
        $asserts = array_fill(0, count($constants), false);

        $asserts[Datatype::INT] = true;
        $asserts[Datatype::FLOAT] = true;
        $asserts[Datatype::NUMBER] = true;

        $idx = 0;
        foreach ($constants as $code) {
            $this->assertSame($asserts[$idx++], Datatype::isNumber($code));
        }

        $this->assertFalse(Datatype::isNumber(null));
    }
}