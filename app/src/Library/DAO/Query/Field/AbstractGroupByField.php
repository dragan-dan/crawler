<?php

namespace Library\DAO\Query\Field;

use Library\DAO\Postgre\BaseDAO;

/**
 * Class AbstractGroupByField
 * @package Library\DAO\Query\Field
 */
abstract class AbstractGroupByField
{
    /**
     * AbstractGroupByField name, a '.' separated list of field names. '.' goes into deeper levels in JSON field
     * @var string
     */
    public $name;

    /**
     * Functions to apply to field
     *
     * @var array
     */
    public $functions;

    /**
     * The type of the field value. The
     * @var string
     */
    public $fieldType;

    /**
     * @param        $name
     * @param string $fieldType
     * @param        $functions
     */
    public function __construct($name, $fieldType = BaseDAO::TYPE_PRIM, $functions = [])
    {
        $this->name      = $name;
        $this->fieldType = $fieldType;
        $this->functions = $functions;
    }

    abstract public function __toString();

    public function getBindParams()
    {
        return [];
    }

    /**
     * @return string
     */
    public function getFieldType()
    {
        return $this->fieldType;
    }

}
