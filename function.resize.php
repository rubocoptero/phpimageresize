<?php

require 'ImagePath.php';
require 'Configuration.php';
require 'Resizer.php';

function resize($imagePath,$opts=null){
	$image = new ImagePath($imagePath);

    try {
        $configuration = new Configuration($opts);
    } catch (InvalidArgumentException $e) {
        return 'cannot resize the image';
    }

	$resizer = new Resizer($configuration);

	return $resizer->resize($image);
	
}
