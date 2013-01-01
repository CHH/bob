<?php

namespace Bob;

use CHH\Optparse;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;

class Cli
{
    protected $opts;
    protected $log;

    public $application;
    public $verbose = false;
    public $forceRun = false;
    public $showAllTasks = false;
    public $trace = false;

    function __construct()
    {
        $this->application = new Application;

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
    }

    function run($argv = null)
    {
        try {
            $this->opts->parse($argv);
        } catch (Optparse\Exception $e) {
            fwrite(STDERR, "{$e->getMessage()}\n\n");
            fwrite(STDERR, $this->formatUsage());
            return 1;
        }

        $this->application->setLogger($this->logger());
        $this->application->trace = $this->trace;
        $this->application->forceRun = $this->forceRun;

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
                $this->logger()->err(sprintf('Directory not found: "%s"', $dir));
                return 1;
            }

            $this->logger()->info(sprintf('Changing working directory to "%s"', realpath($dir)));

            chdir($dir);
        }

        $this->application->configLoadPath = array_merge($this->application->configLoadPath, explode(':', @$_SERVER['BOB_CONFIG_PATH']));

        try {
            $this->application->init();
        } catch (\Exception $e) {
            fwrite(STDERR, $e);
            return 127;
        }

        if ($this->opts["tasks"]) {
            fwrite(STDERR, $this->formatTasksAndDescriptions());
            return 0;
        }

        $this->withErrorHandling(array($this, 'runTasks'));
    }

    function logger()
    {
        if (null === $this->log) {
            $this->log = new Logger("bob");

            $stderrHandler = new StreamHandler(STDERR, $this->verbose ? Logger::DEBUG : Logger::WARNING);
            $stderrHandler->setFormatter(new LineFormatter("%channel%: [%level_name%] %message%" . PHP_EOL));

            $this->log->pushHandler($stderrHandler);
        }

        return $this->log;
    }

    function withErrorHandling($callback)
    {
        try {
            call_user_func($callback);
            return 0;
        } catch (\Exception $e) {
            $this->logger()->err(sprintf(
                "Build failed: %s (use --trace to get a stack trace)", $e->getMessage())
            );

            if ($this->trace) {
                $this->logger()->info($e->getTraceAsString());
            }

            return 1;
        }
    }

    protected function runTasks()
    {
        return $this->application->execute($this->collectTasks());
    }

    protected function collectTasks()
    {
        $tasks = array();
        $args = $this->opts->args();

        foreach ($args as $arg) {
            if (preg_match('/^(\w+)=(.*)$/', $arg, $matches)) {
                $this->application->env[$matches[1]] = trim($matches[2], '"');
                continue;
            }

            $tasks[] = $arg;
        }

        if (empty($tasks)) {
            $tasks += array('default');
        }

        return $tasks;
    }

    protected function formatUsage()
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

    protected function formatTasksAndDescriptions()
    {
        $tasks = $this->application->tasks->getArrayCopy();
        ksort($tasks);

        $text = '';
        $text .= "(in {$this->application->projectDirectory})\n";

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

    protected function initProject()
    {
        $cwd = $_SERVER['PWD'];

        if (file_exists("$cwd/{$this->configFile}")) {
            $this->logger()->err('Project already has a bob_config.php');
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

        $this->logger()->info(sprintf('Initialized project at "%s"', $cwd));
    }
}

