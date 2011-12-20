<?php

namespace Bob;

use Getopt;

require __DIR__.'/../lib/bob.php';
require __DIR__.'/../vendor/Getopt.php';

const E_TASK_NOT_FOUND = 85;
const E_GENERAL = 1;

$context = (object) array(
    'argv' => $_SERVER['argv'],
    'cwd'  => $_SERVER['PWD']
);

$config;

// Internal: Prints a help message.
//
// Returns nothing.
function usage()
{
    echo <<<HELPTEXT
Usage:
  bob.php
  bob.php [-d|--definition <definition>] <task>
  bob.php -t|--tasks
  bob.php -h|--help

Arguments:
  task:
    Name of the Task to run, task names can be everything as
    long as they don't contain spaces.

Options:
  -d|--definition <definition>:
    Lookup <definition> in the current working directory and
    then load tasks from this file instead of "bob_config.php".
  -t|--tasks:
    Displays a fancy list of tasks and their descriptions
  -h|--help:
    Displays this message

HELPTEXT;
}

// Internal: Lists all tasks with their usages and descriptions
// and prints them to STDOUT.
//
// Returns nothing.
function listTasks($config)
{
    $i = 0;
    foreach ($config->tasks as $name => $task) {
        echo $task->usage;

        if ($i === 0) {
            echo " (Default)";
        }

        echo "\n";
        if ($task->description) {
            foreach (explode("\n", $task->description) as $line) {
                echo "    ", ltrim($line), "\n";
            }
        }
        ++$i;
    }
}

function findDependencies($name)
{
    global $config;

    $deps = array();
    $prerequisites = $config->tasks[$name]->prerequisites;

    if ($prerequisites) {
        foreach ($prerequisites as $pr) {
            if ($pr === $name) {
                continue;
            }
            $deps = array_merge($deps, findDependencies($pr));
            $deps[] = $pr;
        }
    }

    return array_unique($deps);
}

$opts = new Getopt(array(
    array('h', 'help', Getopt::NO_ARGUMENT),
    array('t', 'tasks', Getopt::NO_ARGUMENT),
    array('d', 'definition', Getopt::REQUIRED_ARGUMENT)
));

array_shift($context->argv);

try {
    $opts->parse($context->argv);

} catch (\UnexpectedValueException $e) {
    usage();
    exit(1);
}

$definitionName = $opts->getOption('definition') ?: "bob_config.php";

try {
    $config = ConfigFile::evaluate($definitionName);

} catch (\InvalidArgumentException $e) {
    println(sprintf('Error: %s', $e->getMessage()), STDERR);
    exit(1);
}

if ($opts->getOption('tasks')) {
    listTasks($config);
    exit(0);
}

if ($opts->getOption('help')) {
    usage();
    exit(0);
}

if ($operands = $opts->getOperands() and count($operands) > 0) {
    $task = $operands[0];
} else {
    $task = key($config->tasks);
}

if (!isset($config->tasks[$task])) {
    println(sprintf('Error: Task "%s" not found.', $task), STDERR);
}

foreach (findDependencies($task) as $dep) {
    if (!isset($config->tasks[$dep])) {
        println(sprintf('Error: Dependency "%s" not found.', $dep), STDERR);
        exit(E_TASK_NOT_FOUND);
    }
    project()->tasks[] = $config->tasks[$dep];
}

project()->tasks[] = $config->tasks[$task];

$start = microtime(true);
$status = project()->run($context);

printLn(sprintf('# %fs', microtime(true) - $start));

exit($status);
