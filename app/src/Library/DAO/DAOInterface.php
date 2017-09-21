<?php

namespace Library\DAO;

use Exceptions\DBException;
use Library\DAO\Query\QueryInterface;

interface DAOInterface
{
    /**
     * @param QueryInterface $query
     * @return array
     */
    public function findOne($query);
    
    /**
     * @param QueryInterface $query
     * @return array
     */
    public function find($query);
    
    /**
     * @param QueryInterface $query
     * @return resource
     */
    public function insert($data);
    
    /**
     * @param QueryInterface $query
     * @return bool
     */
    public function remove($query);
    
    /**
     * @param QueryInterface $query
     * @return bool
     */
    public function update($query);
    
    /**
     * @param QueryInterface $query
     * @return resource
     */
    public function beginTransaction($query = null);
    
    /**
     * @param QueryInterface $query
     * @return resource
     */
    public function commitTransaction($query = null);
    
    /**
     * @param QueryInterface $query
     * @return resource
     */
    public function rollbackTransaction($query = null);
}
