<?php
require('require/class.Connection.php');
require('require/class.Source.php');

if (isset($_GET['download']))
{
	header('Content-disposition: attachment; filename="locations.geojson"');
}
header('Content-Type: text/javascript');

if (!isset($globalDemo)) {
	if (isset($_GET['coord'])) 
	{
		$coords = explode(',',$_GET['coord']);
//		$spotter_array = Source::getAllLocationInfobyCoord($coords);
		$spotter_array = Source::getAllLocationInfo();
	} else {
		$spotter_array = Source::getAllLocationInfo();
	}
}

$output = '{"type": "FeatureCollection","features": [';
if (!empty($spotter_array))
{
	foreach($spotter_array as $spotter_item)
	{
		date_default_timezone_set('UTC');
		//waypoint plotting
		$output .= '{"type": "Feature",';
		    $output .= '"properties": {';
			$output .= '"name": "'.$spotter_item['name'].'",';
			$output .= '"city": "'.$spotter_item['city'].'",';
			$output .= '"country": "'.$spotter_item['country'].'",';
			$output .= '"altitude": "'.$spotter_item['altitude'].'",';
			$output .= '"popupContent": "'.$spotter_item['name'].' : '.$spotter_item['city'].', '.$spotter_item['country'].'",';
			$output .= '"icon": "'.$globalURL.'/images/antenna.png",';
			$output .= '"type": "'.$spotter_item['type'].'",';
			$output .= '"image_thumb": "'.$spotter_item['image_thumb'].'"';
		    $output .= '},';
		    $output .= '"geometry": {';
			$output .= '"type": "Point",';
			$output .= '"coordinates": [';
			    $output .= $spotter_item['longitude'].', '.$spotter_item['latitude'];
			$output .= ']';
		    $output .= '}';
		$output .= '},';
	}
	$output  = substr($output, 0, -1);
}
$output .= ']}';

print $output;

?>