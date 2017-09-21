<?php

namespace Console\Command\Daemon;

use Exceptions;
use Console;
use Services\Crawler\Messages\AbstractMessage;
use Symfony\Component\Console\Command;
use Symfony\Component\Console\Input;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Application;
use Library\Logger\Logger;
use Library\DAO\Postgre\PostgreConnectionFactory;

/**
 * Class WorkerCommand
 * @package Console\Command\Daemon
 */
class WorkerCommand extends Command\Command
{
    /**
     *
     */
    const CLI_NAME               = 'worker';
    const CLI_DESCRIPTION        = 'Worker thread for crawler';
    const CLI_WORK_TIMEOUT       = 30;
    const CLI_WORK_SLEEP_TIME    = 5;

    const CONSUMER_CLASS_PREFIX  = 'Console\\Command';
    const CONSUMER_CLASS_SUFFIX  = 'Command';

    /**
     * @var \Silex\Application CLI Application
     */
    protected $silexApp;

    /**
     * @var bool Flag to terminate the process on next iteration
     */
    protected $gracefulShutdown = false;

    /**
     * @param \Silex\Application $silexApp
     * @param string $name
     */
    public function __construct(\Silex\Application $silexApp, $name = null) {
        parent::__construct($name);
        $this->silexApp = $silexApp;
    }

    /**
     * Set the name and description app/console will return and bind to in the CLI
     */
    protected function configure()
    {
        $this->setName(self::CLI_NAME)->setDescription(self::CLI_DESCRIPTION);
        $this->addArgument('jobs', InputArgument::IS_ARRAY, 'Jobs to run periodically');
        
        declare(ticks = 1);
        pcntl_signal(SIGTERM, [$this, 'handleShutdownSignal']);
    }

    /**
     * This method calls the API endpoint that handles crawling
     * It is called when run from the CLI (app/console).
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @throws Exceptions\CommandException
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $jobs = $input->getArgument('jobs');

        if (count($jobs) == 0) {
            throw new Exceptions\CommandException('No jobs specified');
        }

        // Initialize worker commands
        $consumers = $this->getConsumers($jobs);

        if (count($consumers) == 0) {
            throw new Exceptions\CommandException('No jobs to run!');
        }

        // Loop until we're stopped
        while (true) {

            Logger::logInfo('***** Cli daemon:worker wake up *****');

            // Execute jobs sequentially
            foreach ($consumers as $consumer) {
                // Check for shutdown signal on each consumer iteration
                if ($this->gracefulShutdown) {
                    Logger::logInfo('***** Cli daemon:worker caught termination signal. Shutting down [consumer-iteration] *****');
                    break 2;
                }
                // Check for work
                $workUnits = $this->fetchWork($consumer);

                // Execute job
                if (!is_null($workUnits)) {
                    $this->processWorkUnits($consumer, $workUnits, $output);
                    try {
                        $this->closeConnection($this->silexApp);
                    } catch (\DBException $dbException) {
                        Logger::logCritical(sprintf(
                            '***** Cli daemon:worker %s exiting script because of db failure on fetch work : %s *****',
                            $consumer->getName(),
                            $dbException->getMessage()
                        ),
                            [ 'exception' => $dbException ]
                        );
                    }
                }
            }
            
            // Check for shutdown signal before sleep
            if ($this->gracefulShutdown) {
                Logger::logInfo('***** Cli daemon:worker caught termination signal. Shutting down [worker-sleep] *****');
                break;
            }

            // Sleep until next cycle starts
            Logger::logDebug('***** Cli daemon:worker sleep *****');

            sleep(self::CLI_WORK_SLEEP_TIME);
        }
    }

    /**
     * @param Command\Command $consumer
     * @return mixed
     */
    public function fetchWork(Command\Command $consumer)
    {
        $workUnits = null;

        try {
            // Fetch waiting work
            $workUnits = $consumer->fetchWork();
            
        } catch (\DBException $commandException) {
            Logger::logError(
                '***** Cli ' . $consumer->getName()
                . ' exiting script because of db failure on fetch work : ' .
                $commandException->getMessage() . ' *****', [ 'exception' => $commandException ]
            );
        } catch (\Exception $commandException) {
            Logger::logError(
                '***** Cli ' . $consumer->getName() . ' fetch work exception: ' . $commandException->getMessage() . ' *****',
                [ 'exception' => $commandException ]
            );

        }

        return $workUnits;
    }
    
    /**
     * Handles graceful shutdown. Switches the flag on for graceful shutdown on SIGTERM.
     * 
     * @param int $sig
     */
    public function handleShutdownSignal($sig)
    {
        Logger::logInfo("***** Cli daemon:worker caught {\"signal\": {$sig}} *****");
        
        if ($sig === SIGTERM) {
            $this->gracefulShutdown = true;
        }
    }

    /**
     * @param Command\Command $consumer
     * @param array $workUnits
     * @param OutputInterface $output
     * @throws Exceptions\CommandException
     */
    protected function processWorkUnits(Command\Command $consumer, Array $workUnits, OutputInterface $output) {
        Logger::logDebug('***** Cli daemon:worker running ' . $consumer->getName(). ' *****');

        // Process work units
        foreach ($workUnits as $workUnit) {
            /** @var \Services\Crawler\Messages\AbstractMessage $workUnit */
            if (false === ($workUnit instanceof AbstractMessage)) {
                throw new Exceptions\CommandException('Invalid workUnit');
            }

            Logger::logDebug('Work unit: ', ['workunit' => $workUnit, 'definition' => $consumer->getDefinition()]);

            // Create fake ARGV for consumer by retrieving the array values of the workerUnit
            // Change null arguments to empty strings to keep argument positions
            $consumerArgv = array_map(function($arg) { return $arg === null ? '' : $arg; }, $workUnit->toArray());
            array_unshift($consumerArgv, $consumer->getName());

            // Set work unit parameters as job Input
            $workerInput = new Input\ArgvInput($consumerArgv, $consumer->getDefinition());

            try {
                // Reset time limit
                set_time_limit(self::CLI_WORK_TIMEOUT);

                Logger::logDebug('***** Cli daemon:worker starting job: ' . $consumer->getName() . ' *****');

                // Process work unit
                $exitCode = $consumer->run($workerInput, $output);

                Logger::logDebug('***** Cli daemon:worker ' . $consumer->getName() . ' exit with code: ' . $exitCode . ' *****');
            } catch (\DBException $dbException) {
                Logger::logCritical(sprintf(
                        '***** Cli daemon:worker %s exiting script because of db failure on fetch work : %s *****',
                        $consumer->getName(),
                        $dbException->getMessage()
                    ),
                    [ 'exception' => $dbException ]
                );
            } catch (\Exception $commandException) {

                Logger::logError(
                    '***** Cli daemon:worker consumer ' . $consumer->getName() . ' exception: ' . $commandException->getMessage() . ' *****',
                    [ 'exception' => $commandException ]
                );
            }
        }
    }

    /**
     * Validate worker commands for specified jobs
     * @param Array $jobs
     * @return Array|Application
     */
    protected function getConsumers(Array $jobs) {

        $consumers = [];

        foreach ($jobs as $jobName) {

            $consumerClass = self::CONSUMER_CLASS_PREFIX;
            foreach (explode(':', $jobName) as $jobToken) {
                $jobToken = implode('', array_map(function($str){ return ucfirst($str);}, explode('-', $jobToken)));
                $consumerClass .= '\\' . ucfirst($jobToken);
            }
            $consumerClass .= self::CONSUMER_CLASS_SUFFIX;

            if (!class_exists($consumerClass)) {
                Logger::logError('Missing work consumer for job ' . $jobName, ['class' => $consumerClass]);
                continue;
            }

            if (!class_implements($consumerClass, 'ConsumerInterface')) {
                Logger::logError('Invalid work consumer  for job ' . $jobName);
                continue;
            }

            // Add usable consumer Application
            $consumers[] = new $consumerClass($this->silexApp);
        }

        return $consumers;
    }

    /**
     * close database connections for the app
     *
     * @param $app
     */
    private function closeConnection($app)
    {
        /** @var resource $db */
        $db = $app['db_connection'];
        PostgreConnectionFactory::close($db);
    }
}
