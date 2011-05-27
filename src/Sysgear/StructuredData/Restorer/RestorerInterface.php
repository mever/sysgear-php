<?php

namespace Sysgear\StructuredData\Restorer;

use Sysgear\StructuredData\Importer\ImporterInterface;

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
     * Set option.
     *
     * @param string $key
     * @param mixed $value
     */
    public function setOption($key, $value);

    /**
     * Get option.
     *
     * @param string $key
     */
    public function getOption($key);

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
     * Set the DOM for restorer.
     *
     * @param \DOMDocument $domDocument
     * @return \Sysgear\StructuredData\Restorer\RestorerInterface
     */
    public function setDom(\DOMDocument $domDocument);
}