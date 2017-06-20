<?php
require_once('require/class.Connection.php');
require_once('require/class.Source.php');
$Source = new Source();

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
		if ((isset($_COOKIE['show_GroundStation']) && $_COOKIE['show_GroundStation'] == 'true') 
		    || (!isset($_COOKIE['show_GroundStation']) && (!isset($globalMapGroundStation) || $globalMapGroundStation === TRUE))) {
			$spotter_array = $Source->getAllLocationInfo();
		} else {
			$spotter_array = $Source->getLocationInfoByType('');
		}
	} else {
		if ((isset($_COOKIE['show_GroundStation']) && $_COOKIE['show_GroundStation'] == 'true') 
		    || (!isset($_COOKIE['show_GroundStation']) && (!isset($globalMapGroundStation) || $globalMapGroundStation === TRUE))) {
			$spotter_array = $Source->getAllLocationInfo();
		} else {
			$spotter_array = $Source->getLocationInfoByType('');
		}
	}
}

$output = '{"type": "FeatureCollection","features": [';
if (!empty($spotter_array) && count($spotter_array) > 0)
{
	foreach($spotter_array as $spotter_item)
	{
		date_default_timezone_set('UTC');
		//waypoint plotting
		$output .= '{"type": "Feature",';
		    $output .= '"properties": {';
			$output .= '"id": "'.$spotter_item['id'].'",';
			$output .= '"location_id": "'.$spotter_item['location_id'].'",';
			$output .= '"name": "'.$spotter_item['name'].'",';
			$output .= '"city": "'.$spotter_item['city'].'",';
			$output .= '"country": "'.$spotter_item['country'].'",';
			$output .= '"altitude": "'.$spotter_item['altitude'].'",';
			if ($spotter_item['name'] != '' && $spotter_item['city'] != '' && $spotter_item['country'] != '')
				$output .= '"popupContent": "'.$spotter_item['name'].' : '.$spotter_item['city'].', '.$spotter_item['country'].'",';
			elseif ($spotter_item['location_id'] != '')
				$output .= '"popupContent": "'.$spotter_item['location_id'].'",';
			$output .= '"icon": "'.$globalURL.'/images/'.$spotter_item['logo'].'",';
			$output .= '"type": "'.$spotter_item['type'].'",';
			if ($spotter_item['type'] == 'wx') {
				$weather = json_decode($spotter_item['description'],true);
				if (isset($weather['temp'])) $output.= '"temp": "'.$weather['temp'].'",';
			}
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