<?php

namespace Providers;

use Library\DAO\Postgre\PostgreConnectionFactory;
use Silex\Application;
use Silex\ServiceProviderInterface;

/**
 * Class PostgresDBConnectionProvider
 * @package Providers
 */
class PostgresDBConnectionProvider implements ServiceProviderInterface
{
    /**
     * @param Application $app
     */
    public function register(Application $app)
    {
        $app['db_connection'] = $app->share(function () use ($app) {
            $config = $app['config'];

            $connection = PostgreConnectionFactory::create($config->get('database_url'));
            return $connection;
        });
    }

    /**
     * @param Application $app
     */
    public function boot(Application $app)
    {
    }

}
