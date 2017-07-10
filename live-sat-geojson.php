<?php
require_once('require/class.Connection.php');
require_once('require/class.Common.php');
require_once('require/class.Satellite.php');
date_default_timezone_set('UTC');
//$begintime = microtime(true);
$Satellite = new Satellite();
$Common = new Common();

if (isset($_GET['download'])) {
	if ($_GET['download'] == "true")
	{
		header('Content-disposition: attachment; filename="flightairmap-sat.json"');
	}
}
$history = urldecode(filter_input(INPUT_GET,'history',FILTER_SANITIZE_STRING));
header('Content-Type: text/javascript');

$begintime = microtime(true);
//$sqltime = round(microtime(true)-$begintime,2);

$spotter_array = array();
if (isset($_COOKIE['sattypes']) && $_COOKIE['sattypes'] != '') {
	$sattypes = explode(',',$_COOKIE['sattypes']);
	foreach ($sattypes as $sattype) {
		//$spotter_array = array_merge($Satellite->position_all_type($sattype,$timeb-$globalLiveInterval,$timeb),$spotter_array);
		$spotter_array = array_merge($Satellite->position_all_type($sattype),$spotter_array);
	}
}

if ((isset($_COOKIE['displayiss']) && $_COOKIE['displayiss'] == 'true') || !isset($_COOKIE['displayiss'])) {
	$spotter_array[] = $Satellite->position('ISS (ZARYA)');
	$spotter_array[] = $Satellite->position('TIANGONG 1');
	$spotter_array[] = $Satellite->position('TIANGONG-2');
}

//$spotter_array = array_unique($spotter_array,SORT_REGULAR);
//print_r($spotter_array);
$sqltime = round(microtime(true)-$begintime,2);

$output = '{"type":"FeatureCollection","features":[';
if (!empty($spotter_array) && is_array($spotter_array))
{
	$last_name = '';
	$coordinatearray = '';
	$timearray = array();
	foreach($spotter_array as $spotter_item)
	{
		$output_data = '';
		$output_data .= '{"type":"Feature","properties":{';
		$output_data .= '"famsatid":"'.$spotter_item['name'].'",';
		$output_data .= '"name":"'.urlencode($spotter_item['name']).'",';
		$output_data .= '"callsign":"'.$spotter_item['name'].'",';
		$output_data .= '"type":"satellite",';
		if ($spotter_item['name'] == 'ISS (ZARYA)') {
			$output_data .= '"aircraft_shadow":"iss.png",';
		} elseif ($spotter_item['name'] == 'TIANGONG 1' || $spotter_item['name'] == 'TIANGONG-2') {
			$output_data .= '"aircraft_shadow":"tiangong1.png",';
		} else {
			$output_data .= '"aircraft_shadow":"defaultsat.png",';
		}
		$output_data .= '"altitude":0,';
		$output_data .= '"sqt":'.$sqltime.',';
		$nextlatlon = $Satellite->position($spotter_item['name'],time()+$globalMapRefresh+20);
		$nextlat = $nextlatlon['latitude'];
		if (abs($nextlat-$spotter_item['latitude']) > 90) {
			if ($spotter_item['latitude'] < 0) $nexlat = -90;
			else $nexlat = 90;
		}
		$nextlon = $nextlatlon['longitude'];
		if (abs($nextlon-$spotter_item['longitude']) > 180) {
			if ($spotter_item['longitude'] < 0) $nextlon = -180;
			else $nextlon = 180;
		}
		$output_data .= '"nextlatlon":['.$nextlat.','.$nextlon.']},';
		//$output_data .= '"heading":"'.$Common->getHeading($spotter_item['latitude'],$spotter_item['longitude'],$nextlatlon['latitude'],$nextlatlon['longitude']).'",';
		$output_data .= '"geometry":{"type":"Point","coordinates":[';
		$output_data .= $spotter_item['longitude'].','.$spotter_item['latitude'];
		$output_data .= ']}},';
		$output .= $output_data;
		if ($history == $spotter_item['name']) {
			$spotter_history_array = $Satellite->position($spotter_item['name'],time()-6000,time());
			$spotter_history_array = array_reverse($spotter_history_array);
			$output_history = '{"type": "Feature","properties": {"callsign": "'.$spotter_item['name'].'","type": "history"},"geometry": {"type": "LineString","coordinates": [';
			foreach ($spotter_history_array as $key => $spotter_history) {
				if ((isset($previous_lon) && abs($previous_lon-$spotter_history['longitude']) > 180) || (isset($previous_lat) && abs($previous_lat-$spotter_history['latitude']) > 90)) {
					break;
				}
				$output_history .= '[';
				$output_history .=  $spotter_history['longitude'].', ';
				$output_history .=  $spotter_history['latitude'];
				$output_history .= '],';
				$previous_lon = $spotter_history['longitude'];
				$previous_lat = $spotter_history['latitude'];
			}
			$output_history = substr($output_history,0,-1);
			$output_history .= ']}},';
			$output .= $output_history;
		}
	}
}
if (isset($output_data)) $output = substr($output,0,-1);
$output .= ']}';
print $output;
?>
