<?php

namespace Bob;

require_once __DIR__.'/../lib/Bob.php';

function App()
{
    static $instance;
    if (null === $instance) $instance = new Application;
    return $instance;
}

function task($name, $callback)
{
    App()->task($name, $callback);
}

function desc($text)
{
    App()->desc($text);
}

App()->run();
