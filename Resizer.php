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

    public function resize ($originalPath) {
        $opts = $this->configuration->asHash();

        $image = new ImagePath($originalPath);
        $source = $image->obtainSourceFilePath($opts['remoteFolder'], $opts['cache_http_minutes']);
        $destination = $image->obtainDestinationFilePath($source, $this->configuration);

        if ($this->isInCache($source, $destination) == false):
            try {
                $this->resizeImage($source, $destination);
            } catch (Exception $e) {
                return 'cannot resize the image';
            }
        endif;

        $resizedImage = str_replace($_SERVER['DOCUMENT_ROOT'],'',$destination);

        return $resizedImage;
    }

    private function isInCache($source, $destination) {
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

    private function resizeImage($sourcePath, $destinationPath) {
        $opts = $this->configuration->asHash();
        $w = $this->configuration->obtainWidth();
        $h = $this->configuration->obtainHeight();

        if(!empty($w) and !empty($h)):
            $cmd = commandWithCrop($sourcePath, $destinationPath);
            if(true === $opts['scale']):
                $cmd = commandWithScale($sourcePath, $destinationPath);
            endif;
        else:
            $cmd = defaultShellCommand($sourcePath, $destinationPath);
        endif;

        $c = exec($cmd, $output, $return_code);
        if($return_code != 0) {
            error_log("Tried to execute : $cmd, return code: $return_code, output: " . print_r($output, true));
            throw new RuntimeException();
        }
    }

    private function commandWithCrop($imagePath, $newPath) {
        $opts = $this->configuration->asHash();
        $w = $this->configuration->obtainWidth();
        $h = $this->configuration->obtainHeight();
        $resize = composeResizeOptions($imagePath);

        $cmd = $this->configuration->obtainConvertPath() ." ". escapeshellarg($imagePath) ." -resize ". escapeshellarg($resize) .
            " -size ". escapeshellarg($w ."x". $h) .
            " xc:". escapeshellarg($opts['canvas-color']) .
            " +swap -gravity center -composite -quality ". escapeshellarg($opts['quality'])." ".escapeshellarg($newPath);

        return $cmd;
    }

    private function commandWithScale($imagePath, $newPath) {
        $opts = $this->configuration->asHash();
        $resize = composeResizeOptions($imagePath);

        $cmd = $this->configuration->obtainConvertPath() ." ". escapeshellarg($imagePath) ." -resize ". escapeshellarg($resize) .
            " -quality ". escapeshellarg($opts['quality']) . " " . escapeshellarg($newPath);

        return $cmd;
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

    private function composeResizeOptions($imagePath) {
        $opts = $this->configuration->asHash();
        $w = $this->configuration->obtainWidth();
        $h = $this->configuration->obtainHeight();

        $resize = "x".$h;

        $hasCrop = (true === $opts['crop']);

        if(!$hasCrop && isPanoramic($imagePath)):
            $resize = $w;
        endif;

        if($hasCrop && !isPanoramic($imagePath)):
            $resize = $w;
        endif;

        return $resize;
    }

    private function isPanoramic($imagePath) {
        list($width,$height) = getimagesize($imagePath);
        return $width > $height;
    }

    private function checkConfiguration($configuration) {
        if (!($configuration instanceof Configuration)) throw new InvalidArgumentException();
    }

}
