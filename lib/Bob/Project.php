<?php

namespace Bob;

class Project
{
    // Public: Tasks to run in this project.
    public $tasks = array();

    function run($context = null)
    {
        $status = 0;
        foreach ($this->tasks as $task) {
            $status = $task($context);
        }

        return $status;
    }
}
