<?php
if ($_GET['departure_airport'] == "" || $_GET['arrival_airport'] == "")
{
	header('Location: /');
}

require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
require_once('require/class.Language.php');
$sort = filter_input(INPUT_GET,'sort',FILTER_SANITIZE_STRING);
$departure_airport = filter_input(INPUT_GET,'departure_airport',FILTER_SANITIZE_STRING);
$arrival_airport = filter_input(INPUT_GET,'arrival_airport',FILTER_SANITIZE_STRING);
$Spotter = new Spotter();
$spotter_array = $Spotter->getSpotterDataByRoute($departure_airport, $arrival_airport, "0,1", $sort);
  
if (!empty($spotter_array))
{
	$title = sprintf(_("Most Common Aircraft between %s (%s), %s - %s (%s), %s"),$spotter_array[0]['departure_airport_name'],$spotter_array[0]['departure_airport_icao'],$spotter_array[0]['departure_airport_country'],$spotter_array[0]['arrival_airport_name'],$spotter_array[0]['arrival_airport_icao'],$spotter_array[0]['arrival_airport_country']);
	require_once('header.php');
	print '<div class="info column">';
	print '<h1>'._("Flights between").' '.$spotter_array[0]['departure_airport_name'].' ('.$spotter_array[0]['departure_airport_icao'].'), '.$spotter_array[0]['departure_airport_country'].' - '.$spotter_array[0]['arrival_airport_name'].' ('.$spotter_array[0]['arrival_airport_icao'].'), '.$spotter_array[0]['arrival_airport_country'].'</h1>';
	print '<div><span class="label">'._("Coming From").'</span><a href="'.$globalURL.'/airport/'.$spotter_array[0]['departure_airport_icao'].'">'.$spotter_array[0]['departure_airport_name'].' ('.$spotter_array[0]['departure_airport_icao'].'), '.$spotter_array[0]['departure_airport_country'].'</a></div>';
	print '<div><span class="label">'._("Flying To").'</span><a href="'.$globalURL.'/airport/'.$spotter_array[0]['arrival_airport_icao'].'">'.$spotter_array[0]['arrival_airport_name'].' ('.$spotter_array[0]['arrival_airport_icao'].'), '.$spotter_array[0]['arrival_airport_country'].'</a></div>';
	print '</div>';

	include('route-sub-menu.php');
	print '<div class="column">';
	print '<h2>'._("Most Common Aircraft").'</h2>';
	print '<p>'.sprintf(_("The statistic below shows the most common aircraft of flights between <strong>%s (%s), %s</strong> and <strong>%s (%s), %s</strong>."),$spotter_array[0]['departure_airport_name'],$spotter_array[0]['departure_airport_icao'],$spotter_array[0]['departure_airport_country'],$spotter_array[0]['arrival_airport_name'],$spotter_array[0]['arrival_airport_icao'],$spotter_array[0]['arrival_airport_country']).'</p>';

	$aircraft_array = $Spotter->countAllAircraftTypesByRoute($departure_airport, $arrival_airport);
	if (!empty($aircraft_array))
	{
		print '<div class="table-responsive">';
		print '<table class="common-type table-striped">';
		print '<thead>';
		print '<th></th>';
		print '<th>'._("Aircraft Type").'</th>';
		print '<th>'._("# of times").'</th>';
		print '<th></th>';
		print '</thead>';
		print '<tbody>';
		$i = 1;
		foreach($aircraft_array as $aircraft_item)
		{
			print '<tr>';
			print '<td><strong>'.$i.'</strong></td>';
			print '<td>';
			print '<a href="'.$globalURL.'/aircraft/'.$aircraft_item['aircraft_icao'].'">'.$aircraft_item['aircraft_name'].' ('.$aircraft_item['aircraft_icao'].')</a>';
			print '</td>';
			print '<td>';
			print $aircraft_item['aircraft_icao_count'];
			print '</td>';
			print '<td><a href="'.$globalURL.'/search?aircraft='.$aircraft_item['aircraft_icao'].'&departure_airport_route='.$departure_airport.'&arrival_airport_route='.$arrival_airport.'">'._("Search flights").'</a></td>';
			print '</tr>';
			$i++;
		}
		print '<tbody>';
		print '</table>';
		print '</div>';
	}
	print '</div>';
} else {
	$title = _("Unknown Route");
	require_once('header.php');
	print '<h1>'._("Error").'</h1>';
	print '<p>'._("Sorry, this route does not exist in this database. :(").'</p>'; 
}

require_once('footer.php');
?>