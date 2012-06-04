<?php

require __DIR__.'/../lib/Bob.php';

// This is the top-level application instance, which holds all
// tasks and contains the logic for running them.
Bob::$application = new \Bob\Application;

exit(Bob::$application->run());

