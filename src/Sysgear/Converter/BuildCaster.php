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
 * Dynamically build a type caster.
 */
class BuildCaster implements CasterInterface
{
    /**
     * @var \Sysgear\Converter\CasterInterface
     */
    protected $caster;

    /**
     * Collection of cast functions.
     *
     * @var array
     */
    protected $castMethods = array();

    /**
     * Source timezone. This is the timezone you cast values from.
     *
     * @var \DateTimeZone
     */
    protected $timezone;

    /**
     * {@inheritdoc}
     */
    public function cast($value, $type)
    {
        if (! $this->castMethods) {
            return $value;
        }

        if (null === $this->caster) {
            $this->build();
        }

        return $this->caster->cast($value, $type, $this->timezone);
    }

    /**
     * {@inheritdoc}
     */
    public function setTimezone(\DateTimeZone $timezone)
    {
        $this->timezone = $timezone;
    }

    /**
     * Clear build, ready to build a new caster.
     */
    public function clear()
    {
        $this->caster = null;
        $this->castMethods = array();
    }

    /**
     * Set cast code.
     *
     * The given code MAY consist of multiple statements. The last statment
     * MUST always return the new type. The given code can use the following local
     * parameters:
     * - $v = the value to cast into its new type
     * - $tz = the DateTimeZone of datetime values
     *
     * @param integer $type
     * @param string $code
     * @throws \LogicException
     * @return \Sysgear\Converter\CasterBuilder
     */
    public function set($type, $code)
    {
        if (null !== $this->caster) {
            throw new \LogicException("The caster is already build");
        }

        $this->castMethods[$type] = $code;
        return $this;
    }

    /**
     * Add cast code.
     *
     * @deprecated Use "set" instead.
     * @param integer $type
     * @param string $code
     * @throws \LogicException
     * @return \Sysgear\Converter\CasterBuilder
     */
    public function add($type, $code)
    {
        return $this->set($type, $code);
    }

    /**
     * Add default PHP types.
     */
    public function useDefaultTypes()
    {
        $this->set(Datatype::STRING, 'return (null === $v) ? null : (string) $v');
        $this->set(Datatype::INT, 'return ("" === $v || null === $v) ? null : (int) $v');
        $this->set(Datatype::FLOAT, 'return ("" === $v || null === $v) ? null : (float) $v');
        $this->set(Datatype::NUMBER, 'return ("" === $v || null === $v) ? null : (float) $v');
        $this->set(Datatype::DATETIME, 'if (empty($v)) {return null;} return new \\DateTime($v, $tz)');
        $this->set(Datatype::DATE, 'if (empty($v)) {return null;} return '.
            'new \\DateTime($v . "00:00:00", new \\DateTimeZone("UTC"));');

        $this->set(Datatype::TIME, 'if (empty($v)) {return null;} return '.
            'new \\DateTime("01-01-1970 " . $v, new \\DateTimeZone("UTC"))');
    }

    /**
     * Build caster.
     */
    protected function build()
    {
        if (null === $this->timezone) {
            $this->timezone = new \DateTimeZone('UTC');
        }

        $switchClause = '';
        foreach ($this->castMethods as $type => $code) {
            $switchClause .= "case {$type}:{$code};return \$v;\n";
        }

        $className = 'GeneratedCaster_' . sha1($switchClause);
        if (! class_exists($className)) {
            $class = "class {$className} {\n".
                "public function cast(\$v, \$type, \$tz) {\nswitch(\$type) {\n{$switchClause}".
                "default: return \$v;\n}}}";

            eval($class);
        }

        $this->caster = new $className();
        return $className;
    }

    public function serialize()
    {
        return serialize(array(
            $this->castMethods,
            (null === $this->timezone) ? null : $this->timezone->getName()));
    }

    public function unserialize($serialized)
    {
        $properties = unserialize($serialized);
        $this->castMethods = $properties[0];
        $this->timezone = (null === @$properties[1]) ? null : new \DateTimeZone($properties[1]);
    }
}