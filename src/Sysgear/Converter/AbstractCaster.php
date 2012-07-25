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

abstract class AbstractCaster implements CasterInterface
{
    /**
     * (non-PHPdoc)
     * @see \Sysgear\Converter\CasterInterface::setTimezone()
     */
    public function setTimezone(\DateTimeZone $timezone)
    {
    }

    /**
     * (non-PHPdoc)
     * @see Serializable::serialize()
     */
    public function serialize()
    {
    }

    /**
     * (non-PHPdoc)
     * @see Serializable::unserialize()
     */
    public function unserialize($data)
    {
    }
}