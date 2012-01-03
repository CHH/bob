<?php

namespace Bob;

// Internal: Represents a file task.
class FileTask extends Task
{
    // Public: Run the task only when it's needed.
    //
    // Returns nothing.
    function invoke()
    {
        if ($this->application->trace and !$this->isNeeded()) {
            println("bob: skipping $this: Not needed.", STDERR);
        }

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
        return !file_exists($this->name)
               or $this->getTimestamp() > filemtime($this->name);
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
