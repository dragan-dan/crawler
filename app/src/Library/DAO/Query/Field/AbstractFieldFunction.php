<?php
/**
 * Created by PhpStorm.
 * User: berkay
 * Date: 6/1/15
 * Time: 1:51 PM
 */

namespace Library\DAO\Query\Field;

abstract class AbstractFieldFunction
{
    /** @var  string */
    protected $name;

    /** @var  array */
    protected $parameters;

    /** @var  string */
    protected $field;

    public function __construct($name, $parameters = [])
    {
        $this->name       = $name;
        $this->parameters = $parameters;
    }

    /**
     * @param string $field
     */
    public function setField($field)
    {
        $this->field = $field;
    }

    abstract public function getStringValue($field);
}
