<?php

namespace Bob;

use Getopt;

// Public: The command line application. Contains the heavy lifting
// of everything Bob does.
class Application
{
    // Public: Contains mappings from task name to a task instance.
    var $tasks = array();

    // Public: The directory where the bob utility was run from.
    // The CWD inside a task refers to the directory where the
    // config file was found.
    var $originalDir;

    // Public: The command line option parser. You can add your own options 
    // when inside a task if you call `addOptions` with the same format as seen here.
    var $opts;

    // Public: Initialize the application.
    function __construct()
    {
        $this->opts = new Getopt(array(
            array('i', 'init', Getopt::NO_ARGUMENT),
            array('h', 'help', Getopt::NO_ARGUMENT),
            array('t', 'tasks', Getopt::NO_ARGUMENT),
            array('d', 'definition', Getopt::REQUIRED_ARGUMENT)
        ));
    }

    // Public: Parses the arguments list for options and
    // then does something useful depending on what is given.
    //
    // argv - A list of arguments supplied on the CLI.
    //
    // Returns the desired exit status as Integer.
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

        if ($this->opts->getOption('init')) {
            $this->initProject();
            return 0;
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

        $runList = $this->buildRunList();

        $start = microtime(true);
        foreach ($runList as $task) {
            $task->invoke();
        }
        printLn(sprintf('# %fs', microtime(true) - $start));
    }

    function initProject()
    {
        if (file_exists(getcwd().'/bob_config.php')) {
            println('Project has already a bob_config.php');
            return;
        }

        $config = <<<'EOF'
<?php

namespace Bob;

desc('Write Hello World to STDOUT');
task('example', function() {
    println("Hello World!");
    println("To add some tasks open the `bob_config.php` in your project root"
        ." at ".getcwd());
});
EOF;

        @file_put_contents(getcwd().'/bob_config.php', $config);
        println('Inited project at '.getcwd());
    }

    // Internal: Looks up the config file path and includes it. Does a 
    // `chdir` to the dirname where the config is located too. So the
    // CWD inside of tasks always refers to the project's root.
    //
    // Returns nothing.
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

        $this->originalDir = $_SERVER['PWD'];
        chdir(dirname($configPath));
    }

    // Internal: Looks in the arguments for tasks, fetches its dependencies
    // and returns a list of tasks which should be run. If no task name is
    // supplied via the CLI then the first defined task is used.
    //
    // Returns a list of tasks to run as Array.
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
        foreach ($this->findDependencies($taskName) as $dep) {
            $runList[] = $this->tasks[$dep];
        }
        $runList[] = $this->tasks[$taskName];

        return $runList;
    }

    // Public: Checks if the task is defined.
    //
    // Returns boolean TRUE if the task is defined, FALSE otherwise.
    function taskExists($name)
    {
        return array_key_exists($name, $this->tasks);
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

                if ($this->taskExists($pr)) {
                    $deps = array_merge($deps, $this->findDependencies($pr));
                    $deps[] = $pr;
                }
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
