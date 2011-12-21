<?php

class FileUtils
{
    static function isAbsolute($path)
    {
        if (realpath($path) === $path) {
            return true;
        }
        return false;
    }

    static function uptodate($src, $dest)
    {
        if (!file_exists($dest)) {
            return false;
        }

        if (filemtime($dest) >= filemtime($src)) {
            return true;
        }
        return false;
    }
}
