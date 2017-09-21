<?php

namespace Library\DAO\Query\Field;

use Library\DAO\Postgre\BaseDAO;
use Library\DAO\Postgre\Query\PostgresQuery;

class AbstractWhereField implements WhereFieldInterface
{
    /**
     * AbstractSelectField name, a '.' separated list of field names. '.' goes into deeper levels in JSON field
     * @var string
     */
    public $name;

    /**
     * Functions to apply to field
     *
     * @var array
     */
    public $operator;

    /**
     * @var mixed
     */
    public $criteriaValue;

    /**
     * Name of the field in condition. This is used if an array comparison is made (so the value should be an array
     *
     * @var string
     */
    public $criteriaField;

    /**
     * The type of the field value. The
     * @var string
     */
    public $fieldType;

    /**
     * @var array
     */
    public $functions;

    /**
     * @param                         $name
     * @param                         $criteriaValue
     * @param string                  $operator
     * @param string                  $fieldType
     * @param string                  $criteriaField
     * @param AbstractFieldFunction[] $functions
     */
    public function __construct(
        $name,
        $criteriaValue,
        $operator = BaseDAO::OP_EQUALS,
        $fieldType = PostgresQuery::TYPE_PRIM,
        $criteriaField = '',
        $functions = []
    ) {
        $this->name          = $name;
        $this->operator      = $operator;
        $this->criteriaValue = $criteriaValue;
        $this->criteriaField = $criteriaField;
        $this->fieldType     = $fieldType;
        $this->functions     = $functions;
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

        // Default place holder for fieldValue.
        $fieldValue = '{?}';

        $criteriaValue = $this->criteriaValue;

        // If this is of value type
        if ($criteriaValue instanceof Value) {

            $value     = $criteriaValue->getValue();
            $functions = $criteriaValue->getFunctions();

            foreach ($functions as $function) {
                if ($function instanceof AbstractFieldFunction) {
                    $fieldValue = $function->getStringValue($fieldValue);
                }
            }

            // overwriting $criteriaValue
            $criteriaValue = $value;
        }

        if (is_null($criteriaValue)) {
            // If null, then we don't use bind variables because of an error in Postgres PHP driver
            $fieldValue = 'NULL';
        } else if (is_array($criteriaValue)) {
            // repeat the fieldValue
            $fieldValue = '(' .  rtrim(str_repeat("$fieldValue,", count($criteriaValue)), ',') . ')';
        }

        $sql = '(' . $fieldName . ') ' . $this->operator . ' ' . $fieldValue;
        return $sql;
    }

    /**
     * Returns an array of bind parameters. Does not support nested arrays.
     *
     * @return array
     */
    public function getBindParam()
    {
        $bindParams = [];
        $bindValue  = null;

        $criteriaValue = $this->criteriaValue;

        if ($this->criteriaValue instanceof Value) {
            $criteriaValue = $this->criteriaValue->getValue();
        }

        if (is_array($criteriaValue)) {
            foreach ($criteriaValue as $value) {
                $bindParams[] = $value;
            }
        } else {
            $bindParams[] = $criteriaValue;
        }

        // due to a PostgrePHP driver issue, null values are not accepted as variable
        // params and throws ERROR
        $bindParams = array_filter($bindParams, function($param) {
            return !is_null($param);
        });

        return $bindParams;
    }
} 
