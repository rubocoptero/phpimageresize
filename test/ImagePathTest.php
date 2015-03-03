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

    public function testObtainLocallyCachedFilePath() {
        $configuration = new Configuration(array('width' => 800, 'height' => 600));
        $imagePath = new ImagePath('http://martinfowler.com/mf.jpg?query=hello&s=fowler');

        $stub = $this->getMockBuilder('FileSystem')
            ->getMock();
        $stub->method('file_get_contents')
            ->willReturn('foo');

        $stub->method('file_exists')
            ->willReturn(true);

        $imagePath->injectFileSystem($stub);

        $this->assertEquals(
            './cache/remote/mf.jpg',
            $imagePath->obtainSourceFilePath($configuration->obtainRemote(), $configuration->obtainCacheMinutes()));

    }

    public function testLocallyCachedFilePathFail() {
        $configuration = new Configuration(array('width' => 800, 'height' => 600));
        $imagePath = new ImagePath('http://martinfowler.com/mf.jpg?query=hello&s=fowler');

        $stub = $this->getMockBuilder('FileSystem')
            ->getMock();
        $stub->method('file_exists')
            ->willReturn(true);

        $stub->method('filemtime')
            ->willReturn(21 * 60);

        $imagePath->injectFileSystem($stub);

        $this->assertEquals(
            './cache/remote/mf.jpg',
            $imagePath->obtainSourceFilePath($configuration->obtainRemote(), $configuration->obtainCacheMinutes())
        );

    }

    public function testComposeDestinationFileNameFromDefinedOutputFilename() {
        $expected = 'mf.jpg';
        $configuration = new Configuration(array('output-filename' => $expected));
        $image = new ImagePath('http://martinfowler.com/mf.jpg?query=hello&s=fowler');
        $srcImagePath = './cache/remote/mf.jpg';

        $stub = $this->getMockBuilder('FileSystem')
            ->getMock();
        $stub->method('md5_file')
            ->willReturn('182jsakjfd938210');
        $stub->method('pathinfo')
            ->willReturn(array('extension' => '.gif'));

        $image->injectFileSystem($stub);

        $this->assertEquals(
            $expected,
            $image->obtainDestinationFilePath($srcImagePath, $configuration)
        );
    }

    public function testComposeDestinationFileNameWithScaleCropWidthAndHeight() {
        $configuration = new Configuration(array(
            'scale' => 4.0,
            'width' => 80,
            'height' => 28,
            'crop' => true
        ));
        $image = new ImagePath('http://martinfowler.com/mf.jpg?query=hello&s=fowler');
        $srcImagePath = './cache/remote/mf.jpg';

        $stub = $this->getMockBuilder('FileSystem')
            ->getMock();
        $stub->method('md5_file')
            ->willReturn('182jsakjfd938210');
        $stub->method('pathinfo')
            ->willReturn(array('extension' => 'gif'));

        $image->injectFileSystem($stub);

        $this->assertEquals(
            './cache/182jsakjfd938210_w80_h28_cp_sc.gif',
            $image->obtainDestinationFilePath($srcImagePath, $configuration)
        );
    }
}
