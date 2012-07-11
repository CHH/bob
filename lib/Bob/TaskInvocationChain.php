<?php

namespace Bob;

use SplStack,
    SplObjectStorage;

class TaskInvocationChain implements \IteratorAggregate
{
    protected $objects, $stack;

    function __construct()
    {
        $this->objects = new SplObjectStorage;
        $this->stack = new SplStack;
    }

    function push($task)
    {
        $this->stack->push($task);
        $this->objects->attach($task);
    }

    function pop()
    {
        $task = $this->stack->pop();
        $this->objects->detach($task);

        return $task;
    }

    function has($task)
    {
        return $this->objects->contains($task);
    }

    function getIterator()
    {
        return $this->stack;
    }
}
