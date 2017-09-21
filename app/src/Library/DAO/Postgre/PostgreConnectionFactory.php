<?php

namespace Library\DAO\Postgre;

use Library\DAO\ConnectionAdapterInterface;
use Exceptions\DBException;
use Library\Logger\Logger;

class PostgreConnectionFactory implements ConnectionAdapterInterface
{

    /**
     * Creates a db connection resource
     *
     * @param string $url - Postgres connection URL
     *
     * @return resource
     * @throws DBException
     */
    public static function create($url)
    {
        $forceNewConnection = defined('_DB_FORCE_NEW_CONNECTION') && true ===_DB_FORCE_NEW_CONNECTION
            ? PGSQL_CONNECT_FORCE_NEW : null;

        $resource = pg_connect($url, $forceNewConnection);

        if (FALSE == $resource) {
            // database connection failed
            throw new DBException(DBException::MESSAGE_DATABASE_CONNECTION);
        }

        return $resource;
    }

    /**
     * Close a db connection
     *
     * @param resource $connection
     * @throws DBException
     */
    public static function close($connection)
    {
        if (!is_resource($connection)) {
            throw new DBException(DBException::MESSAGE_DATABASE_CONNECTION_BROKEN);
        }

        if (!$connection || !pg_close($connection)) {
            Logger::logError('Could not close connection ' . pg_last_error($connection));
        }
    }
}
