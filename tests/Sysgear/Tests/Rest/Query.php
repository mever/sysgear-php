<?php

/*
 * This file is part of the Sysgear package.
*
* (c) Martijn Evers <mevers47@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Sysgear\Tests\Rest;

use Sysgear\Test\TestCase;

class Query extends TestCase
{
    const C = 'Sysgear\Rest\Query';

    public function test_assertField_alphaNum()
    {
        $c = self::C;
        $c::assertField('abC1X23');
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Supplied field is invalid!
     */
    public function test_assertField_start_numeric()
    {
        $c = self::C;
        $c::assertField('123asg');
    }

    public function test_assertField_alias()
    {
        $c = self::C;
        $c::assertField('a.abC1X23');
    }

    public function test_assertField_alias_alphaNum()
    {
        $c = self::C;
        $c::assertField('a52wwSD29.abC1X23');
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Supplied field is invalid!
     */
    public function test_assertField_alias_start_numeric()
    {
        $c = self::C;
        $c::assertField('25.asdghy');
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Supplied field is invalid!
     */
    public function test_assertField_alias_underscore()
    {
        $c = self::C;
        $c::assertField('_.asdghy');
    }

    public function test_buildPartialSelect()
    {
        $query = $this->mock(self::C);
        $this->setProp($query, 'select', array('_.name', 'a.type', '_.abc', 'a.name', 'new.id'));
        $select = $this->exec($query, 'buildPartialSelect');

        $this->assertSame('partial _.{name,abc},partial a.{type,name},partial new.{id}', $select);
    }
}