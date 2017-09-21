<?php

use Services\Factory;

define('PATH_ROOT', dirname(__DIR__));
define('SRC_PATH', PATH_ROOT . '/src/');
define('DEBUG', getenv('DEBUG') ? getenv('DEBUG') : false);

$loader = require_once  PATH_ROOT . '/vendor/autoload.php';

$app          = new Silex\Application();
$app['debug'] = DEBUG;

// Add config
$config        = new \Config\Config();
$app['config'] = $config;

// Initialize Factory with app
Factory::init($app);

// Set Logger config (Monolog Implementation)
$loggerAdapter = new \Library\Logger\Monolog\MonologAdapter(
    $config->get('monolog.logger_name'),
    $config->get('monolog.handler')
);

\Library\Logger\Logger::setLoggerAdapter($loggerAdapter);
//set output error handler for silex web app
setSilexErrorHandler($app);

// Register message queue provider
$app->register(new \Library\Messaging\QueueProvider(), array());

$app->register(new \Providers\PostgresDBConnectionProvider());

/**
 * Set default error handler for silex app
 *
 * @param \Silex\Application $silexApp
 */
function setSilexErrorHandler(Silex\Application $silexApp)
{
    $silexApp->error(
        function (\Exception $e, $code) use ($silexApp) {
            \Library\Logger\Logger::logError(
                $e->getMessage(),
                ['exception' => $e]
            );

            if (php_sapi_name() != 'cli') { //json response type is applicable only in case of http requests
                /**
                 * Hack to determine output.
                 */
                if (strpos($_SERVER['REQUEST_URI'], 'json') !== false) {
                    return new \Symfony\Component\HttpFoundation\JsonResponse(
                        [
                            'error' => true,
                            'message' => $e->getMessage(),
                            'stack' => $silexApp['debug'] ? $e->getTrace() : []
                        ],
                        $code
                    );
                }
            }
        }
    );
}
