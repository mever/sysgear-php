<?php

namespace Sysgear;

use Sysgear\Filter\Expression;
use Sysgear\Filter\Collection;
use Sysgear\Filter\Filter;

/**
 * Source of data.
 *
 * With {@link setDatabase()} the context of the datasource is determined.
 *
 * For example:
 * A database instance, XML file, CSV file, Unix pipe, TCP/IP socket, etc...
 */
class DataSource implements \Serializable
{
    /**
     * The protocol used to exchange data: http, ftp, file, getData, etc...
     *
     * @var string
     */
    protected $protocol;

    /**
     * The data domain or unit of data to exchange.
     *
     * @var string
     */
    protected $dataUnit;

    /**
     * May be any object implementing \Serializable
     * to determine the context of the datasource.
     *
     * E.g.: A database instance.
     *
     * @var \Serializable
     */
    protected $context;

    /**
     * @var \Sysgear\Filter\Collection
     */
    protected $filters;

    /**
     * @var array
     */
    protected $options = array();

    /**
     * Construct a new datasource.
     *
     * @param string $id
     */
    public function __construct($id)
    {
        $this->filters = new Collection();
        $this->setId($id);
    }

    /**
     * Set datasource id.
     *
     * @param string $id
     */
    public function setId($id)
    {
        list($this->protocol, $data) = explode('://', $id, 2);
        $sections = explode('/', $data, 2);

        if (! @empty($sections[0])) {
            $this->dataUnit = $sections[0];
        }

        if (! @empty($sections[1])) {
            $options = json_decode($sections[1], true);
            if (is_array($options)) {
                $this->options = $options;
            }
        }
    }

    /**
     * Return the protocol portion of the datasource.
     *
     * @return string
     */
    public function getProtocol()
    {
        return $this->protocol;
    }

    /**
     * Get datasource options.
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Set the data unit (or data domain).
     *
     * @param string $dataUnit
     */
    public function setDataUnit($dataUnit)
    {
        $this->dataUnit = $dataUnit;
    }

    /**
     * Return the data unit.
     *
     * @return string
     */
    public function getDataUnit()
    {
        return $this->dataUnit;
    }

    /**
     * Set filters.
     *
     * @param Collection $filter
     */
    public function setFilters(Collection $filters)
    {
        $this->filters = $filters;
    }

    /**
     * Return filters.
     *
     * @return \Sysgear\Filter\Collection
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * Set context.
     *
     * @param \Serializable $context
     */
    public function setContext(\Serializable $context)
    {
        $this->context = $context;
    }

    /**
     * Get context.
     *
     * @return \Serializable
     */
    public function getContext()
    {
        return $this->context;
    }

    public function serialize()
    {
        return serialize(array(
            'protocol' => $this->protocol,
            'dataUnit' => $this->dataUnit,
            'filters' => $this->filters,
            'options' => $this->options,
            'context' => $this->context));
    }

    public function unserialize($data)
    {
        $data = unserialize($data);
        $this->protocol = $data['protocol'];
        $this->dataUnit = $data['dataUnit'];
        $this->filters = $data['filters'];
        $this->options = $data['options'];
        $this->context = $data['context'];
    }
}