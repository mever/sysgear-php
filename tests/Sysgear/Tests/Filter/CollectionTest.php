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
use Sysgear\Filter\Filter;
use Sysgear\Filter\Collection;
use Sysgear\Filter\Expression;

class CollectionTest extends \PHPUnit_Framework_TestCase
{
    public function testCompileString_exp()
    {
        $filter = new Expression('FIELD1', 1234);
        $res = $filter->compileString($this->getCompiler());
        $this->assertEquals("'FIELD1' = 1234", $res);
    }

    public function testCompileString_expWithOper()
    {
        $filter = new Expression('FIELD1', 1234, Operator::STR_END_WITH);
        $res = $filter->compileString($this->getCompiler());
        $this->assertEquals("'FIELD1' ".Operator::STR_END_WITH." 1234", $res);
    }

    public function testCompileString_collection()
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
    public function testCompileString_collectionException()
    {
        $filter = new Collection(array(), 'unknown');
    }

    public function testCompileString_collectionAnd()
    {
        $filter = new Collection(array(
            new Expression('FIELD1', 'abc'),
            new Expression('FIELD2', 123)
        ), 'and');
        $res = $filter->compileString($this->getCompiler());
        $this->assertEquals("('FIELD1' = abc and 'FIELD2' = 123)", $res);
    }

    public function testCompileString_collectionOr()
    {
        $filter = new Collection(array(
            new Expression('FIELD1', 'abc'),
            new Expression('FIELD2', 123)
        ), 'or');
        $res = $filter->compileString($this->getCompiler());
        $this->assertEquals("('FIELD1' = abc or 'FIELD2' = 123)", $res);
    }

    public function testCompileString_colNested()
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

    public function testCompileString_arrayValue()
    {
        $filter = new Collection(array(
            new Expression('FIELD1', array(1, 2, 3)),
            new Expression('FIELD2', 123),
            new Collection(array(
                new Expression('FIELD3', 6789, Operator::NUM_LESS_THAN),
                new Expression('FIELD4', 'test123', Operator::STR_END_WITH)
            ), 'or')
        ));
        $res = $filter->compileString($this->getCompiler());

        $lessThan = Operator::NUM_LESS_THAN;
        $endWith = Operator::STR_END_WITH;
        $this->assertEquals("('FIELD1' IN (1, 2, 3) ".
          "and 'FIELD2' = 123 and ('FIELD3' {$lessThan}".
          " 6789 or 'FIELD4' {$endWith} test123))", $res);
    }

    public function testCompileString_arrayValueMode()
    {
        $filter = new Collection(array(
            new Expression('FIELD1', array(1, 2, 3)),
            new Expression('FIELD2', 123),
            new Collection(array(
                new Expression('FIELD3', 6789, Operator::NUM_LESS_THAN),
                new Expression('FIELD4', 'test123', Operator::STR_END_WITH)
            ), 'or')
        ));
        $res = $filter->compileString($this->getCompiler(), Filter::COMPLILE_CAST_ARRAY_EXPRESSION_VALUES);

        $lessThan = Operator::NUM_LESS_THAN;
        $endWith = Operator::STR_END_WITH;
        $this->assertEquals("(('FIELD1' = 1 or 'FIELD1' = 2 or 'FIELD1' = 3) ".
          "and 'FIELD2' = 123 and ('FIELD3' {$lessThan}".
          " 6789 or 'FIELD4' {$endWith} test123))", $res);
    }

    public function testMerge_replaceExpression()
    {
        $filter = new Collection(array(
            new Expression('FIELD1', 'abc'),
            new Expression('FIELD2', 123)
        ));

        $filter->merge(new Expression('FIELD2', 321));
        $this->assertEquals(321, $filter->get(1)->getValue());
    }

    protected function getCompiler()
    {
        return function($type, $filter) {
            if ($type === Collection::COMPILE_COL) {
                return array('(', " {$filter->getType()} ", ')');
            } else {
                $oper = $filter->getOperator();
                if (Operator::EQUAL === $oper) {
                    $oper = '=';
                }
                $value = $filter->getValue();
                if (is_array($value)) {
                    $oper = 'IN';
                    $value = '(' . join(', ', $value) . ')';
                }

                return "'{$filter->getField()}' {$oper} {$value}";
            }
        };
    }
}