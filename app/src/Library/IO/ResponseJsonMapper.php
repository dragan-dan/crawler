<?php

namespace Library\IO;


use Library\IO\Interfaces\ResponseMapperInterface;

/**
 * Class ResponseJsonMapper
 * Json mapper implementation
 *
 * @package Library\IO
 */
class ResponseJsonMapper implements ResponseMapperInterface
{
    /**
     * Generates map structure
     *
     * @param bool $state
     * @param mixed $data
     * @param array $errors array of ErrorContainerInterface
     * @return mixed
     */
    public function generateMap($state, $data, $errors)
    {
        return array('success' => $state, 'response' => $data, 'errors' => $errors);
    }
}
