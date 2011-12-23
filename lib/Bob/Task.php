<?php

namespace Bob;

class Task
{
    static $lastDescription = '';
    static $lastUsage = '';

    public $callback;
    public $name;
    public $prerequisites = array();
    public $description = '';
    public $usage = '';

    function __construct($name, $callback = null)
    {
        $this->name = $name;
        $this->callback = $callback;

        $this->description = self::$lastDescription;
        $this->usage = self::$lastUsage ?: $name;

        Task::$lastDescription = '';
        Task::$lastUsage = '';
    }

    function invoke()
    {
        if (is_callable($this->callback)) {
            return call_user_func($this->callback, $this);
        }
    }

    function __invoke()
    {
        return $this->invoke();
    }
}
