<?php

ini_set("error_reporting", E_ALL);

use Library\Logger\Logger;
use Tests\Mocks\MockLogAdapter;

define('PATH_ROOT', __DIR__ . '/..');
define('DEBUG', true);

$loader = require __DIR__.'/../vendor/autoload.php';

Logger::setLoggerAdapter(new MockLogAdapter());
