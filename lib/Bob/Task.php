<?php

namespace Bob;

use Bob;

# Internal: Represents a single task.
class Task
{
    public
        # Internal: The task's actions as SplDoublyLinkedList, can be empty.
        $actions,

        # Public: Name of the task. Used to invoke the task and used in prerequisites.
        $name,

        # Public: The task's dependencies. When a task name is encountered then this
        # task gets run before this task.
        $prerequisites = array(),

        # Public: The description.
        $description = '',

        # Public: An application instance which holds references
        # to all tasks.
        $application,

        $enable = true;

    protected
        $reenable = false,
        $log;

    # Public: Returns a task instance.
    #
    # Returns a task instance, Null when Task was not found.
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

    # Public: Initializes the task instance.
    #
    # name        - The task name, used to refer to the task in the CLI and
    #               when declaring dependencies.
    # application - The application object, to which this task belongs to.
    function __construct($name, $application)
    {
        $this->name        = $name;
        $this->application = $application;
        $this->log         = $application->log;

        $this->description = TaskRegistry::$lastDescription;
        TaskRegistry::$lastDescription = '';

        $this->actions = new \SplDoublyLinkedList;
    }

    # Child classes of Task should put here their custom logic to determine
    # if the task should do something. See the FileTask class for an
    # example of this.
    #
    # Returns TRUE if the task should be run, FALSE otherwise.
    function isNeeded()
    {
        return true;
    }

    # Public: Collects all dependencies and invokes the task if it's 
    # needed.
    #
    # Returns the callback's return value.
    function invoke()
    {
        if (!$this->enable) {
            if ($this->application->trace) {
                $this->log->debug("{$this->inspect()} is not enabled");
            }
            return;
        }

        if (!$this->reenable and $this->application->invocationChain->has($this)) {
            return;
        }

        if (!$this->application->forceRun and !$this->isNeeded()) {
            $this->application->trace and $this->log->debug("Skipping {$this->inspect()}");
            return;
        }

        $this->application->invocationChain->push($this);

        if ($this->application->trace) {
            $this->log->debug("Invoke {$this->inspect()}");
        }

        foreach ($this->prerequisites as $p) {
            if ($task = $this->application->tasks[$p]) {
                $task->invoke();
            }
        }

        $this->execute();
        $this->reenable = false;
    }

    # Internal: Executes all actions.
    #
    # Returns nothing.
    function execute()
    {
        foreach ($this->actions as $action) {
            call_user_func($action, $this);
        }
    }

    # Clears all actions and prerequisites.
    #
    # Returns nothing.
    function clear()
    {
        $this->actions = new \SplDoublyLinkedList;
        $this->prerequisites = array();
    }

    function reenable()
    {
        $this->reenable = true;
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
