<?php
namespace Library\DAO\Postgre\Query\Field;

use Library\DAO\Query\Field\WhereFieldInterface;

class WhereOrField implements WhereFieldInterface
{
    /**
     * @var WhereFieldInterface[]
     */
    private $whereFields = [];

    /**
     * WhereOrField constructor.
     *
     * @param \Library\DAO\Query\Field\WhereFieldInterface[] $whereFields
     */
    public function __construct(array $whereFields)
    {
        $this->whereFields = $whereFields;
    }


    /**
     * @return string
     */
    public function __toString()
    {
        $sql = '(' . implode(' OR ', $this->whereFields) . ')';

        return $sql;
    }

    /**
     * Returns an array of bind parameters. Does not support nested arrays.
     *
     * @return array
     */
    public function getBindParam()
    {
        $allBindParams = [];

        foreach ((array)$this->whereFields as $whereField) {
            /** @var WhereFieldInterface $whereField */
            $bindParams = $whereField->getBindParam();
            foreach ($bindParams as $param) {
                $allBindParams[] = $param;
            }
        }

        return $allBindParams;
    }

} 
