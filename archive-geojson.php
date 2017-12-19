<?php
// This script can be slow...
set_time_limit(0);
ini_set('max_execution_time',6000);
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

if (isset($_GET['tracker'])) {
	$tracker = true;
}
if (isset($_GET['marine'])) {
	$marine = true;
}
if ($tracker) {
	require_once('require/class.Tracker.php');
	require_once('require/class.TrackerArchive.php');
}
elseif ($marine) {
	require_once('require/class.Marine.php');
	require_once('require/class.MarineArchive.php');
}
else {
	require_once('require/class.Spotter.php');
	require_once('require/class.SpotterArchive.php');
}
$begintime = microtime(true);
if ($tracker) {
	$Tracker = new Tracker();
	$TrackerArchive = new TrackerArchive();
}
elseif ($marine) {
	$Marine = new Marine();
	$MarineArchive = new MarineArchive();
}
else {
	$Spotter = new Spotter();
	$SpotterArchive = new SpotterArchive();
}
$Common = new Common();

if (isset($_GET['download'])) {
	if ($_GET['download'] == "true") {
		header('Content-disposition: attachment; filename="flightairmap-archive.json"');
	}
}
header('Content-Type: text/javascript');

if (!isset($globalJsonCompress)) $compress = true;
else $compress = $globalJsonCompress;

$from_archive = false;
$min = false;
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

if (isset($globalMapPopup) && !$globalMapPopup && !(isset($_COOKIE['flightpopup']) && $_COOKIE['flightpopup'] == 'true')) {
	$min = true;
} else $min = false;

if (isset($_GET['ident'])) {
	$ident = filter_input(INPUT_GET,'ident',FILTER_SANITIZE_STRING);
	$from_archive = true;
	if ($tracker) {
		$spotter_array = $TrackerArchive->getLastArchiveTrackerDataByIdent($ident);
	}
	elseif ($marine) {
		$spotter_array = $MarineArchive->getLastArchiveMarineDataByIdent($ident);
	}
	else {
		$spotter_array = $SpotterArchive->getLastArchiveSpotterDataByIdent($ident);
	}
	$allhistory = true;
}
elseif (isset($_GET['flightaware_id'])) {
	$flightaware_id = filter_input(INPUT_GET,'flightaware_id',FILTER_SANITIZE_STRING);
	$from_archive = true;
	if ($tracker) {
		$spotter_array = $TrackerArchive->getLastArchiveTrackerDataById($flightaware_id);
	}
	elseif ($marine) {
		$spotter_array = $MarineArchive->getLastArchiveMarineDataById($flightaware_id);
	}
	else {
		$spotter_array = $SpotterArchive->getLastArchiveSpotterDataById($flightaware_id);
	}
	$allhistory = true;
}
elseif (isset($_GET['archive']) && isset($_GET['begindate']) && isset($_GET['enddate']) && isset($_GET['speed'])) {
	$from_archive = true;
	$begindate = filter_input(INPUT_GET,'begindate',FILTER_SANITIZE_NUMBER_INT);
	if (isset($globalAircraftMaxUpdate)) $begindate = $begindate - $globalAircraftMaxUpdate;
	else $begindate = $begindate - 3000;
	$enddate = filter_input(INPUT_GET,'enddate',FILTER_SANITIZE_NUMBER_INT);
	$archivespeed = filter_input(INPUT_GET,'speed',FILTER_SANITIZE_NUMBER_INT);
	$part = filter_input(INPUT_GET,'part',FILTER_SANITIZE_NUMBER_INT);
	if ($part == '') $part = 0;
	
	if ($begindate != '' && $enddate != '') {
		$begindate = date('Y-m-d H:i:s',$begindate);
		$enddate = date('Y-m-d H:i:s',$enddate);
		//$spotter_array = $SpotterArchive->getMinLiveSpotterDataPlayback($begindate,$enddate,$filter);
		if ($tracker) {
			$spotter_array = $TrackerArchive->getMinLiveTrackerData($begindate,$enddate,$filter);
		}
		elseif ($marine) {
			$spotter_array = $MarineArchive->getMinLiveMarineData($begindate,$enddate,$filter);
		}
		else {
			$spotter_array = $SpotterArchive->getMinLiveSpotterData($begindate,$enddate,$filter,$part);
		}
	}
}

if (!empty($spotter_array)) {
	//$flightcnt = $SpotterArchive->getLiveSpotterCount($begindate,$enddate,$filter);
	$flightcnt = 0;
	if ($flightcnt == '') $flightcnt = 0;
} else $flightcnt = 0;

$sqltime = round(microtime(true)-$begintime,2);

$pfi = '';
//var_dump($spotter_array);
$j = 0;
$aircrafts_shadow = array();
$output = '{';
$output .= '"type": "FeatureCollection",';
if ($min) $output .= '"minimal": "true",';
else $output .= '"minimal": "false",';
$output .= '"fc": "'.$flightcnt.'",';
$output .= '"sqt": "'.$sqltime.'",';
$begin = true;
if (!empty($spotter_array) && is_array($spotter_array)) {
	$output .= '"features": [';
	foreach($spotter_array as $spotter_item) {
		$j++;
		date_default_timezone_set('UTC');
		if ($tracker) {
			if ($pfi != $spotter_item['famtrackid']) {
				$pfi = $spotter_item['famtrackid'];
				$begin = true;
			} else $spotter_history_array = 0;
		}
		elseif ($marine) {
			if ($pfi != $spotter_item['fammarine_d']) {
				$pfi = $spotter_item['fammarine_id'];
				$begin = true;
			} else $spotter_history_array = 0;
		}
		else {
			if ($pfi != $spotter_item['flightaware_id']) {
				$pfi = $spotter_item['flightaware_id'];
				$begin = true;
			}
		}
		if ($begin) {
			if ($j > 1) {
				if (isset($output_time)) {
					$output_time  = substr($output_time, 0, -1);
					$output .= '"time": ['.$output_time.']';
				}
				$output .= '},';
				$output .= '"geometry": {';
				//$output .= '"type": "MultiPoint",';
				$output .= '"type": "LineString",';
				$output .= '"coordinates": [';
				if (isset($output_history)) {
					$output_history  = substr($output_history, 0, -1);
					$output .= $output_history;
				}
				$output .= ']}},';
			}
			$pfi = $spotter_item['flightaware_id'];
			$output_history = '';
			$output_time = '';
			$output_timediff = '';
			$previousts = 0;
			$end = false;
			$k = 0;
		}

		if ($end === false) {
			$k++;
			$output_history .= '['.$spotter_item['longitude'].', '.$spotter_item['latitude'].'],';
			$output_time .= (strtotime($spotter_item['date'])*1000).',';
			$previousts = strtotime($spotter_item['date']);
			if ($k > 1 && (strtotime($spotter_item['date'])*1000 > $enddate)) $end = true;
		}

		if ($begin) {
			$begin = false;
			//location of aircraft
			$output .= '{';
			$output .= '"type": "Feature",';
			$output .= '"properties": {';
			$output .= '"fi": "'.$pfi.'",';
			if (isset($begindate)) $output .= '"archive_date": "'.$begindate.'",';
			if (isset($spotter_item['ident']) && $spotter_item['ident'] != '') {
				$output .= '"c": "'.str_replace('\\','',$spotter_item['ident']).'",';
				//"
			} else {
				$output .= '"c": "NA",';
			}
			if (!isset($spotter_item['aircraft_shadow']) && !$tracker && !$marine) {
				if (!isset($spotter_item['aircraft_icao']) || $spotter_item['aircraft_icao'] == '') $spotter_item['aircraft_shadow'] = '';
				else {
					$aircraft_icao = $spotter_item['aircraft_icao'];
					$aircraft_info = $Spotter->getAllAircraftInfo($spotter_item['aircraft_icao']);
					if (count($aircraft_info) > 0) $spotter_item['aircraft_shadow'] = $aircraft_info[0]['aircraft_shadow'];
					elseif (isset($spotter_item['format_source']) && $spotter_item['format_source'] == 'aprs') $spotter_item['aircraft_shadow'] = 'PA18.png';
					elseif ($aircraft_icao == 'PARAGLIDER') $spotter_item['aircraft_shadow'] = 'PARAGLIDER.png';
					else $spotter_item['aircraft_shadow'] = '';
					$aircrafts_shadow[$aircraft_icao] = $spotter_item['aircraft_shadow'];
				}
			}

			if (!isset($spotter_item['aircraft_shadow']) || $spotter_item['aircraft_shadow'] == '') {
				if ($tracker) {
					if (isset($spotter_item['type']) && $spotter_item['type'] == 'Ambulance') {
						if ($compress) $output .= '"as": "ambulance.png",';
						else $output .= '"aircraft_shadow": "ambulance.png",';
					}
					elseif (isset($spotter_item['type']) && $spotter_item['type'] == 'Police') {
						if ($compress) $output .= '"as": "police.png",';
						else $output .= '"aircraft_shadow": "police.png",';
					}
					elseif (isset($spotter_item['type']) && $spotter_item['type'] == 'Yacht (Sail)') {
						if ($compress) $output .= '"as": "ship.png",';
						else $output .= '"aircraft_shadow": "ship.png",';
					}
					elseif (isset($spotter_item['type']) && $spotter_item['type'] == 'Ship (Power Boat)') {
						if ($compress) $output .= '"as": "ship.png",';
						else $output .= '"aircraft_shadow": "ship.png",';
					}
					elseif (isset($spotter_item['type']) && $spotter_item['type'] == 'Shuttle') {
						if ($compress) $output .= '"as": "ship.png",';
						else $output .= '"aircraft_shadow": "ship.png",';
					}
					elseif (isset($spotter_item['type']) && $spotter_item['type'] == 'Truck') {
						if ($compress) $output .= '"as": "truck.png",';
						else $output .= '"aircraft_shadow": "truck.png",';
					}
					elseif (isset($spotter_item['type']) && $spotter_item['type'] == 'Truck (18 Wheeler)') {
						if ($compress) $output .= '"as": "truck.png",';
						else $output .= '"aircraft_shadow": "truck.png",';
					}
					elseif (isset($spotter_item['type']) && $spotter_item['type'] == 'Aircraft (small)') {
						if ($compress) $output .= '"as": "aircraft.png",';
						else $output .= '"aircraft_shadow": "aircraft.png",';
					}
					elseif (isset($spotter_item['type']) && $spotter_item['type'] == 'Large Aircraft') {
						if ($compress) $output .= '"as": "aircraft.png",';
						else $output .= '"aircraft_shadow": "aircraft.png",';
					}
					elseif (isset($spotter_item['type']) && $spotter_item['type'] == 'Helicopter') {
						if ($compress) $output .= '"as": "helico.png",';
						else $output .= '"aircraft_shadow": "helico.png",';
					}
					elseif (isset($spotter_item['type']) && $spotter_item['type'] == 'Railroad Engine') {
						if ($compress) $output .= '"as": "rail.png",';
						else $output .= '"aircraft_shadow": "rail.png",';
					}
					elseif (isset($spotter_item['type']) && $spotter_item['type'] == 'Firetruck') {
						if ($compress) $output .= '"as": "firetruck.png",';
						else $output .= '"aircraft_shadow": "firetruck.png",';
					}
					elseif (isset($spotter_item['type']) && $spotter_item['type'] == 'Bus') {
						if ($compress) $output .= '"as": "bus.png",';
						else $output .= '"aircraft_shadow": "bus.png",';
					}
					elseif (isset($spotter_item['type']) && $spotter_item['type'] == 'Phone') {
						if ($compress) $output .= '"as": "phone.png",';
						else $output .= '"aircraft_shadow": "phone.png",';
					}
					elseif (isset($spotter_item['type']) && $spotter_item['type'] == 'Jogger') {
						if ($compress) $output .= '"as": "jogger.png",';
						else $output .= '"aircraft_shadow": "jogger.png",';
					}
					elseif (isset($spotter_item['type']) && $spotter_item['type'] == 'Bike') {
						if ($compress) $output .= '"as": "bike.png",';
						else $output .= '"aircraft_shadow": "bike.png",';
					}
					elseif (isset($spotter_item['type']) && $spotter_item['type'] == 'Motorcycle') {
						if ($compress) $output .= '"as": "motorcycle.png",';
						else $output .= '"aircraft_shadow": "motorcycle.png",';
					}
					elseif (isset($spotter_item['type']) && $spotter_item['type'] == 'Balloon') {
						if ($compress) $output .= '"as": "balloon.png",';
						else $output .= '"aircraft_shadow": "balloon.png",';
					}
					else {
						if ($compress) $output .= '"as": "car.png",';
						else $output .= '"aircraft_shadow": "car.png",';
					}
				}
				elseif ($marine) {
					if ($compress) $output .= '"as": "ship.png",';
					else $output .= '"aircraft_shadow": "ship.png",';
				}
				else {
					if ($compress) $output .= '"as": "default.png",';
					else $output .= '"aircraft_shadow": "default.png",';
				}
			} else {
				if ($compress) $output .= '"as": "'.$spotter_item['aircraft_shadow'].'",';
				else $output .= '"aircraft_shadow": "'.$spotter_item['aircraft_shadow'].'",';
			}

			if (isset($spotter_item['date_iso_8601'])) {
				$output .= '"date_update": "'.date("M j, Y, g:i a T", strtotime($spotter_item['date_iso_8601'])).'",';
			}
			if (isset($spotter_item['date'])) {
				$output .= '"lu": "'.strtotime($spotter_item['date']).'",';
			}
			if (isset($spotter_item['squawk'])) {
				$output .= '"sq": "'.$spotter_item['squawk'].'",';
			}
			if (isset($spotter_item['squawk_usage'])) {
				$output .= '"squawk_usage": "'.$spotter_item['squawk_usage'].'",';
			}
			if (isset($spotter_item['type'])) {
				$output .= '"t": "'.$spotter_item['type'].'",';
			} elseif ($marine) {
				$output .= '"t": "ship",';
			} else {
				$output .= '"t": "aircraft",';
			}
		}
	}

	if ($j > 1) {
		if (isset($output_time)) {
			$output_time  = substr($output_time, 0, -1);
			$output .= '"time": ['.$output_time.']';
		}
		$output .= '},';
		$output .= '"geometry": {';
		//$output .= '"type": "MultiPoint",';
		$output .= '"type": "LineString",';
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
