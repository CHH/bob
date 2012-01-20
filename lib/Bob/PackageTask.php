<?php

namespace Bob;

use Phar,
    PharData,
    ArrayIterator;

// Public: Provides tasks to make a package of your project. 
//
// Defines `package` and `repackage` tasks. These tasks create a 
// `$archiveName-$version.tar` and `.gz` from the files passed as
// prerequisites and rebuilds the package only if one of the prerequisites
// changed.
//
// Examples
//
//   $packageTask = new PackageTask(
//      'dist/myproject', trim(`git log -n 1 --format=%H`), glob('*')
//   );
//
//   # define() defines all tasks on the application instance.
//   $packageTask->define();
class PackageTask
{
    protected $file;
    protected $version;
    protected $prerequisites;

    // Constructor
    //
    // archiveName   - The name of the archive, without version or extension.
    // version       - Gets added to the `archiveName` after a dash.
    // prerequisites - Files which should be put into the archive.
    function __construct($archiveName, $version = null, $prerequisites = array())
    {
        $this->file = $archiveName;
        $this->version = $version;
        $this->prerequisites = $prerequisites;
    }

    // Public: Defines the task library's tasks.
    //
    // Returns nothing.
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
            Task::get('package')->invoke();
        });

        // TODO: automatically exclude target files from prerequisites
        fileTask($file, $this->prerequisites, function($task) {
            file_exists($task->name)       and unlink($task->name);
            file_exists($task->name.'.gz') and unlink($task->name);

            if (!is_dir(dirname($task->name))) {
                mkdir(dirname($task->name), true);
            }

            $archive = new PharData($task->name);
            $files   = new ArrayIterator($task->prerequisites);

            $archive->buildFromIterator($files, getcwd());
            $archive->compress(Phar::GZ);
        });
    }
}
