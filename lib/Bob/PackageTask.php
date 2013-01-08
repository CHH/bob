<?php

namespace Bob;

use Phar;
use PharData;
use ArrayIterator;

# Public: Provides tasks to make a package of your project. 
#
# Defines `package` and `repackage` tasks. These tasks create a 
# `$archiveName-$version.tar` and `.gz` from the files passed as
# prerequisites and rebuilds the package only if one of the prerequisites
# changed.
#
# Examples
#
#   $packageTask = new PackageTask(
#      'dist/myproject', trim(`git log -n 1 --format=%H`), glob('*')
#   );
#
#   # define() defines all tasks on the application instance.
#   $packageTask->register();
class PackageTask implements TaskLibraryInterface
{
    protected $file;
    protected $version;
    protected $prerequisites;

    # Constructor
    #
    # archiveName   - The name of the archive, without version or extension.
    # version       - Gets added to the `archiveName` after a dash.
    # prerequisites - Files which should be put into the archive.
    function __construct($archiveName, $version = null, $prerequisites = array())
    {
        $this->file = $archiveName;
        $this->version = $version;
        $this->prerequisites = $prerequisites;
    }

    function register(Application $app)
    {}

    # Public: Defines the task library's tasks.
    #
    # Returns nothing.
    function boot(Application $app)
    {
        $file = $this->file;

        if ($this->version) {
            $file .= '-'.$this->version;
        }
        $file .= '.tar';

        BuildConfig\desc('Creates a package');
        BuildConfig\task('package', array($file));

        BuildConfig\desc('Recreates the package, even if no file changed.');
        BuildConfig\task('repackage', function() use ($file) {
            unlink($file);
            unlink($file.'.gz');
            BuildConfig\task('package')->invoke();
        });

        # TODO: automatically exclude target files from prerequisites
        BuildConfig\fileTask($file, $this->prerequisites, function($task) {
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
