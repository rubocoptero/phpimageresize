<?php

class ImagePath {

    private $original;
    private $valid_http_protocols = array('http', 'https');
    private $fileSystem;

    public function __construct($url='') {
        $this->original = $this->sanitize($url);
        $this->fileSystem = new FileSystem();
    }

    public function injectFileSystem(FileSystem $fileSystem) {
        $this->fileSystem = $fileSystem;
    }

    public function isHttpProtocol() {
        return in_array($this->obtainScheme(), $this->valid_http_protocols);
    }

    public function obtainFileName() {
        $finfo = pathinfo($this->original);
        list($filename) = explode('?',$finfo['basename']);
        return $filename;
    }

    public function obtainSourceFilePath($remoteFolder, $cacheMinutes) {
        $imagePath = '';

        if($this->isHttpProtocol()):
            $filename = $this->obtainFileName();
            $local_filepath = $remoteFolder .$filename;
            $inCache = $this->isInCache($local_filepath, $cacheMinutes);

            if(!$inCache):
                $this->download($local_filepath);
            endif;
            $imagePath = $local_filepath;
        endif;

        if(!$this->fileSystem->file_exists($imagePath)):
            $imagePath = $_SERVER['DOCUMENT_ROOT'].$imagePath;
            if(!$this->fileSystem->file_exists($imagePath)):
                throw new RuntimeException();
            endif;
        endif;

        return $imagePath;
    }

    public function obtainDestinationFilePath($imagePath, $configuration) {
        $opts = $configuration->asHash();
        $w = $configuration->obtainWidth();
        $h = $configuration->obtainHeight();
        $filename = $this->fileSystem->md5_file($imagePath);
        $finfo = $this->fileSystem->pathinfo($imagePath);
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

    private function download($filePath) {
        $img = $this->fileSystem->file_get_contents($this->original);
        $this->fileSystem->file_put_contents($filePath,$img);
    }

    private function isInCache($filePath, $cacheMinutes) {
        $fileExists = $this->fileSystem->file_exists($filePath);
        $fileValid = $this->fileNotExpired($filePath, $cacheMinutes);

        return $fileExists && $fileValid;
    }

    private function fileNotExpired($filePath, $cacheMinutes) {
        $this->fileSystem->filemtime($filePath) < strtotime('+'. $cacheMinutes. ' minutes');
    }

    private function sanitize($path) {
        return urldecode($path);
    }

    private function obtainScheme() {
        if ($this->original == '') return '';
        $purl = parse_url($this->original);
        return $purl['scheme'];
    }


}