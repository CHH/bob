<?php
/*
 * Put the `bob_config.php` into the "Bob" namespace,
 * otherwise you would've to call the `task` and
 * `desc` functions with a `Bob\` prefix.
 */
namespace Bob;

use FileUtils;

$pharFiles = fileList('*.php')->in(array('lib', 'bin', 'vendor'));

$packageFiles = fileList('*')->in(__DIR__);
$packageTask = new PackageTask(
    '/tmp/bob', trim(`git log -n 1 --format=%H`), $packageFiles
);
$packageTask->define();

// The "default" task is invoked when there's no
// task explicitly given on the command line.
task('default', array('phar'));

// Note: All file paths used here should be relative to the project
// directory. Bob automatically sets the current working directory
// to the path where the `bob_config.php` resides.

desc('Makes a distributable version of Bob, consisting of a composer.json
      and a PHAR file.');
task('phar', array('composer.lock', 'bin/bob.phar'));

task('clean', function() {
    file_exists('bin/bob.phar') and unlink('bin/bob.phar');
    file_exists('bin/bob')      and unlink('bin/bob');
});

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

    $projectDir = Bob::$application->projectDir;

    $phar = new \Phar($task->name, 0, basename($task->name));
    $phar->startBuffering();

    foreach ($task->prerequisites as $file) {
        $file = (string) $file;
        $phar->addFile($file, FileUtils::relativize($file, $projectDir));
    }

    $phar->setStub($stub);
    $phar->stopBuffering();

    chmod($task->name, 0555);

    println(sprintf(
        'Regenerated Archive "%s" with %d entries', basename($task->name), count($phar)
    ));
    unset($phar);
});

desc("Runs Bob's test suite");
task("test", array('phpunit.xml'), function($task) {
    proc('phpunit');
});

fileTask('phpunit.xml', array('phpunit.xml.dist'), function() {
    copy('phpunit.xml.dist', 'phpunit.xml');
});

fileTask('composer.lock', array('composer.json'), function() {
    proc('composer update');
});

desc('Takes an environment variable PREFIX and writes a `bob` executable
      to $PREFIX/bin/bob. PREFIX defaults to "/usr/local".');
task('install', array('bin/bob.phar'), function($task) {
    $prefix = getenv('PREFIX') ?: '/usr/local';

    $success = copy('bin/bob.phar', "$prefix/bin/bob");
    chmod("$prefix/bin/bob", 0755);

    println(sprintf('Installed the `bob` executable in %s.', $prefix));
});

desc('Removes the `bob` excutable from the PREFIX');
task('uninstall', function($task) {
    $prefix = getenv("PREFIX") ?: "/usr/local";

    if (!file_exists("$prefix/bin/bob")) {
        println("Seems that bob is not installed. Aborting.", STDERR);
        return 1;
    }

    unlink("$prefix/bin/bob") and println("Erased bob successfully from $prefix");
});

task('failing', function() {
    return false;
});

