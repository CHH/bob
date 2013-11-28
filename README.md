Bob, your friendly builder
==========================

[![Build Status](https://travis-ci.org/CHH/bob.png?branch=master)](https://travis-ci.org/CHH/bob)

- - -
## Hello World

Put this in a file named `bob_config.php` in your project's root:

```php
<?php

namespace Bob\BuildConfig;

task('default', ['hello']);

task('hello', function() {
	echo "Hello World!\n";
});
```

Run this on your shell:

```
$ php composer.phar require chh/bob:~1.0@dev
$ vendor/bin/bob
```

## What is Bob?

This is Bob. Bob is a lightweight project automation tool in PHP similar to Rake.

Bob _can_ be used as general build tool, but _really shines_ when used in PHP 
projects as you are capable of reusing existing Application Code and Libraries 
in your buildfiles.

**How Bob compares to [Pake](https://github.com/indeyets/pake):**

 - Bob uses a set of namespaced functions for the DSL, so PHP 5.3 __is a must__. 
   **If you need 5.2.x support, look at Pake.**
 - Bob's task definitions directly take a closure for the task's body,
   instead of performing magic with functions named `run_<task name>`.
 - Bob **has no** file finder similar to `pakeFinder`, if you need this
   just use the [Symfony Finder](https://github.com/symfony/Finder).

**How Bob compares to [Phing](http://www.phing.info/trac/):**

 - Bob **does not use XML config files** to define tasks. I think build
   files should be written in the language used to write the project
   itself so the barrier to contribution to build files is as low as possible. 
   Also I think it's quite hilarious to use XML for a DSL with logic and such.
 - Bob has nothing like plugins. To add new functions to Bob's DSL just
   put them into the `Bob\BuildConfig` namespace and require the file somehow at the
   beginning of your build file. **Simply put:** Bob's build configs are _only_ PHP.
 - Bob has **no** rich set of provided tasks and I do not plan to add
   this. _Bob is lightweight._

Getting Started
---------------

### Install

#### Prerequisites

Bob needs at least **PHP 5.3.2** to run. 

If you plan to hack on Bob, please make sure you 
have set `phar.readonly` to `Off` in your `php.ini`. Otherwise you will have a tough luck
creating a PHAR package of Bob.

#### Install into a [Composer](https://github.com/composer/composer)-enabled Project (recommended)

Add the `chh/bob` package to the `require-dev` section in your
`composer.json`:

    {
        "require-dev": {
            "chh/bob": "1.0.*@dev"
        }
    }

Then run `composer install --dev`.

You can invoke Bob with:

    php vendor/bin/bob

or:

    ./vendor/bin/bob

#### System-wide install (Unix-like OS only)

To do a system-wide install, download either a [Release][tags] or
clone the Repository with:

    $ git clone git://github.com/CHH/bob.git
    $ cd Bob

[tags]: https://github.com/CHH/bob/tags

To install all of Bob's dependencies download Composer and run
`composer install`:

    $ wget http://getcomposer.org/composer.phar
    php composer.phar install

Then run `php bin/bob install` and you're done.

By default the `bob` command is created in `/usr/local/bin`. To change
this set a `PREFIX` environment variable, the command is then created
in `$PREFIX/bin`.

### Prepare your project

You can output a usage message by running

	$ php vendor/bin/bob --help

First run in your projects root directory Bob with the `--init` flag.
This creates an empty `bob_config.php` with one example task:

    $ php vendor/bin/bob --init

Bob loads your tasks from a special file named `bob_config.php` in your project's
root directory. Bob also includes all files found in a directory
named `bob_tasks`, in the same directory as your `bob_config.php`. Files loaded from
`bob_tasks` are treated the same way as regular Bob configs.

It's important that you declare that this file belongs to the 
`Bob\BuildConfig` namespace with `namespace Bob\BuildConfig;`, otherwise the DSL functions are
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

Tasks are run by using their name as argument(s) on the command line:

	$ php vendor/bin/bob hello

When Bob is invoked without tasks it tries to invoke
the `default` task.

To set a task as default task assign the task as prerequisite
of the `default` task:

	task('default', array('hello'));

You know, tasks should be self-documenting, you really don't want a
manual for your build config or do you? Bob provides the
`desc` function for that. Let's add some text to our task, which describes
what the task is all about:

	desc('Prints Hello World to the Command Line');
	task('hello', function() {
		println('Hello World');
	});

To view all tasks and their descriptions pass the `--tasks` flag:

	$ php vendor/bin/bob --tasks
	bob hello # Prints Hello World to the Command Line

---

To see more examples for how Bob can be used, simply look into Bob's
`bob_config.php`. It contains all configuration to create Bob's build
artifacts, for example the `bob.phar` and the composer config.

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

    $ php vendor/bin/bob concat.txt
    Concatenating

This will result in a `concat.txt` file in your project root:

    $ cat concat.txt
    foo
    bar
    baz

Let's run it again, without modifying the prerequisites:

    $ php vendor/bin/bob concat.txt

See it? The callback was not run, because the prerequisites were not modified.

Let's verify this:

    $ touch file1.txt
    $ php vendor/bin/bob concat.txt
    Concatenating

The prerequisites of a file task are also resolved as task names, so
they can depend on other file tasks too. Or you can put regular task
names into the prerequisites, but then you've to be careful to not
accidentally treat them as files when looping through all prerequisites.

### Packaging tasks for reusability

Ever did write a collection of tasks which you want to put into a
package and reuse across projects?

Enter Task Libraries.

All task libraries implement the `\Bob\TaskLibraryInterface` which
defines two methods:

* `register(\Bob\Application $app)`: Is called when the library is
  registered in the app.
* `boot(\Bob\Application $app)`: is called just before the Bob is run on
  the command line. Register your tasks here.

Here's a small example which registers a `test` task which uses PHPUnit:

```php
<?php

use Bob\Application;
use Bob\TaskLibraryInterface;
use Bob\BuildConfig as b;

class TestTasks implements TaskLibraryInterface
{
    function register(Application $app)
    {}

    function boot(Application $app)
    {
        $app->fileTask("phpunit.xml", array("phpunit.dist.xml"), function($task) {
            copy($task->prerequisites->current(), $task->name);
        });

        $app->task("test", array("phpunit.xml"), function($task) {
            b\sh("./vendor/bin/phpunit");

        })->description = "Runs the test suite";
    }
}
```

You can use task libraries by calling the `register` function within
your build scripts:

```php
<?php

namespace Bob\BuildConfig;

register(new TestTasks);
```

You will now see the `test` task when you run `./vendor/bin/bob
--tasks`.

Hacking on Bob
--------------

There are lots of ways to improve Bob, **but the most useful for me** is
to simply submit Issues to the [Issue Tracker][issues].

[issues]: http://github.com/CHH/bob/issues

### Contributing Code

I'm using the [Zend Framework Coding Standard][zfcs] in Bob and **so should you**
when contributing code.

Actually I'm using a loose version of it, the notable differences are:

 - I'm treating the `public` keyword as optional in functions.
 - `var` is okay for defining public instance variables.

[zfcs]: http://framework.zend.com/manual/en/coding-standard.html

#### Documentation

The Code Documentation is all done with [Tomdoc](http://tomdoc.org),
though there isn't anything generated for now.

#### Testing

I'm not requiring unit tests for contributions, though functionality
which affects the command line tool should be at least tried a couple of
times.

This shouldn't prevent you from writing Unit Tests though. I'm using
[PHPUnit](http://phpunit.de) for this purpose. There's also a `test`
task which runs `phpunit` (this handles the copying the `phpunit.dist.xml` to
`phpunit.xml` too).

I'm recommending [php-build](https://github.com/CHH/php-build) for doing
tests with multiple versions of PHP.

#### Building

When you've done some changes and want to regenerate the `bob.phar`, simply
run Bob on itself:

    $ php bin/bob.php

To run only the Test Suite:

	$ php bin/bob.php test

(_In case you wonder that the PHAR gets sometimes not regenerated:_ It's
only regenerated when the actual source files for the archive change.)
