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

use Sysgear\Converter\CasterInterface;
use Sysgear\Converter\BuildCaster;

/**
 * Utility to convert and format values with certain data types.
 *
 * Parsing and formatting is weak typed, dateTime
 * values can be formatted as datetime, date and time.
 *
 * One must take into account that not all types can be cast. For example,
 * date and time can never be cast to a datetime type. Date cannot
 * supply time information and time cannot supply date information.
 */
class Converter implements \Serializable
{
    /**
     * @var \Sysgear\Converter\CasterInterface
     */
    protected $caster;

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

    public $formatDatetime = \DATE_W3C;
    public $formatDate = 'Y-m-d';
    public $formatTime = 'H:i:s';

    /**
     * Create a new converter.
     *
     * @param CasterInterface $caster
     */
    public function __construct(CasterInterface $caster = null)
    {
        $this->srcTimezone = new \DateTimeZone('UTC');
        $this->dstTimezone = new \DateTimeZone(date_default_timezone_get());

        if (null === $caster) {
            $this->caster = new BuildCaster();
            $this->caster->useDefaultTypes();
        } else {
            $this->caster = $caster;
        }
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
     * Process a records.
     *
     * @param array $records
     * @param array $types
     */
    public function processRecords(array &$records, array $types)
    {
        foreach ($records as &$record) {
            $this->processRecord($record, $types);
        }
    }

    /**
     * Process a record.
     *
     * @param array $record
     * @param array $types
     */
    public function processRecord(array &$record, array $types)
    {
        foreach ($record as $field => &$value) {
            $value = $this->caster->cast(@$types[$field], $value);
        }
    }

    /**
     * Format records.
     *
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
            'srcTimezone' => $this->srcTimezone,
            'dstTimezone' => $this->dstTimezone,
            'formatDatetime' => $this->formatDatetime,
            'formatDate' => $this->formatDate,
            'formattime' => $this->formatTime
        ));
    }

    public function unserialize($serialized)
    {
        foreach (unserialize($serialized) as $property => $value) {
            $this->{$property} = $value;
        }
    }
}