<?php

namespace Exceptions;

/**
 * Class BaseValidationException
 * Has to be extended in sub services with particular validation exceptions
 *
 * @package Exceptions
 */
class BaseValidationException extends BadRequestException
{
    const MESSAGE_DEFAULT = 'Validation exceptions: %s';

    /**
     * Creates a BaseValidation exception
     *
     * @param string|array $message - Error message. If has parameters then array that has the message
     *                              as first element and parameters as other elements
     * @param int          $code
     * @param \Exception   $previous
     */
    public function __construct(
        $message = self::MESSAGE_DEFAULT,
        $code = 0,
        \Exception $previous = null
    ) {
        $parameters   = [];

        if (is_array($message)) {
            $errorMessage = array_shift($message);
            $parameters   = $message;
        } else {
            $errorMessage = $message;
        }

        if (!empty($parameters)) {
            $message = vsprintf($errorMessage, $parameters);
        }
        parent::__construct($message, $code, $previous);
    }
}
