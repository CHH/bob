<?php

namespace Bob;

// Internal: Holds the config instance for the static DSL methods
// while evaluating the config file.
//
// config - The Config instance, the config holder *does not* create
//          config instances by itself.
//
// Examples
//
// Setup the Config Holder:
//
//     ConfigHolder(new ConfigFile);
//
// Retrieve the config instance by calling the function without arguments:
//
//     $config = ConfigHolder();
//
// Returns an object.
function ConfigHolder($config = null)
{
    static $instance;

    if (null !== $config) {
        $instance = $config;
    }
    return $instance;
}

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
    $taskCount = count(ConfigHolder()->tasks);

    if ($callback === null) {
        $callback = $prerequisites;
        $prerequisites = array();
    }

    $task = new Task($name, $callback);
    $task->prerequisites = $prerequisites;

    $task->description = isset(ConfigHolder()->descriptions[$taskCount])
        ? ConfigHolder()->descriptions[$taskCount]
        : '';

    $task->usage = isset(ConfigHolder()->usages[$taskCount])
        ? ConfigHolder()->usages[$taskCount] 
        : $name;

    ConfigHolder()->tasks[$name] = $task;
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
    ConfigHolder()->descriptions[count(ConfigHolder()->tasks)] = $desc;

    if ($usage) {
        ConfigHolder()->usages[count(ConfigHolder()->tasks)] = $usage;
    }
}

class ConfigFile
{
    public $tasks = array();
    public $descriptions = array();
    public $usages = array();

    static function evaluate($path)
    {
        if (!file_exists($path)) {
            throw new \InvalidArgumentException(sprintf(
                'Config file "%s" not found in "%s".', $filename, $cwd
            ));
        }

        ConfigHolder(new static);
        include $path;
        return ConfigHolder();
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
