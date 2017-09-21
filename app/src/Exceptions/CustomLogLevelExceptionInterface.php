<?php

namespace Exceptions;

/**
 * This interface is used when an exception needs custom log level
 *
 * Interface CustomLogLevelExceptionInterface
 * @package Exceptions
 */
interface CustomLogLevelExceptionInterface
{
    /**
     * @return string. @see Psr\Log\LogLevel constants
     */
    public function getLogLevel();
}
