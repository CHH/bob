<?php

namespace Bob;

require_once __DIR__.'/../lib/bob.php';

$app = new Application;

function runTask($name)
{
    global $app;

    try {
        printLn(sprintf('Running Task "%s"', $name));
        $start = microtime(true);
        $return = $app->execute($name);
        printLn(sprintf('Finished in %f seconds', microtime(true) - $start));

        return $return;
    } catch (\Exception $e) {
        println($e->getMessage());
        return 1;
    }
}

function listTasks()
{
    global $app;

    $i = 0;
    foreach ($app->tasks as $name => $task) {
        $desc = isset($app->descriptions[$i]) ? $app->descriptions[$i] : '';
        echo "$name";

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

function desc($text)
{
    global $app;
    $app->desc($text);
}

$CWD  = $_SERVER['PWD'];
$ARGV = $_SERVER['argv'];
$definition = "$CWD/bob_config.php";

if (!file_exists($definition)) {
    printLn(sprintf('Error: Definition %s not found', $definition));
    exit(1);
}

include $definition;

array_shift($ARGV);

if (isset($ARGV[0])) {
    if ($ARGV[0] == '-t' or $ARGV[0] == '--tasks') {
        listTasks();
        exit(0);
    }

    $task = $ARGV[0];
    array_shift($ARGV);
} else {
    $task = key($app->tasks);
}

$app->argv = $ARGV;
exit(runTask($task));

