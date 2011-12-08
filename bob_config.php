<?php
/*
 * Put the bob_config.php into the "Bob" namespace,
 * otherwise you would've to call the `task` and
 * `desc` functions with a `Bob\` prefix.
 */
namespace Bob;

desc('Creates a self-contained "bob" executable');
task('executable', function() {
    $script = <<<EOF
#!/usr/bin/env php
%s
EOF;

    $bobSrc = file_get_contents(__DIR__.'/bin/bob.php');

    printLn("Writing executable to bin/bob");

    @file_put_contents(__DIR__.'/bin/bob', sprintf($script, $bobSrc));
    chmod(__DIR__.'/bin/bob', 0755);
});

desc('Installs the bob executable in $PREFIX/bin, $PREFIX is an 
Environment Variable and defaults to /usr/local');
task('install', function($app) {
    $app->execute('executable');

    $prefix = isset($_SERVER['PREFIX']) ? $_SERVER['PREFIX'] : '/usr/local';

    printLn(sprintf('Installing the "bob" executable in %s', $prefix));

    if (!is_dir("$prefix/bin")) {
        mkdir("$prefix/bin");
    }

    copy(__DIR__.'/bin/bob', "$prefix/bin/bob");
    chmod("$prefix/bin/bob", 0755);
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
desc('Says "Hello World <name>!"
      Usage: greet <name>');
task('greet', function($app) {
    if (count($app->argv) < 1) {
        echo "greet expects at least one name as arguments\n";
        return 1;
    }

    $name = $app->argv[0];

    echo "Hello World $name!\n";
});

