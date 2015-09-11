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

class DefaultCaster implements CasterInterface
{
    /**
     * Default timezone, if no timezone is present.
     *
     * @var \DateTimeZone
     */
    protected $timezone;

    /**
     * Create a default type caster.
     */
    public function __construct()
    {
        $this->timezone = new \DateTimeZone(date_default_timezone_get());
    }

    /**
     * {@inheritdoc}
     */
    public function cast($value, $type)
    {
        switch ($type) {
            case Datatype::INT:
                return ("" === $value || null === $value) ? null : (int) $value;

            case Datatype::NUMBER:
            case Datatype::FLOAT:
                return ("" === $value || null === $value) ? null : (float) $value;

            case Datatype::DATETIME:
                return (empty($value)) ? null : new \DateTime($value, $this->timezone);

            case Datatype::DATE:
                return (empty($value)) ? null : new \DateTime($value . "00:00:00", new \DateTimeZone("UTC"));

            case Datatype::DATE:
                return (empty($value)) ? null : new \DateTime("01-01-1970 " . $value, new \DateTimeZone("UTC"));

            // assume Datatype::STRING
            default:
                return (null === $value) ? null : (string) $value;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setTimezone(\DateTimeZone $timezone)
    {
        $this->timezone = $timezone;
    }

    public function serialize()
    {
        return serialize((null === $this->timezone) ? null : $this->timezone->getName());
    }

    public function unserialize($serialized)
    {
        $timezone = unserialize($serialized);
        $this->timezone = (null === $timezone) ? null : new \DateTimeZone($timezone);
    }
}