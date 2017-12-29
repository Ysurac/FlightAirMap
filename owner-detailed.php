<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
require_once('require/class.Language.php');
//require_once('require/class.Translation.php');
require_once('require/class.Stats.php');
//require_once('require/class.SpotterLive.php');
require_once('require/class.SpotterArchive.php');

if (!isset($_GET['owner'])){
	header('Location: '.$globalURL.'');
} else {
	$Spotter = new Spotter();
	$SpotterArchive = new SpotterArchive();
	//$Translation = new Translation();
	//calculuation for the pagination
	if(!isset($_GET['limit']))
	{
		$limit_start = 0;
		$limit_end = 25;
		$absolute_difference = 25;
	} else {
		$limit_explode = explode(",", $_GET['limit']);
		if (isset($limit_explode[1])) {
			$limit_start = filter_var($limit_explode[0],FILTER_SANITIZE_NUMBER_INT);
			$limit_end = filter_var($limit_explode[1],FILTER_SANITIZE_NUMBER_INT);
		} else {
			$limit_start = 0;
			$limit_end = 25;
		}
		if (!ctype_digit(strval($limit_start)) || !ctype_digit(strval($limit_end))) {
			$limit_start = 0;
			$limit_end = 25;
		}
	}
	$absolute_difference = abs($limit_start - $limit_end);
	$limit_next = $limit_end + $absolute_difference;
	$limit_previous_1 = $limit_start - $absolute_difference;
	$limit_previous_2 = $limit_end - $absolute_difference;
	
	$page_url = $globalURL.'/owner/'.$_GET['owner'];
	
	$owner = urldecode(filter_input(INPUT_GET,'owner',FILTER_SANITIZE_STRING));
	$sort = filter_input(INPUT_GET,'sort',FILTER_SANITIZE_STRING);
	$year = filter_input(INPUT_GET,'year',FILTER_SANITIZE_NUMBER_INT);
	$month = filter_input(INPUT_GET,'month',FILTER_SANITIZE_NUMBER_INT);
	$filter = array();
	if ($year != '') $filter = array_merge($filter,array('year' => $year));
	if ($month != '') $filter = array_merge($filter,array('month' => $month));
	$spotter_array = $Spotter->getSpotterDataByOwner($owner,$limit_start.",".$absolute_difference, $sort,$filter);
	if (empty($spotter_array) && isset($globalArchiveResults) && $globalArchiveResults) {
		$spotter_array = $SpotterArchive->getSpotterDataByOwner($owner,$limit_start.",".$absolute_difference, $sort,$filter);
	}

	if (!empty($spotter_array))
	{
		$title = sprintf(_("Detailed View for %s"),$spotter_array[0]['aircraft_owner']);
		//$ident = $spotter_array[0]['ident'];
		if (isset($spotter_array[0]['latitude'])) $latitude = $spotter_array[0]['latitude'];
		if (isset($spotter_array[0]['longitude'])) $longitude = $spotter_array[0]['longitude'];
		require_once('header.php');
		print '<div class="info column">';
		print '<h1>'.$spotter_array[0]['aircraft_owner'].'</h1>';
		//print '<div><span class="label">'._("Owner").'</span>'.$spotter_array[0]['aircraft_owner'].'</div>';
		$Stats = new Stats();
		if ($year == '' && $month == '') $flights = $Stats->getStatsOwner($owner);
		else $flights = 0;
		if ($flights == 0) $flights = $Spotter->countFlightsByOwner($owner,$filter);
		print '<div><span class="label">'._("Flights").'</span>'.$flights.'</div>';
		$aircraft_type = count($Spotter->countAllAircraftTypesByOwner($owner,$filter));
		print '<div><span class="label">'._("Aircraft type").'</span>'.$aircraft_type.'</div>';
		$aircraft_registration = count($Spotter->countAllAircraftRegistrationByOwner($owner,$filter));
		print '<div><span class="label">'._("Aircraft").'</span>'.$aircraft_registration.'</div>';
		$aircraft_manufacturer = count($Spotter->countAllAircraftManufacturerByOwner($owner,$filter));
		print '<div><span class="label">'._("Manufacturers").'</span>'.$aircraft_manufacturer.'</div>';
		$airlines = count($Spotter->countAllAirlinesByOwner($owner,$filter));
		print '<div><span class="label">'._("Airlines").'</span>'.$airlines.'</div>';
		$duration = $Spotter->getFlightDurationByOwner($owner,$filter);
		if ($duration != '0') print '<div><span class="label">'._("Total flights spotted duration").'</span>'.$duration.'</div>';
		print '</div>';
	
		include('owner-sub-menu.php');
		print '<div class="table column">';
		print '<p>'.sprintf(_("The table below shows the detailed information of all flights with the owner <strong>%s</strong>."),$spotter_array[0]['aircraft_owner']).'</p>';

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
		print '<p>'._("Sorry, this owner is not in the database. :(").'</p>'; 
	}
}
require_once('footer.php');
?>