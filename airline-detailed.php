<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');

if (!isset($_GET['airline'])){
	header('Location: '.$globalURL.'/airline');
} else{
	$Spotter = new Spotter();
	//calculuation for the pagination
	if(!isset($_GET['limit']) || $_GET['limit'] == "")
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
	
	$airline = filter_input(INPUT_GET,'airline',FILTER_SANITIZE_STRING);
	$page_url = $globalURL.'/airline/'.$airline;
	
	$sort = filter_input(INPUT_GET,'sort',FILTER_SANITIZE_STRING);
	$spotter_array = $Spotter->getSpotterDataByAirline($_GET['airline'],$limit_start.",".$absolute_difference, $sort);
	
	if (!empty($spotter_array))
	{
		if (isset($spotter_array[0]['airline_name']) && isset($spotter_array[0]['airline_icao'])) {
			$title = 'Detailed View for '.$spotter_array[0]['airline_name'].' ('.$spotter_array[0]['airline_icao'].')';
		} else $title = '';
		require_once('header.php');
	  
		print '<div class="select-item">';
		print '<form action="'.$globalURL.'/airline" method="post">';
		print '<select name="airline" class="selectpicker" data-live-search="true">';
		print '<option></option>';
		$airline_names = $Spotter->getAllAirlineNames();
		foreach($airline_names as $airline_name)
		{
			if($_GET['airline'] == $airline_name['airline_icao'])
			{
				print '<option value="'.$airline_name['airline_icao'].'" selected="selected">'.$airline_name['airline_name'].' ('.$airline_name['airline_icao'].')</option>';
			} else {
				print '<option value="'.$airline_name['airline_icao'].'">'.$airline_name['airline_name'].' ('.$airline_name['airline_icao'].')</option>';
			}
		}
		print '</select>';
		print '<button type="submit"><i class="fa fa-angle-double-right"></i></button>';
		print '</form>';
		print '</div>';

		if ($airline != "NA")
		{
			print '<div class="info column">';
			print '<h1>'.$spotter_array[0]['airline_name'].' ('.$spotter_array[0]['airline_icao'].')</h1>';
			if ($globalIVAO && @getimagesize($globalURL.'/images/airlines/'.$spotter_array[0]['airline_icao'].'.gif'))
			{
				print '<img src="'.$globalURL.'/images/airlines/'.$spotter_array[0]['airline_icao'].'.gif" alt="'.$spotter_array[0]['airline_name'].' ('.$spotter_array[0]['airline_icao'].')" title="'.$spotter_array[0]['airline_name'].' ('.$spotter_array[0]['airline_icao'].')" class="logo" />';
			} elseif (@getimagesize($globalURL.'/images/airlines/'.$spotter_array[0]['airline_icao'].'.png'))
			{
				print '<img src="'.$globalURL.'/images/airlines/'.$spotter_array[0]['airline_icao'].'.png" alt="'.$spotter_array[0]['airline_name'].' ('.$spotter_array[0]['airline_icao'].')" title="'.$spotter_array[0]['airline_name'].' ('.$spotter_array[0]['airline_icao'].')" class="logo" />';
			}
			print '<div><span class="label">Name</span>'.$spotter_array[0]['airline_name'].'</div>';
			print '<div><span class="label">Country</span>'.$spotter_array[0]['airline_country'].'</div>';
			print '<div><span class="label">ICAO</span>'.$spotter_array[0]['airline_icao'].'</div>';
			if (isset($spotter_array[0]['airline_iata'])) print '<div><span class="label">IATA</span>'.$spotter_array[0]['airline_iata'].'</div>';
			if (isset($spotter_array[0]['airline_callsign'])) print '<div><span class="label">Callsign</span>'.$spotter_array[0]['airline_callsign'].'</div>'; 
			print '<div><span class="label">Type</span>'.ucwords($spotter_array[0]['airline_type']).'</div>';
			print '</div>';
		} else {
			print '<div class="alert alert-warning">This special airline profile shows all flights that do <u>not</u> have a airline associated with them.</div>';
		}

		include('airline-sub-menu.php');
		print '<div class="table column">';
		if (isset($spotter_array[0]['airline_name'])) {
			print '<p>The table below shows the detailed information of all flights from <strong>'.$spotter_array[0]['airline_name'].'</strong>.</p>';
		}

		include('table-output.php');  
		print '<div class="pagination">';
		if ($limit_previous_1 >= 0)
		{
			print '<a href="'.$page_url.'/'.$limit_previous_1.','.$limit_previous_2.'/'.$sort.'">&laquo;Previous Page</a>';
		}
		if ($spotter_array[0]['query_number_rows'] == $absolute_difference)
		{
			print '<a href="'.$page_url.'/'.$limit_end.','.$limit_next.'/'.$sort.'">Next Page&raquo;</a>';
		}
		print '</div>';
		print '</div>';
	} else {
		$title = "Airline";
		require_once('header.php');
		print '<h1>Error</h1>';
		print '<p>Sorry, the airline does not exist in this database. :(</p>'; 
	}
}
require_once('footer.php');
?>