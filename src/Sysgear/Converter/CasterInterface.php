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

interface CasterInterface extends \Serializable
{
    /**
     * Cast value with $type to a more specific type.
     *
     * @param mixed $value
     * @param integer $type
     * @return mixed
     */
    public function cast($value, $type);

    /**
     * Set the timzone of data to cast.
     *
     * @param \DateTimeZone $timezone
     */
    public function setTimezone(\DateTimeZone $timezone);
}