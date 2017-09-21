<?php
namespace Library\DAO\Postgre\Json\Query\Field;

use Library\DAO\Postgre\BaseDAO;
use Library\DAO\Query\Field\AbstractSortField;

class SortField extends AbstractSortField
{

    /**
     * @param string $name
     * @param string $direction
     */
    public function __construct($name, $direction = self::SORT_ASCENDING)
    {
        $this->name      = $name;
        $this->direction = $direction;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $jsonFieldName = $this->getProcessedFieldName();

        $sql = $jsonFieldName . ' ' . $this->direction;

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
        /** Add single quotes to individual keys */
        $splitFields = explode('.', $this->name);

        foreach ($splitFields as $i => &$keyPart) {
            $keyPart = "'$keyPart'";
        }

        // Add the column name before the field
        array_unshift($splitFields, BaseDAO::JSONB_FIELD_NAME);

        /** Convert JSON dot notation to PostgreSQL's -> and ->> **/
        $processedField = implode('->', $splitFields);

        // Convert the last -> to ->> to work with primitive data types
        $processedField = preg_replace('~->(?!.*->)~', '->>', $processedField);

        return $processedField;
    }

} 
