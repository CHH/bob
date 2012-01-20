<?php

// Public: Contains Utility Functions.
namespace Bob;

require __DIR__.'/../vendor/.composer/autoload.php';
require __DIR__.'/../vendor/ulrichsg/getopt-php/src/Getopt.php';

require __DIR__.'/Bob/TaskRegistry.php';
require __DIR__.'/Bob/Task.php';
require __DIR__.'/Bob/FileTask.php';
require __DIR__.'/Bob/PackageTask.php';
require __DIR__.'/Bob/ConfigFile.php';
require __DIR__.'/Bob/Dsl.php';
require __DIR__.'/Bob/Application.php';

class BuildFailedException extends \Exception
{}

function fail($msg)
{
    throw new BuildFailedException($msg);
}

// Public: Holds the current application instance.
class Bob
{
    // Public: Instance of \Bob\Application
    static $application;
}
