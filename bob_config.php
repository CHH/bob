<?php

# Put the `bob_config.php` into the "Bob\BuildConfig" namespace
# otherwise you would've to call the `task` and
# `desc` functions with a `Bob\BuildConfig` prefix.
namespace Bob\BuildConfig;

use CHH\FileUtils\Path;

$pharFiles = fileList('*.php')->in(array('lib', 'bin', 'vendor'));

register(new \Bob\Library\TestingLibrary, array(
    'testing.dist_config' => 'phpunit.xml.dist'
));

register(new \Bob\Library\ComposerLibrary);

# The "default" task is invoked when there's no
# task explicitly given on the command line.
task('default', array('phar'));

# Note: All file paths used here should be relative to the project
# directory. Bob automatically sets the current working directory
# to the path where the `bob_config.php` resides.

desc('Compiles a executable, standalone PHAR file');
task('phar', array('composer.lock', 'test', 'bin/bob.phar'));

task('clean', function() {
    file_exists('bin/bob.phar') and unlink('bin/bob.phar');
});

task('release', function($task) {
    if (!$version = @$_ENV['version'] ?: @$_SERVER['BOB_VERSION']) {
        failf('No version given');
    }

    if (substr($version, 0, 1) === 'v') {
        $version = substr($version, 1);
    }

    if (!@$_ENV['skip-branching']) {
        sh(sprintf('git checkout -b "release/%s"', $version));
    }

    info(sprintf('----> Setting version to "v%s"', $version));

    $contents = file_get_contents('lib/Bob.php');

    $contents = preg_replace(
        '/(\s*)(const VERSION = ".+")/',
        sprintf('\1const VERSION = "v%s"', $version),
        $contents
    );

    file_put_contents('lib/Bob.php', $contents);
});

fileTask('bin/bob.phar', $pharFiles, function($task) {
    if (file_exists($task->name)) {
        unlink($task->name);
    }

    $stub = <<<'EOF'
#!/usr/bin/env php
<?php

Phar::mapPhar('bob.phar');

require 'phar://bob.phar/bin/bootstrap.php';

__HALT_COMPILER();
EOF;

    $projectDir = \Bob::$application->projectDirectory;

    $phar = new \Phar($task->name, 0, basename($task->name));
    $phar->startBuffering();

    foreach ($task->prerequisites as $file) {
        $file = (string) $file;
        $phar->addFile($file, Path::relativize($file, $projectDir));
    }

    $phar->setStub($stub);
    $phar->stopBuffering();

    chmod($task->name, 0555);

    println(sprintf(
        'Regenerated Archive "%s" with %d entries', basename($task->name), count($phar)
    ));
    unset($phar);
});

desc('Does a system install of Bob, by default to /usr/local/bin');
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

task("foo", function() {});
