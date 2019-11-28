<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
require_once('require/class.Common.php');
require_once('require/class.Language.php');
require_once('require/class.SpotterLive.php');
require_once('require/class.SpotterArchive.php');
require_once('require/class.Elevation.php');
$SpotterLive = new SpotterLive();
$SpotterArchive = new SpotterArchive();
$Elevation = new Elevation();
$Common = new Common();

$from_archive = false;
if (isset($_GET['ident'])) {
	$ident = filter_input(INPUT_GET,'ident',FILTER_SANITIZE_STRING);
	if (isset($_GET['currenttime'])) {
		$currenttime = filter_input(INPUT_GET,'currenttime',FILTER_SANITIZE_NUMBER_INT);
		$currenttime = round($currenttime/1000);
		$spotter_array = $SpotterLive->getDateLiveSpotterDataByIdent($ident,$currenttime);
		if (empty($spotter_array) && isset($globalArchiveResults) && $globalArchiveResults) {
			$from_archive = true;
			$spotter_array = $SpotterArchive->getDateArchiveSpotterDataByIdent($ident,$currenttime);
		}
	} else {
		$spotter_array = $SpotterLive->getLastLiveSpotterDataByIdent($ident);
		if (empty($spotter_array) && isset($globalArchiveResults) && $globalArchiveResults) {
			$from_archive = true;
			$spotter_array = $SpotterArchive->getLastArchiveSpotterDataByIdent($ident);
		}
	}
}
if (isset($_GET['flightaware_id'])) {
	$flightaware_id = filter_input(INPUT_GET,'flightaware_id',FILTER_SANITIZE_STRING);
	if (isset($_GET['currenttime'])) {
		$currenttime = filter_input(INPUT_GET,'currenttime',FILTER_SANITIZE_NUMBER_INT);
		$currenttime = round($currenttime/1000);
		$spotter_array = $SpotterLive->getDateLiveSpotterDataById($flightaware_id,$currenttime);
		if (empty($spotter_array) && isset($globalArchiveResults) && $globalArchiveResults) {
			$from_archive = true;
//			$spotter_array = $SpotterArchive->getLastArchiveSpotterDataById($flightaware_id);
			$spotter_array = $SpotterArchive->getDateArchiveSpotterDataById($flightaware_id,$currenttime);
		}
	} else {
		$spotter_array = $SpotterLive->getLastLiveSpotterDataById($flightaware_id);
		if (empty($spotter_array) && isset($globalArchiveResults) && $globalArchiveResults) {
			$from_archive = true;
			$spotter_array = $SpotterArchive->getLastArchiveSpotterDataById($flightaware_id);
		}
	}
}
 ?>
<div class="alldetails">
<button type="button" class="close">&times;</button>
<?php
if (!empty($spotter_array)) {
	$spotter_item = $spotter_array[0];
	date_default_timezone_set('UTC');
	if (isset($spotter_item['image_thumbnail']) && $spotter_item['image_thumbnail'] != "")
	{
		if ($spotter_item['image_source'] == 'flickr' || $spotter_item['image_source'] == 'wikimedia' || $spotter_item['image_source'] == 'devianart') {
			$image = preg_replace("/^http:/i","https:",$spotter_item['image_thumbnail']);
		} else $image = $spotter_item['image_thumbnail'];
	}
	/* else {
		$image = "images/placeholder_thumb.png";
	} */

	print '<div class="top">';
	if (isset($globalCam) && $globalCam === TRUE) {
		print '<div class="left"><img src="http://192.168.1.47:81/videostream.cgi?user=admin&pwd=888888&resolution=8&rate=0" alt="cam"/></div>';
	}
	if (isset($image)) {
		print '<div class="left"><img src="'.$image.'" alt="'.$spotter_item['registration'].' '.$spotter_item['aircraft_name'].'" title="'.$spotter_item['registration'].' '.$spotter_item['aircraft_name'].' Image &copy; '.$spotter_item['image_copyright'].'"/><br />Image &copy; '.$spotter_item['image_copyright'].'</div>';
	}
	print '<div class="right">';
	print '<div class="callsign-details">';
	if ($spotter_item['ident'] != 'Not Available') {
		print '<div class="callsign"><a href="'.$globalURL.'/redirect/'.$spotter_item['flightaware_id'].'" target="_blank">'.$spotter_item['ident'].'</a>';
		if (isset($spotter_item['blocked']) && $spotter_item['blocked'] === true) print '<img src="'.$globalURL.'/images/forbidden.png" title="'._("Callsign is in blocked FAA list").'" class="blocked" />';
		print '</div>';
	}
	if (isset($spotter_item['airline_name']) && $spotter_item['airline_name'] != 'Not Available') print '<div class="airline">'.$spotter_item['airline_name'].'</div>';
	print '</div>';
	if ($spotter_item['departure_airport'] != 'NA' && $spotter_item['arrival_airport'] != 'NA') {
		print '<div class="nomobile airports"><div class="airport"><span class="code"><a href="'.$globalURL.'/airport/'.$spotter_item['departure_airport'].'" target="_blank">'.$spotter_item['departure_airport'].'</a></span>'.$spotter_item['departure_airport_city'].' '.$spotter_item['departure_airport_country'];
		if (isset($spotter_item['departure_airport_time']) && $spotter_item['departure_airport_time'] != 'NULL') {
			if ($spotter_item['departure_airport_time'] > 2460) {
				print '<br /><span class="time">'.date('H:m',$spotter_item['departure_airport_time']).'</span>';
			} else {
				print '<br /><span class="time">'.$spotter_item['departure_airport_time'].'</span>';
			}
		}
		print '</div><i class="fa fa-long-arrow-right"></i><div class="airport">';
		print '<span class="code"><a href="'.$globalURL.'/airport/'.$spotter_item['arrival_airport'].'" target="_blank">'.$spotter_item['arrival_airport'].'</a></span>'.$spotter_item['arrival_airport_city'].' '.$spotter_item['arrival_airport_country'];
		if (isset($spotter_item['arrival_airport_time']) && $spotter_item['arrival_airport_time'] != 'NULL') {
			if ($spotter_item['arrival_airport_time'] > 2460) {
				print '<br /><span class="time">'.date('H:m',$spotter_item['arrival_airport_time']).'</span>';
			} else {
				print '<br /><span class="time">'.$spotter_item['arrival_airport_time'].'</span>';
			}
		}
		print '</div></div>';
		//if (isset($spotter_item['route_stop'])) print 'Route stop : '.$spotter_item['route_stop'];
		print '</div></div>';
	}
	print '<div class="details"><div class="mobile airports"><div class="airport">';
	print '<span class="code"><a href="'.$globalURL.'/airport/'.$spotter_item['departure_airport'].'" target="_blank">'.$spotter_item['departure_airport'].'</a></span>'.$spotter_item['departure_airport_city'].' '.$spotter_item['departure_airport_country'];
	print '</div><i class="fa fa-long-arrow-right"></i><div class="airport">';
	print '<span class="code"><a href="'.$globalURL.'/airport/'.$spotter_item['arrival_airport'].'" target="_blank">'.$spotter_item['arrival_airport'].'</a></span>'.$spotter_item['arrival_airport_city'].' '.$spotter_item['arrival_airport_country'];
	print '</div>';
	print '</div>';
	print '<div id="aircraft">';
	print '<span>'._("Aircraft").'</span>';
	if (isset($spotter_item['aircraft_wiki'])) print '<a href="'.$spotter_item['aircraft_wiki'].'">'.$spotter_item['aircraft_name'].'</a>';
	if (isset($spotter_item['aircraft_type']) && isset($spotter_item['aircraft_manufacturer']) && $spotter_item['aircraft_manufacturer'] != 'N/A' && isset($spotter_item['aircraft_name']) && $spotter_item['aircraft_name'] != 'N/A') {
		$aircraft_names = explode('/',$spotter_item['aircraft_name']);
		if (count($aircraft_names) == 1) print '<a href="'.$globalURL.'/aircraft/'.$spotter_item['aircraft_type'].'">'.$spotter_item['aircraft_manufacturer'].' '.$spotter_item['aircraft_name'].' ('.$spotter_item['aircraft_type'].')</a>';
		else print '<a href="'.$globalURL.'/aircraft/'.$spotter_item['aircraft_type'].'" title="'.$spotter_item['aircraft_name'].'">'.$spotter_item['aircraft_manufacturer'].' '.$aircraft_names[0].' ('.$spotter_item['aircraft_type'].')</a>';
	} elseif (isset($spotter_item['aircraft_type'])) print '<a href="'.$globalURL.'/aircraft/'.$spotter_item['aircraft_type'].'">'.$spotter_item['aircraft_type'].'</a>';
	else print $spotter_item['aircraft_manufacturer'].' '.$spotter_item['aircraft_name'];
	print '</div>';
	if (isset($spotter_item['registration']) && $spotter_item['registration'] != '') print '<div><span>'._("Registration").'</span><a href="'.$globalURL.'/registration/'.$spotter_item['registration'].'" target="_blank">'.$spotter_item['registration'].'</a></div>';

	print '<div id="altitude"><span>'._("Altitude").'</span>';
	if (isset($globalGroundAltitude) && $globalGroundAltitude) {
		try {
			$groundAltitude = $Elevation->getElevation($spotter_item['latitude'],$spotter_item['longitude']);
		} catch(Exception $e) {
			// If catched not exist
		}
	}

	print '<span class="altitude">';
	if ((!isset($_COOKIE['unitaltitude']) && isset($globalUnitAltitude) && $globalUnitAltitude == 'feet') || (isset($_COOKIE['unitaltitude']) && $_COOKIE['unitaltitude'] == 'feet')) {
		if (isset($spotter_item['real_altitude']) && $spotter_item['real_altitude'] != '') print $spotter_item['real_altitude'].' feet (FL'.$spotter_item['altitude'].')';
		else print $spotter_item['altitude'].'00 feet (FL'.$spotter_item['altitude'].')';
	} else {
		if (isset($spotter_item['real_altitude']) && $spotter_item['real_altitude'] != '') print round($spotter_item['real_altitude']*0.3048).' m (FL'.$spotter_item['altitude'].')';
		else print round($spotter_item['altitude']*30.48).' m (FL'.$spotter_item['altitude'].')';
	}
	print '</span>';

	if (isset($groundAltitude) && $groundAltitude < $spotter_item['altitude']*30.48) {
		print '<br>';
		print '<span>'._("Ground Altitude").'</span>';
		print '<i>';
		print '<span class="groundaltitude">';
		if ((!isset($_COOKIE['unitaltitude']) && isset($globalUnitAltitude) && $globalUnitAltitude == 'feet') || (isset($_COOKIE['unitaltitude']) && $_COOKIE['unitaltitude'] == 'feet')) {
			print round($groundAltitude*3.28084).' feet';
		} else {
			print round($groundAltitude).' m';
		}
		print '</span>';
		print '</i>';
	}
	print '</div>';
	print '<div id="coordinates"><span>'._("Coordinates").'</span>';
	if ((!isset($_COOKIE['unitcoordinate']) && isset($globalUnitCoordinate) && $globalUnitCoordinate == 'dms') || (isset($_COOKIE['unitcoordinate']) && $_COOKIE['unitcoordinate'] == 'dms')) {
		$latitude = $Common->convertDMS($spotter_item['latitude'],'latitude');
		print '<span class="latitude">'.$latitude['deg'].'° '.$latitude['min']."′ ".$latitude['sec'].'" '.$latitude['NSEW'].'</span>, ';
		$longitude = $Common->convertDMS($spotter_item['longitude'],'longitude');
		print '<span class="longitude">'.$longitude['deg'].'° '.$longitude['min']."′ ".$longitude['sec'].'" '.$longitude['NSEW'].'</span>';
	} elseif ((!isset($_COOKIE['unitcoordinate']) && isset($globalUnitCoordinate) && $globalUnitCoordinate == 'dm') || (isset($_COOKIE['unitcoordinate']) && $_COOKIE['unitcoordinate'] == 'dm')) {
		$latitude = $Common->convertDM($spotter_item['latitude'],'latitude');
		print '<span class="latitude">'.$latitude['deg'].'° '.round($latitude['min'],3)."′".$latitude['NSEW'].'</span>, ';
		$longitude = $Common->convertDM($spotter_item['longitude'],'longitude');
		print '<span class="longitude">'.$longitude['deg'].'° '.round($longitude['min'],3)."′".$longitude['NSEW'].'</span>';
	} else {
		print '<span class="latitude">'.$spotter_item['latitude'].'</span>, ';
		print '<span class="longitude">'.$spotter_item['longitude'].'</span>';
	}
	print '</div>';

	print '<div id="speed"><span>'._("Speed").'</span>';
	if ((!isset($_COOKIE['unitspeed']) && isset($globalUnitSpeed) && $globalUnitSpeed == 'mph') || (isset($_COOKIE['unitspeed']) && $_COOKIE['unitspeed'] == 'mph')) {
		print round($spotter_item['ground_speed']*1.15078).' mph';
	} elseif ((!isset($_COOKIE['unitspeed']) && isset($globalUnitSpeed) && $globalUnitSpeed == 'knots') || (isset($_COOKIE['unitspeed']) && $_COOKIE['unitspeed'] == 'knots')) {
		print $spotter_item['ground_speed'].' knots';
	} else {
		print round($spotter_item['ground_speed']*1.852).' km/h';
	}
	print '</div>';
	if (isset($_COOKIE['MapFormat']) && $_COOKIE['MapFormat'] == '3d') {
		print '<div id="realspeed"><span>'._("Calculated Speed").'</span>';
		print '<span class="realspeed"></span>';
		print '</div>';
	}

	if (isset($globalCam) && $globalCam) {
		require_once(dirname(__FILE__).'/require/class.Common.php');
		$Common = new Common();
		$azimuth = round($Common->azimuth($globalCenterLatitude,$globalCenterLongitude,$spotter_item['latitude'],$spotter_item['longitude']));
		$distance = $Common->distance($globalCenterLatitude,$globalCenterLongitude,$spotter_item['latitude'],$spotter_item['longitude'],'m');
		$plunge = round($Common->plunge($globalCenterAltitude,$spotter_item['real_altitude'],$distance));
		print '<div id="camcoordinates"><span>'._("Cam Coordinates").'</span>';
		print 'azimuth: '.$azimuth;
		print ' / ';
		print 'plunge: '.$plunge;
		print ' / ';
		print 'distance: '.$distance;
		print '</div>';
		//echo $Common->getData('http://127.0.0.1/camera.php?azimuth='.$azimuth.'&plunge='.$plunge,'get','','','','','','',false,true);
		//echo $Common->getData('file://'.dirname(__FILE__).'/camera.php?azimuth='.$azimuth.'&plunge='.$plunge,'get','','','','','','',false,true);
		echo $Common->getData('http://'.$_SERVER['SERVER_NAME'].'/camera.php?azimuth='.$azimuth.'&plunge='.$plunge,'get','','','','','','',false,true);
	}
  
	print '<div id="heading"><span>'._("Heading").'</span><span class="heading">'.$spotter_item['heading'].'</span>°</div>';
	if (isset($spotter_item['verticalrate']) && $spotter_item['verticalrate'] != '') {
		print '<div id="verticalrate"><span>'._("Vertical rate").'</span>';
		if ((!isset($_COOKIE['unitaltitude']) && isset($globalUnitAltitude) && $globalUnitAltitude == 'feet') || (isset($_COOKIE['unitaltitude']) && $_COOKIE['unitaltitude'] == 'feet')) {
			print $spotter_item['verticalrate']. ' ft/min';
		} else {
			print round($spotter_item['verticalrate']*0.3048). ' m/min';
		}
		print '</div>';
	}
	if (isset($spotter_item['pilot_name']) && $spotter_item['pilot_name'] != '') {
		print '<div id="pilot"><span>'._("Pilot").'</span>';
		if (isset($spotter_item['pilot_id'])) print $spotter_item['pilot_name'].' ('.$spotter_item['pilot_id'].')';
		else print $spotter_item['pilot_name'];
		print '</div>';
	}
	if (isset($spotter_item['aircraft_owner']) && $spotter_item['aircraft_owner'] != '') {
		print '<div id="owner"><span>'._("Owner").'</span>';
		print $spotter_item['aircraft_owner'];
		print '</div>';
	}
	if (isset($spotter_item['over_country']) && $spotter_item['over_country'] != '') {
		print '<div id="overcountry"><span>'._("Over country").'</span>';
		print $spotter_item['over_country'];
		print '</div>';
	}
	if (isset($spotter_item['source_name']) && $spotter_item['source_name'] != '') {
		print '<div id="source"><span>'._("Source").'</span>';
		print $spotter_item['source_name'];
		print '</div>';
	}
	print '<div id="trackit">';
	if (isset($_COOKIE['MapFormat']) && $_COOKIE['MapFormat'] == '3d') {
		print '<a href="?3d&trackid='.$spotter_item['flightaware_id'].'">'._("Track it link !").'</a>';
	} else {
		print '<a href="?2d&trackid='.$spotter_item['flightaware_id'].'">'._("Track it link !").'</a>';
	}
	print '</div>';
	print '</div>';
	if (isset($globalVA) && $globalVA && isset($globalphpVMS) && $globalphpVMS && isset($globalVATSIM) && $globalVATSIM && isset($globalIVAO) && $globalIVAO && isset($spotter_item['format_source']) && $spotter_item['format_source'] != '' && $spotter_item['format_source'] != 'pireps') print '<div class="waypoints"><span>'._("Source").'</span>'.$spotter_item['format_source'].'</div>';
	if (isset($spotter_item['waypoints']) && $spotter_item['waypoints'] != '') print '<div class="waypoints"><span>'._("Route").'</span>'.$spotter_item['waypoints'].'</div>';
	if (isset($spotter_item['acars']['message'])) print '<div class="acars"><span>'._("Latest ACARS message").'</span>'.trim(str_replace(array("\r\n","\r","\n","\\r","\\n","\\r\\n"),'<br/>',$spotter_item['acars']['message'])).'</div>';
	if (isset($spotter_item['squawk']) && $spotter_item['squawk'] != '' && $spotter_item['squawk'] != 0) print '<div class="bottom">'._("Squawk:").' '.$spotter_item['squawk'].' - '.$spotter_item['squawk_usage'].'</div>';
	print '</div>';
}
?>
</div>
