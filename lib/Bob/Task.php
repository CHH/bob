<?php

namespace Bob;

// Internal: Represents a single task.
class Task
{
    // Internal: Stores the description for the next created task.
    static $lastDescription = '';

    // Internal: Stores the usage message for the next created task.
    static $lastUsage = '';

    // Internal: The task's callback, can be empty.
    public $callback;

    // Public: Name of the task. Used to invoke the task and used in prerequisites.
    public $name;

    // Public: The task's dependencies. When a task name is encountered then this
    // task gets run before this task.
    public $prerequisites = array();

    // Public: The description.
    public $description = '';

    // Public: The usage message.
    public $usage = '';

    // Public: Initializes the task instance.
    //
    // name     - The task name, used to refer to the task in the CLI and
    //            when declaring dependencies.
    // callback - The code to run when the task is invoked (optional).
    function __construct($name, $callback = null)
    {
        $this->name = $name;
        $this->callback = $callback;

        $this->description = self::$lastDescription;
        $this->usage = self::$lastUsage ?: $name;

        Task::$lastDescription = '';
        Task::$lastUsage = '';
    }

    // Public: invokes a given task.
    //
    // Todo
    //
    //  - Do dependency resolution here?
    //
    // Returns the callback's return value.
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
