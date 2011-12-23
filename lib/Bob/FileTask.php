<?php

namespace Bob;

function fileTask($target, $prerequisites = array(), $callback)
{
    if ($prerequisites instanceof \Traversable) {
        $prerequisites = iterator_to_array($prerequisites);
    }

    $task = new FileTask($target, $callback);
    $task->prerequisites = $prerequisites;

    Bob::$application->tasks[$target] = $task;
}

class FileTask extends Task
{
    function invoke()
    {
        if ($this->isNeeded()) {
            parent::invoke();
        }
    }

    function isNeeded()
    {
        if (!file_exists($this->name) or $this->getTimestamp() > filemtime($this->name)) {
            return true;
        }
        return false;
    }

    function getTimestamp()
    {
        $lastModified = max(
            array_map(
                function($file) {
                    return filemtime($file);
                },
                $this->prerequisites
            )
        );

        return $lastModified;
    }
}
