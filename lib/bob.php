<?php

namespace Bob;

function printLn($line)
{
    echo "[bob] $line\n";
}

// Renders a PHP template
function template($file, $vars = array())
{
    if (!file_exists($file)) {
        throw \InvalidArgumentException(sprintf(
            'File %s does not exist.', $file
        ));
    }

    $template = function($__file, $__vars) {
        foreach ($__vars as $var => $value) {
            $$var = $value;
        }
        unset($__vars, $var, $value);

        ob_start();
        include($__file);
        $rendered = ob_get_clean();

        return $rendered;
    };

    return $template($file, $vars);
}
