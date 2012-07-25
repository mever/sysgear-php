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
     * @var \DateTimeZone
     */
    protected $timezone;

    /**
     * Create a format collection.
     *
     * When no timezone is specified the PHP default timezone is assumed.
     *
     * @param \DateTimeZone $timezone
     */
    public function __construct(\DateTimeZone $timezone = null)
    {
        $this->timezone = (null === $timezone) ? new \DateTimeZone(date_default_timezone_get()) : $timezone;
    }

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
     * Set the timezone for all formats.
     *
     * @param \DateTimeZone $timezone
     * @return \Sysgear\Converter\FormatCollection
     */
    public function setTimezone(\DateTimeZone $timezone)
    {
        $this->timezone = $timezone;
        return $this;
    }

    /**
     * Return timezone of formats.
     *
     * @return \DateTimeZone
     */
    public function getTimezone()
    {
        return $this->timezone;
    }

    /**
     * Set format.
     *
     * @param int $type
     * @param string $format
     * @return \Sysgear\Converter\FormatCollection
     */
    public function set($type, $format)
    {
        $this->formats[$type] = $format;
        return $this;
    }

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