<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
if (!isset($_GET['aircraft_type'])) {
        header('Location: '.$globalURL.'/aircraft');
        die();
}
$Spotter = new Spotter();
$spotter_array = $Spotter->getSpotterDataByAircraft($_GET['aircraft_type'],"0,1","");


if (!empty($spotter_array))
{
	$title = 'Most Common Routes from '.$spotter_array[0]['aircraft_name'].' ('.$spotter_array[0]['aircraft_type'].')';
	require_once('header.php');
	print '<div class="select-item">';
	print '<form action="'.$globalURL.'/aircraft" method="post">';
	print '<select name="aircraft_type" class="selectpicker" data-live-search="true">';
	print '<option></option>';
	$aircraft_types = $Spotter->getAllAircraftTypes();
	foreach($aircraft_types as $aircraft_type)
	{
		if($_GET['aircraft_type'] == $aircraft_type['aircraft_icao'])
		{
			print '<option value="'.$aircraft_type['aircraft_icao'].'" selected="selected">'.$aircraft_type['aircraft_name'].' ('.$aircraft_type['aircraft_icao'].')</option>';
		} else {
			print '<option value="'.$aircraft_type['aircraft_icao'].'">'.$aircraft_type['aircraft_name'].' ('.$aircraft_type['aircraft_icao'].')</option>';
		}
	}
	print '</select>';
	print '<button type="submit"><i class="fa fa-angle-double-right"></i></button>';
	print '</form>';
	print '</div>';

	if ($_GET['aircraft_type'] != "NA")
	{
		print '<div class="info column">';
		print '<h1>'.$spotter_array[0]['aircraft_name'].' ('.$spotter_array[0]['aircraft_type'].')</h1>';
		print '<div><span class="label">Name</span>'.$spotter_array[0]['aircraft_name'].'</div>';
		print '<div><span class="label">ICAO</span>'.$spotter_array[0]['aircraft_type'].'</div>'; 
		print '<div><span class="label">Manufacturer</span><a href="'.$globalURL.'/manufacturer/'.strtolower(str_replace(" ", "-", $spotter_array[0]['aircraft_manufacturer'])).'">'.$spotter_array[0]['aircraft_manufacturer'].'</a></div>';
		print '</div>';
	} else {
		print '<div class="alert alert-warning">This special aircraft profile shows all flights in where the aircraft type is unknown.</div>';
	}
	include('aircraft-sub-menu.php');
	print '<div class="column">';
	print '<h2>Most Common Routes</h2>';
	print '<p>The statistic below shows the most common routes from <strong>'.$spotter_array[0]['aircraft_name'].' ('.$spotter_array[0]['aircraft_type'].')</strong>.</p>';

	$route_array = $Spotter->countAllRoutesByAircraft($_GET['aircraft_type']);
	if (!empty($route_array))
	{
		print '<div class="table-responsive">';
		print '<table class="common-routes">';
		print '<thead>';
		print '<th></th>';
		print '<th>Departure Airport</th>';
		print '<th>Arrival Airport</th>';
		print '<th># of Times</th>';
		print '<th></th>';
		print '<th></th>';
		print '</thead>';
		print '<tbody>';
		$i = 1;
		foreach($route_array as $route_item)
		{
			print '<tr>';
			print '<td><strong>'.$i.'</strong></td>';
			print '<td>';
			print '<a href="'.$globalURL.'/airport/'.$route_item['airport_departure_icao'].'">'.$route_item['airport_departure_city'].', '.$route_item['airport_departure_country'].' ('.$route_item['airport_departure_icao'].')</a>';
			print '</td>';
			print '<td>';
			print '<a href="'.$globalURL.'/airport/'.$route_item['airport_arrival_icao'].'">'.$route_item['airport_arrival_city'].', '.$route_item['airport_arrival_country'].' ('.$route_item['airport_arrival_icao'].')</a>';
			print '</td>';
			print '<td>';
			print $route_item['route_count'];
			print '</td>';
			print '<td>';
			print '<a href="'.$globalURL.'/search?aircraft='.$_GET['aircraft_type'].'&departure_airport_route='.$route_item['airport_departure_icao'].'&arrival_airport_route='.$route_item['airport_arrival_icao'].'">Search Flights</a>';
			print '</td>';
			print '<td>';
			print '<a href="'.$globalURL.'/route/'.$route_item['airport_departure_icao'].'/'.$route_item['airport_arrival_icao'].'">Route Profile</a>';
			print '</td>';
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
	print '<h1>Error</h1>';
	print '<p>Sorry, the aircraft type does not exist in this database. :(</p>';  
}

require_once('footer.php');
?>