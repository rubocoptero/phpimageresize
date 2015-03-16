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

}
