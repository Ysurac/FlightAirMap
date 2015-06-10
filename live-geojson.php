<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');
require('require/class.SpotterLive.php');
require('require/class.SpotterArchive.php');

if (isset($_GET['download'])) {
    if ($_GET['download'] == "true")
    {
	header('Content-disposition: attachment; filename="flightairmap.json"');
    }
}
header('Content-Type: text/javascript');

$from_archive = false;
if (isset($_GET['coord'])) {
	$coord = explode(',',$_GET['coord']);
	$spotter_array = SpotterLive::getLiveSpotterDatabyCoord($coord);
} else {
	$spotter_array = SpotterLive::getLiveSpotterData();
}
if (isset($_GET['ident'])) {
	$ident = $_GET['ident'];
	$spotter_array = SpotterLive::getLastLiveSpotterDataByIdent($ident);
	if (empty($spotter_array)) {
		$from_archive = true;
		$spotter_array = SpotterArchive::getLastArchiveSpotterDataByIdent($ident);
	}
}
if (isset($_GET['flightaware_id'])) {
	$flightaware_id = $_GET['flightaware_id'];
	$spotter_array = SpotterLive::getLastLiveSpotterDataById($flightaware_id);
	if (empty($spotter_array)) {
		$from_archive = true;
		$spotter_array = SpotterArchive::getLastArchiveSpotterDataById($flightaware_id);
	}
}

if (!empty($spotter_array)) {
	$flightcnt = SpotterLive::getLiveSpotterCount();
	if ($flightcnt == '') $flightcnt = 0;
} else $flightcnt = 0;

$output = '{';
	$output .= '"type": "FeatureCollection",';
		$output .= '"features": [';

		if (!empty($spotter_array))
		{
			//print_r($spotter_array);
			foreach($spotter_array as $spotter_item)
			{
				date_default_timezone_set('UTC');

				if (isset($spotter_item['image_thumbnail']) && $spotter_item['image_thumbnail'] != "")
				{
					$image = $spotter_item['image_thumbnail'];
				} else {
					$image = "images/placeholder_thumb.png";
				}

				//waypoint plotting
                /*
				$output .= '{';
					$output .= '"type": "Feature",';
						$output .= '"properties": {';
                            $output .= '"flightaware_id": "'.$spotter_item['flightaware_id'].'",';
							$output .= '"callsign": "'.$spotter_item['ident'].'",';
							$output .= '"registration": "'.$spotter_item['registration'].'",';
							$output .= '"aircraft_name": "'.$spotter_item['aircraft_name'].' ('.$spotter_item['aircraft_type'].')",';
							$output .= '"airline_name": "'.$spotter_item['airline_name'].'",';
							$output .= '"departure_airport_code": "'.$spotter_item['departure_airport'].'",';
							$output .= '"departure_airport": "'.$spotter_item['departure_airport_city'].', '.$spotter_item['departure_airport_country'].'",';
							$output .= '"arrival_airport_code": "'.$spotter_item['arrival_airport'].'",';
							$output .= '"arrival_airport": "'.$spotter_item['arrival_airport_city'].', '.$spotter_item['arrival_airport_country'].'",';
							$output .= '"date_update": "'.date("M j, Y, g:i a T", strtotime($spotter_item['date_iso_8601'])).'",';
							$output .= '"latitude": "'.$spotter_item['latitude'].'",';
							$output .= '"longitude": "'.$spotter_item['longitude'].'",';
							$output .= '"ground_speed": "'.$spotter_item['ground_speed'].'",';
							$output .= '"altitude": "'.$spotter_item['altitude'].'",';
							$output .= '"heading": "'.$spotter_item['heading'].'",';
							$output .= '"image": "'.$image.'",';
							$output .= '"type": "route"';
						$output .= '},';
						$output .= '"geometry": {';
							$output .= '"type": "LineString",';
								$output .= '"coordinates": [';
									$waypoint_pieces = explode(' ', $spotter_item['waypoints']);
									$waypoint_pieces = array_chunk($waypoint_pieces, 2);

									foreach ($waypoint_pieces as $waypoint_coordinate)
									{
										$output .= '[';
													$output .=  $waypoint_coordinate[1].', ';
													$output .=  $waypoint_coordinate[0];
										$output .= '],';

									}
									$output = substr($output, 0, -1);
								$output .= ']';
							$output .= '}';
				$output .= '},';
                */

				//location of aircraft
//				print_r($spotter_item);
				$output .= '{';
					$output .= '"type": "Feature",';
						$output .= '"properties": {';
							$output .= '"flightaware_id": "'.$spotter_item['flightaware_id'].'",';
							$output .= '"flight_cnt": "'.$flightcnt.'",';
							//$output .= '"flight_cnt": "'.$spotter_item['nb'].'",';
							$output .= '"callsign": "'.$spotter_item['ident'].'",';
							$output .= '"registration": "'.$spotter_item['registration'].'",';
						if (isset($spotter_item['aircraft_name']) && isset($spotter_item['aircraft_type'])) {
							$output .= '"aircraft_name": "'.$spotter_item['aircraft_name'].' ('.$spotter_item['aircraft_type'].')",';
							$output .= '"aircraft_wiki": "http://'.strtolower($globalLanguage).'.wikipedia.org/wiki/'.urlencode(str_replace(' ','_',$spotter_item['aircraft_name'])).'",';
						} elseif (isset($spotter_item['aircraft_type'])) {
							$output .= '"aircraft_name": "NA ('.$spotter_item['aircraft_type'].')",';
						} else {
							$output .= '"aircraft_name": "NA",';
						}
						$output .= '"aircraft_shadow": "'.$spotter_item['aircraft_shadow'].'",';
						if (isset($spotter_item['airline_name'])) {
							$output .= '"airline_name": "'.$spotter_item['airline_name'].'",';
						} else {
							$output .= '"airline_name": "NA",';
						}
						if (isset($spotter_item['departure_airport'])) {
							$output .= '"departure_airport_code": "'.$spotter_item['departure_airport'].'",';
						}
						if (isset($spotter_item['departure_airport_city'])) {
							$output .= '"departure_airport": "'.$spotter_item['departure_airport_city'].', '.$spotter_item['departure_airport_country'].'",';
						}
						if (isset($spotter_item['departure_airport_time'])) {
							$output .= '"departure_airport_time": "'.$spotter_item['departure_airport_time'].'",';
						}
						if (isset($spotter_item['arrival_airport_time'])) {
							$output .= '"arrival_airport_time": "'.$spotter_item['arrival_airport_time'].'",';
						}
						if (isset($spotter_item['arrival_airport'])) {
							$output .= '"arrival_airport_code": "'.$spotter_item['arrival_airport'].'",';
						}
						if (isset($spotter_item['arrival_airport_city'])) {
							$output .= '"arrival_airport": "'.$spotter_item['arrival_airport_city'].', '.$spotter_item['arrival_airport_country'].'",';
						}
						if (isset($spotter_item['date_iso_8601'])) {
							$output .= '"date_update": "'.date("M j, Y, g:i a T", strtotime($spotter_item['date_iso_8601'])).'",';
						}
							$output .= '"latitude": "'.$spotter_item['latitude'].'",';
							$output .= '"longitude": "'.$spotter_item['longitude'].'",';
							$output .= '"ground_speed": "'.$spotter_item['ground_speed'].'",';
							$output .= '"altitude": "'.$spotter_item['altitude'].'",';
							$output .= '"heading": "'.$spotter_item['heading'].'",';
							$output .= '"image": "'.$image.'",';
						if (isset($spotter_item['image_copyright']) && $spotter_item['image_copyright'] != '') {
							$output .= '"image_copyright": "'.str_replace('"',"'",trim(str_replace(array("\r\n","\r","\n","\\r","\\n","\\r\\n"),'',$spotter_item['image_copyright']))).'",';
						}
						if (isset($spotter_item['image_source_website'])) {
							$output .= '"image_source_website": "'.urlencode($spotter_item['image_source_website']).'",';
						}
						if (isset($spotter_item['squawk'])) {
							$output .= '"squawk": "'.$spotter_item['squawk'].'",';
						}
						if (isset($spotter_item['squawk_usage'])) {
							$output .= '"squawk_usage": "'.$spotter_item['squawk_usage'].'",';
						}
						if (isset($spotter_item['pilot_id'])) {
							$output .= '"pilot_id": "'.$spotter_item['pilot_id'].'",';
						}
						if (isset($spotter_item['pilot_name'])) {
							$output .= '"pilot_name": "'.$spotter_item['pilot_name'].'",';
						}
						if (isset($spotter_item['acars'])) {
							$output .= '"acars": "'.trim(str_replace(array("\r\n","\r","\n","\\r","\\n","\\r\\n"), '<br />',$spotter_item['acars']['message'])).'",';
						}
							// FIXME : type when not aircraft ?
							$output .= '"type": "aircraft"';
						$output .= '},';
						$output .= '"geometry": {';
							$output .= '"type": "Point",';
								$output .= '"coordinates": [';
										$output .=  $spotter_item['longitude'].', ';
										$output .=  $spotter_item['latitude'];
								$output .= ']';
							$output .= '}';
				$output .= '},';
                
                
                //previous location history of aircraft
                $output .= '{';
					$output .= '"type": "Feature",';
                        $output .= '"properties": {';
							$output .= '"callsign": "'.$spotter_item['ident'].'",';
							$output .= '"type": "history"';
						$output .= '},';
						$output .= '"geometry": {';
							$output .= '"type": "LineString",';
								$output .= '"coordinates": [';
                                    //$spotter_history_array = SpotterLive::getAllLiveSpotterDataByIdent($spotter_item['ident']);
                                    if ($from_archive) {
					    $spotter_history_array = SpotterArchive::getAllArchiveSpotterDataById($spotter_item['flightaware_id']);
                                    } else {
					    $spotter_history_array = SpotterLive::getAllLiveSpotterDataById($spotter_item['flightaware_id']);
                                    }
										$d = false;
										$neg = false;
									foreach ($spotter_history_array as $key => $spotter_history)
									{
										
										
										if (abs($spotter_history['longitude']-$spotter_item['longitude']) > 200 || $d==true) {
											if ($d == false) {
												$output .= '';
												$output .= ',';
												$d = true;
											}
									        } else {
											$output .= '[';
											$output .=  $spotter_history['longitude'].', ';
											$output .=  $spotter_history['latitude'];
											$output .= '],';
										}
									}
									$output = substr($output, 0, -1);
								$output .= ']';
							$output .= '}';
				$output .= '},';
                
			}
		} else {
			$output .= '{';
			$output .= '"type": "Feature",';
			$output .= '"properties": {';
			$output .= '"flight_cnt": "'.$flightcnt.'"}},';
		}
		
		$output  = substr($output, 0, -1);

		$output .= ']';
$output .= '}';

print $output;

?>
