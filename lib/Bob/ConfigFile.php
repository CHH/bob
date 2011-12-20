<?php

namespace Bob;

// Holds the config instance for the static DSL methods
// while evaluating the config file.
$_bob_config;

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
    global $_bob_config;
    $taskCount = count($_bob_config->tasks);

    if ($callback === null) {
        $callback = $prerequisites;
        $prerequisites = array();
    }

    $task = new Task($name, $callback);
    $task->prerequisites = $prerequisites;
    $task->description = isset($_bob_config->descriptions[$taskCount]) ? $_bob_config->descriptions[$taskCount] : '';
    $task->usage = isset($_bob_config->usages[$taskCount]) ? $_bob_config->usages[$taskCount] : $name;

    $tasks[$name] = $task;
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
    global $_bob_config;

    $_bob_config->descriptions[count($_bob_config->tasks)] = $desc;

    if ($usage) {
        $_bob_config->usages[count($_bob_config->tasks)] = $usage;
    }
}

class ConfigFile
{
    public $tasks = array();
    public $descriptions = array();
    public $usages = array();

    static function evaluate($filename = "bob_config.php", $cwd = null)
    {
        global $_bob_config;

        $cwd    = $cwd ?: $_SERVER['PWD'];
        $path   = static::findConfigFile($filename, $cwd);

        if (false === $path) {
            throw new \InvalidArgumentException(sprintf(
                'Config file "%s" not found in "%s".', $filename, $cwd
            ));
        }

        $_bob_config = new static;
        include $path;
        return $_bob_config;
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
    static protected function findConfigFile($filename, $cwd)
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
