<?php

class Configuration {
    const CACHE_PATH = './cache/';
    const REMOTE_PATH = './cache/remote/';

    const CACHE_KEY = 'cacheFolder';
    const REMOTE_KEY = 'remoteFolder';
    const CACHE_MINUTES_KEY = 'cache_http_minutes';
    const WIDTH_KEY = 'width';
    const HEIGHT_KEY = 'height';
    const OUTPUT_KEY = 'output-filename';
    const QUALITY_KEY = 'quality';

    const CONVERT_PATH = 'convert';

    private $opts;

    public function __construct($opts=array()) {
        $sanitized= $this->sanitize($opts);

        $this->validate($opts);

        $defaults = array(
            'crop' => false,
            'scale' => 'false',
            'thumbnail' => false,
            'maxOnly' => false,
            'canvas-color' => 'transparent',
            self::CACHE_KEY => self::CACHE_PATH,
            self::REMOTE_KEY => self::REMOTE_PATH,
            self::QUALITY_KEY => 90,
            'cache_http_minutes' => 20,
        );

        $this->opts = array_merge($defaults, $sanitized);
    }

    public function asHash() {
        return $this->opts;
    }

    public function obtainCache() {
        return $this->opts[self::CACHE_KEY];
    }

    public function obtainRemote() {
        return $this->opts[self::REMOTE_KEY];
    }

    public function obtainConvertPath() {
        return self::CONVERT_PATH;
    }

    public function obtainWidth() {
        return $this->opts[self::WIDTH_KEY];
    }

    public function obtainHeight() {
        return $this->opts[self::HEIGHT_KEY];
    }

    public function obtainCacheMinutes() {
        return $this->opts[self::CACHE_MINUTES_KEY];
    }

    public function obtainOutputFilename() {
        return $this->opts[self::OUTPUT_KEY];
    }

    public function obtainQuality() {
        return $this->opts[self::QUALITY_KEY];
    }

    private function validate ($opts) {
        if (empty($opts[self::WIDTH_KEY]) &&
            empty($opts[self::HEIGHT_KEY]) &&
            empty($opts[self::OUTPUT_KEY])
        ) {
            throw new InvalidArgumentException();
        }
    }

    private function hasWidthAndHeight()
    {
        $w = $this->obtainWidth();
        $h = $this->obtainHeight();

        return !empty($w) and !empty($h);
    }

    private function sanitize($opts)
    {
        if ($opts == null) return array();

        return $opts;
    }
}