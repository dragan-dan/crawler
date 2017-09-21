<?php

namespace Library\IO;

use Silex\Application;

/**
 * Class Response
 * Handles Http Response
 * In charge of format and type of error
 * Depends on response skeleton(schema)
 * @package Services\IO
 */
class Request
{
    /** @var  \Symfony\Component\HttpFoundation\Request */
    protected $request;

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function __construct($request)
    {
        $this->request = $request;
    }

    /**
     * @param string $param
     * @param mixed $defaultValue
     * @return mixed
     */
    public function getParam($param, $defaultValue = '')
    {
        return $this->request->get($param, $defaultValue);
    }

    /**
     * @param string $param
     * @param mixed $defaultValue
     * @return mixed
     */
    public function getHeader($param, $defaultValue = '')
    {
        return $this->request->headers->get($param, $defaultValue);
    }

    /**
     * @return bool
     */
    public function isXmlHttpRequest()
    {
        return $this->request->isXmlHttpRequest();
    }

}
