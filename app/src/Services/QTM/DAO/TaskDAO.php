<?php
namespace Services\QTM\DAO;

use Library\DAO\Postgre\BaseDAO;
use Library\DAO\Postgre\Query\Field\FieldFunction;
use Library\DAO\Query\QueryFactory;
use Exceptions\DBException;
use Library\Logger\Logger;

/**
 * Class TaskDAO
 * @package Services\QTM\DAO
 */
class TaskDAO extends BaseDAO
{
    /*
     * Table name that stores sub_tasks
     */
    protected $subtaskTable = 'sub_tasks';
    const KEY_TASK_ID       = 'task_id';

    const KEY_MESSAGE_LIST  = 'message_list';
    const KEY_STATUS        = 'status';
    const KEY_CREATE_TIME   = 'create_time';
    const KEY_CALLBACK_URL  = 'callback_url';
    const KEY_MESSAGE_ID    = 'message_id';
    const KEY_TRACK         = 'track';

    const STATUS_ENABLED    = 'enabled';
    const STATUS_DISABLED   = 'disabled';

    /**
     * insert task
     *
     * @param             $taskId
     * @param             $messageList
     * @param             $status
     * @param string|null $callbackUrl
     *
     * @param bool        $track
     *
     * @throws DBException
     */
    public function save($taskId, $messageList, $status, $callbackUrl = null, $track = true)
    {
        $data = [
            self::KEY_TASK_ID       => $taskId,
            self::KEY_CREATE_TIME   => time(),
            self::KEY_STATUS        => $status,
            self::KEY_TRACK         => $track
        ];

        if (!is_null($callbackUrl)) {
            $data[self::KEY_CALLBACK_URL] = $callbackUrl;
        }

        $query = QueryFactory::create($this->table)
            ->setInsertData($data);

        $result = $this->insert($query);

        if (!$result) {
            throw new DBException(
                sprintf(DBException::MESSAGE_QTM_TASK_SAVE_ERROR, print_r($data, true))
            );
        }

        if (!empty($messageList)) {
            $this->saveSubtasks($taskId, $messageList);
        }
    }

    /**
     * Inserts subtasks in batch for given taskId
     *
     * @param string $taskId
     * @param array  $messageIdList
     *
     * @throws DBException
     */
    private function saveSubtasks($taskId, $messageIdList)
    {
        $insertData = [];
        foreach ($messageIdList as $messageId) {
            $subtaskData = [
                self::KEY_TASK_ID    => $taskId,
                self::KEY_MESSAGE_ID => $messageId
            ];
            $insertData[] = $subtaskData;
        }
        $query = QueryFactory::create($this->subtaskTable)
                             ->setInsertData($insertData);

        $result = $this->insertBulk($query);
        if (!$result) {
            $dbError = $this->getLastErrorMessage();
            throw new DBException(
                "Error inserting subtasks (task:$taskId) : " . $dbError
            );
        }
    }

    /**
     * Delete a given task and all of its subtasks
     *
     * @param string $taskId
     *
     * @throws DBException
     */
    public function deleteTask($taskId)
    {
        Logger::logDebug(
            'Deleting task',
            [Logger::TASK_ID_KEY => $taskId]
        );

        $query = QueryFactory::create($this->subtaskTable)
                             ->addCondition('task_id', $taskId);

        if (!$this->remove($query)) {
            $dbError = $this->getLastErrorMessage();
            throw new DBException(
                "Error deleting subtasks (task:$taskId) : " . $dbError
            );
        }

        $query = QueryFactory::create($this->table)
                             ->addCondition('task_id', $taskId);

        if (!$this->remove($query)) {
            $dbError = $this->getLastErrorMessage();
            throw new DBException(
                "Error deleting task (task:$taskId) : " . $dbError
            );
        }
    }

    /**
     * Add subtask to a given task
     *
     * @param string $taskId
     * @param int    $messageId
     *
     * @throws DBException
     */
    public function addSubTask($taskId, $messageId)
    {
        $subtaskData = [
            self::KEY_TASK_ID => $taskId,
            self::KEY_MESSAGE_ID => $messageId
        ];

        Logger::logDebug(
            'Adding subtask',
            [Logger::TASK_ID_KEY => $subtaskData]
        );

        $query = QueryFactory::create($this->subtaskTable)
            ->setInsertData($subtaskData);

        $result = $this->insert($query);

        if (!$result) {
            $dbError = $this->getLastErrorMessage();
            throw new DBException(
                "Error adding sub task (task:$taskId) : " . $dbError
            );
        }

    }

    /**
     * Removes a subtask
     *
     * @param string $taskId
     * @param int    $messageId
     *
     * @throws DBException
     */
    public function removeSubTask($taskId, $messageId)
    {
        Logger::logDebug(
            'Removing subtask',
            [Logger::TASK_ID_KEY => $taskId, 'message_id' => $messageId]
        );

        $query = QueryFactory::create($this->subtaskTable)
            ->addCondition(self::KEY_TASK_ID, $taskId)
            ->addCondition(self::KEY_MESSAGE_ID, $messageId);

        $result = $this->remove($query);

        if (!$result) {
            $dbError = $this->getLastErrorMessage();
            throw new DBException(
                "Error removing sub task (task:$taskId) : " . $dbError
            );
        }
    }

    /**
     * update task status
     *
     * @param $taskId
     * @param $status
     *
     * @throws DBException
     */
    public function updateStatus($taskId, $status)
    {
        Logger::logDebug(
            'Updating task status',
            [Logger::TASK_ID_KEY => $taskId, 'status' => $status]
        );

        $query = QueryFactory::create($this->table)
            ->addCondition(self::KEY_TASK_ID, $taskId)
            ->setUpdateData([self::KEY_STATUS => $status]);

        $result = $this->update($query);

        if (!$result) {
            $dbError = $this->getLastErrorMessage();
            throw new DBException(
                "Error updating task status (task:$taskId) : " . $dbError
            );
        }
    }

    /**
     * Checks if a task is complete.
     * A task is completed when it has no subtasks left and task status is active
     *
     * @param $task
     *
     * @return bool
     */
    public function isTaskComplete($task)
    {
        if (!is_array($task)) {
            $task = $this->findTaskById($task);
        }

        return !$this->hasSubtasks($task[self::KEY_TASK_ID])
            && $task[self::KEY_STATUS] === self::STATUS_ENABLED;
    }

    /**
     * Checks if a given task has subtasks
     *
     * @param string $taskId
     *
     * @return bool
     */
    private function hasSubtasks($taskId)
    {
        $hasSubtasks = false;

        $query = QueryFactory::create($this->subtaskTable)
            ->addSelectField(
                self::KEY_TASK_ID,
                'subtask_count',
                self::TYPE_PRIM,
                [new FieldFunction('count')]
            )
            ->addCondition(self::KEY_TASK_ID, $taskId);

        $result = $this->findOne($query);

        if (!empty($result) && isset($result['subtask_count']) && intval($result['subtask_count']) > 0) {
            $hasSubtasks = true;
        }

        return $hasSubtasks;
    }

    /**
     * find task by id
     *
     * @param $taskId
     * @return array
     */
    public function findTaskById($taskId)
    {
        $query = QueryFactory::create($this->table)
            ->addCondition(self::KEY_TASK_ID, $taskId);
        $result = $this->findOne($query);
        return $result;
    }

    /**
     * get task status
     *
     * @param $taskId
     * @return string|null
     */
    public function getTaskStatus($taskId)
    {
        $task = $this->findTaskById($taskId);
        return ($task) ? $task[self::KEY_STATUS] : null;
    }
}
