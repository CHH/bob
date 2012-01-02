<?php

namespace Bob;

// Public: Defines the callback as a task with the given name.
//
// name          - Task Name.
// prerequisites - List of Dependency names.
// callback      - The task's action, can be any callback.
//
// Examples
//
//     task('hello', function() {
//         echo "Hello World\n";
//     });
//
// Returns nothing.
function task($name, $prerequisites = array(), $callback = null)
{
    if (empty($name)) {
        throw new \InvalidArgumentException('Task Name cannot be empty');
    }

    if ($prerequisites instanceof Task) {
        $task = $prerequisites;
        $prerequisites = array();
    } else {
        $task = new Task($name, $callback, $prerequisites);
    }

    Bob::$application->project->register($task);
}

// Public: Defines the description of the subsequent task.
//
// text  - Description text, should explain in plain sentences
//         what the task does.
// usage - A usage message, must start with the task name and
//         should then be followed by the arguments.
//
// Examples
//
//     desc('Says Hello World to NAME', 'greet NAME');
//     task('greet', function($task) {
//         $operands = Bob::$application->opts->getOperands();
//         $name = $operands[1];
//
//         echo "Hello World $name!\n";
//     });
//
// Returns nothing.
function desc($desc, $usage = '')
{
    Task::$lastDescription = $desc;
    Task::$lastUsage = $usage;
}
