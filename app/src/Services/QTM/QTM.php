<?php
namespace Services\QTM;

use Library\UUID\UUID;
use Services\QTM\DAO\TaskDAO;
use Services\QTM\Exceptions\QTMException;
use GuzzleHttp\Client as HttpClient;
use Library\Logger\Logger;
use Services\Crawler\Messages\AbstractMessage;
use Uecode\Bundle\QPushBundle\Message\Message as QueueItem;

/**
 * Class QTM (Queue Task Manager)
 * @package Services\QTM
 */
class QTM
{
    const QUEUE_URL       = 'url';

    /**
     * Flag to track execution time of tasks
     *
     * @var bool
     */
    protected $trackTaskTime = true;

    /**
     * @var TaskDAO
     */
    protected $taskDAO;

    /**
     * @var callable
     */
    protected $queueProvider;

    /**
     * @var \Uecode\Bundle\QPushBundle\Provider\ProviderInterface[]
     */
    protected $queues = [];

    /**
     * @var string
     */
    protected $authToken;

    /**
     * Builds a QTM object
     *
     * @param TaskDAO       $taskDAO
     * @param callable      $queueProvider
     */
    public function __construct(TaskDAO $taskDAO, $queueProvider)
    {
        $this->taskDAO        = $taskDAO;
        $this->queueProvider  = $queueProvider;
        $this->http           = new HttpClient();
    }

    /**
     * Returns a list of known queues as <queue name> => <queue config|queue tag> pairs.
     *
     * @return array
     */
    private function getKnownQueues()
    {
        return [
            self::QUEUE_URL       => 'url',
        ];
    }

    /**
     * Generates a task id (md5 hash)
     *
     * @return string
     */
    private function generateTaskId()
    {
        return UUID::v4();
    }

    /**
     * Publishes a message to requested queue. If $callbackUrl is supplied
     * and not null, it will generate a new task and add the message to that task.
     * Otherwise, if a $taskId is provided, it will queue the message and add the
     * queued message to the existing task.
     *
     * @param  string       $queue        Queue to publish on
     * @param  array        $message      Message to publish
     * @param  null|string  $callbackUrl  Url to call after the task is completed (all messages consumed)
     * @param  null|string  $taskId       Existing task id to add the message to
     * @param  array        $options      Published message options
     *
     * @return array                      An [$messageId, $taskId] array.
     * @throws QTMException
     */
    public function publish($queue, array $message, $callbackUrl = null, $taskId = null, $options = [])
    {
        if (is_null($taskId)) {
            $taskId = isset($message['task_id']) ? $message['task_id'] : null;
        }

        if (is_null($taskId)) {
            // new job - create new task
            $taskId = $this->generateTaskId();

            // Option that indicates if task will be tracked for execution time
            $this->taskDAO->save($taskId, [], TaskDAO::STATUS_ENABLED, $callbackUrl, $this->trackTaskTime);
        }

        Logger::logDebug(
            'Publishing message to the queue',
            ['queue' => $queue, 'message' => $message, Logger::TASK_ID_KEY => $taskId, 'callback_url' => $callbackUrl]
        );

        $queueInstance = $this->getQueue($queue);

        $message['task_id'] = $taskId;
        $messageId = $queueInstance->publish($message, $options);

        $this->taskDAO->addSubTask($taskId, $messageId);

        return [$messageId, $taskId];
    }

    /**
     * Removes message from task message list and call callback url if task has ended
     *
     * @param string          $queue
     * @param AbstractMessage $message
     *
     * @throws QTMException
     * @throws \Exceptions\DBException
     */
    public function finish($queue, $message)
    {
        Logger::logDebug(
            'Finished processing message.',
            ['queue' => $queue, 'message' => ($message instanceof AbstractMessage) ? $message->toArray() : $message]
        );

        $queueInstance = $this->getQueue($queue);

        if ($message instanceof AbstractMessage) {
            $messageId     = $message->messageId;
            $taskId        = $message->taskId;
            $messageHandle = $message->messageHandle;
        } elseif (is_array($message)) {
            $messageId      = $message['message_id'];
            $taskId         = $message['task_id'];
            $messageHandle  = isset($message['message_handle']) ? $message['message_handle'] : null;
        } else {
            Logger::logError(sprintf('Trying to remove message of type %s and class %s', gettype($message), get_class($message)));
        }

        //delete message from queue
        if (!empty($messageHandle)) {
            Logger::logInfo(sprintf('Removing message with handle: %s', $messageHandle));
            $queueInstance->delete($messageHandle);
        } else {
            Logger::logInfo(sprintf('Removing message with id: %s', $messageId));
            $queueInstance->delete($messageId);
        }

        if (isset($taskId)) {
            // remove from the messageList of the task
            $this->taskDAO->removeSubTask($taskId, $messageId);

            //check if the task is finished and call the callback url
            $task = $this->taskDAO->findTaskById($taskId);

            if ($this->taskDAO->isTaskComplete($task)) {
                Logger::logDebug(
                    'Task finished',
                    ['task' => $queue]
                );

                if (isset($task[TaskDAO::KEY_CALLBACK_URL])) {
                    $this->callCallbackUrl($task);
                }

                /*
                 * Because task['track'] is introduced with SC-1967, old tasks in db won't have this field.
                 * So if it is not sent, we're tracking the execution time anyway. Later this can be removed after all
                 * the tasks are created with track key
                 */
                if (!isset($task['track'])
                    || (bool)$task['track']) {
                    $this->logExecutionTime($task);
                }

                $this->taskDAO->deleteTask($taskId);
            }
        }
    }

    /**
     * Delete message from queue by messageId
     *
     * @param $queue
     * @param $message
     * @throws QTMException
     */
    public function deleteMessage($queue, $message)
    {
        if ($message instanceof AbstractMessage) {
            $messageId     = $message->messageId;
            $messageHandle = $message->messageHandle;
        } elseif (is_array($message)) {
            $messageId      = $message['id'];
            $messageHandle  = isset($message['handle']) ? $message['handle'] : null;
        }

        $queueInstance = $this->getQueue($queue);

        //delete message from queue
        if (!empty($messageHandle)) {
            $queueInstance->delete($messageHandle);
        } else {
            $queueInstance->delete($messageId);
        }
    }

    /**
     * Get queue by queue name and lazy load the queue
     *
     * @param $queue
     * @return \Uecode\Bundle\QPushBundle\Provider\ProviderInterface
     * @throws QTMException
     */
    protected function getQueue($queue)
    {
        if (!isset($this->queues[$queue])) {
            $knownQueues = $this->getKnownQueues();
            if (isset($knownQueues[$queue])) {
                $provider = $this->queueProvider;
                $this->queues[$queue] = $provider($knownQueues[$queue]);
            } else {
                throw new QTMException("Queue $queue is unknown");
            }
        }

        return $this->queues[$queue];
    }

    /**
     * Receiving message from queue
     *
     * @param $queue
     * @param $options
     * @return array
     * @throws QTMException
     */
    public function receive($queue, $options)
    {
        $queueInstance = $this->getQueue($queue);

        return $this->formatQueueItems($queueInstance->receive($options));
    }

    /**
     * Calls the given URL
     *
     * @param string $url
     * @param array  $params
     *
     * @return bool|\Psr\Http\Message\ResponseInterface
     */
    protected function call($url, $params = [])
    {
        $options = [
            'json' => $params,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->authToken
            ]
        ];

        try {
            $this->http->post($url, $options);
            return true;
        } catch (\Exception $e) {
            Logger::logError($e->getMessage(), [$e]);
            return false;
        }
    }

    /**
     * Formats the external queue component items into plain data items (arrays)
     *
     * @param QueueItem[] $items
     *
     * @return array of message data
     */
    private function formatQueueItems(array $items = [])
    {
        $messages = [];

        foreach ($items as $item) {
            $id = $message = $handle = null;

            if (!empty($item)) {
                $id = $item->getId();
                $message = $item->getBody();
                $handle = $this->extractQueueMessageHandle($item);
            } else {
                Logger::logWarning("[QTM] Queue returned invalid message", [$item]);
            }

            $messages[] = [
              'id' => $id,
              'message' => $message,
              'handle' => $handle
            ];
        }

        return $messages;
    }

    /**
     * Gets the message handle for the queues that support such thing
     *
     * @param QueueItem $item
     * @return null|string
     */
    private function extractQueueMessageHandle(QueueItem $item)
    {
        // this is valid only for SQS queues so far
        // this will probably be replaced with a switch statement in the future
        $meta = $item->getMetadata();
        if (isset($meta['ReceiptHandle'])) {
            return $meta['ReceiptHandle'];
        }

        return null;
    }

    /**
     * get task status
     *
     * @param $taskId
     * @return null|string
     */
    public function getTaskStatus($taskId)
    {
        return $this->taskDAO->getTaskStatus($taskId);
    }

    /**
     * Generate an empty task
     *
     * @param string      $status - status of see const params STATUS_ENABLED, STATUS_DISABLED
     * @param string|null $callbackUrl
     *
     * @return string
     * @throws \Exceptions\DBException
     */
    public function generateEmptyTask($status = TaskDAO::STATUS_ENABLED, $callbackUrl = null)
    {
        $taskId = $this->generateTaskId();
        $this->taskDAO->save($taskId, [], $status, $callbackUrl);
        return $taskId;
    }

    /**
     * update task status
     *
     * @param $taskId
     * @param $status
     * @return bool
     */
    public function updateTaskStatus($taskId, $status)
    {
        return $this->taskDAO->updateStatus($taskId, $status);
    }

    /**
     * Logs the tasks execution time
     *
     * @param array $task
     */
    protected function logExecutionTime($task)
    {
        //log the total execution time to process the entire task
        $executionTime = time() - $task['create_time'];
        Logger::logInfo(
            '** Task - finished',
            [
                Logger::PROCESS_KEY        => 'QTM',
                Logger::ACTION_KEY         => 'finishTask',
                Logger::TASK_ID_KEY        => $task['task_id'],
                Logger::EXECUTION_TIME_KEY => $executionTime
            ]
        );
    }

    /**
     * Calls the tasks callback URL
     *
     * @param array $task
     */
    private function callCallbackUrl($task)
    {
        Logger::logDebug('Calling the task callback_url for:' . $task['task_id'], ['task' => $task]);

        $this->call($task[TaskDAO::KEY_CALLBACK_URL]);
    }

    /**
     * Sets the time tracking setting of QTM
     *
     * @param bool $value
     */
    public function setTaskTimeTracking($value)
    {
        $this->trackTaskTime = (bool)$value;
    }
}
