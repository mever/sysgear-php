<?php

namespace Sysgear;

class DatetimeFormatter
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

    public $formatDate = 'Y-m-d';
    public $formatTime = 'H:i:s';
    public $formatDatetime = \DATE_W3C;

    /**
     * Create a new date/time formatter.
     */
    public function __construct()
    {
        $this->srcTimezone = date_default_timezone_get();
    }

    /**
     * Cast date, time and datetime fields in records to another timezone and/or formatting.
     *
     * @param array $records Two dimensional array of rows and columns
     * @param array $datatypes Date types to cast indexed by column index
     * @param string $toTimezone Timezone to cast to
     * @param map $formats Map of specified formats. When absent use formatting specified with the datatype. Accepts:
     * - date: date format (default: Y-m-d) (see: http://nl2.php.net/manual/en/function.date.php)
     * - time: time format (default: H:i:s) (see: http://nl2.php.net/manual/en/function.date.php)
     * - datetime: datetime format (default: DATE_W3C) (see: http://nl2.php.net/manual/en/function.date.php)
     *
     * @throws \Exception
     */
    public function castRecords(array &$records, array $datatypes, $toTimezone = null, array $formats = array())
    {
        // specify timezones
        $srcTz = $this->srcTimezone;
        $dstTz = $toTimezone ?: $this->dstTimezone;

        // specify formats
        $format = new \stdClass();
        $format->date = array_key_exists('date', $formats) ? $formats['date'] : $this->formatDate;
        $format->time = array_key_exists('time', $formats) ? $formats['time'] : $this->formatTime;
        $format->datetime = array_key_exists('datetime', $formats) ? $formats['datetime'] : $this->formatDatetime;

        // cast function
        $cast = function($cellValue, $dt) use ($format, $srcTz, $dstTz) {
            switch ($dt) {
                case Datatype::DATE:
                    $date = new \DateTime($cellValue, new \DateTimeZone($srcTz));
                    return $date->format($format->date);

                case Datatype::DATETIME:
                    $date = new \DateTime($cellValue, new \DateTimeZone($srcTz));
                    $date->setTimezone(new \DateTimeZone($dstTz));
                    return $date->format($format->datetime);

                case Datatype::TIME:
                    $date = new \DateTime($cellValue, new \DateTimeZone($srcTz));
                    $date->setTimezone(new \DateTimeZone($dstTz));
                    return $date->format($format->time);

                default:
                    throw new \Exception("Can not cast date, time or datetime");
            }
        };

        // perform cast
        foreach ($records as &$record) {
            foreach ($datatypes as $idx => $dt) {
                $record[$idx] = $cast($record[$idx], $dt);
            }
        }

        unset($record);
    }
}