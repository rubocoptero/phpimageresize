<?php

include 'Configuration.php';

class ConfigurationTest extends PHPUnit_Framework_TestCase {
    private $minimumOpts = array(
        'output-filename' => 'somewhere'
    );

    private $defaults = array(
        'crop' => false,
        'scale' => 'false',
        'thumbnail' => false,
        'maxOnly' => false,
        'canvas-color' => 'transparent',
        'cacheFolder' => './cache/',
        'remoteFolder' => './cache/remote/',
        'quality' => 90,
        'cache_http_minutes' => 20,
    );

    public function testOpts()
    {
        $this->assertInstanceOf(
            'Configuration',
            new Configuration($this->minimumOpts)
            );
    }

    public function testDefaultsAreMerged() {
        $opts = $this->minimumOpts;
        $configuration = new Configuration($opts);

        $this->assertEquals(
            $this->mergeWithDefaults($opts),
            $configuration->asHash());
    }

    public function testDefaultsNotOverwriteConfiguration() {

        $opts = array(
            'thumbnail' => true,
            'maxOnly' => true,
            'width' => 50
        );

        $configuration = new Configuration($opts);
        $configured = $configuration->asHash();

        $this->assertTrue($configured['thumbnail']);
        $this->assertTrue($configured['maxOnly']);
    }

    public function testObtainCache() {
        $configuration = new Configuration($this->minimumOpts);

        $this->assertEquals('./cache/', $configuration->obtainCache());
    }

    public function testObtainRemote() {
        $configuration = new Configuration($this->minimumOpts);

        $this->assertEquals('./cache/remote/', $configuration->obtainRemote());
    }

    public function testObtainConvertPath() {
        $configuration = new Configuration($this->minimumOpts);

        $this->assertEquals('convert', $configuration->obtainConvertPath());
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testThrowsAnExceptionWhenResizeValuesAreNotDefined() {
        $configuration = new Configuration(null);
    }

    private function mergeWithDefaults ($opts) {
        return array_merge(
            $this->defaults,
            $opts
        );
    }
}

?>
