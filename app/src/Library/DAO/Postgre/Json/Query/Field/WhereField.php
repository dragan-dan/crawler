<?php
namespace Library\DAO\Postgre\Json\Query\Field;

use Library\DAO\Postgre\BaseDAO;
use Library\DAO\Query\Field\AbstractFieldFunction;
use Library\DAO\Query\Field\AbstractWhereField;
use Library\DAO\Query\Field\Value;

/**
 * Class WhereField
 * @package Library\DAO\Postgre\Json\Query\Field
 */
class WhereField extends AbstractWhereField
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

        $processedValue = $this->getProcessedValue();
        $jsonCriteria  = (null === $processedValue) ? ' NULL ' : $processedValue;

        $sql = '(' . $jsonFieldName . ') ' . $this->operator . ' ' . $jsonCriteria;
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
        $processedField = implode("->", $splitFields);

        if (!in_array($this->operator, [BaseDAO::OP_CONTAINS, BaseDAO::OP_EXISTS, BaseDAO::OP_EXISTS_OR, BaseDAO::OP_CONTAINS_INVERSE]))
        {
            // Convert the last -> to ->> to work with primitive data types
            $processedField = preg_replace('~->(?!.*->)~', '->>', $processedField);
        }

        return $processedField;
    }

    /**
     * Process value to postgres document format
     *
     * @return mixed|string
     * @throws \Exception
     */
    private function getProcessedValue()
    {
        $criteriaValue = $this->criteriaValue;

        // Default place holder for fieldValue
        $fieldValue = '{?}';

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

        if (is_array($criteriaValue)) {
            $criteriaValue = rtrim(str_repeat("$fieldValue,", count($criteriaValue)), ',');
            if (in_array($this->operator, [BaseDAO::OP_EXISTS, BaseDAO::OP_EXISTS_OR, BaseDAO::OP_EXISTS_AND])) {
                // In case of json operators add array
                $criteriaValue = 'array[' . $criteriaValue . ']';
            } else {
                $criteriaValue = '(' . $criteriaValue . ')';
            }
        } else if (is_string($criteriaValue) || is_integer($criteriaValue)) {
            $criteriaValue = " $fieldValue ";
        }

        if ($this->criteriaField) {
            $criteriaValue = '\'[{"' . $this->criteriaField . '": ' . $fieldValue .' }]\'';
        }

        return $criteriaValue;
    }

} 
