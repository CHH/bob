<?php

namespace Bob;

class ConfigFile
{
    // Internal: Looks up the provided definition file
    // in the directory tree, starting by the provided
    // directory walks the tree up until it reaches the
    // filesystem boundary.
    //
    // filename - File name to look up
    // cwd      - Starting point for traversing up the
    //            directory tree.
    //
    // Returns the absolute path to the file as String or
    // False if the file was not found.
    static function findConfigFile($filename, $cwd)
    {
        if (!is_dir($cwd)) {
            throw new \InvalidArgumentException(sprintf(
                '%s is not a directory', $cwd
            ));
        }

        // Look for the definition Name in the $cwd
        // until one is found.
        while (!$rp = realpath("$cwd/$filename")) {
            // Go up the hierarchy
            $cwd .= '/..';

            // We are at the filesystem boundary if there's
            // nothing to go up.
            if (realpath($cwd) === false) {
                break;
            }
        }

        return $rp;
    }
}
