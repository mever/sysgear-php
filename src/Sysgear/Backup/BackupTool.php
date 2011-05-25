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

        $this->options = array_merge(array(
            'datetime' => true), $options);
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
     */
    public function readFile($path)
    {
        $this->importer->fromString(file_get_contents($path));
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

        $dom = $this->writeContent($collector->getDom());
        $this->exporter->setDom($dom);
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
        $document = $this->importer->getDom();
        $dom = new \DOMDocument('1.0', 'UTF-8');

        // Create restorer.
        if (array_key_exists("merger", $this->options)) {
            $this->restorerOptions["merger"] = $this->options["merger"];
        }
        $restorerOptions = array_merge($this->restorerOptions, $restorerOptions);
        $restorer = new BackupRestorer($restorerOptions);

        // Collect content to restore.
        $content = $document->getElementsByTagName('content')->item(0);
        foreach ($content->childNodes as $child) {

            if (XML_ELEMENT_NODE === $child->nodeType) {
                $dom->appendChild($dom->importNode($child, true));
                $restorer->setDom($dom);

                if (null === $object) {
                    $object = $this->createRootObject($child);
                }
                break;
            }
        }

        // Restore object.
        $object->restoreStructedData($restorer);

        // Merge object with entity manager (if one given).
        if (array_key_exists("merger", $this->options)) {
            $this->options["merger"]->merge($object);
            $this->options["merger"]->flush();
        }

        return $object;
    }

    /**
     * Restore collection from DOM to $object.
     *
     * @param \DOMDocument $dom
     * @param array $restorerOptions
     * @param BackupableInterface $object
     * @return BackupableInterface
     */
    public function restoreFromDom(\DOMDocument $dom, array $restorerOptions = array())
    {
        // Create restorer.
        if (array_key_exists("merger", $this->options)) {
            $this->restorerOptions["merger"] = $this->options["merger"];
        }
        $restorerOptions = array_merge($this->restorerOptions, $restorerOptions);
        $restorer = new BackupRestorer($restorerOptions);
        $restorer->setDom($dom);

        // Collect content to restore.
        foreach ($dom->childNodes as $child) {

            if (XML_ELEMENT_NODE === $child->nodeType) {
                $object = $restorer->restore($child);
                break;
            }
        }

        return $object;
    }

    /**
     * Create root object to restore from.
     *
     * @param \DOMElement $element
     * @return \Sysgear\Backup\BackupableInterface
     */
    protected function createRootObject(\DOMElement $element)
    {
        $class = trim($element->attributes->getNamedItem('class')->textContent);
        return new $class();
    }

    /**
     * Write backup content.
     *
     * @param \DOMDocument $document
     * @return \DOMDocument
     */
    protected function writeContent(\DOMDocument $document)
    {
        // Create backup
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $backupElem = $dom->createElement('backup');

        // Create metadata
        $metadataElem = $dom->createElement('metadata');
        $backupElem->appendChild($metadataElem);
        foreach ($this->options as $name => $option) {
            $this->setMetadata($dom, $metadataElem, $name, $option);
        }

        // Create backup content
        $content = $dom->createElement('content');
        $backupElem->appendChild($content);
        foreach ($document->childNodes as $child) {
            $content->appendChild($dom->importNode($child, true));
        }

        $dom->appendChild($backupElem);
        return $dom;
    }

    /**
     * Create metadata for this backup.
     *
     * @param \DOMDocument $document
     * @param \DOMNode $node
     * @param string $name
     * @param mixed $option
     */
    protected function setMetadata(\DOMDocument $document, \DOMNode $node, $name, $option)
    {
        switch ($name) {
        case self::META_DATETIME:
            $format = self::DEFAULT_DATETIME_FORMAT;
            if (is_array($option)) {
                $format = $option['format'];
                $option = true;
            }
            if ((boolean) $option) {
                $dateElem = $document->createElement('datetime');
                $node->appendChild($dateElem);
                $dateElem->setAttribute('format', $format);
                $dateElem->setAttribute('value', date($format));
                $dateElem->setAttribute('description', "PHP date format. See: ".
                    "http://nl3.php.net/manual/en/function.date.php");
            }
            break;
        }
    }
}