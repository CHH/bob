Bob, your friendly builder
==========================

This is Bob. Bob is a build tool for PHP projects similar to Rake, but
aims to be _very_ lightweight.

It features a very simple DSL and supports file tasks too.

How is Bob compared to [Pake](https://github.com/indeyets/pake):

 - Bob's DSL is a set of namespaced functions, so PHP 5.3 __is a must__.
 - Bob's task definitions directly take a closure for the task's body,
   instead of needing some named `run_`.
 - Bob **has no** file finder similar to `pakeFinder`, if you need this
   just use the [Symfony Finder](https://github.com/symfony/Finder).

How is Bob compared to [Phing](http://www.phing.info/trac/):

 - Bob **does not use XML config files** to define tasks. I think build
   files should be written in the language used to write the project
   itself so _everyone_ in the team can contribute to them. Also it's
   quite hilarious to use XML to model a DSL with logic and such.
 - Bob has nothing like plugins. To add new functions to Bob's DSL just
   put them into the `Bob` namespace and require the file somehow at the
   beginning of your build file.

   Simply put:
   The build files are only PHP.
 - Bob has **no** rich set of provided tasks and I do not plan to add
   this. _Bob is lightweight._

Getting Started
---------------

### Install

#### Composer-enabled Project

Simply add the `chh/bob` package to your `require` section in your
`composer.json`:

    {
        "require": {
            "chh/bob": "master-dev"
        }
    }

Then run `composer install`.

You can invoke Bob with:

    php vendor/bin/bob.phar

or:

    ./vendor/bin/bob.phar

#### System-wide install

To do a system-wide install, download either a zipball of Bob or
clone the Bob Repository with:

    $ git clone git://github.com/CHH/Bob.git

Then `cd Bob` and run `php bin/bob.php install`. You can then run Bob
via the `bob` command.

By default the `bob` command is created in `/usr/local/bin`. To change
this set a `PREFIX` environment variable, the command is then created
in `PREFIX/bin`.

### Prepare your project

You can output a usage message by running

	$ php bob.phar --help

First run in your projects root directory Bob with the `--init` flag.
This creates an empty `bob_config.php` with one example task:

    $ php bob.phar --init

The `bob_config.php` file contains all your project's tasks.

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

### File Tasks

A file task is a special kind of task, which gets only run if either the
target (the product of some operation) does not exist, or the
prerequisites are newer than the target.

So file tasks are very handy if you've some artifacts which are
generated from other files, and which you don't want to regenerate
if nothing has changed.

For example: Let's write a task which concatenates three input files to
one output file.

First we have to create the prerequisites:

    $ echo "foo\n" > file1.txt
    $ echo "bar\n" > file2.txt
    $ echo "baz\n" > file3.txt

Then put this into your `bob_config.php`:

    fileTask('concat.txt', array('file1.txt', 'file2.txt', 'file3.txt'), function($task) {
        println("Concatenating");
        $concat = '';
        
        foreach ($task->prerequisites as $file) {
            $concat .= file_get_contents($file);
        }

        @file_put_contents($task->name, $concat);
    });

Let's run this task:

    $ php bob.phar concat.txt
    Concatenating

This will result in a `concat.txt` file in your project root:

    $ cat concat.txt
    foo
    bar
    baz

Let's run it again, without modifying the prerequisites:

    $ php bob.phar concat.txt

See it? The callback was not run, because the prerequisites were not modified.

Let's verify this:

    $ touch file1.txt
    $ php bob.phar concat.txt
    Concatenating

The prerequisites of a file task are also resolved as task names, so
they can depend on other file tasks too. Or you can put regular task
names into the prerequisites, but then you've to be careful to not
accidentally treat them as files when looping through all prerequisites.
