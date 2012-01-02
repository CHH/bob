<?php

namespace Bob\Test;

use Bob\TaskRegistry;

class TaskRegistryTest extends \PHPUnit_Framework_TestCase
{
    var $registry;

    function setUp()
    {
        $this->registry = new TaskRegistry;
    }

    function testOffsetGetReturnsNullWhenNoTask()
    {
        $this->assertNull($this->registry['foo']);
    }

    function testRegisterTreatsNamePropertyAsKey()
    {
        $task = (object) array(
            'name' => 'foo'
        );

        $this->registry->register($task);

        $this->assertEquals($task, $this->registry['foo']);
    }
}
