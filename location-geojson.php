<?php
require_once('require/class.Connection.php');
require_once('require/class.Source.php');
$Source = new Source();

if (isset($_GET['download']))
{
	header('Content-disposition: attachment; filename="locations.geojson"');
}
header('Content-Type: text/javascript');
$spotter_array = array();
if (isset($_GET['coord'])) 
{
	$coords = explode(',',$_GET['coord']);
	if ((isset($_COOKIE['show_GroundStation']) && $_COOKIE['show_GroundStation'] == 'true') 
	    || (!isset($_COOKIE['show_GroundStation']) && (isset($globalMapGroundStation) && $globalMapGroundStation === TRUE))) {
		//$spotter_array = $Source->getAllLocationInfo();
		$spotter_array = array_merge($spotter_array,$Source->getLocationInfoByType('gs',$coords));
	}
	if ((isset($_COOKIE['show_WeatherStation']) && $_COOKIE['show_WeatherStation'] == 'true') 
	    || (!isset($_COOKIE['show_WeatherStation']) && (isset($globalMapWeatherStation) && $globalMapWeatherStation === TRUE))) {
		$spotter_array = array_merge($spotter_array,$Source->getLocationInfoByType('wx',$coords));
	}
	if ((isset($_COOKIE['show_Lightning']) && $_COOKIE['show_Lightning'] == 'true') 
	    || (!isset($_COOKIE['show_Lightning']) && (isset($globalMapLightning) && $globalMapLightning === TRUE))) {
		$spotter_array = array_merge($spotter_array,$Source->getLocationInfoByType('lightning',$coords));
	}
	if ((isset($_COOKIE['show_Fires']) && $_COOKIE['show_Fires'] == 'true') 
	    || (!isset($_COOKIE['show_Fires']) && (isset($globalMapFires) && $globalMapFires === TRUE))) {
		$spotter_array = array_merge($spotter_array,$Source->getLocationInfoByType('fires',$coords,true));
	}
	if (!isset($globalDemo)) {
		$spotter_array = array_merge($spotter_array,$Source->getLocationInfoByType(''));
	}
} else {
	if ((isset($_COOKIE['show_GroundStation']) && $_COOKIE['show_GroundStation'] == 'true') 
	    || (!isset($_COOKIE['show_GroundStation']) && (isset($globalMapGroundStation) && $globalMapGroundStation === TRUE))) {
		//$spotter_array = $Source->getAllLocationInfo();
		$spotter_array = array_merge($spotter_array,$Source->getLocationInfoByType('gs'));
	}
	if ((isset($_COOKIE['show_WeatherStation']) && $_COOKIE['show_WeatherStation'] == 'true') 
	    || (!isset($_COOKIE['show_WeatherStation']) && (isset($globalMapWeatherStation) && $globalMapWeatherStation === TRUE))) {
		$spotter_array = array_merge($spotter_array,$Source->getLocationInfoByType('wx'));
	}
	if ((isset($_COOKIE['show_Lightning']) && $_COOKIE['show_Lightning'] == 'true') 
	    || (!isset($_COOKIE['show_Lightning']) && (isset($globalMapLightning) && $globalMapLightning === TRUE))) {
		$spotter_array = array_merge($spotter_array,$Source->getLocationInfoByType('lightning'));
	}
	if ((isset($_COOKIE['show_Fire']) && $_COOKIE['show_Fire'] == 'true') 
	    || (!isset($_COOKIE['show_Fire']) && (isset($globalMapFires) && $globalMapFires === TRUE))) {
		$spotter_array = array_merge($spotter_array,$Source->getLocationInfoByType('fires',array(),true));
	}
	if (!isset($globalDemo)) {
		$spotter_array = array_merge($spotter_array,$Source->getLocationInfoByType(''));
	}
}


$output = '{"type": "FeatureCollection","features": [';
if (!empty($spotter_array) && count($spotter_array) > 0)
{
	foreach($spotter_array as $spotter_item)
	{
		date_default_timezone_set('UTC');
		$output .= '{"type": "Feature",';
		$output .= '"properties": {';
		$output .= '"id": "'.$spotter_item['id'].'",';
		$output .= '"location_id": "'.$spotter_item['location_id'].'",';
		$output .= '"name": "'.$spotter_item['name'].'",';
		$output .= '"city": "'.$spotter_item['city'].'",';
		$output .= '"country": "'.$spotter_item['country'].'",';
		$output .= '"altitude": "'.$spotter_item['altitude'].'",';
		if ($spotter_item['name'] != '' && $spotter_item['city'] != '' && $spotter_item['country'] != '') {
			$output .= '"popupContent": "'.$spotter_item['name'].' : '.$spotter_item['city'].', '.$spotter_item['country'].'",';
		} elseif ($spotter_item['location_id'] != '') {
			$output .= '"popupContent": "'.$spotter_item['location_id'].'",';
		}
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
		$output .= '"coordinates": ['.$spotter_item['longitude'].', '.$spotter_item['latitude'].']';
		$output .= '}';
		$output .= '},';
	}
	$output  = substr($output, 0, -1);
}
$output .= ']}';
print $output;
?>