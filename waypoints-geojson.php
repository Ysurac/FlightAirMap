<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');

if (isset($_GET['download']))
{
	header('Content-disposition: attachment; filename="waypoints.geojson"');
}
header('Content-Type: text/javascript');

//if (isset($_GET['coord'])) 
//{
	$coords = explode(',',$_GET['coord']);
	$spotter_array = Spotter::getAllWaypointsInfobyCoord($coords);
//} else {
//	$spotter_array = Spotter::getAllAirportInfobyCountry(array('France','Switzerland'));
//}
      
$output = '{"type": "FeatureCollection","features": [';
            
if (!empty($spotter_array))
{	  
	foreach($spotter_array as $spotter_item)
	{
		date_default_timezone_set('UTC');
		//waypoint plotting
		$output .= '{"type": "Feature",';
		    $output .= '"properties": {';
			$output .= '"ident": "'.$spotter_item['ident'].'",';
			$output .= '"control": "'.$spotter_item['control'].'",';
			$output .= '"usage": "'.$spotter_item['usage'].'",';
			$output .= '"popupContent": "'.$spotter_item['ident'].' : '.$spotter_item['control'].', '.$spotter_item['usage'].'",';
			if ($spotter_item['usage'] == 'RNAV') {
				$output .= '"icon": "images/flag_green.png"';
			} elseif ($spotter_item['usage'] == 'High Level') {
				$output .= '"icon": "images/flag_red.png"';
			} elseif ($spotter_item['usage'] == 'Low Level') {
				$output .= '"icon": "images/flag_yellow.png"';
			} elseif ($spotter_item['usage'] == 'High and Low Level') {
				$output .= '"icon": "images/flag_orange.png"';
			} elseif ($spotter_item['usage'] == 'Terminal') {
				$output .= '"icon": "images/flag_finish.png"';
			} else {
				$output .= '"icon": "images/flag_blue.png"';
			}
		    $output .= '},';
		    $output .= '"geometry": {';
			$output .= '"type": "Point",';
			$output .= '"coordinates": [';
			    $output .= $spotter_item['longitude'].', '.$spotter_item['latitude'];
			$output .= ']';
		    $output .= '}';
		$output .= '},';
	}
}
$output  = substr($output, 0, -1);
$output .= ']}';

print $output;

?>