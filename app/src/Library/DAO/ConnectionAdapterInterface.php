<?php

namespace Library\DAO;

/**
 * Class ConnectionAdapterInterface
 * Common Interface for database connections
 * @package Library\DAO
 */
interface ConnectionAdapterInterface
{
    /**
     * @param $url
     *
     * @return resource
     */
    public static function create($url);
}
