<?php

namespace Services\Crawler\Exceptions;

use Exceptions\BadRequestException;

/**
 * Class MissingInputException
 * Non catchable exception on the service level
 *
 * @package Services\Crawler\Exceptions
 */
class MissingInputException extends BadRequestException
{
    const MESSAGE_URL_MISSING = 'URL input missing';
}
