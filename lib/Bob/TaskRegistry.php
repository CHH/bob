<?php

namespace Bob;

# Internal: A registry for tasks
class TaskRegistry extends \ArrayObject
{
    # Internal: Stores the description for the next created task.
    static $lastDescription = '';

    function offsetGet($name)
    {
        if (isset($this[$name])) {
            return parent::offsetGet($name);
        }
    }

    function offsetSet($offset, $task)
    {
        parent::offsetSet($task->name, $task);
    }
}

