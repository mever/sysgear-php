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

/**
 * Dynamically call a type caster.
 */
class DynamicCaster implements CasterInterface
{
    /**
     * Collection of cast functions indexed by type.
     *
     * @var \Closure[]
     */
    protected $casters = array();

    /**
     * Source timezone. This is the timezone you cast values from.
     *
     * @var \DateTimeZone
     */
    protected $timezone;

    /**
     * Add caster function to dynamicly cast a type.
     *
     * @param string $type
     * @param \Closure $caster
     * @return \Sysgear\Converter\DynamicCaster
     */
    public function add($type, \Closure $caster)
    {
        $this->casters[$type] = $caster;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function cast($value, $type)
    {
        if (isset($this->casters[$type])) {
            $castFunc = $this->casters[$type];
            return $castFunc($value, $this->timezone);
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function setTimezone(\DateTimeZone $timezone)
    {
        $this->timezone = $timezone;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize(array(
            (null === $this->timezone) ? null : $this->timezone->getName()
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        $properties = unserialize($serialized);
        $this->timezone = (null === @$properties[0]) ? null : new \DateTimeZone($properties[0]);
    }
}