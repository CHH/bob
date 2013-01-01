<?php

function includeIfExists($file)
{
    if (file_exists($file)) {
        require_once $file;
        return true;
    }
}

if ((!$loader = includeIfExists(__DIR__.'/../vendor/autoload.php')) && (!$loader = includeIfExists(__DIR__.'/../../../autoload.php'))) {
    die('You must set up the project dependencies, run the following commands:'.PHP_EOL.
        'curl -s http://getcomposer.org/installer | php'.PHP_EOL.
        'php composer.phar install'.PHP_EOL);
}

require_once(__DIR__ . '/../lib/Bob.php');

$cli = new \Bob\Cli;

Bob::$application = $cli->application;

exit($cli->run());

