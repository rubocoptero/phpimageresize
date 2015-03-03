<?php

require 'ImagePath.php';
require 'Configuration.php';
require 'Resizer.php';

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
