<?php

namespace Library\DAO\Query\Field;

/**
 * Class Value
 * @package Library\DAO\Query\Field
 */
class Value
{
    /**
     * @var
     */
    protected $value;

    /**
     * @var AbstractFieldFunction[]
     */
    protected $functions;

    /**
     * Value constructor.
     *
     * @param                         $value
     * @param AbstractFieldFunction[] $functions
     */
    public function __construct($value, array $functions)
    {
        $this->value     = $value;
        $this->functions = $functions;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return AbstractFieldFunction[]
     */
    public function getFunctions()
    {
        return $this->functions;
    }

}
