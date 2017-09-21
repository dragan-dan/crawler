<?php

namespace Library\DAO\Postgre\Json\Query\Field\Test;

use Library\DAO\Postgre\Json\Query\Field\SortField;

/**
 * Class SortFieldTest
 * @package Library\DAO\Postgre\Query\Field\Test
 */
class SortFieldTest extends \PHPUnit_Framework_TestCase
{
    public function testSortFieldWithDefaultDirection()
    {
        $expectedOutput = "data->'parent'->>'child' ASC";

        $name   = 'parent.child';

        $sortField = new SortField($name);

        $this->assertEquals($expectedOutput, $sortField->__toString());
    }

    public function testSortFieldAsc()
    {
        $expectedOutput = "data->>'field' ASC";

        $name      = 'field';
        $direction = SortField::SORT_ASCENDING;

        $sortField = new SortField($name, $direction);

        $this->assertEquals($expectedOutput, $sortField->__toString());
    }

    public function testSortFieldDesc()
    {
        $expectedOutput = "data->>'field' DESC";

        $name      = 'field';
        $direction = SortField::SORT_DESCENDING;

        $sortField = new SortField($name, $direction);

        $this->assertEquals($expectedOutput, $sortField->__toString());
    }


}
