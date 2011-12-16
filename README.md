Bob, your friendly builder
==========================

This is Bob. Bob is a build tool for PHP projects. It's tiny (~200 LOC)
and messy (no OOP, warning: may contain usages of global variables!).

Getting Started
---------------

First you need to download the [bob.phar][phar] file and place it
somewhere.

You can output a usage message by running

	$ php bob.phar --help

---

**For Unix and Linux Users:** You can don't have to run the `bob.phar`
with `php`. Just run `chmod a+x bob.phar`, then you can run it like
this:

	$ ./bob.phar --help

Then you also can rename it to just `bob`:

	$ mv bob.phar bob
	$ ./bob --help

---

Then in your project's root, create a file named `bob_config.php`:

    <?php
	namespace Bob;

This is the file which Bob looks for tasks, when run on the project.
It's important that you declare that this file belongs to the 
`Bob` namespace with `namespace Bob;`, otherwise the DSL functions are
not available.

Now let's define our first task. This task will output "Hello World":

	task('hello', function() {
		println('Hello World');
	});

Let's run this task:

	$ php bob.phar hello

You know, tasks should be self-documenting, you really don't want a
manual for your build config or do you?. Bob provides the
`desc` function for that. Let's add some text to our task, which says
what it is all about:

	desc('Prints Hello World to the Command Line');
	task('hello', function() {
		println('Hello World');
	});

To view all tasks and their descriptions add a `--tasks` flag:

	$ php bob.phar --tasks
	hello
	    Prints Hello World to the Command Line
