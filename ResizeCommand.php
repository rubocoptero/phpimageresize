<?php


class ResizeCommand {
    private $configuration;

    public function __construct($configuration) {
        $this->configuration = $configuration;
    }

    public function execute($sourcePath, $destinationPath) {
        $opts = $this->configuration->asHash();

        if($this->configuration->hasWidthAndHeight()):
            if(true === $opts['scale']):
                $cmdParams = $this->obtainScaleParams($sourcePath);
            else:
                $cmdParams = $this->obtainCropParams($sourcePath);
            endif;
        else:
            $cmdParams = $this->obtainDefaultParams();
        endif;

        $cmd = $this->obtainBeginning($sourcePath) . $cmdParams . $this->obtainEnding($destinationPath);

        $c = exec($cmd, $output, $return_code);
        if($return_code != 0) {
            error_log("Tried to execute : $cmd, return code: $return_code, output: " . print_r($output, true));
            throw new RuntimeException();
        }
    }

    private function obtainDefaultParams() {
        $opts = $this->configuration->asHash();
        $w = $this->configuration->obtainWidth();
        $h = $this->configuration->obtainHeight();

        $command = " -thumbnail ". (!empty($h) ? 'x':'') . $w ."".
            (isset($opts['maxOnly']) && $opts['maxOnly'] == true ? "\>" : "");

        return $command;
    }

    private function obtainScaleParams($imagePath) {
        $resize = $this->composeResizeOptions($imagePath);

        return " -resize ". escapeshellarg($resize);
    }

    private function obtainCropParams($imagePath) {
        $opts = $this->configuration->asHash();
        $w = $this->configuration->obtainWidth();
        $h = $this->configuration->obtainHeight();

        $cmd = obtainScaleParams($imagePath) .
            " -size ". escapeshellarg($w ."x". $h) .
            " xc:". escapeshellarg($opts['canvas-color']) .
            " +swap -gravity center -composite";

        return $cmd;
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

    // Likely should go to Image
    private function isPanoramic($path) {
        list($width,$height) = getimagesize($path);
        return $width > $height;
    }

    private function obtainBeginning($sourcePath)
    {
        return $this->configuration->obtainConvertPath() . " " . escapeshellarg($sourcePath);
    }

    private function obtainEnding ($destinationPath)
    {
        return " -quality ". escapeshellarg($this->configuration->obtainQuality()) .
        " " . escapeshellarg($destinationPath);
    }
}
