<?php

namespace Bob;

// Public: Defines the callback as a task with the given name.
//
// name     - Task Name.
// callback - A callback, which gets run if the task is requested.
//
// Examples
//
//     task('hello', function() {
//         echo "Hello World\n";
//     });
//
// Returns nothing.
function task($name, $prerequisites = array(), $callback = null)
{
    if ($callback === null) {
        $callback = $prerequisites;
        $prerequisites = array();
    }

    $task = new Task($name, $callback);
    $task->prerequisites = $prerequisites;

    Bob::$application->registerTask($name, $task);
}

// Public: Defines the description of the subsequent task.
//
// text  - Description text, should explain in plain sentences
//         what the task does.
// usage - A usage message, must start with the task name and
//         should then be followed by the arguments.
//
// Examples
//
//     desc('Says Hello World to NAME', 'greet NAME');
//     task('greet', function($ctx) {
//         $name = $ctx->argv[1];
//
//         echo "Hello World $name!\n";
//     });
//
// Returns nothing.
function desc($desc, $usage = null)
{
    Bob::$application->addDescription($desc);

    if ($usage) {
        Bob::$application->addUsage($usage);
    }
}

class ConfigFile
{
    static function evaluate($path)
    {
        if (!file_exists($path)) {
            throw new \InvalidArgumentException(sprintf(
                'Config file "%s" not found in "%s".', $filename, $cwd
            ));
        }

        include $path;
    }

    // Internal: Looks up the provided definition file
    // in the directory tree, starting by the provided
    // directory walks the tree up until it reaches the
    // filesystem boundary.
    //
    // filename - File name to look up
    // cwd      - Starting point for traversing up the
    //            directory tree.
    //
    // Returns the absolute path to the file as String or
    // False if the file was not found.
    static function findConfigFile($filename, $cwd)
    {
        if (!is_dir($cwd)) {
            throw new \InvalidArgumentException(sprintf(
                '%s is not a directory', $cwd
            ));
        }

        // Look for the definition Name in the $cwd
        // until one is found.
        while (!$rp = realpath("$cwd/$filename")) {
            // Go up the hierarchy
            $cwd .= '/..';

            // We are at the filesystem boundary if there's
            // nothing to go up.
            if (realpath($cwd) === false) {
                break;
            }
        }

        return $rp;
    }
}
