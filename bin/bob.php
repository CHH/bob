<?php

namespace Bob;

require_once __DIR__.'/../lib/bob.php';

const E_TASK_NOT_FOUND = 85;
const E_DEFINITION_NOT_FOUND = 86;

$app = new Application;

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

function runTask($name)
{
    global $app;

    if (!isset($app->tasks[$name])) {
        printLn(sprintf('Error: Task "%s" not found.', $name));
        return E_TASK_NOT_FOUND;
    }

    printLn(sprintf('Running Task "%s"', $name));

    try {
        $start = microtime(true);
        $return = $app->execute($name);
        printLn(sprintf('Finished in %f seconds', microtime(true) - $start));
    } catch (\Exception $e) {
        println('Error: '.$e);
        $return = 1;
    }

    return $return === null ? 0 : $return;
}

function listTasks()
{
    global $app;

    $i = 0;
    foreach ($app->tasks as $name => $task) {
        $desc  = isset($app->descriptions[$i]) ? $app->descriptions[$i] : '';
        $usage = isset($app->usages[$i]) ? $app->usages[$i] : $name;

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
    global $app;
    $app->task($name, $callback);
}

function desc($text, $usage = null)
{
    global $app;
    $app->desc($text, $usage);
}

$CWD  = $_SERVER['PWD'];
$ARGV = $_SERVER['argv'];
$definition = "$CWD/bob_config.php";

if (!file_exists($definition)) {
    printLn(sprintf('Error: Definition %s not found', $definition));
    exit(E_DEFINITION_NOT_FOUND);
}

include $definition;

array_shift($ARGV);

if (isset($ARGV[0])) {
    if ($ARGV[0] == '-t' or $ARGV[0] == '--tasks') {
        listTasks();
        exit(0);
    }

    if ($ARGV[0] == '-h' or $ARGV[0] == '--help') {
        usage();
        exit(0);
    }

    $task = $ARGV[0];
    array_shift($ARGV);
} else {
    $task = key($app->tasks);
}

$app->argv = $ARGV;
exit(runTask($task));

