<?php

class ImagePath {

    private $path;
    private $fileSystem;

    private $valid_http_protocols = array('http', 'https');

    public function __construct($url='') {
        $this->path = $this->sanitize($url);
        $this->fileSystem = new FileSystem();
    }

    public function injectFileSystem(FileSystem $fileSystem) {
        $this->fileSystem = $fileSystem;
    }

    public function sanitizedPath() {
        return $this->path;
    }

    public function isHttpProtocol() {
        return in_array($this->obtainScheme(), $this->valid_http_protocols);
    }

    public function obtainFileName() {
        $finfo = pathinfo($this->path);
        list($filename) = explode('?',$finfo['basename']);
        return $filename;
    }

    // Should to be moved to ImagePath

    public function obtainFilePath($remoteFolder, $minutesToExpire) {
        $imagePath = '';

        if($this->isHttpProtocol()):
            $filename = $this->obtainFileName();
            $local_filepath = $remoteFolder .$filename;
            $inCache = $this->isInCache($local_filepath, $minutesToExpire);

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


    private function download($filePath) {
        $img = $this->fileSystem->file_get_contents($this->sanitizedPath());
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
        if ($this->path == '') return '';
        $purl = parse_url($this->path);
        return $purl['scheme'];
    }
}