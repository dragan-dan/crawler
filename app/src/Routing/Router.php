<?php

namespace Routing;

use Exceptions\CustomLogLevelExceptionInterface;
use Exceptions\NotFoundException;
use Library\Logger\Logger;
use Services\Factory;
use Library\IO\Response;
use Silex\Application;
use Silex\Controller;
use Symfony\Component\HttpFoundation\Request;


/**
 * Class Router
 * Router wrapper. Handles mapping between routes and services
 * Instantiates service when matching route found
 * @package Routing
 */
class Router
{
    /**
     * @var Application
     */
    private $silexApp;

    /**
     * @var Response
     */
    private $responder;

    const AUTH_HEADER_NAME = 'Authorization';
    const AUTH_KEY_PREFIX  = 'Bearer';

    /**
     * Create new router
     * @param Application $silexApp
     * @param Response $responder
     */
    public function __construct(Application $silexApp, Response $responder)
    {
        $this->silexApp = $silexApp;
        $this->responder = $responder;
    }

    /**
     * Route if found in mapping
     * Pass response to the responder
     *
     * @param $mapping array([] =>'route' => array('service' => 'service class name', 'action' => 'method name'))
     */
    public function route($mapping)
    {
        $app = $this->silexApp;

        foreach ($mapping as $map) {
            $proxy = function () use ($map, $app) {

                $serviceName = $map['service'];
                $serviceNameWithExt = $serviceName . '_service';

                if ($app->offsetExists($serviceNameWithExt)) {
                    // Get service from app container
                    $service = $app[$serviceNameWithExt];
                } else {
                    // Create service using factory
                    $service = Factory::create($serviceName);
                }
                $action = $map['action'];

                try {
                    $serviceResponse = call_user_func_array(
                        [$service, $action],
                        $this->getParamsFromRequest($app['request'])
                    );
                    return $this->responder->respond($serviceResponse);
                } catch (\Exception $e ) {

                    // Handle exception logging
                    if ($e instanceof CustomLogLevelExceptionInterface) {
                        Logger::log($e, $e->getLogLevel(), []);
                    } elseif ($e instanceof NotFoundException) {
                        Logger::logWarning($e);
                    } else {
                        Logger::logError($e);
                    }

                    // Respond to exception
                    return $this->responder->failed($e);
                }
            };

            $matcher = $this->silexApp->match($map['route'], $proxy);
            $this->setDefaultAndRequiredParamsToMatcher($map, $matcher);
            $this->setHttpMethod($matcher, $map['methods']);
        }
    }

    /**
     * Get parameters from get and post and sotre them in params
     * @param Request $request
     * @return array
     */
    private function getParamsFromRequest($request)
    {
        $params = [];

        $routeParams = $request->attributes->get('_route_params');
        $params = $params + $routeParams;

        // Get query string parameters
        $inputParams = $request->query->all();

        // Get POST, PUT params
        if (in_array($request->getMethod(), ['POST', 'PUT'])) {
            $inputParams += $request->request->all();
        }

        $params[] = $inputParams;

        return $params;
    }

    private function setHttpMethod(Controller $matcher, $methods)
    {
        $matcher->method(implode($methods, '|'));
    }

    /**
     * @param $params
     * @param $matcher
     * @param $func function name to call on matcher
     */
    private function applyParamsToMatcher($params, $matcher, $func)
    {
        foreach ($params as $paramKey => $paramValue) {
            $matcher->$func($paramKey, $paramValue);
        }
    }

    /**
     * @param $map
     * @param $matcher
     */
    private function setDefaultAndRequiredParamsToMatcher($map, $matcher)
    {
        if (isset($map['defaults'])) {
            // Set Default route param values
            $this->applyParamsToMatcher($map['defaults'], $matcher, 'value');
        }
        if (isset($map['requirements'])) {
            //Set Required parameters to matcher
            $this->applyParamsToMatcher(isset($map['requirements']) ? $map['requirements'] : [], $matcher, 'assert');
        }
    }

    /**
     * Get request token without bearer
     *
     * @return bool|string
     */
    private function getRequestToken($apiAuthString)
    {
        if (strpos($apiAuthString, self::AUTH_KEY_PREFIX) === 0) {
            return trim(substr($apiAuthString, strlen(self::AUTH_KEY_PREFIX)));
        }
        return false;
    }
}
