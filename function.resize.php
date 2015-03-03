<?php

require 'ImagePath.php';
require 'Configuration.php';
require 'Resizer.php';

function sanitize($path) {
	return urldecode($path);
}

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


function defaultShellCommand($configuration, $imagePath, $newPath) {
	$opts = $configuration->asHash();
	$w = $configuration->obtainWidth();
	$h = $configuration->obtainHeight();

	$command = $configuration->obtainConvertPath() ." " . escapeshellarg($imagePath) .
	" -thumbnail ". (!empty($h) ? 'x':'') . $w ."".
	(isset($opts['maxOnly']) && $opts['maxOnly'] == true ? "\>" : "") .
	" -quality ". escapeshellarg($opts['quality']) ." ". escapeshellarg($newPath);

	return $command;
}

function isPanoramic($imagePath) {
	list($width,$height) = getimagesize($imagePath);
	return $width > $height;
}

function composeResizeOptions($imagePath, $configuration) {
	$opts = $configuration->asHash();
	$w = $configuration->obtainWidth();
	$h = $configuration->obtainHeight();

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

function commandWithScale($imagePath, $newPath, $configuration) {
	$opts = $configuration->asHash();
	$resize = composeResizeOptions($imagePath, $configuration);

	$cmd = $configuration->obtainConvertPath() ." ". escapeshellarg($imagePath) ." -resize ". escapeshellarg($resize) .
		" -quality ". escapeshellarg($opts['quality']) . " " . escapeshellarg($newPath);

	return $cmd;
}

function commandWithCrop($imagePath, $newPath, $configuration) {
	$opts = $configuration->asHash();
	$w = $configuration->obtainWidth();
	$h = $configuration->obtainHeight();
	$resize = composeResizeOptions($imagePath, $configuration);

	$cmd = $configuration->obtainConvertPath() ." ". escapeshellarg($imagePath) ." -resize ". escapeshellarg($resize) .
		" -size ". escapeshellarg($w ."x". $h) .
		" xc:". escapeshellarg($opts['canvas-color']) .
		" +swap -gravity center -composite -quality ". escapeshellarg($opts['quality'])." ".escapeshellarg($newPath);

	return $cmd;
}

function doResize($imagePath, $newPath, $configuration) {
	$opts = $configuration->asHash();
	$w = $configuration->obtainWidth();
	$h = $configuration->obtainHeight();

	if(!empty($w) and !empty($h)):
		$cmd = commandWithCrop($imagePath, $newPath, $configuration);
		if(true === $opts['scale']):
			$cmd = commandWithScale($imagePath, $newPath, $configuration);
		endif;
	else:
		$cmd = defaultShellCommand($configuration, $imagePath, $newPath);
	endif;

	$c = exec($cmd, $output, $return_code);
	if($return_code != 0) {
		error_log("Tried to execute : $cmd, return code: $return_code, output: " . print_r($output, true));
		throw new RuntimeException();
	}
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
		$sourcePath = $resizer.resize($image);
	} catch (Exception $e) {
		return 'image not found';
	}
	$destinationPath = $image->obtainDestinationFilePath($sourcePath, $configuration);

    $create = !isInCache($destinationPath, $sourcePath);

	if($create == true):
		try {
			doResize($sourcePath, $destinationPath, $configuration);
		} catch (Exception $e) {
			return 'cannot resize the image';
		}
	endif;

	// The new path must be the return value of resizer resize

	$cacheFilePath = str_replace($_SERVER['DOCUMENT_ROOT'],'',$destinationPath);

	return $cacheFilePath;

}
