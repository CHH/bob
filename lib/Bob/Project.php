<?php

namespace Bob;

class Project implements \ArrayAccess
{
    protected $tasks = array();

    function register($task)
    {
        $task->project = $this;
        $this->tasks[$task->name] = $task;
    }

    function taskExists($task)
    {
        return array_key_exists($task, $this->tasks);
    }

    function offsetGet($name)
    {
        if ($this->taskExists($name)) {
            return $this->tasks[$name];
        }
    }

    function offsetExists($offset) 
    {
        return $this->taskExists($offset);
    }

    function getTasks()
    {
        return $this->tasks;
    }

    function offsetSet($offset, $value) {}
    function offsetUnset($offset) {}
}
