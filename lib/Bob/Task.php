<?php

namespace Bob;

// Internal: Represents a single task.
class Task
{
    // Internal: The task's actions, can be empty.
    var $actions = array();

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

    protected $invoked = false;

    static function get($name)
    {
        return Bob::$application->tasks[$name];
    }

    static function defineTask($name, $prerequisites = null, $action = null)
    {
        foreach (array_filter(array($prerequisites, $action)) as $arg) {
            switch (true) {
                case is_callable($arg):
                    $action = $arg;
                    break;
                case is_array($arg):
                case ($arg instanceof \Traversable):
                case ($arg instanceof \Iterator):
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

        $task->enhance($prerequisites, $action);

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

        $this->actions = new \SplDoublyLinkedList;
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
        if ($this->invoked) return;

        if (!$this->application->forceRun and !$this->isNeeded()) {
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

        $this->execute();
        $this->invoked = true;
    }

    function execute()
    {
        foreach ($this->actions as $action) {
            call_user_func($action, $this);
        }
    }

    function clear()
    {
        $this->actions = new \SplDoublyLinkedList;
        $this->prerequisites = array();
    }

    function reenable()
    {
        $this->invoked = false;
    }

    function enhance($deps = null, $action = null)
    {
        if ($deps) {
            foreach ($deps as $d) {
                $this->addPrerequisite($d);
            }
        }

        if (is_callable($action)) {
            $this->actions[] = $action;
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

    function getTaskPrerequisites()
    {
        $tasks = array();

        foreach ($this->prerequisites as $p) {
            if ($this->application->taskDefined($p)) {
                $tasks[] = $p;
            }
        }

        return $tasks;
    }

    function inspect()
    {
        $prereqs = join(', ', $this->getTaskPrerequisites());
        $class = get_class($this);
        return "<$class {$this->name} => $prereqs>";
    }

    function __toString()
    {
        return $this->name;
    }
}
