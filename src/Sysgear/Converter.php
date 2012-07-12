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
use Sysgear\Converter\BuildCaster;

/**
 * Utility to cast and format values from one system to the next.
 *
 * There are three concepts:
 * - casting: Interpreting a mixed PHP value with a specific type and turn it into a homogeneous PHP type.
 * - formatting: Get a homogeneous PHP type and format it as a string.
 * - processing: Cast and format a mixed PHP type.
 *
 * The separation between casting and formatting is so each system can choose to support specific
 * types to cast into without formatting it. Secondly, one can choose to stick with a casting schema
 * and altering the format to form diffrent representations. This allows the utility to be used as:
 *
 * A. A utility to help convert types from one system to the next, i.e. casting.
 * B. A utility to help represent data from a system, i.e. formatting.
 *
 * Or C; both A and B, i.e. processing.
 */
class Converter implements \Serializable
{
    /**
     * @var \Sysgear\Converter\CasterInterface
     */
    protected $caster;

    /**
     * @var \Sysgear\Converter\FormatterInterface
     */
    protected $formatter;

    /**
     * Source timezone. This is the timezone of current values.
     *
     * Default: date_default_timezone_get
     *
     * @var \DateTimeZone
     */
    protected $srcTimezone;

    /**
     * Destination timezone. This is the timezone you cast values to.
     *
     * @var \DateTimeZone
     */
    protected $dstTimezone;

    /**
     * @deprecated
     */
    public $formatDatetime = \DATE_W3C;
    public $formatDate = 'Y-m-d';
    public $formatTime = 'H:i:s';

    /**
     * Create a new converter.
     *
     * @param CasterInterface $caster
     * @param FormatterInterface $formatter
     */
    public function __construct(CasterInterface $caster = null, FormatterInterface $formatter = null)
    {
        $this->srcTimezone = new \DateTimeZone('UTC');
        $this->dstTimezone = new \DateTimeZone(date_default_timezone_get());

        if (null === $caster) {
            $this->caster = new BuildCaster();
            $this->caster->useDefaultTypes();
        } else {
            $this->caster = $caster;
        }

        if (null === $formatter) {
            $this->formatter = new DefaultFormatter();
        } else {
            $this->formatter = $formatter;
        }
    }

    /**
     * Set caster.
     *
     * @param CasterInterface $caster
     * @return \Sysgear\Converter
     */
    public function setCaster(CasterInterface $caster)
    {
        $this->caster = $caster;
        return $this;
    }

    /**
     * Set source timzone. This is the timezone from which data is formatted / cast.
     *
     * @param \DateTimeZone $timezone
     * @return \Sysgear\Converter
     */
    public function setTimezoneSrc(\DateTimeZone $timezone)
    {
        $this->srcTimezone = $timezone;
        $this->caster->setTimezone($timezone);
        return $this;
    }

    /**
     * Return source timezone.
     *
     * @return \DateTimeZone
     */
    public function getTimezoneSrc()
    {
        return $this->srcTimezone;
    }

    /**
     * Set destination timzone. This is the timezone to which data needs to be formatted / cast.
     *
     * @param \DateTimeZone $timezone
     * @return \Sysgear\Converter
     */
    public function setTimezoneDest(\DateTimeZone $timezone)
    {
        $this->dstTimezone = $timezone;
        return $this;
    }

    /**
     * Return destination timezone.
     *
     * @return \DateTimeZone
     */
    public function getTimezoneDest()
    {
        return $this->dstTimezone;
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
            $value = $this->caster->cast($value, @$types[$field]);
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
        return $this->caster->cast($value, $type);
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
        foreach ($record as $field => $value) {
            $newRecord[$field] = $this->process($value, @$types[$field]);
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
        return $this->formatter->format($this->caster->cast($value, $type), $type);
    }

    /**
     * Format records.
     *
     * @deprecated
     * @param array $records Two dimensional array of rows and columns
     * @param array $dataTypes Date types to format indexed by column index
     */
    public function formatRecords(array &$records, array $dataTypes)
    {
        foreach ($records as &$record) {
            $this->formatRecord($record, $dataTypes);
        }
    }

    /**
     * Format record.
     *
     * @deprecated
     * @param array $record Array of columns
     * @param array $dataTypes Date types to format indexed by column index
     */
    public function formatRecord(array &$record, array $dataTypes)
    {
        foreach ($record as $i => &$field) {
            $this->formatValue($field, @$dataTypes[$i]);
        }
    }

    /**
     * Format value.
     *
     * @deprecated
     * @param array $value Value to format
     * @param array $dataType Date type to format to
     */
    public function formatValue(&$value, $dataType)
    {
        if (null === $value) {
            return;
        }

        switch ($dataType) {
            case Datatype::DATE:
                if (empty($value)) {
                    $value = null;

                } elseif ($value instanceof \DateTime) {
                    $value = $value->format($this->formatDate);

                } elseif (Datatype::TIME !== $this->getDateTimeType($value)) {
                    $date = new \DateTime($value, $this->srcTimezone);
                    $value = $date->format($this->formatDate);

                } else {
                    throw new \LogicException('Trying to format, something that looks like a ' .
                        Datatype::toDesc($this->getDateTimeType($value)) . ', as date');
                }
                break;

            case Datatype::DATETIME:
                if (empty($value)) {
                    $value = null;

                } elseif ($value instanceof \DateTime) {
                    $value->setTimezone($this->dstTimezone);
                    $value = $value->format($this->formatDatetime);

                } elseif (Datatype::DATETIME === $this->getDateTimeType($value)) {
                    $date = new \DateTime($value, $this->srcTimezone);
                    $date->setTimezone($this->dstTimezone);
                    $value = $date->format($this->formatDatetime);

                } else {
                    throw new \LogicException('Trying to format, something that looks like a ' .
                        Datatype::toDesc($this->getDateTimeType($value)) . ', as datetime');
                }
                break;

            case Datatype::TIME:
                if (empty($value)) {
                    $value = null;

                } elseif ($value instanceof \DateTime) {
                    $value = $value->format($this->formatTime);

                } else {
                    $dt = $this->getDateTimeType($value);
                    if (Datatype::DATE !== $dt) {
                        $date = new \DateTime($value, $this->srcTimezone);

                        // only change timezone if datetime is supplied, this
                        // should prevent unexpected DST calculations.
                        if (Datatype::DATETIME === $dt) {
                            $date->setTimezone($this->dstTimezone);
                        }
                        $value = $date->format($this->formatTime);

                    } else {
                        throw new \LogicException('Trying to format, something that looks like a ' .
                            Datatype::toDesc($dt) . ', as time');
                    }
                }
                break;
        }
    }

    /**
     * Format a specific date.
     *
     * @deprecated
     * @param \DateTime $date
     * @param integer $dataType
     * @return string
     */
    public function formatDate(\DateTime $date, $dataType = Datatype::DATETIME)
    {
        switch ($dataType) {
            case Datatype::DATE:
                $format = $this->formatDate;
                break;

            case Datatype::TIME:
                $format = $this->formatTime;
                break;

            default:
                $format = $this->formatDatetime;
                break;
        }

        return $date->setTimezone($this->dstTimezone)->format($format);
    }

    /**
     * Determine if $value is a datetime, date or time.
     *
     * @deprecated
     * @param string $value
     * @return integer Datetime, date or time contant
     */
    protected function getDateTimeType($value)
    {
        if (strlen($value) > 10) {
            return Datatype::DATETIME;
        }

        $match = null;
        preg_match('(^\d+-\d+-\d+|\d+:\d+(:\d+)?$)', trim($value), $match);
        return ($match && ':' === @$match[0][2]) ? Datatype::TIME : Datatype::DATE;
    }

    public function serialize()
    {
        return serialize(array(
            'caster' => $this->caster,
            'formatter' => $this->formatter,
            'srcTimezone' => $this->srcTimezone->getName(),
            'dstTimezone' => $this->dstTimezone->getName(),
            'formatDatetime' => $this->formatDatetime,
            'formatDate' => $this->formatDate,
            'formatTime' => $this->formatTime
        ));
    }

    public function unserialize($serialized)
    {
        $properties = unserialize($serialized);
        $this->caster = $properties['caster'];
        $this->formatter = $properties['formatter'];
        $this->srcTimezone = new \DateTimeZone($properties['srcTimezone']);
        $this->dstTimezone = new \DateTimeZone($properties['dstTimezone']);
        $this->formatDatetime = $properties['formatDatetime'];
        $this->formatDate = $properties['formatDate'];
        $this->formatTime = $properties['formatTime'];
    }
}