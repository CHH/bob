<?php

namespace Bob;

// Internal: Holds the config object
// used by the static DSL.
function ConfigHolder(Config $config = null)
{
    static $instance;

    if ($config !== null) {
        $instance = $config;
    }
    return $instance;
}

function task($name, $prerequisites = array(), $callback = null)
{
}

function desc($desc, $usage = null)
{
}

class Config
{
    public $tasks = array();
    public $descriptions = array();
    public $usages = array();

    static function read($dir)
    {
        $config = new static;
        
        ConfigHolder($config);


    }
}
