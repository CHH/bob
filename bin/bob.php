<?php

namespace Bob;

function task($name, $callback)
{
    App()->task($name, $callback);
}

function desc($text)
{
    App()->desc($text);
}

function printLn($line)
{
    echo "[bob] $line\n";
}

function App()
{
    static $instance;
    if (null === $instance) $instance = new Application;
    return $instance;
}

class Application
{
    var $tasks = array();
    var $descriptions = array();
    var $argv = array();

    function task($name, $callback)
    {
        $this->tasks[$name] = $callback;
        return $this;
    }

    function desc($text)
    {
        $this->descriptions[count($this->tasks)] = $text;
        return $this;
    }

    function run()
    {
        $cwd = $_SERVER['PWD'];
        $definition = "$cwd/bob_config.php";
        $this->argv = $_SERVER['argv'];

        // Remove the script name
        array_shift($this->argv);

        if (!file_exists($definition)) {
            printLn(sprintf('Error: No bob_config.php found in %s', $definition));
            exit(1);
        }

        include $definition;

        // Run first defined task if called without arguments
        if (empty($this->argv)) {
            exit($this->runTask(key($this->tasks)));
        }

        if ($this->argv[0] == '-t') {
            $this->listTasks();
            exit(0);
        }

        $task = array_shift($this->argv);
        exit($this->runTask($task));
    }

    function listTasks()
    {
        $i = 0;
        foreach ($this->tasks as $name => $task) {
            $desc = isset($this->descriptions[$i]) ? $this->descriptions[$i] : '';
            echo "$name";
            if ($desc) echo ": $desc";
            echo "\n";
            ++$i;
        }
    }

    function execute($name)
    {
        if (!isset($this->tasks[$name])) {
            throw new \Exception(sprintf('Task "%s" not found.', $name));
        }

        $task = $this->tasks[$name];
        return call_user_func($task, $this);
    }

    function runTask($name)
    {
        try {
            printLn(sprintf('Running Task "%s"', $name));
            $start = microtime(true);
            $return = $this->execute($name);
            printLn(sprintf('Finished in %f seconds', microtime(true) - $start));

            return $return;

        } catch (\Exception $e) {
            println($e->getMessage());
            return 1;
        }
    }
}

// Start the application
App()->run();
