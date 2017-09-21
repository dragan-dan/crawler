<?php

namespace Library\DAO\Query;

use Library\DAO\Query\Field\AbstractFieldFunction;
use Library\DAO\Query\Field\WhereFieldInterface;

/**
 * Interface QueryInterface
 * @package Library\DAO\Query
 */
interface QueryInterface
{
    /**
     * @param                         $name
     * @param                         $criteriaValue
     * @param                         $operator
     * @param                         $fieldType
     * @param                         $criteriaField
     * @param AbstractFieldFunction[] $functions
     *
     * @return QueryInterface
     */
    public function addCondition($name, $criteriaValue, $operator = null, $fieldType = null, $criteriaField = null, $functions = []);

    /**
     * @param WhereFieldInterface[] $whereFields
     *
     * @return QueryInterface
     */
    public function addConditionOr($whereFields);

    /**
     * @param $condition
     *
     * @return QueryInterface
     */
    public function addConditionObject($condition);

    /**
      * @param $alias
     * @param $fieldType
     * @param $functions
     *
     * @return QueryInterface
     */
    public function addSelectField($name, $alias = null, $fieldType = null, $functions = []);

    /**
     * @param $selectField
     *
     * @return QueryInterface
     */
    public function addSelectFieldObject($selectField);

    /**
     * @param QueryInterface $query
     * @param $alias
     *
     * @return QueryInterface
     */
    public function addInnerSelectQuery($query, $alias);

    /**
     * @param $name
     * @param $direction
     *
     * @return QueryInterface
     */
    public function addSort($name, $direction = null);

    /**
     * @param $sort
     *
     * @return QueryInterface
     */
    public function addSortObject($sort);

    /**
     * @param array $fields
     *
     * @return QueryInterface
     */
    public function setGroupBy($fields);


    /**
     * @param $newData
     *
     * @return QueryInterface
     */
    public function setUpdateData($newData);

    /**
     * @param $newData
     *
     * @return QueryInterface
     */
    public function setInsertData($newData);

    /**
     * @param int $limit
     *
     * @return QueryInterface
     */
    public function setLimit($limit);

    /**
     * @param int $offset
     *
     * @return QueryInterface
     */
    public function setOffset($offset);

    /**
     * @param string $query
     *
     * @return QueryInterface
     */
    public function setStringQuery($query);

    /**
     * @param $field
     * @param $value
     *
     * @return QueryInterface
     */
    public function addUpdateData($field, $value);

    /**
     * Gets the bind parameters looking at the conditions
     *
     * @return mixed
     */
    public function getBindParams();

    /**
     * Adds the placeholders for bind params
     *
     * @param $queryStr
     *
     * @return mixed
     */
    public function addParameterPlaceholders($queryStr);

    /**
     * @param boolean $bindParams
     *
     * @return mixed
     */
    public function getSelectQuery($bindParams = true);

    /**
     * @param $data
     *
     * @return mixed
     */
    public function getUpdateQuery($data);

    /**
     * @return mixed
     */
    public function getDeleteQuery();

    /**
     * @return mixed
     */
    public function getInsertQuery();

    /**
     * Returns select fields
     * @return mixed
     */
    public function getSelectFields();

    /**
     * Gets update data
     * @return mixed
     */
    public function getUpdateData();

    /**
     * get query for bulk insert
     *
     * @return string
     */
    public function getBulkInsertQuery();

    /**
     * @return string
     */
    public function getBeginTransactionQuery();

    /**
     * @return string
     */
    public function getCommitTransactionQuery();

    /**
     * @return string
     */
    public function getRollbackTransactionQuery();
}
