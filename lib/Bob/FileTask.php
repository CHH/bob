<?php

namespace Bob;

use itertools;

# Internal: Represents a file task.
class FileTask extends Task
{
    # Public: Checks if the target exists or one of the prerequisites is newer
    # than the target file.
    #
    # Returns TRUE if the task must be run, or FALSE otherwise.
    function isNeeded()
    {
        return !file_exists($this->name)
               or $this->getTimestamp() > @filemtime($this->name);
    }

    # Internal: Returns the timestamp when the prerequisites were last modified.
    #
    # Returns the time as Unix Epoche represented by an Integer.
    function getTimestamp()
    {
        $lastModifiedTimes = iterator_to_array(itertools\filter(itertools\map(
            function($file) {
                if (file_exists($file)) {
                    return @filemtime($file);
                }
            },
            $this->prerequisites
        )));

        if ($lastModifiedTimes) {
            return max($lastModifiedTimes);
        }

        return 0;
    }
}
