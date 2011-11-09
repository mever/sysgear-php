<?php

namespace Sysgear\StructuredData\Restorer;

use Sysgear\StructuredData\Importer\ImporterInterface;
use Sysgear\StructuredData\Node;

/**
 * Responsible for restoring data.
 *
 * @author (c) Martijn Evers <martijn4evers@gmail.com>
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
     * Restore data to object.
     *
     * @param \StdClass $object
     * @return array Return a list of remaining properties, those that could not
     *               be set because the restorer was not able to access the
     *               modifiers of the $object.
     */
    public function toObject($object);

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