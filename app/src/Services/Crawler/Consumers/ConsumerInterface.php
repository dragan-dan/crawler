<?php

namespace Services\Crawler\Consumers;

use Console\Command\Crawler\Actions\ConsumerActionInterface;
use Services\QTM\QTM;
use Services\Crawler\Messages\AbstractMessage;

interface ConsumerInterface
{
    public function __construct(QTM $qtm, ConsumerActionInterface $consumerAction);

    public function run(AbstractMessage $message);

    public function fetchWork($limit, $wait_time, $message_visibility_timeout);
}
