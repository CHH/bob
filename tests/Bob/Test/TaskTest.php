<?php

namespace Bob\Test;

use Bob\Task, Bob\Application;

class TaskTest extends \PHPUnit_Framework_TestCase
{
    function testInvokeRunsActionsOnlyOnce()
    {
        $invoked = 0;

        $t = new Task('foo', new Application);
        $t->actions[] = function() use (&$invoked) {
            $invoked++;
        };

        $t->invoke();
        $t->invoke();

        $this->assertEquals(1, $invoked);

        $t->reenable();
        $t->invoke();
        $this->assertEquals(2, $invoked);
    }

    function isNeededReturnsTrue()
    {
        $t = new Task('foo', new Application);
        $this->assertEquals(true, $t->isNeeded());
    }
}
