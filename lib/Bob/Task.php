<?php

namespace Bob;

class Task
{
    public $callback;
    public $name;
    public $prerequisites = array();
    public $description = '';
    public $usage = '';

    function __construct($name, $callback)
    {
        if (!is_callable($callback)) {
            throw new \InvalidArgumentException('Callback is not valid');
        }

        $this->name = $name;
        $this->callback = $callback;
    }

    function invoke()
    {
        return call_user_func($this->callback, $this);
    }

    function __invoke()
    {
        return $this->invoke();
    }
}
