<?php

namespace Library\Logger\Monolog;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\NewRelicHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Logger;

class MonologHandlerFactory
{
    /**
     * @param $type
     *
     * @return AbstractProcessingHandler
     */
    public function createHandler($params)
    {
        $type   = $params['type'];
        $level  = isset($params['level']) ? $this->getLogLevelValue($params['level']) : Logger::DEBUG;
        $bubble = isset($params['bubble']) ? boolval($params['bubble']) : true;

        // TODO: add other handler types
        switch ($type) {
            case 'newrelic':
                if (!extension_loaded('newrelic')) {
                    return;
                }
                $appName            = isset($params['appName']) ? $params['appName'] : null;
                $explodeArrays      = isset($params['explodeArrays']) ? $params['explodeArrays'] : false;
                $transactionName    = isset($params['transactionName']) ? $params['transactionName'] : null;
                $handler            = new NewRelicHandler($level, $bubble, $appName, $explodeArrays, $transactionName);
                break;
            case 'stream':
            default:
                $path            = $params['path'] ? : '/tmp/app.log';
                $filePermissions = isset($params['filePermissions']) ? $params['filePermissions'] : null;
                $useLocking      = isset($params['useLocking']) ? boolval($params['useLocking']) : false;
                $handler         = new StreamHandler($path, $level, $bubble, $filePermissions, $useLocking);
                break;
        }

        if (isset($params['line_format']) && is_array($params['line_format']) && ! empty($params['line_format'])) {
            $lineFormat = $params['line_format'];

            $format = isset($lineFormat['format']) ? $lineFormat['format'] : null;
            $dateFormat = isset($lineFormat['date_format']) ? $lineFormat['date_format'] : null;
            $allowInlineLineBreaks = isset($lineFormat['allow_inline_breaks'])
                ? (bool)$lineFormat['allow_inline_breaks']
                : false;
            $ignoreEmptyContextAndExtra = isset($lineFormat['ignore_empty_context_and_extra'])
                ? (bool)$lineFormat['ignore_empty_context_and_extra']
                : false;

            $formatter = new LineFormatter($format, $dateFormat, $allowInlineLineBreaks, $ignoreEmptyContextAndExtra);
            $handler->setFormatter($formatter);
        }

        return $handler;
    }

    /**
     * Possible options are debug, info, notice, warning, error, critical, alert, emergency
     *
     * @param $logLevel
     *
     * @return int
     */
    public function getLogLevelValue($logLevel)
    {
        switch ($logLevel) {
            case 'emergency':
                $level = Logger::EMERGENCY;
                break;
            case 'alert':
                $level = Logger::ALERT;
                break;
            case 'critical':
                $level = Logger::CRITICAL;
                break;
            case 'error':
                $level = Logger::ERROR;
                break;
            case 'warning':
                $level = Logger::WARNING;
                break;
            case 'notice':
                $level = Logger::NOTICE;
                break;
            case 'info':
                $level = Logger::INFO;
                break;
            case 'debug':
            default:
                $level = Logger::DEBUG;
                break;
        }

        return $level;
    }

}
