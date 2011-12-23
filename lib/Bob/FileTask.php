<?php

namespace Bob;

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
    if ($prerequisites instanceof \Traversable) {
        $prerequisites = iterator_to_array($prerequisites);
    }

    $task = new FileTask($target, $callback);
    $task->prerequisites = $prerequisites;

    Bob::$application->tasks[$target] = $task;
}

// Internal: Represents a file task.
class FileTask extends Task
{
    // Public: Run the task only when it's needed.
    //
    // Returns nothing.
    function invoke()
    {
        if ($this->isNeeded()) {
            parent::invoke();
        }
    }

    // Public: Checks if the target exists or one of the prerequisites is newer
    // than the target file.
    //
    // Returns TRUE if the task must be run, or FALSE otherwise.
    function isNeeded()
    {
        if (!file_exists($this->name) or $this->getTimestamp() > filemtime($this->name)) {
            return true;
        }
        return false;
    }

    // Internal: Returns the timestamp when the prerequisites were last modified.
    //
    // Returns the time as Unix Epoche represented by an Integer.
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
