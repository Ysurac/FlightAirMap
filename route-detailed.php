<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
require_once('require/class.Language.php');

if (!isset($_GET['departure_airport']) || !isset($_GET['arrival_airport'])){
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
	
	$departure_airport = filter_input(INPUT_GET,'departure_airport',FILTER_SANITIZE_STRING);
	$arrival_airport = filter_input(INPUT_GET,'arrival_airport',FILTER_SANITIZE_STRING);
	$page_url = $globalURL.'/route/'.$departure_airport.'/'.$arrival_airport;
	$sort = filter_input(INPUT_GET,'sort',FILTER_SANITIZE_STRING);
	if ($sort != '') {
		$spotter_array = $Spotter->getSpotterDataByRoute($departure_airport, $arrival_airport, $limit_start.",".$absolute_difference, $sort);
	} else {
		$spotter_array = $Spotter->getSpotterDataByRoute($departure_airport, $arrival_airport, $limit_start.",".$absolute_difference, '');
	}
 
	if (!empty($spotter_array))
	{
		$title = sprintf(_("Detailed View for flights between %s (%s), %s - %s (%s), %s"),$spotter_array[0]['departure_airport_name'],$spotter_array[0]['departure_airport_icao'],$spotter_array[0]['departure_airport_country'],$spotter_array[0]['arrival_airport_name'],$spotter_array[0]['arrival_airport_icao'],$spotter_array[0]['arrival_airport_country']);
		require_once('header.php');
		print '<div class="info column">';
		print '<h1>'._("Flights between").' '.$spotter_array[0]['departure_airport_name'].' ('.$spotter_array[0]['departure_airport_icao'].'), '.$spotter_array[0]['departure_airport_country'].' - '.$spotter_array[0]['arrival_airport_name'].' ('.$spotter_array[0]['arrival_airport_icao'].'), '.$spotter_array[0]['arrival_airport_country'].'</h1>';
		print '<div><span class="label">'._("Coming From").'</span><a href="'.$globalURL.'/airport/'.$spotter_array[0]['departure_airport_icao'].'">'.$spotter_array[0]['departure_airport_name'].' ('.$spotter_array[0]['departure_airport_icao'].'), '.$spotter_array[0]['departure_airport_country'].'</a></div>';
		print '<div><span class="label">'._("Flying To").'</span><a href="'.$globalURL.'/airport/'.$spotter_array[0]['arrival_airport_icao'].'">'.$spotter_array[0]['arrival_airport_name'].' ('.$spotter_array[0]['arrival_airport_icao'].'), '.$spotter_array[0]['arrival_airport_country'].'</a></div>';
		print '</div>';

		include('route-sub-menu.php');
		print '<div class="table column">';
		print '<p>'.sprintf(_("The table below shows the detailed information of all flights that used the route <strong>%s - %s</strong>."),$spotter_array[0]['departure_airport_icao'],$spotter_array[0]['arrival_airport_icao']).'</p>';
    
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
		$title = _("Unknown Route");
		require_once('header.php');
		print '<h1>'._("Error").'</h1>';
		print '<p>'._("Sorry, this route does not exist in this database. :(").'</p>'; 
	}
}

require_once('footer.php');
?>