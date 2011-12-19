<?php

namespace FileUtils;

function uptodate($src, $dest)
{
    if (!file_exists($dest)) {
        return false;
    }

    if (filemtime($dest) >= filemtime($src)) {
        return true;
    }
    return false;
}
