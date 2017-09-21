<?php

namespace Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Interface for Consumer threads
 * For the Worker process based on the producer-consumer model
 *
 * @package Console\Command
 */
interface ConsumerInterface {
    /**
     * This method calls the API endpoint
     * It is called when run from the CLI (app/console).
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return int $exitCode
     */
    public function run(InputInterface $input, OutputInterface $output);
    /**
     * Fetch work unit(-s)
     * @return mixed Array with work or null if no work at the moment
     */
    public function fetchWork();
}
