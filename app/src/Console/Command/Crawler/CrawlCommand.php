<?php

namespace Console\Command\Crawler;

use Console\Command;
use Services\Crawler\Messages\CrawlerMessage;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Services\Factory;
use Library\Logger\Logger;

class CrawlCommand extends Command\AbstractCommand implements Command\ConsumerInterface
{
    const CLI_NAME               = 'crawler:crawl';
    const CLI_DESCRIPTION        = 'Crawl the urls in the queue';
    const MAX_SPAWN_AMOUNT_WORK  = 10;
    const RECEIVE_WAIT_TIME      = 1;

    /**
     * @var \Services\Crawler\Consumers\CrawlerConsumer $consumer
     */
    protected $consumer;


    /**
     * Set the name and description app/console will return and bind to in the CLI
     */
    protected function configure()
    {
        $this->setName(self::CLI_NAME)->setDescription(self::CLI_DESCRIPTION);
        $this->addArgument('url', InputArgument::REQUIRED);
        $this->addArgument('messageId', InputArgument::OPTIONAL);
        $this->addArgument('taskId', InputArgument::OPTIONAL);
        $this->addArgument('messageHandle', InputArgument::OPTIONAL);

        $this->consumer = Factory::create('crawlerConsumer');
    }

    /**
     * This method calls the CrawlConsumer with the parameters from the messageQueue
     * It is called when run from the CLI (app/console).
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $url      = $input->getArgument('url');
        $messageId      = $input->getArgument('messageId');
        // we are passing null optional arguments as empty strings because of argument positioning,
        // so we need to switch that back to using null instead
        $taskId         = $input->getArgument('taskId') ? $input->getArgument('taskId') : null;
        $messageHandle  = $input->getArgument('messageHandle') ? $input->getArgument('messageHandle') : null;


        $message = new CrawlerMessage($url, $messageId, $taskId, $messageHandle);

        $crawlStartTime = microtime(true);
        Logger::logInfo('** Cli :crawler - starting', [
            Logger::PROCESS_KEY => self::CLI_PROCESS,
            Logger::ACTION_KEY => self::CLI_NAME,
            Logger::URL_KEY => $url
        ]);
        $this->consumer->run($message);
        $crawlEndTime = microtime(true);
        Logger::logInfo(
            '** Cli :crawler - finished',
            [
                Logger::PROCESS_KEY => self::CLI_PROCESS,
                Logger::ACTION_KEY => self::CLI_NAME,
                Logger::URL_KEY => $url,
                Logger::EXECUTION_TIME_KEY => ($crawlEndTime - $crawlStartTime)
            ]
        );
    }

    /**
     * Fetch available work
     *
     * @param void
     * @return mixed $workUnits
     */
    public function fetchWork()
    {
        $workUnits = $this->consumer->fetchWork(
            self::MAX_SPAWN_AMOUNT_WORK,
            self::RECEIVE_WAIT_TIME
        );

        return $workUnits;
    }
}
