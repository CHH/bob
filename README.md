Bob, your friendly builder
==========================

This is Bob. Bob is a build tool for PHP projects. It's tiny (~200 LOC)
and messy (no OOP, warning: may contain usages of global variables!).

Getting Started
---------------

### Install

First you need to download the [bob.phar][phar] file and place it
somewhere.

#### System-wide install

To do a system-wide install, checkout either a zipball of Bob or
clone the Bob Repository with:

    $ git clone git://github.com/CHH/Bob.git

Then `cd Bob` and run `php bin/bob.php install`. You can then run Bob
via the `bob` executable.

### Prepare your project

You can output a usage message by running

	$ php bob.phar --help

First run in your projects root directory Bob with the `--init` flag.
This creates an empty `bob_config.php` with one example task:

    $ php bob.phar --init

The `bob_config.php` is the file which Bob looks for tasks, when run on the project.
It's important that you declare that this file belongs to the 
`Bob` namespace with `namespace Bob;`, otherwise the DSL functions are
not available.

---

**Hint:** It doesn't matter if you're in a sub-directory of your
project, Bob _will_ find your `bob_config.php` by wandering up
the directory tree.

---

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

To view all tasks and their descriptions pass the `--tasks` flag:

	$ php bob.phar --tasks
	hello
	    Prints Hello World to the Command Line

If you pass no arguments to Bob, it runs the first defined task in your
`bob_config.php`.

