<?php

namespace Bob;

use Getopt,
    FileUtils;

// Public: The command line application. Contains the heavy lifting
// of everything Bob does.
class Application
{
    // Public: Contains mappings from task name to a task instance.
    var $project;

    // Public: The directory where the bob utility was run from.
    // The CWD inside a task refers to the directory where the
    // config file was found.
    var $originalDir;
    var $projectDir;

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

        $this->project = new Project;
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

        if ($operands = $this->opts->getOperands() and count($operands) > 0) {
            $taskName = $operands[0];
        } else {
            $taskName = "default";
        }

        if (!$this->project->taskExists($taskName)) {
            throw new \Exception(sprintf('Error: Task "%s" not found.', $taskName));
        }

        $start = microtime(true);
        $task = $this->project[$taskName];

        FileUtils::withCWD($this->projectDir, function() use ($task) {
            return $task->invoke();
        });

        printLn(sprintf('# %f seconds', microtime(true) - $start));
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

task('default', array('example'));

desc('Write Hello World to STDOUT');
task('example', function() {
    println("Hello World!");
    println("To add some tasks open the `bob_config.php` in your project root"
        ." at ".getcwd());
});
EOF;

        @file_put_contents(getcwd().'/bob_config.php', $config);
        println('Initialized project at '.getcwd());
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

        include $configPath;

        $this->originalDir = $_SERVER['PWD'];
        $this->projectDir = dirname($configPath);

        if (is_dir($this->projectDir.'/.bob_tasks.d')) {
            $taskSearchDir = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->projectDir.'/.bob_tasks.d')
            );

            foreach ($taskSearchDir as $file) {
                if ($file->isFile() 
                    and pathinfo($file->getRealpath(), PATHINFO_EXTENSION) == 'php') {
                    include $file->getRealpath();
                }
            }
        }
    }

    function formatTasksAndDescriptions()
    {
        $tasks = $this->project->getTasks();
        ksort($tasks);

        $text = '';

        $text .= "(in {$this->projectDir})\n";

        foreach ($tasks as $name => $task) {
            if ($name === 'default') {
                continue;
            }

            $text .= $task->usage;

            $text .= "\n";
            if ($task->description) {
                foreach (explode("\n", $task->description) as $line) {
                    $text .= "    ".ltrim($line)."\n";
                }
            }
        }

        return $text;
    }

    function formatUsage()
    {
        return <<<HELPTEXT
Usage:
  bob.php
  bob.php --init
  bob.php [-d|--definition <definition>] <task>
  bob.php -t|--tasks
  bob.php -h|--help

Arguments:
  task:
    Name of the Task to run, task names can be everything as
    long as they don't contain spaces.

Options:
  -i|--init:
    Creates an empty `bob_config.php` in the current working
    directory if none exists.
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
