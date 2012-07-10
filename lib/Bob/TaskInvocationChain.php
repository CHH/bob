<?php

namespace Bob;

class TaskInvocationChain extends \SplStack
{
    function has($taskName)
    {
        foreach ($this as $task) {
            if ($task->name === $taskName) {
                return true;
            }
        }
    }
}
