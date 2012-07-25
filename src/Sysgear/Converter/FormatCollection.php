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

class FormatCollection
{
    /**
     * Collection of generic formats.
     *
     * @var array
     */
    protected $formats = array(

        // PHP date function format
        Datatype::DATETIME => 'Y-m-d\TH:i:s\Z',
        Datatype::DATE => 'Y-m-d',
        Datatype::TIME => 'H:i:s'
    );

    /**
     * Return format string.
     *
     * @param int $type
     * @return string
     */
    public function get($type)
    {
        return @$this->formats[$type];
    }
}