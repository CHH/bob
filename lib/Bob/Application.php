<?php

namespace Bob;

use CHH\Optparse,
    CHH\FileUtils\Path,
    Symfony\Component\Finder\Finder,
    Monolog\Logger,
    Monolog\Handler\StreamHandler,
    Monolog\Formatter\LineFormatter;

# Public: The command line application. Contains the heavy lifting
# of everything Bob does.
class Application
{
    # Public: Contains mappings from task name to a task instance.
    public
        $tasks,

        # Public: The working directory where the bob utility was run from.
        $originalDir,

        # Public: The directory where the root config was found. This
        # directory is set as working directory while tasks are executed.
        $projectDir,

        # Public: The command line option parser. You can add your own options
        # when inside a task if you call `addOptions` with the same format as seen here.
        $opts,

        # Public: Enable tracing.
        $trace = false,

        $configFile = "bob_config.php",
        $configSearchDir = "bob_tasks",

        # List of paths of all loaded config files.
        $loadedConfigs = array(),

        # Public: Should tasks run even if they're not needed?
        $forceRun = false,

        # Show all tasks in `--tasks`.
        $showAllTasks = false,

        # Logger instance.
        $log,

        $invocationChain,

        $verbose = false;

    # Public: Initialize the application.
    function __construct()
    {
        $this->opts = new Optparse\Parser;
        $this->opts
            ->addFlag('init', array("alias" => '-i'))
            ->addFlag('help', array("alias" => '-h'))
            ->addFlag('tasks', array("alias" => '-t'))
            ->addFlag('chdir', array("alias" => '-C', "has_value" => true))
            ->addFlagVar('all', $this->showAllTasks, array("alias" => '-A'))
            ->addFlagVar('trace', $this->trace, array("alias" => '-T'))
            ->addFlagVar('force', $this->forceRun, array("alias" => '-f'))
            ->addFlagVar('verbose', $this->verbose, array("alias" => '-v'))
        ;

        $this->tasks = new TaskRegistry;
        $this->invocationChain = new TaskInvocationChain;

        $this->log = new Logger("bob");

        $stderrHandler = new StreamHandler(STDERR);
        $stderrHandler->setFormatter(new LineFormatter("%channel%: [%level_name%] %message%" . PHP_EOL));

        $this->log->pushHandler($stderrHandler);
    }

    # Public: Parses the arguments list for options and
    # then does something useful depending on what is given.
    #
    # argv - A list of arguments supplied on the CLI.
    #
    # Returns the desired exit status as Integer.
    function run($argv = null)
    {
        try {
            $this->opts->parse($argv);
        } catch (Optparse\Exception $e) {
            fwrite(STDERR, "{$e->getMessage()}\n\n");
            fwrite(STDERR, $this->formatUsage());
            return 1;
        }

        if ($this->opts["init"]) {
            $this->initProject();
            return 0;
        }

        if ($this->opts["help"]) {
            fwrite(STDERR, $this->formatUsage());
            return 0;
        }

        if ($dir = $this->opts["chdir"]) {
            if (!is_dir($dir)) {
                $this->log->err(sprintf('Dir not found: "%s"', $dir));
                return 1;
            }

            $this->log->info(sprintf('Changing working directory to "%s"', realpath($dir)));

            chdir($dir);
        }

        if (!$this->loadConfig()) {
            return 127;
        }

        if ($this->opts["tasks"]) {
            fwrite(STDERR, $this->formatTasksAndDescriptions());
            return 0;
        }

        return $this->withErrorHandling(array($this, 'runTasks'));
    }

    protected function collectTasks()
    {
        $tasks = array();
        $args = $this->opts->args();

        foreach ($args as $arg) {
            if (preg_match('/^(\w+)=(.*)$/', $arg, $matches)) {
                $_ENV[$matches[1]] = trim($matches[2], '"');
                continue;
            }

            $tasks[] = $arg;
        }

        if (empty($tasks)) {
            $tasks += array('default');
        }

        return $tasks;
    }

    protected function runTasks()
    {
        $start = microtime(true);

        foreach ($this->collectTasks() as $taskName) {
            if (!$task = $this->tasks[$taskName]) {
                throw new \Exception(sprintf('Task "%s" not found.', $taskName));
            }

            if ($this->verbose) {
                $this->log->info(sprintf(
                    'Running Task "%s"', $taskName
                ));
            }

            Path::chdir($this->projectDir, function() use ($task) {
                return $task->invoke();
            });
        }

        if ($this->verbose) {
            $this->log->info(sprintf('Build finished in %f seconds', microtime(true) - $start));
        }
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
    }

    protected function initProject()
    {
        $cwd = $_SERVER['PWD'];

        if (file_exists("$cwd/{$this->configFile}")) {
            $this->log->err('Project already has a bob_config.php');
            return;
        }

        $config = <<<'EOF'
<?php

namespace Bob\BuildConfig;

task('default', array('example'));

desc('Write Hello World to STDOUT');
task('example', function() {
    println("Hello World!");
    println("To add some tasks open the `bob_config.php` in your project root"
        ." at ".getcwd());
});
EOF;

        @file_put_contents("$cwd/{$this->configFile}", $config);

        $this->log->info(sprintf('Initialized project at "%s"', $cwd));
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
            $this->log->err(sprintf(
                "Filesystem boundary reached, no %s found.\n",
                $this->configFile
            ));
            return false;
        }

        include $configPath;
        $this->loadedConfigs[] = $configPath;

        # Save the original working directory, the working directory
        # gets set to the project directory while running tasks.
        $this->originalDir = getcwd();

        # The project dir is the directory of the root config.
        $this->projectDir = dirname($configPath);

        # Load tasks from the search dir in "./bob_tasks/"
        if (is_dir("{$this->projectDir}/{$this->configSearchDir}")) {
            $finder = Finder::create()
                ->files()->name("*.php")
                ->in("{$this->projectDir}/{$this->configSearchDir}");

            foreach ($finder as $file) {
                include $file->getRealpath();
                $this->loadedConfigs[] = $file->getRealpath();
            }
        }

        return true;
    }

    function withErrorHandling($callback)
    {
        try {
            call_user_func($callback);
            return 0;
        } catch (\Exception $e) {
            $this->log->err(sprintf(
                "Build failed: %s (use --trace to get a stack trace)", $e->getMessage())
            );

            if ($this->trace) {
                $this->log->info($e->getTraceAsString());
            }
            return 1;
        }
    }

    function formatTasksAndDescriptions()
    {
        $tasks = $this->tasks->getArrayCopy();
        ksort($tasks);

        $text = '';
        $text .= "(in {$this->projectDir})\n";

        foreach ($tasks as $name => $task) {
            if ($name === 'default' || (!$task->description && !$this->showAllTasks)) {
                continue;
            }

            if ($task instanceof FileTask) {
                $text .= "File => {$task->name}";
            } else {
                $text .= "bob {$task->name}";
            }

            $text .= "\n";

            if ($task->description) {
                $text .= "\t{$task->description}\n";
            }
        }

        return $text;
    }

    function formatUsage()
    {
        $version = \Bob::VERSION;
        $bin = basename($_SERVER['SCRIPT_NAME']);

        return <<<HELPTEXT
bob $version

Usage:
  $bin
  $bin [VAR=VALUE...] [TASK...]
  $bin --init
  $bin -t|--tasks
  $bin -h|--help

Arguments:
  TASK:
    One or more task names to run. Task names can be everything as
    long as they don't contain spaces.
  VAR=VALUE:
    One or more environment variable definitions.
    These get placed in the \$_ENV array.

Options:
  -i|--init:
    Creates an empty `bob_config.php` in the current working
    directory if none exists.
  -t|--tasks:
    Displays a fancy list of tasks and their descriptions
  -A|--all:
    Shows all tasks, even file tasks and tasks without description.
  -C|--chdir <dir>:
    Changes the working directory to <dir> before running tasks.
  -T|--trace:
    Logs trace messages to STDERR
  -f|--force:
    Force to run all tasks, even if they're not needed
  -v|--verbose:
    Be more verbose.
  -h|--help:
    Displays this message


HELPTEXT;
    }
}
