<?php

namespace Console\Command;

use Symfony\Component\Console\Command\Command;
use Silex\Application;

/**
 * Class AbstractCommand
 * Base cli command.
 * Can be used for cli commands that require access to the main Silex\Application
 * @package Console\Command
 */
abstract class AbstractCommand extends Command implements ConsumerInterface
{
    const CLI_PROCESS = 'worker';

    /**
     * @var Application
     */
    protected $silexApp;

    public function __construct(Application $silexApp, $name = null)
    {
        parent::__construct($name);
        $this->silexApp = $silexApp;
    }
}
