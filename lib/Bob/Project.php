<?php

namespace Bob;

// Public: Creates and stores the project instance.
function ProjectHolder(Project $project = null)
{
    static $instance;

    if (null !== $project) {
        $instance = $project;
    }
    return $instance;
}

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
