<?php

require 'FileSystem.php';

class Resizer {

    private $image;
    private $configuration;
    private $fileSystem;

    public function __construct($image, $configuration) {
        $this->checkImage($image);
        $this->checkConfiguration($configuration);
        $this->image = $image;
        $this->configuration = $configuration;
        $this->fileSystem = new FileSystem();
    }

    public function injectFileSystem(FileSystem $fileSystem) {
        $this->fileSystem = $fileSystem;
    }

    private function checkImage($image) {
        if (!($image instanceof ImagePath)) throw new InvalidArgumentException();
    }

    private function checkConfiguration($configuration) {
        if (!($configuration instanceof Configuration)) throw new InvalidArgumentException();
    }

    function composeNewPathFrom($currentPath) {
        $w = $this->configuration->obtainWidth();
        $h = $this->configuration->obtainHeight();
        $filename = $this->fileSystem->md5_file($currentPath);
        $finfo = $this->fileSystem->pathinfo($currentPath);
        $ext = $finfo['extension'];
        $opts = $this->configuration->asHash();

        $cropSignal = isset($opts['crop']) && $opts['crop'] == true ? "_cp" : "";
        $scaleSignal = isset($opts['scale']) && $opts['scale'] == true ? "_sc" : "";
        $widthSignal = !empty($w) ? '_w'.$w : '';
        $heightSignal = !empty($h) ? '_h'.$h : '';
        $extension = '.'.$ext;

        $newPath = $this->configuration->obtainCache() .$filename.$widthSignal.$heightSignal.$cropSignal.$scaleSignal.$extension;

        if($opts['output-filename']) {
            $newPath = $opts['output-filename'];
        }

        return $newPath;
    }
}