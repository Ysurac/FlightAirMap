<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
require_once('require/class.Stats.php');
require_once('require/class.Language.php');

if (!isset($_GET['aircraft_type'])) {
	header('Location: '.$globalURL.'/aircraft');
	die();
}
$aircraft_type = filter_input(INPUT_GET,'aircraft_type',FILTER_SANITIZE_STRING);
$Spotter = new Spotter();
$spotter_array = $Spotter->getSpotterDataByAircraft($aircraft_type,"0,1","");


if (!empty($spotter_array))
{
	$title = sprintf(_("Most Common Airlines from %s (%s)"),$spotter_array[0]['aircraft_name'],$spotter_array[0]['aircraft_type']);
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
			print '<option value="'.$aircrafttype['aircraft_icao'].'" selected="selected">'.$aircrafttype['aircraft_name'].' ('.$aircrafttype['aircraft_icao'].')</option>';
		} else {
			print '<option value="'.$aircrafttype['aircraft_icao'].'">'.$aircrafttype['aircraft_name'].' ('.$aircrafttype['aircraft_icao'].')</option>';
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
		print '<h1>'.$spotter_array[0]['aircraft_name'].' ('.$spotter_array[0]['aircraft_type'].')</h1>';
		print '<div><span class="label">'._("Name").'</span>'.$spotter_array[0]['aircraft_name'].'</div>';
		print '<div><span class="label">'._("ICAO").'</span>'.$spotter_array[0]['aircraft_type'].'</div>'; 
		print '<div><span class="label">'._("Manufacturer").'</span><a href="'.$globalURL.'/manufacturer/'.strtolower(str_replace(" ", "-", $spotter_array[0]['aircraft_manufacturer'])).'">'.$spotter_array[0]['aircraft_manufacturer'].'</a></div>';
		print '</div>';
	} else {
		print '<div class="alert alert-warning">'._("This special aircraft profile shows all flights in where the aircraft type is unknown.").'</div>';
	}
	include('aircraft-sub-menu.php');
	print '<div class="column">';
	print '<h2>'._("Most Common Airlines").'</h2>';
	print '<p>'.sprintf(_("The statistic below shows the most common airlines of flights from <strong>%s (%s)</strong>."),$spotter_array[0]['aircraft_name'],$spotter_array[0]['aircraft_type']).'</p>';

	$airline_array = $Spotter->countAllAirlinesByAircraft($aircraft_type);

	if (!empty($airline_array))
	{
		print '<div class="table-responsive">';
		print '<table class="common-airline">';
		print '<thead>';
		print '<th></th>';
		print '<th></th>';
		print '<th>'._("Airline").'</th>';
		print '<th>'._("Country").'</th>';
		print '<th>'._("# of times").'</th>';
		print '<th></th>';
		print '</thead>';
		print '<tbody>';
		$i = 1;
		foreach($airline_array as $airline_item)
		{
			print '<tr>';
			print '<td><strong>'.$i.'</strong></td>';
			print '<td class="logo">';
			print '<a href="'.$globalURL.'/airline/'.$airline_item['airline_icao'].'"><img src="';
			if (@getimagesize($globalURL.'/images/airlines/'.$airline_item['airline_icao'].'.png'))
			{
				print $globalURL.'/images/airlines/'.$airline_item['airline_icao'].'.png';
			} else {
				print $globalURL.'/images/airlines/placeholder.png';
			}
			print '" /></a>';
			print '</td>';
			print '<td>';
			print '<a href="'.$globalURL.'/airline/'.$airline_item['airline_icao'].'">'.$airline_item['airline_name'].' ('.$airline_item['airline_icao'].')</a>';
			print '</td>';
			print '<td>';
			print '<a href="'.$globalURL.'/country/'.strtolower(str_replace(" ", "-", $airline_item['airline_country'])).'">'.$airline_item['airline_country'].'</a>';
			print '</td>';
			print '<td>';
			print $airline_item['airline_count'];
			print '</td>';
			print '<td><a href="'.$globalURL.'/search?airline='.$airline_item['airline_icao'].'&aircraft='.$aircraft_type.'">Search flights</a></td>';
			print '</tr>';
			$i++;
		}
		print '<tbody>';
		print '</table>';
		print '</div>';
	}
	print '</div>';
} else {
	$title = "Aircraft Type";
	require_once('header.php');
	print '<h1>'._("Error").'</h1>';
	print '<p>'._("Sorry, the aircraft type does not exist in this database. :(").'</p>';  
}

require_once('footer.php');
?>