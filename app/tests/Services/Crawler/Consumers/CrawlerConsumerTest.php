<?php

use Services\Crawler\Consumers\CrawlerConsumer;

class CrawlerConsumerTest extends \PHPUnit_Framework_TestCase
{
    public function testRunFromCli()
    {
        $message         = current($this->getMockCrawlerMessages(1));
        $crawlerCA       = $this->getMockCrawlerConsumerAction(['crawl']);
        $queueService    = $this->getMockQueueService(['delete','getQueue']);
        $crawlerConsumer = new CrawlerConsumer($queueService, $crawlerCA);

        $crawlerCA->expects($this->once())
            ->method('crawl')
            ->with(['url' => $message->url, 'taskId' => $message->taskId]);

        $queueService->expects($this->never())
            ->method('getQueue')
            ->willReturn($this->getMockQueueProvider([]));

        $queueService->expects($this->never())
            ->method('delete')
            ->with($message->messageId);

        $crawlerConsumer->run($message);
    }

    public function testRunFromQueue()
    {
        $message         = current($this->getMockCrawlerMessages(1, true));
        $crawlerCA       = $this->getMockCrawlerConsumerAction(['pushUrl']);
        $queueService    = $this->getMockQueueService(['delete','getQueue']);
        $crawlerConsumer = new CrawlerConsumer($queueService, $crawlerCA);

        $crawlerCA->expects($this->never())
            ->method('crawl')
            ->with(['url' => $message->url, 'taskId' => $message->taskId]);

        $queueService->expects($this->once())
            ->method('getQueue')
            ->willReturn($this->getMockQueueProvider([]));


        $queueService->expects($this->never())
            ->method('delete')
            ->with($message->messageId);

        $crawlerConsumer->run($message);
    }

    /**
     * Test succesfully returning work from the message queue
     */
    public function testfetchWorkFromQueue()
    {
        $message_num    = 5;
        $limit          = 5;
        $wait_time      = 5;
        $messages       = $this->getMockCrawlerMessages($message_num, true);
        $queueResponses = $this->getMockQueueResponses($messages);
        $crawlerCA      = $this->getMockCrawlerConsumerAction([]);
        $queueService   = $this->getMockQueueService(['receive','getQueue','getTaskStatus']);

        $queueService->expects($this->once())
            ->method('receive')
            ->with('url',[
                'messages_to_receive' => $limit,
                'receive_wait_time'   => $wait_time
            ])
            ->willReturn($queueResponses);

        $queueService->expects($this->never())
            ->method('getQueue')
            ->willReturn($this->getMockQueueProvider([]));

        $queueService->expects($this->exactly(5))
            ->method('getTaskStatus')
            ->willReturn(true);

        $crawlerConsumer = new CrawlerConsumer($queueService, $crawlerCA);

        $workUnits = $crawlerConsumer->fetchWork($limit, $wait_time);
        $this->assertInternalType('array', $workUnits);
        $this->assertEquals($message_num, count($workUnits));
        $this->assertContainsOnlyInstancesOf('Services\Crawler\Messages\CrawlerMessage', $workUnits);

        $workUnit = $workUnits[0];
        $this->assertEquals($messages[0]->url, $workUnit->url);
    }

    /**
     * Test fetching work from an empty queue
     */
    public function testFetchWorkFromEmptyQueue()
    {
        $message_num    = 0;
        $limit          = 5;
        $wait_time      = 5;
        $messages       = $this->getMockCrawlerMessages($message_num, true);
        $queueResponses = $this->getMockQueueResponses($messages);
        $crawlerCA      = $this->getMockCrawlerConsumerAction([]);
        $queueService   = $this->getMockQueueService(['receive','getQueue']);

        $queueService->expects($this->once())
            ->method('receive')
            ->with('url',[
                'messages_to_receive' => $limit,
                'receive_wait_time'   => $wait_time
            ])
            ->will($this->returnValue($queueResponses));

        $queueService->expects($this->never())
            ->method('getQueue')
            ->willReturn($this->getMockQueueProvider([]));

        $crawlerConsumer = new CrawlerConsumer($queueService, $crawlerCA);

        $workUnits = $crawlerConsumer->fetchWork($limit, $wait_time);
        $this->assertInternalType('array', $workUnits);
        $this->assertEquals($message_num, count($workUnits));
    }

    /**
     * Mock the queueService
     *
     * @param array $methods
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\Uecode\Bundle\QPushBundle\Provider\CustomProvider
     */
    private function getMockQueueService($methods)
    {
        $mockTaskDAO = $this->getMockTaskDAO([]);
        $mockCustomProvider = $this->getMockQueueProvider([]);
        return $this->getMockBuilder('Services\QTM\QTM')
            ->setConstructorArgs([$mockTaskDAO,$mockCustomProvider])
            ->setMethods($methods)
            ->getMock();
    }

    private function getMockQueueProvider($methods)
    {
        return $this->getMockBuilder('Uecode\Bundle\QPushBundle\Provider\CustomProvider')
            ->disableOriginalConstructor()
            ->setMethods($methods)
            ->getMock();
    }

    private function getMockTaskDAO($methods)
    {
        return $this->getMockBuilder('Services\QTM\DAO\TaskDAO')
            ->disableOriginalConstructor()
            ->setMethods($methods)
            ->getMock();
    }

    /**
     * Mock the crawler consumer action
     *
     * @param array $methods
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\Services\Crawler\Crawler
     */
    private function getMockCrawlerConsumerAction($methods)
    {
        $crawlerService = $this->getMockCrawlerService(['startCrawler']);

        return $this->getMockBuilder('Console\Command\Crawler\Actions\CrawlerConsumerAction')
            ->setConstructorArgs([$crawlerService])
            ->setMethods($methods)
            ->getMock();
    }

    private function getMockCrawlerService($methods)
    {
        return $this->getMockBuilder('Services\Crawler\Crawler')
            ->disableOriginalConstructor()
            ->setMethods($methods)
            ->getMock();
    }

    /**
     * Mock a crawlerMessage
     *
     * @param int  $num
     * @param bool $containsMessageId
     *
     * @return \Services\Crawler\Messages\CrawlerMessage[]
     */
    private function getMockCrawlerMessages($num=1, $containsMessageId=false)
    {
        $messages = [];
        for ($i=0; $i<$num; $i++) {
            $url = 'www.aaaa.com';
            $messageId = $containsMessageId ? rand(1, 100) : null;
            $taskId = rand(1,300);

            $messages[] = new \Services\Crawler\Messages\CrawlerMessage(
                $url,
                $messageId,
                $taskId
            );
        }

        return $messages;
    }

    /**
     * Mock queue responses (based on crawlerMessages)
     *
     * @param $messages
     *
     * @return \Uecode\Bundle\QPushBundle\Message\Message[]
     */
    private function getMockQueueResponses($messages)
    {
        $responses = [];
        foreach($messages as $message) {
            $body              = [];
            $body['message']['url'] = $message->url;
            $body['message']['task_id'] = $message->taskId;
            $body['id'] = $message->taskId;
            $body['handle'] = '';


            $responses[] = $body;
        }

        return $responses;
    }
}
