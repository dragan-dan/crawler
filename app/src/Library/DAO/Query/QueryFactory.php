<?php

namespace Library\DAO\Query;

use Library\DAO\Postgre\Json\Query\PostgresJSONQuery;
use Library\DAO\Postgre\Query\Field\FieldFunction;
use Library\DAO\Postgre\Query\PostgresQuery;
use Library\DAO\Query\Field\AbstractFieldFunction;
use \Library\DAO\Postgre\Query\Field\WhereField as RelationalWhereField;
use \Library\DAO\Postgre\Json\Query\Field\WhereField as JsonWhereField;

/**
 * Class QueryFactory - Factory for creating query objects
 *
 * @package Library\DAO\Query
 */
class QueryFactory
{
    const TYPE_RELATIONAL = 'relational';
    const TYPE_DOCUMENT   = 'document';

    /**
     * @param string $tableName
     * @param string $type
     *
     * @return QueryInterface
     */
    public static function create($tableName = '', $type = self::TYPE_DOCUMENT)
    {
        if ($type == self::TYPE_RELATIONAL) {
            $query = new PostgresQuery($tableName);
        }  else {
            $query = new PostgresJSONQuery($tableName);
        }

        return $query;
    }

    /**
     * @param string                  $type
     * @param                         $name
     * @param                         $criteriaValue
     * @param string                  $operator
     * @param string                  $fieldType
     * @param string                  $criteriaField
     * @param AbstractFieldFunction[] $functions
     *
     * @return \Library\DAO\Postgre\Json\Query\Field\WhereField|\Library\DAO\Postgre\Query\Field\WhereField
     */
    public static function createWhereField(
        $type = self::TYPE_DOCUMENT,
        $name,
        $criteriaValue,
        $operator = '=',
        $fieldType = 'primitive',
        $criteriaField = '',
        $functions = []
    ) {
        switch ($type) {
            case self::TYPE_RELATIONAL:
                $whereField = new RelationalWhereField(
                    $name, $criteriaValue, $operator, $fieldType, $criteriaField, $functions
                );
                break;
            case self::TYPE_DOCUMENT: // also the default
            default:
                $whereField = new JsonWhereField(
                    $name, $criteriaValue, $operator, $fieldType, $criteriaField, $functions
                );
                break;
        }

        return $whereField;
    }

    /**
     * Creates a new field function
     *
     * @param $name
     * @param $parameters
     *
     * @return FieldFunction
     */
    public static function createFieldFunction($name, $parameters)
    {
        return new FieldFunction($name, $parameters);
    }
}
