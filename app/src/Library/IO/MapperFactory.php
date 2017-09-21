<?php

namespace Library\IO;

/**
 * Class MapperFactory
 * Creates mapper of particular type
 *
 * @package Library\IO
 */
class MapperFactory
{
    const MAPPER_TYPE_JSON = 'json';
    const ERROR_MAPPER_NOT_FOUND = 'mapper not found';

    /**
     * @param $type
     * @return ResponseMapperInterface
     */
    public static function create($type)
    {
        if ($type == self::MAPPER_TYPE_JSON) {
            return new ResponseJsonMapper();
        }
        throw new \Exception(self::ERROR_MAPPER_NOT_FOUND);
    }
}
