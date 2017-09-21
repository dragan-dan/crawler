<?php

namespace Services\Crawler\Consumers;

use Console\Command\Crawler\Actions\ConsumerActionInterface;
use Services\QTM\QTM;
use Services\Crawler\Messages\AbstractMessage;
use Services\Crawler\Messages\CrawlerMessage;
use Services\Crawler\Crawler;
use Library\Logger\Logger;

class CrawlerConsumer extends AbstractConsumer implements ConsumerInterface
{
    /**
     * @var Crawler CrawlerConsumerAction
     */
    private $crawlerAction;

    /**
     * @param QTM                     $qtm
     * @param ConsumerActionInterface $consumerAction
     */
    public function __construct(QTM $qtm, ConsumerActionInterface $consumerAction)
    {
        $this->crawlerAction = $consumerAction;
        parent::__construct($qtm);
    }

    /**
     * This method calls the API endpoint that handles crawling.
     * It is called through the CrawlerCommand (CLI) or the QueueSync service.
     *
     * @param AbstractMessage $message
     *
     * @throws \Exceptions\ApiException
     */
    public function run(AbstractMessage $message)
    {
        $url            = $message->url;
        $messageId      = $message->messageId;
        $taskId         = $message->taskId;
        $messageHandle  = $message->messageHandle;

        try {
            $this->crawlerAction->crawl(['url' => $url, 'taskId' => $taskId]);
        } finally {
            if (!empty($messageId) || !empty($messageHandle)) {
                $this->qtm->finish(QTM::QUEUE_URL, $message);
            }
        }
    }

    /**
     * Fetch available work from the queueService.
     *
     * @param $limit
     * @param $waitTime
     * @param $messageVisibilityTimeout
     *
     * @return array
     */
    public function fetchWork($limit, $waitTime, $messageVisibilityTimeout=null)
    {
        $options = [
            'messages_to_receive'        => $limit,
            'receive_wait_time'          => $waitTime
        ];

        if (null !== $messageVisibilityTimeout) {
            $options['message_visibility_timeout'] = $messageVisibilityTimeout;
        }

        $queueItems = $this->qtm->receive(QTM::QUEUE_URL, $options);
        $workUnits = [];

        foreach ($queueItems as $queueItem) {
            $taskId = isset($queueItem['message']['task_id']) ? $queueItem['message']['task_id'] : null;
            if (!$this->hasValidTask($taskId)) {
                Logger::logWarning(
                    'Delete message when no task is present.',
                    [
                        'queue'   => QTM::QUEUE_URL,
                        'message' => ($queueItem['message'] instanceof AbstractMessage) ?
                            $queueItem['message']->toArray() :
                            $queueItem['message']
                    ]
                );
                // delete message from queue when task is not found
                $this->qtm->deleteMessage(QTM::QUEUE_URL, $queueItem);
                continue;
            };
            $workUnits[] = new CrawlerMessage(
                $queueItem['message']['url'],
                $queueItem['id'],
                $taskId,
                $queueItem['handle']
            );
        }
        
        return $workUnits;
    }
}
