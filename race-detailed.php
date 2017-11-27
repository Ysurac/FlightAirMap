<?php
require_once('require/class.Connection.php');
require_once('require/class.Marine.php');
require_once('require/class.Stats.php');
require_once('require/class.Language.php');
require_once('require/class.MarineArchive.php');

if (!isset($_GET['race'])){
	header('Location: '.$globalURL.'/');
} else {
	$type = 'marine';
	$Marine = new Marine();
	$MarineArchive = new MarineArchive();
	//calculuation for the pagination
	if(!isset($_GET['limit']))
	{
		$limit_start = 0;
		$limit_end = 25;
		$absolute_difference = 25;
	} else {
		$limit_explode = explode(",", $_GET['limit']);
		$limit_start = $limit_explode[0];
		$limit_end = $limit_explode[1];
		if (!ctype_digit(strval($limit_start)) || !ctype_digit(strval($limit_end))) {
			$limit_start = 0;
			$limit_end = 25;
		}
	}
	$absolute_difference = abs($limit_start - $limit_end);
	$limit_next = $limit_end + $absolute_difference;
	$limit_previous_1 = $limit_start - $absolute_difference;
	$limit_previous_2 = $limit_end - $absolute_difference;
	
	
	$race = filter_input(INPUT_GET,'race',FILTER_SANITIZE_STRING);
	$page_url = $globalURL.'/marine/race/'.$race;
	$sort = filter_input(INPUT_GET,'sort',FILTER_SANITIZE_STRING);
	$year = filter_input(INPUT_GET,'year',FILTER_SANITIZE_NUMBER_INT);
	$month = filter_input(INPUT_GET,'month',FILTER_SANITIZE_NUMBER_INT);
	$race_data = $Marine->getRace($race);
	$filter = array();
	if ($year != '') $filter = array_merge($filter,array('year' => $year));
	if ($month != '') $filter = array_merge($filter,array('month' => $month));
	if ($sort != '') 
	{
		$spotter_array = $Marine->getMarineDataByRace($race,$limit_start.",".$absolute_difference, $sort,$filter);
		/*
		if (empty($spotter_array) && isset($globalArchiveResults) && $globalArchiveResults) {
			$spotter_array = $MarineArchive->getMarineDataByRace($race,$limit_start.",".$absolute_difference, $sort,$filter);
		}
		*/
	} else {
		$spotter_array = $Marine->getMarineDataByRace($race,$limit_start.",".$absolute_difference,'',$filter);
		/*
		if (empty($spotter_array) && isset($globalArchiveResults) && $globalArchiveResults) {
			$spotter_array = $MarineArchive->getMarineDataByRace($race,$limit_start.",".$absolute_difference,'',$filter);
		}
		*/
	}

	if (!empty($spotter_array))
	{
		$title = sprintf(_("Detailed View for %s"),$spotter_array[0]['race_name']);
		$ident = $spotter_array[0]['ident'];
		if (isset($spotter_array[0]['latitude'])) $latitude = $spotter_array[0]['latitude'];
		if (isset($spotter_array[0]['longitude'])) $longitude = $spotter_array[0]['longitude'];
		require_once('header.php');
		/*
		if (isset($globalArchive) && $globalArchive) {
			// Requirement for altitude graph
			print '<script type="text/javascript" src="https://www.google.com/jsapi"></script>';
			$all_data = $MarineArchive->getAltitudeSpeedArchiveMarineDataById($spotter_array[0]['flightaware_id']);
			if (isset($globalTimezone)) {
				date_default_timezone_set($globalTimezone);
			} else date_default_timezone_set('UTC');
			if (count($all_data) > 0) {
				print '<div id="chart6" class="chart" width="100%"></div>
                    <script> 
                        google.load("visualization", "1.1", {packages:["line","corechart"]});
                      google.setOnLoadCallback(drawChart6);
                      function drawChart6() {
                        var data = google.visualization.arrayToDataTable([
                            ["Hour","'._("Altitude").'","'._("Speed").'"], ';
                            $altitude_data = '';
				foreach($all_data as $data)
				{
					$altitude_data .= '[ "'.date("G:i",strtotime($data['date']." UTC")).'",'.$data['altitude'].','.$data['ground_speed'].'],';
				}
				$altitude_data = substr($altitude_data, 0, -1);
				print $altitude_data.']);

                        var options = {
                            legend: {position: "none"},
                            series: {
                                0: {axis: "Altitude"},
                                1: {axis: "Speed"}
                            },
                            axes: {
                                y: {
                                    Altitude: {label: "'._("Altitude (FL)").'"},
                                    Speed: {label: "'._("Speed (knots)").'"},
                                }
                            },
                            height:210
                        };

                        var chart = new google.charts.Line(document.getElementById("chart6"));
                        chart.draw(data, options);
                      }
                      $(window).resize(function(){
                              drawChart6();
                            });
				 </script>';
  			}
		}
		*/
		print '<div class="info column">';
		print '<h1>'.$spotter_array[0]['race_name'].'</h1>';
		if (isset($spotter_array[0]['race_id']) && $spotter_array[0]['race_id'] != '') print '<div><span class="label">'._("race ID").'</span>'.$spotter_array[0]['race_id'].'</div>';
		/*
		if ($year == '' && $month == '') {
			$Stats = new Stats();
			$flights = $Stats->getStatsrace($race);
		} else $flights = 0;
		if ($flights == 0)
		*/
		$flights = $Marine->countCaptainsByRace($race,$filter);
		print '<div><span class="label">'._("Captains").'</span>'.$flights.'</div>';
		$aircraft_type = count($Marine->countAllBoatTypesByRace($race,$filter));
		print '<div><span class="label">'._("Boat type").'</span>'.$aircraft_type.'</div>';
		//$aircraft_registration = count($Marine->countAllAircraftRegistrationByrace($race,$filter));
		//print '<div><span class="label">'._("Aircraft").'</span>'.$aircraft_registration.'</div>';
		//$aircraft_manufacturer = count($Marine->countAllAircraftManufacturerByrace($race,$filter));
		//print '<div><span class="label">'._("Manufacturers").'</span>'.$aircraft_manufacturer.'</div>';
		//$airlines = count($Marine->countAllAirlinesByrace($race,$filter));
		//print '<div><span class="label">'._("Airlines").'</span>'.$airlines.'</div>';
		//$duration = $Marine->getRaceDurationByrace($race,$filter);
		//if ($duration != '0') print '<div><span class="label">'._("Total races spotted duration").'</span>'.$duration.'</div>';
		if (isset($race_data['race_creator']) && $race_data['race_creator'] != '') {
			print '<div><span class="creator">'._("Creator").'</span>'.$race_data['race_creator'].'</div>';
		}
		if (isset($race_data['race_startdate']) && $race_data['race_startdate'] != '') {
			if (isset($globalTimezone)) {
				date_default_timezone_set($globalTimezone);
			} else date_default_timezone_set('UTC');
			print '<div><span class="startdate">'._("Start date").'</span>'.date("r", strtotime($race_data['race_startdate'])).'</div>';
		}
		print '</div>';
		if (isset($race_data['race_desc']) && $race_data['race_desc'] != '') {
			print '<div class="well">'.nl2br($race_data['race_desc']).'</div>';
		}
	
		//include('race-sub-menu.php');
		print '<div class="table column">';
		print '<p>'.sprintf(_("The table below shows the detailed information of all races with the race <strong>%s</strong>."),$spotter_array[0]['race_name']).'</p>';

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
		$title = _("Owner");
		require_once('header.php');
		print '<h1>'._("Error").'</h1>';
		print '<p>'._("Sorry, this race is not in the database. :(").'</p>'; 
	}
}
require_once('footer.php');
?>