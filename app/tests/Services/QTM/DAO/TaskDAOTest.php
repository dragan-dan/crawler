<?php

namespace Services\QTM\DAO\Test;       
use Mockery\MockInterface;
use Services\QTM\DAO\TaskDAO;

/**
 * Class TaskDAOTest
 * @package Services\QTM\DAO
 */
class TaskDAOTest extends \PHPUnit_Framework_TestCase
{
    public function testSaveUnsuccessful()
    {
        $this->setExpectedException('Exceptions\DBException');

        $testTaskId  = 'qwerty';
        $messageList = [];
        $status      = 'active';

        /** @var MockInterface|TaskDAO $taskDAO */
        $taskDAO = \Mockery::mock('Services\QTM\DAO\TaskDAO')->makePartial();

        $taskDAO->shouldReceive('insert')
                ->once()
                ->andReturn(false);
        $taskDAO->shouldReceive('insertBulk')
                ->never();

        $taskDAO->save($testTaskId, $messageList, $status);
    }

    public function testSave()
    {
        $testTaskId  = 'qwerty';
        $messageList = [1,2];
        $status      = 'active';

        /** @var MockInterface|TaskDAO $taskDAO */
        $taskDAO = \Mockery::mock('Services\QTM\DAO\TaskDAO')->makePartial();

        $taskDAO->shouldReceive('insert')
                ->once()
                ->andReturn(true);
        $taskDAO->shouldReceive('insertBulk')
                ->once()
                ->andReturn(true);

        $taskDAO->save($testTaskId, $messageList, $status);
    }

    public function testDeleteTaskUnsuccessful()
    {
        $this->setExpectedException('Exceptions\DBException');

        $testTaskId  = 'qwerty';

        /** @var MockInterface|TaskDAO $taskDAO */
        $taskDAO = \Mockery::mock('Services\QTM\DAO\TaskDAO')->makePartial();

        $taskDAO->shouldReceive('remove')
                ->once()
                ->andReturn(false);

        $taskDAO->deleteTask($testTaskId);

    }

    public function testDeleteTaskSubtaskUnsuccessful()
    {
        $this->setExpectedException('Exceptions\DBException');

        $testTaskId  = 'qwerty';

        /** @var MockInterface|TaskDAO $taskDAO */
        $taskDAO = \Mockery::mock('Services\QTM\DAO\TaskDAO')->makePartial();

        $taskDAO->shouldReceive('remove')
                ->twice()
                ->andReturn(true, false);

        $taskDAO->deleteTask($testTaskId);
    }

    public function testDeleteTask()
    {
        $testTaskId  = 'qwerty';

        /** @var MockInterface|TaskDAO $taskDAO */
        $taskDAO = \Mockery::mock('Services\QTM\DAO\TaskDAO')->makePartial();

        $taskDAO->shouldReceive('remove')
                ->twice()
                ->andReturn(true, true);

        $taskDAO->deleteTask($testTaskId);
    }

    public function testAddTaskUnsuccessful()
    {
        $this->setExpectedException('Exceptions\DBException');

        $testTaskId = 'qwerty';
        $messageId  = 1;

        /** @var MockInterface|TaskDAO $taskDAO */
        $taskDAO = \Mockery::mock('Services\QTM\DAO\TaskDAO')->makePartial();

        $taskDAO->shouldReceive('insert')
                ->once()
                ->andReturn(false);

        $taskDAO->addSubTask($testTaskId, $messageId);
    }

    public function testAddTask()
    {
        $testTaskId = 'qwerty';
        $messageId  = 1;

        /** @var MockInterface|TaskDAO $taskDAO */
        $taskDAO = \Mockery::mock('Services\QTM\DAO\TaskDAO')->makePartial();

        $taskDAO->shouldReceive('insert')
                ->once()
                ->andReturn(true);

        $result = $taskDAO->addSubTask($testTaskId, $messageId);
    }

    public function testRemoveSubTaskUnsuccessful()
    {
        $this->setExpectedException('Exceptions\DBException');

        $testTaskId = 'qwerty';
        $messageId  = 1;

        /** @var MockInterface|TaskDAO $taskDAO */
        $taskDAO = \Mockery::mock('Services\QTM\DAO\TaskDAO')->makePartial();

        $taskDAO->shouldReceive('remove')
                ->once()
                ->andReturn(false);

        $taskDAO->removeSubTask($testTaskId, $messageId);
    }

    public function testRemoveSubTask()
    {
        $testTaskId = 'qwerty';
        $messageId  = 1;

        /** @var MockInterface|TaskDAO $taskDAO */
        $taskDAO = \Mockery::mock('Services\QTM\DAO\TaskDAO')->makePartial();

        $taskDAO->shouldReceive('remove')
                ->once()
                ->andReturn(true);

        $taskDAO->removeSubTask($testTaskId, $messageId);
    }

    public function testUpdateStatusUnsuccessful()
    {
        $this->setExpectedException('Exceptions\DBException');

        $testTaskId = 'qwerty';
        $testStatus = 'active';

        /** @var MockInterface|TaskDAO $taskDAO */
        $taskDAO = \Mockery::mock('Services\QTM\DAO\TaskDAO')->makePartial();

        $taskDAO->shouldReceive('update')
                ->once()
                ->andReturn(false);

        $taskDAO->updateStatus($testTaskId, $testStatus);
    }

    public function testUpdateStatus()
    {
        $testTaskId = 'qwerty';
        $testStatus = 'active';

        /** @var MockInterface|TaskDAO $taskDAO */
        $taskDAO = \Mockery::mock('Services\QTM\DAO\TaskDAO')->makePartial();

        $taskDAO->shouldReceive('update')
                ->once()
                ->andReturn(true);

        $taskDAO->updateStatus($testTaskId, $testStatus);
    }

    public function testIsTaskCompleteWithTaskDataNotCompleted()
    {
        $testTask = ['task_id' => 'qwerty' ,'status' => 'active'];
        $testSubtaskCountResult = ['subtask_count' => 3];

        /** @var MockInterface|TaskDAO $taskDAO */
        $taskDAO = \Mockery::mock('Services\QTM\DAO\TaskDAO')->makePartial();

        $taskDAO->shouldReceive('findTaskById')
                ->never();
        $taskDAO->shouldReceive('findOne')
                ->once()
                ->andReturn($testSubtaskCountResult);

        $result = $taskDAO->isTaskComplete($testTask);

        $this->assertFalse($result);
    }

    public function testIsTaskCompleteWithTaskIdNotCompleted()
    {
        $testTaskId = 'qwerty';
        $testTaskResponse = ['task_id' => 'qwerty' ,'status' => 'active'];
        $testSubtaskCountResult = ['subtask_count' => 3];

        /** @var MockInterface|TaskDAO $taskDAO */
        $taskDAO = \Mockery::mock('Services\QTM\DAO\TaskDAO')->makePartial();

        $taskDAO->shouldReceive('findTaskById')
                ->once()
                ->andReturn($testTaskResponse);
        $taskDAO->shouldReceive('findOne')
                ->once()
                ->andReturn($testSubtaskCountResult);

        $result = $taskDAO->isTaskComplete($testTaskId);

        $this->assertFalse($result);
    }

    public function testIsTaskCompleteWithTaskData()
    {
        $testTask = ['task_id' => 'qwerty' ,'status' => 'enabled'];
        $testSubtaskCountResult = ['subtask_count' => 0];

        /** @var MockInterface|TaskDAO $taskDAO */
        $taskDAO = \Mockery::mock('Services\QTM\DAO\TaskDAO')->makePartial();

        $taskDAO->shouldReceive('findTaskById')
                ->never();
        $taskDAO->shouldReceive('findOne')
                ->once()
                ->andReturn($testSubtaskCountResult);

        $result = $taskDAO->isTaskComplete($testTask);

        $this->assertTrue($result);
    }

    public function testIsTaskCompleteWithTaskId()
    {
        $testTaskId = 'qwerty';
        $testTaskResponse = ['task_id' => 'qwerty' ,'status' => 'enabled'];
        $testSubtaskCountResult = ['subtask_count' => 0];

        /** @var MockInterface|TaskDAO $taskDAO */
        $taskDAO = \Mockery::mock('Services\QTM\DAO\TaskDAO')->makePartial();

        $taskDAO->shouldReceive('findTaskById')
                ->once()
                ->andReturn($testTaskResponse);
        $taskDAO->shouldReceive('findOne')
                ->once()
                ->andReturn($testSubtaskCountResult);

        $result = $taskDAO->isTaskComplete($testTaskId);

        $this->assertTrue($result);
    }

    protected function tearDown()
    {
        \Mockery::close();
    }


}
