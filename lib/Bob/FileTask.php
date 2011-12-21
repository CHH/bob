<?php

namespace Bob;

class FileTask extends Task
{
    function __invoke($context = null)
    {
        if (!$this->isNeeded()) return;
        parent::__invoke($context);
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
