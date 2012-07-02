<?php

/*
 * This file is part of the Sysgear package.
*
* (c) Martijn Evers <mevers47@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/


namespace Sysgear\Converter;

use Sysgear\Datatype;

class DefaultFormatter implements FormatterInterface
{
    public function format($value, $type = null)
    {
        if (Datatype::isNumber($type)) {
            return (float) $value;

        } elseif ($value instanceof \DateTime) {
            return $value->format(\DateTime::W3C);
        }

        return $value;
    }
}