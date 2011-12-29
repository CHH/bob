<?php
/*
 * Put the `bob_config.php` into the "Bob" namespace,
 * otherwise you would've to call the `task` and
 * `desc` functions with a `Bob\` prefix.
 */
namespace Bob;

// Construct a list of all files which should go into the bob.phar
$pharFiles = FileList(array(
    'LICENSE.txt',
    'bin/*.php',
    'lib/*.php',
    'lib/**/*.php',
    'vendor/FileUtils.php',
    'vendor/Getopt.php',
));

// Your package's name. Used to identify it on Packagist.org
function getName()
{
    return 'chh/bob';
}

// Should return the package's version.
//
// An idea would be to fetch the latest tag from the repo 
// in your favourite VCS.
function getVersion()
{
}

// Should return an array of authors.
// Each individual author should be an array of `name`, 
// `email` and optionally a `homepage`
//
// By default this parses an `AUTHORS.txt` file in the root
// of the project which is formatted with an author name on
// each line. For example:
//
//     Christoph Hochstrasser <christoph.hochstrasser@gmail.com>
//     John Doe <john@example.com>
//
function getAuthors()
{
    $authorsFile = 'AUTHORS.txt';

    if (!file_exists($authorsFile)) {
        return array();
    }

    $authors = array();

    foreach (new \SplFileObject($authorsFile) as $line) {
        if (preg_match('/^(.+) <(.+)>$/', $line, $matches)) {
            $authors[] = array(
                'name' => $matches[1],
                'email' => $matches[2]
            );
        }
    }

    return $authors;
}

function getExecutables()
{
    $binDir = 'bin';

    if (!is_dir($binDir)) {
        return array();
    }

    $executables = array();

    foreach (new \DirectoryIterator($binDir) as $file) {
        if ($file->isFile() and $file->isExecutable()) {
            $logicalPath = substr($file->getRealpath(), strlen($_SERVER['PWD']) + 1);
            $executables[] = $logicalPath;
        }
    }

    return $executables;
}

// Note: All file paths used here should be relative to the project
// directory. Bob automatically sets the current working directory
// to the path where the `bob_config.php` resides.

// The first defined task is the default task for the case
// Bob is executed without a task name.
desc('Makes a distributable version of Bob, consisting of a composer.json 
      and a PHAR file.');
task('dist', array('composer.json', 'bin/bob.phar'));

desc('Generates an executable PHP Archive (PHAR) from the project files.');
fileTask('bin/bob.phar', $pharFiles, function($task) {
    if (file_exists($task->name)) {
        unlink($task->name);
    }

    $stub = <<<'EOF'
#!/usr/bin/env php
<?php

Phar::mapPhar('bob.phar');

require 'phar://bob.phar/bin/bob.php';

__HALT_COMPILER();
EOF;

    $phar = new \Phar($task->name, 0, basename($task->name));
    $phar->startBuffering();

    foreach ($task->prerequisites as $file) {
        $phar->addFile($file, substr($file, (strpos(getcwd(), $file) === 0) ? (strlen(getcwd()) + 1) : 0));
    }

    $phar->setStub($stub);
    $phar->stopBuffering();

    chmod($task->name, 0555);

    println(sprintf('Regenerated Archive "%s" with %d entries', basename($task->name), count($phar)));
    unset($phar);
});

desc('Takes an environment variable PREFIX and writes a `bob` executable
      to $PREFIX/bin/bob. PREFIX defaults to "/usr/local".');
task('install', array('dist'), function($task) {
    $prefix = getenv('PREFIX') ?: '/usr/local';


    $success = copy('bin/bob.phar', "$prefix/bin/bob");
    chmod("$prefix/bin/bob", 0755);

    println(sprintf('Installed the `bob` executable in %s.', $prefix));
});

desc('Removes the `bob` excutable from the PREFIX');
task('uninstall', array('dist'), function($task) {
    $prefix = getenv("PREFIX") ?: "/usr/local";

    if (!file_exists("$prefix/bin/bob")) {
        println("Seems that bob is not installed. Aborting.", STDERR);
        return 1;
    }

    if (false !== unlink("$prefix/bin/bob")) {
        println("Erased bob successfully from $prefix");
    }
});

/*
 * Each Task consists of an optional `desc()` call
 * with a meaningful Task description, and a call to
 * `task()` with the task name and task body (usually
 * as Closure).
 *
 * **Important:** The call to `desc()` must be _before_
 * the call to `task()`.
 *
 * The only argument a task receives is the task instance.
 * Via the task instance you've access to the prerequisites
 * and name of the task which is very useful for file tasks.
 */
desc('Example: Says "Hello World NAME!"', 'greet NAME');
task('greet', array('foo'), function($task) {
    echo "Hello World! I'm the {$task->name} Task!\n";

    echo "I've following prerequisites:\n";
    echo " - ", join("\n - ", $task->prerequisites), "\n";
});

task('foo', array('bar'), function() {
    echo "This is the Foo Task\n";
});

task('bar', function() {
    echo "This is the Bar Task\n";
});

desc('Generates the composer.json from the composer_spec.php');
task('composer.json', array('composer_spec.php'), function($task) {
    $NAME = getName();
    $AUTHORS = getAuthors();
    $EXECUTABLES = getExecutables();
    $VERSION = getVersion();

    $pkg = include($task->prerequisites[0]);

    if (!is_array($pkg)) {
        println('Error: composer_spec.php MUST return an array');
        exit(1);
    }

    $jsonOptions = 0;

    if (defined('JSON_PRETTY_PRINT')) {
        $jsonOptions |= JSON_PRETTY_PRINT;
    }

    if (defined("JSON_UNESCAPED_SLASHES")) {
        $jsonOptions |= JSON_UNESCAPED_SLASHES;
    }

    $json = json_encode($pkg, $jsonOptions);

    println('Writing composer.json');
    @file_put_contents($task->name, $json);
});
