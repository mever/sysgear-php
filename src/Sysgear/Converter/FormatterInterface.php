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

interface FormatterInterface
{
    /**
     * Return value in a specific format.
     *
     * @param mixed $value
     * @param integer $type
     * @return mixed
     */
    public function format($value, $type = null);
}