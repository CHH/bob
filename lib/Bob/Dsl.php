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
function task($name, $prerequisites = null, $callback = null)
{
    return Task::defineTask($name, $prerequisites, $callback);
}

// Public: Config file function for creating a task which is only run
// when the target file does not exist, or the prerequisites were modified.
//
// target        - Filename of the resulting file, this is set as task name. Use
//                 paths relative to the CWD (the CWD is always set to the root
//                 of your project for you).
// prerequisites - List of files which are needed to generate the target. The callback
//                 which generates the target is only run when one of this files is newer
//                 than the target file. You can access this list from within the task via
//                 the task's `prerequisites` property.
// callback      - Place your logic needed to generate the target here. It's only run when
//                 the prerequisites were modified or the target does not exist.
//
// Returns nothing.
function fileTask($target, $prerequisites = array(), $callback)
{
    return FileTask::defineTask($target, $prerequisites, $callback);
}

// Public: Defines the description of the subsequent task.
//
// desc  - Description text, should explain in plain sentences
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
function desc($desc)
{
    TaskRegistry::$lastDescription = $desc;
}

