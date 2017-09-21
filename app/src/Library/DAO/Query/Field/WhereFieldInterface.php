<?php

namespace Library\DAO\Query\Field;

interface WhereFieldInterface
{
    /**
     * @return string
     */
    public function __toString();

    /**
     * Returns an array of bind parameters. Does not support nested arrays.
     *
     * @return array
     */
    public function getBindParam();
} 
