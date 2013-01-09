<?php

namespace Bob\Test;

use Monolog\Logger;
use Monolog\Handler\TestHandler;

class TaskLibraryTest extends \PHPUnit_Framework_TestCase
{
    protected $application;

    function setUp()
    {
        $this->application = new \Bob\Application;

        $this->application['log'] = $this->application->share(function() {
            $log = new Logger('bob');
            $log->pushHandler(new TestHandler);

            return $log;
        });

        $this->application['config.file'] = null;
    }

    function testRegister()
    {
        $lib = $this->getMock('\\Bob\\TaskLibraryInterface');

        $lib->expects($this->once())->method('register')
            ->with($this->equalTo($this->application));

        $this->application->register($lib);
    }

    function testBoot()
    {
        $lib = $this->getMock('\\Bob\\TaskLibraryInterface');

        $lib->expects($this->once())->method('register')
            ->with($this->equalTo($this->application));

        $lib->expects($this->once())->method('boot')
            ->with($this->equalTo($this->application));

        $this->application->register($lib);

        $this->application->init();
    }
}

