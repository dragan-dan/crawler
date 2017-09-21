<?php

namespace Library\DAO\Postgre\Test {

use Library\DAO\Postgre\BaseDAO;
use Library\DAO\Query\QueryFactory;

/**
 * Class BaseDAOTest
 * @package Library\DAO\Postgre\Test
 */
class BaseDAOTest extends \PHPUnit_Framework_TestCase
{
    public function testFindRelationalWithColumns()
    {
        $expectedResult = [
            ['alias' => 'value1']
        ];

        $mockResultFromStorage = [
            ['alias' => 'value1']
        ];

        $this->setMockResultSet($mockResultFromStorage);

        $query = QueryFactory::create('my_table', QueryFactory::TYPE_RELATIONAL)
                             ->addSelectField('field1', 'alias')
                             ->addCondition('field2', 'value2');

        $baseDAO = $this->createBaseDao();

        $result = $baseDAO->find($query);

        $this->assertEquals($expectedResult, $result, 'Results mismatch: testFindRelationalWithColumns');
    }

    public function testFindWithJsonQuery()
    {
        $expectedResult = [
            ['field1' => 'value1']
        ];

        $mockResultFromStorage = [
            [
                'data' => json_encode(['field1' => 'value1'])
            ]
        ];

        $this->setMockResultSet($mockResultFromStorage);

        $query = QueryFactory::create('my_table', QueryFactory::TYPE_DOCUMENT)
                             ->addCondition('field2', 'value2');

        $baseDAO = $this->createBaseDao();

        $result = $baseDAO->find($query);

        $this->assertEquals($expectedResult, $result, 'Results mismatch: testFindWithJsonQuery');
    }

    public function testFindWithJsonQueryWithColumns()
    {
        $expectedResult = [
            ['alias' => 'value']
        ];

        $mockResultFromStorage = [
            [
                'alias' => 'value'
            ]
        ];

        $this->setMockResultSet($mockResultFromStorage);

        $query = QueryFactory::create('my_table', QueryFactory::TYPE_DOCUMENT)
                      ->addSelectField('field1', 'alias')
                      ->addCondition('field2', 'value2');

        $baseDAO = $this->createBaseDao();

        $result = $baseDAO->find($query);

        $this->assertEquals($expectedResult, $result, 'Results mismatch: testFindWithJsonQueryWithColumns');
    }

    public function testFindWithJsonQueryWithArrayColumn()
    {
        $expectedResult = [
            ['alias' => ['2016-07-13', '2016-07-14']]
        ];

        $mockResultFromStorage = [
            [
                'alias' => "[\"2016-07-13\", \"2016-07-14\"]"
            ]
        ];

        $this->setMockResultSet($mockResultFromStorage);

        $query = QueryFactory::create('my_table', QueryFactory::TYPE_DOCUMENT)
                             ->addSelectField('field1', 'alias', BaseDAO::TYPE_ARRAY);

        $baseDAO = $this->createBaseDao();

        $result = $baseDAO->find($query);

        $this->assertEquals($expectedResult, $result, 'Results mismatch: testFindWithJsonQueryWithArrayColumn');
    }

    /**
     * @return BaseDAO
     */
    private function createBaseDao()
    {
        // mock the db connection resource with a tmpfile resource
        $connection = tmpfile();
        return new BaseDAO($connection, 'my_table');
    }

    private function setMockResultSet($resultSet)
    {
        global $mockResult;

        $mockResult = $resultSet;
    }

}

}

// Mocking global PHP functions
namespace Library\DAO\Postgre {

    define ('PGSQL_CONNECTION_OK', 0);

    function pg_query_params($conn, $query, $params)
    {
        return true;
    }

    function pg_connection_status($resource)
    {
        return PGSQL_CONNECTION_OK;
    }

    function pg_fetch_all($resource)
    {
        global $mockResult;

        return $mockResult;
    }
}
