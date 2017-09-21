<?php

namespace Library\DAO\Postgre\Query\Test;

use Library\DAO\Postgre\BaseDAO;
use Library\DAO\Postgre\Query\Field\SelectField;
use Library\DAO\Postgre\Query\Field\SortField;
use Library\DAO\Postgre\Query\Field\WhereField;
use Library\DAO\Query\QueryFactory;

/**
 * Class PostgresQueryTest
 * @package Library\DAO\Postgre\Query\Test
 */
class PostgresQueryTest extends \PHPUnit_Framework_TestCase
{
    public function testAddCondition()
    {
        $expectedOutput = "SELECT  *  FROM my_table WHERE (my_field) = $1";

        $query = $this->createQuery();

        $query->addCondition('my_field', 1, BaseDAO::OP_EQUALS);

        $this->assertEquals($expectedOutput, $query->getSelectQuery());
        $this->assertEquals([1], $query->getBindParams(), 'Wrong bind params');
    }

    public function testAddConditionOr()
    {
        $expectedOutput = 'SELECT  *  FROM my_table WHERE ((field1) = $1 OR (field2) IN ($2,$3,$4))';

        $query = $this->createQuery();

        $query->addConditionOr([
            new WhereField('field1', 1, BaseDAO::OP_EQUALS),
            new WhereField('field2', [1, 2, 3], BaseDAO::OP_IN)
        ]);

        $this->assertEquals($expectedOutput, $query->getSelectQuery());
        $this->assertEquals([1, 1, 2, 3], $query->getBindParams(), 'Wrong bind params');
    }

    public function testAddConditionObject()
    {
        $expectedOutput = "SELECT  *  FROM my_table WHERE (my_field) = $1";

        $query = $this->createQuery();

        $condition = new WhereField('my_field', 1, BaseDAO::OP_EQUALS);
        $query->addConditionObject($condition);

        $this->assertEquals($expectedOutput, $query->getSelectQuery());
        $this->assertEquals([1], $query->getBindParams(), 'Wrong bind params');
    }

    public function testAddSelectField()
    {
        $expectedOutput = "SELECT field AS alias FROM my_table";

        $query = $this->createQuery();

        $query->addSelectField('field', 'alias');

        $this->assertEquals($expectedOutput, $query->getSelectQuery());
        $this->assertEquals([], $query->getBindParams(), 'Wrong bind params');
    }

    public function testAddSelectFieldObject()
    {
        $expectedOutput = "SELECT field AS alias FROM my_table";

        $query = $this->createQuery();

        $selectField = new SelectField('field', 'alias');
        $query->addSelectFieldObject($selectField);

        $this->assertEquals($expectedOutput, $query->getSelectQuery());
        $this->assertEquals([], $query->getBindParams(), 'Wrong bind params');
    }

    public function testAddInnerSelect()
    {
        $expectedOutput = "SELECT (SELECT  *  FROM my_table WHERE (field1) = $1) as query_alias FROM my_table";

        $query = $this->createQuery();

        $innerQuery = QueryFactory::create('my_table', QueryFactory::TYPE_RELATIONAL)
                                  ->addCondition('field1', 1, BaseDAO::OP_EQUALS);

        $query->addInnerSelectQuery($innerQuery, 'query_alias');
        $this->assertEquals($expectedOutput, $query->getSelectQuery());
        $this->assertEquals([1], $query->getBindParams(), 'Wrong bind params');
    }

    public function testAddSort()
    {
        $expectedOutput = "SELECT  *  FROM my_table ORDER BY my_field ASC";

        $query = $this->createQuery();

        $query->addSort('my_field', 'ASC');

        $this->assertEquals($expectedOutput, $query->getSelectQuery());
        $this->assertEquals([], $query->getBindParams(), 'Wrong bind params');
    }

    public function testAddSortObject()
    {
        $expectedOutput = "SELECT  *  FROM my_table ORDER BY my_field ASC";

        $query = $this->createQuery();

        $sort = new SortField('my_field', 'ASC');
        $query->addSortObject($sort);

        $this->assertEquals($expectedOutput, $query->getSelectQuery());
        $this->assertEquals([], $query->getBindParams(), 'Wrong bind params');
    }

    public function testSetGroupBy()
    {
        $expectedOutput = "SELECT  *  FROM my_table GROUP BY field1";

        $query = $this->createQuery();

        $query->setGroupBy(['field1']);

        $this->assertEquals($expectedOutput, $query->getSelectQuery());
        $this->assertEquals([], $query->getBindParams(), 'Wrong bind params');
    }

    public function testSetUpdateData()
    {
        $updateData = [
            'field1' => 'value1',
            'field2' => 'value2'
        ];

        $query = $this->createQuery();
        $query->setUpdateData($updateData);

        $this->assertEquals($updateData, $query->getUpdateData());
    }

    public function testAddUpdateData()
    {
        $expectedUpdateData = [
            'field1' => 'value1',
            'field2' => 'value2'
        ];

        $query = $this->createQuery();

        $query->addUpdateData('field1', 'value1');
        $query->addUpdateData('field2', 'value2');

        $this->assertEquals($expectedUpdateData, $query->getUpdateData());
    }

    public function testGetSelectQuery()
    {
        $expectedQueryWithBindParams =
            "SELECT field1 AS alias FROM my_table WHERE (field2) = $1 GROUP BY field4 ORDER BY field3 ASC LIMIT 5 OFFSET 4";
        $expectedQueryWithoutBindParams =
            "SELECT field1 AS alias FROM my_table WHERE (field2) = {?} GROUP BY field4 ORDER BY field3 ASC LIMIT 5 OFFSET 4";
        $expectedBindParams = [
            'value2'
        ];
        $query = $this->createQuery()
                      ->addSelectField('field1', 'alias')
                      ->addCondition('field2', 'value2')
                      ->addSort('field3', 'ASC')
                      ->setGroupBy(['field4'])
                      ->setLimit(5)
                      ->setOffset(4);

        $this->assertEquals(
            $expectedQueryWithBindParams,
            $query->getSelectQuery(true),
            'Query (with bind params processed) error'
        );
        $this->assertEquals(
            $expectedQueryWithoutBindParams,
            $query->getSelectQuery(false),
            'Query (without bind params processed) error'
        );
        $this->assertEquals($expectedBindParams, $query->getBindParams());
    }

    public function testGetUpdateQuery()
    {
        $this->setExpectedException('Exceptions\NotImplementedException', 'getUpdateQuery');

        $query = $this->createQuery();

        $query->getUpdateQuery([]);
    }

    public function testGetDeleteQuery()
    {
        $this->setExpectedException('Exceptions\NotImplementedException', 'getDeleteQuery');

        $query = $this->createQuery();

        $query->getDeleteQuery();
    }

    public function testGetInsertQuery()
    {
        if (!function_exists('pg_escape_string')) {
            $this->markTestSkipped('Skipped test since PHP pgsql extension not loaded');
        }

        $expectedQuery = "INSERT INTO my_table(field1,field2,field3) VALUES ('value1','value2','value3')";

        $query = $this->createQuery()
            ->setInsertData([
               'field1' => 'value1',
               'field2' => 'value2',
               'field3' => 'value3'
            ]);

        $this->assertEquals($expectedQuery, $query->getInsertQuery(), 'Query mismatch');
    }

    public function testGetBulkInsertQueryWithNoInsertData()
    {
        $expectedQuery = '';

        $query = $this->createQuery();

        $this->assertEquals($expectedQuery, $query->getBulkInsertQuery(), 'Query mismatch');
    }

    public function testGetBulkInsertQuery()
    {
        if (!function_exists('pg_escape_string')) {
            $this->markTestSkipped('Skipped test since PHP pgsql extension not loaded');
        }

        $expectedQuery = "INSERT INTO my_table('field1,field2') VALUES  ('value1a','value2a'), ('value1b','value2b')";

        $bulkInsertData = [
            [
                'field1' => 'value1a',
                'field2' => 'value2a',
            ],
            [
                'field1' => 'value1b',
                'field2' => 'value2b',
            ]
        ];

        $query = $this->createQuery()
            ->setInsertData($bulkInsertData);

        $this->assertEquals($expectedQuery, $query->getBulkInsertQuery(), 'Query mismatch');
    }

    /**
     * @return \Library\DAO\Query\QueryInterface
     */
    private function createQuery()
    {
        return QueryFactory::create('my_table', QueryFactory::TYPE_RELATIONAL);
    }


}
