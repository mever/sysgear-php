<?php

namespace Sysgear\StructuredData\Collector;

use Doctrine\ORM\Tools\Export\ClassMetadataExporter;

class Doctrine2EntityCollector extends AbstractCollector
{
    /**
     * @var \Doctrine\ORM\Tools\Export\ClassMetadataExporter
     */
    protected $classMetadataExporter;

    /**
     * Doctrine2 class metadata exporter type.
     * 
     * @var string
     */
    protected $exporterDriverType = 'annotation';

    public function __construct()
    {
        parent::__construct();
        $this->classMetadataExporter = new ClassMetadataExporter();
    }

    public function scanObject(\StdClass $object)
    {
//        // TODO
//        $this->classMetadataExporter->getExporter($this->exporterDriverType);
    }
}