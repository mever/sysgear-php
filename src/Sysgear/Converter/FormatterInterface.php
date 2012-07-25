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
     * Create a new formatter instance.
     *
     * @param FormatCollection $formats
     */
    public function __construct(FormatCollection $formats);

    /**
     * Return value in a specific format.
     *
     * @param mixed $value
     * @param integer $type
     * @return mixed
     */
    public function format($value, $type = null);

    /**
     * Supply format specification for each type.
     *
     * @param FormatCollection $formats
     */
    public function setFormats(FormatCollection $formats);

    /**
     * Return the format specification for each type.
     *
     * @return \Sysgear\Converter\FormatCollection
     */
    public function getFormats();
}