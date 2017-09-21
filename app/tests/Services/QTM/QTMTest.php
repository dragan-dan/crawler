<?php

namespace Services\QTM\Test;

use Services\QTM\QTM;
use Uecode\Bundle\QPushBundle\Message\Message;
use Mockery;

/**
 * Class QTMTest
 * @package Services\QTM\Test
 */
class QTMTest extends \PHPUnit_Framework_TestCase
{
    private $testQueueName = 'url';

    public function testPublishWithoutTask()
    {
        $testMessageId = 1;
        $testMessage = [
            'message_id' => $testMessageId
        ];

        $taskDAO = Mockery::mock('Services\QTM\DAO\TaskDAO');
        $taskDAO->shouldReceive('save')->once();
        $taskDAO->shouldReceive('addSubTask')->once();

        $queueProvider = $this->getQueueProvider(
            [
                ['method' => 'publish', 'times' => 1, 'return' => $testMessageId]
            ]
        );

        $qtm = $this->getQTM($taskDAO, $queueProvider);

        list($messageId, $taskId) = $qtm->publish($this->testQueueName, $testMessage, null, null);

        $this->assertNotNull($taskId, 'task_id is null');
        $this->assertEquals($testMessageId, $messageId, 'message_id is not equal to 1');
    }

    public function testPublishWithTask()
    {
        $testMessageId = 1;
        $testTaskId = 123;
        $testMessage = [
            'message_id' => $testMessageId,
            'task_id' => $testTaskId
        ];

        $taskDAO = Mockery::mock('Services\QTM\DAO\TaskDAO');
        $taskDAO->shouldReceive('addSubTask')->once();

        $queueProvider = $this->getQueueProvider(
            [
                ['method' => 'publish', 'times' => 1, 'return' => $testMessageId]
            ]
        );
        $qtm = $this->getQTM($taskDAO, $queueProvider);

        list($messageId, $taskId) = $qtm->publish($this->testQueueName, $testMessage, null, $testTaskId);

        $this->assertEquals(123, $taskId, 'task_id is not equal to sent task id');
        $this->assertEquals($testMessageId, $messageId, 'message_id does not match');
    }

    public function testFinishWithNoTask()
    {
        $testMessageId = 1;

        $testMessage = [
            'message_id' => $testMessageId,
            'task_id'    => null
        ];

        $queueProvider = $this->getQueueProvider(
            [
                ['method' => 'delete', 'times' => 1, 'return' => true]
            ]
        );
        $qtm = $this->getQTM(null, $queueProvider);

        $qtm->finish($this->testQueueName, $testMessage);

    }

    public function testFinishWithIncompleteTask()
    {
        $testMessageId = 1;
        $testTaskId    = 10;

        $testMessage = [
            'message_id' => $testMessageId,
            'task_id'    => $testTaskId
        ];

        $testTask = ['test_task'];

        $taskDAO = Mockery::mock('Services\QTM\DAO\TaskDAO');
        $taskDAO->shouldReceive('removeSubTask')->once();
        $taskDAO->shouldReceive('findTaskById')
                ->once()
                ->andReturn($testTask);
        $taskDAO->shouldReceive('isTaskComplete')
                ->once()
                ->with($testTask)
                ->andReturn(false);
        $taskDAO->shouldReceive('deleteTask')->never();

        $queueProvider = $this->getQueueProvider(
            [
                ['method' => 'delete', 'times' => 1, 'return' => true]
            ]
        );
        $qtm = $this->getQTM($taskDAO, $queueProvider);

        $qtm->finish($this->testQueueName, $testMessage);
    }

    public function testFinishWithCompletedTask()
    {
        $testMessageId = 1;
        $testTaskId    = 10;

        $testMessage = [
            'message_id' => $testMessageId,
            'task_id'    => $testTaskId
        ];

        $testTask = ['create_time' => '1459349032', 'task_id' => $testTaskId];

        $taskDAO = Mockery::mock('Services\QTM\DAO\TaskDAO');
        $taskDAO->shouldReceive('removeSubTask')->once();
        $taskDAO->shouldReceive('findTaskById')
                ->once()
                ->andReturn($testTask);
        $taskDAO->shouldReceive('isTaskComplete')
                ->once()
                ->with($testTask)
                ->andReturn(true);
        $taskDAO->shouldReceive('deleteTask')->once();

        $queueProvider = $this->getQueueProvider(
            [
                ['method' => 'delete', 'times' => 1, 'return' => true]
            ]
        );
        // Partial mock
        $qtm = Mockery::mock('Services\QTM\QTM[call]', [$taskDAO, $queueProvider, null])
            ->shouldAllowMockingProtectedMethods();

        // No callbackURL provided, so call should not be called
        $qtm->shouldReceive('call')->never();

        $qtm->finish($this->testQueueName, $testMessage);
    }

    public function testFinishWithCompletedTaskWithCallbackUrl()
    {
        $testMessageId = 1;
        $testTaskId    = 10;

        $testMessage = [
            'message_id' => $testMessageId,
            'task_id'    => $testTaskId
        ];

        $testTask = ['callback_url' => 'some_url', 'create_time' => '1459349032', 'task_id' => $testTaskId];

        $taskDAO = Mockery::mock('Services\QTM\DAO\TaskDAO');
        $taskDAO->shouldReceive('removeSubTask')->once();
        $taskDAO->shouldReceive('findTaskById')
                ->once()
                ->andReturn($testTask);
        $taskDAO->shouldReceive('isTaskComplete')
                ->once()
                ->with($testTask)
                ->andReturn(true);
        $taskDAO->shouldReceive('deleteTask')->once();

        $queueProvider = $this->getQueueProvider(
            [
                ['method' => 'delete', 'times' => 1, 'return' => true]
            ]
        );
        // Partial mock
        $qtm = Mockery::mock('Services\QTM\QTM[call]', [$taskDAO, $queueProvider, null])
                       ->shouldAllowMockingProtectedMethods();

        $qtm->shouldReceive('call')->once();

        $qtm->finish($this->testQueueName, $testMessage);
    }

    public function testFinishTrackExecutionTime()
    {
        $testMessageId = 1;
        $testTaskId    = 10;

        $testMessage = [
            'message_id' => $testMessageId,
            'task_id'    => $testTaskId
        ];

        $testTask = [
            'callback_url' => 'some_url',
            'create_time' => '1459349032',
            'task_id' => $testTaskId,
            'track' => true
        ];

        $taskDAO = Mockery::mock('Services\QTM\DAO\TaskDAO');
        $taskDAO->shouldReceive('removeSubTask')->once();
        $taskDAO->shouldReceive('findTaskById')
                ->once()
                ->andReturn($testTask);
        $taskDAO->shouldReceive('isTaskComplete')
                ->once()
                ->with($testTask)
                ->andReturn(true);
        $taskDAO->shouldReceive('deleteTask')->once();

        $queueProvider = $this->getQueueProvider(
            [
                ['method' => 'delete', 'times' => 1, 'return' => true]
            ]
        );
        // Partial mock
        $qtm = Mockery::mock('Services\QTM\QTM[call,logExecutionTime]', [$taskDAO, $queueProvider, null])
                       ->shouldAllowMockingProtectedMethods();

        $qtm->shouldReceive('call')->once();
        $qtm->shouldReceive('logExecutionTime')->once();

        $qtm->finish($this->testQueueName, $testMessage);
    }

    public function testFinishNoTrackExecutionTime()
    {
        $testMessageId = 1;
        $testTaskId    = 10;

        $testMessage = [
            'message_id' => $testMessageId,
            'task_id'    => $testTaskId
        ];

        $testTask = [
            'callback_url' => 'some_url',
            'create_time' => '1459349032',
            'task_id' => $testTaskId,
            'track' => false
        ];

        $taskDAO = Mockery::mock('Services\QTM\DAO\TaskDAO');
        $taskDAO->shouldReceive('removeSubTask')->once();
        $taskDAO->shouldReceive('findTaskById')
                ->once()
                ->andReturn($testTask);
        $taskDAO->shouldReceive('isTaskComplete')
                ->once()
                ->with($testTask)
                ->andReturn(true);
        $taskDAO->shouldReceive('deleteTask')->once();

        $queueProvider = $this->getQueueProvider(
            [
                ['method' => 'delete', 'times' => 1, 'return' => true]
            ]
        );
        // Partial mock
        $qtm = Mockery::mock('Services\QTM\QTM[call,logExecutionTime]', [$taskDAO, $queueProvider, null])
                       ->shouldAllowMockingProtectedMethods();

        $qtm->shouldReceive('call')->once();
        $qtm->shouldReceive('logExecutionTime')->never();

        $qtm->finish($this->testQueueName, $testMessage);
    }

    public function testReceive()
    {
        $testMessageId     = 1;
        $testMessage       = ['test_body'];
        $testMessageHandle = 'test_message_handle';
        $testMetaData      = ['ReceiptHandle' => $testMessageHandle];
        $expectedReturn    = [
            [
                'id'      => $testMessageId,
                'message' => $testMessage,
                'handle'  => $testMessageHandle
            ]
        ];

        $workArray     = [new Message($testMessageId, $testMessage, $testMetaData)];
        $queueProvider = $this->getQueueProvider(
            [
                ['method' => 'receive', 'times' => 1, 'return' => $workArray]
            ]
        );

        $qtm = $this->getQTM(null, $queueProvider);
        $result = $qtm->receive($this->testQueueName, []);

        $this->assertEquals($expectedReturn, $result);
    }

    public function testGetTaskStatus()
    {
        $taskId = 1;

        $taskDAO = Mockery::mock('Services\QTM\DAO\TaskDAO');
        $taskDAO->shouldReceive('getTaskStatus')->once();

        $qtm = $this->getQTM($taskDAO, null);

        $qtm->getTaskStatus($taskId);
    }

    public function testGenerateEmptyTask()
    {
        $taskDAO = Mockery::mock('Services\QTM\DAO\TaskDAO');
        $taskDAO->shouldReceive('save')->once();

        $qtm = $this->getQTM($taskDAO, null);

        $taskId = $qtm->generateEmptyTask();

        $this->assertNotNull($taskId);
    }

    public function testUpdateTaskStatus()
    {
        $taskId = 1;
        $testStatus = 'test_status';

        $taskDAO = Mockery::mock('Services\QTM\DAO\TaskDAO');
        $taskDAO->shouldReceive('updateStatus')->once();

        $qtm = $this->getQTM($taskDAO, null);

        $qtm->updateTaskStatus($taskId, $testStatus);
    }

    private function getQTM($taskDAO = null, $queueProvider = null)
    {
        if (is_null($queueProvider)) {
            $queueProvider = $this->getQueueProvider([]);
        }

        if (is_null($taskDAO)) {
            $taskDAO = Mockery::mock('Services\QTM\DAO\TaskDAO');
        }

        $qtm = new QTM($taskDAO, $queueProvider, null);

        return $qtm;
    }

    private function getQueueProvider($mockMethods)
    {
        // Use a test provider for mocking the queue
        $queueProvider = function ($queueName) use ($mockMethods) {

            $mockQueueProvider = Mockery::mock('Uecode\Bundle\QPushBundle\Provider\AbstractProvider');

            foreach ($mockMethods as $mockMethod) {
                $mockQueueProvider
                    ->shouldReceive($mockMethod['method'])
                    ->times($mockMethod['times'])
                    ->andReturn($mockMethod['return']);
            }

            return $mockQueueProvider;
        };

        return $queueProvider;
    }

    protected function tearDown()
    {
        Mockery::close();
    }
}
