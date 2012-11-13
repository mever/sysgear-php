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
    protected $encodedPath = '';

    /**
     * @var boolean
     */
    protected $isCollection = false;

    /**
     * Create a new node path.
     *
     * TODO: check encoding
     *
     * @param string $encodedPath
     */
    public function __construct($encodedPath = '')
    {
        $this->encodedPath = (string) $encodedPath;
    }

    /**
     * Add a new path segment.
     *
     * @param string $type Segment content type code: {@see self::*}
     * @param string $name
     * @param integer $idx
     * @throws \InvalidArgumentException
     * @return \Sysgear\StructuredData\NodePath
     */
    public function add($type, $name, $idx = 0)
    {
        if (strlen($type) > 1) {
            throw new \InvalidArgumentException('given segment code has more than one character');
        }

        if ($this->isCollection) {
            $this->isCollection = false;
            $type = $idx . $type;
        }

        if (self::COLLECTION === $type) {
            $this->isCollection = true;
        }

        $this->encodedPath .= '\\' . $type . addslashes($name);
        return $this;
    }

    /**
     * Chaining supported add node.
     *
     * @param string $name
     * @param integer $idx
     * @return \Sysgear\StructuredData\NodePath
     */
    public function node($name, $idx = 0)
    {
        $this->add(self::NODE, $name, $idx);
        return $this;
    }

    /**
     * Chaining supported add collection.
     *
     * @param string $name
     * @param integer $idx
     * @return \Sysgear\StructuredData\NodePath
     */
    public function col($name, $idx = 0)
    {
        $this->add(self::COLLECTION, $name, $idx);
        return $this;
    }

    /**
     * Chaining supported add value.
     *
     * @param string $name
     * @param integer $idx
     * @return \Sysgear\StructuredData\NodePath
     */
    public function value($name, $idx = 0)
    {
        $this->add(self::VALUE, $name, $idx);
        return $this;
    }

    /**
     * Get path segments.
     *
     * Each segment is a string. The first character indicates the segment type
     * when it is not numeric it is a content type defined in this class. When
     * it is numeric, the preseeding segment was a collection. The number represents
     * the index of the current segment in that collection, it is than followed by
     * the not-numeric content type character. After the content type character
     * the name of the path segment is presented.
     *
     * @return string[] segments
     */
    public function getSegments()
    {
        $parts = explode('\\', substr($this->encodedPath, 1));
        $segments = array();
        $isEscaped = false;

        foreach ($parts as $segment) {

            if ($isEscaped) {
                $segments[count($segments) - 1] .= '\\' . $segment;
                $isEscaped = false;
                continue;
            }

            if ('' === $segment) {
                $isEscaped = true;
            } else {
                $segments[] = $segment;
            }
        }

        return $segments;
    }

    /**
     * Is this path in the supplied path, as parent or exact match.
     *
     * @param NodePath $path
     * @return boolean
     */
    public function in(NodePath $path)
    {
        $subPathSegments = $this->getSegments();
        $superPathSegments = $path->getSegments();

        if (count($subPathSegments) > count($superPathSegments)) {
            return false;
        }

        foreach ($subPathSegments as $idx => $segment) {
            if ($segment !== $superPathSegments[$idx]) {
                return false;
            }
        }

        return true;
    }

    /**
     * Return segment name by segment index.
     *
     * TODO: unit test
     *
     * @param integer $index Segment index
     * @return string
     */
    public function getSegmentName($index)
    {
        $segments = $this->getSegments();
        $segment = $segments[$index];
        if (is_numeric($segment[0])) {
            return preg_replace('/[0-9]*.(.*)/', '${1}', $segment);
        }

        return substr($segment, 1);
    }

    /**
     * Clear the path.
     */
    public function clear()
    {
        $this->encodedPath = '';
    }

    /**
     * Return this path as a string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->encodedPath;
    }
}