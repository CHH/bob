<?php

namespace Bob;

use CHH\FileUtils\Path;
use Symfony\Component\Finder\Finder;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;

# Public: The command line application. Contains the heavy lifting
# of everything Bob does.
class Application extends \Pimple
{
    # Public: Contains mappings from task name to a task instance.
    public
        # Public: The working directory where the bob utility was run from.
        $originalDirectory,

        # Public: The directory where the root config was found. This
        # directory is set as working directory while tasks are executed.
        $projectDirectory,

        # List of paths of all loaded config files.
        $loadedConfigs = array(),

        # Public: Should tasks run even if they're not needed?
        $forceRun = false,
        $trace = false,

        # Variables given as arguments via "var=value"
        $env = array();

    protected $taskLibraries = array();

    # Public: Initialize the application.
    function __construct()
    {
        $app = $this;

        $this['tasks'] = $this->share(function() {
            return new TaskRegistry;
        });

        $this['config.load_path'] = array('./bob_tasks');
        $this['config.file'] = 'bob_config.php';
        $this['default_task_class'] = "\\Bob\\Task";

        $this['task_factory'] = $this->protect(function($name) use ($app) {
            $action = null;
            $prerequisites = null;
            $class = $app['default_task_class'];

            foreach (array_filter(array_slice(func_get_args(), 1)) as $arg) {
                switch (true) {
                    case is_callable($arg):
                        $action = $arg;
                        break;
                    case is_string($arg) and class_exists($arg):
                        $class = $arg;
                        break;
                    case is_array($arg):
                    case ($arg instanceof \Traversable):
                    case ($arg instanceof \Iterator):
                        $prerequisites = $arg;
                        break;
                }
            }

            if (empty($name)) {
                throw new \InvalidArgumentException('Name cannot be empty');
            }

            if ($app->taskDefined($name)) {
                $task = $app['tasks'][$name];
            } else {
                $task = new $class($name, $app);
                $app->defineTask($task);
            }

            $task->enhance($prerequisites, $action);

            return $task;
        });

        $this['log.verbose'] = false;

        $this['log'] = $this->share(function() use ($app) {
            $log = new Logger("bob");

            $stderrHandler = new StreamHandler(STDERR, $app['log.verbose'] ? Logger::DEBUG : Logger::WARNING);
            $stderrHandler->setFormatter(new LineFormatter("%channel%: [%level_name%] %message%" . PHP_EOL));

            $log->pushHandler($stderrHandler);

            return $log;
        });

        $this['invocation_chain'] = $this->share(function() {
            return new TaskInvocationChain;
        });
    }

    function register(TaskLibraryInterface $taskLib, array $parameters = array())
    {
        $taskLib->register($this);
        $this->taskLibraries[] = $taskLib;

        foreach ($parameters as $param => $value) {
            $this[$param] = $value;
        }

        return $this;
    }

    function init()
    {
        $this->loadConfig();

        foreach ($this->taskLibraries as $taskLib) {
            $taskLib->boot($this);
        }
    }

    function task($name, $prerequisites = null, $action = null)
    {
        return $this['task_factory']($name, $prerequisites, $action);
    }

    function fileTask($target, $prerequisites, $action)
    {
        return $this['task_factory']($target, $prerequisites, $action, "\\Bob\\FileTask");
    }

    function execute($tasks)
    {
        $this->prepareEnv();

        $tasks = (array) $tasks;
        $start = microtime(true);

        foreach ($tasks as $taskName) {
            if (!$task = $this['tasks'][$taskName]) {
                throw new \InvalidArgumentException(sprintf('Task "%s" not found.', $taskName));
            }

            $this['log']->info(sprintf(
                'Running Task "%s"', $taskName
            ));

            if ($this->projectDirectory) {
                Path::chdir($this->projectDirectory, array($task, 'invoke'));
            } else {
                $task->invoke();
            }
        }

        $this['log']->info(sprintf('Build finished in %f seconds', microtime(true) - $start));
    }

    function taskDefined($task)
    {
        if (is_object($task) and !empty($task->name)) {
            $task = $task->name;
        }

        return (bool) $this['tasks'][$task];
    }

    function defineTask($task)
    {
        $this['tasks'][] = $task;
        return $this;
    }

    protected function prepareEnv()
    {
        $_ENV = array_merge($_ENV, $this->env);
    }

    # Internal: Looks up the build config files from the root of the project
    # and from the search dir in `./bob_tasks`. Build Config files contain
    # the task definitions.
    #
    # Returns nothing.
    protected function loadConfig()
    {
        $configPath = false;

        foreach ((array) $this['config.file'] as $file) {
            $configPath = ConfigFile::findConfigFile($file, getcwd());

            if (false !== $configPath) {
                break;
            }
        }

        if (false === $configPath) {
            $this['log']->err(sprintf(
                "Filesystem boundary reached, none of %s found.\n",
                json_encode((array) $this['config.file'])
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

        $configLoadPath = array_filter($this['config.load_path'], 'is_dir');

        if ($configLoadPath) {
            $cwd = getcwd();
            chdir($this->projectDirectory);

            $finder = Finder::create()
                ->files()->name("*.php")
                ->in($configLoadPath);

            foreach ($finder as $file) {
                include $file->getRealpath();

                $this['log']->info(sprintf('Loaded config "%s"', $file));
                $this->loadedConfigs[] = $file->getRealpath();
            }

            chdir($cwd);
        }

        return true;
    }
}

