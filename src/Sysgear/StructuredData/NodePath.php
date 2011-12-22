<?php

/*
 * This file is part of the Sysgear package.
*
* (c) Martijn Evers <mevers47@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Sysgear\StructuredData;

/**
 * Represent a path through a node structure as a string.
 *
 * @example \Ncompany\Cfunctions\0\Nadmin\Cmembers\2\Nuser\Vname
 */
class NodePath
{
    const NODE = 'N';
    const COLLECTION = 'C';
    const VALUE = 'V';

    /**
     * @var string
     */
    protected $encodededPath = '';

    /**
     * @var boolean
     */
    protected $isCollection = false;

    /**
     * Add a new path segment.
     *
     * @param string $segment Segment code constant: {@see self::*}
     * @param string $name
     * @param integer $idx
     * @throws \InvalidArgumentException
     */
    public function add($segment, $name, $idx = 0)
    {
        if ($this->isCollection) {
            $this->encodededPath .= '\\' . $idx;
            $this->isCollection = false;
        }

        if (strlen($segment) > 1) {
            throw new \InvalidArgumentException('given segment code has more than one character');
        }

        if (self::COLLECTION === $segment) {
            $this->isCollection = true;
        }

        $this->encodededPath .= '\\' . $segment . addslashes($name);
    }

    /**
     * Clear the path.
     */
    public function clear()
    {
        $this->encodededPath = '';
    }

    /**
     * Return this path as a string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->encodededPath;
    }
}