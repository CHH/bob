<?php

namespace Bob;

use SplStack;
use SplObjectStorage;

class TaskInvocationChain implements \IteratorAggregate
{
    protected $objectMap, $stack;

    function __construct()
    {
        $this->objectMap = new SplObjectStorage;
        $this->stack = new SplStack;
    }

    function push($task)
    {
        $this->stack->push($task);
        $this->objectMap->attach($task);
    }

    function pop()
    {
        $task = $this->stack->pop();
        $this->objectMap->detach($task);

        return $task;
    }

    function has($task)
    {
        return $this->objectMap->contains($task);
    }

    function getIterator()
    {
        return $this->stack;
    }
}
