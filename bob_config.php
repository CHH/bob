<?php
/*
 * Put the `bob_config.php` into the "Bob" namespace,
 * otherwise you would've to call the `task` and
 * `desc` functions with a `Bob\` prefix.
 */
namespace Bob;

desc('Creates a composer.json in the root of the project');
task('composer', function() {
    $authors = array();

    foreach (new \SplFileObject(__DIR__.'/AUTHORS.txt') as $line) {
        if (preg_match('/^(.+) <(.+)>$/', $line, $matches)) {
            $authors[] = array(
                'name' => $matches[1],
                'email' => $matches[2]
            );
        }
    }

    $json = template(__DIR__.'/composer.json.php', array(
        'authors' => json_encode($authors, JSON_UNESCAPED_SLASHES)
    ));

    @file_put_contents(__DIR__.'/composer.json', $json);
});

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
    $phar->buildFromDirectory(__DIR__, '/(bin\/|lib\/)(.*)\.php$/');
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
    if (count($app->argv) < 1) {
        echo "greet expects at least one name as arguments\n";
        return 1;
    }

    $name = $app->argv[0];

    echo "Hello World $name!\n";
});

