<?php

namespace Bob;

use Getopt;

class Application
{
    var $tasks = array();
    var $descriptions = array();
    var $usages = array();
    var $originalDir;
    var $opts;

    function __construct()
    {
        $this->opts = new Getopt(array(
            array('h', 'help', Getopt::NO_ARGUMENT),
            array('t', 'tasks', Getopt::NO_ARGUMENT),
            array('d', 'definition', Getopt::REQUIRED_ARGUMENT)
        ));
    }

    function run($argv = null)
    {
        if (null === $argv) {
            $argv = $_SERVER['argv'];
            array_shift($argv);
        }

        try {
            $this->opts->parse($argv);
        } catch (\UnexpectedValueException $e) {
            println($this->formatUsage(), STDERR);
            return 1;
        }

        $this->loadConfig();

        if ($this->opts->getOption('help')) {
            echo $this->formatUsage();
            return 0;
        }

        if ($this->opts->getOption('tasks')) {
            echo $this->formatTasksAndDescriptions();
            return 0;
        }

        $this->originalDir = $_SERVER['PWD'];
        chdir(dirname($configPath));

        $runList = $this->buildRunList();

        $start = microtime(true);
        foreach ($runList as $task) {
            $task->invoke();
        }
        printLn(sprintf('# %fs', microtime(true) - $start));
    }

    function loadConfig()
    {
        $configName = $this->opts->getOption('definition') ?: 'bob_config.php';
        $configPath = ConfigFile::findConfigFile($configName, $_SERVER['PWD']);

        if (false === $configPath) {
            throw new \Exception(sprintf(
                'Error: Filesystem boundary reached. No %s found.', 
                $configName
            ));
        }

        ConfigFile::evaluate($configPath);
    }

    function buildRunList()
    {
        if ($operands = $this->opts->getOperands() and count($operands) > 0) {
            $taskName = $operands[0];
        } else {
            $taskName = key($this->tasks);
        }

        if (!$this->taskExists($taskName)) {
            throw new \Exception(sprintf('Error: Task "%s" not found.', $taskName));
        }

        $runList = array();
        // Todo: Add dependencies here to runlist.
        $runList[] = $this->tasks[$taskName];

        return $runList;
    }

    function taskExists($name)
    {
        return array_key_exists($name, $this->tasks);
    }

    function addDescription($text)
    {
        $this->descriptions[count($this->tasks)] = $text;
    }

    function addUsage($text)
    {
        $this->usages[count($this->tasks)] = $text;
    }

    function registerTask($name, $task)
    {
        $taskCount = count($this->tasks);

        $task->description = isset($this->descriptions[$taskCount])
            ? $this->descriptions[$taskCount]
            : '';

        $task->usage = isset($this->usages[$taskCount])
            ? $this->usages[$taskCount]
            : $name;

        $this->tasks[$name] = $task;
    }

    function findDependencies($name)
    {
        $deps = array();
        $prerequisites = $this->tasks[$name]->prerequisites;

        if ($prerequisites) {
            foreach ($prerequisites as $pr) {
                if ($pr === $name) {
                    continue;
                }
                $deps = array_merge($deps, $this->findDependencies($pr));
                $deps[] = $pr;
            }
        }

        return array_unique($deps);
    }

    function formatTasksAndDescriptions()
    {
        $i = 0;
        $text = '';

        foreach ($this->tasks as $name => $task) {
            $text .= $task->usage;

            if ($i === 0) {
                $text .= " (Default)";
            }

            $text .= "\n";
            if ($task->description) {
                foreach (explode("\n", $task->description) as $line) {
                    $text .= "    ".ltrim($line)."\n";
                }
            }
            ++$i;
        }

        return $text;
    }

    function formatUsage()
    {
        return <<<HELPTEXT
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
}
