<?php

namespace Console\Command\Crawler\Actions;

interface ConsumerActionInterface
{
    /**
     * @param array $params
     */
    public function crawl($params);
}
