<?php

namespace Bob\Test;

use Monolog\Logger;
use Monolog\Handler\TestHandler;

class ApplicationTest extends \PHPUnit_Framework_TestCase
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

        \Bob::$application = $this->application;
    }

    function testTaskShort()
    {
        $action = function() {
            return "bar";
        };

        $task = $this->application->task("foo", $action);

        $this->assertTrue($this->application->taskDefined("foo"));
        $this->assertInstanceOf("\\Bob\\Task", $task);

        $this->assertEquals("foo", $task->name);
        $this->assertNull($task->prerequisites);
        $this->assertEquals($action, $task->actions[0]);
    }

    function testTaskLong()
    {
        $action = function() {};
        $deps = new \ArrayIterator(array('bar'));

        $task = $this->application->task('foo', $deps, $action);

        $this->assertTrue($this->application->taskDefined('foo'));

        $this->assertEquals($deps, $task->prerequisites);
        $this->assertEquals($action, $task->actions[0]);
    }

    function testTaskReturnsExistingTaskWhenAlreadyDefined()
    {
        $task = $this->application->task('foo', function() {});

        $this->assertEquals($task, $this->application->task('foo'));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    function testExecuteThrowsExceptionWhenTaskNotFound()
    {
        $this->application->execute('foo');
    }

    function testExecute()
    {
        $invoked = 0;

        $this->application->task('foo', array('bar'), function() use (&$invoked) {
            $invoked++;
        });

        $this->application->task('bar', function() use (&$invoked) {
            $invoked++;
        });

        $this->application->execute('foo');

        $this->assertEquals(2, $invoked);
    }

    function testExecuteSetsWorkingDirectoryToProjectDirectory()
    {
        $cwd = "";

        $this->application->task('foo', function() use (&$cwd) {
            $cwd = getcwd();
        });

        $this->application->projectDirectory = __DIR__;

        $this->application->execute('foo');

        $this->assertEquals(__DIR__, $cwd);
    }

    function testLoadDefaultRootConfig()
    {
        $cwd = getcwd();
        chdir(__DIR__ . '/fixtures');

        $this->application->init();

        $this->assertTrue($this->application->taskDefined('baz'));
        $this->assertContains(__DIR__ . '/fixtures/bob_config.php', $this->application->loadedConfigs);

        chdir($cwd);
    }

    function testLoadCustomRootConfig()
    {
        $cwd = getcwd();
        chdir(__DIR__ . '/fixtures');

        $this->application['config.file'] = "Bobfile.php";

        $this->application->init();

        $this->assertTrue($this->application->taskDefined('foobar'));
        $this->assertContains(__DIR__ . '/fixtures/Bobfile.php', $this->application->loadedConfigs);

        chdir($cwd);
    }

    function testMultipleRootConfigNamesChoosesFirstFound()
    {
        $cwd = getcwd();
        chdir(__DIR__ . '/fixtures');

        $this->application['config.file'] = array("Bobfile.php", "bob_config.php");

        $this->application->init();

        $this->assertTrue($this->application->taskDefined('foobar'));
        $this->assertContains(__DIR__ . '/fixtures/Bobfile.php', $this->application->loadedConfigs);

        chdir($cwd);
    }

    function testLoadConfigsFromLoadPath()
    {
        $cwd = getcwd();
        chdir(__DIR__ . '/fixtures');

        $this->application['config.load_path'] = array(__DIR__ . '/fixtures/tasks');
        $this->application->init();

        $this->assertTrue($this->application->taskDefined('foo'));
        $this->assertTrue($this->application->taskDefined('bar'));

        $this->assertContains(__DIR__ . '/fixtures/tasks/foo.php', $this->application->loadedConfigs);
        $this->assertContains(__DIR__ . '/fixtures/tasks/bar.php', $this->application->loadedConfigs);

        chdir($cwd);
    }
}

