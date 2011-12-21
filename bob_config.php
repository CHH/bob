<?php
/*
 * Put the `bob_config.php` into the "Bob" namespace,
 * otherwise you would've to call the `task` and
 * `desc` functions with a `Bob\` prefix.
 */
namespace Bob;

// Because task files are simple PHP files which call some
// functions, task libraries can simply be included by requiring
// or including them.
require __DIR__.'/boblib/composer.php';

$files = FileList(array(
    __DIR__.'/LICENSE.txt',
    __DIR__.'/bin/*.php',
    __DIR__.'/lib/*.php',
    __DIR__.'/lib/**/*.php',
    __DIR__.'/vendor/FileUtils.php',
    __DIR__.'/vendor/Getopt.php',
));

fileTask('bin/bob.phar', $files, function($task) {
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
        $phar->addFile($file, substr($file, strlen(__DIR__) + 1));
    }

    $phar->setStub($stub);
    $phar->stopBuffering();

    chmod($task->name, 0555);

    println(sprintf('Regenerated Archive "%s" with %d entries', basename($task->name), count($phar)));
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
 * Each Task callback additionally receives the `$app`
 * as argument, which contains an `argv` property.
 * The `argv` property already contains only the arguments
 * for the task, the script name and task name are already
 * removed.
 */
desc('Says "Hello World NAME!"', 'greet NAME');
task('greet', array('foo'), function($task) {
    echo "Hello World!\n";
});

task('foo', array('bar'), function() {
    echo "This is the Foo Task\n";
});

task('bar', function() {
    echo "This is the Bar Task\n";
});
