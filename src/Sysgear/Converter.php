<?php

/*
 * This file is part of the Sysgear package.
 *
 * (c) Martijn Evers <mevers47@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Sysgear;

use Sysgear\Converter\FormatterInterface;
use Sysgear\Converter\DefaultFormatter;
use Sysgear\Converter\CasterInterface;
use Sysgear\Converter\DefaultCaster;

/**
 * This component can be used to converter and format types and data.
 *
 * It can cast values from a foreign type system (A) to the PHP type system.
 *
 * Additionally it can perform two independent actions after that:
 * * It can cast it again to another foreign type system (B).
 * * It can format the values to string.
 *
 * It has serveral functions:
 * * casting - cast foreign types to PHP types
 * * formatting - format PHP types to PHP strings
 * * processing - cast foreign types A to foreign types B (specified by "setForeignCaster")
 * * convertering - cast foreign types A and format them
 *
 * Additional notes to the developer of this package:
 * Type system A is "srcCaster"
 * Type system B is optional "dstCaster"
 */
class Converter implements \Serializable
{
    /**
     * Cast foreign type system to PHP native types.
     *
     * @var \Sysgear\Converter\CasterInterface
     */
    protected $srcCaster;

    /**
     * Optional caster to cast PHP native types to a foreign type system.
     *
     * @var \Sysgear\Converter\CasterInterface
     */
    protected $dstCaster;

    /**
     * Format values from the PHP type system to string.
     *
     * @var \Sysgear\Converter\FormatterInterface
     */
    protected $formatter;

    /**
     * Create a new converter.
     *
     * @param CasterInterface $caster
     * @param FormatterInterface $formatter
     */
    public function __construct(CasterInterface $caster = null, FormatterInterface $formatter = null)
    {
        if (null === $caster) {
            $caster = new DefaultCaster();
        }

        $this->srcCaster = $caster;
        if (null === $formatter) {
            $this->formatter = new DefaultFormatter();
        } else {
            $this->formatter = $formatter;
        }
    }

    /**
     * Set a caster to cast types to a foreign type system.
     *
     * @param CasterInterface $caster
     * @return \Sysgear\Converter
     */
    public function setForeignCaster(CasterInterface $caster)
    {
        $this->dstCaster = $caster;
        return $this;
    }

    /**
     * Cast field types in records.
     *
     * @param array $records
     * @param array $types
     */
    public function castRecords(array &$records, array $types)
    {
        foreach ($records as &$record) {
            $this->castRecord($record, $types);
        }
    }

    /**
     * Cast fields in record.
     *
     * @param array $record
     * @param array $types
     */
    public function castRecord(array &$record, array $types)
    {
        foreach ($record as $field => &$value) {
            $value = $this->srcCaster->cast($value, @$types[$field]);
        }
    }

    /**
     * Cast a specific value.
     *
     * @param mixed $value
     * @param integer $type
     * @return mixed
     */
    public function cast($value, $type)
    {
        return $this->srcCaster->cast($value, $type);
    }

    /**
     * Format records.
     *
     * @param array $records Two dimensional array of rows and columns
     * @param array $types Datatypes to format indexed by column index
     */
    public function formatRecords(array &$records, array $types = array())
    {
        foreach ($records as &$record) {
            $record = $this->formatRecord($record, $types);
        }
    }

    /**
     * Format record.
     *
     * @deprecated
     * @param array $record Array of columns
     * @param array $types Datatypes to format indexed by column index
     */
    public function formatRecord(array &$record, array $types = array())
    {
        $newRecord = array();
        foreach ($record as $idx => $value) {
            $newRecord[$idx] = $this->formatValue($value, @$types[$idx]);
        }

        return $newRecord;
    }

    /**
     * Format value.
     *
     * @param mixed $value Value to format
     * @param array $dataType Datatype to format
     * @return mixed
     */
    public function format($value, $type = null)
    {
        return $this->formatter->format($value, $type);
    }

    /**
     * Process records.
     *
     * @param array $records
     * @param array $types
     */
    public function processRecords(array &$records, array $types)
    {
        foreach ($records as &$record) {
            $record = $this->processRecord($record, $types);
        }
    }

    /**
     * Process record.
     *
     * @param array $record
     * @param array $types
     * @return array
     */
    public function processRecord(array $record, array $types)
    {
        $newRecord = array();
        foreach ($record as $idx => $value) {
            $newRecord[$idx] = $this->process($value, @$types[$idx]);
        }

        return $newRecord;
    }

    /**
     * Process value.
     *
     * @param mixed $value
     * @param integer $type
     * @return mixed
     */
    public function process($value, $type)
    {
        return $this->formatter->format($this->srcCaster->cast($value, $type), $type);
    }

    /**
     * Convert records.
     *
     * @param array $records
     * @param array $types
     */
    public function convertRecords(array &$records, array $types)
    {
        foreach ($records as &$record) {
            $record = $this->convertRecord($record, $types);
        }
    }

    /**
     * Convert record.
     *
     * @param array $record
     * @param array $types
     * @return array
     */
    public function convertRecord(array $record, array $types)
    {
        $newRecord = array();
        $dstCaster = $this->dstCaster;
        if (null === $dstCaster) {
            foreach ($record as $idx => $value) {
                $newRecord[] = $this->srcCaster->cast($value, $types[$idx]);
            }

        } else {
            foreach ($record as $idx => $value) {
                $type = $types[$idx];
                $newRecord[] = $dstCaster->cast($this->srcCaster->cast($value, $type), $type);
            }
        }

        return $newRecord;
    }

    /**
     * Convert source $value to destination of type.
     *
     * @param mixed $value
     * @param integer $type
     * @return mixed
     */
    public function convert($value, $type)
    {
        $value = $this->srcCaster->cast($value, $type);
        return (null === $this->dstCaster) ? $value : $this->dstCaster->cast($value, $type);
    }

    public function serialize()
    {
        return serialize(array(
            'srcCaster' => $this->srcCaster,
            'dstCaster' => $this->dstCaster,
            'formatter' => $this->formatter
        ));
    }

    public function unserialize($serialized)
    {
        $properties = unserialize($serialized);
        $this->srcCaster = $properties['srcCaster'];
        $this->dstCaster = $properties['dstCaster'];
        $this->formatter = $properties['formatter'];
    }
}