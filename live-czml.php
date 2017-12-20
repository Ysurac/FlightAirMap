<?php
/**
 * This file is part of FlightAirmap.
 *
 * Copyright (c) Ycarus (Yannick Chabanois) <support@flightairmap.com>
 * Licensed under AGPL license.
 * For more information see: https://www.flightairmap.com/
*/
require_once('require/class.Connection.php');
require_once('require/class.Common.php');
if (isset($globalProtect) && $globalProtect) {
	@session_start();
	if (!isset($_SESSION['protect']) || !isset($_SERVER['HTTP_REFERER'])) {
		echo 'You must access this page using the right way.';
		die();
	}
}

$no3dmodels = false; // Only for testing
$one3dmodel = false; // Only for testing
if ((isset($globalMap3DForceModel) && $globalMap3DForceModel != '') || (isset($globalMap3DOneModel) && $globalMap3DOneModel)) {
	$one3dmodel = true;
}
if (isset($_COOKIE['one3dmodel']) && $_COOKIE['one3dmodel'] == 'true') {
	$one3dmodel = true;
}
$tracker = false;
$marine = false;
if (isset($_GET['tracker'])) $tracker = true;
if (isset($_GET['marine'])) $marine = true;
if ($tracker) {
	require_once('require/class.Tracker.php');
	require_once('require/class.TrackerLive.php');
	require_once('require/class.TrackerArchive.php');
	$TrackerLive = new TrackerLive();
	$Tracker = new Tracker();
	$TrackerArchive = new TrackerArchive();
} elseif ($marine) {
	require_once('require/class.Marine.php');
	require_once('require/class.MarineLive.php');
	require_once('require/class.MarineArchive.php');
	$MarineLive = new MarineLive();
	$Marine = new Marine();
	$MarineArchive = new MarineArchive();
} else {
	require_once('require/class.Spotter.php');
	require_once('require/class.SpotterLive.php');
	require_once('require/class.SpotterArchive.php');
	$SpotterLive = new SpotterLive();
	$Spotter = new Spotter();
	$SpotterArchive = new SpotterArchive();
}

date_default_timezone_set('UTC');
$begintime = microtime(true);
$Common = new Common();


function quaternionrotate($heading, $attitude = 0, $bank = 0) {
    // Assuming the angles are in radians.
    $c1 = cos($heading/2);
    $s1 = sin($heading/2);
    $c2 = cos($attitude/2);
    $s2 = sin($attitude/2);
    $c3 = cos($bank/2);
    $s3 = sin($bank/2);
    $c1c2 = $c1*$c2;
    $s1s2 = $s1*$s2;
    $w =$c1c2*$c3 - $s1s2*$s3;
    $x =$c1c2*$s3 + $s1s2*$c3;
    $y =$s1*$c2*$c3 + $c1*$s2*$s3;
    $z =$c1*$s2*$c3 - $s1*$c2*$s3;
    return array('x' => $x,'y' => $y,'z' => $z,'w' => $w);
//    return array('x' => '0.0','y' => '-0.931','z' => '0.0','w' => '0.365');

}


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
$limit = 0;
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

if (isset($_COOKIE['map_3d_limit'])) {
	$limit = filter_var($_COOKIE['map_3d_limit'],FILTER_SANITIZE_NUMBER_INT);
}

/*
if (isset($globalMapPopup) && !$globalMapPopup && !(isset($_COOKIE['flightpopup']) && $_COOKIE['flightpopup'] == 'true')) {
	$min = true;
}

if (isset($_GET['ident'])) {
	$ident = filter_input(INPUT_GET,'ident',FILTER_SANITIZE_STRING);
	$spotter_array = $SpotterLive->getLastLiveSpotterDataByIdent($ident);
	if (empty($spotter_array)) {
		$from_archive = true;
		$spotter_array = $SpotterArchive->getLastArchiveSpotterDataByIdent($ident);
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
} elseif (isset($_GET['coord'])) {
	$coord = explode(',',$_GET['coord']);
	$spotter_array = $SpotterLive->getLiveSpotterDatabyCoord($coord,$filter);
} elseif (isset($_GET['archive']) && isset($_GET['begindate']) && isset($_GET['enddate']) && isset($_GET['speed'])) {
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
	//$spotter_array = $SpotterLive->getMinLiveSpotterData($filter);
	$spotter_array = $SpotterLive->getMinLastLiveSpotterData($filter);
#	$min = true;
} else {
	$spotter_array = $SpotterLive->getLiveSpotterData('','',$filter);
}
*/
if (isset($_GET['archive']) && isset($_GET['begindate']) && isset($_GET['enddate']) && isset($_GET['speed'])) {
	$from_archive = true;
//	$begindate = filter_input(INPUT_GET,'begindate',FILTER_VALIDATE_REGEXP,array("options"=>array("regexp"=>'~^\d{4}/\d{2}/\d{2}$~')));
//	$enddate = filter_input(INPUT_GET,'enddate',FILTER_VALIDATE_REGEXP,array("options"=>array("regexp"=>'~^\d{4}/\d{2}/\d{2}$~')));
	$begindate = filter_input(INPUT_GET,'begindate',FILTER_SANITIZE_NUMBER_INT);
	$enddate = filter_input(INPUT_GET,'enddate',FILTER_SANITIZE_NUMBER_INT);
	$archivespeed = filter_input(INPUT_GET,'speed',FILTER_SANITIZE_NUMBER_INT);
	$begindate = date('Y-m-d H:i:s',$begindate);
	$enddate = date('Y-m-d H:i:s',$enddate);
	if ($tracker) {
		$spotter_array = $TrackerArchive->getMinLiveTrackerDataPlayback($begindate,$enddate,$filter);
	} elseif ($marine) {
		$spotter_array = $MarineArchive->getMinLiveMarineDataPlayback($begindate,$enddate,$filter);
	} else {
		$spotter_array = $SpotterArchive->getMinLiveSpotterDataPlayback($begindate,$enddate,$filter);
	}
} elseif (isset($_COOKIE['archive']) && isset($_COOKIE['archive_begin']) && isset($_COOKIE['archive_end']) && isset($_COOKIE['archive_speed'])) {
	$from_archive = true;
//	$begindate = filter_input(INPUT_GET,'begindate',FILTER_VALIDATE_REGEXP,array("options"=>array("regexp"=>'~^\d{4}/\d{2}/\d{2}$~')));
//	$enddate = filter_input(INPUT_GET,'enddate',FILTER_VALIDATE_REGEXP,array("options"=>array("regexp"=>'~^\d{4}/\d{2}/\d{2}$~')));
//	$begindate = filter_var($_COOKIE['archive_begin'],FILTER_SANITIZE_NUMBER_INT);
//	$enddate = filter_var($_COOKIE['archive_end'],FILTER_SANITIZE_NUMBER_INT);
	$begindate = $_COOKIE['archive_begin'];
	$begindateinitial = $_COOKIE['archive_begin'];
	if (isset($globalAircraftMaxUpdate)) {
		$begindate = $begindate - $globalAircraftMaxUpdate;
	} else {
		$begindate = $begindate - 3000;
	}
	$enddate = $_COOKIE['archive_end'];
	$enddateinitial = $_COOKIE['archive_end'];
	$archivespeed = filter_var($_COOKIE['archive_speed'],FILTER_SANITIZE_NUMBER_INT);
	$begindate = date('Y-m-d H:i:s',$begindate);
	$enddate = date('Y-m-d H:i:s',$enddate);
	//echo 'Begin : '.$begindate.' - End : '.$enddate."\n";
	if ($tracker) {
		$spotter_array = $TrackerArchive->getMinLiveTrackerData($begindate,$enddate,$filter);
	} elseif ($marine) {
		$spotter_array = $MarineArchive->getMinLiveMarineData($begindate,$enddate,$filter);
	} else {
		$spotter_array = $SpotterArchive->getMinLiveSpotterData($begindate,$enddate,$filter);
	}
} elseif ($tracker) {
	$coord = array();
	if (isset($_GET['coord']) && $_GET['coord'] != '') {
		$coord = explode(',',$_GET['coord']);
		if (!(filter_var($coord[0],FILTER_VALIDATE_FLOAT) && filter_var($coord[1],FILTER_VALIDATE_FLOAT) && filter_var($coord[2],FILTER_VALIDATE_FLOAT) && filter_var($coord[3],FILTER_VALIDATE_FLOAT) 
		    && $coord[0] > -180.0 && $coord[0] < 180.0 && $coord[1] > -90.0 && $coord[1] < 90.0 && $coord[2] > -180.0 && $coord[2] < 180.0 && $coord[3] > -90.0 && $coord[3] < 90.0)) {
			$coord = array();
		}
	}
	$previous_filter = $filter;
	if ((isset($_COOKIE['singlemodel']) && $_COOKIE['singlemodel'] == 'true') && (isset($_COOKIE['MapTrackTracker']) && $_COOKIE['MapTrackTracker'] != '')) {
		$filter = array_merge($filter,array('id' => $_COOKIE['MapTrackTracker']));
		$spotter_array = $TrackerLive->getMinLastLiveTrackerData($coord,$filter,false);
	/*
	} elseif (isset($_COOKIE['MapTrack']) && $_COOKIE['MapTrack'] != '' && !empty($coord)) {
		$spotter_array = $TrackerLive->getMinLastLiveTrackerData($coord,$filter,true,$_COOKIE['MapTrack']);
	*/
	} elseif (!isset($_COOKIE['singlemodel']) || $_COOKIE['singlemodel'] == 'false') {
		$spotter_array = $TrackerLive->getMinLastLiveTrackerData($coord,$filter,false);
	} else {
		$spotter_array = array();
	}
	$filter = $previous_filter;
} elseif ($marine) {
	$coord = array();
	//if (isset($_GET['coord']) && $_GET['coord'] != '') {
	if (!((isset($_COOKIE['singlemodel']) && $_COOKIE['singlemodel'] == 'true') && (isset($_COOKIE['MapTrackMarine']) && $_COOKIE['MapTrackMarine'] != '')) && isset($_GET['coord']) && $_GET['coord'] != '') {
		$coord = explode(',',$_GET['coord']);
		if (!(filter_var($coord[0],FILTER_VALIDATE_FLOAT) && filter_var($coord[1],FILTER_VALIDATE_FLOAT) && filter_var($coord[2],FILTER_VALIDATE_FLOAT) && filter_var($coord[3],FILTER_VALIDATE_FLOAT) 
		    && $coord[0] > -180.0 && $coord[0] < 180.0 && $coord[1] > -90.0 && $coord[1] < 90.0 && $coord[2] > -180.0 && $coord[2] < 180.0 && $coord[3] > -90.0 && $coord[3] < 90.0)) {
			$coord = array();
		}
	}
	$previous_filter = $filter;
	if (((isset($_COOKIE['singlemodel']) && $_COOKIE['singlemodel'] == 'true') || (!isset($_COOKIE['singlemodel']) && isset($globalMap3DSelected) && $globalMap3DSelected)) && (isset($_COOKIE['MapTrackMarine']) && $_COOKIE['MapTrackMarine'] != '')) {
		//$filter = array_merge($filter,array('id' => $_COOKIE['MapTrackMarine']));
		//$spotter_array = $MarineLive->getMinLastLiveMarineData($coord,$filter,false);
		$spotter_array = $MarineLive->getMinLastLiveMarineDataByID($_COOKIE['MapTrackMarine'],$filter,false);
	} elseif (isset($_COOKIE['MapTrackMarine']) && $_COOKIE['MapTrackMarine'] != '' && !empty($coord)) {
		$spotter_array = $MarineLive->getMinLastLiveMarineData($coord,$filter,false,$_COOKIE['MapTrack']);
	} elseif (!isset($_COOKIE['singlemodel']) || $_COOKIE['singlemodel'] == 'false') {
		$spotter_array = $MarineLive->getMinLastLiveMarineData($coord,$filter,false);
	} else {
		$spotter_array = array();
	}
	$filter = $previous_filter;
} else {
	$coord = array();
	if (!((isset($_COOKIE['singlemodel']) && $_COOKIE['singlemodel'] == 'true') && (isset($_COOKIE['MapTrack']) && $_COOKIE['MapTrack'] != '')) && isset($_GET['coord']) && $_GET['coord'] != '') {
		$coord = explode(',',$_GET['coord']);
		if (!(filter_var($coord[0],FILTER_VALIDATE_FLOAT) && filter_var($coord[1],FILTER_VALIDATE_FLOAT) && filter_var($coord[2],FILTER_VALIDATE_FLOAT) && filter_var($coord[3],FILTER_VALIDATE_FLOAT) 
		    && $coord[0] > -180.0 && $coord[0] < 180.0 && $coord[1] > -90.0 && $coord[1] < 90.0 && $coord[2] > -180.0 && $coord[2] < 180.0 && $coord[3] > -90.0 && $coord[3] < 90.0)) {
			$coord = array();
		}
	}
	$previous_filter = $filter;
	if (((isset($_COOKIE['singlemodel']) && $_COOKIE['singlemodel'] == 'true') || (!isset($_COOKIE['singlemodel']) && isset($globalMap3DSelected) && $globalMap3DSelected)) && (isset($_COOKIE['MapTrack']) && $_COOKIE['MapTrack'] != '')) {
		//$filter = array_merge($filter,array('id' => $_COOKIE['MapTrack']));
		$spotter_array = $SpotterLive->getMinLastLiveSpotterDataByID($_COOKIE['MapTrack'],$filter,$limit);
		//$spotter_array = $SpotterLive->getMinLastLiveSpotterData($coord,$filter,false);
	} elseif (isset($_COOKIE['MapTrack']) && $_COOKIE['MapTrack'] != '') {
		//$spotter_array = $SpotterLive->getMinLastLiveSpotterDataByID($_COOKIE['MapTrack'],$filter,false);
		//if (empty($spotter_array)) $spotter_array = $SpotterLive->getMinLastLiveSpotterData($coord,$filter,false,$_COOKIE['MapTrack']);
		$spotter_array = $SpotterLive->getMinLastLiveSpotterData($coord,$filter,$limit,$_COOKIE['MapTrack']);
	} elseif (!isset($_COOKIE['singlemodel']) || $_COOKIE['singlemodel'] == 'false') {
		$spotter_array = $SpotterLive->getMinLastLiveSpotterData($coord,$filter,$limit);
	} else {
		$spotter_array = array();
	}
	$filter = $previous_filter;
}
//print_r($spotter_array);
if (!empty($spotter_array) && isset($coord)) {
	if ($tracker) {
		if (isset($_GET['archive'])) {
			$flightcnt = $TrackerArchive->getLiveTrackerCount($begindate,$enddate,$filter);
		} else {
			$flightcnt = $TrackerLive->getLiveTrackerCount($filter);
		}
	} elseif ($marine) {
		if (isset($_GET['archive'])) {
			$flightcnt = $MarineArchive->getLiveMarineCount($begindate,$enddate,$filter);
		} else {
			$flightcnt = $MarineLive->getLiveMarineCount($filter);
		}
	} else {
		if (isset($_GET['archive'])) {
			$flightcnt = $SpotterArchive->getLiveSpotterCount($begindate,$enddate,$filter);
		} else {
			$flightcnt = $SpotterLive->getLiveSpotterCount($filter);
		}
	}
	if ($flightcnt == '') $flightcnt = 0;
} else $flightcnt = 0;

$sqltime = round(microtime(true)-$begintime,2);
$minitime = time();
$minitracktime_begin = time();
$minitracktime = $minitracktime_begin;
$maxitime = 0;
$lastupdate = filter_input(INPUT_GET,'update',FILTER_SANITIZE_NUMBER_INT);
$modelsdb = array();
if (file_exists(dirname(__FILE__).'/models/modelsdb')) {
	if (($handle = fopen(dirname(__FILE__).'/models/modelsdb','r')) !== FALSE) {
		while (($row = fgetcsv($handle,1000)) !== FALSE) {
			if (isset($row[1]) ){
				$model = $row[0];
				$modelsdb[$model] = $row[1];
			}
		}
		fclose($handle);
	}
}
$modelsdb2 = array();
if (file_exists(dirname(__FILE__).'/models/gltf2/modelsdb')) {
	if (($handle = fopen(dirname(__FILE__).'/models/gltf2/modelsdb','r')) !== FALSE) {
		while (($row = fgetcsv($handle,1000)) !== FALSE) {
			if (isset($row[1]) ){
				$model = $row[0];
				$glb = $row[1];
				if (isset($row[2])) {
					$minisize = $row[2];
					$modelsdb2[$model] = array('glb' => $row[1], 'size' => $minisize);
				} else {
					$modelsdb2[$model] = array('glb' => $row[1], 'size' => 20);
				}
			}
		}
		fclose($handle);
	}
}
$heightrelative = 'NONE';
//$heightrelative = 'RELATIVE_TO_GROUND';
$j = 0;
$prev_flightaware_id = '';
$speed = 1;
$gltf2 = false;
$scale = 1.0;
$minimumpixelsize = 20;
if (isset($archivespeed)) $speed = $archivespeed;
$output = '[';
if ($tracker) {
	$output .= '{"id" : "document", "name" : "tracker","version" : "1.0"';
} elseif ($marine) {
	$output .= '{"id" : "document", "name" : "marine","version" : "1.0"';
} else {
	$output .= '{"id" : "document", "name" : "fam","version" : "1.0"';
}
//	$output .= ',"clock": {"interval" : "'.date("c",time()-$globalLiveInterval).'/'.date("c").'","currentTime" : "'.date("c",time() - $globalLiveInterval).'","multiplier" : 1,"range" : "LOOP_STOP","step": "SYSTEM_CLOCK_MULTIPLIER"}';

//	$output .= ',"clock": {"interval" : "'.date("c",time()-$globalLiveInterval).'/'.date("c").'","currentTime" : "'.date("c",time() - $globalLiveInterval).'","multiplier" : 1,"range" : "UNBOUNDED","step": "SYSTEM_CLOCK_MULTIPLIER"}';
//$output .= ',"clock": {"currentTime" : "'.date("c",time() - $globalLiveInterval).'","multiplier" : 1,"range" : "UNBOUNDED","step": "SYSTEM_CLOCK_MULTIPLIER"}';
if ($from_archive === true) {
	$output .= ',"clock": {"currentTime" : "%minitime%","multiplier" : '.$speed.',"range" : "UNBOUNDED","step": "SYSTEM_CLOCK_MULTIPLIER","interval": "%minitime%/%maxitime%"}';
} else {
	$output .= ',"clock": {"currentTime" : "%minitime%","multiplier" : '.$speed.',"range" : "UNBOUNDED","step": "SYSTEM_CLOCK_MULTIPLIER"}';
}

//	$output .= ',"clock": {"interval" : "'.date("c",time()-$globalLiveInterval).'/'.date("c").'","currentTime" : "'.date("c",time() - $globalLiveInterval).'","multiplier" : 1,"step": "SYSTEM_CLOCK_MULTIPLIER"}';
$output .= '},';
if (!empty($spotter_array) && is_array($spotter_array))
{
	$nblatlong = 0;
	foreach($spotter_array as $spotter_item)
	{
		$j++;
		//if (isset($spotter_item['format_source']) && $spotter_item['format_source'] == 'airwhere') $heightrelative = 'RELATIVE_TO_GROUND';
		date_default_timezone_set('UTC');
		if (isset($spotter_item['image_thumbnail']) && $spotter_item['image_thumbnail'] != "")
		{
			$image = $spotter_item['image_thumbnail'];
		} else {
			$image = "images/placeholder_thumb.png";
		}

                if (isset($spotter_item['flightaware_id'])) $id = $spotter_item['flightaware_id'];
                elseif (isset($spotter_item['famtrackid'])) $id = $spotter_item['famtrackid'];
                elseif (isset($spotter_item['fammarine_id'])) $id = $spotter_item['fammarine_id'];
                if ($prev_flightaware_id != $id) {
			if ($prev_flightaware_id != '') {
				/*
				if ($nblatlong == 1) {
					$output .= ',"'.date("c").'", ';
					$output .= $prevlong.', ';
					$output .= $prevlat;
					if (!$marine) $output .= ', '.$prevalt;
					else $output .= ', 0';
				}
				*/
				$output .= ']';
				$output .= '}';
				//$output .= ', '.$orientation.']}';
				$output .= '},';
			}
			$orientation = '';
			$prev_flightaware_id = $id;
			$nblatlong = 0;
			$output .= '{';
			$output .= '"id": "'.$id.'",';
			$output .= '"properties": {';
			$output .= '"flightcnt": "'.$flightcnt.'",';
			$output .= '"onground": %onground%,';
			$output .= '"lastupdate": "'.$lastupdate.'",';
			if (isset($spotter_item['aircraft_icao'])) {
				$output .= '"aircraft_icao": "'.$spotter_item['aircraft_icao'].'",';
			}
			if (isset($spotter_item['departure_airport'])) {
				$output .= '"departure_airport_code": "'.$spotter_item['departure_airport'].'",';
			}
			if (isset($spotter_item['arrival_airport'])) {
				$output .= '"arrival_airport_code": "'.$spotter_item['arrival_airport'].'",';
			}
			if (isset($spotter_item['squawk'])) {
				$output .= '"squawk": "'.$spotter_item['squawk'].'",';
			}
			if (isset($spotter_item['registration'])) $output .= '"registration": "'.$spotter_item['registration'].'",';
			if (isset($spotter_item['format_source'])) $output .= '"format": "'.$spotter_item['format_source'].'",';
			if (isset($spotter_item['ident'])) $output.= '"ident": '.json_encode($spotter_item['ident']).',';
			if ($tracker) {
				if (isset($spotter_item['type'])) $output .= '"tracker_type": '.json_encode($spotter_item['type']).',';
				$output.= '"type": "tracker"';
			} elseif ($marine) {
				if (isset($spotter_item['type'])) $output .= '"marine_type": '.json_encode($spotter_item['type']).',';
				if (isset($spotter_item['captain_name'])) $output .= '"captain": '.json_encode($spotter_item['captain_name']).',';
				if (isset($spotter_item['race_id'])) $output .= '"raceid": '.$spotter_item['race_id'].',';
				if (isset($spotter_item['race_name'])) $output .= '"race": '.json_encode($spotter_item['race_name']).',';
				if (isset($spotter_item['race_rank'])) $output .= '"rank": "'.$spotter_item['race_rank'].'",';
				$output.= '"type": "marine"';
			} else {
				if ($one3dmodel === false && isset($globalMap3DLiveries) && $globalMap3DLiveries) {
					$aircraft_icao = $spotter_item['aircraft_icao'];
					$ident = $spotter_item['ident'];
					if ($ident != '') {
						if (is_numeric(substr(substr($ident, 0, 3), -1, 1))) {
							$airline_icao = substr($ident, 0, 2);
						} elseif (is_numeric(substr(substr($ident, 0, 4), -1, 1))) {
							$airline_icao = substr($ident, 0, 3);
						}
						if (isset($airline_icao)) {
							$imagefile = $aircraft_icao.'-'.$airline_icao.'.png';
							if (file_exists(dirname(__FILE__).'/models/gltf2/liveries/'.$imagefile)) {
								$output.= '"liveries": "'.$globalURL.'/models/gltf2/liveries/'.$imagefile.'",';
							}
						}
					}
					//if ($ident != '') $output.= '"ident": "'.$ident.'",';
				}
				$output.= '"gltf2": %gltf2%,';
				$output.= '"type": "flight"';
			}
			$output .= '},';

			$output .= '"path" : { ';
			$output .= '"show" : false, ';
			//$output .= '"heightReference": "'.$heightrelative.'",';
			$output .= '"material" : { ';
			$output .= '"polylineOutline" : { ';
			$output .= '"color" : { "rgba" : [238, 250, 255, 255] }, ';
			$output .= '"outlineColor" : { "rgba" : [200, 209, 214, 255] }, ';
			$output .= '"outlineWidth" : 5, ';
			$output .= '"polylineGlow" : { "color" : { "rgba" : [214, 208, 214, 255] }, "glowPower" : 3 } ';
			$output .= '}';
			$output .= '}, ';
			//$output .= '"heightReference": "'.$heightrelative.'",';
			$output .= '"width" : 6, "leadTime" : 0, "trailTime" : 100000000, "resolution" : 20 },';
			//$output .= '"heightReference": "'.$heightrelative.'",';
			//$output .= ' "billboard" : {"image" : "data:image/png;base64,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","scale" : 1.5},';
			if ($no3dmodels) {
				if (isset($spotter_item['aircraft_icao'])) {
					$aircraft_icao = $spotter_item['aircraft_icao'];
					if ($aircraft_icao != '') {
						$aircraft_info = $Spotter->getAllAircraftInfo($aircraft_icao);
						if (isset($aircraft_info[0]['engine_type'])) {
							$aircraft_shadow = $aircraft_info[0]['aircraft_shadow'];
							$spotter_item['engine_type'] = $aircraft_info[0]['engine_type'];
							$spotter_item['wake_category'] = $aircraft_info[0]['wake_category'];
							$spotter_item['engine_count'] = $aircraft_info[0]['engine_count'];
						} else $aircraft_shadow = '';
	    					$output .= ' "billboard" : {"image" : "'.$globalURL.'/images/aircrafts/new/'.$aircraft_shadow.'","scale" : 0.5';
						if (isset($_COOKIE['IconColorForce']) && $_COOKIE['IconColorForce'] == 'true' && isset($_COOKIE['IconColor'])) {
							$rgb = $Common->hex2rgb($_COOKIE['IconColor']);
							$output .= ',"color": {"rgba" : ['.$rgb[0].','.$rgb[1].','.$rgb[2].',255]}';
						}
						$output .= '},';
					}
				} else $output .= ' "billboard" : {"image" : "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACgAAAAfCAYAAACVgY94AAAACXBIWXMAAC4jAAAuIwF4pT92AAAKT2lDQ1BQaG90b3Nob3AgSUNDIHByb2ZpbGUAAHjanVNnVFPpFj333vRCS4iAlEtvUhUIIFJCi4AUkSYqIQkQSoghodkVUcERRUUEG8igiAOOjoCMFVEsDIoK2AfkIaKOg6OIisr74Xuja9a89+bN/rXXPues852zzwfACAyWSDNRNYAMqUIeEeCDx8TG4eQuQIEKJHAAEAizZCFz/SMBAPh+PDwrIsAHvgABeNMLCADATZvAMByH/w/qQplcAYCEAcB0kThLCIAUAEB6jkKmAEBGAYCdmCZTAKAEAGDLY2LjAFAtAGAnf+bTAICd+Jl7AQBblCEVAaCRACATZYhEAGg7AKzPVopFAFgwABRmS8Q5ANgtADBJV2ZIALC3AMDOEAuyAAgMADBRiIUpAAR7AGDIIyN4AISZABRG8lc88SuuEOcqAAB4mbI8uSQ5RYFbCC1xB1dXLh4ozkkXKxQ2YQJhmkAuwnmZGTKBNA/g88wAAKCRFRHgg/P9eM4Ors7ONo62Dl8t6r8G/yJiYuP+5c+rcEAAAOF0ftH+LC+zGoA7BoBt/qIl7gRoXgugdfeLZrIPQLUAoOnaV/Nw+H48PEWhkLnZ2eXk5NhKxEJbYcpXff5nwl/AV/1s+X48/Pf14L7iJIEyXYFHBPjgwsz0TKUcz5IJhGLc5o9H/LcL//wd0yLESWK5WCoU41EScY5EmozzMqUiiUKSKcUl0v9k4t8s+wM+3zUAsGo+AXuRLahdYwP2SycQWHTA4vcAAPK7b8HUKAgDgGiD4c93/+8//UegJQCAZkmScQAAXkQkLlTKsz/HCAAARKCBKrBBG/TBGCzABhzBBdzBC/xgNoRCJMTCQhBCCmSAHHJgKayCQiiGzbAdKmAv1EAdNMBRaIaTcA4uwlW4Dj1wD/phCJ7BKLyBCQRByAgTYSHaiAFiilgjjggXmYX4IcFIBBKLJCDJiBRRIkuRNUgxUopUIFVIHfI9cgI5h1xGupE7yAAygvyGvEcxlIGyUT3UDLVDuag3GoRGogvQZHQxmo8WoJvQcrQaPYw2oefQq2gP2o8+Q8cwwOgYBzPEbDAuxsNCsTgsCZNjy7EirAyrxhqwVqwDu4n1Y8+xdwQSgUXACTYEd0IgYR5BSFhMWE7YSKggHCQ0EdoJNwkDhFHCJyKTqEu0JroR+cQYYjIxh1hILCPWEo8TLxB7iEPENyQSiUMyJ7mQAkmxpFTSEtJG0m5SI+ksqZs0SBojk8naZGuyBzmULCAryIXkneTD5DPkG+Qh8lsKnWJAcaT4U+IoUspqShnlEOU05QZlmDJBVaOaUt2ooVQRNY9aQq2htlKvUYeoEzR1mjnNgxZJS6WtopXTGmgXaPdpr+h0uhHdlR5Ol9BX0svpR+iX6AP0dwwNhhWDx4hnKBmbGAcYZxl3GK+YTKYZ04sZx1QwNzHrmOeZD5lvVVgqtip8FZHKCpVKlSaVGyovVKmqpqreqgtV81XLVI+pXlN9rkZVM1PjqQnUlqtVqp1Q61MbU2epO6iHqmeob1Q/pH5Z/YkGWcNMw09DpFGgsV/jvMYgC2MZs3gsIWsNq4Z1gTXEJrHN2Xx2KruY/R27iz2qqaE5QzNKM1ezUvOUZj8H45hx+Jx0TgnnKKeX836K3hTvKeIpG6Y0TLkxZVxrqpaXllirSKtRq0frvTau7aedpr1Fu1n7gQ5Bx0onXCdHZ4/OBZ3nU9lT3acKpxZNPTr1ri6qa6UbobtEd79up+6Ynr5egJ5Mb6feeb3n+hx9L/1U/W36p/VHDFgGswwkBtsMzhg8xTVxbzwdL8fb8VFDXcNAQ6VhlWGX4YSRudE8o9VGjUYPjGnGXOMk423GbcajJgYmISZLTepN7ppSTbmmKaY7TDtMx83MzaLN1pk1mz0x1zLnm+eb15vft2BaeFostqi2uGVJsuRaplnutrxuhVo5WaVYVVpds0atna0l1rutu6cRp7lOk06rntZnw7Dxtsm2qbcZsOXYBtuutm22fWFnYhdnt8Wuw+6TvZN9un2N/T0HDYfZDqsdWh1+c7RyFDpWOt6azpzuP33F9JbpL2dYzxDP2DPjthPLKcRpnVOb00dnF2e5c4PziIuJS4LLLpc+Lpsbxt3IveRKdPVxXeF60vWdm7Obwu2o26/uNu5p7ofcn8w0nymeWTNz0MPIQ+BR5dE/C5+VMGvfrH5PQ0+BZ7XnIy9jL5FXrdewt6V3qvdh7xc+9j5yn+M+4zw33jLeWV/MN8C3yLfLT8Nvnl+F30N/I/9k/3r/0QCngCUBZwOJgUGBWwL7+Hp8Ib+OPzrbZfay2e1BjKC5QRVBj4KtguXBrSFoyOyQrSH355jOkc5pDoVQfujW0Adh5mGLw34MJ4WHhVeGP45wiFga0TGXNXfR3ENz30T6RJZE3ptnMU85ry1KNSo+qi5qPNo3ujS6P8YuZlnM1VidWElsSxw5LiquNm5svt/87fOH4p3iC+N7F5gvyF1weaHOwvSFpxapLhIsOpZATIhOOJTwQRAqqBaMJfITdyWOCnnCHcJnIi/RNtGI2ENcKh5O8kgqTXqS7JG8NXkkxTOlLOW5hCepkLxMDUzdmzqeFpp2IG0yPTq9MYOSkZBxQqohTZO2Z+pn5mZ2y6xlhbL+xW6Lty8elQfJa7OQrAVZLQq2QqboVFoo1yoHsmdlV2a/zYnKOZarnivN7cyzytuQN5zvn//tEsIS4ZK2pYZLVy0dWOa9rGo5sjxxedsK4xUFK4ZWBqw8uIq2Km3VT6vtV5eufr0mek1rgV7ByoLBtQFr6wtVCuWFfevc1+1dT1gvWd+1YfqGnRs+FYmKrhTbF5cVf9go3HjlG4dvyr+Z3JS0qavEuWTPZtJm6ebeLZ5bDpaql+aXDm4N2dq0Dd9WtO319kXbL5fNKNu7g7ZDuaO/PLi8ZafJzs07P1SkVPRU+lQ27tLdtWHX+G7R7ht7vPY07NXbW7z3/T7JvttVAVVN1WbVZftJ+7P3P66Jqun4lvttXa1ObXHtxwPSA/0HIw6217nU1R3SPVRSj9Yr60cOxx++/p3vdy0NNg1VjZzG4iNwRHnk6fcJ3/ceDTradox7rOEH0x92HWcdL2pCmvKaRptTmvtbYlu6T8w+0dbq3nr8R9sfD5w0PFl5SvNUyWna6YLTk2fyz4ydlZ19fi753GDborZ752PO32oPb++6EHTh0kX/i+c7vDvOXPK4dPKy2+UTV7hXmq86X23qdOo8/pPTT8e7nLuarrlca7nuer21e2b36RueN87d9L158Rb/1tWeOT3dvfN6b/fF9/XfFt1+cif9zsu72Xcn7q28T7xf9EDtQdlD3YfVP1v+3Njv3H9qwHeg89HcR/cGhYPP/pH1jw9DBY+Zj8uGDYbrnjg+OTniP3L96fynQ89kzyaeF/6i/suuFxYvfvjV69fO0ZjRoZfyl5O/bXyl/erA6xmv28bCxh6+yXgzMV70VvvtwXfcdx3vo98PT+R8IH8o/2j5sfVT0Kf7kxmTk/8EA5jz/GMzLdsAAAAgY0hSTQAAeiUAAICDAAD5/wAAgOkAAHUwAADqYAAAOpgAABdvkl/FRgAAA7VJREFUeNrEl2uIlWUQx39nXUu0m2uQbZYrbabdLKMs/VBkmHQjioqFIhBS+hKEQpQRgVAf2u5RQkGBRUllRH4I2e5ZUBJlEZVt5i0tTfHStrZ6fn35L70d9n7Obg88vOedmWfmf2bmmZkXlRrtq9V16mZ1iVqqhd5agXvQf1c5zw/V8dXqrqO6dQKwBrgdWApsCb0VqAc2AnOrMVANwIsD4BLgTOBPYB2wHJgEzAG+ANqAu4ZsZYiuX5QwfqI2hvaNulA9J7zLQn8o76vUuuHOwXHqSzH4aIF+TWjnBkSH+nCBf716SP1KPWO4AJ6ltgfIjRW8p9U/1KPz/ry6RT2mIDNF3Zjz19Ya4G1R/J16dgWvQd2pPlXhMdVZPUTgxfCW1wJgXUJpQlvfg8zs8K8r0Caom9QHetG7NGfa1ElDBThRXRtFd/Qh16puKIS3e7+clBjdy7kL1b3q4fzJQQGck5z6Nb97kxujblWf64HXov7Vl/E4YXWccP9AAd6dAx+ox/WTArNzY1t64B0f8K0DyLXuUvRGZfcpCo1VX4tg6wB76WMB0dALf526foAX8cqUot2pGP8B2Kz+krBeNYjS8636dh/8Beo2deoA9TWp76pd6g0q9cDNwKvAD8A84EfglLRBe2g+JWAfcEF68bPABOCoAl/gIPA5MA64FVgGnNhP292W3r0SeB1YVlJXAjcBP8XwyQUj9AKwAzg2+/fQSsBhoJxBAaALaIzenZGnD911wA7gEDAD2FFSpwOzgDHZ5T7+ZSlGd2d6AXgi5+qAn+O5U0PbBVwKtAD3AHuB8f3YGBUdncCGoQ4LE9XtGRqK9LnduVPRIu2BPqwD65IYbS7Qpql7Ql9YoJcy9bwzkgPrfOCj5G33+h54E/g0PAr5thq4ApgyEgNrc27aWwVaPTA1QJ4BjgTGFvhteV40EgPrgvTP7qlmZqFnl9WD+b2posN83E/NrEkOjlI/U1fkfUYa/pe5IE3qZPW8jFOqiyN7p3pAPX04c7AxYSoDDcAjKT2LgLXA6IR2M3Bviv59wDTgQGTPH84Qd8+HXfHcoUws2zM0HMjuUPep+xP2PWpnwtw0GJsldbBpewQwE/gbeDyt7H1gcW53O7AC+A3Yn6+/W+Ld9SnWA15DAVhc8xK2TuA9YHrCuhV4EngFuBx4YagG6qv8cF+T52kB2Zy+e1I8taUacNV+uBdXO7ABmJwJpwx8XQvF9TUCWM64tiQhbq/oMv+7BwFWpQzNT8vbVQul/wwAGzzdmXU1xuUAAAAASUVORK5CYII=","scale" : 0.5},';
			} elseif ($one3dmodel) {
				if (isset($globalMap3DForceModel) && $globalMap3DForceModel != '') {
					$output .= '"model": {"gltf" : "'.$globalURL.'/models/'.$globalMap3DForceModel.'","scale" : '.$scale.',"minimumPixelSize": '.$minimumpixelsize.'';
				} else {
					$output .= '"model": {"gltf" : "'.$globalURL.'/models/737.glb","scale" : '.$scale.',"minimumPixelSize": '.$minimumpixelsize.'';
				}
				$output .= ',"heightReference": "'.$heightrelative.'"';
				if (isset($_COOKIE['IconColorForce']) && $_COOKIE['IconColorForce'] == 'true' && isset($_COOKIE['IconColor'])) {
					$rgb = $Common->hex2rgb($_COOKIE['IconColor']);
					$output .= ',"color": {"rgba" : ['.$rgb[0].','.$rgb[1].','.$rgb[2].',255]}';
				}
				$output .= '},';
			} else {
				if (isset($spotter_item['aircraft_icao'])) {
					$aircraft_icao = $spotter_item['aircraft_icao'];
					if (isset($modelsdb2[$aircraft_icao]) && $aircraft_icao != '') {
						$gltf2 = true;
						$output .= '"model": {"gltf" : "'.$globalURL.'/models/gltf2/'.$modelsdb2[$aircraft_icao]['glb'].'","scale" : '.$scale.',"minimumPixelSize": '.$modelsdb2[$aircraft_icao]['size'];
						$output .= ',"heightReference": "'.$heightrelative.'"';
						if (isset($_COOKIE['IconColorForce']) && $_COOKIE['IconColorForce'] == 'true' && isset($_COOKIE['IconColor'])) {
							$rgb = $Common->hex2rgb($_COOKIE['IconColor']);
							$output .= ',"color": {"rgba" : ['.$rgb[0].','.$rgb[1].','.$rgb[2].',255]}';
						}
    						$output .= '},';
					} elseif (isset($modelsdb[$aircraft_icao]) && $aircraft_icao != '') {
						$output .= '"model": {"gltf" : "'.$globalURL.'/models/'.$modelsdb[$aircraft_icao].'","scale" : '.$scale.',"minimumPixelSize": '.$minimumpixelsize.'';
						$output .= ',"heightReference": "'.$heightrelative.'"';
						if (isset($_COOKIE['IconColorForce']) && $_COOKIE['IconColorForce'] == 'true' && isset($_COOKIE['IconColor'])) {
							$rgb = $Common->hex2rgb($_COOKIE['IconColor']);
							$output .= ',"color": {"rgba" : ['.$rgb[0].','.$rgb[1].','.$rgb[2].',255]}';
						}
    						$output .= '},';
					} elseif ($aircraft_icao != '') {
						$aircraft_info = $Spotter->getAllAircraftInfo($aircraft_icao);
						if (isset($aircraft_info[0]['engine_type'])) {
							$aircraft_shadow = $aircraft_info[0]['aircraft_shadow'];
							$spotter_item['engine_type'] = $aircraft_info[0]['engine_type'];
							$spotter_item['wake_category'] = $aircraft_info[0]['wake_category'];
							$spotter_item['engine_count'] = $aircraft_info[0]['engine_count'];
						} else $aircraft_shadow = '';
						if ($aircraft_shadow != '') {
							if (isset($modelsdb2[$aircraft_shadow])) {
								$output .= '"model": {"gltf" : "'.$globalURL.'/models/gltf2/'.$modelsdb2[$aircraft_shadow]['glb'].'","scale" : '.$scale.',"minimumPixelSize": '.$modelsdb2[$aircraft_shadow]['size'];
								$output .= ',"heightReference": "'.$heightrelative.'"';
								if (isset($_COOKIE['IconColorForce']) && $_COOKIE['IconColorForce'] == 'true' && isset($_COOKIE['IconColor'])) {
									$rgb = $Common->hex2rgb($_COOKIE['IconColor']);
									$output .= ',"color": {"rgba" : ['.$rgb[0].','.$rgb[1].','.$rgb[2].',255]}';
								}
								$output .= '},';
								$modelsdb2[$aircraft_icao] = $modelsdb2[$aircraft_shadow];
							} elseif (isset($modelsdb[$aircraft_shadow])) {
								$output .= '"model": {"gltf" : "'.$globalURL.'/models/'.$modelsdb[$aircraft_shadow].'","scale" : '.$scale.',"minimumPixelSize": '.$minimumpixelsize.'';
								$output .= ',"heightReference": "'.$heightrelative.'"';
								if (isset($_COOKIE['IconColorForce']) && $_COOKIE['IconColorForce'] == 'true' && isset($_COOKIE['IconColor'])) {
									$rgb = $Common->hex2rgb($_COOKIE['IconColor']);
									$output .= ',"color": {"rgba" : ['.$rgb[0].','.$rgb[1].','.$rgb[2].',255]}';
								}
								$output .= '},';
								$modelsdb[$aircraft_icao] = $modelsdb[$aircraft_shadow];
							} elseif ($spotter_item['engine_type'] == 'Jet') {
								if ($spotter_item['engine_count'] == '1') {
									if ($spotter_item['wake_category'] == 'M') {
										$model = 'J1M';
									} elseif ($spotter_item['wake_category'] == 'L') {
										$model = '';
									}
								} elseif ($spotter_item['engine_count'] == '2') {
									if ($spotter_item['wake_category'] == 'M') {
										$model = 'J2M';
									} elseif ($spotter_item['wake_category'] == 'H') {
										$model = 'J2H';
									} elseif ($spotter_item['wake_category'] == 'L') {
										$model = 'J2L';
									}
								} elseif ($spotter_item['engine_count'] == '3') {
									if ($spotter_item['wake_category'] == 'M') {
										$model = 'J3M';
									} elseif ($spotter_item['wake_category'] == 'H') {
										$model = 'J3H';
									}
								} elseif ($spotter_item['engine_count'] == '4') {
									if ($spotter_item['wake_category'] == 'M') {
										$model = 'J4M';
									} elseif ($spotter_item['wake_category'] == 'H') {
										$model = 'J4H';
									}
								}
								if (isset($modelsdb2[$model])) {
									$output .= '"model": {"gltf" : "'.$globalURL.'/models/gltf2/'.$modelsdb2[$model]['glb'].'","scale" : '.$scale.',"minimumPixelSize": '.$modelsdb2[$model]['size'];
									$output .= ',"heightReference": "'.$heightrelative.'"';
									if (isset($_COOKIE['IconColorForce']) && $_COOKIE['IconColorForce'] == 'true' && isset($_COOKIE['IconColor'])) {
										$rgb = $Common->hex2rgb($_COOKIE['IconColor']);
										$output .= ',"color": {"rgba" : ['.$rgb[0].','.$rgb[1].','.$rgb[2].',255]}';
									}
									$output .= '},';
									$modelsdb2[$aircraft_icao] = $modelsdb2[$model];
								} elseif (isset($modelsdb[$model])) {
									$output .= '"model": {"gltf" : "'.$globalURL.'/models/'.$modelsdb[$model].'","scale" : '.$scale.',"minimumPixelSize": '.$minimumpixelsize.'';
									$output .= ',"heightReference": "'.$heightrelative.'"';
									if (isset($_COOKIE['IconColorForce']) && $_COOKIE['IconColorForce'] == 'true' && isset($_COOKIE['IconColor'])) {
										$rgb = $Common->hex2rgb($_COOKIE['IconColor']);
										$output .= ',"color": {"rgba" : ['.$rgb[0].','.$rgb[1].','.$rgb[2].',255]}';
									}
									$output .= '},';
									$modelsdb[$aircraft_icao] = $modelsdb[$model];
								} else {
									$output .= '"model": {"gltf" : "'.$globalURL.'/models/Cesium_Air.glb","scale" : '.$scale.',"minimumPixelSize": '.$minimumpixelsize.'';
									$output .= ',"heightReference": "'.$heightrelative.'"';
									if (isset($_COOKIE['IconColorForce']) && $_COOKIE['IconColorForce'] == 'true' && isset($_COOKIE['IconColor'])) {
										$rgb = $Common->hex2rgb($_COOKIE['IconColor']);
										$output .= ',"color": {"rgba" : ['.$rgb[0].','.$rgb[1].','.$rgb[2].',255]}';
									}
									$output .= '},';
									$modelsdb[$aircraft_icao] = 'Cesium_Air.glb';
								}
							} elseif ($spotter_item['engine_type'] == 'Turboprop') {
								if ($spotter_item['engine_count'] == '1') {
									if ($spotter_item['wake_category'] == 'L') {
										$model = 'T1L';
									}
								} elseif ($spotter_item['engine_count'] == '2') {
									if ($spotter_item['wake_category'] == 'M') {
										$model = 'T2M';
									} elseif ($spotter_item['wake_category'] == 'L') {
										$model = 'T2L';
									}
								} elseif ($spotter_item['engine_count'] == '4') {
									if ($spotter_item['wake_category'] == 'M') {
									} elseif ($spotter_item['wake_category'] == 'H') {
										$model = 'T4H';
									}
								}
								if (isset($modelsdb[$model])) {
									$output .= '"model": {"gltf" : "'.$globalURL.'/models/'.$modelsdb[$model].'","scale" : '.$scale.',"minimumPixelSize": '.$minimumpixelsize.'';
									$output .= ',"heightReference": "'.$heightrelative.'"';
									if (isset($_COOKIE['IconColorForce']) && $_COOKIE['IconColorForce'] == 'true' && isset($_COOKIE['IconColor'])) {
										$rgb = $Common->hex2rgb($_COOKIE['IconColor']);
										$output .= ',"color": {"rgba" : ['.$rgb[0].','.$rgb[1].','.$rgb[2].',255]}';
									}
									$output .= '},';
									$modelsdb[$aircraft_icao] = $modelsdb[$model];
								} else {
									$output .= '"model": {"gltf" : "'.$globalURL.'/models/Cesium_Air.glb","scale" : '.$scale.',"minimumPixelSize": '.$minimumpixelsize.'';
									$output .= ',"heightReference": "'.$heightrelative.'"';
									if (isset($_COOKIE['IconColorForce']) && $_COOKIE['IconColorForce'] == 'true' && isset($_COOKIE['IconColor'])) {
										$rgb = $Common->hex2rgb($_COOKIE['IconColor']);
										$output .= ',"color": {"rgba" : ['.$rgb[0].','.$rgb[1].','.$rgb[2].',255]}';
									}
									$output .= '},';
									$modelsdb[$aircraft_icao] = 'Cesium_Air.glb';
								}
							} elseif ($spotter_item['engine_type'] == 'Piston') {
								if ($spotter_item['engine_count'] == '1') {
									if ($spotter_item['wake_category'] == 'L') {
										$model = 'P1L';
									} elseif ($spotter_item['wake_category'] == 'M') {
										$model = 'P1M';
									}
								} elseif ($spotter_item['engine_count'] == '2') {
									if ($spotter_item['wake_category'] == 'M') {
										$model = 'P2M';
									} elseif ($spotter_item['wake_category'] == 'L') {
										$model = 'P2L';
									}
									// ju52 = P3M
								}
								if (isset($modelsdb[$model])) {
									$output .= '"model": {"gltf" : "'.$globalURL.'/models/'.$modelsdb[$model].'","scale" : '.$scale.',"minimumPixelSize": '.$minimumpixelsize.'';
									$output .= ',"heightReference": "'.$heightrelative.'"';
									if (isset($_COOKIE['IconColorForce']) && $_COOKIE['IconColorForce'] == 'true' && isset($_COOKIE['IconColor'])) {
										$rgb = $Common->hex2rgb($_COOKIE['IconColor']);
										$output .= ',"color": {"rgba" : ['.$rgb[0].','.$rgb[1].','.$rgb[2].',255]}';
									}
									$output .= '},';
									$modelsdb[$aircraft_icao] = $modelsdb[$model];
								} else {
									$output .= '"model": {"gltf" : "'.$globalURL.'/models/Cesium_Air.glb","scale" : '.$scale.',"minimumPixelSize": '.$minimumpixelsize.'';
									$output .= ',"heightReference": "'.$heightrelative.'"';
									if (isset($_COOKIE['IconColorForce']) && $_COOKIE['IconColorForce'] == 'true' && isset($_COOKIE['IconColor'])) {
										$rgb = $Common->hex2rgb($_COOKIE['IconColor']);
										$output .= ',"color": {"rgba" : ['.$rgb[0].','.$rgb[1].','.$rgb[2].',255]}';
									}
									$output .= '},';
									$modelsdb[$aircraft_icao] = 'Cesium_Air.glb';
								}
							} else {
								$output .= '"model": {"gltf" : "'.$globalURL.'/models/Cesium_Air.glb","scale" : '.$scale.',"minimumPixelSize": '.$minimumpixelsize.'';
								$output .= ',"heightReference": "'.$heightrelative.'"';
								if (isset($_COOKIE['IconColorForce']) && $_COOKIE['IconColorForce'] == 'true' && isset($_COOKIE['IconColor'])) {
									$rgb = $Common->hex2rgb($_COOKIE['IconColor']);
									$output .= ',"color": {"rgba" : ['.$rgb[0].','.$rgb[1].','.$rgb[2].',255]}';
								}
								$output .= '},';
								//if ($spotter_item['aircraft_shadow'] != '') $output .= '"aircraft_shadow": "'.$spotter_item['aircraft_shadow'].'",';
								if ($spotter_item['aircraft_icao'] != '') $output .= '"aircraft_icao": "'.$spotter_item['aircraft_icao'].'",';
								$modelsdb[$aircraft_icao] = 'Cesium_Air.glb';
							}
						} elseif (isset($spotter_item['format_source']) && $spotter_item['format_source'] == 'aprs') {
							$aircraft_shadow = 'PA18';
							$output .= '"model": {"gltf" : "'.$globalURL.'/models/'.$modelsdb[$aircraft_shadow].'","scale" : '.$scale.',"minimumPixelSize": '.$minimumpixelsize.'';
							$output .= ',"heightReference": "'.$heightrelative.'"';
							if (isset($_COOKIE['IconColorForce']) && $_COOKIE['IconColorForce'] == 'true' && isset($_COOKIE['IconColor'])) {
								$rgb = $Common->hex2rgb($_COOKIE['IconColor']);
								$output .= ',"color": {"rgba" : ['.$rgb[0].','.$rgb[1].','.$rgb[2].',255]}';
							}
							$output .= '},';
							$modelsdb[$aircraft_icao] = $modelsdb[$aircraft_shadow];
						} else {
							$output .= '"model": {"gltf" : "'.$globalURL.'/models/Cesium_Air.glb","scale" : '.$scale.',"minimumPixelSize": '.$minimumpixelsize.'';
							$output .= ',"heightReference": "'.$heightrelative.'"';
							if (isset($_COOKIE['IconColorForce']) && $_COOKIE['IconColorForce'] == 'true' && isset($_COOKIE['IconColor'])) {
								$rgb = $Common->hex2rgb($_COOKIE['IconColor']);
								$output .= ',"color": {"rgba" : ['.$rgb[0].','.$rgb[1].','.$rgb[2].',255]}';
							}
							$output .= '},';
							//if ($spotter_item['aircraft_shadow'] != '') $output .= '"aircraft_shadow": "'.$spotter_item['aircraft_shadow'].'",';
							if ($spotter_item['aircraft_icao'] != '') $output .= '"aircraft_icao": "'.$spotter_item['aircraft_icao'].'",';
							$modelsdb[$aircraft_icao] = 'Cesium_Air.glb';
						}
					} else {
						$output .= '"model": {"gltf" : "'.$globalURL.'/models/Cesium_Air.glb","scale" : '.$scale.',"minimumPixelSize": '.$minimumpixelsize.'';
						$output .= ',"heightReference": "'.$heightrelative.'"';
						//$output .= ',"color": {"rgba" : [255,0,0,255]}';
						if (isset($_COOKIE['IconColorForce']) && $_COOKIE['IconColorForce'] == 'true' && isset($_COOKIE['IconColor'])) {
							$rgb = $Common->hex2rgb($_COOKIE['IconColor']);
							$output .= ',"color": {"rgba" : ['.$rgb[0].','.$rgb[1].','.$rgb[2].',255]}';
						}
						$output .= '},';
						//if ($spotter_item['aircraft_shadow'] != '') $output .= '"aircraft_shadow": "'.$spotter_item['aircraft_shadow'].'",';
						if ($spotter_item['aircraft_icao'] != '') $output .= '"aircraft_icao": "'.$spotter_item['aircraft_icao'].'",';
						$modelsdb[$aircraft_icao] = 'Cesium_Air.glb';
					}
				} elseif ($tracker && isset($spotter_item['type'])) {
					if ($spotter_item['type'] == 'Car' || $spotter_item['type'] == 'Van') {
						$onground = true;
						//$output .= '"model": {"gltf" : "'.$globalURL.'/models/vehicules/car.glb","scale" : '.$scale.',"minimumPixelSize": '.$minimumpixelsize.',';
						$output .= '"model": {"gltf" : "'.$globalURL.'/models/vehicules/car.gltf","scale" : '.$scale.',"minimumPixelSize": '.$minimumpixelsize.'';
						//$output .= ',"heightReference": "'.$heightrelative.'"';
						$output .= ',"heightReference": "CLAMP_TO_GROUND"';
						if (isset($_COOKIE['TrackerIconColorForce']) && $_COOKIE['TrackerIconColorForce'] == 'true' && isset($_COOKIE['TrackerIconColor'])) {
							$rgb = $Common->hex2rgb($_COOKIE['TrackerIconColor']);
							$output .= ',"color": {"rgba" : ['.$rgb[0].','.$rgb[1].','.$rgb[2].',255]}';
							$output .= ',"colorBlendMode" : "MIX"';
						}
						$output .= '},';
					} elseif ($spotter_item['type'] == 'Truck' || $spotter_item['type'] == 'Truck (18 Wheeler)') {
						$onground = true;
						$output .= '"model": {"gltf" : "'.$globalURL.'/models/vehicules/truck.gltf","scale" : '.$scale.',"minimumPixelSize": 10';
						//$output .= ',"heightReference": "'.$heightrelative.'"';
						$output .= ',"heightReference": "CLAMP_TO_GROUND"';
						if (isset($_COOKIE['TrackerIconColorForce']) && $_COOKIE['TrackerIconColorForce'] == 'true' && isset($_COOKIE['TrackerIconColor'])) {
							$rgb = $Common->hex2rgb($_COOKIE['TrackerIconColor']);
							$output .= ',"color": {"rgba" : ['.$rgb[0].','.$rgb[1].','.$rgb[2].',255]}';
							$output .= ',"colorBlendMode" : "MIX"';
						}
						$output .= '},';
					} elseif ($spotter_item['type'] == 'Firetruck') {
						$onground = true;
						$output .= '"model": {"gltf" : "'.$globalURL.'/models/vehicules/firetruck.glb","scale" : '.$scale.',"minimumPixelSize": 0';
						//$output .= ',"heightReference": "'.$heightrelative.'"';
						$output .= ',"heightReference": "CLAMP_TO_GROUND"';
						if (isset($_COOKIE['TrackerIconColorForce']) && $_COOKIE['TrackerIconColorForce'] == 'true' && isset($_COOKIE['TrackerIconColor'])) {
							$rgb = $Common->hex2rgb($_COOKIE['TrackerIconColor']);
							$output .= ',"color": {"rgba" : ['.$rgb[0].','.$rgb[1].','.$rgb[2].',255]}';
							$output .= ',"colorBlendMode" : "MIX"';
						}
						$output .= '},';
					} elseif ($spotter_item['type'] == 'Bike') {
						$onground = true;
						$output .= '"model": {"gltf" : "'.$globalURL.'/models/vehicules/cycle.glb","scale" : '.$scale.',"minimumPixelSize": '.$minimumpixelsize.'';
						//$output .= ',"heightReference": "'.$heightrelative.'"';
						$output .= ',"heightReference": "CLAMP_TO_GROUND"';
						if (isset($_COOKIE['TrackerIconColorForce']) && $_COOKIE['TrackerIconColorForce'] == 'true' && isset($_COOKIE['TrackerIconColor'])) {
							$rgb = $Common->hex2rgb($_COOKIE['TrackerIconColor']);
							$output .= ',"color": {"rgba" : ['.$rgb[0].','.$rgb[1].','.$rgb[2].',255]}';
							$output .= ',"colorBlendMode" : "MIX"';
						}
						$output .= '},';
					} elseif ($spotter_item['type'] == 'Police') {
						$onground = true;
						$output .= '"model": {"gltf" : "'.$globalURL.'/models/vehicules/police.glb","scale" : '.$scale.',"minimumPixelSize": '.$minimumpixelsize.'';
						//$output .= ',"heightReference": "'.$heightrelative.'"';
						$output .= ',"heightReference": "CLAMP_TO_GROUND"';
						if (isset($_COOKIE['TrackerIconColorForce']) && $_COOKIE['TrackerIconColorForce'] == 'true' && isset($_COOKIE['TrackerIconColor'])) {
							$rgb = $Common->hex2rgb($_COOKIE['TrackerIconColor']);
							$output .= ',"color": {"rgba" : ['.$rgb[0].','.$rgb[1].','.$rgb[2].',255]}';
							$output .= ',"colorBlendMode" : "MIX"';
						}
						$output .= '},';
					} elseif ($spotter_item['type'] == 'Balloon') {
						$output .= '"model": {"gltf" : "'.$globalURL.'/models/vehicules/ball.glb","scale" : '.$scale.',"minimumPixelSize": '.$minimumpixelsize.'';
						$output .= ',"heightReference": "'.$heightrelative.'"';
						if (isset($_COOKIE['TrackerIconColorForce']) && $_COOKIE['TrackerIconColorForce'] == 'true' && isset($_COOKIE['TrackerIconColor'])) {
							$rgb = $Common->hex2rgb($_COOKIE['TrackerIconColor']);
							$output .= ',"color": {"rgba" : ['.$rgb[0].','.$rgb[1].','.$rgb[2].',255]}';
							$output .= ',"colorBlendMode" : "MIX"';
						}
						$output .= '},';
					} elseif ($spotter_item['type'] == 'Ship (Power Boat)' || $spotter_item['type'] == 'Yatch (Sail)') {
						$onground = true;
						$output .= '"model": {"gltf" : "'.$globalURL.'/models/vehicules/boat.glb","scale" : '.$scale.',"minimumPixelSize": '.$minimumpixelsize.'';
						//$output .= ',"heightReference": "'.$heightrelative.'"';
						$output .= ',"heightReference": "CLAMP_TO_GROUND"';
						if (isset($_COOKIE['TrackerIconColorForce']) && $_COOKIE['TrackerIconColorForce'] == 'true' && isset($_COOKIE['TrackerIconColor'])) {
							$rgb = $Common->hex2rgb($_COOKIE['TrackerIconColor']);
							$output .= ',"color": {"rgba" : ['.$rgb[0].','.$rgb[1].','.$rgb[2].',255]}';
							$output .= ',"colorBlendMode" : "MIX"';
						}
						$output .= '},';
					} else {
						$onground = true;
						$output .= '"model": {"gltf" : "'.$globalURL.'/models/vehicules/car.gltf","scale" : '.$scale.',"minimumPixelSize": '.$minimumpixelsize.'';
						//$output .= '"model": {"gltf" : "'.$globalURL.'/models/vehicules/Cesium_Ground.glb","scale" : '.$scale.',"minimumPixelSize": '.$minimumpixelsize.'';
						$output .= ',"heightReference": "'.$heightrelative.'"';
						if (isset($_COOKIE['TrackerIconColorForce']) && $_COOKIE['TrackerIconColorForce'] == 'true' && isset($_COOKIE['TrackerIconColor'])) {
							$rgb = $Common->hex2rgb($_COOKIE['TrackerIconColor']);
							$output .= ',"color": {"rgba" : ['.$rgb[0].','.$rgb[1].','.$rgb[2].',255]}';
							$output .= ',"colorBlendMode" : "MIX"';
						}
						$output .= '},';
					}
				} elseif ($marine) {
					if ($spotter_item['type_id'] == 36) {
						$output .= '"model": {"gltf" : "'.$globalURL.'/models/vehicules/sail.glb","scale" : '.$scale.',"minimumPixelSize": '.$minimumpixelsize.'';
					} else {
						$output .= '"model": {"gltf" : "'.$globalURL.'/models/vehicules/boat.glb","scale" : '.$scale.',"minimumPixelSize": '.$minimumpixelsize.'';
					}
					//$output .= ',"heightReference": "'.$heightrelative.'"';
					$output .= ',"heightReference": "CLAMP_TO_GROUND"';
					if (isset($_COOKIE['MarineIconColorForce']) && $_COOKIE['MarineIconColorForce'] == 'true' && isset($_COOKIE['MarineIconColor'])) {
						$rgb = $Common->hex2rgb($_COOKIE['MarineIconColor']);
						$output .= ',"color": {"rgba" : ['.$rgb[0].','.$rgb[1].','.$rgb[2].',255]}';
						$output .= ',"colorBlendMode" : "MIX"';
					}
					$output .= '},';
				}
			}
			if (isset($onground) && $onground) $output = str_replace('%onground%','true',$output);
			else $output = str_replace('%onground%','false',$output);

	//		$output .= '"heightReference": "CLAMP_TO_GROUND",';
			//$output .= '"heightReference": "'.$heightrelative.'",';
	//		$output .= '"heightReference": "NONE",';
			$output .= '"position": {';
			$output .= '"interpolationAlgorithm":"HERMITE","interpolationDegree":3,';
			//$output .= '"heightReference": "'.$heightrelative.'",';
			$output .= '"type": "Point",';
	//		$output .= '"interpolationAlgorithm" : "LAGRANGE",';
	//		$output .= '"interpolationDegree" : 5,';
	//		$output .= '"epoch" : "'.date("c",strtotime($spotter_item['date'])).'", ';
			$output .= '"cartographicDegrees": [';
			if ($minitime > strtotime($spotter_item['date'])) $minitime = strtotime($spotter_item['date']);
			if (isset($_COOKIE['MapTrack']) && $id == $_COOKIE['MapTrack'] && $minitracktime > strtotime($spotter_item['date'])) $minitracktime = strtotime($spotter_item['date']);
			if ($maxitime < strtotime($spotter_item['date'])) $maxitime = strtotime($spotter_item['date']);
			$output .= '"'.date("c",strtotime($spotter_item['date'])).'", ';
			$output .= $spotter_item['longitude'].', ';
			$output .= $spotter_item['latitude'];
			$prevlong = $spotter_item['longitude'];
			$prevlat = $spotter_item['latitude'];
			//if (!$tracker && !$marine) {
			//if (!$marine && (!isset($onground) || !$onground)) {
			if (!$marine) {
				if (isset($spotter_item['real_altitude']) && $spotter_item['real_altitude'] != '') {
					$output .= ', '.round($spotter_item['real_altitude']*0.3048);
					if ($tracker) {
						$prevalt = round($spotter_item['real_altitude']*0.3048);
					} else {
						$prevalt = round($spotter_item['real_altitude']*30.48);
					}
				} elseif ($tracker) {
					$output .= ', '.round($spotter_item['altitude']*0.3048);
					$prevalt = round($spotter_item['altitude']*0.3048);
				} else {
					$output .= ', '.round($spotter_item['altitude']*30.48);
					$prevalt = round($spotter_item['altitude']*30.48);
				}
			} else $output .= ', 0';
			//$orientation = '"orientation" : { ';
			//$orientation .= '"unitQuaternion": [';
			//$quat = quaternionrotate(deg2rad($spotter_item['heading']),deg2rad(0),deg2rad(0));
			//$orientation .= '"'.date("c",strtotime($spotter_item['date'])).'",'.$quat['x'].','.$quat['y'].','.$quat['z'].','.$quat['w'];
		} else {
			$nblatlong = $nblatlong+1;
			$output .= ',"'.date("c",strtotime($spotter_item['date'])).'", ';
			if ($maxitime < strtotime($spotter_item['date'])) $maxitime = strtotime($spotter_item['date']);
			if ($spotter_item['ground_speed'] == 0) {
				$output .= $prevlong.', ';
				$output .= $prevlat;
				//if (!$marine && (!isset($onground) || !$onground)) $output .= ', '.$prevalt;
				if (!$marine) $output .= ', '.$prevalt;
				else $output .= ', 0';
			} else {
				$output .= $spotter_item['longitude'].', ';
				$output .= $spotter_item['latitude'];
				//if (!$marine && (!isset($onground) || !$onground)) {
				if (!$marine) {
					if ($spotter_item['altitude'] == '') {
						if ($prevalt != '') {
							$output .= ', '.$prevalt;
						} else {
							$output .= ', 0';
						}
					} else {
						if (isset($spotter_item['real_altitude']) && $spotter_item['real_altitude'] != '') $output .= ', '.round($spotter_item['real_altitude']*0.3048);
						elseif ($tracker) {
							$output .= ', '.round($spotter_item['altitude']*0.3048);
						} else {
							$output .= ', '.round($spotter_item['altitude']*30.48);
						}
					}
				} else $output .= ', 0';
			}
			//$quat = quaternionrotate(deg2rad($spotter_item['heading']),deg2rad(0),deg2rad(0));
			//$orientation .= ',"'.date("c",strtotime($spotter_item['date'])).'",'.$quat['x'].','.$quat['y'].','.$quat['z'].','.$quat['w'];
		}
	}
	//$output  = substr($output, 0, -1);
	$output .= ']}}';
} else {
	$output  = substr($output, 0, -1);
}
$output .= ']';
if (isset($globalArchive) && $globalArchive === TRUE) {
	if (isset($begindateinitial)) {
		$output = str_replace('%minitime%',date("c",$begindateinitial),$output);
	} elseif ((time()-$globalLiveInterval) > $minitime) {
		if (time()-$globalLiveInterval > $maxitime) {
			$output = str_replace('%minitime%',date("c",$maxitime),$output);
		} else {
			$output = str_replace('%minitime%',date("c",time()-$globalLiveInterval),$output);
		}
	}
	else $output = str_replace('%minitime%',date("c",$minitime),$output);
} elseif (isset($_COOKIE['MapTrack']) && $_COOKIE['MapTrack'] != '' && $minitracktime != $minitracktime_begin) {
	$output = str_replace('%minitime%',date("c",$minitracktime),$output);
} else {
	$output = str_replace('%minitime%',date("c",$minitime),$output);
}
if (isset($enddateinitial)) {
	$output = str_replace('%maxitime%',date("c",$enddateinitial),$output);
} else {
	$output = str_replace('%maxitime%',date("c",$maxitime),$output);
}
if ($gltf2) $output = str_replace('%gltf2%','true',$output);
else $output = str_replace('%gltf2%','false',$output);
print $output;
?>
