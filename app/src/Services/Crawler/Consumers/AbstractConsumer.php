<?php

namespace Services\Crawler\Consumers;

use Library\Logger\Logger;
use Services\QTM\DAO\TaskDAO;
use Services\QTM\QTM;

abstract class AbstractConsumer
{
    /**
     * @var QTM
     */
    protected $qtm;

    /**
     * @param QTM $qtm
     */
    public function __construct($qtm)
    {
        $this->qtm = $qtm;
    }

    /**
     * check if a task exists and is enabled
     *
     * @param $taskId
     * @return bool
     */
    public function hasValidTask($taskId)
    {
        if ($taskId) {
            $status = $this->qtm->getTaskStatus($taskId);
            //status null means the task is not found
            if (is_null($status)) {
                Logger::logError('Found message with invalid task id: '.$taskId);
                return false;
            } elseif ($status != TaskDAO::STATUS_ENABLED) {
                Logger::logInfo('Skipping message for disabled task id: '.$taskId);
                return false;
            }
        }
        return true;
    }
}
