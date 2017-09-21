<?php

namespace Providers\Services;

use Services\Mock\DAO\MockDAO;
use Services\Mock\Mock;
use Silex\Application;
use Silex\ServiceProviderInterface;

/**
 * Responsible for registering and booting the mock service
 *
 * Class MockServiceProvider
 * @package Providers\Services
 */
class MockServiceProvider implements ServiceProviderInterface
{
    /**
     * Registers the mock_service
     *
     * @param Application $app
     */
    public function register(Application $app)
    {
        $app['mock_service'] = $app->share(
            function () use ($app) {
                $reservationConn = $app['reservations_db_connection'];
                $mockDAO         = new MockDAO($reservationConn, '');

                return new Mock($mockDAO);
            }
        );
    }

    /**
     * Bootstraps the application
     *
     * @param Application $app
     */
    public function boot(Application $app)
    {
    }
}
