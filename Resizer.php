<?php

require 'FileSystem.php';

class Resizer {
    private $configuration;
    private $fileSystem;

    public function __construct($configuration) {
        $this->checkConfiguration($configuration);
        $this->configuration = $configuration;
        $this->fileSystem = new FileSystem();
    }

    public function injectFileSystem(FileSystem $fileSystem) {
        $this->fileSystem = $fileSystem;
    }

    public function resize ($image) {
        $this->checkPath($image);

        $opts = $this->configuration->asHash();
        $imagePath = $image->obtainSourceFilePath($opts['remoteFolder'], $opts['cache_http_minutes']);

        return $imagePath;
    }

    public function isInCache($destination, $source) {
        $isInCache = false;

        if(file_exists($destination) == true):
            $isInCache = true;
            $origFileTime = date("YmdHis", $this->fileSystem->filemtime($source));
            $newFileTime = date("YmdHis", $this->fileSystem->filemtime($destination));
            if($newFileTime < $origFileTime): # Not using $opts['expire-time'] ??
                $isInCache = false;
            endif;
        endif;

        return $isInCache;
    }

    private function checkPath($path) {
        if (!($path instanceof ImagePath)) throw new InvalidArgumentException();
    }

    private function checkConfiguration($configuration) {
        if (!($configuration instanceof Configuration)) throw new InvalidArgumentException();
    }

}
