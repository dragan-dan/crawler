<?php

namespace Library\DAO\Query;

/**
 * Class QueryFactoryTest
 * @package Library\DAO\Query
 */
class QueryFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateRelationQuery()
    {
        $query = QueryFactory::create('table', QueryFactory::TYPE_RELATIONAL);

        $this->assertInstanceOf(
            'Library\DAO\Postgre\Query\PostgresQuery',
            $query,
            'Query is not of expected type'
        );
    }

    public function testCreateJsonQuery()
    {
        $query = QueryFactory::create('table', QueryFactory::TYPE_DOCUMENT);

        $this->assertInstanceOf(
            'Library\DAO\Postgre\Json\Query\PostgresJSONQuery',
            $query,
            'Query is not of expected type'
        );
    }

    public function testDefaultQuery()
    {
        $query = QueryFactory::create('table');

        $this->assertInstanceOf(
            'Library\DAO\Postgre\Json\Query\PostgresJSONQuery',
            $query,
            'Query is not of expected type'
        );
    }

    public function testCreateRelationalWhereField()
    {
        $whereField = QueryFactory::createWhereField(QueryFactory::TYPE_RELATIONAL, 'field1', 'value1');

        $this->assertInstanceOf(
            'Library\DAO\Postgre\Query\Field\WhereField',
            $whereField,
            'Query is not of expected type'
        );
    }

    public function testCreateJSONWhereField()
    {
        $whereField = QueryFactory::createWhereField(QueryFactory::TYPE_DOCUMENT, 'field1', 'value1');

        $this->assertInstanceOf(
            'Library\DAO\Postgre\Json\Query\Field\WhereField',
            $whereField,
            'Query is not of expected type'
        );
    }

    public function testCreateFieldFunction()
    {
        $fieldFunction = QueryFactory::createFieldFunction('name', ['param1', 'param2']);

        $this->assertInstanceOf(
            'Library\DAO\Postgre\Query\Field\FieldFunction',
            $fieldFunction,
            'Function is not of expected type'
        );

    }
}
