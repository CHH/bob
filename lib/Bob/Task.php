<?php

namespace Bob;

// Internal: Represents a single task.
class Task
{
    // Internal: The task's action, can be empty.
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

    // Public: An application instance which holds references
    // to all tasks.
    var $application;

    static function defineTask()
    {
        foreach (array_filter(func_get_args()) as $arg) {
            switch (true) {
                case is_callable($arg):
                    $callback = $arg;
                    break;
                case is_string($arg):
                    $name = $arg;
                    break;
                case is_array($arg):
                case ($arg instanceof \Traversable):
                    $prerequisites = $arg;
                    break;
            }
        }

        if (empty($name)) {
            throw new \InvalidArgumentException('Name cannot be empty');
        }

        if (Bob::$application->taskDefined($name)) {
            $task = Bob::$application->tasks[$name];
        } else {
            $task = new static($name, Bob::$application);
        }

        empty($prerequisites) ?: $task->prerequisites = $prerequisites;
        empty($callback)      ?: $task->callback = $callback;

        Bob::$application->defineTask($task);
    }

    // Public: Initializes the task instance.
    //
    // name        - The task name, used to refer to the task in the CLI and
    //               when declaring dependencies.
    // application - The application object, to which this task belongs to.
    function __construct($name, $application)
    {
        $this->name        = $name;
        $this->application = $application;

        $this->description = TaskRegistry::$lastDescription;
        $this->usage       = TaskRegistry::$lastUsage ?: $name;

        TaskRegistry::$lastDescription = '';
        TaskRegistry::$lastUsage = '';
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
            if ($task = $this->application->tasks[$p]) {
                $task->invoke();
            }
        }

        if (is_callable($this->callback)) {
            return call_user_func($this->callback, $this);
        }
    }
}
