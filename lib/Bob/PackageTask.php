<?php

namespace Bob;

use Phar,
    PharData,
    ArrayIterator;

class PackageTask
{
    protected $file;
    protected $version;
    protected $prerequisites;

    function __construct($archiveName, $version = null, $prerequisites = array())
    {
        $this->file = $archiveName;
        $this->version = $version;
        $this->prerequisites = $prerequisites;
    }

    function define()
    {
        $file = $this->file;

        if ($this->version) {
            $file .= '-'.$this->version;
        }
        $file .= '.tar';

        desc('Creates a package');
        task('package', array($file));

        desc('Recreates the package, even if no file changed.');
        task('repackage', function() use ($file) {
            unlink($file);
            unlink($file.'.gz');
            Bob::$application->tasks['package']->invoke();
        });

        fileTask($file, $this->prerequisites, array($this, 'archiveTask'));
    }

    function archiveTask($task)
    {
        file_exists($task->name)       and unlink($task->name);
        file_exists($task->name.'.gz') and unlink($task->name);

        if (!is_dir(dirname($task->name))) {
            mkdir(dirname($task->name), true);
        }

        $archive = new PharData($task->name);
        $files   = new ArrayIterator($task->prerequisites);

        $archive->buildFromIterator($files, getcwd());
        $archive->compress(Phar::GZ);
    }
}
