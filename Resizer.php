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
                $this->doResize($sourcePath, $destinationPath);
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

    private function doResize($imagePath, $newPath) {
        $opts = $this->configuration->asHash();
        $w = $this->configuration->obtainWidth();
        $h = $this->configuration->obtainHeight();

        if(!empty($w) and !empty($h)):
            $cmd = $this->commandWithCrop($imagePath, $newPath, $this->configuration);
            if(true === $opts['scale']):
                $cmd = $this->commandWithScale($imagePath, $newPath, $this->configuration);
            endif;
        else:
            $cmd = $this->defaultShellCommand($this->configuration, $imagePath, $newPath);
        endif;

        $c = exec($cmd, $output, $return_code);
        if($return_code != 0) {
            error_log("Tried to execute : $cmd, return code: $return_code, output: " . print_r($output, true));
            throw new RuntimeException();
        }
    }

    private function defaultShellCommand($imagePath, $newPath) {
        $opts = $this->configuration->asHash();
        $w = $this->configuration->obtainWidth();
        $h = $this->configuration->obtainHeight();

        $command = $this->configuration->obtainConvertPath() ." " . escapeshellarg($imagePath) .

            " -thumbnail ". (!empty($h) ? 'x':'') . $w ."".
            (isset($opts['maxOnly']) && $opts['maxOnly'] == true ? "\>" : "") .

            " -quality ". escapeshellarg($opts['quality']) ." ". escapeshellarg($newPath);

        return $command;
    }

    private function isPanoramic($imagePath) {
        list($width,$height) = getimagesize($imagePath);
        return $width > $height;
    }

    private function composeResizeOptions($imagePath) {
        $opts = $this->configuration->asHash();
        $w = $this->configuration->obtainWidth();
        $h = $this->configuration->obtainHeight();

        $resize = "x".$h;

        $hasCrop = (true === $opts['crop']);

        if(!$hasCrop && $this->isPanoramic($imagePath)):
            $resize = $w;
        endif;

        if($hasCrop && !$this->isPanoramic($imagePath)):
            $resize = $w;
        endif;

        return $resize;
    }

    private function commandWithScale($imagePath, $newPath) {
        $opts = $this->configuration->asHash();
        $resize = $this->composeResizeOptions($imagePath);

        $cmd = $this->configuration->obtainConvertPath() ." ". escapeshellarg($imagePath) .
            " -resize ". escapeshellarg($resize) .
            " -quality ". escapeshellarg($opts['quality']) . " " . escapeshellarg($newPath);

        return $cmd;
    }

    private function commandWithCrop($imagePath, $newPath) {
        $opts = $this->configuration->asHash();
        $w = $this->configuration->obtainWidth();
        $h = $this->configuration->obtainHeight();
        $resize = $this->composeResizeOptions($imagePath);

        $cmd = $this->configuration->obtainConvertPath() ." ". escapeshellarg($imagePath) .

            " -resize ". escapeshellarg($resize) .

            " -size ". escapeshellarg($w ."x". $h) .
            " xc:". escapeshellarg($opts['canvas-color']) .
            " +swap -gravity center -composite" .

            " -quality " . escapeshellarg($opts['quality'])." ".escapeshellarg($newPath);

        return $cmd;
    }



    private function checkImage($image) {
        if (!($image instanceof ImagePath)) throw new InvalidArgumentException();
    }

    private function checkConfiguration($configuration) {
        if (!($configuration instanceof Configuration)) throw new InvalidArgumentException();
    }
}