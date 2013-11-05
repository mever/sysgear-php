<?php

namespace Sysgear;

interface OperationInterface
{
    /**
     * Perform operation on object.
     *
     * @param mixed $object
     * @param array $params Key-value map of parameters.
     */
    public function execute($object, array $params = array());
}