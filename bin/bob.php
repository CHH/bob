<?php

namespace Bob;

require_once __DIR__.'/../lib/bob.php';

const E_TASK_NOT_FOUND = 85;
const E_DEFINITION_NOT_FOUND = 86;

// Store all tasks, with their name as key
// and the callback as value.
$tasks = array();

// Descriptions and usages are simple
// lists. The `desc` function simply appends
// to these lists.
//
// Therefore the call to `desc` _must_ happen
// before a task is defined, so the indexes match
// the order of the tasks in the `$tasks` array.
$descriptions = array();
$usages = array();

$context = (object) array(
    'argv' => $_SERVER['argv'],
    'cwd'  => $_SERVER['PWD']
);

// You can output a usage message with the `-h` or `--help`
// flags.
function usage()
{
    echo <<<HELPTEXT
Usage: bob.php [-t|--tasks] <task>

Arguments:
  task:
    Name of the Task to run, task names can be everything as
    long as they don't contain spaces.

Options:
  -t|--tasks:
    Displays a fancy list of tasks and their descriptions
  -h|--help:
    Displays this message

HELPTEXT;
}

function execute($name)
{
    global $tasks, $context;

    if (!isset($tasks[$name])) {
        printLn(sprintf('Error: Task "%s" not found.', $name));
        exit(E_TASK_NOT_FOUND);
    }

    $task = $tasks[$name];
    return call_user_func($task, $context);
}

function runTask($name, $context = null)
{
    printLn(sprintf('Running Task "%s"', $name));

    try {
        $start = microtime(true);
        $return = execute($name, $context);
        printLn(sprintf('Finished in %f seconds', microtime(true) - $start));
    } catch (\Exception $e) {
        println('Error: '.$e);
        $return = 1;
    }

    return $return === null ? 0 : $return;
}

function listTasks()
{
    global $tasks, $descriptions, $usages;

    $i = 0;
    foreach ($tasks as $name => $task) {
        $desc  = isset($descriptions[$i]) ? $descriptions[$i] : '';
        $usage = isset($usages[$i]) ? $usages[$i] : $name;

        echo "$usage";

        if ($i === 0) {
            echo " (Default)";
        }

        echo "\n";
        if ($desc) {
            foreach (explode("\n", $desc) as $descLine) {
                $descLine = ltrim($descLine);
                echo "    $descLine\n";
            }
        }
        ++$i;
    }
}

function task($name, $callback)
{
    global $tasks;
    $tasks[$name] = $callback;
}

function desc($text, $usage = null)
{
    global $tasks, $descriptions, $usages;

    $descriptions[count($tasks)] = $text;

    if ($usage) {
        $usages[count($tasks)] = $usage;
    }
}

$definition = "{$context->cwd}/bob_config.php";

if (!file_exists($definition)) {
    printLn(sprintf('Error: Definition %s not found', $definition));
    exit(E_DEFINITION_NOT_FOUND);
}

include $definition;

array_shift($context->argv);

if (isset($context->argv[0])) {
    if ($context->argv[0] == '-t' or $context->argv[0] == '--tasks') {
        listTasks();
        exit(0);
    }

    if ($context->argv[0] == '-h' or $context->argv[0] == '--help') {
        usage();
        exit(0);
    }

    $task = $context->argv[0];
    array_shift($context->argv);
} else {
    $task = key($tasks);
}

exit(runTask($task));

