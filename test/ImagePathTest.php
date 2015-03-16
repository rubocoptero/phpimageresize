<?php
require_once 'ImagePath.php';

class ImagePathTest extends PHPUnit_Framework_TestCase {

    public function testIsSanitizedAtInstantiation() {
        $url = 'https://www.google.com/webhp?sourceid=chrome-instant&ion=1&espv=2&ie=UTF-8#safe=off&q=php%20define%20dictionary';
        $expected = 'https://www.google.com/webhp?sourceid=chrome-instant&ion=1&espv=2&ie=UTF-8#safe=off&q=php define dictionary';

        $imagePath = new ImagePath($url);

        $this->assertEquals($expected, $imagePath->sanitizedPath());
    }

    public function testIsHttpProtocol() {
        $url = 'https://example.com';

        $imagePath = new ImagePath($url);

        $this->assertTrue($imagePath->isHttpProtocol());

        $imagePath = new ImagePath('ftp://example.com');

        $this->assertFalse($imagePath->isHttpProtocol());

        $imagePath = new ImagePath(null);

        $this->assertFalse($imagePath->isHttpProtocol());
    }

    public function testObtainFileName() {
        $url = 'http://martinfowler.com/mf.jpg?query=hello&s=fowler';

        $imagePath = new ImagePath($url);

        $this->assertEquals('mf.jpg', $imagePath->obtainFileName());
    }

    public function testObtainLocallyCachedFilePathFromPreviousDownload() {
        $imagePath = new ImagePath('http://martinfowler.com/mf.jpg?query=hello&s=fowler');

        $stub = $this->getMockBuilder('FileSystem')
            ->getMock();
        $stub->method('file_get_contents')
            ->willReturn('foo');

        $stub->method('file_exists')
            ->willReturn(true);

        $imagePath->injectFileSystem($stub);

        $this->assertEquals('./cache/remote/mf.jpg', $imagePath->obtainFilePath('./cache/remote/', 10));

    }

    public function testLocallyCachedFilePathFailFromPreviousDownload() {
        $imagePath = new ImagePath('http://martinfowler.com/mf.jpg?query=hello&s=fowler');

        $stub = $this->getMockBuilder('FileSystem')
            ->getMock();
        $stub->method('file_exists')
            ->willReturn(true);

        $stub->method('filemtime')
            ->willReturn(21 * 60);

        $imagePath->injectFileSystem($stub);

        $this->assertEquals('./cache/remote/mf.jpg', $imagePath->obtainFilePath('./cache/remote/', 10));

    }

}
