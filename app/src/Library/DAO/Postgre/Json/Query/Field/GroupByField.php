<?php
namespace Library\DAO\Postgre\Json\Query\Field;

use Library\DAO\Postgre\BaseDAO;
use Library\DAO\Postgre\Query\PostgresQuery;
use Library\DAO\Query\Field\AbstractFieldFunction;
use Library\DAO\Query\Field\AbstractGroupByField;

/**
 * Class AbstractGroupByField - Represents a group by field in DB PostgresQuery
 *
 * @package Library\DAO\Postgre\PostgresQuery
 */
class GroupByField extends AbstractGroupByField
{
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

        $sql = $jsonFieldName;

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

