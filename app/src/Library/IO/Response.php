<?php

namespace Library\IO;

use Exceptions\BadRequestException;
use Exceptions\ConflictException;
use Exceptions\DuplicateExceptionInterface;
use Exceptions\ForbiddenException;
use Exceptions\MandatoryExceptionInterface;
use Exceptions\NotFoundException;
use Exceptions\NotImplementedException;
use Exceptions\ResponseMessageInterface;
use Exceptions\ServiceUnavailableException;
use Library\Interfaces\ErrorContainerInterface;
use Library\IO\Interfaces\ResponseMapperInterface;
use Silex\Application;

/**
 * Class Response
 * Handles Http Response
 * In charge of format and type of error
 * Depends on response skeleton(schema)
 * @package Services\IO
 */
class Response
{
    const ERROR_UNKNOWN             = "unknown error";
    const ERROR_BAD_REQUEST         = "bad request";
    const ERROR_FORBIDDEN           = "forbidden";
    const ERROR_NOT_IMPLEMENTED     = "not implemented";
    const ERROR_NOT_FOUND           = "not found";
    const ERROR_CONFLICT            = "conflict";
    const ERROR_SERVICE_UNAVAILABLE = "service unavailable";
    const ERROR_INTERNAL            = "internal server";

    const ERROR_TYPE_MANDATORY = 'mandatory';
    const ERROR_TYPE_DUPLICATE = 'duplicate';

    /**
     * @var Application
     */
    private $silexApp;
    /**
     * Response format
     * @var string
     */
    private $responseStructure;
    /**
     * @var ResponseMapperInterface
     */
    private $mapper;

    /**
     * Formatting added as a setting placeholder. Feel free to add format switch functionality if you gonna need it
     * @param $silexApp
     * @param string $format
     */
    public function __construct($silexApp, $format = 'json')
    {
        $this->mapper = MapperFactory::create($format);
        $this->silexApp = $silexApp;
        $this->responseStructure = $format;
    }

    /**
     * Responds with data.
     *
     * @param array|ErrorContainerInterface $serviceReply
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Exception
     */
    public function respond($serviceReply)
    {
        switch ($this->responseStructure) {
            case 'json':
                $success        = false;
                $errors         = array();
                $responseStruct = [];

                if ($serviceReply === true) {
                    $success = true;
                } elseif (is_array($serviceReply)) {
                    $responseStruct = $serviceReply;
                    $success = true;
                }

                $responseData = $this->mapper->generateMap($success, $responseStruct, $errors);
                $response     = $this->silexApp->json($responseData);
                break;
            default:
                $response = $this->failed(new NotImplementedException('Format not implemented ' . $this->responseStructure));
                break;
        }

        return $response;
    }

    /**
     * Creates failed response depending in exception received
     */
    public function failed(\Exception $exception)
    {
        if ($exception instanceof BadRequestException) {
            $code = 400;
            $failure = $this->setErrorMessageFromException($exception, self::ERROR_BAD_REQUEST);
        } elseif ($exception instanceof NotImplementedException) {
            $code = 501;
            $failure = $this->setErrorMessageFromException($exception, self::ERROR_NOT_IMPLEMENTED);
        } elseif ($exception instanceof ForbiddenException) {
            $code = 403;
            $failure = $this->setErrorMessageFromException($exception, self::ERROR_FORBIDDEN);
        } elseif ($exception instanceof NotFoundException) {
            $code = 404;
            $failure = $this->setErrorMessageFromException($exception, self::ERROR_NOT_FOUND);
        } elseif ($exception instanceof ConflictException) {
            $code = 409;
            $failure = $this->setErrorMessageFromException($exception, self::ERROR_CONFLICT);
        } elseif ($exception instanceof ServiceUnavailableException) {
            $code = 503;
            $failure = $this->setErrorMessageFromException($exception, self::ERROR_SERVICE_UNAVAILABLE);
        } else {
            $code = 500;
            $failure = $this->setErrorMessageFromException($exception, self::ERROR_INTERNAL);
        }
        $format = $this->responseStructure;
        $response = [];

        // Check exception type
        $type = self::ERROR_TYPE_MANDATORY;
        if ($exception instanceof DuplicateExceptionInterface) {
            $type = self::ERROR_TYPE_DUPLICATE;
        } else if ($exception instanceof MandatoryExceptionInterface) {
            $type = self::ERROR_TYPE_MANDATORY;
        }

        // Add error response message
        if ($exception instanceof ResponseMessageInterface) {
            $response = $exception->getResponse();
        }

        return $this->silexApp->$format(
            $this->mapper->generateMap(
                false,
                $response,
                array('message' => $failure, 'type' => $type)
            ))->setStatusCode($code);
    }

    /**
     * Sets default message from provided exception instance
     *
     * @param \Exception $exception
     * @param string $defaultMessage
     * @return string
     */
    private function setErrorMessageFromException($exception, $defaultMessage)
    {
        return $exception->getMessage() == null ? $defaultMessage : $exception->getMessage();
    }
}

