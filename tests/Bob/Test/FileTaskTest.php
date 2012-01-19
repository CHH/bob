<?php

namespace Bob\Test;

use Bob\FileTask,
    Bob\Application;

class FileTaskTest extends \PHPUnit_Framework_TestCase
{
    function getFixturesDir()
    {
        return __DIR__.'/fixtures/file_task';
    }

    function testIsNeededWhenTargetDoesNotExist()
    {
        $t = new FileTask($this->getFixturesDir().'/foo.txt', new Application);

        $this->assertTrue($t->isNeeded());
    }

    function testIsNeededWhenPrerequisitesAreNewerThanTarget()
    {
        $t = new FileTask($this->getFixturesDir().'/out.txt', new Application);
        $t->enhance(array(
            $this->getFixturesDir().'/in1.txt', 
            $this->getFixturesDir().'/in2.txt')
        );

        touch($this->getFixturesDir().'/in1.txt');
        touch($this->getFixturesDir().'/out.txt', strtotime('-1 minute'));

        $this->assertTrue($t->isNeeded());
    }

    function testIsNotNeededWhenTargetNewerThanPrerequisites()
    {
        $t = new FileTask($this->getFixturesDir().'/out.txt', new Application);
        $t->enhance(array(
            $this->getFixturesDir().'/in1.txt', 
            $this->getFixturesDir().'/in2.txt')
        );

        touch($this->getFixturesDir().'/in1.txt', strtotime('-1 minute'));
        touch($this->getFixturesDir().'/in2.txt', strtotime('-1 minute'));
        touch($this->getFixturesDir().'/out.txt');

        $this->assertFalse($t->isNeeded());
    }
}
