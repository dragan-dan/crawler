<?php

namespace Library\IO\Interfaces;

/**
 * Interface ResponseMapperInterface
 * Wraps mapping functionality for Response
 *
 * @package Library\IO\Interfaces
 */
interface ResponseMapperInterface
{
    /**
     * @param bool $state
     * @param mixed $data
     * @param array $errors array of ErrorContainerInterface
     * @return mixed
     */
    public function generateMap($state, $data, $errors);
}
