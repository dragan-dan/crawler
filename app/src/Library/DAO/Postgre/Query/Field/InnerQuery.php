<?php
namespace Library\DAO\Postgre\Query\Field;

use Library\DAO\Query\Field\AbstractSelectField;
use Library\DAO\Query\QueryInterface;

/**
 * Class AbstractSelectField - Represents a select field in DB PostgresQuery
 *
 * @package Library\DAO\Postgre\PostgresQuery
 */
class InnerQuery extends AbstractSelectField
{
    /**
     * @var QueryInterface
     */
    public $query;

    /**
     * @param QueryInterface $query
     * @param string         $alias
     */
    public function __construct($query, $alias)
    {
        $this->query     = $query;
        $this->alias     = $alias;
        $this->fieldType = 'innerQuery';
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $sql = $this->query->getSelectQuery(false);

        $sql = '(' . $sql . ') as ' . $this->alias;

        return $sql;
    }

    public function getBindParams()
    {
        return $this->query->getBindParams();
    }
}

