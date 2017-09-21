<?php
namespace Library\DAO\Postgre\Query\Field;

use Library\DAO\Query\Field\AbstractFieldFunction;

class FieldFunction extends AbstractFieldFunction
{
    /**
     * Gets the string query for a function.
     *
     * If $this->parameters array has 'self' in it, then self will be replaced by $field
     * It has support for CAST function, since CAST function syntax is different than traditional functions
     *
     * @param $field
     *
     * @return string
     */
    public function getStringValue($field)
    {
        $this->field = $field;

        $functionName = $this->name;

        if (is_array($this->parameters) && !empty($this->parameters)) {
            $functionParameters    = $this->parameters;
            $functionParametersStr = implode(',', $functionParameters);
            $functionParametersStr = str_replace('self', $this->field, $functionParametersStr);

            // Special case for #> operator
            if ($functionName == '#>') {
                $jsonFieldName = $this->field . ' #> \'{' . $functionParametersStr . '}\'';
            } else if ($functionName == 'CAST') {
                $functionParametersStr = "$field AS $functionParameters[0]";
                $jsonFieldName = $functionName . '(' . $functionParametersStr . ')';
            } else {
                $jsonFieldName = $functionName . '(' . $functionParametersStr . ')';
            }

        } else {
            $jsonFieldName = $functionName . '(' . $this->field . ')';
        }

        return $jsonFieldName;
    }

}
