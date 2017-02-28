<?php
if ($_GET['departure_airport'] == "" || $_GET['arrival_airport'] == "")
{
	header('Location: /');
}

require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
require_once('require/class.Language.php');
$Spotter = new Spotter();
$sort = filter_input(INPUT_GET,'sort',FILTER_SANITIZE_STRING);
$departure_airport = filter_input(INPUT_GET,'departure_airport',FILTER_SANITIZE_STRING);
$arrival_airport = filter_input(INPUT_GET,'arrival_airport',FILTER_SANITIZE_STRING);
if (isset($_GET['departure_airport']) && isset($_GET['arrival_airport'])) {
	$spotter_array = $Spotter->getSpotterDataByRoute($departure_airport, $arrival_airport, "0,1", $sort);
} else $spotter_array = array();
  
if (!empty($spotter_array))
{
	$title = sprintf(_("Most Common Airlines between %s (%s), %s - %s (%s), %s"),$spotter_array[0]['departure_airport_name'],$spotter_array[0]['departure_airport_icao'],$spotter_array[0]['departure_airport_country'],$spotter_array[0]['arrival_airport_name'],$spotter_array[0]['arrival_airport_icao'],$spotter_array[0]['arrival_airport_country']);
	require_once('header.php');
	print '<div class="info column">';
	print '<h1>'._("Flights between").' '.$spotter_array[0]['departure_airport_name'].' ('.$spotter_array[0]['departure_airport_icao'].'), '.$spotter_array[0]['departure_airport_country'].' - '.$spotter_array[0]['arrival_airport_name'].' ('.$spotter_array[0]['arrival_airport_icao'].'), '.$spotter_array[0]['arrival_airport_country'].'</h1>';
	print '<div><span class="label">'._("Coming From").'</span><a href="'.$globalURL.'/airport/'.$spotter_array[0]['departure_airport_icao'].'">'.$spotter_array[0]['departure_airport_name'].' ('.$spotter_array[0]['departure_airport_icao'].'), '.$spotter_array[0]['departure_airport_country'].'</a></div>';
	print '<div><span class="label">'._("Flying To").'</span><a href="'.$globalURL.'/airport/'.$spotter_array[0]['arrival_airport_icao'].'">'.$spotter_array[0]['arrival_airport_name'].' ('.$spotter_array[0]['arrival_airport_icao'].'), '.$spotter_array[0]['arrival_airport_country'].'</a></div>';
	print '</div>';

	include('route-sub-menu.php');
	print '<div class="column">';
	print '<h2>'._("Most Common Airlines").'</h2>';
	print '<p>'.sprintf(_("The statistic below shows the most common airlines of flights between <strong>%s (%s), %s</strong> and <strong>%s (%s), %s</strong>."),$spotter_array[0]['departure_airport_name'],$spotter_array[0]['departure_airport_icao'],$spotter_array[0]['departure_airport_country'],$spotter_array[0]['arrival_airport_name'],$spotter_array[0]['arrival_airport_icao'],$spotter_array[0]['arrival_airport_country']).'</p>';
	$airline_array = $Spotter->countAllAirlinesByRoute($departure_airport, $arrival_airport);
	if (!empty($airline_array))
	{
		print '<div class="table-responsive">';
		print '<table class="common-airline table-striped">';
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
			if ($globalIVAO && @getimagesize($globalURL.'/images/airlines/'.$airline_item['airline_icao'].'.gif'))
			{
				print $globalURL.'/images/airlines/'.$airline_item['airline_icao'].'.gif';
			} elseif (@getimagesize($globalURL.'/images/airlines/'.$airline_item['airline_icao'].'.png'))
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
			print '<td><a href="'.$globalURL.'/search?airline='.$airline_item['airline_icao'].'&departure_airport_route='.$departure_airport.'&arrival_airport_route='.$arrival_airport.'">'._("Search flights").'</a></td>';
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