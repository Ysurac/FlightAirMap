<?php
require_once('require/class.Connection.php');
require_once('require/class.Marine.php');
$Marine = new Marine();
if (isset($_GET['download']))
{
	header('Content-disposition: attachment; filename="races.geojson"');
}
header('Content-Type: text/javascript');

$race_id = filter_input(INPUT_GET,'race_id',FILTER_SANITIZE_NUMBER_INT);
if ($race_id != '') {
	$race_array = $Marine->getRace($race_id);
} else {
	$race_array = array();
}
      
$output = '{"type": "FeatureCollection","features": [';
if (!empty($race_array))
{	
	$course = json_decode($race_array['race_markers'],true);
	$i = 0;
	$f = count($course);
	foreach($course as $marker)
	{
		date_default_timezone_set('UTC');
		$output .= '{"type": "Feature",';
		$output .= '"properties": {';
		$output .= '"ref": "'.$marker['marine_race_id'].'",';
		$output .= '"raceid": "'.$race_array['race_id'].'",';
		$output .= '"race": "'.$race_array['race_name'].'",';
		$output .= '"name": "'.$marker['name'].'",';
		$output .= '"type": "'.$marker['type'].'",';
		if ($i == 0 || $i == 1) {
			$output .= '"icon": "images/tsk/tsk-start.png",';
		} elseif ($i == $f-1 || $i == $f-2) {
			$output .= '"icon": "images/tsk/tsk-finish.png",';
		} elseif ($marker['type'] == 4) {
			$output .= '"icon": "images/kardinalstonne.png",';
		} else {
			$output .= '"icon": "images/lateraltonne.png",';
		}
		$output .= '"stroke": "#f0f0f0",';
		$output .= '"stroke-width": 2';
		$output .= '},';
		$output .= '"geometry": {';
		$output .= '"type": "Point",';
		$output .= '"coordinates": ';
		$output .= '['.$marker['lon'].', '.$marker['lat'].']';
		$output .= '}';
		$output .= '},';
		$i++;
	}
/*
	$output .= '{"type": "Feature",';
	$output .= '"properties": {';
	$output .= '"stroke": "#f0f0f0",';
	$output .= '"stroke-width": 2';
	$output .= '},';
	$output .= '"geometry": {';
	$output .= '"type": "LineString",';
	$output .= '"coordinates": [';
	foreach($course as $marker)
	{
		date_default_timezone_set('UTC');
		$output .= '['.$marker['lon'].', '.$marker['lat'].'],';
	}
	$output  = substr($output, 0, -1);
	$output .= ']';
	$output .= '}';
	$output .= '},';
*/
}
$output  = substr($output, 0, -1);
$output .= ']}';

print $output;

?>