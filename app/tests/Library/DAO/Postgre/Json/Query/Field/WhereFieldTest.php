<?php

namespace Library\DAO\Postgre\Json\Query\Field\Test;

use Library\DAO\Postgre\BaseDAO;
use Library\DAO\Postgre\Query\Field\FieldFunction;
use Library\DAO\Postgre\Json\Query\Field\WhereField;
use Library\DAO\Query\Field\Value;

/**
 * Class WhereFieldTest
 * @package Library\DAO\Postgre\Query\Field\Test
 */
class WhereFieldTest extends \PHPUnit_Framework_TestCase
{
    public function testWhereField()
    {
        $expectedOutput = "(data->'parent'->>'child') =  {?} ";

        $name          = 'parent.child';
        $criteriaValue = 5;

        $whereField = new WhereField($name, $criteriaValue, BaseDAO::OP_EQUALS);

        $this->assertEquals($expectedOutput, $whereField->__toString(), 'wrong query output');
        $this->assertEquals([5], $whereField->getBindParam(), 'Wrong bind parameter');
    }

    public function testWhereFieldWithNullValue()
    {
        $expectedOutput = "(data->>'field') IS  NULL ";

        $name          = 'field';
        $criteriaValue = null;

        $whereField = new WhereField($name, $criteriaValue, BaseDAO::OP_IS);

        $this->assertEquals($expectedOutput, $whereField->__toString(), 'wrong query output');
        $this->assertEmpty($whereField->getBindParam(), 'Wrong bind parameter');
    }

    public function testWhereFieldWithFunctions()
    {
        $expectedOutput = "(SUM(TEST_FUNC(data->>'field',p1))) =  {?} ";

        $name          = 'field';
        $criteriaValue = 5;
        $functions     = [
            new FieldFunction('TEST_FUNC', ['self', 'p1']),
            new FieldFunction('SUM')
        ];

        $whereField = new WhereField($name, $criteriaValue, BaseDAO::OP_EQUALS, BaseDAO::TYPE_PRIM, '', $functions);

        $this->assertEquals($expectedOutput, $whereField->__toString(), 'wrong query output');
        $this->assertEquals([5], $whereField->getBindParam(), 'Wrong bind parameter');
    }

    public function testWhereFieldWithValueFunction()
    {
        $expectedOutput = "(data->>'field') =  SUM(TEST_FUNC({?})) ";

        $name          = 'field';
        $criteriaValue = new Value(
            5,
            [
                new FieldFunction('TEST_FUNC', ['self']),
                new FieldFunction('SUM'),
            ]
        );

        $whereField = new WhereField($name, $criteriaValue, BaseDAO::OP_EQUALS);

        $this->assertEquals($expectedOutput, $whereField->__toString(), 'wrong query output');
        $this->assertEquals([5], $whereField->getBindParam(), 'Wrong bind parameter');
    }

    public function testWhereFieldWithFunctionAndValueFunction()
    {
        $expectedOutput = "(TEST_FUNC2(data->>'field',p1)) =  TEST_FUNC({?}) ";

        $name          = 'field';
        $criteriaValue = new Value(5, [new FieldFunction('TEST_FUNC', ['self'])]);
        $functions     = [new FieldFunction('TEST_FUNC2', ['self', 'p1'])];

        $whereField = new WhereField($name, $criteriaValue, BaseDAO::OP_EQUALS, BaseDAO::TYPE_PRIM, '', $functions);

        $this->assertEquals($expectedOutput, $whereField->__toString(), 'wrong query output');
        $this->assertEquals([5], $whereField->getBindParam(), 'Wrong bind parameter');
    }

    public function testWhereFieldWithArrayValues()
    {
        $expectedOutput = "(data->>'field') IN ({?},{?},{?})";

        $name          = 'field';
        $criteriaValue = [5,6,7];

        $whereField = new WhereField($name, $criteriaValue, BaseDAO::OP_IN, BaseDAO::TYPE_PRIM);

        $this->assertEquals($expectedOutput, $whereField->__toString(), 'wrong query output');
        $this->assertEquals([5,6,7], $whereField->getBindParam(), 'Wrong bind parameter');
    }

    public function testWhereFieldWithArrayValuesAndFunction()
    {
        $expectedOutput = "(data->>'field') IN (MULTIPLY({?},5),MULTIPLY({?},5),MULTIPLY({?},5))";

        $name          = 'field';
        $criteriaValue = new Value([5,6,7], [new FieldFunction('MULTIPLY', ['self', 5])]);

        $whereField = new WhereField($name, $criteriaValue, BaseDAO::OP_IN);

        $this->assertEquals($expectedOutput, $whereField->__toString(), 'wrong query output');
        $this->assertEquals([5,6,7], $whereField->getBindParam(), 'Wrong bind parameter');
    }

    public function testWhereFieldExistOperator()
    {
        $expectedOutput = "(data->'parent'->'child') ? array[{?},{?}]";

        $name          = 'parent.child';
        $criteriaValue = [5,6];

        $whereField = new WhereField($name, $criteriaValue, BaseDAO::OP_EXISTS);

        $this->assertEquals($expectedOutput, $whereField->__toString(), 'wrong query output');
        $this->assertEquals([5,6], $whereField->getBindParam(), 'Wrong bind parameter');
    }

    public function testWhereCriteriaField()
    {
        $expectedOutput = "(data->'field') <@ '[{\"foo\": {?} }]'";

        $name          = 'field';
        $criteriaValue = 5;
        $criteriaField = 'foo';

        $whereField = new WhereField($name, $criteriaValue, BaseDAO::OP_CONTAINS_INVERSE, BaseDAO::TYPE_OBJECT, $criteriaField);

        $this->assertEquals($expectedOutput, $whereField->__toString(), 'wrong query output');
        $this->assertEquals([5], $whereField->getBindParam(), 'Wrong bind parameter');
    }

}
