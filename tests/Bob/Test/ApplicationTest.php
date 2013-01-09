<?php

namespace Bob\Test;

class ApplicationTest extends \PHPUnit_Framework_TestCase
{
    protected $application;

    function setUp()
    {
        $this->application = new \Bob\Application;
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
}

