<?php

namespace Bob;

require __DIR__.'/../lib/bob.php';

class Bob
{
    static $application;
}

// This is the top-level application instance, which holds all
// tasks and contains the logic for running them.
Bob::$application = new Application;

try {
    Bob::$application->run();

} catch (\Exception $e) {
    // Print exceptions to STDERR and exit with an error.
    println($e, STDERR);
    exit(1);
}

