<?php

namespace Library\DAO\Postgre\Query\Field\Test;

use Library\DAO\Postgre\Query\Field\FieldFunction;

/**
 * Class FieldFunctionTest
 * @package Library\DAO\Postgre\Query\Field\Test
 */
class FieldFunctionTest extends \PHPUnit_Framework_TestCase
{

    public function testFunctionWithoutParameters()
    {
        $expectedOutput = 'SUM(test_field)';

        $funcName   = 'SUM';
        $parameters = [];
        $testField  = 'test_field'; // this is the place holder for bind params

        $function = new FieldFunction($funcName, $parameters);
        $query = $function->getStringValue($testField);

        $this->assertEquals($expectedOutput, $query);
    }

    public function testFunctionWithParameters()
    {
        $expectedOutput = 'ADDITION(p1,p2)';

        $funcName   = 'ADDITION';
        $parameters = ['p1', 'p2'];
        $testField  = 'test_field'; // this is the place holder for bind params

        $function = new FieldFunction($funcName, $parameters);
        $query = $function->getStringValue($testField);

        $this->assertEquals($expectedOutput, $query);
    }

    public function testFunctionWithSelf()
    {
        $expectedOutput = 'ADDITION(test_field,p1)';

        $funcName   = 'ADDITION';
        $parameters = ['self', 'p1'];
        $testField  = 'test_field'; // this is the place holder for bind params

        $function = new FieldFunction($funcName, $parameters);
        $query = $function->getStringValue($testField);

        $this->assertEquals($expectedOutput, $query);
    }

    public function testCastFunction()
    {
        $expectedOutput = 'CAST(test_field AS timestamp with time zone)';

        $funcName   = 'CAST';
        $parameters = ['timestamp with time zone'];
        $testField  = 'test_field'; // this is the place holder for bind params

        $function = new FieldFunction($funcName, $parameters);
        $query = $function->getStringValue($testField);

        $this->assertEquals($expectedOutput, $query);
    }

}
