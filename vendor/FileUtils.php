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

    // Public: Checks if the dest file is not older than the
    // source file.
    //
    // src
    // dest
    //
    // Returns TRUE when the destination is up to date, FALSE otherwise.
    static function upToDate($src, $dest)
    {
        if (!file_exists($dest)) {
            return false;
        }

        if (filemtime($dest) >= filemtime($src)) {
            return true;
        }
        return false;
    }

    static function relativize($path, $basePath = null)
    {
        if (null === $basePath) {
            $basePath = $_SERVER['PWD'];
        }

        $path = realpath($path);
        $basePath = realpath($basePath);

        if (false === $path) {
            throw new \InvalidArgumentException('Path does not exist.');
        }

        if (false === $basePath) {
            throw new \InvalidArgumentException('Base path does not exist.');
        }

        if (false === ($pos = strpos($path, $basePath))) {
            return $path;
        }

        return substr($path, strlen($basePath) + 1);
    }

    // Public: Sets the Current Working Directory to the path
    // given with `dir` and changes it back to the previous working
    // directory after running the callback.
    //
    // dir      - This directory becomes the CWD.
    // callback - The callback which should be run inside the CWD.
    // argv     - Additional arguments which should get passed to the
    //            callback.
    //
    // Returns the return value of the supplied callback.
    static function chdir($dir, $callback = null, $argv = array())
    {
        if (!is_dir($dir)) {
            throw new \InvalidArgumentException("$dir does not exist.");
        }

        $cwd = getcwd();
        chdir($dir);

        if (null === $callback) {
            return;
        }

        $returnValue = call_user_func_array($callback, $argv);

        chdir($cwd);
        return $returnValue;
    }

    static function cd($dir, $callback = null, $argv = array()) 
    {
        return static::chdir($dir, $callback, $argv);
    }
}
