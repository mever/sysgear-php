<?php

namespace Sysgear\Backup;

use Sysgear\StructuredData\Collector\BackupCollector;
use Sysgear\StructuredData\Restorer\BackupRestorer;
use Sysgear\StructuredData\Exporter\ExporterInterface;
use Sysgear\StructuredData\Importer\ImporterInterface;
use Sysgear\StructuredData\NodeProperty;
use Sysgear\StructuredData\Node;

/**
 * Universal tool to backup about anything.
 *
 * * Uses a structured data collector to backup data and pass it to the exporter.
 * * Uses an importer to import a backup and restore it using a restorer.
 *
 * @author (c) Martijn Evers <mevers47@gmail.com>
 */
class BackupTool
{
    /**
     * @var \Sysgear\StructuredData\Exporter\ExporterInterface
     */
    protected $exporter;

    /**
     * @var \Sysgear\StructuredData\Importer\ImporterInterface
     */
    protected $importer;

    /**
     * Configuration options for this backup tool.
     *
     * @var array
     */
    protected $options = array();

    /**
     * Configuration options for the collector.
     *
     * @var array
     */
    protected $collectorOptions = array();

    /**
     * Configuration options for the restorer.
     *
     * @var array
     */
    protected $restorerOptions = array();

    /**
     * Create backup utility.
     *
     * @param \Sysgear\StructuredData\Exporter\ExporterInterface $exporter
     * @param \Sysgear\StructuredData\Importer\ImporterInterface $importer
     * @param array $options
     */
    public function __construct(ExporterInterface $exporter,
        ImporterInterface $importer, array $options = array())
    {
        $this->exporter = $exporter;
        $this->importer = $importer;
        $this->options = $options;
    }

    /**
     * Set configuration option.
     *
     * @param string $key
     * @param mixed $value
     * @return \Sysgear\Backup\BackupTool
     */
    public function setOption($key, $value)
    {
        $this->options[$key] = $value;
        return $this;
    }

    /**
     * Set collector configuration option.
     *
     * @param string $key
     * @param mixed $value
     * @return \Sysgear\Backup\BackupTool
     */
    public function setCollectorOption($key, $value)
    {
        $this->collectorOptions[$key] = $value;
        return $this;
    }

    /**
     * Set restorer configuration option.
     *
     * @param string $key
     * @param mixed $value
     * @return \Sysgear\Backup\BackupTool
     */
    public function setRestorerOption($key, $value)
    {
        $this->restorerOptions[$key] = $value;
        return $this;
    }

    /**
     * Read restore data from file.
     *
     * @param string $path
     * @return \Sysgear\Backup\BackupTool
     */
    public function readFile($path)
    {
        $this->importer->fromString(file_get_contents($path));
        return $this;
    }

    /**
     * Backup collection of stuctured data from $object.
     *
     * @param \Sysgear\Backup\BackupableInterface $object
     * @param array $collectorOptions
     * @return \Sysgear\StructuredData\Exporter\ExporterInterface
     */
    public function backup(BackupableInterface $object, array $collectorOptions = array())
    {
        $collectorOptions = array_merge($this->collectorOptions, $collectorOptions);
        $collector = new BackupCollector($collectorOptions);
        $object->collectStructedData($collector);

        $node = $this->writeContent($collector->getNode());
        $this->exporter->setNode($node);
        return $this->exporter;
    }

    /**
     * Restore collection of structed data to $object.
     *
     * @param array $restorerOptions
     * @param BackupableInterface $object
     * @return BackupableInterface
     */
    public function restore(array $restorerOptions = array(), BackupableInterface $object = null)
    {
        // create restorer
        if (array_key_exists("merger", $this->options)) {
            $this->restorerOptions["merger"] = $this->options["merger"];
        }

        $restorerOptions = array_merge($this->restorerOptions, $restorerOptions);
        $restorer = new BackupRestorer($restorerOptions);

        $contentNode = $this->importer->getNode()->getProperty('content');
        if (null === $contentNode) {
            throw Exception::backupHasNoContent($this->importer->getNode());
        }

        return $restorer->restore($contentNode, $object);
    }

    /**
     * Write backup content.
     *
     * @param Node $node
     * @return \DOMDocument
     */
    protected function writeContent(Node $node)
    {
        // create backup
        $backupNode = new Node('container', 'backup');

        // set date
        $format = \DateTime::RFC1123;
        $backupNode->setProperty('date', new NodeProperty('rfc1123', date($format)));

        // create backup content
        $backupNode->setProperty('content', $node);
        return $backupNode;
    }
}