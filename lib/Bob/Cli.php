<?php

namespace Bob;

use CHH\Optparse;

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
            ->addFlag('version')
            ->addFlag('help', array("alias" => '-h'))
            ->addFlag('tasks', array("alias" => '-t'))
            ->addFlag('chdir', array("alias" => '-C', "has_value" => true))
            ->addFlag('verbose', array("alias" => '-v'))
            ->addFlagVar('all', $this->showAllTasks, array("alias" => '-A'))
            ->addFlagVar('trace', $this->trace, array("alias" => '-T'))
            ->addFlagVar('force', $this->forceRun, array("alias" => '-f'))
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

        $this->application['log.verbose'] = (bool) $this->opts['verbose'];

        $this->application->trace = $this->trace;
        $this->application->forceRun = $this->forceRun;

        if ($this->opts['version']) {
            printf("Bob %s\n", \Bob::VERSION);
            return 0;
        }

        if ($this->opts["init"]) {
            return $this->initProject() ? 0 : 1;
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

        $this->application['config.load_path'] = array_merge($this->application['config.load_path'], explode(':', @$_SERVER['BOB_CONFIG_PATH']));

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
        return $this->application['log'];
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
        $tasks = $this->application['tasks']->getArrayCopy();
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
        if (file_exists("bob_config.php")) {
            fwrite(STDERR, "Project already has a bob_config.php\n");
            return false;
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

        @file_put_contents("bob_config.php", $config);

        printf("Initialized project at \"%s\"\n", getcwd());
        return true;
    }
}

