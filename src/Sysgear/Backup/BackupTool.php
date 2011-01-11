<?php

namespace Sysgear\Backup;

use Sysgear\StructuredData\Collector\BackupCollector;
use Sysgear\StructuredData\Restorer\BackupRestorer;
use Sysgear\StructuredData\Exporter\ExporterInterface;
use Sysgear\StructuredData\Importer\ImporterInterface;

/**
 * Universal tool to backup about anything.
 * 
 * * Uses a structured data collector to backup data and pass it to the exporter.
 * * Uses an importer to import a backup and restore it using a restorer.
 * 
 * @author (c) Martijn Evers <martijn4evers@gmail.com>
 */
class BackupTool
{
    const META_DATETIME = 'datetime';
    const DEFAULT_DATETIME_FORMAT = \DateTime::W3C;

    /**
     * @var \Sysgear\StructuredData\Exporter\ExporterInterface
     */
    protected $exporter;

    /**
     * @var \Sysgear\StructuredData\Importer\ImporterInterface
     */
    protected $importer;

    /**
     * @var \DOMDocument
     */
    protected $document;

    /**
     * Configuration options for this backup tool.
     * 
     * @var array
     */
    protected $options = array();

    /**
     * Create backup utility.
     * 
     * @param \Sysgear\StructuredData\Exporter\ExporterInterface $exporter
     * @param \Sysgear\StructuredData\Importer\ImporterInterface $importer
     * @param array $options
     */
    public function __construct(ExporterInterface $exporter, ImporterInterface $importer, array $options = array())
    {
        $this->exporter = $exporter;
        $this->importer = $importer;

        $this->options = array_merge(array(
            'datetime' => true), $options);
    }

    /**
     * Backup collection of stuctured data from $object.
     * 
     * @param \Sysgear\Backup\BackupableInterface $object
     * @param array $collectorOptions
     * @return \Sysgear\StructuredData\Exporter\ExporterInterface
     */
    public function backup(BackupableInterface $object, array $collectorOptions = null)
    {
        $collector = new BackupCollector($collectorOptions);
        $object->collectStructedData($collector);
        $this->writeContent($collector->getDom());

        $this->exporter->setDom($this->document);
        return $this->exporter;
    }

    /**
     * Restore collection of structed data to $object.
     * 
     * @param \Sysgear\Backup\BackupableInterface $object
     * @param array $restorerOptions
     * @return \Sysgear\Backup\BackupableInterface
     */
    public function restore(BackupableInterface $object, array $restorerOptions = null)
    {
        $this->document = $this->importer->getDom();
        $restorer = new BackupRestorer($restorerOptions);
        $restorer->setDom($this->readContent());

        $object->restoreStructedData($restorer);
        return $object;
    }

    /**
     * Read backup content.
     * 
     * @return \DOMDocument
     */
    protected function readContent()
    {
        $doc = new \DOMDocument('1.0', 'utf8');
        $content = $this->document->getElementsByTagName('content')->item(0);
        foreach ($content->childNodes as $child) {
            $doc->appendChild($doc->importNode($child, true));
        }
        return $doc;
    }

    /**
     * Write backup content.
     * 
     * @param \DOMDocument $dom
     */
    protected function writeContent(\DOMDocument $dom)
    {
        // Create backup
        $doc = $this->document = new \DOMDocument('1.0', 'utf8');
        $backupElem = $doc->createElement('backup');

        // Create metadata
        $metadataElem = $doc->createElement('metadata');
        $backupElem->appendChild($metadataElem);
        foreach ($this->options as $name => $option) {
            $this->setMetadata($metadataElem, $name, $option);
        }

        // Create backup content
        $content = $doc->createElement('content');
        $backupElem->appendChild($content);
        foreach ($dom->childNodes as $child) {
            $content->appendChild($doc->importNode($child, true));
        }

        $doc->appendChild($backupElem);
    }

    /**
     * Create metadata for this backup.
     * 
     * @param \DOMNode $node
     * @param string $name
     * @param mixed $option
     */
    protected function setMetadata(\DOMNode $node, $name, $option)
    {
        switch ($name) {
        case self::META_DATETIME:
            $format = self::DEFAULT_DATETIME_FORMAT;
            if (is_array($option)) {
                $format = $option['format'];
                $option = true;
            }
            if ((boolean) $option) {
                $dateElem = $this->document->createElement('datetime');
                $node->appendChild($dateElem);
                $dateElem->setAttribute('format', "W3C (php date format: {$format})");
                $dateElem->setAttribute('value', date($format));
            }
            break;
        }
    }
}