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

class Cursor
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
     * Advances the cursor to the next result.
     *
     * @return \Application\WmsBundle\Contract\Iface\Cursor
     */
    public function next()
    {
        $this->pointer++;
        return $this;
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