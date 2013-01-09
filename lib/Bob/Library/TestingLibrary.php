<?php

namespace Bob\Library;

use Bob\Application;
use Bob\TaskLibraryInterface;
use Bob\BuildConfig as b;

class TestingLibrary implements TaskLibraryInterface
{
    function register(Application $app)
    {
    }

    function boot(Application $app)
    {
        if (!isset($app['testing.dist_config'])) {
            $app['testing.dist_config'] = "phpunit.dist.xml";
        }

        if (!isset($app['testing.phpunit_bin'])) {
            $app['testing.phpunit_bin'] = "./vendor/bin/phpunit";
        }

        $app->fileTask('phpunit.xml', array($app['testing.dist_config']), function($task) {
            copy($task->prerequisites->current(), $task->name);
        });

        $app->task('test', array('phpunit.xml'), function() use ($app) {
            b\sh($app['testing.phpunit_bin'], array('fail_on_error' => true));

        })->description = "Run test suite";
    }
}

