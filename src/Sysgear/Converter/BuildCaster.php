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

/**
 * Dynamically build a type caster.
 */
use Sysgear\Datatype;

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
     * (non-PHPdoc)
     * @see Sysgear\Converter.CasterInterface::cast()
     */
    public function cast($type, $value)
    {
        if (! $this->castMethods) {
            return $value;
        }

        if (null === $this->caster) {
            $this->build();
        }

        return $this->caster->cast($type, $value, $this->timezone);
    }

    /**
     * (non-PHPdoc)
     * @see Sysgear\Converter.CasterInterface::setTimezone()
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
     * Add cast code.
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
    public function add($type, $code)
    {
        if (null !== $this->caster) {
            throw new \LogicException("The caster is already build");
        }

        $this->castMethods[$type] = $code;
        return $this;
    }

    /**
     * Add default PHP types.
     */
    public function useDefaultTypes()
    {
        $this->add(Datatype::INT, 'return (int) $v');
        $this->add(Datatype::FLOAT, 'return (float) $v');
        $this->add(Datatype::NUMBER, 'return (float) $v');
        $this->add(Datatype::DATETIME, 'return new \\DateTime($v, $tz)');
    }

    /**
     * Build caster.
     */
    protected function build()
    {
        if (null === $this->timezone) {
            $this->timezone = new \DateTimeZone('Zulu');
        }

        $switchClause = '';
        foreach ($this->castMethods as $type => $code) {
            $switchClause .= "case {$type}:{$code};return \$v;\n";
        }

        $className = 'GeneratedCaster_' . sha1($switchClause);
        if (! class_exists($className)) {
            $class = "class {$className} {\n".
                "public function cast(\$type, \$v, \$tz) {\nswitch(\$type) {\n{$switchClause}".
                "default: return \$v;\n}}}";

            eval($class);
        }

        $this->caster = new $className();
        return $className;
    }
}