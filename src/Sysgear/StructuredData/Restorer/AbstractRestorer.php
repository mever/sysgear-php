<?php

namespace Sysgear\StructuredData\Restorer;

use Sysgear\StructuredData\Importer\ImporterInterface;

abstract class AbstractRestorer implements RestorerInterface
{
    /**
     * @var \DOMDocument
     */
    protected $document;

    /**
     * Construct data collector.
     *
     * @param array $options
     */
    public function __construct(array $options = array())
    {
        $this->document = new \DOMDocument('1.0', 'UTF-8');
        foreach ($options as $key => $value) {
            $this->setOption($key, $value);
        }
    }

    /**
     * Set option.
     *
     * @param string $key
     * @param mixed $value
     */
    public function setOption($key, $value)
    {
    }

    /**
     * (non-PHPdoc)
     * @see Sysgear\StructuredData\Restorer.RestorerInterface::setDom()
     */
    public function setDom(\DOMDocument $domDocument)
    {
        $this->document = $domDocument;
        return $this;
    }

    /**
     * Return the node name which represents this $object.
     *
     * @param object $object
     * @return string
     */
    protected function getNodeName($object)
    {
        $fullClassname = get_class($object);
        $pos = strrpos($fullClassname, '\\');
        return (false === $pos) ? $fullClassname : substr($fullClassname, $pos + 1);
    }
}