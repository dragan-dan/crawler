<?php

namespace Library\DAO\Postgre\Query\Field\Test;

use Library\DAO\Postgre\BaseDAO;
use Library\DAO\Postgre\Query\Field\FieldFunction;
use Library\DAO\Postgre\Query\Field\SelectField;

/**
 * Class SelectFieldTest
 * @package Library\DAO\Postgre\Query\Field\Test
 */
class SelectFieldTest extends \PHPUnit_Framework_TestCase
{

    public function testSelectField()
    {
        $expectedOutput = 'field AS alias';

        $name   = 'field';
        $alias  = 'alias';

        $selectField = new SelectField($name, $alias, BaseDAO::TYPE_PRIM);

        $this->assertEquals($expectedOutput, $selectField);
    }

    public function testSelectFieldWithFunction()
    {
        $expectedOutput = 'ADDITION(field,p1) AS alias';

        $name      = 'field';
        $alias     = 'alias';
        $functions = [new FieldFunction('ADDITION', ['self', 'p1'])];

        $selectField = new SelectField($name, $alias, BaseDAO::TYPE_PRIM, $functions);

        $this->assertEquals($expectedOutput, $selectField);
    }

    public function testSelectFieldWithMultipleFunctions()
    {
        $expectedOutput = 'SUM(MULTIPLICATION(ADDITION(field,p1),p2)) AS alias';

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

    public function testGetBindParameters()
    {
        $name   = 'field';
        $alias  = 'alias';

        $selectField = new SelectField($name, $alias, BaseDAO::TYPE_PRIM);

        $this->assertEmpty($selectField->getBindParams());
    }
}
