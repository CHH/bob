<?php

namespace Bob;

// Internal: A registry for tasks
class TaskRegistry extends \ArrayObject
{
    // Public: Registers the task object. Task objects must at least
    // have a "name" property and an "invoke" method.
    function register($task)
    {
        $task->tasks = $this;
        $this[$task->name] = $task;
    }

    function offsetGet($name)
    {
        if (isset($this[$name])) {
            return parent::offsetGet($name);
        }
    }
}
