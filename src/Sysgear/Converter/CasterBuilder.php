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

class CasterBuilder implements CasterInterface
{
    /**
     * @var \Sysgear\Converter\CasterInterface
     */
    protected $caster;

    protected $castMethods = array();

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
        $this->add(Datatype::DATETIME, 'return new \\DateTime($v)');
    }

    /**
     * Build caster.
     */
    protected function build()
    {
        $switchClause = '';
        foreach ($this->castMethods as $type => $code) {
            $switchClause .= "case {$type}:{$code};break;\n";
        }

        $className = 'GeneratedCaster_' . sha1($switchClause);
        if (! class_exists($className)) {
            $class = "class {$className} implements Sysgear\\Converter\\CasterInterface {\n".
                "public function cast(\$type, \$v) {\nswitch(\$type) {\n{$switchClause}".
                "default: return \$v;\n}}}";

            eval($class);
        }

        $this->caster = new $className();
        return $className;
    }

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

        return $this->caster->cast($type, $value);
    }
}