<?php

namespace Library\DAO\Postgre\Query\Field\Test;

use Library\DAO\Postgre\BaseDAO;
use Library\DAO\Postgre\Query\Field\FieldFunction;
use Library\DAO\Postgre\Query\Field\GroupByField;

/**
 * Class GroupByFieldTest
 * @package Library\DAO\Postgre\Query\Field\Test
 */
class GroupByFieldTest extends \PHPUnit_Framework_TestCase
{
    public function testGroupByField()
    {
        $expectedOutput = 'field';

        $name   = 'field';

        $sortField = new GroupByField($name);

        $this->assertEquals($expectedOutput, $sortField->__toString());
    }

    public function testGroupByFieldWithFunction()
    {
        $expectedOutput = 'ROUND(field,2)';

        $name   = 'field';

        $sortField = new GroupByField(
            $name,
            BaseDAO::TYPE_PRIM,
            [
                new FieldFunction('ROUND', ['self', 2])
            ]
        );

        $this->assertEquals($expectedOutput, $sortField->__toString());
    }

    public function testBindParameters()
    {
        $name   = 'field';
        $sortField = new GroupByField($name);

        $this->assertEquals([], $sortField->getBindParams());
    }

}
