<?php
namespace Library\DAO\Postgre\Query\Field;

use Library\DAO\Postgre\Query\PostgresQuery;
use Library\DAO\Query\Field\AbstractSelectField;
use Library\DAO\Query\Field\AbstractFieldFunction;

/**
 * Class AbstractSelectField - Represents a select field in DB PostgresQuery
 *
 * @package Library\DAO\Postgre\PostgresQuery
 */
class SelectField extends AbstractSelectField
{
    /**
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
     * @param        $name
     * @param        $alias
     * @param string $fieldType
     * @param        $functions
     */
    public function __construct($name, $alias = null, $fieldType = PostgresQuery::TYPE_PRIM, $functions = [])
    {
        if (!$alias) {
            $alias = $name;
        }

        $this->name      = $name;
        $this->alias     = $alias;
        $this->fieldType = $fieldType;
        $this->functions = $functions;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $fieldName = $this->name;

        foreach ($this->functions as $functionData) {
            if ($functionData instanceof AbstractFieldFunction) {
                $fieldName = $functionData->getStringValue($fieldName);
            }
        }

        $sql = $fieldName . ' AS ' . $this->alias;

        return $sql;
    }
}

