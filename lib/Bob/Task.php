<?php

namespace Bob;

// Internal: Represents a single task.
class Task
{
    // Internal: The task's action, can be empty.
    var $action;

    // Public: Name of the task. Used to invoke the task and used in prerequisites.
    var $name;

    // Public: The task's dependencies. When a task name is encountered then this
    // task gets run before this task.
    var $prerequisites = array();

    // Public: The description.
    var $description = '';

    // Public: An application instance which holds references
    // to all tasks.
    var $application;

    static function defineTask()
    {
        foreach (array_filter(func_get_args()) as $arg) {
            switch (true) {
                case is_callable($arg):
                    $action = $arg;
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

        if (!empty($prerequisites)) {
            foreach ($prerequisites as $p) {
                $task->addPrerequisite($p);
            }
        }

        empty($action) ?: $task->action = $action;

        Bob::$application->defineTask($task);
        return $task;
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
        TaskRegistry::$lastDescription = '';
    }

    // Child classes of Task should put here their custom logic to determine
    // if the task should do something. See the FileTask class for an
    // example of this.
    //
    // Returns TRUE if the task should be run, FALSE otherwise.
    function isNeeded()
    {
        return true;
    }

    // Public: Collects all dependencies and invokes the task if it's 
    // needed.
    //
    // Returns the callback's return value.
    function invoke()
    {
        if (!$this->isNeeded()) {
            $this->application->trace and println("bob: skipping {$this->inspect()}", STDERR);
            return;
        }

        if ($this->application->trace) {
            println("bob: invoke {$this->inspect()}", STDERR);
        }

        foreach ($this->prerequisites as $p) {
            if ($task = $this->application->tasks[$p]) {
                $task->invoke();
            }
        }

        if (is_callable($this->action)) {
            return call_user_func($this->action, $this);
        }
    }

    function addPrerequisite($prerequisite)
    {
        $this->prerequisites[] = (string) $prerequisite;
        return $this;
    }

    function getPrerequisites()
    {
        return $this->prerequisites;
    }

    function inspect()
    {
        return sprintf(
            '[%s] (%s)', $this->name, get_class($this)
        );
    }

    function __toString()
    {
        return $this->name;
    }
}
