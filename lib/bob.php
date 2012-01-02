<?php

// Public: Contains Utility Functions.
namespace Bob;

require __DIR__.'/../vendor/FileUtils.php';
require __DIR__.'/../vendor/Getopt.php';

require __DIR__.'/Bob/Project.php';
require __DIR__.'/Bob/Task.php';
require __DIR__.'/Bob/FileTask.php';
require __DIR__.'/Bob/ConfigFile.php';
require __DIR__.'/Bob/Dsl.php';
require __DIR__.'/Bob/Application.php';

// Public: Appends an End-Of-Line character to the given
// text and writes it to a stream.
//
// line   - Text to write.
// stream - Resource to write the text to (optional). By
//          default the text is printed to STDOUT via `echo`
//
// Returns Nothing.
function println($line, $stream = null)
{
    $line = $line.PHP_EOL;

    if (is_resource($stream)) {
        fwrite($stream, $line);
    } else {
        echo "$line";
    }
}

// Public: Renders a PHP template.
//
// file - Template file, this must be a valid PHP file.
// vars - The local variables which should be available
//        within the template script.
//
// Returns the rendered template as String.
function template($file, $vars = array())
{
    if (!file_exists($file)) {
        throw \InvalidArgumentException(sprintf(
            'File %s does not exist.', $file
        ));
    }

    $template = function($__file, $__vars) {
        extract($__vars);
        unset($__vars, $var, $value);

        ob_start();
        include($__file);
        return ob_get_clean();
    };

    return $template($file, $vars);
}

// Public: Takes a list of expressions and joins them to
// a list of paths.
//
// patterns - List of shell file patterns.
//
// Returns a list of paths.
function fileList($patterns)
{
    $fileList = array();

    foreach ($patterns as $p) {
        $fileList = array_merge($fileList, glob($p));
    }

    return $fileList;
}

