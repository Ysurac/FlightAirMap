<?php
require_once('require/class.Connection.php');
require_once('require/class.Language.php');
require_once('require/class.Translation.php');
$type = '';
$ident = urldecode(filter_input(INPUT_GET,'ident',FILTER_SANITIZE_STRING));
if (isset($_GET['marine'])) {
	require_once('require/class.Marine.php');
	require_once('require/class.MarineLive.php');
	require_once('require/class.MarineArchive.php');
	$Marine = new Marine();
	$MarineArchive = new MarineArchive();
	$type = 'marine';
	$page_url = $globalURL.'/marine/ident/'.$_GET['ident'];
} elseif (isset($_GET['tracker'])) {
	require_once('require/class.Tracker.php');
	require_once('require/class.TrackerLive.php');
	require_once('require/class.TrackerArchive.php');
	$Tracker = new Tracker();
	$TrackerArchive = new TrackerArchive();
	$type = 'tracker';
	$page_url = $globalURL.'/tracker/ident/'.$_GET['ident'];
} else {
	require_once('require/class.Spotter.php');
	require_once('require/class.SpotterLive.php');
	require_once('require/class.SpotterArchive.php');
	$Spotter = new Spotter();
	$SpotterArchive = new SpotterArchive();
	$type = 'aircraft';
	$page_url = $globalURL.'/ident/'.$_GET['ident'];
}

if (!isset($_GET['ident'])){
	header('Location: '.$globalURL.'');
} else {
	$Translation = new Translation();
	//calculuation for the pagination
	if(!isset($_GET['limit']))
	{
		$limit_start = 0;
		$limit_end = 25;
		$absolute_difference = 25;
	} else {
		$limit_explode = explode(",", $_GET['limit']);
		if (isset($limit_explode[1])) {
			$limit_start = $limit_explode[0];
			$limit_end = $limit_explode[1];
			if (!ctype_digit(strval($limit_start)) || !ctype_digit(strval($limit_end))) {
				$limit_start = 0;
				$limit_end = 25;
			}
		} else {
			$limit_start = 0;
			$limit_end = 25;
		}
	}
	$absolute_difference = abs($limit_start - $limit_end);
	$limit_next = $limit_end + $absolute_difference;
	$limit_previous_1 = $limit_start - $absolute_difference;
	$limit_previous_2 = $limit_end - $absolute_difference;
	
	$sort = filter_input(INPUT_GET,'sort',FILTER_SANITIZE_STRING);
	if ($type == 'aircraft') {
		if ($sort != '') 
		{
			$spotter_array = $Spotter->getSpotterDataByIdent($ident,$limit_start.",".$absolute_difference, $sort);
			if (empty($spotter_array) && isset($globalArchiveResults) && $globalArchiveResults) {
				$spotter_array = $SpotterArchive->getSpotterDataByIdent($ident,$limit_start.",".$absolute_difference, $sort);
			}
		} else {
			$spotter_array = $Spotter->getSpotterDataByIdent($ident,$limit_start.",".$absolute_difference);
			if (empty($spotter_array) && isset($globalArchiveResults) && $globalArchiveResults) {
				$spotter_array = $SpotterArchive->getSpotterDataByIdent($ident,$limit_start.",".$absolute_difference);
			}
		}
		if (empty($spotter_array)) {
			$new_ident = $Translation->checkTranslation($ident);
			if ($new_ident != $ident) {
				$ident = $new_ident;
				if ($sort != '') 
				{
					$spotter_array = $Spotter->getSpotterDataByIdent($ident,$limit_start.",".$absolute_difference, $sort);
					if (empty($spotter_array) && isset($globalArchiveResults) && $globalArchiveResults) {
						$spotter_array = $SpotterArchive->getSpotterDataByIdent($ident,$limit_start.",".$absolute_difference, $sort);
					}
				} else {
					$spotter_array = $Spotter->getSpotterDataByIdent($ident,$limit_start.",".$absolute_difference);
					if (empty($spotter_array) && isset($globalArchiveResults) && $globalArchiveResults) {
						$spotter_array = $SpotterArchive->getSpotterDataByIdent($ident,$limit_start.",".$absolute_difference);
					}
				}
			}
		}
	} elseif ($type == 'marine') {
		if ($sort != '') 
		{
			$spotter_array = $Marine->getMarineDataByIdent($ident,$limit_start.",".$absolute_difference, $sort);
			if (empty($spotter_array) && isset($globalArchiveResults) && $globalArchiveResults) {
				$spotter_array = $MarineArchive->getMarineDataByIdent($ident,$limit_start.",".$absolute_difference, $sort);
			}
		} else {
			$spotter_array = $Marine->getMarineDataByIdent($ident,$limit_start.",".$absolute_difference);
			if (empty($spotter_array) && isset($globalArchiveResults) && $globalArchiveResults) {
				$spotter_array = $MarineArchive->getMarineDataByIdent($ident,$limit_start.",".$absolute_difference);
			}
		}
	} elseif ($type == 'tracker') {
		if ($sort != '') 
		{
			$spotter_array = $Tracker->getTrackerDataByIdent($ident,$limit_start.",".$absolute_difference, $sort);
			if (empty($spotter_array) && isset($globalArchiveResults) && $globalArchiveResults) {
				$spotter_array = $TrackerArchive->getTrackerDataByIdent($ident,$limit_start.",".$absolute_difference, $sort);
			}
		} else {
			$spotter_array = $Tracker->getTrackerDataByIdent($ident,$limit_start.",".$absolute_difference);
			if (empty($spotter_array) && isset($globalArchiveResults) && $globalArchiveResults) {
				$spotter_array = $TrackerArchive->getTrackerDataByIdent($ident,$limit_start.",".$absolute_difference);
			}
		}
	}

	if (!empty($spotter_array))
	{
		$title = sprintf(_("Detailed View for %s"),$spotter_array[0]['ident']);
		$ident = $spotter_array[0]['ident'];
		if (isset($spotter_array[0]['latitude'])) $latitude = $spotter_array[0]['latitude'];
		if (isset($spotter_array[0]['longitude'])) $longitude = $spotter_array[0]['longitude'];
		require_once('header.php');
		if (isset($globalArchive) && $globalArchive && $type == 'aircraft') {
			// Requirement for altitude graph
			$all_data = $SpotterArchive->getAltitudeSpeedArchiveSpotterDataById($spotter_array[0]['flightaware_id']);
			if (isset($globalTimezone)) {
				date_default_timezone_set($globalTimezone);
			} else date_default_timezone_set('UTC');
			if (is_array($all_data) && count($all_data) > 1) {
				print '<link href="'.$globalURL.'/css/c3.min.css" rel="stylesheet" type="text/css">';
				print '<script type="text/javascript" src="'.$globalURL.'/js/d3.min.js"></script>';
				print '<script type="text/javascript" src="'.$globalURL.'/js/c3.min.js"></script>';
				print '<div id="chart" class="chart" width="100%"></div><script>';
				$altitude_data = '';
				$hour_data = '';
				$speed_data = '';
				foreach($all_data as $data)
				{
					$hour_data .= '"'.$data['date'].'",';
					if (isset($data['real_altitude']) && $data['real_altitude'] != '') {
						$altitude = $data['real_altitude'];
					} else {
						$altitude = $data['altitude'].'00';
					}
					if ((!isset($_COOKIE['unitaltitude']) && isset($globalUnitAltitude) && $globalUnitAltitude == 'feet') || (isset($_COOKIE['unitaltitude']) && $_COOKIE['unitaltitude'] == 'feet')) {
						$unit = 'feet';
					} else {
						$unit = 'm';
						$altitude = round($altitude*0.3048);
					}
					$altitude_data .= $altitude.',';
					$speed = $data['ground_speed'];
					if ((!isset($_COOKIE['unitspeed']) && isset($globalUnitSpeed) && $globalUnitSpeed == 'mph') || (isset($_COOKIE['unitspeed']) && $_COOKIE['unitspeed'] == 'mph')) {
						$speed = round($speed*1.15078);
						$units = 'mph';
					} elseif ((!isset($_COOKIE['unitspeed']) && isset($globalUnitSpeed) && $globalUnitSpeed == 'knots') || (isset($_COOKIE['unitspeed']) && $_COOKIE['unitspeed'] == 'knots')) {
						$units = 'knots';
					} else {
						$speed = round($speed*1.852);
						$units = 'km/h';
					}
					$speed_data .= $speed.',';
				}
				$hour_data = "['x',".substr($hour_data, 0, -1)."]";
				$altitude_data = "['altitude',".substr($altitude_data,0,-1)."]";
				$speed_data = "['speed',".substr($speed_data,0,-1)."]";
				print 'c3.generate({
				    bindto: "#chart",
				    data: {
					x: "x",
					axes: {
					    altitude: "y",
					    speed: "y2"
					},
					xFormat: "%Y-%m-%d %H:%M:%S",
					columns: ['.$hour_data.','.$altitude_data.','.$speed_data.'],
					colors: { 
					    altitude: "#1a3151",
					    speed: "#aa0000"
					}
				    },
				    axis: { 
					x: { 
					    type: "timeseries", tick: { format: "%H:%M:%S"}
					},
					y: {
					    label: "Altitude ('.$unit.')"
					},
					y2: { 
					    label: "Speed ('.$units.')",
					    show: true
					}
				    },
				    legend: { show: false }});';
				print '</script>';
  			}
		}
		print '<div class="info column">';
		print '<br/><br/><br/>';
		print '<h1>'.$spotter_array[0]['ident'].'</h1>';
		print '<div><span class="label">'._("Ident").'</span>'.$spotter_array[0]['ident'].'</div>';
		if (isset($spotter_array[0]['blocked']) && $spotter_array[0]['blocked'] === true) print '<div>'._("Callsign is in blocked FAA list").'</div>';
		if (isset($spotter_array[0]['airline_icao'])) {
			print '<div><span class="label">'._("Airline").'</span><a href="'.$globalURL.'/airline/'.$spotter_array[0]['airline_icao'].'">'.$spotter_array[0]['airline_name'].'</a></div>'; 
		}
		if ($type == 'aircraft') print '<div><span class="label">'._("Flight History").'</span><a href="http://flightaware.com/live/flight/'.$spotter_array[0]['ident'].'" target="_blank">'._("View the Flight History of this callsign").'</a></div>';
		print '</div>';
	
		if ($type == 'aircraft') include('ident-sub-menu.php');
		print '<div class="table column">';
		if ($type == 'aircraft') print '<p>'.sprintf(_("The table below shows the detailed information of all flights with the ident/callsign of <strong>%s</strong>."),$spotter_array[0]['ident']).'</p>';
		elseif ($type == 'marine') print '<p>'.sprintf(_("The table below shows the detailed information of all vessels with the ident/callsign of <strong>%s</strong>."),$spotter_array[0]['ident']).'</p>';
		elseif ($type == 'tracker') print '<p>'.sprintf(_("The table below shows the detailed information of all trackers with the ident/callsign of <strong>%s</strong>."),$spotter_array[0]['ident']).'</p>';

		include('table-output.php'); 
		print '<div class="pagination">';
		if ($limit_previous_1 >= 0)
		{
			print '<a href="'.$page_url.'/'.$limit_previous_1.','.$limit_previous_2.'/'.$sort.'">&laquo;'._("Previous Page").'</a>';
		}
		if ($spotter_array[0]['query_number_rows'] == $absolute_difference)
		{
			print '<a href="'.$page_url.'/'.$limit_end.','.$limit_next.'/'.$sort.'">'._("Next Page").'&raquo;</a>';
		}
		print '</div>';
		print '</div>';
	} else {
		$title = _("Ident");
		require_once('header.php');
		print '<h1>'._("Error").'</h1>';
		print '<p>'._("Sorry, this ident/callsign is not in the database. :(").'</p>'; 
	}
}
require_once('footer.php');
?>