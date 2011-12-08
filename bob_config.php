<?php
namespace Bobfile;

desc('Creates a self-contained "bob" executable');
task('executable', function() {
    $script = <<<EOF
#!/usr/bin/env php
%s
EOF;

    $bobSrc = file_get_contents(__DIR__.'/bin/bob.php');

    @file_put_contents(__DIR__.'/bin/bob', sprintf($script, $bobSrc));
    chmod(__DIR__.'/bin/bob', 0755);
});

task('install', function() {
    $prefix = isset($_ENV['PREFIX']) ? $_ENV['PREFIX'] : '/usr/local';
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
desc('Says "Hello World <name>!"');
task('greet', function($app) {
    if (count($app->argv) < 1) {
        echo "greet expects at least one name as arguments\n";
        return 1;
    }

    $name = $app->argv[0];

    echo "Hello World $name!\n";
});

task('bar', function() {

});

