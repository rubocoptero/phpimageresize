<?php

require_once 'Resizer.php';
require_once 'ImagePath.php';
require_once 'Configuration.php';
date_default_timezone_set('Europe/Berlin');


class ResizerTest extends PHPUnit_Framework_TestCase {
    private $validOpts = array('height' => 20);


    /**
     * @expectedException InvalidArgumentException
     */
    public function testNecessaryPathCollaboration() {
        $resizer = new Resizer('anyNonPathObject', new Configuration($this->validOpts));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testNecessaryConfigurationCollaboration() {
        $resizer = new Resizer(new ImagePath(''), 'nonConfigurationObject');
    }

    public function testInstantiation() {
        $this->assertInstanceOf('Resizer', new Resizer(new ImagePath(''), new Configuration($this->validOpts)));
    }

    public function testCreateNewPath() {
        $resizer = new Resizer(
            new ImagePath('http://martinfowler.com/mf.jpg?query=hello&s=fowler'),
            new Configuration($this->validOpts)
        );
    }

    public function testComposeNewPathWithOuputFilename () {
        $resizer = new Resizer(
            new ImagePath('http://martinfowler.com/mf.jpg?query=hello&s=fowler'),
            new Configuration(array('output-filename' =>  'hola.jpg'))
        );
        $this->mockFileSystem($resizer);

        $this->assertEquals('hola.jpg', $resizer->composeNewPathFrom('adios.jpg'));
    }

    public function testComposeNewPathWithEverythingExceptOutputFilename () {
        $resizer = new Resizer(
            new ImagePath('http://martinfowler.com/mf.jpg?query=hello&s=fowler'),
            new Configuration(array(
                'width' =>  800,
                'height' => 600,
                'crop' => true,
                'scale' => true
            ))
        );
        $this->mockFileSystem($resizer);


        $this->assertEquals(
            './cache/jasfjo3uj2912309u13j1esj09safjls_w800_h600_cp_sc.jpg',
            $resizer->composeNewPathFrom('adios.jpg')
        );
    }

    private function mockFileSystem($resizer)
    {
        $stub = $this->getMockBuilder('FileSystem')
            ->getMock();
        $stub->method('md5_file')
            ->willReturn('jasfjo3uj2912309u13j1esj09safjls');

        $stub->method('pathinfo')
            ->willReturn(array('extension' => 'jpg'));

        $resizer->injectFileSystem($stub);
    }

}
