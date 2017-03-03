<?php
require_once('require/class.Connection.php');
require_once('require/class.Common.php');
$tracker = false;
if (isset($_GET['tracker'])) $tracker = true;
if ($tracker) {
	require_once('require/class.Tracker.php');
	require_once('require/class.TrackerLive.php');
	//require_once('require/class.SpotterArchive.php');
	$TrackerLive = new TrackerLive();
	$Tracker = new Tracker();
//	$SpotterArchive = new SpotterArchive();
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
if ((!isset($globalMapVAchoose) || $globalMapVAchoose) && isset($globalVATSIM) && $globalVATSIM && isset($_COOKIE['filter_ShowVATSIM']) && $_COOKIE['filter_ShowVATSIM'] == 'true') $filter['source'] = array_merge($filter['source'],array('vatsimtxt'));
if ((!isset($globalMapVAchoose) || $globalMapVAchoose) && isset($globalIVAO) && $globalIVAO && isset($_COOKIE['filter_ShowIVAO']) && $_COOKIE['filter_ShowIVAO'] == 'true') $filter['source'] = array_merge($filter['source'],array('whazzup'));
if ((!isset($globalMapVAchoose) || $globalMapVAchoose) && isset($globalphpVMS) && $globalphpVMS && isset($_COOKIE['filter_ShowVMS']) && $_COOKIE['filter_ShowVMS'] == 'true') $filter['source'] = array_merge($filter['source'],array('phpvmacars'));
if ((!isset($globalMapchoose) || $globalMapchoose) && isset($globalSBS1) && $globalSBS1 && isset($_COOKIE['filter_ShowSBS1']) && $_COOKIE['filter_ShowSBS1'] == 'true') $filter['source'] = array_merge($filter['source'],array('sbs'));
if ((!isset($globalMapchoose) || $globalMapchoose) && isset($globalAPRS) && $globalAPRS && isset($_COOKIE['filter_ShowAPRS']) && $_COOKIE['filter_ShowAPRS'] == 'true') $filter['source'] = array_merge($filter['source'],array('aprs'));
if (isset($_COOKIE['filter_ident']) && $_COOKIE['filter_ident'] != '') $filter['ident'] = filter_var($_COOKIE['filter_ident'],FILTER_SANITIZE_STRING);
if (isset($_COOKIE['filter_Airlines']) && $_COOKIE['filter_Airlines'] != '') $filter['airlines'] = filter_var_array(explode(',',$_COOKIE['filter_Airlines']),FILTER_SANITIZE_STRING);
if (isset($_COOKIE['filter_Sources']) && $_COOKIE['filter_Sources'] != '') $filter['source_aprs'] = filter_var_array(explode(',',$_COOKIE['filter_Sources']),FILTER_SANITIZE_STRING);
if (isset($_COOKIE['filter_airlinestype']) && $_COOKIE['filter_airlinestype'] != 'all') $filter['airlinestype'] = filter_var($_COOKIE['filter_airlinestype'],FILTER_SANITIZE_STRING);
if (isset($_COOKIE['filter_alliance']) && $_COOKIE['filter_alliance'] != 'all') $filter['alliance'] = filter_var($_COOKIE['filter_alliance'],FILTER_SANITIZE_STRING);
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
	$spotter_array = $SpotterArchive->getMinLiveSpotterDataPlayback($begindate,$enddate,$filter);
} elseif (isset($_COOKIE['archive']) && isset($_COOKIE['archive_begin']) && isset($_COOKIE['archive_end']) && isset($_COOKIE['archive_speed'])) {
	$from_archive = true;
//	$begindate = filter_input(INPUT_GET,'begindate',FILTER_VALIDATE_REGEXP,array("options"=>array("regexp"=>'~^\d{4}/\d{2}/\d{2}$~')));
//	$enddate = filter_input(INPUT_GET,'enddate',FILTER_VALIDATE_REGEXP,array("options"=>array("regexp"=>'~^\d{4}/\d{2}/\d{2}$~')));
//	$begindate = filter_var($_COOKIE['archive_begin'],FILTER_SANITIZE_NUMBER_INT);
//	$enddate = filter_var($_COOKIE['archive_end'],FILTER_SANITIZE_NUMBER_INT);
	$begindate = $_COOKIE['archive_begin'];
	$enddate = $_COOKIE['archive_end'];

	$archivespeed = filter_var($_COOKIE['archive_speed'],FILTER_SANITIZE_NUMBER_INT);
	$begindate = date('Y-m-d H:i:s',$begindate);
	$enddate = date('Y-m-d H:i:s',$enddate);
//	echo 'Begin : '.$begindate.' - End : '.$enddate."\n";
	$spotter_array = $SpotterArchive->getMinLiveSpotterData($begindate,$enddate,$filter);
} elseif ($tracker) {
	$spotter_array = $TrackerLive->getMinLastLiveTrackerData($filter);
} else {
	$spotter_array = $SpotterLive->getMinLastLiveSpotterData($filter);
}

if (!empty($spotter_array)) {
	if (isset($_GET['archive'])) {
		$flightcnt = $SpotterArchive->getLiveSpotterCount($begindate,$enddate,$filter);
	} elseif ($tracker) {
		$flightcnt = $TrackerLive->getLiveTrackerCount($filter);
	} else {
		$flightcnt = $SpotterLive->getLiveSpotterCount($filter);
	}
	if ($flightcnt == '') $flightcnt = 0;
} else $flightcnt = 0;

$sqltime = round(microtime(true)-$begintime,2);
$minitime = time();
$maxitime = 0;


$modelsdb = array();
if (file_exists('models/modelsdb')) {
	if (($handle = fopen('models/modelsdb','r')) !== FALSE) {
		while (($row = fgetcsv($handle,1000)) !== FALSE) {
			if (isset($row[1]) ){
				$model = $row[0];
				$modelsdb[$model] = $row[1];
			}
		}
		fclose($handle);
	}
}
$heightrelative = 'NONE';
$j = 0;
$prev_flightaware_id = '';
$speed = 1;
if (isset($archivespeed)) $speed = $archivespeed;
$output = '[';
if ($tracker) {
	$output .= '{"id" : "document", "name" : "tracker","version" : "1.0"';
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

                if (isset($spotter_item['flightaware_id'])) $id = $spotter_item['flightaware_id'];
                elseif (isset($spotter_item['famtrackid'])) $id = $spotter_item['famtrackid'];
		if ($prev_flightaware_id != $id) {
			if ($prev_flightaware_id != '') {
				$output .= ']';
				$output .= '}';
				//$output .= ', '.$orientation.']}';
				$output .= '},';
			}
			$orientation = '';
			$prev_flightaware_id = $id;
			$output .= '{';
			$output .= '"id": "'.$id.'",';
			$output .= '"properties": {';
			// Not yet supported in CZML with Cesium
			$output .= '},';

			$output .= '"path" : { ';
			$output .= '"show" : false, ';
			$output .= '"material" : { ';
			$output .= '"polylineOutline" : { ';
			$output .= '"color" : { "rgba" : [238, 250, 255, 255] }, ';
			$output .= '"outlineColor" : { "rgba" : [200, 209, 214, 255] }, ';
			$output .= '"outlineWidth" : 5, ';
			$output .= '"polylineGlow" : { "color" : { "rgba" : [214, 208, 214, 255] }, "glowPower" : 3 } ';
			$output .= '}';
			$output .= '}, ';
			$output .= '"heightReference": "'.$heightrelative.'",';
			$output .= '"width" : 6, "leadTime" : 0, "trailTime" : 1000000, "resolution" : 10 },';
			//$output .= ' "billboard" : {"image" : "data:image/png;base64,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","scale" : 1.5},';
			if (isset($spotter_item['aircraft_icao'])) {
				$aircraft_icao = $spotter_item['aircraft_icao'];
				if (isset($modelsdb[$aircraft_icao])) {
					$output .= '"model": {"gltf" : "'.$globalURL.'/models/'.$modelsdb[$aircraft_icao].'","scale" : 1.0,"minimumPixelSize": 20';
					$output .= ',"heightReference": "'.$heightrelative.'"';
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
						if (isset($modelsdb[$aircraft_shadow])) {
							$output .= '"model": {"gltf" : "'.$globalURL.'/models/'.$modelsdb[$aircraft_shadow].'","scale" : 1.0,"minimumPixelSize": 20';
							$output .= ',"heightReference": "'.$heightrelative.'"';
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
							if (isset($modelsdb[$model])) {
								$output .= '"model": {"gltf" : "'.$globalURL.'/models/'.$modelsdb[$model].'","scale" : 1.0,"minimumPixelSize": 20';
								$output .= ',"heightReference": "'.$heightrelative.'"';
								$output .= '},';
								$modelsdb[$aircraft_icao] = $modelsdb[$model];
							} else {
								$output .= '"model": {"gltf" : "'.$globalURL.'/models/Cesium_Air.glb","scale" : 1.0,"minimumPixelSize": 20';
								$output .= ',"heightReference": "'.$heightrelative.'"';
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
								$output .= '"model": {"gltf" : "'.$globalURL.'/models/'.$modelsdb[$model].'","scale" : 1.0,"minimumPixelSize": 20';
								$output .= ',"heightReference": "'.$heightrelative.'"';
								$output .= '},';
								$modelsdb[$aircraft_icao] = $modelsdb[$model];
							} else {
								$output .= '"model": {"gltf" : "'.$globalURL.'/models/Cesium_Air.glb","scale" : 1.0,"minimumPixelSize": 20';
								$output .= ',"heightReference": "'.$heightrelative.'"';
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
								$output .= '"model": {"gltf" : "'.$globalURL.'/models/'.$modelsdb[$model].'","scale" : 1.0,"minimumPixelSize": 20';
								$output .= ',"heightReference": "'.$heightrelative.'"';
								$output .= '},';
								$modelsdb[$aircraft_icao] = $modelsdb[$model];
							} else {
								$output .= '"model": {"gltf" : "'.$globalURL.'/models/Cesium_Air.glb","scale" : 1.0,"minimumPixelSize": 20';
								$output .= ',"heightReference": "'.$heightrelative.'"';
								$output .= '},';
								$modelsdb[$aircraft_icao] = 'Cesium_Air.glb';
							}
						} else {
							$output .= '"model": {"gltf" : "'.$globalURL.'/models/Cesium_Air.glb","scale" : 1.0,"minimumPixelSize": 20';
							$output .= ',"heightReference": "'.$heightrelative.'"';
							$output .= '},';
								//if ($spotter_item['aircraft_shadow'] != '') $output .= '"aircraft_shadow": "'.$spotter_item['aircraft_shadow'].'",';
							if ($spotter_item['aircraft_icao'] != '') $output .= '"aircraft_icao": "'.$spotter_item['aircraft_icao'].'",';
							$modelsdb[$aircraft_icao] = 'Cesium_Air.glb';
						}
					} elseif (isset($spotter_item['format_source']) && $spotter_item['format_source'] == 'aprs') {
						$aircraft_shadow = 'PA18';
						$output .= '"model": {"gltf" : "'.$globalURL.'/models/'.$modelsdb[$aircraft_shadow].'","scale" : 1.0,"minimumPixelSize": 20';
						$output .= ',"heightReference": "'.$heightrelative.'"';
						$output .= '},';
						$modelsdb[$aircraft_icao] = $modelsdb[$aircraft_shadow];
					} else {
						$output .= '"model": {"gltf" : "'.$globalURL.'/models/Cesium_Air.glb","scale" : 1.0,"minimumPixelSize": 20';
						$output .= ',"heightReference": "'.$heightrelative.'"';
						$output .= '},';
						//if ($spotter_item['aircraft_shadow'] != '') $output .= '"aircraft_shadow": "'.$spotter_item['aircraft_shadow'].'",';
						if ($spotter_item['aircraft_icao'] != '') $output .= '"aircraft_icao": "'.$spotter_item['aircraft_icao'].'",';
						$modelsdb[$aircraft_icao] = 'Cesium_Air.glb';
					}
				} else {
					$output .= '"model": {"gltf" : "'.$globalURL.'/models/Cesium_Air.glb","scale" : 1.0,"minimumPixelSize": 20';
					$output .= ',"heightReference": "'.$heightrelative.'"';
					$output .= '},';
					//if ($spotter_item['aircraft_shadow'] != '') $output .= '"aircraft_shadow": "'.$spotter_item['aircraft_shadow'].'",';
					if ($spotter_item['aircraft_icao'] != '') $output .= '"aircraft_icao": "'.$spotter_item['aircraft_icao'].'",';
					$modelsdb[$aircraft_icao] = 'Cesium_Air.glb';
				}
			} elseif ($tracker && isset($spotter_item['type'])) {
				
				if ($spotter_item['type'] == 'Car') {
					//$output .= '"model": {"gltf" : "'.$globalURL.'/models/vehicules/car.glb","scale" : 1.0,"minimumPixelSize": 20,';
					$output .= '"model": {"gltf" : "'.$globalURL.'/models/vehicules/car.gltf","scale" : 1.0,"minimumPixelSize": 20';
					$output .= ',"heightReference": "'.$heightrelative.'"';
					$output .= '},';
				} elseif ($spotter_item['type'] == 'Truck' || $spotter_item['type'] == 'Truck (18 Wheeler)') {
					$output .= '"model": {"gltf" : "'.$globalURL.'/models/vehicules/truck.gltf","scale" : 1.0,"minimumPixelSize": 20';
					$output .= ',"heightReference": "'.$heightrelative.'"';
					$output .= '},';
				} elseif ($spotter_item['type'] == 'Firetruck') {
					$output .= '"model": {"gltf" : "'.$globalURL.'/models/vehicules/firetruck.glb","scale" : 1.0,"minimumPixelSize": 20';
					$output .= ',"heightReference": "'.$heightrelative.'"';
					$output .= '},';
				} elseif ($spotter_item['type'] == 'Bike') {
					$output .= '"model": {"gltf" : "'.$globalURL.'/models/vehicules/cycle.glb","scale" : 1.0,"minimumPixelSize": 20';
					$output .= ',"heightReference": "'.$heightrelative.'"';
					$output .= '},';
				} elseif ($spotter_item['type'] == 'Police') {
					$output .= '"model": {"gltf" : "'.$globalURL.'/models/vehicules/police.glb","scale" : 1.0,"minimumPixelSize": 20';
					$output .= ',"heightReference": "'.$heightrelative.'"';
					$output .= '},';
				} elseif ($spotter_item['type'] == 'Balloon') {
					$output .= '"model": {"gltf" : "'.$globalURL.'/models/vehicules/ball.glb","scale" : 1.0,"minimumPixelSize": 20';
					$output .= ',"heightReference": "'.$heightrelative.'"';
					$output .= '},';
				} elseif ($spotter_item['type'] == 'Ship (Power Boat)' || $spotter_item['type'] == 'Yatch (Sail)') {
					$output .= '"model": {"gltf" : "'.$globalURL.'/models/vehicules/boat.glb","scale" : 1.0,"minimumPixelSize": 20';
					$output .= ',"heightReference": "'.$heightrelative.'"';
					$output .= '},';
				} else {
					$output .= '"model": {"gltf" : "'.$globalURL.'/models/vehicules/truck.gltf","scale" : 1.0,"minimumPixelSize": 20';
					//$output .= '"model": {"gltf" : "'.$globalURL.'/models/vehicules/Cesium_Ground.glb","scale" : 1.0,"minimumPixelSize": 20';
					$output .= ',"heightReference": "'.$heightrelative.'"';
					$output .= '},';
				}
			}
	//		$output .= '"heightReference": "CLAMP_TO_GROUND",';
			$output .= '"heightReference": "'.$heightrelative.'",';
	//		$output .= '"heightReference": "NONE",';
			$output .= '"position": {';
			$output .= '"interpolationAlgorithm":"HERMITE","interpolationDegree":3,';
			$output .= '"type": "Point",';
	//		$output .= '"interpolationAlgorithm" : "LAGRANGE",';
	//		$output .= '"interpolationDegree" : 5,';
	//		$output .= '"epoch" : "'.date("c",strtotime($spotter_item['date'])).'", ';
			$output .= '"cartographicDegrees": [';
			if ($minitime > strtotime($spotter_item['date'])) $minitime = strtotime($spotter_item['date']);
			if ($maxitime < strtotime($spotter_item['date'])) $maxitime = strtotime($spotter_item['date']);
			$output .= '"'.date("c",strtotime($spotter_item['date'])).'", ';
			$output .= $spotter_item['longitude'].', ';
			$output .= $spotter_item['latitude'];
			$prevlong = $spotter_item['longitude'];
			$prevlat = $spotter_item['latitude'];
			if (!$tracker) {
				$output .= ', '.round($spotter_item['altitude']*30.48);
				$prevalt = round($spotter_item['altitude']*30.48);
			} else $output .= ', 0';
			//$orientation = '"orientation" : { ';
			//$orientation .= '"unitQuaternion": [';
			//$quat = quaternionrotate(deg2rad($spotter_item['heading']),deg2rad(0),deg2rad(0));
			//$orientation .= '"'.date("c",strtotime($spotter_item['date'])).'",'.$quat['x'].','.$quat['y'].','.$quat['z'].','.$quat['w'];
		} else {
			$output .= ',"'.date("c",strtotime($spotter_item['date'])).'", ';
			if ($maxitime < strtotime($spotter_item['date'])) $maxitime = strtotime($spotter_item['date']);
			if ($spotter_item['ground_speed'] == 0) {
				$output .= $prevlong.', ';
				$output .= $prevlat;
				if (!$tracker) $output .= ', '.$prevalt;
				else $output .= ', 0';
			} else {
				$output .= $spotter_item['longitude'].', ';
				$output .= $spotter_item['latitude'];
				if (!$tracker) {
					if ($spotter_item['altitude'] == '') {
						if ($prevalt != '') {
							$output .= ', '.$prevalt;
						} else {
							$output .= ', 0';
						}
					} else {
						$output .= ', '.round($spotter_item['altitude']*30.48);
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
$output = str_replace('%minitime%',date("c",$minitime),$output);
$output = str_replace('%maxitime%',date("c",$maxitime),$output);
print $output;
?>
