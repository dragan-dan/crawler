<?php

namespace Library\DAO\Postgre;

use Library\DAO\DAOInterface;
use Library\DAO\Postgre\Json\Query\PostgresJSONQuery;
use Library\DAO\Postgre\Query\PostgresQuery;
use Library\DAO\Query\Field\AbstractSelectField;
use Library\DAO\Query\QueryFactory;
use Library\DAO\Query\QueryInterface;
use Exceptions\DBException;
use Library\Logger\Logger;

class BaseDAO implements DAOInterface
{
    protected $table;

    /** This is the name column that json data is stored */
    const JSONB_FIELD_NAME = 'data';

    // Field value types
    const TYPE_OBJECT = 'object';
    const TYPE_ARRAY  = 'array';
    const TYPE_PRIM   = 'primitive';

    /** Equal */
    const OP_EQUALS = '=';

    /** IN */
    const OP_IN = 'IN';

    /** IS */
    const OP_IS = 'IS';

    /** IS NOT */
    const OP_IS_NOT = 'IS NOT';

    /** Value exists in field */
    const OP_EXISTS = '?';

    /** Any of the values exist in field */
    const OP_EXISTS_OR = '?|';

    /** All of the values exist in field */
    const OP_EXISTS_AND = '?&';

    /** Left contains right */
    const OP_CONTAINS = '@>';

    /** Left greater than right */
    const OP_GREATER_THAN = '>';

    /** Left smaller than right */
    const OP_LOWER_THAN = '<';

    /** Left greater than or equals right */
    const OP_GREATER_THAN_EQUALS = '>=';

    /** Left smaller than or equals right */
    const OP_LOWER_THAN_EQUALS = '<=';

    /** Right contains left */
    const OP_CONTAINS_INVERSE = '<@';

    // TODO: Remove Query types after migrating to QueryFactory fully
    /** PostgresQuery type 'Order' */
    const QUERY_TYPE_ORDER  = "ORDER";

    /** PostgresQuery type 'Select' */
    const QUERY_TYPE_SELECT = "SELECT";

    /** PostgresQuery type 'Where' */
    const QUERY_TYPE_WHERE  = "WHERE";

    /**
     * Postgre sql resource
     *
     * @var resource
     */
    protected $conn;

    /**
     * Prepared transaction id
     */
    protected $preparedTransaction;

    /**
     * Get last error
     * @var string last postgresql error message
     */
    protected $lastErrorMessage;

    public function __construct($connection, $table)
    {
        $this->table = $table;
        $this->conn  = $connection;
    }

    /**
     * @param QueryInterface $query
     *
     * @return array
     */
    public function find($query)
    {
        $queryStr   = $query->getSelectQuery();
        $bindParams = $query->getBindParams();
        $result     = $this->executeSelectNew($queryStr, $bindParams, $query->getSelectFields());

        return $result;
    }

    /**
     * @param QueryInterface $query
     *
     * @return array
     */
    public function findOne($query)
    {
        $resultSet = $this->find($query);

        if (!is_null($resultSet) && is_array($resultSet) && !empty($resultSet)) {
            $resultSet = current($resultSet);
        }

        return $resultSet;
    }

    /**
     * @param QueryInterface $query
     *
     * @return resource
     */
    public function insert($query)
    {
        $queryStr = $query->getInsertQuery();

        return $this->execute($queryStr);
    }


    /**
     * @param QueryInterface $query
     *
     * @return resource
     */
    public function insertBulk($query)
    {
        $queryStr = $query->getBulkInsertQuery();
        return $this->execute($queryStr);
    }

    /**
     *
     * @param QueryInterface $query
     *
     * @return bool
     */
    public function update($query)
    {
        $result = $this->find($query);

        if (empty($result)) {
            // records not found
            return false;
        }

        foreach ($result as $item) {

            foreach ((array)$query->getUpdateData() as $key => $value) {

                $fieldPath = explode('.', $key);

                $fieldValue = &$item;
                foreach ($fieldPath as $path) {
                    $fieldValue = &$fieldValue[$path];
                }

                $fieldValue = $value;
            }

            $itemJson = json_encode($item);

            $queryStr = $query->getUpdateQuery($itemJson);
            $bindParams = $query->getBindParams();
            $result = $this->executeNew($queryStr, $bindParams);

            if (!$result) {
                // record could not be updated
                return false;
            }
        }

        return true;
    }

    /**
     *
     * @param QueryInterface $query
     *
     * @return bool
     */
    public function remove($query)
    {
        $queryStr   = $query->getDeleteQuery();
        $bindParams = $query->getBindParams();

        $result = $this->executeNew($queryStr, $bindParams);

        return (bool) $result;
    }

    /**
     * Return last error message on our DB connection
     * @return mixed
     */
    public function getLastErrorMessage() {
        return $this->lastErrorMessage;
    }

    /**
     * Exclude fields from result. Fields that have not 1 as their value will be removed from the given result
     *
     * @param array $result
     * @param array $fields
     *
     * @return array
     */
    protected function processExcludedFields($result, $fields)
    {
        $excludeFields = [];

        foreach ($fields as $field => $value) {
            if ($value === 0) {
                $excludeFields[] = $field;
            }
        }

        if (empty($excludeFields)) {
            // No field to exclude. Send result
            return $result;
        }

        // remove extra information when sending a list of events
        $result = array_map(function ($el) use ($excludeFields) {

            foreach ($excludeFields as $field) {
                unset($el[$field]);
            }

            return $el;
        }, $result);

        return $result;
    }

    /**
     * @deprecated use executeSelectNew
     *
     * Execute and return all results
     *
     * @param string $query
     *
     * @return array
     */
    protected function executeSelect($query, $fields)
    {
        $rs = $this->execute($query);
        $result = pg_fetch_all($rs);

        if (is_array($result)) {
            $result = array_map(function ($el) use ($fields) {

                $jsonStr = '';

                if ($fields) {
                    $jsonStrArray = [];
                    foreach ($fields as $fieldName => $value) {
                        if ($value === 1) {
                            $jsonStrArray[] = '"' . $fieldName . '":' . $el[strtolower($fieldName)];
                        } elseif ($value !== 0) {
                            $jsonStrArray[] = $el[strtolower($fieldName)];
                        }
                    }

                    if (!empty($jsonStrArray)) {
                        if (count($jsonStrArray) === 1) {
                            $jsonStr = current($jsonStrArray);
                        } else {
                            $jsonStr = '[' . implode(',', $jsonStrArray). ']';
                        }

                    }
                }

                if (empty($jsonStr)) {
                    $jsonStr .= $el['data'];
                }

                $bla = json_decode($jsonStr, true);
                return $bla;
            }, $result);
        }
        return $result;
    }

    /**
     * @param string $query
     * @param array  $params
     * @param array  $fields
     *
     * @return array
     * @throws DBException
     */
    protected function executeSelectNew($query, $params, $fields)
    {
        // uncomment this to debug the actual query with the placeholders replaced. Useful when line by line debugging,
        // or to log the query
        // $debugQuery = $this->debugQuery($query, $params);

        //checking postgres connection and tries to reconnect if its broken or does not exist
        $this->resetConnection();

        $rs = pg_query_params($this->conn, $query, $params);
        $result = pg_fetch_all($rs);

        if (is_array($result)) {
            $result = array_map(function ($el) use ($fields) {

                $document = [];

                if (!$fields) {
                    // No fields specified, select all
                    $document = json_decode($el[self::JSONB_FIELD_NAME], true);
                } else {
                    // Get the selected fields
                    foreach ($fields as $field)
                    {
                        /** @var AbstractSelectField $field */
                        if ($field->fieldType === PostgresQuery::TYPE_PRIM || $field->fieldType === 'innerQuery') {
                            $document[$field->getAlias()] = $el[$field->getAlias()];
                        } else {
                            $document[$field->getAlias()] = json_decode($el[$field->getAlias()], true);
                        }
                    }
                }

                return $document;
            }, $result);
        }

        return $result;
    }

    /**
     * Executes a query
     *
     * @param $query
     * @return resource
     * @throws DBException
     */
    protected function execute($query)
    {
        //checking postgres connection and tries to reconnect if its broken or does not exist
        $this->resetConnection();

        $rs = pg_query($this->conn, $query);

        // Capture error message on failure
        if (!$rs) {
            $this->lastErrorMessage = pg_last_error($this->conn);
        }

        return $rs;
    }

    /**
     * Executes a query
     *
     * @param string $query
     * @param array $bindParams
     *
     * @return resource
     * @throws DBException
     */
    protected function executeNew($query, $bindParams)
    {
        //checking postgres connection and tries to reconnect if its broken or does not exist
        $this->resetConnection();

        $rs = pg_query_params($this->conn, $query, $bindParams);

        // Capture error message on failure
        if (!$rs) {
            $this->lastErrorMessage = pg_last_error($this->conn);
        }

        return $rs;
    }

    /**
     * Gets the query and params and returns the query with the bind variables replaced.
     *
     * @param string $query
     * @param array  $params
     *
     * @return string
     */
    protected function debugQuery($query, $params)
    {
        return preg_replace_callback(
            '/\$(\d+)\b/',
            function ($match) use ($params) {
                $key = ($match[1] - 1);

                return (is_null($params[$key]) ? 'NULL' : pg_escape_literal($params[$key]));
            },
            $query);
    }

    /**
     * @param QueryInterface $query
     * @return resource
     */
    public function beginTransaction($query = null)
    {
        if (is_null($query)) {
            $query = QueryFactory::create();
        }
        return $this->execute($query->getBeginTransactionQuery());
    }

    /**
     * @param QueryInterface $query
     * @return resource
     */
    public function commitTransaction($query = null)
    {
        if (is_null($query)) {
            $query = QueryFactory::create();
        }
        return $this->execute($query->getCommitTransactionQuery());
    }

    /**
     * @param QueryInterface $query
     * @return resource
     */
    public function rollbackTransaction($query = null)
    {
        if (is_null($query)) {
            $query = QueryFactory::create();
        }
        return $this->execute($query->getRollbackTransactionQuery());
    }

    /**
     * /Checking postgres connection and tries to reconnect if its broken or does not exist
     *
     * @throws DBException
     */
    private function resetConnection()
    {
        if (!is_resource($this->conn)) {
            throw new DBException(DBException::MESSAGE_DATABASE_CONNECTION_BROKEN);
        }

        if (pg_connection_status($this->conn) === PGSQL_CONNECTION_OK) {
            return;
        }

        if (!pg_connection_reset($this->conn)) {
            throw new DBException(DBException::MESSAGE_DATABASE_CONNECTION);
        }
    }
}
