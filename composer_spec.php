<?php

$pkg = array();

// $NAME is generated by the getName() function
// in `bob_config.php`.
$pkg['name'] = $NAME;

$pkg['require'] = array(
    'php' => '>=5.3.2',
);

$pkg['description'] = "A tiny and messy build tool for PHP projects.";
$pkg['keywords']    = array('build');
$pkg['license']     = "MIT";
$pkg['homepage']    = "https://github.com/CHH/Bob";

// These get computed in the `bob_config.php`
$pkg['version'] = $VERSION;
$pkg['authors'] = $AUTHORS;
$pkg['bin'] = $EXECUTABLES;

// DO NOT REMOVE THIS
return $pkg;
