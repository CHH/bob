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
    var $callback;

    // Public: Name of the task. Used to invoke the task and used in prerequisites.
    var $name;

    // Public: The task's dependencies. When a task name is encountered then this
    // task gets run before this task.
    var $prerequisites = array();

    // Public: The description.
    var $description = '';

    // Public: The usage message.
    var $usage = '';

    var $project;

    // Public: Initializes the task instance.
    //
    // name          - The task name, used to refer to the task in the CLI and
    //                 when declaring dependencies.
    // callback      - The code to run when the task is invoked (optional).
    // prerequisites - The task's dependencies (optional).
    function __construct($name, $prerequisites = array(), $callback = null)
    {
        $this->name = $name;

        foreach (array_filter(array($prerequisites, $callback)) as $var) {
            switch (true) {
                case is_callable($var):
                    $this->callback = $var;
                    break;
                case is_array($var):
                    $this->prerequisites = $var;
                    break;
            }
        }

        $this->description = self::$lastDescription;
        $this->usage = self::$lastUsage ?: $name;

        self::$lastDescription = '';
        self::$lastUsage = '';
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
        foreach ($this->prerequisites as $p) {
            if ($this->project->taskExists($p)) {
                $this->project[$p]->invoke();
            }
        }

        if (is_callable($this->callback)) {
            return call_user_func($this->callback, $this);
        }
    }

    function __invoke()
    {
        return $this->invoke();
    }
}
