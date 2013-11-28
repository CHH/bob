<?php

namespace Bob\Library;

use Bob\Application;
use Bob\TaskLibraryInterface;
use Bob\BuildConfig as b;

class ComposerLibrary implements TaskLibraryInterface
{
    function register(Application $app)
    {
    }

    function boot(Application $app)
    {
        $app->task('composer.phar', function($task) {
            if (file_exists($task->name)) {
                return true;
            }

            $src = fopen('http://getcomposer.org/composer.phar', 'rb');
            $dest = fopen($task->name, 'wb');

            stream_copy_to_stream($src, $dest);
        });

        $app->task('composer:install', array('composer.phar'), function() {
            b\php(array('composer.phar', 'install'), null, array('failOnError' => true));
        });

        $app->task('composer:update', array('composer.phar'), function() {
            b\php(array('composer.phar', 'update'), null, array('failOnError' => true));
        });

        $app->fileTask('composer.lock', array('composer.phar', 'composer.json'), function($task) use ($app) {
            $app->task('composer:update')->invoke();
        });
    }
}
