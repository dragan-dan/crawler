<?php

namespace Library\DAO\Query\Field;

class AbstractSortField
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $direction;

    const SORT_ASCENDING  = 'ASC';
    const SORT_DESCENDING = 'DESC';
} 
