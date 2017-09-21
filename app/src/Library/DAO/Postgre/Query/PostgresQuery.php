<?php

namespace Library\DAO\Postgre\Query;

use Exceptions\NotImplementedException;
use Library\DAO\Postgre\Query\Field\WhereOrField;
use Library\DAO\Postgre\Query\Field\InnerQuery;
use Library\DAO\Postgre\Query\Field\SelectField;
use Library\DAO\Postgre\Query\Field\SortField;
use Library\DAO\Postgre\Query\Field\WhereField;
use Library\DAO\Query\Field\AbstractFieldFunction;
use Library\DAO\Query\Field\AbstractSelectField;
use Library\DAO\Query\Field\AbstractSortField;
use Library\DAO\Query\Field\WhereFieldInterface;
use Library\DAO\Query\QueryInterface;
use Library\Logger\Logger;

/**
 * Abstract PostgresQuery object. This is the skeleton object for a DB query.
 *
 * Class PostgresQuery
 *
 * @package Library\DAO\Postgre\PostgresQuery
 */
class PostgresQuery implements QueryInterface
{
    /** This is the name column that json data is stored */
    const JSONB_FIELD_NAME = 'data';

    /** PostgresQuery type 'Select' */
    const QUERY_TYPE_SELECT = "SELECT";

    // AbstractSelectField value types
    const TYPE_OBJECT = 'object';
    const TYPE_ARRAY  = 'array';
    const TYPE_PRIM   = 'primitive';

    const BEGIN_TRANSACTION = 'BEGIN';
    const COMMIT_TRANSACTION  = 'COMMIT';
    const ROLLBACK_TRANSACTION = 'ROLLBACK';

    /** @var string|InnerQuery - table to query */
    public $table;

    /** @var WhereFieldInterface[] */
    public $conditions;

    /** @var AbstractSelectField[] */
    public $selectFields;

    /** @var AbstractSortField[]  */
    public $sorts;

    /** @var string[] */
    public $groupBys;

    /** @var int  */
    public $limit;

    /** @var int  */
    public $offset;

    /** @var array */
    public $updateData;

    /** @var  array */
    protected $insertData;

    /** @var int - this is the incrementing index that is used to enumerate placeholders for bindParams */
    protected $placeholderIndex = 1;

    /** @var string */
    protected $query;

    /**
     * @param                       $table
     * @param WhereFieldInterface[]  $conditions
     * @param AbstractSelectField[] $selectFields
     * @param AbstractSortField[]   $sorts
     * @param int                   $limit
     * @param int                   $offset
     */
    public function __construct(
        $table,
        $conditions = null,
        $selectFields = null,
        $sorts = null,
        $limit = null,
        $offset = null
    ) {
        $this->table        = $table;
        $this->selectFields = $selectFields;
        $this->conditions   = $conditions;
        $this->sorts        = $sorts;
        $this->limit        = $limit;
        $this->offset       = $offset;

        return $this;
    }

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
     * @return QueryInterface
     */
    public function addCondition(
        $name,
        $criteriaValue,
        $operator = '=',
        $fieldType = 'primitive',
        $criteriaField = '',
        $functions = []
    ) {
        if (is_null($this->conditions)) {
            $this->conditions = [];
        }

        $this->conditions[] = new WhereField(
            $name, $criteriaValue, $operator, $fieldType, $criteriaField
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
     * @param WhereFieldInterface $condition
     *
     * @return QueryInterface - for chaining
     */
    public function addConditionObject($condition)
    {
        if (is_null($this->conditions)) {
            $this->conditions = [];
        }

        $this->conditions[] = $condition;

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
     * @param AbstractSelectField $selectField
     *
     * @return QueryInterface
     */
    public function addSelectFieldObject($selectField)
    {
        if (is_null($this->selectFields)) {
            $this->selectFields = [];
        }

        $this->selectFields[] = $selectField;

        return $this;
    }

    /**
     * @param QueryInterface $query
     * @param                $alias
     *
     * @return QueryInterface
     */
    public function addInnerSelectQuery($query, $alias)
    {
        if (is_null($this->selectFields)) {
            $this->selectFields = [];
        }

        $this->selectFields[] = new InnerQuery($query, $alias);

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
     * @param SortField $sort
     *
     * @return QueryInterface
     */
    public function addSortObject($sort)
    {
        if (is_null($this->sorts)) {
            $this->sorts = [];
        }

        $this->sorts[] = $sort;

        return $this;
    }

    /**
     * @param array $fields
     *
     * @return QueryInterface
     */
    public function setGroupBy($fields)
    {
        $this->groupBys = $fields;

        return $this;
    }

    /**
     * @param array $newData
     *
     * @return QueryInterface
     */
    public function setUpdateData($newData)
    {
        $this->updateData = $newData;

        return $this;
    }

    /**
     * @param $newData
     *
     * @return QueryInterface
     */
    public function setInsertData($newData)
    {
        $this->insertData = $newData;

        return $this;
    }

    /**
     * @param int $limit
     *
     * @return QueryInterface
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * @param int $offset
     *
     * @return QueryInterface
     */
    public function setOffset($offset)
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * @param string $query
     *
     * @return QueryInterface
     */
    public function setStringQuery($query)
    {
        $this->query = $query;

        return $this;
    }

    /**
     * @param string $field
     * @param string $value
     *
     * @return QueryInterface
     */
    public function addUpdateData($field, $value)
    {
        if (is_null($this->updateData)) {
            $this->updateData = [];
        }

        $this->updateData[$field] = $value;

        return $this;
    }

    /**
     * Gets the bind parameters looking at the conditions
     *
     * @return array
     */
    public function getBindParams()
    {
        $allBindParams = [];

        foreach ((array)$this->selectFields as $innerSelectQuery) {
            /** @var AbstractSelectField $innerSelectQuery */
            $bindParams = $innerSelectQuery->getBindParams();
            foreach ($bindParams as $param) {
                $allBindParams[] = $param;
            }
        }

        foreach ((array)$this->conditions as $condition) {
            /** @var WhereField $condition */
            $bindParams = $condition->getBindParam();
            foreach ($bindParams as $param) {
                $allBindParams[] = $param;
            }
        }

        return $allBindParams;
    }

    /**
     * Adds the placeholders for bind params
     *
     * @param string $queryStr
     *
     * @return mixed
     */
    public function addParameterPlaceholders($queryStr)
    {
        // Mark placeholders
        $this->placeholderIndex = 1;
        return preg_replace_callback(
            '/\{\?\}/',
            array($this, 'callbackReplacePlaceHolders')
            , $queryStr
        );
    }

    /**
     * Callback function that is used to replace placeholders with index numbers
     *
     * @param $e
     *
     * @return string
     */
    protected function callbackReplacePlaceHolders($e)
    {
        return '$' . $this->placeholderIndex++;
    }

    /**
     * Form and return select query
     *
     * @param bool $bindParams
     * @return string
     */
    public function getSelectQuery($bindParams = true)
    {
        if ($this->query) return $this->query;

        $queryStr = 'SELECT ';

        // Select Fields
        $selectQuerySQL = '';
        if (!$this->selectFields) {
            $fieldsSQL = ' * ';
        } else {
            $fieldsSQL = implode(',', $this->selectFields);
        }

        $queryStr .= $fieldsSQL;

        $queryStr .= $selectQuerySQL;

        $queryStr .= ' FROM ' . $this->table;

        // PostgresQuery
        $conditionsSQL = "";
        if ($this->conditions) {
            $conditionsSQL = ' WHERE ' . implode(' AND ', $this->conditions);
        }
        $queryStr .= $conditionsSQL;

        $groupBySQL = "";
        if ($this->groupBys) {
            $groupBySQL = ' GROUP BY ' . implode(', ', $this->groupBys);
        }
        $queryStr .= $groupBySQL;

        // Sort
        $sortSQL = '';
        if ($this->sorts) {
            $sortSQL = ' ORDER BY ' . implode(',', $this->sorts);
        }
        $queryStr .= $sortSQL;

        if ($this->limit) {
            $queryStr .= ' LIMIT ' . $this->limit;
        }

        if ($this->offset) {
            $queryStr .= ' OFFSET ' . $this->offset;
        }

        if ($bindParams) {
            $queryStr = $this->addParameterPlaceholders($queryStr);
        }

        return $queryStr;
    }

    public function getUpdateQuery($data)
    {
        throw new NotImplementedException('getUpdateQuery');
    }

    public function getDeleteQuery()
    {
        throw new NotImplementedException('getDeleteQuery');

    }

    public function getInsertQuery()
    {
        $this->insertData = array_filter($this->insertData);
        $insertDataKeys   = array_map('pg_escape_string', array_keys($this->insertData));
        $insertDataValues = array_map('pg_escape_string', array_values($this->insertData));

        $queryStr = 'INSERT INTO ' . $this->table
                    . '(' . implode(',', $insertDataKeys) . ')'
                    . ' VALUES (\'' . implode('\',\'', $insertDataValues) . '\')';

        return $queryStr;
    }

    /**
     * Returns select fields
     *
     * @return mixed
     */
    public function getSelectFields()
    {
        return $this->selectFields;
    }

    /**
     * Gets update data
     *
     * @return mixed
     */
    public function getUpdateData()
    {
        return $this->updateData;
    }

    /**
     * get query for bulk insert
     *
     * @return string
     */
    public function getBulkInsertQuery()
    {
        if (count($this->insertData) == 0) {
            return '';
        }
        foreach ($this->insertData as $key => $data) {
            $this->insertData[$key] = array_filter($data);
        }

        $firstElement = current($this->insertData);
        $insertDataKeys = array_map('pg_escape_string', array_keys($firstElement));
        $inserts = [];
        foreach ($this->insertData as $data) {
            $insertDataValues = array_map('pg_escape_string', array_values($data));
            $inserts[] = ' (\'' . implode('\',\'', $insertDataValues) . '\')';
        }
        $queryStr = 'INSERT INTO ' . $this->table
            . '(\'' . implode(',', $insertDataKeys) . '\')'
            . ' VALUES '.implode(',', $inserts);
        return $queryStr;
    }

    /**
     * @return string
     */
    public function getBeginTransactionQuery()
    {
        return self::BEGIN_TRANSACTION;
    }

    /**
     * @return string
     */
    public function getCommitTransactionQuery()
    {
        return self::COMMIT_TRANSACTION;
    }

    /**
     * @return string
     */
    public function getRollbackTransactionQuery()
    {
        return self::ROLLBACK_TRANSACTION;
    }
}
