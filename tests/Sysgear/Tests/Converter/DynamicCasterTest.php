<?php

/*
 * This file is part of the Sysgear package.
 *
 * (c) Martijn Evers <mevers47@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sysgear\Tests\Converter;

use Sysgear\Datatype;
use Sysgear\Converter\DynamicCaster;

class DynamicCasterTest extends \PHPUnit_Framework_TestCase
{
    public function testAdd()
    {
        $caster = new DynamicCaster();
        $caster->add(Datatype::INT, function($value) {
            return number_format($value, 0, '.', '-');
        });

        $this->assertEquals('123-456-789', $caster->cast(123456789.1012, Datatype::INT));
    }
}