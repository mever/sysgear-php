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

/**
 * Utility to format values with certain data types.
 *
 * Parsing and formatting is weak typed, dateTime
 * values can be formatted as datetime, date and time.
 *
 * One must take into account that not all types can be cast. For example,
 * date and time can never be cast to a datetime type. Date cannot
 * supply time information and time cannot supply date information.
 */
class Formatter
{
    /**
     * Source timezone. This is the timezone of current values.
     *
     * Default: date_default_timezone_get
     *
     * @var string
     */
    public $srcTimezone;

    /**
     * Destination timezone. This is the timezone you cast values to.
     *
     * @var string
     */
    public $dstTimezone = 'Zulu';

    public $formatDatetime = \DATE_W3C;
    public $formatDate = 'Y-m-d';
    public $formatTime = 'H:i:s';

    /**
     * Create a new date/time formatter.
     */
    public function __construct()
    {
        $this->srcTimezone = date_default_timezone_get();
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
                } elseif (Datatype::TIME !== $this->getDateTimeType($value)) {
                    $date = new \DateTime($value, new \DateTimeZone($this->srcTimezone));
                    $value = $date->format($this->formatDate);
                } else {
                    throw new \LogicException('Trying to format, something that looks like a ' .
                        Datatype::toDesc($this->getDateTimeType($value)) . ', as date');
                }
                break;

            case Datatype::DATETIME:
                if (empty($value)) {
                    $value = null;
                } elseif (Datatype::DATETIME === $this->getDateTimeType($value)) {
                    $date = new \DateTime($value, new \DateTimeZone($this->srcTimezone));
                    $date->setTimezone(new \DateTimeZone($this->dstTimezone));
                    $value = $date->format($this->formatDatetime);
                } else {
                    throw new \LogicException('Trying to format, something that looks like a ' .
                        Datatype::toDesc($this->getDateTimeType($value)) . ', as datetime');
                }
                break;

            case Datatype::TIME:
                $dt = $this->getDateTimeType($value);
                if (empty($value)) {
                    $value = null;
                } elseif (Datatype::DATE !== $dt) {
                    $date = new \DateTime($value, new \DateTimeZone($this->srcTimezone));
                    if (Datatype::DATETIME === $dt) {
                        $date->setTimezone(new \DateTimeZone($this->dstTimezone));
                    }
                    $value = $date->format($this->formatTime);
                } else {
                    throw new \LogicException('Trying to format, something that looks like a ' .
                        Datatype::toDesc($dt) . ', as time');
                }
                break;
        }
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
}