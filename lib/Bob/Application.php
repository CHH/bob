<?php

namespace Bob;

use CHH\FileUtils\Path;
use Symfony\Component\Finder\Finder;
use Monolog\Logger;

# Public: The command line application. Contains the heavy lifting
# of everything Bob does.
class Application
{
    # Public: Contains mappings from task name to a task instance.
    public
        $tasks,

        # Public: The working directory where the bob utility was run from.
        $originalDirectory,

        # Public: The directory where the root config was found. This
        # directory is set as working directory while tasks are executed.
        $projectDirectory,

        $configFile = "bob_config.php",
        $configLoadPath = array('./bob_tasks'),

        # List of paths of all loaded config files.
        $loadedConfigs = array(),

        # Public: Should tasks run even if they're not needed?
        $forceRun = false,
        $trace = false,

        $invocationChain;

    protected $log;

    # Public: Initialize the application.
    function __construct()
    {
        $this->tasks = new TaskRegistry;
        $this->invocationChain = new TaskInvocationChain;
    }

    function init()
    {
        $this->loadConfig();
    }

    function execute($tasks)
    {
        $tasks = (array) $tasks;
        $start = microtime(true);

        foreach ($tasks as $taskName) {
            if (!$task = $this->tasks[$taskName]) {
                throw new \Exception(sprintf('Task "%s" not found.', $taskName));
            }

            $this->log->info(sprintf(
                'Running Task "%s"', $taskName
            ));

            Path::chdir($this->projectDirectory, function() use ($task) {
                return $task->invoke();
            });
        }

        $this->logger()->info(sprintf('Build finished in %f seconds', microtime(true) - $start));
    }

    function taskDefined($task)
    {
        if (is_object($task) and !empty($task->name)) {
            $task = $task->name;
        }

        return (bool) $this->tasks[$task];
    }

    function defineTask($task)
    {
        $this->tasks[] = $task;
        return $this;
    }

    function logger()
    {
        return $this->log;
    }

    function setLogger(Logger $logger)
    {
        $this->log = $logger;
    }

    # Internal: Looks up the build config files from the root of the project
    # and from the search dir in `./bob_tasks`. Build Config files contain
    # the task definitions.
    #
    # Returns nothing.
    protected function loadConfig()
    {
        $configPath = ConfigFile::findConfigFile($this->configFile, getcwd());

        if (false === $configPath) {
            $this->logger()->err(sprintf(
                "Filesystem boundary reached, no %s found.\n",
                $this->configFile
            ));
            return false;
        }

        include $configPath;
        $this->loadedConfigs[] = $configPath;

        # Save the original working directory, the working directory
        # gets set to the project directory while running tasks.
        $this->originalDirectory = getcwd();

        # The project dir is the directory of the root config.
        $this->projectDirectory = dirname($configPath);

        $configLoadPath = array_filter($this->configLoadPath, 'is_dir');

        if ($configLoadPath) {
            $cwd = getcwd();
            chdir($this->projectDirectory);

            $finder = Finder::create()
                ->files()->name("*.php")
                ->in($configLoadPath);

            foreach ($finder as $file) {
                include $file->getRealpath();

                $this->logger()->info(sprintf('Loaded config "%s"', $file));
                $this->loadedConfigs[] = $file->getRealpath();
            }

            chdir($cwd);
        }

        return true;
    }
}

