<?php

namespace Tests\Mocks;

use Library\Logger\LoggerAdapterInterface;

/**
 * Class MockLogAdapter - A mock LoggerAdapter implementation
 * @package Mock
 */
class MockLogAdapter implements LoggerAdapterInterface
{
    /**
     * Generic log method
     *
     * @param       $message
     * @param array $context
     * @param       $level
     */
    public function log($message, array $context = array(), $level) {}

    public function logDebug($message, array $context = []) {}

    public function logInfo($message, array $context = []) {}

    public function logNotice($message, array $context = []) {}

    public function logWarning($message, array $context = []) {}

    public function logError($message, array $context = []) {}

    public function logCritical($message, array $context = []) {}

    public function logAlert($message, array $context = []) {}

    public function logEmergency($message, array $context = []) {}
}
