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
        if (null === $value) {
            return null;
        }

        switch ($type) {
            case Datatype::DATETIME:
                $value->setTimezone(new \DateTimeZone('UTC'));
                return $value->format('Y-m-d\TH:i:s\Z');

            case Datatype::DATE:
                return $value->format('Y-m-d');

            case Datatype::TIME:
                return $value->format('H:i:s');

            case Datatype::NUMBER:
            case Datatype::FLOAT:
                return (float) $value;

            case Datatype::INT:
                return (int) $value;

            default:
                return (string) $value;
        }
    }
}