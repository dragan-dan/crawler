<?php

namespace Library\Logger;

class Logger
{
    const URL_KEY = 'url'; // should be used the same key as in nginx logs
    const TASK_ID_KEY = 'task_id';
    const PROCESS_KEY = 'process';
    const ACTION_KEY = 'action';
    const EXECUTION_TIME_KEY = 'execution_time';
    /**
     * When adding new key check with DevOps team if it present in `accepted_keys` from application grok filter:
     * @link https://github.com/ticketscript/ticketscript-elk-grok/blob/develop/files/filters/application.conf
     */

    /**
     * @var LoggerAdapterInterface
     */
    private static $loggerInstance;

    /**
     * @param LoggerAdapterInterface $loggerAdapter
     */
    public static function setLoggerAdapter($loggerAdapter)
    {
        self::$loggerInstance = $loggerAdapter;
    }

    /**
     * @return LoggerAdapterInterface
     * @throws \Exception
     */
    private static function getLoggerAdapter()
    {
        if (null === self::$loggerInstance) {
            throw new \Exception('No logger instance set. Please set one with method setLoggerAdapter on your bootstrap');
        }

        return self::$loggerInstance;
    }

    /**
     * @param       $message
     * @param       $level
     * @param array $context
     *
     * @throws \Exception
     */
    public static function log($message, $level, $context = array())
    {
        $logger = self::getLoggerAdapter();
        $logger->log($message, $context, $level);
    }

    public static function logDebug($message, array $context = [])
    {
        $logger = self::getLoggerAdapter();
        $logger->logDebug($message, $context);
    }

    public static function logInfo($message, array $context = [])
    {
        $logger = self::getLoggerAdapter();
        $logger->logInfo($message, $context);
    }

    public static function logNotice($message, array $context = [])
    {
        $logger = self::getLoggerAdapter();
        $logger->logNotice($message, $context);
    }

    public static function logWarning($message, array $context = [])
    {
        $logger = self::getLoggerAdapter();
        $logger->logWarning($message, $context);
    }

    public static function logError($message, array $context = [])
    {
        $logger = self::getLoggerAdapter();
        $logger->logError($message, $context);
    }

    public static function logCritical($message, array $context = [])
    {
        $logger = self::getLoggerAdapter();
        $logger->logCritical($message, $context);
    }

    public static function logAlert($message, array $context = [])
    {
        $logger = self::getLoggerAdapter();
        $logger->logAlert($message, $context);
    }

    public static function logEmergency($message, array $context = [])
    {
        $logger = self::getLoggerAdapter();
        $logger->logEmergency($message, $context);
    }
}
