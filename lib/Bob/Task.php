<?php

namespace Bob;

use Bob;
use itertools;

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
        $prerequisites,

        # Public: The description.
        $description = '',

        # Public: An application instance which holds references
        # to all tasks.
        $application,
        $enable = true;

    protected $reenable = false;

    # Public: Initializes the task instance.
    #
    # name        - The task name, used to refer to the task in the CLI and
    #               when declaring dependencies.
    # application - The application object, to which this task belongs to.
    function __construct($name, $application)
    {
        $this->name        = $name;
        $this->application = $application;

        $this->description = TaskRegistry::$lastDescription;
        TaskRegistry::$lastDescription = '';

        $this->clear();
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
                $this->application['log']->debug("{$this->inspect()} is not enabled");
            }
            return;
        }

        if (!$this->reenable and $this->application['invocation_chain']->has($this)) {
            return;
        }

        if (!$this->application->forceRun and !$this->isNeeded()) {
            $this->application->trace and $this->application['log']->debug("Skipping {$this->inspect()}");
            return;
        }

        $this->application['invocation_chain']->push($this);

        if ($this->application->trace) {
            $this->application['log']->debug("Invoke {$this->inspect()}");
        }

        $app = $this->application;

        itertools\walk(
            itertools\filter(itertools\to_iterator($this->prerequisites), function($p) use ($app) {
                return $app->taskDefined((string) $p);
            }),
            function($p) use ($app) {
                $app['tasks'][(string) $p]->invoke();
            }
        );

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
        $this->prerequisites = null;
    }

    function reenable()
    {
        $this->reenable = true;
    }

    function enhance($deps = null, $action = null)
    {
        if ($deps) {
            if ($this->prerequisites instanceof \Iterator) {
                $p = new \AppendIterator;
                $p->append($this->prerequisites);
                $p->append(itertools\to_iterator($deps));
                $this->prerequisites = $p;
            } else {
                $this->prerequisites = itertools\to_iterator($deps);
            }
        }

        if (is_callable($action)) {
            $this->actions->push($action);
        }
    }

    function getTaskPrerequisites()
    {
        $app = $this->application;

        return itertools\filter(
            itertools\to_iterator($this->prerequisites),
            function($task) use ($app) {
                return $app->taskDefined($task);
            }
        );
    }

    function inspect()
    {
        $prereqs = join(', ', iterator_to_array($this->getTaskPrerequisites()));
        $class = get_class($this);
        return "<$class {$this->name} => $prereqs>";
    }

    function __toString()
    {
        return $this->name;
    }
}
