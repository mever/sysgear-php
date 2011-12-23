<?php

/*
 * This file is part of the Sysgear package.
*
* (c) Martijn Evers <mevers47@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Sysgear\Backup;

use Sysgear\StructuredData\NodePath;
use Sysgear\Filter\Collection;
use Sysgear\Filter\Expression;
use Sysgear\Operator;
use \Serializable;

class InventoryManager implements Serializable
{
    /**
     * @var \Sysgear\StructuredData\NodePath
     */
    protected $currentPath;

    /**
     * @var \Sysgear\Filter\Collection
     */
    protected $include;

    /**
     * @var \Sysgear\Filter\Collection
     */
    protected $exclude;

    /**
     * Create a new inventory manager.
     */
    public function __construct()
    {
        $this->currentPath = new NodePath();
        $this->exclude = new Collection();
        $this->include = new Collection();
    }

    /**
     * Return the include list.
     *
     * @return \Sysgear\Filter\Collection
     */
    public function getIncludeList()
    {
        return $this->include;
    }

    /**
     * Return the exclude list.
     *
     * @return \Sysgear\Filter\Collection
     */
    public function getExcludeList()
    {
        return $this->exclude;
    }

    public function isBlocked($segment, $name, $idx = 0)
    {
        $this->currentPath->add($segment, $name, $idx);
        return ! $this->isAllowed($this->currentPath);
    }

    public function isAllowed(NodePath $path, $value = null)
    {
        $allowed = true;
        if (0 !== $this->include->count()) {
            $allowed = $this->checkCollection($this->include, $path, $value);
        }

        if ($allowed && 0 !== $this->exclude->count()) {
            $allowed = ! $this->checkCollection($this->exclude, $path, $value);
        }

        return $allowed;
    }

    protected function checkCollection(Collection $filter, NodePath $path, $value)
    {
        switch ($filter->getType()) {
            case Collection::TYPE_AND:
                foreach ($filter as $item) {

                    // get result for item in collection
                    if ($item instanceof Collection) {
                        $res = $this->checkCollection($item, $path, $value);
                    } else {
                        $res = $this->checkExpression($item, $path, $value);
                    }

                    // stop if a match is NOT found
                    if (false === $res) {
                        return false;
                    }
                }
                return true;

            case Collection::TYPE_OR:
                foreach ($filter as $item) {

                    // get result for item in collection
                    if ($item instanceof Collection) {
                        $res = $this->checkCollection($item, $path, $value);
                    } else {
                        $res = $this->checkExpression($item, $path, $value);
                    }

                    // stop if a match is found
                    if (true === $res) {
                        return true;
                    }
                }
                return false;
        }
    }

    protected function checkExpression(Expression $filter, NodePath $path, $value)
    {
        $fieldPath = new NodePath($filter->getField());
        if ($fieldPath->in($path)) {
            return Operator::compare($filter->getValue(), $filter->getOperator(), $value);
        }

        return false;
    }

    public function serialize()
    {
        return serialize(array('path' => $this->path,
            'exclude' => $this->exclude,
            'include' => $this->include));
    }

    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        $this->path = $data['path'];
        $this->exclude = $data['exclude'];
        $this->include = $data['include'];
    }
}