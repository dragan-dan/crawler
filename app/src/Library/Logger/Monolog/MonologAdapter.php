<?php

namespace Library\Logger\Monolog;

use Library\Logger\LoggerAdapterInterface;
use Monolog\Logger;

class MonologAdapter implements LoggerAdapterInterface
{
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @param $loggerName
     * @param $handlerOptions
     */
    public function __construct($loggerName, $handlerOptions)
    {
        $monologHandlerBuilder = new MonologHandlerFactory();
        $handlers = [];
        foreach ($handlerOptions as $options) {
            if (isset($options['enabled']) && !boolval($options['enabled'])) {
                continue;
            }
            $handlers[] = $monologHandlerBuilder->createHandler($options);
        }
        $this->logger = new Logger($loggerName, $handlers);
    }

    public function logDebug($message, array $context = [])
    {
        $this->logger->addDebug($message, $context);
    }

    public function logInfo($message, array $context = [])
    {
        $this->logger->addInfo($message, $context);
    }

    public function logNotice($message, array $context = [])
    {
        $this->logger->addNotice($message, $context);
    }

    public function logWarning($message, array $context = [])
    {
        $this->logger->addWarning($message, $context);
    }

    public function logError($message, array $context = [])
    {
        $this->logger->addError($message, $context);
    }

    public function logCritical($message, array $context = [])
    {
        $this->logger->addCritical($message, $context);
    }

    public function logAlert($message, array $context = [])
    {
        $this->logger->addAlert($message, $context);
    }

    public function logEmergency($message, array $context = [])
    {
        $this->logger->addEmergency($message, $context);
    }

    /**
     * Generic log method
     *
     * @param       $message
     * @param array $context
     * @param       $level
     */
    public function log($message, array $context = array(), $level)
    {
        $this->logger->log($level, $message, $context);
    }

}
