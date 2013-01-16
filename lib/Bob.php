<?php

require_once(__DIR__ . "/Bob/Dsl.php");

# Internal: Holds the current application instance.
class Bob
{
    const VERSION = "v1.0.x-dev";

    # Instance of \Bob\Application
    static $application;
}
