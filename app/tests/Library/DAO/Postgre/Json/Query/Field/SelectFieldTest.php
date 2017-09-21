<?php

namespace Library\DAO\Postgre\Json\Query\Field\Test;

use Library\DAO\Postgre\BaseDAO;
use Library\DAO\Postgre\Query\Field\FieldFunction;
use Library\DAO\Postgre\Json\Query\Field\SelectField;

/**
 * Class SelectFieldTest
 * @package Library\DAO\Postgre\Query\Field\Test
 */
class SelectFieldTest extends \PHPUnit_Framework_TestCase
{

    public function testSelectField()
    {
        $expectedOutput = "data->'parent'->>'child' AS alias";

        $name   = 'parent.child';
        $alias  = 'alias';

        $selectField = new SelectField($name, $alias, BaseDAO::TYPE_PRIM);

        $this->assertEquals($expectedOutput, $selectField->__toString());
    }

    public function testSelectFieldWithFunction()
    {
        $expectedOutput = "ADDITION(data->>'field',p1) AS alias";

        $name      = 'field';
        $alias     = 'alias';
        $functions = [new FieldFunction('ADDITION', ['self', 'p1'])];

        $selectField = new SelectField($name, $alias, BaseDAO::TYPE_PRIM, $functions);

        $this->assertEquals($expectedOutput, $selectField->__toString());
    }

    public function testSelectFieldWithMultipleFunctions()
    {
        $expectedOutput = "SUM(MULTIPLICATION(ADDITION(data->>'field',p1),p2)) AS alias";

        $name      = 'field';
        $alias     = 'alias';
        $functions = [
            new FieldFunction('ADDITION', ['self', 'p1']),
            new FieldFunction('MULTIPLICATION', ['self', 'p2']),
            new FieldFunction('SUM')
        ];

        $selectField = new SelectField($name, $alias, BaseDAO::TYPE_PRIM, $functions);

        $this->assertEquals($expectedOutput, $selectField->__toString());
    }

    public function testSelectAll()
    {
        $expectedOutput = "data AS alias";

        // data is the main column name, when entered data as name, we can just get it as is
        $name   = 'data';
        $alias  = 'alias';

        $selectField = new SelectField($name, $alias, BaseDAO::TYPE_OBJECT);

        $this->assertEquals($expectedOutput, $selectField->__toString());
    }

    public function testSelectFieldNonPrim()
    {
        $expectedOutput = "data->'parent'->'child' AS alias";

        $name   = 'parent.child';
        $alias  = 'alias';

        $selectField = new SelectField($name, $alias, BaseDAO::TYPE_OBJECT);

        $this->assertEquals($expectedOutput, $selectField->__toString());
    }

    public function testGetBindParameters()
    {
        $name   = 'field';
        $alias  = 'alias';

        $selectField = new SelectField($name, $alias, BaseDAO::TYPE_PRIM);

        $this->assertEmpty($selectField->getBindParams());
    }
}
