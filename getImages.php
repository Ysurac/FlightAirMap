<?php

function hexToRGB($hex) {
	$hex = str_replace("#", "", $hex);
	$color = array();
	if (strlen($hex) == 3) {
	    $color['r'] = hexdec(substr($hex, 0, 1) . $r);
	    $color['g'] = hexdec(substr($hex, 1, 1) . $g);
	    $color['b'] = hexdec(substr($hex, 2, 1) . $b);
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
if (file_exists(dirname(__FILE__).DIRECTORY_SEPARATOR.'cache/'.$color.'-'.$filename)) {
    header('Content-type: image/png');
    readfile(dirname(__FILE__).DIRECTORY_SEPARATOR.'cache/'.$color.'-'.$filename);
    exit(0);
}
$original = dirname(__FILE__).DIRECTORY_SEPARATOR.'images/aircrafts/new/'.$filename;
if (!file_exists($original)) {
    echo "File not found";
}

if (extension_loaded('gd') && function_exists('gd_info')) {
    $image = imagecreatefrompng($original);
    $index = imagecolorexact($image,26,49,81);
    $c = hexToRGB($color);
    imagecolorset($image,$index,$c['r'],$c['g'],$c['b']);
/*
    $ig = imagecolorat($image, 0, 0);
    imagecolortransparent($image, $ig);
  */

    header('Content-type: image/png');
    imagealphablending($image, false);
    imagesavealpha($image, true);
    imagepng($image);
    if (is_writable('cache')) {
        imagepng($image,dirname(__FILE__).DIRECTORY_SEPARATOR.'cache/'.$color.'-'.$filename);
    }
    imagedestroy($image);
} else {
    header('Content-type: image/png');
    if ($color == 'FF0000') readfile(dirname(__FILE__).DIRECTORY_SEPARATOR.'images/aircrafts/selected/'.$filename);
    else readfile(dirname(__FILE__).DIRECTORY_SEPARATOR.'images/aircrafts/'.$filename);
}
?>