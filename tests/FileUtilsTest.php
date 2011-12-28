<?php

class FileUtilsTest extends \PHPUnit_Framework_TestCase
{
    function testWithCwd()
    {
        $testCwd = getcwd();
        $dir = realpath('/tmp');

        $cwd = FileUtils::withCWD($dir, function() {
            return getcwd();
        });

        $this->assertEquals($testCwd, getcwd(), "Does not influence the CWD outside of the callback");
        $this->assertEquals($dir, $cwd);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    function testWithCwdThrowsExceptionWhenDirectoryDoesNotExist()
    {
        FileUtils::withCWD('/foo', function() {});
    }
}
