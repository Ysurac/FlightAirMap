<?php
require_once('require/class.Connection.php');
require_once('require/class.Common.php');

if (isset($globalProtect) && $globalProtect) {
	@session_start();
	if (!isset($_SESSION['protect']) || !isset($_SERVER['HTTP_REFERER'])) {
		echo 'You must access this page using the right way.';
		die();
	}
}
$tracker = false;
$marine = false;
$usecoord = false;
if (isset($_GET['test'])) exit();
if (isset($_GET['tracker'])) {
    $tracker = true;
}
if (isset($_GET['marine'])) {
    $marine = true;
}
if ($tracker) {
    require_once('require/class.Tracker.php');
    require_once('require/class.TrackerLive.php');
    require_once('require/class.TrackerArchive.php');
} elseif ($marine) {
    require_once('require/class.Marine.php');
    require_once('require/class.MarineLive.php');
    require_once('require/class.MarineArchive.php');
} else {
    require_once('require/class.Spotter.php');
    require_once('require/class.SpotterLive.php');
    require_once('require/class.SpotterArchive.php');
}

$begintime = microtime(true);
if ($tracker) {
	$TrackerLive = new TrackerLive();
	$Tracker = new Tracker();
	$TrackerArchive = new TrackerArchive();
} elseif ($marine) {
	$MarineLive = new MarineLive();
	$Marine = new Marine();
	$MarineArchive = new MarineArchive();
} else {
	$SpotterLive = new SpotterLive();
	$Spotter = new Spotter();
	$SpotterArchive = new SpotterArchive();
}
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

$limit = 0;
$from_archive = false;
$min = true;
$allhistory = false;
$filter['source'] = array();
if ((!isset($globalMapVAchoose) || $globalMapVAchoose) && isset($globalVATSIM) && $globalVATSIM && isset($_COOKIE['filter_ShowVATSIM']) && $_COOKIE['filter_ShowVATSIM'] == 'true') $filter['source'] = array_merge($filter['source'],array('vatsimtxt'));
if ((!isset($globalMapVAchoose) || $globalMapVAchoose) && isset($globalIVAO) && $globalIVAO && isset($_COOKIE['filter_ShowIVAO']) && $_COOKIE['filter_ShowIVAO'] == 'true') $filter['source'] = array_merge($filter['source'],array('whazzup'));
if ((!isset($globalMapVAchoose) || $globalMapVAchoose) && isset($globalphpVMS) && $globalphpVMS && isset($_COOKIE['filter_ShowVMS']) && $_COOKIE['filter_ShowVMS'] == 'true') $filter['source'] = array_merge($filter['source'],array('phpvmacars'));
if ((!isset($globalMapchoose) || $globalMapchoose) && isset($globalSBS1) && $globalSBS1 && isset($_COOKIE['filter_ShowSBS1']) && $_COOKIE['filter_ShowSBS1'] == 'true') $filter['source'] = array_merge($filter['source'],array('sbs','famaprs'));
if ((!isset($globalMapchoose) || $globalMapchoose) && isset($globalAPRS) && $globalAPRS && isset($_COOKIE['filter_ShowAPRS']) && $_COOKIE['filter_ShowAPRS'] == 'true') $filter['source'] = array_merge($filter['source'],array('aprs'));
if (isset($_COOKIE['filter_ident']) && $_COOKIE['filter_ident'] != '') $filter['ident'] = filter_var($_COOKIE['filter_ident'],FILTER_SANITIZE_STRING);
if (isset($_COOKIE['filter_mmsi']) && $_COOKIE['filter_mmsi'] != '') $filter['mmsi'] = filter_var($_COOKIE['filter_mmsi'],FILTER_SANITIZE_STRING);
if (isset($_COOKIE['filter_Airlines']) && $_COOKIE['filter_Airlines'] != '') $filter['airlines'] = filter_var_array(explode(',',$_COOKIE['filter_Airlines']),FILTER_SANITIZE_STRING);
if (isset($_COOKIE['filter_Sources']) && $_COOKIE['filter_Sources'] != '') $filter['source_aprs'] = filter_var_array(explode(',',$_COOKIE['filter_Sources']),FILTER_SANITIZE_STRING);
if (isset($_COOKIE['filter_airlinestype']) && $_COOKIE['filter_airlinestype'] != 'all') $filter['airlinestype'] = filter_var($_COOKIE['filter_airlinestype'],FILTER_SANITIZE_STRING);
if (isset($_COOKIE['filter_alliance']) && $_COOKIE['filter_alliance'] != 'all') $filter['alliance'] = filter_var($_COOKIE['filter_alliance'],FILTER_SANITIZE_STRING);
if (isset($_COOKIE['filter_race']) && $_COOKIE['filter_race'] != 'all') $filter['race'] = filter_var($_COOKIE['filter_race'],FILTER_SANITIZE_NUMBER_INT);
if (isset($_COOKIE['filter_blocked']) && $_COOKIE['filter_blocked'] == 'true') $filter['blocked'] = true;

if (isset($globalMapPopup) && !$globalMapPopup && !(isset($_COOKIE['flightpopup']) && $_COOKIE['flightpopup'] == 'true')) {
	$min = true;
} else $min = false;


if (isset($_COOKIE['map_2d_limit'])) {
	$limit = filter_var($_COOKIE['map_2d_limit'],FILTER_SANITIZE_NUMBER_INT);
}

$spotter_array = array();

if (isset($_GET['ident'])) {
	$ident = urldecode(filter_input(INPUT_GET,'ident',FILTER_SANITIZE_STRING));
	if ($tracker) {
		$spotter_array = $TrackerLive->getLastLiveTrackerDataByIdent($ident);
	} elseif ($marine) {
		$spotter_array = $MarineLive->getLastLiveMarineDataByIdent($ident);
	} else {
		$spotter_array = $SpotterLive->getLastLiveSpotterDataByIdent($ident);
		if (empty($spotter_array)) {
			$from_archive = true;
			$spotter_array = $SpotterArchive->getLastArchiveSpotterDataByIdent($ident);
		}
	}
	$allhistory = true;
} elseif (isset($_GET['flightaware_id'])) {
	$flightaware_id = filter_input(INPUT_GET,'flightaware_id',FILTER_SANITIZE_STRING);
	$spotter_array = $SpotterLive->getLastLiveSpotterDataById($flightaware_id);
	if (empty($spotter_array)) {
		$from_archive = true;
		$spotter_array = $SpotterArchive->getLastArchiveSpotterDataById($flightaware_id);
	}
	$allhistory = true;
} elseif (isset($_GET['famtrack_id'])) {
	$famtrack_id = urldecode(filter_input(INPUT_GET,'famtrack_id',FILTER_SANITIZE_STRING));
	$spotter_array = $TrackerLive->getLastLiveTrackerDataById($famtrack_id);
	$allhistory = true;
} elseif (isset($_GET['fammarine_id'])) {
	$fammarine_id = urldecode(filter_input(INPUT_GET,'fammarine_id',FILTER_SANITIZE_STRING));
	$spotter_array = $MarineLive->getLastLiveMarineDataById($fammarine_id);
	$allhistory = true;
/*
} elseif (isset($globalMapUseBbox) && $globalMapUseBbox && isset($_GET['coord']) && (!isset($globalMapPopup) || $globalMapPopup || (isset($_COOKIE['flightpopup']) && $_COOKIE['flightpopup'] == 'true'))) {
	$usecoord = true;
	$coord = explode(',',$_GET['coord']);
	if (filter_var($coord[0],FILTER_VALIDATE_FLOAT) && filter_var($coord[1],FILTER_VALIDATE_FLOAT) && filter_var($coord[2],FILTER_VALIDATE_FLOAT) && filter_var($coord[3],FILTER_VALIDATE_FLOAT) 
	    && $coord[0] > -180.0 && $coord[0] < 180.0 && $coord[1] > -90.0 && $coord[1] < 90.0 && $coord[2] > -180.0 && $coord[2] < 180.0 && $coord[3] > -90.0 && $coord[3] < 90.0) {
		if ($tracker) {
			$spotter_array = $TrackerLive->getLiveTrackerDatabyCoord($coord,$filter);
		} elseif ($marine) {
			$spotter_array = $MarineLive->getLiveMarineDatabyCoord($coord,$filter);
		} else {
			$spotter_array = $SpotterLive->getLiveSpotterDatabyCoord($coord,$filter);
		}
	}
*/
} elseif (isset($globalMapUseBbox) && $globalMapUseBbox && isset($_GET['coord']) && $min && !isset($_GET['archive'])) {
	$usecoord = true;
	$coord = explode(',',$_GET['coord']);
	if (filter_var($coord[0],FILTER_VALIDATE_FLOAT) && filter_var($coord[1],FILTER_VALIDATE_FLOAT) && filter_var($coord[2],FILTER_VALIDATE_FLOAT) && filter_var($coord[3],FILTER_VALIDATE_FLOAT) 
	    && $coord[0] > -180.0 && $coord[0] < 180.0 && $coord[1] > -90.0 && $coord[1] < 90.0 && $coord[2] > -180.0 && $coord[2] < 180.0 && $coord[3] > -90.0 && $coord[3] < 90.0) {
		if ($tracker) {
			$spotter_array = $TrackerLive->getMinLiveTrackerDatabyCoord($coord,$filter);
		} elseif ($marine) {
			$spotter_array = $MarineLive->getMinLiveMarineDatabyCoord($coord,$filter);
		} else {
			$spotter_array = $SpotterLive->getMinLiveSpotterDatabyCoord($coord,$limit,$filter);
		}
	} else {
		if ($tracker) {
			$spotter_array = $TrackerLive->getMinLiveTrackerData($filter);
		} elseif ($marine) {
			$spotter_array = $MarineLive->getMinLiveMarineData($filter);
		} else {
			$spotter_array = $SpotterLive->getMinLiveSpotterData($limit,$filter);
		}
	}
} elseif (isset($_GET['archive']) && isset($_GET['begindate']) && isset($_GET['enddate']) && isset($_GET['speed']) && !isset($_GET['tracker']) && !isset($_GET['marine'])) {
	$from_archive = true;
//	$begindate = filter_input(INPUT_GET,'begindate',FILTER_VALIDATE_REGEXP,array("options"=>array("regexp"=>'~^\d{4}/\d{2}/\d{2}$~')));
//	$enddate = filter_input(INPUT_GET,'enddate',FILTER_VALIDATE_REGEXP,array("options"=>array("regexp"=>'~^\d{4}/\d{2}/\d{2}$~')));
	$begindate = filter_input(INPUT_GET,'begindate',FILTER_SANITIZE_NUMBER_INT);
	$enddate = filter_input(INPUT_GET,'enddate',FILTER_SANITIZE_NUMBER_INT);
	$archivespeed = filter_input(INPUT_GET,'speed',FILTER_SANITIZE_NUMBER_INT);
	$begindate = date('Y-m-d H:i:s',$begindate);
	$enddate = date('Y-m-d H:i:s',$enddate);
	$spotter_array = $SpotterArchive->getMinLiveSpotterData($begindate,$enddate,$filter);
} elseif ($min) {
	if ($tracker) {
		$spotter_array = $TrackerLive->getMinLiveTrackerData($filter);
	} elseif ($marine) {
		$spotter_array = $MarineLive->getMinLiveMarineData($filter);
	} else {
		$spotter_array = $SpotterLive->getMinLiveSpotterData($limit,$filter);
	}
#	$min = true;
} else {
	if ($tracker) {
		$spotter_array = $TrackerLive->getLiveTrackerData('','',$filter);
	} elseif ($marine) {
		$spotter_array = $marineLive->getLiveMarineData('','',$filter);
	} else {
		$spotter_array = $SpotterLive->getLiveSpotterData('','',$filter);
	}
}

if ($usecoord) {
	if (isset($_GET['archive'])) {
		$flightcnt = $SpotterArchive->getLiveSpotterCount($begindate,$enddate,$filter);
	} else {
		if ($tracker) {
			$flightcnt = $TrackerLive->getLiveTrackerCount($filter);
		} elseif ($marine) {
			$flightcnt = $MarineLive->getLiveMarineCount($filter);
		} else {
			$flightcnt = $SpotterLive->getLiveSpotterCount($filter);
		}
	}
	if ($flightcnt == '') $flightcnt = 0;
} else $flightcnt = 0;

$sqltime = round(microtime(true)-$begintime,2);

$currenttime = filter_input(INPUT_GET,'currenttime',FILTER_SANITIZE_NUMBER_INT);
if ($currenttime != '') $currenttime = round($currenttime/1000);

if ((!isset($_COOKIE['flightestimation']) && isset($globalMapEstimation) && $globalMapEstimation === FALSE) || (isset($_COOKIE['flightestimation']) && $_COOKIE['flightestimation'] == 'false')) $usenextlatlon = false;
else $usenextlatlon = true;
if ($usenextlatlon === false) $currenttime = '';
$j = 0;
$prev_flightaware_id = '';
$aircrafts_shadow = array();
$output = '{';
	$output .= '"type": "FeatureCollection",';
		if ($min) $output .= '"minimal": "true",';
		else $output .= '"minimal": "false",';
		//$output .= '"fc": "'.$flightcnt.'",';
		$output .= '"sqt": "'.$sqltime.'",';

		if (!empty($spotter_array) && is_array($spotter_array))
		{
			$output .= '"features": [';
			foreach($spotter_array as $spotter_item)
			{
				$j++;
				unset($idistance);
				date_default_timezone_set('UTC');

				if (isset($spotter_item['image_thumbnail']) && $spotter_item['image_thumbnail'] != "")
				{
					$image = $spotter_item['image_thumbnail'];
				} else {
					$image = "images/placeholder_thumb.png";
				}

/*
				if ($prev_flightaware_id != $spotter_item['flightaware_id']) {
				    if ($prev_flightaware_id != '') {
						$output .= ']';
						$output .= '}';
						$output .= '},';
				    }
				$prev_flightaware_id = $spotter_item['flightaware_id'];
*/

				//location of aircraft
//				print_r($spotter_item);
				$output .= '{';
					$output .= '"type": "Feature",';
						//$output .= '"fc": "'.$flightcnt.'",';
						//$output .= '"sqt": "'.$sqltime.'",';
						if (isset($spotter_item['flightaware_id'])) {
							$output .= '"id": "'.$spotter_item['flightaware_id'].'",';
						} elseif (isset($spotter_item['famtrackid'])) {
							$output .= '"id": "'.$spotter_item['famtrackid'].'",';
						} elseif (isset($spotter_item['fammarine_id'])) {
							$output .= '"id": "'.$spotter_item['fammarine_id'].'",';
						}
						$output .= '"properties": {';
						if (isset($spotter_item['flightaware_id'])) {
							if ($compress) $output .= '"fi": "'.$spotter_item['flightaware_id'].'",';
							else $output .= '"flightaware_id": "'.$spotter_item['flightaware_id'].'",';
						} elseif (isset($spotter_item['famtrackid'])) {
							if ($compress) $output .= '"fti": "'.$spotter_item['famtrackid'].'",';
							else $output .= '"famtrackid": "'.$spotter_item['famtrackid'].'",';
						} elseif (isset($spotter_item['fammarine_id'])) {
							if ($compress) $output .= '"fmi": "'.$spotter_item['fammarine_id'].'",';
							else $output .= '"fammarineid": "'.$spotter_item['fammarine_id'].'",';
						}
						$output .= '"fc": "'.$flightcnt.'",';
						$output .= '"sqt": "'.$sqltime.'",';
						if (isset($begindate)) $output .= '"archive_date": "'.$begindate.'",';

/*
							if ($min) $output .= '"minimal": "true",';
							else $output .= '"minimal": "false",';
*/
							//$output .= '"fc": "'.$spotter_item['nb'].'",';
						if (isset($spotter_item['ident']) && $spotter_item['ident'] != '') {
							if ($compress) $output .= '"c": '.json_encode(str_replace('\\','',$spotter_item['ident'])).',';
							else $output .= '"callsign": '.json_encode(str_replace('\\','',$spotter_item['ident'])).',';
							//'
						} else {
							if ($compress) $output .= '"c": "NA",';
							else $output .= '"callsign": "NA",';
						}
						if (isset($spotter_item['registration'])) {
							if ($compress) $output .= '"reg": '.json_encode($spotter_item['registration']).',';
							else $output .= '"registration": '.json_encode($spotter_item['registration']).',';
						}
						if (isset($spotter_item['aircraft_name']) && isset($spotter_item['aircraft_type'])) {
							$output .= '"aircraft_name": "'.$spotter_item['aircraft_name'].' ('.$spotter_item['aircraft_type'].')",';
							$output .= '"aircraft_wiki": "http://'.strtolower($globalLanguage).'.wikipedia.org/wiki/'.urlencode(str_replace(' ','_',$spotter_item['aircraft_name'])).'",';
						} elseif (isset($spotter_item['aircraft_type'])) {
							$output .= '"aircraft_name": "NA ('.$spotter_item['aircraft_type'].')",';
						} elseif (!$min) {
							$output .= '"aircraft_name": "NA",';
						}
						if (isset($spotter_item['aircraft_icao'])) {
							if ($compress) $output .= '"ai": "'.$spotter_item['aircraft_icao'].'",';
							else $output .= '"aircraft_icao": "'.$spotter_item['aircraft_icao'].'",';
						}
						if (!isset($spotter_item['aircraft_shadow']) && !$tracker && !$marine) {
							if (!isset($spotter_item['aircraft_icao']) || $spotter_item['aircraft_icao'] == '') $spotter_item['aircraft_shadow'] = '';
							else {
								$aircraft_icao = $spotter_item['aircraft_icao'];
								if (isset($aircrafts_shadow[$aircraft_icao])) $spotter_item['aircraft_shadow'] = $aircrafts_shadow[$aircraft_icao];
								else {
									$aircraft_info = $Spotter->getAllAircraftInfo($spotter_item['aircraft_icao']);
									if (count($aircraft_info) > 0) $spotter_item['aircraft_shadow'] = $aircraft_info[0]['aircraft_shadow'];
									elseif (isset($spotter_item['format_source']) && $spotter_item['format_source'] == 'aprs') $spotter_item['aircraft_shadow'] = 'PA18.png';
									elseif ($aircraft_icao == 'PARAGLIDER') $spotter_item['aircraft_shadow'] = 'PARAGLIDER.png';
									else $spotter_item['aircraft_shadow'] = '';
									$aircrafts_shadow[$aircraft_icao] = $spotter_item['aircraft_shadow'];
								}
							}
						}
						if (!isset($spotter_item['aircraft_shadow']) || $spotter_item['aircraft_shadow'] == '') {
							if ($tracker) {
								if (isset($spotter_item['type']) && $spotter_item['type'] == 'Ambulance') {
									if ($compress) $output .= '"as": "ambulance.png",';
									else $output .= '"aircraft_shadow": "ambulance.png",';
								} elseif (isset($spotter_item['type']) && $spotter_item['type'] == 'Police') {
									if ($compress) $output .= '"as": "police.png",';
									else $output .= '"aircraft_shadow": "police.png",';
								} elseif (isset($spotter_item['type']) && $spotter_item['type'] == 'Yacht (Sail)') {
									if ($compress) $output .= '"as": "ship.png",';
									else $output .= '"aircraft_shadow": "ship.png",';
								} elseif (isset($spotter_item['type']) && $spotter_item['type'] == 'Ship (Power Boat)') {
									if ($compress) $output .= '"as": "ship.png",';
									else $output .= '"aircraft_shadow": "ship.png",';
								} elseif (isset($spotter_item['type']) && $spotter_item['type'] == 'Shuttle') {
									if ($compress) $output .= '"as": "ship.png",';
									else $output .= '"aircraft_shadow": "ship.png",';
								} elseif (isset($spotter_item['type']) && $spotter_item['type'] == 'Truck') {
									if ($compress) $output .= '"as": "truck.png",';
									else $output .= '"aircraft_shadow": "truck.png",';
								} elseif (isset($spotter_item['type']) && $spotter_item['type'] == 'Truck (18 Wheeler)') {
									if ($compress) $output .= '"as": "truck.png",';
									else $output .= '"aircraft_shadow": "truck.png",';
								} elseif (isset($spotter_item['type']) && $spotter_item['type'] == 'Aircraft (small)') {
									if ($compress) $output .= '"as": "aircraft.png",';
									else $output .= '"aircraft_shadow": "aircraft.png",';
								} elseif (isset($spotter_item['type']) && $spotter_item['type'] == 'Large Aircraft') {
									if ($compress) $output .= '"as": "aircraft.png",';
									else $output .= '"aircraft_shadow": "aircraft.png",';
								} elseif (isset($spotter_item['type']) && $spotter_item['type'] == 'Helicopter') {
									if ($compress) $output .= '"as": "helico.png",';
									else $output .= '"aircraft_shadow": "helico.png",';
								} elseif (isset($spotter_item['type']) && $spotter_item['type'] == 'Railroad Engine') {
									if ($compress) $output .= '"as": "rail.png",';
									else $output .= '"aircraft_shadow": "rail.png",';
								} elseif (isset($spotter_item['type']) && $spotter_item['type'] == 'Firetruck') {
									if ($compress) $output .= '"as": "firetruck.png",';
									else $output .= '"aircraft_shadow": "firetruck.png",';
								} elseif (isset($spotter_item['type']) && $spotter_item['type'] == 'Bus') {
									if ($compress) $output .= '"as": "bus.png",';
									else $output .= '"aircraft_shadow": "bus.png",';
								} elseif (isset($spotter_item['type']) && $spotter_item['type'] == 'Phone') {
									if ($compress) $output .= '"as": "phone.png",';
									else $output .= '"aircraft_shadow": "phone.png",';
								} elseif (isset($spotter_item['type']) && $spotter_item['type'] == 'Jogger') {
									if ($compress) $output .= '"as": "jogger.png",';
									else $output .= '"aircraft_shadow": "jogger.png",';
								} elseif (isset($spotter_item['type']) && $spotter_item['type'] == 'Bike') {
									if ($compress) $output .= '"as": "bike.png",';
									else $output .= '"aircraft_shadow": "bike.png",';
								} elseif (isset($spotter_item['type']) && $spotter_item['type'] == 'Motorcycle') {
									if ($compress) $output .= '"as": "motorcycle.png",';
									else $output .= '"aircraft_shadow": "motorcycle.png",';
								} elseif (isset($spotter_item['type']) && $spotter_item['type'] == 'Balloon') {
									if ($compress) $output .= '"as": "balloon.png",';
									else $output .= '"aircraft_shadow": "balloon.png",';
								} else {
									if ($compress) $output .= '"as": "car.png",';
									else $output .= '"aircraft_shadow": "car.png",';
								}
							} elseif ($marine) {
								if (isset($spotter_item['type']) && ($spotter_item['type']  == '50&#39; Performance Cruiser' || $spotter_item['type']  == '50\' Performance Cruiser' || $spotter_item['type'] == 'Sail')) {
									if ($compress) $output .= '"as": "50perfcruiser.png",';
									else $output .= '"aircraft_shadow": "50perfcruiser.png",';
								} elseif (isset($spotter_item['type']) && $spotter_item['type']  == 'Sailaway Cruiser 38') {
									if ($compress) $output .= '"as": "cruiser38.png",';
									else $output .= '"aircraft_shadow": "cruiser38.png",';
								} elseif (isset($spotter_item['type']) && $spotter_item['type']  == 'Mini Transat') {
									if ($compress) $output .= '"as": "transat.png",';
									else $output .= '"aircraft_shadow": "transat.png",';
								} elseif (isset($spotter_item['type']) && $spotter_item['type']  == '52&#39; Cruising Cat') {
									if ($compress) $output .= '"as": "catamaran.png",';
									else $output .= '"aircraft_shadow": "catamaran.png",';
								} elseif (isset($spotter_item['type']) && $spotter_item['type']  == 'Caribbean Rose') {
									if ($compress) $output .= '"as": "carib.png",';
									else $output .= '"aircraft_shadow": "carib.png",';
								} elseif (isset($spotter_item['type']) && $spotter_item['type']  == 'Nordic Folkboat') {
									if ($compress) $output .= '"as": "nordic.png",';
									else $output .= '"aircraft_shadow": "nordic.png",';
								} elseif (isset($spotter_item['type']) && $spotter_item['type']  == '32&#39; Offshore Racer') {
									if ($compress) $output .= '"as": "nordic.png",';
									else $output .= '"aircraft_shadow": "50perfcruiser.png",';
								} else {
									if ($compress) $output .= '"as": "ship.png",';
									else $output .= '"aircraft_shadow": "ship.png",';
								}
							} else {
								if ($compress) $output .= '"as": "default.png",';
								else $output .= '"aircraft_shadow": "default.png",';
							}
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
							$output .= '"lu": "'.strtotime($spotter_item['date_iso_8601']).'",';
						} elseif (isset($spotter_item['date'])) {
							$output .= '"lu": "'.strtotime($spotter_item['date']).'",';
						}
						if (!$min) {
							$output .= '"latitude": "'.$spotter_item['latitude'].'",';
							$output .= '"longitude": "'.$spotter_item['longitude'].'",';
							$output .= '"ground_speed": "'.$spotter_item['ground_speed'].'",';
						}
						
						if (isset($spotter_item['real_altitude'])) {
							if ($compress) $output .= '"a": "'.($spotter_item['real_altitude']/100).'",';
							else $output .= '"altitude": "'.($spotter_item['real_altitude']/100).'",';
						} elseif (isset($spotter_item['altitude'])) {
							if ($compress) $output .= '"a": "'.$spotter_item['altitude'].'",';
							else $output .= '"altitude": "'.$spotter_item['altitude'].'",';
						}
						
						$heading = $spotter_item['heading'];
						
						if (isset($archivespeed) || $usenextlatlon) {
							if (isset($spotter_item['arrival_airport']) && $spotter_item['arrival_airport'] != 'NA') {
								if (isset($spotter_item['arrival_airport_latitude'])) {
									$cheading = $Common->getHeading($spotter_item['latitude'],$spotter_item['longitude'],$spotter_item['arrival_airport_latitude'],$spotter_item['arrival_airport_longitude']);
									$idistance = $Common->distance($spotter_item['latitude'],$spotter_item['longitude'],$spotter_item['arrival_airport_latitude'],$spotter_item['arrival_airport_longitude']);
									$farr_lat = $spotter_item['arrival_airport_latitude'];
									$farr_lon = $spotter_item['arrival_airport_longitude'];
								} else {
									$aairport = $Spotter->getAllAirportInfo($spotter_item['arrival_airport']);
									if (isset($aairport[0]['latitude'])) {
										$cheading = $Common->getHeading($spotter_item['latitude'],$spotter_item['longitude'],$aairport[0]['latitude'],$aairport[0]['longitude']);
										$idistance = $Common->distance($spotter_item['latitude'],$spotter_item['longitude'],$aairport[0]['latitude'],$aairport[0]['longitude']);
										$farr_lat = $aairport[0]['latitude'];
										$farr_lon = $aairport[0]['longitude'];
									}
								}
							}
						}
						
						if ($compress)$output .= '"h": "'.$heading.'",';
						else $output .= '"heading": "'.$heading.'",';
						if ($currenttime != '') {
							if (strtotime($spotter_item['date']) < $currenttime) {
								if (isset($archivespeed)) {
									$nextcoord = $Common->nextcoord($spotter_item['latitude'],$spotter_item['longitude'],$spotter_item['ground_speed'],$heading,$archivespeed,($currenttime-strtotime($spotter_item['date'])+$globalMapRefresh));
									$fdistance = $Common->distance($spotter_item['latitude'],$spotter_item['longitude'],$nextcoord['latitude'],$nextcoord['longitude']);
									if (!isset($idistance) || $fdistance < $idistance) $output .= '"nextlatlon": ['.$nextcoord['latitude'].','.$nextcoord['longitude'].'],';
									else {
										$nextcoord = $Common->nextcoord($spotter_item['latitude'],$spotter_item['longitude'],$spotter_item['ground_speed'],$cheading,$archivespeed,($currenttime-strtotime($spotter_item['date'])+$globalMapRefresh));
										$fdistance = $Common->distance($spotter_item['latitude'],$spotter_item['longitude'],$nextcoord['latitude'],$nextcoord['longitude']);
										if (!isset($idistance) || $fdistance < $idistance) $output .= '"nextlatlon": ['.$nextcoord['latitude'].','.$nextcoord['longitude'].'],';
										else {
											$nextcoord = $Common->nextcoord($spotter_item['latitude'],$spotter_item['longitude'],$spotter_item['ground_speed'],$heading,$archivespeed);
											$output .= '"nextlatlon": ['.$nextcoord['latitude'].','.$nextcoord['longitude'].'],';
										}
									}
								} elseif ($usenextlatlon) {
									$nextcoord = $Common->nextcoord($spotter_item['latitude'],$spotter_item['longitude'],$spotter_item['ground_speed'],$heading,1,($currenttime-strtotime($spotter_item['date'])+$globalMapRefresh));
									$fdistance = $Common->distance($spotter_item['latitude'],$spotter_item['longitude'],$nextcoord['latitude'],$nextcoord['longitude']);
									if (!isset($idistance) || $fdistance < $idistance) $output .= '"nextlatlon": ['.$nextcoord['latitude'].','.$nextcoord['longitude'].'],';
									else {
										$nextcoord = $Common->nextcoord($spotter_item['latitude'],$spotter_item['longitude'],$spotter_item['ground_speed'],$cheading,1,($currenttime-strtotime($spotter_item['date'])+$globalMapRefresh));
										$fdistance = $Common->distance($spotter_item['latitude'],$spotter_item['longitude'],$nextcoord['latitude'],$nextcoord['longitude']);
										if (!isset($idistance) || $fdistance < $idistance) $output .= '"nextlatlon": ['.$nextcoord['latitude'].','.$nextcoord['longitude'].'],';
										else {
											$nextcoord = $Common->nextcoord($spotter_item['latitude'],$spotter_item['longitude'],$spotter_item['ground_speed'],$heading);
											$output .= '"nextlatlon": ['.$nextcoord['latitude'].','.$nextcoord['longitude'].'],';
										}
									}
								}
							} else {
								if (isset($archivespeed)) {
									$nextcoord = $Common->nextcoord($spotter_item['latitude'],$spotter_item['longitude'],$spotter_item['ground_speed'],$heading,$archivespeed);
									$output .= '"nextlatlon": ['.$nextcoord['latitude'].','.$nextcoord['longitude'].'],';
								} elseif ($usenextlatlon) {
									$nextcoord = $Common->nextcoord($spotter_item['latitude'],$spotter_item['longitude'],$spotter_item['ground_speed'],$heading);
									$output .= '"nextlatlon": ['.$nextcoord['latitude'].','.$nextcoord['longitude'].'],';
								}
							}
						} else {
							if (isset($archivespeed)) {
								$nextcoord = $Common->nextcoord($spotter_item['latitude'],$spotter_item['longitude'],$spotter_item['ground_speed'],$heading,$archivespeed);
								$fdistance = $Common->distance($spotter_item['latitude'],$spotter_item['longitude'],$nextcoord['latitude'],$nextcoord['longitude']);
								if (!isset($idistance) || $fdistance < $idistance) {
									$output .= '"nextlatlon": ['.$nextcoord['latitude'].','.$nextcoord['longitude'].'],';
								} else {
									$nextcoord = $Common->nextcoord($spotter_item['latitude'],$spotter_item['longitude'],$spotter_item['ground_speed'],$cheading,$archivespeed);
									//$fdistance = $Common->distance($spotter_item['latitude'],$spotter_item['longitude'],$nextcoord['latitude'],$nextcoord['longitude']);
									$output .= '"nextlatlon": ['.$nextcoord['latitude'].','.$nextcoord['longitude'].'],';
								}
							} elseif ($usenextlatlon) {
								$nextcoord = $Common->nextcoord($spotter_item['latitude'],$spotter_item['longitude'],$spotter_item['ground_speed'],$heading);
								$fdistance = $Common->distance($spotter_item['latitude'],$spotter_item['longitude'],$nextcoord['latitude'],$nextcoord['longitude']);
								if (!isset($idistance) || $fdistance < $idistance) {
										$output .= '"nextlatlon": ['.$nextcoord['latitude'].','.$nextcoord['longitude'].'],';
								} else {
									$nextcoord = $Common->nextcoord($spotter_item['latitude'],$spotter_item['longitude'],$spotter_item['ground_speed'],$cheading);
									//$fdistance = $Common->distance($spotter_item['latitude'],$spotter_item['longitude'],$nextcoord['latitude'],$nextcoord['longitude']);
									$output .= '"nextlatlon": ['.$nextcoord['latitude'].','.$nextcoord['longitude'].'],';
								}
							}
						}

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
						if (isset($spotter_item['captain_name'])) {
							$output .= '"cap": '.json_encode($spotter_item['captain_name']).',';
						}
						if (isset($spotter_item['race_id']) && $spotter_item['race_id'] != '') {
							$output .= '"rid": '.$spotter_item['race_id'].',';
						}
						if (isset($spotter_item['race_rank']) && $spotter_item['race_rank'] != '') {
							$output .= '"rrk": '.$spotter_item['race_rank'].',';
						}
						if (isset($spotter_item['race_name']) && $spotter_item['race_name'] != '') {
							$output .= '"rname": '.json_encode($spotter_item['race_name']).',';
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
						// type when not aircraft ?
						if (isset($spotter_item['type'])) {
							if ($compress) $output .= '"t": "'.$spotter_item['type'].'"';
							else $output .= '"type": "'.$spotter_item['type'].'"';
						} elseif ($marine) {
							if ($compress) $output .= '"t": "ship"';
							else $output .= '"type": "ship"';
						} else {
							if ($compress) $output .= '"t": "aircraft"';
							else $output .= '"type": "aircraft"';
						}
						$output .= '},';
						$output .= '"geometry": {';
							$output .= '"type": "Point",';
								$output .= '"coordinates": [';
								if ($currenttime != '') {
									if (strtotime($spotter_item['date']) < $currenttime) {
										if (!isset($archivespeed)) $archivespeed = 1;
										$nextcoord = $Common->nextcoord($spotter_item['latitude'],$spotter_item['longitude'],$spotter_item['ground_speed'],$heading,$archivespeed,($currenttime-strtotime($spotter_item['date'])));
										$fdistance = $Common->distance($spotter_item['latitude'],$spotter_item['longitude'],$nextcoord['latitude'],$nextcoord['longitude']);
										if (!isset($idistance) || $fdistance < $idistance) $output .= $nextcoord['longitude'].','.$nextcoord['latitude'];
										else {
											$nextcoord = $Common->nextcoord($spotter_item['latitude'],$spotter_item['longitude'],$spotter_item['ground_speed'],$cheading,$archivespeed,($currenttime-strtotime($spotter_item['date'])));
											$fdistance = $Common->distance($spotter_item['latitude'],$spotter_item['longitude'],$nextcoord['latitude'],$nextcoord['longitude']);
											if (!isset($idistance) || $fdistance < $idistance) $output .= $nextcoord['longitude'].','.$nextcoord['latitude'];
											else {
												$output .= $spotter_item['longitude'].', ';
												$output .= $spotter_item['latitude'];
											}
										}
										/*
										if (!isset($archivespeed)) $archivespeed = 1;
										$nextcoord = $Common->nextcoord($spotter_item['latitude'],$spotter_item['longitude'],$spotter_item['ground_speed'],$spotter_item['heading'],$archivespeed,$currenttime-strtotime($spotter_item['date']));
										$output .= $nextcoord['longitude'].','.$nextcoord['latitude'];
										*/
									} else {
										$output .= $spotter_item['longitude'].', ';
										$output .= $spotter_item['latitude'];
									}
								} else {
									$output .= $spotter_item['longitude'].', ';
									$output .= $spotter_item['latitude'];
								}
										/*
										.', ';
										$output .= $spotter_item['altitude']*30.48;
										$output .= ', '.strtotime($spotter_item['date']);
										*/
								$output .= ']';
							$output .= '}';
				$output .= '},';
					
			/*	} else {
								$output .= ', ';
								$output .= $spotter_item['longitude'].', ';
								$output .= $spotter_item['latitude'].', ';
								$output .= $spotter_item['altitude']*30.48;
								$output .= ', '.strtotime($spotter_item['date']);

				}
*/                

/*                
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
									$history_output = '';
									foreach ($spotter_history_array as $key => $spotter_history)
									{
										if (abs($spotter_history['longitude']-$spotter_item['longitude']) > 200 || $d==true) {
											if ($d == false) $d = true;
									        } else {
											$history_output .= '[';
											$history_output .=  $spotter_history['longitude'].', ';
											$history_output .=  $spotter_history['latitude'].',';
											$history_output .=  $spotter_history['altitude'];
											$history_output .= '],';

										}
									}
									if ($history_output != '') $output .= substr($history_output, 0, -1);
								$output .= ']';
							$output .= '}';
				$output .= '},';
                
			}
*/
				$history = filter_input(INPUT_GET,'history',FILTER_SANITIZE_STRING);
				if ($history == '' && isset($_COOKIE['history'])) $history = $_COOKIE['history'];
				
				if (
				    (isset($_COOKIE['flightpath']) && $_COOKIE['flightpath'] == 'true') 
				    || ((isset($globalMapHistory) && $globalMapHistory) || $allhistory)
				//    || (isset($history) && $history != '' && $history != 'NA' && ($history == $spotter_item['ident'] || $history == $spotter_item['flightaware_id']))
				//    || (isset($history) && $history != '' && $history != 'NA' && $history == $spotter_item['ident'])
				    || (isset($history) && $history != '' && $history != 'NA' && isset($spotter_item['flightaware_id']) && str_replace('-','',$history) == str_replace('-','',$spotter_item['flightaware_id']))
				    || (isset($history) && $history == '' && isset($spotter_item['flightaware_id']) && isset($_GET['flightaware_id']) && $_GET['flightaware_id'] == $spotter_item['flightaware_id'])
				    || (isset($history) && $history != '' && $history != 'NA' && isset($spotter_item['fammarine_id']) && str_replace('-','',$history) == str_replace('-','',$spotter_item['fammarine_id']))
				    || (isset($history) && $history == '' && isset($spotter_item['flightaware_id']) && isset($_GET['fammarine_id']) && $_GET['fammarine_id'] == $spotter_item['fammarine_id'])
				    || (isset($history) && $history != '' && $history != 'NA' && isset($spotter_item['famtrackid']) && str_replace('-','',$history) == str_replace('-','',$spotter_item['famtrackid']))
				    || (isset($history) && $history == '' && isset($spotter_item['flightaware_id']) && isset($_GET['famtrackid']) && $_GET['famtrackid'] == $spotter_item['famtrackid'])
				    ) {
					if ($tracker) {
						if ($from_archive || $globalArchive) {
							$spotter_history_array = $TrackerArchive->getAllArchiveTrackerDataById($spotter_item['famtrackid']);
						} else {
							$spotter_history_array = $TrackerLive->getAllLiveTrackerDataById($spotter_item['famtrackid']);
						}
						if (((isset($_COOKIE['mapmatching']) && $_COOKIE['mapmatching'] == 'true') ||
						    (!isset($_COOKIE['mapmatching']) && $globalMapMatching === TRUE)) && 
						    isset($_GET['zoom']) && $_GET['zoom'] > 12 && 
						    isset($spotter_item['type']) && (
							$spotter_item['type'] == 'Firetruck' ||
							$spotter_item['type'] == 'Ambulance' ||
							$spotter_item['type'] == 'Truck (18 Wheeler)' ||
							$spotter_item['type'] == 'Truck' ||
							$spotter_item['type'] == 'Mobile Satellite Station' ||
							$spotter_item['type'] == 'Van' ||
							$spotter_item['type'] == 'Police' ||
							$spotter_item['type'] == 'Bus' ||
							$spotter_item['type'] == 'Jeep' ||
							$spotter_item['type'] == 'Motorcycle' ||
							$spotter_item['type'] == 'Car'
						    )
						) {
							require(dirname(__FILE__).'/require/class.MapMatching.php');
							$MapMatching = new MapMatching();
							if (isset($spotter_item['date_iso_8601'])) {
								$spotter_history_array_mm = array_merge($spotter_history_array,array(array('latitude' => $spotter_item['latitude'],'longitude' => $spotter_item['longitude'],'date' => date('c',strtotime($spotter_item['date_iso_8601'])))));
							} else {
								$spotter_history_array_mm = array_merge($spotter_history_array,array(array('latitude' => $spotter_item['latitude'],'longitude' => $spotter_item['longitude'],'date' => date('c',strtotime($spotter_item['date'])))));
							}
							$spotter_history_array = $MapMatching->match($spotter_history_array_mm);
						}
					} elseif ($marine) {
						if ($from_archive || $globalArchive) {
							$spotter_history_array = $MarineArchive->getAllArchiveMarineDataById($spotter_item['fammarine_id']);
						} else {
							$spotter_history_array = $MarineLive->getAllLiveMarineDataById($spotter_item['fammarine_id']);
						}
					} else {
						if ($from_archive || $globalArchive) {
							$spotter_history_array = $SpotterArchive->getAllArchiveSpotterDataById($spotter_item['flightaware_id']);
							//print_r($spotter_history_array);
						} else {
							$spotter_history_array = $SpotterLive->getAllLiveSpotterDataById($spotter_item['flightaware_id']);
						}
					}
					$d = false;
					foreach ($spotter_history_array as $key => $spotter_history)
					{
						if (isset($spotter_history['altitude'])) {
							$alt = round($spotter_history['altitude']/10)*10;
							if (!isset($prev_alt) || $prev_alt != $alt) {
								if (isset($prev_alt)) {
									$output_history .= '['.$spotter_history['longitude'].', '.$spotter_history['latitude'].', '.$spotter_history['altitude'].']';
									$output_history .= ']}},';
									$output .= $output_history;
								}
								if ($compress) $output_history = '{"type": "Feature","properties": {"c": "'.$spotter_item['ident'].'","t": "history","a": "'.$alt.'"},"geometry": {"type": "LineString","coordinates": [';
								else $output_history = '{"type": "Feature","properties": {"callsign": "'.$spotter_item['ident'].'","type": "history","altitude": "'.$alt.'"},"geometry": {"type": "LineString","coordinates": [';
							}
							$output_history .= '[';
							$output_history .=  $spotter_history['longitude'].', ';
							$output_history .=  $spotter_history['latitude'].', ';
							$output_history .=  $spotter_history['altitude']*30.48;
							$output_history .= '],';
							/*
							if ($from_archive === false) {
								$output_history .= '[';
								$output_history .=  $spotter_item['longitude'].', ';
								$output_history .=  $spotter_item['latitude'].', ';
								$output_history .=  $spotter_item['altitude']*30.48;
								$output_history .= '],';
							}
							*/
							$prev_alt = $alt;
						} else {
							if ($d === false) {
								if ($compress) {
									$output_history = '{"type": "Feature","properties": {"c": "'.$spotter_item['ident'].'",';
									if (isset($spotter_history_array[0]['mapmatching_engine']) && $spotter_history_array[0]['mapmatching_engine'] == 'graphhopper') $output_history .= '"atr": "Powered by <a href=\"https://www.graphhopper.com/\">GraphHopper API</a>", Map matching engine use data from © <a href=\"http://www.openstreetmap.org/copyright\">OpenStreetMap</a>",';
									elseif (isset($spotter_history_array[0]['mapmatching_engine'])) $output_history .= '"atr": "Map matching engine use data from © <a href=\"http://www.openstreetmap.org/copyright\">OpenStreetMap</a>",';
									$output_history .= '"t": "history"},"geometry": {"type": "LineString","coordinates": [';
								} else $output_history = '{"type": "Feature","properties": {"callsign": "'.$spotter_item['ident'].'","type": "history"},"geometry": {"type": "LineString","coordinates": [';
								$d = true;
							}
							$output_history .= '[';
							$output_history .=  $spotter_history['longitude'].', ';
							$output_history .=  $spotter_history['latitude'];
							$output_history .= '],';
							/*
							if ($from_archive === false) {
								$output_history .= '[';
								$output_history .=  $spotter_item['longitude'].', ';
								$output_history .=  $spotter_item['latitude'];
								$output_history .= '],';
							}
							*/
						}
					}
					if (isset($output_history)) {
						//echo $output_history;
						if ($from_archive === false && !isset($spotter_history_array[0]['mapmatching_engine'])) {
							$output_historyd = '[';
							$output_historyd .=  $spotter_item['longitude'].', ';
							$output_historyd .=  $spotter_item['latitude'];
							if (isset($spotter_history['altitude'])) $output_historyd .=  ','.$spotter_item['altitude']*30.48;
							$output_historyd .= '],';
							//$output_history = $output_historyd.$output_history;
							$output_history = $output_history.$output_historyd;
						} elseif (isset($spotter_history_array[0]['mapmatching_engine'])) {
							$last = array_pop($spotter_history_array);
							$latitude = $last['latitude'];
							$longitude = $last['longitude'];
							$output = str_replace('"coordinates": ['.$spotter_item['longitude'].', '.$spotter_item['latitude'].']}','"coordinates": ['.$longitude.', '.$latitude.']}',$output);
						}
						
						$output_history  = substr($output_history, 0, -1);
						$output_history .= ']}},';
						$output .= $output_history;
						unset($prev_alt);
						unset($output_history);
					}
					
				}
				
				if (((isset($history) && $history != '' && $history != 'NA' && isset($spotter_item['flightaware_id']) && str_replace('-','',$history) == str_replace('-','',$spotter_item['flightaware_id']))
				    || (isset($history) && $history == '' && isset($spotter_item['flightaware_id']) && isset($_GET['flightaware_id']) && $_GET['flightaware_id'] == $spotter_item['flightaware_id']))
				     && (isset($spotter_item['departure_airport']) 
				        && $spotter_item['departure_airport'] != 'NA' 
				        && isset($spotter_item['arrival_airport']) 
				        && $spotter_item['arrival_airport'] != 'NA' 
				        && ((isset($_COOKIE['MapRoute']) && $_COOKIE['MapRoute'] == "true") 
				    	    || (!isset($_COOKIE['MapRoute']) && isset($globalMapRoute) && $globalMapRoute)))) {
				    if ($compress) $output_air = '{"type": "Feature","properties": {"c": "'.$spotter_item['ident'].'","t": "route"},"geometry": {"type": "LineString","coordinates": [';
				    else $output_air = '{"type": "Feature","properties": {"callsign": "'.$spotter_item['ident'].'","type": "route"},"geometry": {"type": "LineString","coordinates": [';
				    if (isset($spotter_item['departure_airport_latitude'])) {
					$output_air .= '['.$spotter_item['departure_airport_longitude'].','.$spotter_item['departure_airport_latitude'].'],';
				    } elseif (isset($spotter_item['departure_airport']) && $spotter_item['departure_airport'] != 'NA') {
					$dairport = $Spotter->getAllAirportInfo($spotter_item['departure_airport']);
					if (isset($dairport[0]['latitude'])) {
					    $output_air .= '['.$dairport[0]['longitude'].','.$dairport[0]['latitude'].'],';
					}
				    }
				    if (isset($spotter_item['arrival_airport_latitude'])) {
					$output_air .= '['.$spotter_item['arrival_airport_longitude'].','.$spotter_item['arrival_airport_latitude'].'],';
				    } elseif (isset($spotter_item['arrival_airport']) && $spotter_item['arrival_airport'] != 'NA') {
					$aairport = $Spotter->getAllAirportInfo($spotter_item['arrival_airport']);
					if (isset($aairport[0]['latitude'])) {
					    $output_air .= '['.$aairport[0]['longitude'].','.$aairport[0]['latitude'].'],';
					}
				    }
				    $output_air  = substr($output_air, 0, -1);
				    $output_air .= ']}},';
				    $output .= $output_air;
				    unset($output_air);
				}

				//if (isset($history) && $history != '' && $history == $spotter_item['ident'] && isset($spotter_item['departure_airport']) && $spotter_item['departure_airport'] != 'NA' && isset($spotter_item['arrival_airport']) && $spotter_item['arrival_airport'] != 'NA' && ((isset($_COOKIE['MapRoute']) && $_COOKIE['MapRoute'] == "true") || (!isset($_COOKIE['MapRoute']) && (!isset($globalMapRoute) || (isset($globalMapRoute) && $globalMapRoute))))) {
				//if (isset($history) && $history != '' && $history == $spotter_item['ident'] && isset($spotter_item['arrival_airport']) && $spotter_item['arrival_airport'] != 'NA' && ((isset($_COOKIE['MapRoute']) && $_COOKIE['MapRoute'] == "true") || (!isset($_COOKIE['MapRoute']) && (!isset($globalMapRoute) || (isset($globalMapRoute) && $globalMapRoute))))) {
				if (((isset($history) && $history != '' && $history != 'NA' && isset($spotter_item['flightaware_id']) && str_replace('-','',$history) == str_replace('-','',$spotter_item['flightaware_id']))
				    || (isset($history) && $history == '' && isset($spotter_item['flightaware_id']) && isset($_GET['flightaware_id']) && $_GET['flightaware_id'] == $spotter_item['flightaware_id']))
				     && (isset($spotter_item['arrival_airport']) 
				        && $spotter_item['arrival_airport'] != 'NA' 
				        && ((isset($_COOKIE['MapRemainingRoute']) && $_COOKIE['MapRemainingRoute'] == "true") 
				    	    || (!isset($_COOKIE['MapRemainingRoute']) && (!isset($globalMapRemainingRoute) 
				    	    || (isset($globalMapRemainingRoute) && $globalMapRemainingRoute)))))) {
				    $havedata = false;
				    if ($compress) $output_dest = '{"type": "Feature","properties": {"c": "'.$spotter_item['ident'].'","t": "routedest"},"geometry": {"type": "LineString","coordinates": [';
				    else $output_dest = '{"type": "Feature","properties": {"callsign": "'.$spotter_item['ident'].'","type": "routedest"},"geometry": {"type": "LineString","coordinates": [';
				    
				    //$output_dest .= '['.$spotter_item['longitude'].','.$spotter_item['latitude'].'],';
				    if (isset($spotter_item['arrival_airport_latitude'])) {
					//$output_dest .= '['.$spotter_item['arrival_airport_longitude'].','.$spotter_item['arrival_airport_latitude'].']';
					$end_lon = $spotter_item['arrival_airport_longitude'];
					$end_lat = $spotter_item['arrival_airport_latitude'];
					$havedata = true;
				    } elseif (isset($spotter_item['arrival_airport']) && $spotter_item['arrival_airport'] != 'NA') {
					$aairport = $Spotter->getAllAirportInfo($spotter_item['arrival_airport']);
					if (isset($aairport[0]['latitude'])) {
					    //$output_dest .= '['.$aairport[0]['longitude'].','.$aairport[0]['latitude'].']';
					    $end_lon = $aairport[0]['longitude'];
					    $end_lat = $aairport[0]['latitude'];
					    $havedata = true;
					}
				    }
				    if ($havedata) {
					$line = $Common->greatCircle($spotter_item['latitude'],$spotter_item['longitude'],$end_lat,$end_lon);
					foreach ($line[0] as $coord) {
						$output_dest .= '['.$coord[0].','.$coord[1].'],';
					}
					$output_dest  = substr($output_dest, 0, -1);
				    }
				    $output_dest .= ']}},';
				    if ($havedata) $output .= $output_dest;
				    unset($output_dest);
				}
			}
			$output  = substr($output, 0, -1);
			$output .= ']';
			$output .= ',"initial_sqltime": "'.$sqltime.'",';
			$output .= '"totaltime": "'.round(microtime(true)-$begintime,2).'",';
			if (isset($begindate)) $output .= '"archive_date": "'.$begindate.'",';
			$output .= '"fc": "'.$j.'"';
		} else {
			$output .= '"features": ';
			$output .= '[{';
			$output .= '"type": "Feature",';
			$output .= '"properties": {';
			$output .= '"fc": "'.$flightcnt.'",';
			$output .= '"empty": "true"}';
			$output .= ',"geometry": {"type": "Point","coordinates": [0, 0]}';
			$output .= '}]';
		}
		
$output .= '}';

print $output;

?>
