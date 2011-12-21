<?php

namespace Bob;

require __DIR__.'/../lib/bob.php';

class Bob
{
    static $application;
}

Bob::$application = new Application;

try {
    Bob::$application->run();

} catch (\Exception $e) {
    println($e, STDERR);
    exit(1);
}

