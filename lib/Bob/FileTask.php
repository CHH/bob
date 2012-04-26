<?php

namespace Bob;

# Internal: Represents a file task.
class FileTask extends Task
{
    static function defineTask($name, $prerequisites = null, $action = null)
    {
        return parent::defineTask($name, $prerequisites, $action);
    }

    # Public: Checks if the target exists or one of the prerequisites is newer
    # than the target file.
    #
    # Returns TRUE if the task must be run, or FALSE otherwise.
    function isNeeded()
    {
        return !file_exists($this->name)
               or $this->getTimestamp() > filemtime($this->name);
    }

    # Internal: Returns the timestamp when the prerequisites were last modified.
    #
    # Returns the time as Unix Epoche represented by an Integer.
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
