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

// Public: Holds the current application instance.
class Bob
{
    // Public: Instance of \Bob\Application
    static $application;
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
//   # Print something to STDERR (uses fwrite)
//   println('Error', STDERR);
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
//
// Examples
//
//   # template.phtml
//   Hello <?= $name ? >
//
//   $t = template('template.phtml');
//   echo $t(array('name' => 'Christoph'));
//   # => Hello Christoph
//
// Returns an anonymous function of the variables, which returns
// the rendered String.
function template($file)
{
    if (!file_exists($file)) {
        throw \InvalidArgumentException(sprintf(
            'File %s does not exist.', $file
        ));
    }

    $__file = $file;

    $template = function($__vars) use ($__file) {
        extract($__vars);
        unset($__vars, $var, $value);

        ob_start();
        include($__file);
        return ob_get_clean();
    };

    return $template;
}

// Public: Runs a system command
//
// cmd      - Command with arguments as String or List. Lists get joined by a single space.
// callback - A callback which receives the success as Boolean
//            and the Process instance as second argument (optional).
//
// Examples
//
//   # Triggers the default behaviour, the command's output is
//   # displayed on STDOUT and the build fails when the exit code
//   # was greater than zero.
//   sh('ls -l');
//
//   # When a callback is passed as second argument, then the callback
//   # receives the success status ($ok) as Boolean and a process instance
//   # as second argument. The default behaviour is prevented too.
//   sh('ls -A', function($ok, $process) {
//       $ok or fwrite($process->getErrorOutput(), STDERR);
//   });
//
// Returns nothing.
function sh($cmd, $callback = null)
{
    $cmd = join(' ', (array) $cmd);

    if (!is_callable($callback)) {
        $showCmd = sprintf(
            "bob: sh(%s)", strlen($cmd) > 42 ? substr($cmd, 0, 42).'...' : $cmd
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

// Public: Run a PHP Process with the given arguments.
//
// argv     - The argv either as Array or String. Arrays get joined by a single space.
// callback - See sh()
//
// Examples
//
//   # Runs a PHP dev server on `localhost:4000` with the document root
//   # `public/` and the router script `public/index.php`.
//   php(array('-S', 'localhost:4000', '-t', 'public/', 'public/index.php'));
//
// Returns nothing.
function php($argv, $callback = null)
{
    $execFinder = new \Symfony\Component\Process\PhpExecutableFinder;
    $php = $execFinder->find();

    return sh(array($php, join(' ', (array) $argv)), $callback);
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

