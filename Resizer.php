<?php

require 'FileSystem.php';

class Resizer {

    private $image;
    private $configuration;
    private $fileSystem;

    public function __construct($path, $configuration) {
        $this->checkPath($path);
        $this->checkConfiguration($configuration);
        $this->image = $path;
        $this->configuration = $configuration;
        $this->fileSystem = new FileSystem();
    }

    public function injectFileSystem(FileSystem $fileSystem) {
        $this->fileSystem = $fileSystem;
    }

    private function checkPath($path) {
        if (!($path instanceof ImagePath)) throw new InvalidArgumentException();
    }

    private function checkConfiguration($configuration) {
        if (!($configuration instanceof Configuration)) throw new InvalidArgumentException();
    }
}