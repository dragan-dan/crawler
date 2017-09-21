<?php

namespace Library\DAO\Query\Field;

/**
 * Class AbstractSelectField
 * @package Library\DAO\Query\Field
 */
abstract class AbstractSelectField
{
    /**
     * Alias of the select field
     *
     * @var string
     */
    public $alias;

    /**
     * The type of the field value. The
     * @var string
     */
    public $fieldType;

    abstract public function __toString();

    public function getBindParams()
    {
        return [];
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @return string
     */
    public function getFieldType()
    {
        return $this->fieldType;
    }

}
