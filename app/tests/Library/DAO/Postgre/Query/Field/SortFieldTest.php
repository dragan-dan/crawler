<?php

namespace Library\DAO\Postgre\Query\Field\Test;

use Library\DAO\Postgre\Query\Field\SortField;

/**
 * Class SortFieldTest
 * @package Library\DAO\Postgre\Query\Field\Test
 */
class SortFieldTest extends \PHPUnit_Framework_TestCase
{

    public function testSortFieldWithDefaultDirection()
    {
        $expectedOutput = 'field ASC';

        $name   = 'field';

        $sortField = new SortField($name);

        $this->assertEquals($expectedOutput, $sortField);
    }

    public function testSortFieldAsc()
    {
        $expectedOutput = 'field ASC';

        $name      = 'field';
        $direction = SortField::SORT_ASCENDING;

        $sortField = new SortField($name, $direction);

        $this->assertEquals($expectedOutput, $sortField);
    }

    public function testSortFieldDesc()
    {
        $expectedOutput = 'field DESC';

        $name      = 'field';
        $direction = SortField::SORT_DESCENDING;

        $sortField = new SortField($name, $direction);

        $this->assertEquals($expectedOutput, $sortField);
    }


}
