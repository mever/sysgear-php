<?php

/*
 * This file is part of the Sysgear package.
*
* (c) Martijn Evers <mevers47@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/


namespace Sysgear;

class Cursor implements \Iterator
{
    protected $callback;
    protected $count;
    protected $pointer = 0;

    /**
     * Creates a new cursor.
     *
     * @param Closure $callback
     * @param integer $count
     */
    public function __construct(\Closure $callback, $count = null)
    {
        $this->callback = $callback;
        $this->count = $count;
    }

    /**
     * Return the next object to which this cursor points, and advance the cursor.
     *
     * @return array
     */
    public function getNext()
    {
        $callback = $this->callback;
        return $callback($this->pointer++);
    }

    /**
     * Returns the current element.
     *
     * @return array
     */
    public function current()
    {
        $callback = $this->callback;
        return $callback($this->pointer);
    }

    /**
     * Return the pointer.
     *
     * @return integer
     */
    public function key()
    {
        return $this->pointer;
    }

    /**
     * Advances the cursor to the next result.
     */
    public function next()
    {
        $this->pointer++;
    }

    /**
     * This operation is not supported!
     */
    public function rewind()
    {
        // NOT SUPPORTED FOR CURSORS
    }

    /**
     * Checks if current position is valid.
     *
     * @return boolean
     */
    public function valid()
    {
        if (null === $this->count) {
            return (null !== $this->current());
        } else {
            return ($this->pointer < $this->count);
        }
    }

    /**
     * Counts the number of results for this cursor.
     *
     * @return integer
     */
    public function count()
    {
        return $this->count;
    }
}