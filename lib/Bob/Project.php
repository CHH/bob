<?php

namespace Bob;

// Public: Creates and stores the project instance.
function Project()
{
    static $project;

    if (null === $project) {
        $project = new Project;
    }
    return $project;
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
