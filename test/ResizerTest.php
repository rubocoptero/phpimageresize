<?php

require_once 'Resizer.php';
require_once 'ImagePath.php';
require_once 'Configuration.php';
date_default_timezone_set('Europe/Berlin');


class ResizerTest extends PHPUnit_Framework_TestCase {

    /**
     * @expectedException InvalidArgumentException
     */
    public function testNecessaryCollaboration() {
        $resizer = new Resizer('nonConfigurationObject');
    }

    public function testInstantiation() {
        $this->assertInstanceOf('Resizer', new Resizer(new Configuration(array('height' => 20))));
    }

    public function testCreateNewPath() {
        $configuration = new Configuration(array('width' => 800, 'height' => 600));
        $resizer = new Resizer($configuration);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testResizeNeedAnImageObject() {
        $configuration = new Configuration(array('width' => 800, 'height' => 600));
        $resizer = new Resizer($configuration);
        $resizer->resize('notAnImage');
    }

    public function testResizeShouldObtainFilePath() {
        $configuration = new Configuration(array('width' => 800, 'height' => 600));
        $resizer = new Resizer($configuration);

        $image = $this->getMockBuilder('ImagePath')->getMock();
        $image->method('obtainFilePath');
        $image->expects($this->once())
            ->method('obtainFilePath')
            ->with($this->identicalTo('./cache/remote/'));

        $resizer->resize($image);
    }

}
