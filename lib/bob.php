<?php

require __DIR__.'/../vendor/.composer/autoload.php';
require __DIR__.'/../vendor/ulrichsg/getopt-php/src/Getopt.php';

require __DIR__.'/Bob/TaskRegistry.php';
require __DIR__.'/Bob/Task.php';
require __DIR__.'/Bob/FileTask.php';
require __DIR__.'/Bob/PackageTask.php';
require __DIR__.'/Bob/ConfigFile.php';
require __DIR__.'/Bob/Dsl.php';
require __DIR__.'/Bob/Application.php';

// Internal: Holds the current application instance.
class Bob
{
    // Instance of \Bob\Application
    static $application;
}
