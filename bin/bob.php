<?php

// Bobfile DSL
namespace Bobfile
{
    function task($name, $callback)
    {
        \Bob\App()->task($name, $callback);
    }

    function desc($text)
    {
        \Bob\App()->desc($text);
    }
}

namespace Bob
{
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
            $definition = "$cwd/Bobfile";
            $this->argv = $_SERVER['argv'];

            // Remove the script name
            array_shift($this->argv);

            if (!file_exists($definition)) {
                printLn(sprintf('Error: No Bobfile found in %s', $definition));
                exit(1);
            }

            include $definition;

            // Run first defined task if called without arguments
            if (empty($this->argv)) {
                $this->execute(key($this->tasks));
                exit(0);
            }

            if ($this->argv[0] == '-t') {
                $this->listTasks();
                exit(0);
            }

            $task = array_shift($this->argv);
            exit($this->execute($task));
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
                printLn(sprintf('Task "%s" not found', $name));
                exit(1);
            }

            $task = $this->tasks[$name];

            printLn(sprintf('Running Task "%s"', $name));
            $start = microtime(true);
            $return = call_user_func($task, $this);
            printLn(sprintf('Finished in %f seconds', microtime(true) - $start));

            return $return;
        }
    }

    // Start the application
    App()->run();
}
