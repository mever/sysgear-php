<?php

namespace Sysgear\Backup;

use Sysgear\StructuredData\Collector\BackupCollector;
use Sysgear\StructuredData\Restorer\BackupRestorer;
use Sysgear\StructuredData\Restorer\DoctrineRestorer;
use Sysgear\StructuredData\Exporter\ExporterInterface;
use Sysgear\StructuredData\Importer\ImporterInterface;
use Sysgear\StructuredData\NodeProperty;
use Sysgear\StructuredData\Node;

/**
 * Universal tool to backup about anything.
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
     * Backup collection of structured data from $object.
     *
     * @param \Sysgear\Backup\BackupableInterface $object
     * @param array $collectorOptions
     * @return \Sysgear\StructuredData\Exporter\ExporterInterface
     */
    public function backup(BackupableInterface $object, array $collectorOptions = array())
    {
        $collectorOptions = array_merge($this->collectorOptions, $collectorOptions);
        $collector = new BackupCollector($collectorOptions);
        $object->collectStructuredData($collector);

        $node = $this->writeContent($collector->getNode());
        $this->exporter->setNode($node);
        return $this->exporter;
    }

    /**
     * Restore collection of structured data to $object.
     *
     * @param array $restorerOptions
     * @param BackupableInterface $object
     * @return BackupableInterface
     * @throws Exception
     */
    public function restore(array $restorerOptions = array(), BackupableInterface $object = null)
    {
        $contentNode = $this->importer->getNode()->getProperty('content');
        if (null === $contentNode) {
            throw Exception::backupHasNoContent($this->importer->getNode());
        }

        $restoredObject = null;
        $restorerOptions = array_merge($this->restorerOptions, $restorerOptions);
        if (array_key_exists('entityManager', $restorerOptions)) {
            $restorer = new DoctrineRestorer($restorerOptions);
            $restorerOptions['entityManager']->transactional(function() use ($restorer, $contentNode, &$restoredObject) {
                $restoredObject = $restorer->restore($contentNode);
            });
        }

        if (null === $restoredObject && true === @$restorerOptions['reconstruct']) {
            $restorer = new BackupRestorer($restorerOptions);
            return $restorer->restore($contentNode, $object);
        } else {

            return $restoredObject;
        }
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