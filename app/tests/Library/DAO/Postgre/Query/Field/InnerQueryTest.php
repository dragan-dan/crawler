<?php

namespace Library\DAO\Postgre\Query\Field\Test;

use Library\DAO\Postgre\BaseDAO;
use Library\DAO\Postgre\Query\Field\InnerQuery;
use Library\DAO\Query\QueryFactory;

/**
 * Class SelectFieldTest
 * @package Library\DAO\Postgre\Query\Field\Test
 */
class InnerQueryTest extends \PHPUnit_Framework_TestCase
{

    public function testInnerQuery()
    {
        $expectedOutput = '(SELECT  *  FROM my_table) as table_alias';

        $query = QueryFactory::create('my_table', QueryFactory::TYPE_RELATIONAL);

        $innerQuery = new InnerQuery($query, 'table_alias');

        $this->assertEquals($expectedOutput, $innerQuery->__toString(), 'wrong query output');
    }

    public function testGetBindParameters()
    {
        $expectedOutput = '(SELECT  *  FROM my_table WHERE (field1) = {?}) as table_alias';

        $criteriaValue = 1;
        $query = QueryFactory::create('my_table', QueryFactory::TYPE_RELATIONAL)
            ->addCondition('field1', $criteriaValue, BaseDAO::OP_EQUALS);

        $innerQuery = new InnerQuery($query, 'table_alias');

        $this->assertEquals($expectedOutput, $innerQuery->__toString(), 'wrong query output');
        $this->assertEquals([$criteriaValue], $innerQuery->getBindParams());
    }

}
