<?php

/*
 * This file is part of the Sysgear package.
 *
 * (c) Martijn Evers <martijn4evers@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sysgear\Tests\Filter;

use Sysgear\Operator;
use Sysgear\Filter\Collection;
use Sysgear\Filter\Expression;

class CollectionTest extends \PHPUnit_Framework_TestCase
{
    public function testCompileStringExp()
    {
        $filter = new Expression('FIELD1', 1234);
        $res = $filter->compileString($this->getCompiler());
        $this->assertEquals("'FIELD1' = 1234", $res);
    }

    public function testCompileStringExpWithOper()
    {
        $filter = new Expression('FIELD1', 1234, Operator::STR_END_WITH);
        $res = $filter->compileString($this->getCompiler());
        $this->assertEquals("'FIELD1' ".Operator::STR_END_WITH." 1234", $res);
    }

    public function testCompileStringCollection()
    {
        $filter = new Collection(array(
            new Expression('FIELD1', 'abc'),
            new Expression('FIELD2', 123)
        ));
        $res = $filter->compileString($this->getCompiler());
        $this->assertEquals("('FIELD1' = abc and 'FIELD2' = 123)", $res);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage First argument must be "and" or "or"
     */
    public function testCompileStringCollectionException()
    {
        $filter = new Collection(array(), 'unknown');
    }

    public function testCompileStringCollectionAnd()
    {
        $filter = new Collection(array(
            new Expression('FIELD1', 'abc'),
            new Expression('FIELD2', 123)
        ), 'and');
        $res = $filter->compileString($this->getCompiler());
        $this->assertEquals("('FIELD1' = abc and 'FIELD2' = 123)", $res);
    }

    public function testCompileStringCollectionOr()
    {
        $filter = new Collection(array(
            new Expression('FIELD1', 'abc'),
            new Expression('FIELD2', 123)
        ), 'or');
        $res = $filter->compileString($this->getCompiler());
        $this->assertEquals("('FIELD1' = abc or 'FIELD2' = 123)", $res);
    }

    public function testCompileStringColNested()
    {
        $filter = new Collection(array(
            new Expression('FIELD1', 'abc'),
            new Expression('FIELD2', 123),
            new Collection(array(
                new Expression('FIELD3', 6789, Operator::NUM_LESS_THAN),
                new Expression('FIELD4', 'test123', Operator::STR_END_WITH)
            ), 'or')
        ));
        $res = $filter->compileString($this->getCompiler());

        $lessThan = Operator::NUM_LESS_THAN;
        $endWith = Operator::STR_END_WITH;
        $this->assertEquals("('FIELD1' = abc and 'FIELD2' = 123 and ('FIELD3' {$lessThan}".
          " 6789 or 'FIELD4' {$endWith} test123))", $res);
    }

    public function getCompiler()
    {
        return function($type, $filter) {
            if ($type === Collection::COMPILE_COL) {
                return array('(', " {$filter->getType()} ", ')');
            } else {
                $oper = $filter->getOperator();
                if (Operator::EQUAL === $oper) {
                    $oper = '=';
                }
                return "'{$filter->getField()}' {$oper} {$filter->getValue()}";
            }
        };
    }
}