<?php

namespace Sysgear\StructuredData\Restorer;

use Sysgear\StructuredData\Importer\ImporterInterface;
use Sysgear\StructuredData\Node;

/**
 * Responsible for restoring data.
 *
 * @author (c) Martijn Evers <mevers47@gmail.com>
 */
interface RestorerInterface
{
    /**
     * Construct data restorer.
     *
     * @param array $options
     */
    public function __construct(array $options = array());

    /**
     * Set option.
     *
     * @param string $key
     * @param mixed $value
     */
    public function setOption($key, $value);

    /**
     * Restore a object.
     *
     * @param Node $node The node representing the object to restore.
     * @param object $object Optional object to restore, else try to create an object based
     *   on metadata stored in the $node.
     *
     * @return object
     */
    public function restore(Node $node, $object = null);
}