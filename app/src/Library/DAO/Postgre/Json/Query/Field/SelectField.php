<?php
namespace Library\DAO\Postgre\Json\Query\Field;

use Library\DAO\Postgre\BaseDAO;
use Library\DAO\Postgre\Query\PostgresQuery;
use Library\DAO\Query\Field\AbstractFieldFunction;
use Library\DAO\Query\Field\AbstractSelectField;

/**
 * Class AbstractSelectField - Represents a select field in DB PostgresQuery
 *
 * @package Library\DAO\Postgre\PostgresQuery
 */
class SelectField extends AbstractSelectField
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
    public $functions;

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
        $jsonFieldName = $this->getProcessedFieldName();

        foreach ($this->functions as $functionData) {

            if ($functionData instanceof AbstractFieldFunction) {
                $jsonFieldName = $functionData->getStringValue($jsonFieldName);
            }

        }

        $sql = $jsonFieldName . ' AS ' . $this->alias;

        return $sql;
    }

    /**
     * Get the processed fieldName that is appended by column name of the JSONB field and separated
     * by JSON operators (->, ->>)
     *
     * @return string
     */
    private function getProcessedFieldName()
    {
        if (BaseDAO::JSONB_FIELD_NAME === $this->name) {
            return $this->name;
        }

        /** Add single quotes to individual keys */
        $splitFields = explode('.', $this->name);

        foreach ($splitFields as $i => &$keyPart) {
            $keyPart = "'" . $keyPart . "'";
        }

        // Add the column name before the field
        array_unshift($splitFields, BaseDAO::JSONB_FIELD_NAME);

        /** Convert JSON dot notation to PostgreSQL's -> and ->> **/
        $processedField = implode('->', $splitFields);

        if (in_array($this->fieldType, [PostgresQuery::TYPE_PRIM])) {
            // Convert the last -> to ->> to work with primitive data types
            $processedField = preg_replace('~->(?!.*->)~', '->>', $processedField);
        }

        return $processedField;
    }

}

