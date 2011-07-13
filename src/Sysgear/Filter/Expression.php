<?php

namespace Sysgear\Filter;

class Expression extends Filter
{
    protected $field;
    protected $operator;
    protected $value;

    public function __construct($field, $value, $operator = \Sysgear\Operator::EQUAL)
    {
        $this->field = $field;
        $this->value = $value;
        $this->operator = $operator;
    }

    /**
     * Gets the PHP array representation of this filter expression.
     *
     * @return array The PHP array representation of this filter expression.
     */
    public function toArray()
    {
        return array('F' => $this->field, 'V' => $this->value, 'O' => $this->operator);
    }

    /**
     * Sets the filter field.
     *
     * @param string $field The filter field.
     * @return \Sysgear\Filter\Expression
     */
    public function setField($field)
    {
        $this->field = $field;
        return $this;
    }

    /**
     * Gets the filter field.
     *
     * @return string The filter field value.
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * Sets the filter operator.
     *
     * @param int $field The filter operator.
     * @return \Sysgear\Filter\Expression
     */
    public function setOperator($operator)
    {
        $this->operator = $operator;
        return $this;
    }

    /**
     * Gets the filter operator.
     *
     * @return string The filter operator value.
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * Sets the filter value.
     *
     * @param string $field The filter value.
     * @return \Sysgear\Filter\Expression
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * Gets the filter value.
     *
     * @return string The filter value.
     */
    public function getValue()
    {
        return $this->value;
    }

    public function serialize()
    {
        return serialize(array(
        	'F' => $this->field,
        	'V' => $this->value,
        	'O' => $this->operator));
    }

    public function unserialize($data)
    {
        $data = unserialize($data);
        $this->field = $data['F'];
        $this->value = $data['V'];
        $this->operator = $data['O'];
    }
}