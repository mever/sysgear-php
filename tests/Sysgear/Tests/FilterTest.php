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

use Sysgear\Operator;
use Sysgear\Filter;

class FilterTest extends \PHPUnit_Framework_TestCase
{
    public function testCompileStringExp()
    {
        $filter = new Filter(array('F' => 'FIELD1', 'V' => 1234));
        $res = $filter->compileString($this->getCompiler());
        $this->assertEquals("'FIELD1' = 1234", $res);
    }

    public function testCompileStringExpWithOper()
    {
        $filter = new Filter(array('F' => 'FIELD1', 'V' => 1234, 'O' => Operator::STR_END_WITH));
        $res = $filter->compileString($this->getCompiler());
        $this->assertEquals("'FIELD1' ".Operator::STR_END_WITH." 1234", $res);
    }

    public function testCompileStringCol()
    {
        $filter = new Filter(array('C' => array(array('F' => 'FIELD1', 'V' => 'abc'))));
        $res = $filter->compileString($this->getCompiler());
        $this->assertEquals("('FIELD1' = abc)", $res);

        $filter = new Filter(array('C' => array(
            array('F' => 'FIELD1', 'V' => 'abc'), array('F' => 'FIELD2', 'V' => 123))));
        $res = $filter->compileString($this->getCompiler());
        $this->assertEquals("('FIELD1' = abc and 'FIELD2' = 123)", $res);

        $filter = new Filter(array('T' => 'or', 'C' => array(
            array('F' => 'FIELD1', 'V' => 'abc'), array('F' => 'FIELD2', 'V' => 123))));
        $res = $filter->compileString($this->getCompiler());
        $this->assertEquals("('FIELD1' = abc or 'FIELD2' = 123)", $res);
    }

    public function testCompileStringColNested()
    {
        $filter = new Filter(array('C' => array(
            array('F' => 'FIELD1', 'V' => 'abc'), array('F' => 'FIELD2', 'V' => 123),
            array('T' => 'or', 'C' => array(
                array('F' => 'FIELD3', 'V' => 6789, 'O' => Operator::NUM_LESS_THAN),
                array('F' => 'FIELD4', 'V' => 'test123', 'O' => Operator::STR_END_WITH))))));
        $res = $filter->compileString($this->getCompiler());
        
        $lessThan = Operator::NUM_LESS_THAN;
        $endWith = Operator::STR_END_WITH;
        $this->assertEquals("('FIELD1' = abc and 'FIELD2' = 123 and ('FIELD3' {$lessThan}".
        	" 6789 or 'FIELD4' {$endWith} test123))", $res);
    }

    public function getCompiler()
    {
        return function($type, array $filter) {
            if ($type === Filter::COLLECTION) {
                return array('(', array_key_exists('T', $filter) ? " {$filter['T']} " : ' and ', ')');
            } else {
                $oper = (array_key_exists('O', $filter) ? $filter['O'] : '=');
                return "'{$filter['F']}' {$oper} {$filter['V']}";
            }
        };
    }
}