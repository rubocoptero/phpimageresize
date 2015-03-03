<?php

require 'FileSystem.php';

class Resizer {
    private $configuration;

    public function __construct($configuration) {
        $this->checkConfiguration($configuration);
        $this->configuration = $configuration;
    }

    public function resize ($image) {
        $this->checkPath($image);

        $opts = $this->configuration->asHash();
        $imagePath = $image->obtainFilePath($opts['remoteFolder'], $opts['cache_http_minutes']);

        return $imagePath;
    }

    private function checkPath($path) {
        if (!($path instanceof ImagePath)) throw new InvalidArgumentException();
    }

    private function checkConfiguration($configuration) {
        if (!($configuration instanceof Configuration)) throw new InvalidArgumentException();
    }

}
