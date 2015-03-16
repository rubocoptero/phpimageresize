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

    private function checkImage($path) {
        if (!($path instanceof ImagePath)) throw new InvalidArgumentException();
    }

    private function checkConfiguration($configuration) {
        if (!($configuration instanceof Configuration)) throw new InvalidArgumentException();
    }

    function composeNewPath($imagePath, $configuration) {
        $w = $configuration->obtainWidth();
        $h = $configuration->obtainHeight();
        $filename = md5_file($imagePath);
        $finfo = pathinfo($imagePath);
        $ext = $finfo['extension'];

        $cropSignal = isset($opts['crop']) && $opts['crop'] == true ? "_cp" : "";
        $scaleSignal = isset($opts['scale']) && $opts['scale'] == true ? "_sc" : "";
        $widthSignal = !empty($w) ? '_w'.$w : '';
        $heightSignal = !empty($h) ? '_h'.$h : '';
        $extension = '.'.$ext;

        $newPath = $configuration->obtainCache() .$filename.$widthSignal.$heightSignal.$cropSignal.$scaleSignal.$extension;

        if($opts['output-filename']) {
            $newPath = $opts['output-filename'];
        }

        return $newPath;
    }
}