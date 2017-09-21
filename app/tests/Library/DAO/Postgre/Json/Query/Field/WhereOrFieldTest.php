<?php

namespace Library\DAO\Postgre\Json\Query\Field\Test;

use Library\DAO\Postgre\BaseDAO;
use Library\DAO\Postgre\Query\Field\FieldFunction;
use Library\DAO\Postgre\Query\Field\SelectField;
use Library\DAO\Postgre\Query\Field\WhereField;
use Library\DAO\Postgre\Query\Field\WhereOrField;
use Library\DAO\Query\Field\Value;

/**
 * Class WhereOrFieldTest
 * @package Library\DAO\Postgre\Query\Field\Test
 */
class WhereOrFieldTest extends \PHPUnit_Framework_TestCase
{
    public function testWhereOrField()
    {
        $expectedOutput = '((field1) = {?} OR (field2) IN ({?},{?},{?}))';

        $whereField1 = new WhereField('field1', 1, BaseDAO::OP_EQUALS);
        $whereField2 = new WhereField('field2', [5,6,7], BaseDAO::OP_IN);

        $whereOrField = new WhereOrField([$whereField1, $whereField2]);

        $this->assertEquals($expectedOutput, $whereOrField->__toString(), 'wrong query output');
        $this->assertEquals([1,5,6,7], $whereOrField->getBindParam(), 'Wrong bind parameter');
    }

    public function testWhereFieldWithNullValue()
    {
        $expectedOutput = '(field) = NULL';

        $name          = 'field';
        $criteriaValue = null;

        $selectField = new WhereField($name, $criteriaValue, BaseDAO::OP_EQUALS, BaseDAO::TYPE_PRIM);

        $this->assertEquals($expectedOutput, $selectField->__toString(), 'wrong query output');
        $this->assertEmpty($selectField->getBindParam(), 'Wrong bind parameter');
    }

    public function testWhereFieldWithFunctions()
    {
        $expectedOutput = '(SUM(TEST_FUNC(field,p1))) = {?}';

        $name          = 'field';
        $criteriaValue = 5;
        $functions     = [
            new FieldFunction('TEST_FUNC', ['self', 'p1']),
            new FieldFunction('SUM')
        ];

        $selectField = new WhereField($name, $criteriaValue, BaseDAO::OP_EQUALS, BaseDAO::TYPE_PRIM, '', $functions);

        $this->assertEquals($expectedOutput, $selectField->__toString(), 'wrong query output');
        $this->assertEquals([5], $selectField->getBindParam(), 'Wrong bind parameter');
    }

    public function testWhereFieldWithValueFunction()
    {
        $expectedOutput = '(field) = SUM(TEST_FUNC({?}))';

        $name          = 'field';
        $criteriaValue = new Value(
            5,
            [
                new FieldFunction('TEST_FUNC', ['self']),
                new FieldFunction('SUM'),
            ]
        );

        $selectField = new WhereField($name, $criteriaValue, BaseDAO::OP_EQUALS);

        $this->assertEquals($expectedOutput, $selectField->__toString(), 'wrong query output');
        $this->assertEquals([5], $selectField->getBindParam(), 'Wrong bind parameter');
    }

    public function testWhereFieldWithFunctionAndValueFunction()
    {
        $expectedOutput = '(TEST_FUNC2(field,p1)) = TEST_FUNC({?})';

        $name          = 'field';
        $criteriaValue = new Value(5, [new FieldFunction('TEST_FUNC', ['self'])]);
        $functions     = [new FieldFunction('TEST_FUNC2', ['self', 'p1'])];

        $selectField = new WhereField($name, $criteriaValue, BaseDAO::OP_EQUALS, BaseDAO::TYPE_PRIM, '', $functions);

        $this->assertEquals($expectedOutput, $selectField->__toString(), 'wrong query output');
        $this->assertEquals([5], $selectField->getBindParam(), 'Wrong bind parameter');
    }

    public function testWhereFieldWithArrayValues()
    {
        $expectedOutput = '(field) IN ({?},{?},{?})';

        $name          = 'field';
        $criteriaValue = [5,6,7];

        $selectField = new WhereField($name, $criteriaValue, BaseDAO::OP_IN);

        $this->assertEquals($expectedOutput, $selectField->__toString(), 'wrong query output');
        $this->assertEquals([5,6,7], $selectField->getBindParam(), 'Wrong bind parameter');
    }

    public function testWhereFieldWithArrayValuesAndFunction()
    {
        $expectedOutput = '(field) IN (MULTIPLY({?},5),MULTIPLY({?},5),MULTIPLY({?},5))';

        $name          = 'field';
        $criteriaValue = new Value([5,6,7], [new FieldFunction('MULTIPLY', ['self', 5])]);

        $selectField = new WhereField($name, $criteriaValue, BaseDAO::OP_IN);

        $this->assertEquals($expectedOutput, $selectField->__toString(), 'wrong query output');
        $this->assertEquals([5,6,7], $selectField->getBindParam(), 'Wrong bind parameter');
    }

}
