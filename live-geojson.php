<?php
require_once('require/class.Connection.php');
require_once('require/class.Common.php');
require_once('require/class.Spotter.php');
require_once('require/class.SpotterLive.php');
require_once('require/class.SpotterArchive.php');
$begintime = microtime(true);
$SpotterLive = new SpotterLive();
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

if (isset($globalMapPopup) && !$globalMapPopup && !(isset($_COOKIE['flightpopup']) && $_COOKIE['flightpopup'] == 'true')) $min = true;

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
} elseif (isset($_GET['coord']) && (!isset($globalMapPopup) || $globalMapPopup || (isset($_COOKIE['flightpopup']) && $_COOKIE['flightpopup'] == 'true'))) {
//if (isset($_GET['coord'])) {
	$coord = explode(',',$_GET['coord']);
	$spotter_array = $SpotterLive->getLiveSpotterDatabyCoord($coord,$filter);

#} elseif (isset($globalMapPopup) && !$globalMapPopup) {
} elseif (isset($_GET['archive']) && isset($_GET['begindate']) && isset($_GET['enddate'])) {
	$from_archive = true;
//	$begindate = filter_input(INPUT_GET,'begindate',FILTER_VALIDATE_REGEXP,array("options"=>array("regexp"=>'~^\d{4}/\d{2}/\d{2}$~')));
//	$enddate = filter_input(INPUT_GET,'enddate',FILTER_VALIDATE_REGEXP,array("options"=>array("regexp"=>'~^\d{4}/\d{2}/\d{2}$~')));
	$begindate = filter_input(INPUT_GET,'begindate',FILTER_SANITIZE_NUMBER_INT);
	$enddate = filter_input(INPUT_GET,'enddate',FILTER_SANITIZE_NUMBER_INT);
	$begindate = date('Y-m-d H:i:s',$begindate);
	$enddate = date('Y-m-d H:i:s',$enddate);
	$spotter_array = $SpotterArchive->getMinLiveSpotterData($begindate,$enddate,$filter);
} elseif ($min) {
	$spotter_array = $SpotterLive->getMinLiveSpotterData($filter);
#	$min = true;
} else {
	$spotter_array = $SpotterLive->getLiveSpotterData('','',$filter);
}

if (!empty($spotter_array)) {
	if (isset($_GET['archive'])) {
		$flightcnt = $SpotterArchive->getLiveSpotterCount($begindate,$enddate,$filter);
	} else {
		$flightcnt = $SpotterLive->getLiveSpotterCount($filter);
	}
	if ($flightcnt == '') $flightcnt = 0;
} else $flightcnt = 0;

$sqltime = round(microtime(true)-$begintime,2);

$j = 0;

$output = '{';
	$output .= '"type": "FeatureCollection",';
		if ($min) $output .= '"minimal": "true",';
		else $output .= '"minimal": "false",';

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
						$output .= '"properties": {';
							$output .= '"flightaware_id": "'.$spotter_item['flightaware_id'].'",';
							$output .= '"flight_cnt": "'.$flightcnt.'",';
							$output .= '"sqltime": "'.$sqltime.'",';
							if (isset($begindate)) $output .= '"archive_date": "'.$begindate.'",';

/*
							if ($min) $output .= '"minimal": "true",';
							else $output .= '"minimal": "false",';
*/
							//$output .= '"flight_cnt": "'.$spotter_item['nb'].'",';
						if (isset($spotter_item['ident']) && $spotter_item['ident'] != '') {
							$output .= '"callsign": "'.$spotter_item['ident'].'",';
						} else {
							$output .= '"callsign": "NA",';
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
						if (isset($spotter_item['aircraft_icao'])) {
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
						    $output .= '"aircraft_shadow": "default.png",';
						} else $output .= '"aircraft_shadow": "'.$spotter_item['aircraft_shadow'].'",';
						if (isset($spotter_item['airline_name'])) {
							$output .= '"airline_name": "'.$spotter_item['airline_name'].'",';
						} elseif (!$min) {
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
						$nextcoord = $Common->nextcoord($spotter_item['latitude'],$spotter_item['longitude'],$spotter_item['ground_speed'],$spotter_item['heading']);
						$output .= '"nextlatitude": "'.$nextcoord['latitude'].'",';
						$output .= '"nextlongitude": "'.$nextcoord['longitude'].'",';
						$output .= '"nextlatlon": ['.$nextcoord['latitude'].','.$nextcoord['longitude'].'],';

						if (!$min) $output .= '"image": "'.$image.'",';
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
						if (isset($spotter_item['waypoints']) && $spotter_item['waypoints'] != '') {
							$output .= '"waypoints": "'.$spotter_item['waypoints'].'",';
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
				if ((isset($_COOKIE['flightpath']) && $_COOKIE['flightpath'] == 'true') || (!isset($_COOKIE['flightpath']) && (!isset($globalMapHistory) || $globalMapHistory || $allhistory) || (isset($_GET['history']) && $_GET['history'] != '' && $_GET['history'] != 'NA' && ($_GET['history'] == $spotter_item['ident'] || $_GET['history'] == $spotter_item['flightaware_id'])))) {
                                    if ($from_archive) {
					    $spotter_history_array = $SpotterArchive->getAllArchiveSpotterDataById($spotter_item['flightaware_id']);
                                    } else {
					    $spotter_history_array = $SpotterLive->getAllLiveSpotterDataById($spotter_item['flightaware_id']);
                                    }
                            	$d = false;
				foreach ($spotter_history_array as $key => $spotter_history)
				{
				    if (abs($spotter_history['longitude']-$spotter_item['longitude']) > 200 || $d==true) {
					if ($d == false) $d = true;
				    } else {
					$alt = round($spotter_history['altitude']/10)*10;
					if (!isset($prev_alt) || $prev_alt != $alt) {
					    if (isset($prev_alt)) {
						//$output_history .= '['.$spotter_history['longitude'].', '.$spotter_history['latitude'].','.$spotter_history['altitude'].']';
						$output_history .= '['.$spotter_history['longitude'].', '.$spotter_history['latitude'].']';
						$output_history .= ']}},';
						$output .= $output_history;
					    }
					    $output_history = '{"type": "Feature","properties": {"callsign": "'.$spotter_item['ident'].'","type": "history","altitude": "'.$alt.'"},"geometry": {"type": "LineString","coordinates": [';
					}
					$output_history .= '[';
					$output_history .=  $spotter_history['longitude'].', ';
					$output_history .=  $spotter_history['latitude'];
					//$output_history .=  $spotter_history['altitude'];
					$output_history .= '],';
					$prev_alt = $alt;
				    }
				}
				if (isset($output_history)) {
				    $output_history  = substr($output_history, 0, -1);
				    $output_history .= ']}},';
				    $output .= $output_history;
				    unset($prev_alt);
				    unset($output_history);
				}
				}
				
				if (isset($_GET['history']) && $_GET['history'] == $spotter_item['ident'] && isset($spotter_item['departure_airport']) && $spotter_item['departure_airport'] != 'NA' && isset($spotter_item['arrival_airport']) && $spotter_item['arrival_airport'] != 'NA' && ((isset($_COOKIE['MapRoute']) && $_COOKIE['MapRoute'] == "true") || (!isset($_COOKIE['MapRoute']) && (!isset($globalMapRoute) || (isset($globalMapRoute) && $globalMapRoute))))) {
				    $output_air = '{"type": "Feature","properties": {"callsign": "'.$spotter_item['ident'].'","type": "route"},"geometry": {"type": "LineString","coordinates": [';
				    if (isset($spotter_item['departure_airport_latitude'])) {
					$output_air .= '['.$spotter_item['departure_airport_longitude'].','.$spotter_item['departure_airport_latitude'].'],';
				    } elseif (isset($spotter_item['departure_airport']) && $spotter_item['departure_airport'] != 'NA') {
					$dairport = $Spotter->getAllAirportInfo($spotter_item['departure_airport']);
					//print_r($dairport);
					//echo $spotter_item['departure_airport'];
					if (isset($dairport[0]['latitude'])) {
					    $output_air .= '['.$dairport[0]['longitude'].','.$dairport[0]['latitude'].'],';
					}
				    }
				    if (isset($spotter_item['arrival_airport_latitude'])) {
					$output_air .= '['.$spotter_item['arrival_airport_longitude'].','.$spotter_item['arrival_airport_latitude'].']';
				    } elseif (isset($spotter_item['arrival_airport']) && $spotter_item['arrival_airport'] != 'NA') {
					//print_r($aairport);
					$aairport = $Spotter->getAllAirportInfo($spotter_item['arrival_airport']);
					if (isset($aairport[0]['latitude'])) {
					    $output_air .= '['.$aairport[0]['longitude'].','.$aairport[0]['latitude'].']';
					}
				    }
				    $output_air .= ']}},';
				    $output .= $output_air;
				    unset($output_air);
				}
			}
			$output  = substr($output, 0, -1);
			$output .= ']';
			$output .= ',"initial_sqltime": "'.$sqltime.'",';
			$output .= '"totaltime": "'.round(microtime(true)-$begintime,2).'",';
			if (isset($begindate)) $output .= '"archive_date": "'.$begindate.'",';
			$output .= '"flight_cnt": "'.$j.'"';
		} else {
			$output .= '"features": ';
			$output .= '{';
			$output .= '"type": "Feature",';
			$output .= '"properties": {';
			$output .= '"flight_cnt": "'.$flightcnt.'"}}';
		}
		
$output .= '}';

print $output;

?>
