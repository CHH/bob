<?php

// Public: Contains Utility Functions.
namespace Bob;

require __DIR__.'/../vendor/.composer/autoload.php';
require __DIR__.'/../vendor/ulrichsg/getopt-php/src/Getopt.php';

require __DIR__.'/Bob/TaskRegistry.php';
require __DIR__.'/Bob/Task.php';
require __DIR__.'/Bob/FileTask.php';
require __DIR__.'/Bob/PackageTask.php';
require __DIR__.'/Bob/ConfigFile.php';
require __DIR__.'/Bob/Dsl.php';
require __DIR__.'/Bob/Application.php';

use Symfony\Component\Process\Process;

class Exception extends \Exception
{}

function fail($msg)
{
    throw new Exception($msg);
}

// Public: Appends an End-Of-Line character to the given
// text and writes it to a stream.
//
// line   - Text to write.
// stream - Resource to write the text to (optional). By
//          default the text is printed to STDOUT via `echo`
//
// Examples
//
//     # Print something to STDERR (uses fwrite)
//     println('Error', STDERR);
//
// Returns Nothing.
function println($line, $stream = null)
{
    $line = $line.PHP_EOL;

    if (is_resource($stream)) {
        fwrite($stream, $line);
    } else {
        echo "$line";
    }
}

// Public: Renders a PHP template.
//
// file - Template file, this must be a valid PHP file.
// vars - The local variables which should be available
//        within the template script.
//
// Returns the rendered template as String.
function template($file, $vars = array())
{
    if (!file_exists($file)) {
        throw \InvalidArgumentException(sprintf(
            'File %s does not exist.', $file
        ));
    }

    $template = function($__file, $__vars) {
        extract($__vars);
        unset($__vars, $var, $value);

        ob_start();
        include($__file);
        return ob_get_clean();
    };

    return $template($file, $vars);
}

// Public: Runs a command in a new process.
//
// cmd      - Command with arguments as String.
// callback - A callback which receives the success as Boolean
//            and the Process instance as second argument.
//
// Examples
//
//   proc('ls -A', function($ok, $process) {
//       $ok or fwrite($process->getErrorOutput(), STDERR);
//   });
//
// Returns the Output as String.
function proc($cmd, $callback = null)
{
    $cmd = join(' ', (array) $cmd);

    if (!is_callable($callback)) {
        $showCmd = sprintf(
            "bob: proc(%s)", strlen($cmd) > 42 ? substr($cmd, 0, 42).'...' : $cmd
        );

        $callback = function($ok, $process) use ($showCmd) {
            $ok or fail("Command failed with status ({$process->getExitCode()}) [$showCmd]");

            println($showCmd, STDERR);
            echo $process->getOutput();
        };
    }

    $process = new Process($cmd);
    $process->run();

    call_user_func($callback, $process->isSuccessful(), $process);
}

// Public: Run something on the shell.
function sh($script, $callback = null)
{
    $script = join(' ', (array) $script);

    if (!is_callable($callback)) {
        $showCmd = sprintf(
            "bob: sh(%s)", strlen($script) > 42 ? substr($script, 0, 42).'...' : $script
        );

        $callback = function($ok, $process) use ($showCmd) {
            $ok or fail("Command failed with status ({$process->getExitCode()}) [$showCmd]");

            println($showCmd, STDERR);
            echo $process->getOutput();
        };
    }

    $process = new Process('/bin/sh');
    $process->setStdin($script);
    $process->run();

    call_user_func($callback, $process->isSuccessful(), $process);
}

// Public: Run a PHP Process.
function php($argv, $callback = null)
{
    $argv = join(' ', (array) $argv);

    $execFinder = new \Symfony\Component\Process\PhpExecutableFinder;
    $php = $execFinder->find();

    return proc("$php $argv", $callback);
}

// Public: Takes a list of expressions and joins them to
// a list of paths.
//
// patterns - List of shell file patterns.
//
// Returns a list of paths.
function fileList($patterns)
{
    $patterns = (array) $patterns;
    $finder = new \Symfony\Component\Finder\Finder;
    $finder->files();

    foreach ($patterns as $p) {
        $finder->name($p);
    }

    return $finder;
}

