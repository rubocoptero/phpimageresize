<?php


class ResizeCommand {
    private $configuration;

    public function __construct($configuration) {
        $this->configuration = $configuration;
    }

    public function execute($imagePath, $newPath) {
        $opts = $this->configuration->asHash();
        $w = $this->configuration->obtainWidth();
        $h = $this->configuration->obtainHeight();

        if(!empty($w) and !empty($h)):
            if(true === $opts['scale']):
                $cmdParams = $this->obtainScaleParams($imagePath, $newPath, $this->configuration);
            else:
                $cmdParams = $this->obtainCropParams($imagePath, $newPath, $this->configuration);
            endif;
        else:
            $cmdParams = $this->obtainDefaultParams($this->configuration, $imagePath, $newPath);
        endif;

        $cmd = $this->obtainBeginning($imagePath) . $cmdParams . $this->obtainEnding($newPath);

        $c = exec($cmd, $output, $return_code);
        if($return_code != 0) {
            error_log("Tried to execute : $cmd, return code: $return_code, output: " . print_r($output, true));
            throw new RuntimeException();
        }
    }

    private function obtainDefaultParams($imagePath, $newPath) {
        $opts = $this->configuration->asHash();
        $w = $this->configuration->obtainWidth();
        $h = $this->configuration->obtainHeight();

        $command = " -thumbnail ". (!empty($h) ? 'x':'') . $w ."".
            (isset($opts['maxOnly']) && $opts['maxOnly'] == true ? "\>" : "");

        return $command;
    }

    private function obtainScaleParams($imagePath, $newPath) {
        $resize = $this->composeResizeOptions($imagePath);

        $cmd = " -resize ". escapeshellarg($resize);

        return $cmd;
    }

    private function obtainCropParams($imagePath, $newPath) {
        $opts = $this->configuration->asHash();
        $w = $this->configuration->obtainWidth();
        $h = $this->configuration->obtainHeight();
        $resize = $this->composeResizeOptions($imagePath);

        $cmd = " -resize ". escapeshellarg($resize) .
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

    private function isPanoramic($imagePath) {
        list($width,$height) = getimagesize($imagePath);
        return $width > $height;
    }

    private function obtainBeginning($imagePath)
    {
        return $this->configuration->obtainConvertPath() . " " . escapeshellarg($imagePath);
    }

    private function obtainEnding ($newPath)
    {
        " -quality ". escapeshellarg($this->configuration->obtainQuality()) .
        " " . escapeshellarg($newPath);
    }
}
