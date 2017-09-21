<?php
namespace Library\DAO\Postgre\Query\Field;

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
        $fieldName = $this->name;

        $sql = $fieldName . ' ' . $this->direction;

        return $sql;
    }

} 
