<?php

namespace Bob;

require __DIR__.'/../lib/bob.php';

// This is the top-level application instance, which holds all
// tasks and contains the logic for running them.
Bob::$application = new Application;

exit(Bob::$application->run());

