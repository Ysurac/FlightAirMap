<?php

function hexToRGB($hex) {
	$hex = str_replace("#", "", $hex);
	$color = array();
	if (strlen($hex) == 3) {
	    $color['r'] = hexdec(substr($hex, 0, 1) . substr($hex,0,1));
	    $color['g'] = hexdec(substr($hex, 1, 1) . substr($hex,1,1));
	    $color['b'] = hexdec(substr($hex, 2, 1) . substr($hex,2,1));
	} else if (strlen($hex) == 6) {
	    $color['r'] = hexdec(substr($hex, 0, 2));
	    $color['g'] = hexdec(substr($hex, 2, 2));
	    $color['b'] = hexdec(substr($hex, 4, 2));
	}
	return $color;
}


if (!isset($_GET['color']) || $_GET['color'] == '' || !preg_match('/^([a-fA-F0-9]){3}(([a-fA-F0-9]){3})?\b/',$_GET['color'])) { 
    exit(0);
}
$color = $_GET['color'];
if (!isset($_GET['filename']) || !preg_match('/^[a-z0-9-_]+\.png$/', strtolower($_GET['filename']))) {
    echo "Incorrect filename";
    exit(0);
}
$filename = $_GET['filename'];
if (file_exists(dirname(__FILE__).'/cache/'.$color.'-'.$filename) && is_readable(dirname(__FILE__).'/cache/'.$color.'-'.$filename)) {
    header('Content-type: image/png');
    readfile(dirname(__FILE__).'/cache/'.$color.'-'.$filename);
    exit(0);
}
if (isset($_GET['tracker'])) {
	$original = dirname(__FILE__).'/images/vehicules/color/'.$filename;
	if (!file_exists($original)) {
		$original = dirname(__FILE__).'/images/vehicules/'.$filename;
	}
} elseif (isset($_GET['marine'])) {
	//$original = dirname(__FILE__).DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'vehicules'.DIRECTORY_SEPARATOR.$filename;
	//$original = dirname(__FILE__).'/images/vehicules/color/'.$filename;
	$original = dirname(__FILE__).'/images/marine/'.$filename;
} elseif (isset($_GET['satellite'])) {
	//$original = dirname(__FILE__).DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'vehicules'.DIRECTORY_SEPARATOR.$filename;
	$original = dirname(__FILE__).'/images/satellites/'.$filename;
} else {
	$original = dirname(__FILE__).'/images/aircrafts/new/'.$filename;
}
if (!file_exists($original)) {
    echo "File not found";
}

if (extension_loaded('gd') && function_exists('gd_info')) {
    $image = imagecreatefrompng($original);
    $index = imagecolorexact($image,26,49,81);
    if ($index < 0) {
	$index = imagecolorexact($image,25,49,79);
    }
    if ($index < 0) {
	$index = imagecolorexact($image,0,0,0);
    }
    $c = hexToRGB($color);
    imagecolorset($image,$index,$c['r'],$c['g'],$c['b']);
 /*
    $ig = imagecolorat($image, 0, 0);
    imagecolortransparent($image, $ig);
   */

    header('Content-type: image/png');
    if (isset($_GET['resize']) && function_exists('imagecopyresampled')) {
	$resize = filter_input(INPUT_GET,'resize',FILTER_SANITIZE_NUMBER_INT);
	$newimg = imagecreatetruecolor($resize,$resize);
        imagealphablending($newimg, false);
	imagesavealpha($newimg, true);
	imagecopyresampled($newimg,$image,0,0,0,0,15,15,imagesx($image),imagesy($image));
	if (isset($_GET['heading'])) {
    	    $heading = filter_input(INPUT_GET,'heading',FILTER_SANITIZE_NUMBER_INT);
    	    $rotation = imagerotate($newimg,$heading,imageColorAllocateAlpha($newimg,0,0,0,127));
    	    imagealphablending($rotation, false);
	    imagesavealpha($rotation, true);
    	    imagepng($rotation);
    	    imagedestroy($newimg);
    	    imagedestroy($image);
    	    imagedestroy($rotation);
	
	} else {
    	    imagepng($newimg);
    	    imagedestroy($newimg);
    	    imagedestroy($image);
        }
    } else {
	imagealphablending($image, false);
        imagesavealpha($image, true);
	imagepng($image);
	imagepng($image);
	if (is_writable(dirname(__FILE__).'/cache')) {
    	    imagepng($image,dirname(__FILE__).'/cache/'.$color.'-'.$filename);
	}
        imagedestroy($image);
    }
} else {
    header('Content-type: image/png');
    if (isset($_GET['tracker'])) {
        readfile(dirname(__FILE__).'/images/vehicules/'.$filename);
    } elseif (isset($_GET['marine'])) {
        readfile(dirname(__FILE__).'/images/vehicules/'.$filename);
    } elseif (isset($_GET['satellite'])) {
        readfile(dirname(__FILE__).'/images/satellites/'.$filename);
    } else {
        if ($color == 'FF0000') readfile(dirname(__FILE__).'/images/aircrafts/selected/'.$filename);
	else readfile(dirname(__FILE__).'/images/aircrafts/'.$filename);
    }
}
?>