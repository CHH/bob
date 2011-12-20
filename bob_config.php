<?php
/*
 * Put the `bob_config.php` into the "Bob" namespace,
 * otherwise you would've to call the `task` and
 * `desc` functions with a `Bob\` prefix.
 */
namespace Bob;

// You can pull in other tasks by simply requiring the file
require __DIR__.'/bob_composer_config.php';

fileTask(__DIR__.'/bin/bob.phar', array(__DIR__), function($task) {
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
    $phar->buildFromDirectory($task->prerequisites[0], '/(bin\/|lib\/|vendor\/)(.*)\.php$/');
    $phar['LICENSE.txt'] = file_get_contents(__DIR__.'/LICENSE.txt');
    $phar->setStub($stub);
    $phar->stopBuffering();

    chmod($task->name, 0555);

    println(sprintf('Generated Archive "%s" with %d entries', basename($task->name), count($phar)));
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
task('greet', ['foo'], function($task) {
    if (count($task->context->argv) < 2) {
        echo "greet expects at least one name as arguments\n";
        return 1;
    }

    $name = $task->context->argv[1];

    echo "Hello World $name!\n";
});

task('foo', ['bar'], function() {
    echo "This is the Foo Task\n";
});

task('bar', function() {
    echo "This is the Bar Task\n";
});
