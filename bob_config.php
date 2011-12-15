<?php
/*
 * Put the `bob_config.php` into the "Bob" namespace,
 * otherwise you would've to call the `task` and
 * `desc` functions with a `Bob\` prefix.
 */
namespace Bob;

// You can pull in other tasks by simply requiring the file
require __DIR__.'/bob_composer_config.php';

desc('Creates a distributable, self-contained and executable PHAR file');
task('dist', function() {
    if (file_exists(__DIR__.'/bin/bob.phar')) {
        unlink(__DIR__.'/bin/bob.phar');
    }

    $stub = <<<'EOF'
#!/usr/bin/env php
<?php

Phar::mapPhar('bob.phar');

require 'phar://bob.phar/bin/bob.php';

__HALT_COMPILER();
EOF;

    $phar = new \Phar(__DIR__.'/bin/bob.phar', 0, 'bob.phar');
    $phar->startBuffering();
    $phar->buildFromDirectory(__DIR__, '/(bin\/|lib\/|vendor\/)(.*)\.php$/');
    $phar['LICENSE.txt'] = file_get_contents(__DIR__.'/LICENSE.txt');
    $phar->setStub($stub);
    $phar->stopBuffering();

    chmod(__DIR__.'/bin/bob.phar', 0555);

    printLn(sprintf('Generated Archive "bin/bob.phar" with %d entries', count($phar)));
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
task('greet', function($app) {
    if (count($app->argv) < 2) {
        echo "greet expects at least one name as arguments\n";
        return 1;
    }

    $name = $app->argv[1];

    echo "Hello World $name!\n";
});

