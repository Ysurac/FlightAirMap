<?php
require_once('require/class.Connection.php');
require_once('require/class.Common.php');
require_once('require/class.Spotter.php');
require_once('require/class.SpotterArchive.php');
$begintime = microtime(true);
$Spotter = new Spotter();
$SpotterArchive = new SpotterArchive();
$Common = new Common();

if (isset($_GET['download'])) {
    if ($_GET['download'] == "true")
    {
	header('Content-disposition: attachment; filename="flightairmap.json"');
    }
}
header('Content-Type: text/javascript');

if (!isset($globalJsonCompress)) $compress = true;
else $compress = $globalJsonCompress;

$from_archive = false;
$min = false;
$allhistory = false;
$filter['source'] = array();
if ((!isset($globalMapVAchoose) || $globalMapVAchoose) && isset($globalVATSIM) && $globalVATSIM && isset($_COOKIE['ShowVATSIM']) && $_COOKIE['ShowVATSIM'] == 'true') $filter['source'] = array_merge($filter['source'],array('vatsimtxt'));
if ((!isset($globalMapVAchoose) || $globalMapVAchoose) && isset($globalIVAO) && $globalIVAO && isset($_COOKIE['ShowIVAO']) && $_COOKIE['ShowIVAO'] == 'true') $filter['source'] = array_merge($filter['source'],array('whazzup'));
if ((!isset($globalMapVAchoose) || $globalMapVAchoose) && isset($globalphpVMS) && $globalphpVMS && isset($_COOKIE['ShowVMS']) && $_COOKIE['ShowVMS'] == 'true') $filter['source'] = array_merge($filter['source'],array('phpvmacars'));
if ((!isset($globalMapchoose) || $globalMapchoose) && isset($globalSBS1) && $globalSBS1 && isset($_COOKIE['ShowSBS1']) && $_COOKIE['ShowSBS1'] == 'true') $filter['source'] = array_merge($filter['source'],array('sbs'));
if ((!isset($globalMapchoose) || $globalMapchoose) && isset($globalAPRS) && $globalAPRS && isset($_COOKIE['ShowAPRS']) && $_COOKIE['ShowAPRS'] == 'true') $filter['source'] = array_merge($filter['source'],array('aprs'));
if (isset($_COOKIE['Airlines']) && $_COOKIE['Airlines'] != '') $filter['airlines'] = explode(',',$_COOKIE['Airlines']);
if (isset($_COOKIE['Sources']) && $_COOKIE['Sources'] != '') $filter['source_aprs'] = explode(',',$_COOKIE['Sources']);
if (isset($_COOKIE['airlinestype']) && $_COOKIE['airlinestype'] != 'all') $filter['airlinestype'] = $_COOKIE['airlinestype'];

if (isset($globalMapPopup) && !$globalMapPopup && !(isset($_COOKIE['flightpopup']) && $_COOKIE['flightpopup'] == 'true')) {
	$min = true;
}

if (isset($_GET['ident'])) {
	$ident = filter_input(INPUT_GET,'ident',FILTER_SANITIZE_STRING);
	$from_archive = true;
	$spotter_array = $SpotterArchive->getLastArchiveSpotterDataByIdent($ident);
	$allhistory = true;
} elseif (isset($_GET['flightaware_id'])) {
	$flightaware_id = filter_input(INPUT_GET,'flightaware_id',FILTER_SANITIZE_STRING);
	$from_archive = true;
	$spotter_array = $SpotterArchive->getLastArchiveSpotterDataById($flightaware_id);
	$allhistory = true;
} elseif (isset($_GET['archive']) && isset($_GET['begindate']) && isset($_GET['enddate']) && isset($_GET['speed'])) {
	$from_archive = true;
//	$begindate = filter_input(INPUT_GET,'begindate',FILTER_VALIDATE_REGEXP,array("options"=>array("regexp"=>'~^\d{4}/\d{2}/\d{2}$~')));
//	$enddate = filter_input(INPUT_GET,'enddate',FILTER_VALIDATE_REGEXP,array("options"=>array("regexp"=>'~^\d{4}/\d{2}/\d{2}$~')));
	$begindate = filter_input(INPUT_GET,'begindate',FILTER_SANITIZE_NUMBER_INT);
	$enddate = filter_input(INPUT_GET,'enddate',FILTER_SANITIZE_NUMBER_INT);
	$archivespeed = filter_input(INPUT_GET,'speed',FILTER_SANITIZE_NUMBER_INT);
	$begindate = date('Y-m-d H:i:s',$begindate);
	$enddate = date('Y-m-d H:i:s',$enddate);
	$spotter_array = $SpotterArchive->getMinLiveSpotterDataPlayback($begindate,$enddate,$filter);
}

if (!empty($spotter_array)) {
	$flightcnt = $SpotterArchive->getLiveSpotterCount($begindate,$enddate,$filter);
	if ($flightcnt == '') $flightcnt = 0;
} else $flightcnt = 0;

$sqltime = round(microtime(true)-$begintime,2);

//var_dump($spotter_array);
$j = 0;

$output = '{';
	$output .= '"type": "FeatureCollection",';
		if ($min) $output .= '"minimal": "true",';
		else $output .= '"minimal": "false",';
		$output .= '"fc": "'.$flightcnt.'",';
		$output .= '"sqt": "'.$sqltime.'",';

		if (!empty($spotter_array) && is_array($spotter_array))
		{
			$output .= '"features": [';
			foreach($spotter_array as $spotter_item)
			{
				$j++;
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
						//$output .= '"fc": "'.$flightcnt.'",';
						//$output .= '"sqt": "'.$sqltime.'",';
						$output .= '"properties": {';
							if ($compress) $output .= '"fi": "'.$spotter_item['flightaware_id'].'",';
							else $output .= '"flightaware_id": "'.$spotter_item['flightaware_id'].'",';
							$output .= '"fc": "'.$flightcnt.'",';
							$output .= '"sqt": "'.$sqltime.'",';
							if (isset($begindate)) $output .= '"archive_date": "'.$begindate.'",';

/*
							if ($min) $output .= '"minimal": "true",';
							else $output .= '"minimal": "false",';
*/
							//$output .= '"fc": "'.$spotter_item['nb'].'",';
						if (isset($spotter_item['ident']) && $spotter_item['ident'] != '') {
							if ($compress) $output .= '"c": "'.$spotter_item['ident'].'",';
							else $output .= '"callsign": "'.$spotter_item['ident'].'",';
						} else {
							if ($compress) $output .= '"c": "NA",';
							else $output .= '"callsign": "NA",';
						}
						if (isset($spotter_item['registration'])) $output .= '"registration": "'.$spotter_item['registration'].'",';
						if (isset($spotter_item['aircraft_name']) && isset($spotter_item['aircraft_type'])) {
							$output .= '"aircraft_name": "'.$spotter_item['aircraft_name'].' ('.$spotter_item['aircraft_type'].')",';
							$output .= '"aircraft_wiki": "http://'.strtolower($globalLanguage).'.wikipedia.org/wiki/'.urlencode(str_replace(' ','_',$spotter_item['aircraft_name'])).'",';
						} elseif (isset($spotter_item['aircraft_type'])) {
							$output .= '"aircraft_name": "NA ('.$spotter_item['aircraft_type'].')",';
						} elseif (!$min) {
							$output .= '"aircraft_name": "NA",';
						}
						if (!$min && isset($spotter_item['aircraft_icao'])) {
							$output .= '"aircraft_icao": "'.$spotter_item['aircraft_icao'].'",';
						}
						if (!isset($spotter_item['aircraft_shadow'])) {
							if (!isset($spotter_item['aircraft_icao']) || $spotter_item['aircraft_icao'] == '') $spotter_item['aircraft_shadow'] = '';
							else {
								$aircraft_info = $Spotter->getAllAircraftInfo($spotter_item['aircraft_icao']);
								if (count($aircraft_info) > 0) $spotter_item['aircraft_shadow'] = $aircraft_info[0]['aircraft_shadow'];
								else $spotter_item['aircraft_shadow'] = '';
							}
						}
						if ($spotter_item['aircraft_shadow'] == '') {
							if ($compress) $output .= '"as": "default.png",';
							else $output .= '"aircraft_shadow": "default.png",';
						} else {
							if ($compress) $output .= '"as": "'.$spotter_item['aircraft_shadow'].'",';
							else $output .= '"aircraft_shadow": "'.$spotter_item['aircraft_shadow'].'",';
						}
						if (isset($spotter_item['airline_name'])) {
							$output .= '"airline_name": "'.$spotter_item['airline_name'].'",';
						} elseif (!$min) {
							$output .= '"airline_name": "NA",';
						}
						if (isset($spotter_item['departure_airport'])) {
							if ($compress) $output .= '"dac": "'.$spotter_item['departure_airport'].'",';
							else $output .= '"departure_airport_code": "'.$spotter_item['departure_airport'].'",';
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
							if ($compress) $output .= '"aac": "'.$spotter_item['arrival_airport'].'",';
							else $output .= '"arrival_airport_code": "'.$spotter_item['arrival_airport'].'",';
						}
						if (isset($spotter_item['arrival_airport_city'])) {
							$output .= '"arrival_airport": "'.$spotter_item['arrival_airport_city'].', '.$spotter_item['arrival_airport_country'].'",';
						}
						
						if (isset($spotter_item['date_iso_8601'])) {
							$output .= '"date_update": "'.date("M j, Y, g:i a T", strtotime($spotter_item['date_iso_8601'])).'",';
						}
						if (isset($spotter_item['date'])) {
							$output .= '"lu": "'.strtotime($spotter_item['date']).'",';
						}
						if (!$min) {
							$output .= '"latitude": "'.$spotter_item['latitude'].'",';
							$output .= '"longitude": "'.$spotter_item['longitude'].'",';
							$output .= '"ground_speed": "'.$spotter_item['ground_speed'].'",';
						}
						
						if ($compress) $output .= '"a": "'.$spotter_item['altitude'].'",';
						else $output .= '"altitude": "'.$spotter_item['altitude'].'",';
						if ($compress)$output .= '"h": "'.$spotter_item['heading'].'",';
						else $output .= '"heading": "'.$spotter_item['heading'].'",';
						
						if (isset($archivespeed)) $nextcoord = $Common->nextcoord($spotter_item['latitude'],$spotter_item['longitude'],$spotter_item['ground_speed'],$spotter_item['heading'],$archivespeed);
						else $nextcoord = $Common->nextcoord($spotter_item['latitude'],$spotter_item['longitude'],$spotter_item['ground_speed'],$spotter_item['heading']);
						//$output .= '"nextlatitude": "'.$nextcoord['latitude'].'",';
						//$output .= '"nextlongitude": "'.$nextcoord['longitude'].'",';
						$output .= '"nextlatlon": ['.$nextcoord['latitude'].','.$nextcoord['longitude'].'],';

						if (!$min) $output .= '"image": "'.$image.'",';
						if (isset($spotter_item['image_copyright']) && $spotter_item['image_copyright'] != '') {
							$output .= '"image_copyright": "'.str_replace('"',"'",trim(str_replace(array("\r\n","\r","\n","\\r","\\n","\\r\\n"),'',$spotter_item['image_copyright']))).'",';
						}
						if (isset($spotter_item['image_source_website'])) {
							$output .= '"image_source_website": "'.urlencode($spotter_item['image_source_website']).'",';
						}
						if (isset($spotter_item['squawk'])) {
							if ($compress) $output .= '"sq": "'.$spotter_item['squawk'].'",';
							else $output .= '"squawk": "'.$spotter_item['squawk'].'",';
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
						if (isset($spotter_item['waypoints']) && $spotter_item['waypoints'] != '') {
							$output .= '"waypoints": "'.$spotter_item['waypoints'].'",';
						}
						if (isset($spotter_item['acars'])) {
							$output .= '"acars": "'.trim(str_replace(array("\r\n","\r","\n","\\r","\\n","\\r\\n"), '<br />',$spotter_item['acars']['message'])).'",';
						}
						$spotter_history_array = $SpotterArchive->getCoordArchiveSpotterDataById($spotter_item['flightaware_id']);
						//$spotter_history_array = array();
				$output_history = '';
				$output_time = '';
				foreach ($spotter_history_array as $key => $spotter_history)
				{
					$output_history .= '['.$spotter_history['longitude'].', '.$spotter_history['latitude'].'],';
					$output_time .= (strtotime($spotter_history['date'])*1000).',';
				}
				if (isset($output_time)) {
				    $output_time  = substr($output_time, 0, -1);
				    $output .= '"time": ['.$output_time.'],';
				}



							// FIXME : type when not aircraft ?
						if ($compress) $output .= '"t": "aircraft"';
						else $output .= '"type": "aircraft"';
						$output .= '},';
						$output .= '"geometry": {';
						$output .= '"type": "MultiPoint",';
						$output .= '"coordinates": [';
						
				if (isset($output_history)) {
				    $output_history  = substr($output_history, 0, -1);
				    $output .= $output_history;
				}
				
						$output .= ']';
						$output .= '}';
				$output .= '},';

			}
			$output  = substr($output, 0, -1);
			$output .= ']';
			$output .= ',"initial_sqltime": "'.$sqltime.'",';
			$output .= '"totaltime": "'.round(microtime(true)-$begintime,2).'",';
			if (isset($begindate)) $output .= '"archive_date": "'.$begindate.'",';
			$output .= '"fc": "'.$flightcnt.'"';
		} else {
			$output .= '"features": ';
			$output .= '{';
			$output .= '"type": "Feature",';
			$output .= '"properties": {';
			$output .= '"fc": "'.$flightcnt.'"}}';
		}
		
$output .= '}';

print $output;

?>
