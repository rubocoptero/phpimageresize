<?php

require 'ImagePath.php';
require 'Configuration.php';
require 'Resizer.php';

function isInCache($path, $imagePath) {
    $isInCache = false;
    if(file_exists($path) == true):
        $isInCache = true;
        $origFileTime = date("YmdHis",filemtime($imagePath));
        $newFileTime = date("YmdHis",filemtime($path));
        if($newFileTime < $origFileTime): # Not using $opts['expire-time'] ??
            $isInCache = false;
        endif;
    endif;

    return $isInCache;
}

function resize($originalPath,$opts=null){
	$image = new ImagePath($originalPath);
    try {
        $configuration = new Configuration($opts);
    } catch (InvalidArgumentException $e) {
        return 'cannot resize the image';
    }


    $resizer = new Resizer($configuration);

	try {
		$resizedImage = $resizer.resize($image);
	} catch (Exception $e) {
		return 'image not found';
	}

	return $resizedImage;

}
