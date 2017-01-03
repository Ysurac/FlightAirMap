<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
require_once('require/class.Language.php');

if (!isset($_GET['registration'])){
	header('Location: '.$globalURL.'');
} else {
	$Spotter = new Spotter();
	//calculuation for the pagination
	if(!isset($_GET['limit']))
	{
		$limit_start = 0;
		$limit_end = 25;
		$absolute_difference = 25;
	}  else {
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
	$registration = filter_input(INPUT_GET,'registration',FILTER_SANITIZE_STRING);
	$sort = filter_input(INPUT_GET,'sort',FILTER_SANITIZE_STRING);
	
	$page_url = $globalURL.'/registration/'.$registration;
	
	if ($sort != '') {
		$spotter_array = $Spotter->getSpotterDataByRegistration($registration, $limit_start.",".$absolute_difference, $sort);
	} else {
		$spotter_array = $Spotter->getSpotterDataByRegistration($registration, $limit_start.",".$absolute_difference, '');
	}
	$aircraft_array = $Spotter->getAircraftInfoByRegistration($registration);
	
	if (!empty($spotter_array))
	{
		$title = sprintf(_("Detailed View of aircraft with registration %s"),$registration);
		require_once('header.php');
		print '<div class="info column">';
		print '<h1>'.$registration.' - '.$aircraft_array[0]['aircraft_name'].' ('.$aircraft_array[0]['aircraft_icao'].')</h1>';
		print '<div><span class="label">'._("Name").'</span><a href="'.$globalURL.'/aircraft/'.$aircraft_array[0]['aircraft_icao'].'">'.$aircraft_array[0]['aircraft_name'].'</a></div>';
		print '<div><span class="label">'._("ICAO").'</span><a href="'.$globalURL.'/aircraft/'.$aircraft_array[0]['aircraft_icao'].'">'.$aircraft_array[0]['aircraft_icao'].'</a></div>'; 
		print '<div><span class="label">'._("Manufacturer").'</span><a href="'.$globalURL.'/manufacturer/'.strtolower(str_replace(" ", "-", $aircraft_array[0]['aircraft_manufacturer'])).'">'.$aircraft_array[0]['aircraft_manufacturer'].'</a></div>';
		print '</div>';
		
		include('registration-sub-menu.php');
		print '<div class="table column">';
		print '<p>'.sprintf(_("The table below shows the detailed information of all flights of aircraft with the registration <strong>%s</strong>."),$registration).'</p>';

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
		$title = _("Registration");
		require_once('header.php');
		print '<h1>'._("Error").'</h1>';
		print '<p>'._("Sorry, this registration does not exist in this database. :(").'</p>'; 
	}
}

require_once('footer.php');
?>