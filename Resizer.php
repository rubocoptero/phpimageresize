<?php

require 'FileSystem.php';
require 'ResizeCommand.php';

class Resizer {
    private $configuration;
    private $fileSystem;
    private $resizeCommand;

    public function __construct($configuration) {
        $this->checkConfiguration($configuration);
        $this->configuration = $configuration;
        $this->fileSystem = new FileSystem();
        $this->resizeCommand = new ResizeCommand($configuration);
    }

    public function injectFileSystem(FileSystem $fileSystem) {
        $this->fileSystem = $fileSystem;
    }

    public function resize ($image) {
        $this->checkImage($image);

        try {
            $sourcePath = $image->obtainFilePath(
                $this->configuration->obtainRemote(),
                $this->configuration->obtainCacheMinutes()
            );
        } catch (Exception $e) {
            return 'image not found';
        }

        $destinationPath = $this->composeNewPathFrom($sourcePath);

        $create = !$this->isInCache($destinationPath, $sourcePath);

        if($create == true):
            try {
                $this->resizeCommand->execute($sourcePath, $destinationPath);
            } catch (Exception $e) {
                return 'cannot resize the image';
            }
        endif;

        // The new path must be the return value of resizer resize

        $cacheFilePath = str_replace($_SERVER['DOCUMENT_ROOT'],'',$destinationPath);

        return $cacheFilePath;
    }

    public function composeNewPathFrom($currentPath) {
        if($this->configuration->obtainOutputFilename()) {
            $newPath = $this->configuration->obtainOutputFilename();
        } else {
            $newPath = $this->configuration->obtainCache() . $this->obtainNewFilename($currentPath);
        }

        return $newPath;
    }

    private function obtainNewFilename ($currentPath) {
        $filename = $this->fileSystem->md5_file($currentPath);
        $finfo = $this->fileSystem->pathinfo($currentPath);
        $extension = '.' . $finfo['extension'];

        return $filename . $this->composeFilenameSuffix() . $extension;
    }

    private function composeFilenameSuffix () {
        $opts = $this->configuration->asHash();
        $w = $this->configuration->obtainWidth();
        $h = $this->configuration->obtainHeight();
        $cropSignal = isset($opts['crop']) && $opts['crop'] == true ? "_cp" : "";
        $scaleSignal = isset($opts['scale']) && $opts['scale'] == true ? "_sc" : "";
        $widthSignal = !empty($w) ? '_w'.$w : '';
        $heightSignal = !empty($h) ? '_h'.$h : '';

        return $widthSignal.$heightSignal.$cropSignal.$scaleSignal;
    }

    private function isInCache($destinationPath, $sourcePath) {
        $isInCache = false;
        if(file_exists($destinationPath) == true):
            $isInCache = true;
            $sourceFileTime = date("YmdHis", filemtime($sourcePath));
            $destinationFileTime = date("YmdHis", filemtime($destinationPath));
            if($destinationFileTime < $sourceFileTime): # Not using $opts['expire-time'] ??
                $isInCache = false;
            endif;
        endif;

        return $isInCache;
    }
    
    private function checkImage($image) {
        if (!($image instanceof ImagePath)) throw new InvalidArgumentException();
    }

    private function checkConfiguration($configuration) {
        if (!($configuration instanceof Configuration)) throw new InvalidArgumentException();
    }
}