<?php

namespace Library\DAO\Postgre\Json\Query;
use Library\DAO\Postgre\Json\Query\Field\SelectField;
use Library\DAO\Postgre\Json\Query\Field\SortField;
use Library\DAO\Postgre\Json\Query\Field\WhereField;
use Library\DAO\Postgre\Json\Query\Field\WhereOrField;
use Library\DAO\Postgre\Query\PostgresQuery;
use Library\DAO\Query\Field\AbstractFieldFunction;
use Library\DAO\Query\Field\AbstractSortField;
use Library\DAO\Query\Field\WhereFieldInterface;
use Library\DAO\Query\QueryInterface;

/**
 * Abstract PostgresQuery object. This is the skeleton object for a DB query.
 *
 * Class PostgresQuery
 *
 * @package Library\DAO\Postgre\PostgresQuery
 */
class PostgresJSONQuery extends PostgresQuery
{
    /**
     * Adds a new condition
     *
     * @param string                  $name
     * @param mixed                   $criteriaValue
     * @param string                  $operator
     * @param string                  $fieldType
     * @param string                  $criteriaField
     * @param AbstractFieldFunction[] $functions
     *
     * @return $this
     */
    public function addCondition(
        $name, $criteriaValue, $operator = '=', $fieldType = 'primitive', $criteriaField = '', $functions = []
    ) {
        if (is_null($this->conditions)) {
            $this->conditions = [];
        }

        $this->conditions[] = new WhereField(
            $name, $criteriaValue, $operator, $fieldType, $criteriaField, $functions
        );

        return $this;
    }

    /**
     * @param WhereFieldInterface[] $whereFields
     *
     * @return QueryInterface
     */
    public function addConditionOr($whereFields)
    {
        if (is_null($this->conditions)) {
            $this->conditions = [];
        }

        $this->conditions[] = new WhereOrField($whereFields);

        return $this;
    }

    /**
     * Adds a new select field
     *
     * @param string $name
     * @param string $alias
     * @param string $fieldType
     * @param array  $functions
     *
     * @return QueryInterface
     */
    public function addSelectField($name, $alias = '', $fieldType = self::TYPE_PRIM, $functions = [])
    {
        if (is_null($this->selectFields)) {
            $this->selectFields = [];
        }

        $this->selectFields[] = new SelectField($name, $alias, $fieldType, $functions);

        return $this;
    }

    /**
     * Adds a sort field
     *
     * @param string $name
     * @param string $direction
     *
     * @return QueryInterface
     */
    public function addSort($name, $direction = AbstractSortField::SORT_ASCENDING)
    {
        if (is_null($this->sorts)) {
            $this->sorts = [];
        }

        $this->sorts[] = new SortField($name, $direction);

        return $this;
    }


    /**
     * Form and return update query
     *
     * @param string $data - New json_encoded value of the field
     * @return string
     */
    public function getUpdateQuery($data)
    {
        $queryStr = 'UPDATE ';

        $queryStr .= $this->table;

        // Set Fields
        $queryStr .= ' SET ' . self::JSONB_FIELD_NAME . '=\'' . pg_escape_string($data) . '\' ';

        // PostgresQuery
        $conditionsSQL = '';
        if (!empty($this->conditions)) {
            $conditionsSQL = ' WHERE ' . implode(' AND ', $this->conditions);
        }
        $queryStr .= $conditionsSQL;

        $queryStr = $this->addParameterPlaceholders($queryStr);

        return $queryStr;
    }

    /**
     * @return mixed
     */
    public function getDeleteQuery()
    {
        $queryStr = 'DELETE FROM ' . $this->table;

        $conditionsSQL = '';
        if ($this->conditions) {
            $conditionsSQL = ' WHERE ' . implode(' AND ', $this->conditions);
        }
        $queryStr .= $conditionsSQL;

        $queryStr = $this->addParameterPlaceholders($queryStr);

        return $queryStr;

    }

    /**
     * @return mixed
     */
    public function getInsertQuery()
    {
        $insertDataJson = json_encode($this->insertData);

        $queryStr = 'INSERT INTO ' . $this->table
            . ' VALUES (\'' . pg_escape_string($insertDataJson) . '\')';

        return $queryStr;
    }

    /**
     * @return string
     */
    public function getBulkInsertQuery()
    {
        $inserts = [];
        foreach ($this->insertData as $key => $data) {
            $insertDataJson = pg_escape_string(json_encode($data));
            $inserts[] = ' (\'' . $insertDataJson . '\')';
        }

        $queryStr = 'INSERT INTO ' . $this->table
            .' VALUES ' . implode(', ', $inserts);

        return $queryStr;
    }

}
