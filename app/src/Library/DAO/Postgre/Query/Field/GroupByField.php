<?php
namespace Library\DAO\Postgre\Query\Field;

use Library\DAO\Query\Field\AbstractFieldFunction;
use Library\DAO\Query\Field\AbstractGroupByField;

/**
 * Class GroupByField
 * @package Library\DAO\Postgre\Query\Field
 */
class GroupByField extends AbstractGroupByField
{
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

        $sql = $fieldName;

        return $sql;
    }

} 
