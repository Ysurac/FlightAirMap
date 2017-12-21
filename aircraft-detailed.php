<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
require_once('require/class.Stats.php');
require_once('require/class.Language.php');
$Spotter = new Spotter();

if (!isset($_GET['aircraft_type'])){
	header('Location: '.$globalURL.'/aircraft');
} else {
	//calculuation for the pagination
	if(!isset($_GET['limit']) || count(explode(",", $_GET['limit'])) < 2)
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
	
	$aircraft_type = filter_input(INPUT_GET,'aircraft_type',FILTER_SANITIZE_STRING);
	$page_url = $globalURL.'/aircraft/'.$aircraft_type;
	
	$sort = htmlspecialchars(filter_input(INPUT_GET,'sort',FILTER_SANITIZE_STRING));
	$spotter_array = $Spotter->getSpotterDataByAircraft($aircraft_type,$limit_start.",".$absolute_difference, $sort);
	
	$aircraft_info = $Spotter->getAllAircraftInfo($aircraft_type);
	if (!empty($spotter_array) || !empty($aircraft_info))
	{
		if (!empty($aircraft_info)) {
			$title = sprintf(_("Detailed View for %s (%s)"),$aircraft_info[0]['type'],$aircraft_info[0]['icao']);
		} else {
			$title = sprintf(_("Detailed View for %s (%s)"),$spotter_array[0]['aircraft_name'],$spotter_array[0]['aircraft_type']);
		}
		require_once('header.php');
	    
		print '<div class="select-item">';
		print '<form action="'.$globalURL.'/aircraft" method="get">';
		print '<select name="aircraft_type" class="selectpicker" data-live-search="true">';
		print '<option></option>';
		$Stats = new Stats();
		$aircraft_types = $Stats->getAllAircraftTypes();
		if (empty($aircraft_types)) $aircraft_types = $Spotter->getAllAircraftTypes();
		foreach($aircraft_types as $aircrafttype)
		{
			if($aircraft_type == $aircrafttype['aircraft_icao'])
			{
				print '<option value="'.$aircrafttype['aircraft_icao'].'" selected="selected">'.$aircrafttype['aircraft_manufacturer'].' '.$aircrafttype['aircraft_name'].' ('.$aircrafttype['aircraft_icao'].')</option>';
			} else {
				print '<option value="'.$aircrafttype['aircraft_icao'].'">'.$aircrafttype['aircraft_manufacturer'].' '.$aircrafttype['aircraft_name'].' ('.$aircrafttype['aircraft_icao'].')</option>';
			}
		}
		print '</select>';
		print '<button type="submit"><i class="fa fa-angle-double-right"></i></button>';
		print '</form>';
		print '</div>';
		print '<br />';
		if ($aircraft_type != "NA")
		{
			print '<div class="info column">';
			if (!empty($aircraft_info)) {
				print '<h1>'.$aircraft_info[0]['type'].' ('.$aircraft_info[0]['icao'].')</h1>';
				print '<div><span class="label">'._("Name").'</span>'.$aircraft_info[0]['type'].'</div>';
				print '<div><span class="label">'._("ICAO").'</span>'.$aircraft_info[0]['icao'].'</div>'; 
				print '<div><span class="label">'._("Manufacturer").'</span><a href="'.$globalURL.'/manufacturer/'.strtolower(str_replace(" ", "-", $aircraft_info[0]['manufacturer'])).'">'.$aircraft_info[0]['manufacturer'].'</a></div>';
				if ($aircraft_info[0]['aircraft_description'] != '' && $aircraft_info[0]['aircraft_description'] != 'None') print '<div><span class="label">'._("Description").'</span>'.$aircraft_info[0]['aircraft_description'].'</div>'; 
				if ($aircraft_info[0]['engine_type'] != '' && $aircraft_info[0]['engine_type'] != 'None') print '<div><span class="label">'._("Engine").'</span>'.$aircraft_info[0]['engine_type'].'</div>'; 
				if ($aircraft_info[0]['engine_count'] != '' && $aircraft_info[0]['engine_count'] != 0) print '<div><span class="label">'._("Engine count").'</span>'.$aircraft_info[0]['engine_count'].'</div>'; 
			} else {
				print '<h1>'.$spotter_array[0]['aircraft_name'].' ('.$spotter_array[0]['aircraft_type'].')</h1>';
				print '<div><span class="label">'._("Name").'</span>'.$spotter_array[0]['aircraft_name'].'</div>';
				print '<div><span class="label">'._("ICAO").'</span>'.$spotter_array[0]['aircraft_type'].'</div>'; 
				if (isset($spotter_array[0]['aircraft_manufacturer'])) print '<div><span class="label">'._("Manufacturer").'</span><a href="'.$globalURL.'/manufacturer/'.strtolower(str_replace(" ", "-", $spotter_array[0]['aircraft_manufacturer'])).'">'.$spotter_array[0]['aircraft_manufacturer'].'</a></div>';
			}
			print '</div>';
		} else {
			print '<div class="alert alert-warning">'._("This special aircraft profile shows all flights in where the aircraft type is unknown.").'</div>';
		}
		
		if (!empty($spotter_array)) {
			include('aircraft-sub-menu.php');
			print '<div class="table column">';
			print '<p>'.sprintf(_("The table below shows the detailed information of all flights from <strong>%s (%s)</strong>."),$spotter_array[0]['aircraft_name'],$spotter_array[0]['aircraft_type']).'</p>';
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
			print '<p>'._("No flights of this aircraft type exist in this database.").'</p>';
		}
	} else {
		$title = _("Aircraft");
		require_once('header.php');
		print '<h1>'._("Errors").'</h1>';
		print '<p>'._("Sorry, the aircraft type does not exist in this database. :(").'</p>'; 
	}
}
require_once('footer.php');
?>