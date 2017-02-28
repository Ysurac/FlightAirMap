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
	$title = sprintf(_("Most Common Aircraft by Registration between %s (%s), %s - %s (%s), %s"),$spotter_array[0]['departure_airport_name'],$spotter_array[0]['departure_airport_icao'],$spotter_array[0]['departure_airport_country'],$spotter_array[0]['arrival_airport_name'],$spotter_array[0]['arrival_airport_icao'],$spotter_array[0]['arrival_airport_country']);
	require_once('header.php');
	print '<div class="info column">';
	print '<h1>'._("Flights between").' '.$spotter_array[0]['departure_airport_name'].' ('.$spotter_array[0]['departure_airport_icao'].'), '.$spotter_array[0]['departure_airport_country'].' - '.$spotter_array[0]['arrival_airport_name'].' ('.$spotter_array[0]['arrival_airport_icao'].'), '.$spotter_array[0]['arrival_airport_country'].'</h1>';
	print '<div><span class="label">'._("Coming From").'</span><a href="'.$globalURL.'/airport/'.$spotter_array[0]['departure_airport_icao'].'">'.$spotter_array[0]['departure_airport_name'].' ('.$spotter_array[0]['departure_airport_icao'].'), '.$spotter_array[0]['departure_airport_country'].'</a></div>';
	print '<div><span class="label">'._("Flying To").'</span><a href="'.$globalURL.'/airport/'.$spotter_array[0]['arrival_airport_icao'].'">'.$spotter_array[0]['arrival_airport_name'].' ('.$spotter_array[0]['arrival_airport_icao'].'), '.$spotter_array[0]['arrival_airport_country'].'</a></div>';
	print '</div>';

	include('route-sub-menu.php');
	print '<div class="column">';
	print '<h2>'._("Most Common Aircraft by Registration").'</h2>';
	print '<p>'.sprintf(_("The statistic below shows the most common aircraft by registration of flights between <strong>%s (%s), %s</strong> and <strong>%s (%s), %s</strong>."),$spotter_array[0]['departure_airport_name'],$spotter_array[0]['departure_airport_icao'],$spotter_array[0]['departure_airport_country'],$spotter_array[0]['arrival_airport_name'],$spotter_array[0]['arrival_airport_icao'],$spotter_array[0]['arrival_airport_country']).'</p>';
  
	$aircraft_array = $Spotter->countAllAircraftRegistrationByRoute($departure_airport, $arrival_airport);
	if (!empty($aircraft_array))
	{
		print '<div class="table-responsive">';
		print '<table class="common-type table-striped">';
		print '<thead>';
		print '<th></th>';
		print '<th></th>';
		print '<th>'._("Registration").'</th>';
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
			if ($aircraft_item['image_thumbnail'] != "")
			{
				print '<td class="aircraft_thumbnail">';
				if (isset($aircraft_item['aircraft_type'])) {
					print '<a href="'.$globalURL.'/registration/'.$aircraft_item['registration'].'"><img src="'.$aircraft_item['image_thumbnail'].'" class="img-rounded" data-toggle="popover" title="'.$aircraft_item['registration'].' - '.$aircraft_item['aircraft_icao'].' - '.$aircraft_item['airline_name'].'" alt="'.$aircraft_item['registration'].' - '.$aircraft_item['aircraft_type'].' - '.$aircraft_item['airline_name'].'" data-content="'._("Registration:").' '.$aircraft_item['registration'].'<br />'._("Aircraft:").' '.$aircraft_item['aircraft_name'].' ('.$aircraft_item['aircraft_icao'].')<br />'._("Airline:").' '.$aircraft_item['airline_name'].'" data-html="true" width="100px" /></a>';
				} else {
					print '<a href="'.$globalURL.'/registration/'.$aircraft_item['registration'].'"><img src="'.$aircraft_item['image_thumbnail'].'" class="img-rounded" data-toggle="popover" title="'.$aircraft_item['registration'].' - '.$aircraft_item['aircraft_icao'].' - '.$aircraft_item['airline_name'].'" alt="'.$aircraft_item['registration'].' - '.$aircraft_item['airline_name'].'" data-content="'._("Registration:").' '.$aircraft_item['registration'].'<br />'._("Aircraft:").' '.$aircraft_item['aircraft_name'].' ('.$aircraft_item['aircraft_icao'].')<br />'._("Airline:").' '.$aircraft_item['airline_name'].'" data-html="true" width="100px" /></a>';
				}
				print '</td>';
			} else {
				print '<td class="aircraft_thumbnail">';
				if (isset($aircraft_item['aircraft_type'])) {
					print '<a href="'.$globalURL.'/registration/'.$aircraft_item['registration'].'"><img src="'.$globalURL.'/images/placeholder_thumb.png" class="img-rounded" data-toggle="popover" title="'.$aircraft_item['registration'].' - '.$aircraft_item['aircraft_icao'].' - '.$aircraft_item['airline_name'].'" alt="'.$aircraft_item['registration'].' - '.$aircraft_item['aircraft_type'].' - '.$aircraft_item['airline_name'].'" data-content="'._("Registration:").' '.$aircraft_item['registration'].'<br />'._("Aircraft:").' '.$aircraft_item['aircraft_name'].' ('.$aircraft_item['aircraft_icao'].')<br />'._("Airline:").' '.$aircraft_item['airline_name'].'" data-html="true" width="100px" /></a>';
				} else {
					print '<a href="'.$globalURL.'/registration/'.$aircraft_item['registration'].'"><img src="'.$globalURL.'/images/placeholder_thumb.png" class="img-rounded" data-toggle="popover" title="'.$aircraft_item['registration'].' - '.$aircraft_item['aircraft_icao'].' - '.$aircraft_item['airline_name'].'" alt="'.$aircraft_item['registration'].' - '.$aircraft_item['airline_name'].'" data-content="'._("Registration:").' '.$aircraft_item['registration'].'<br />'._("Aircraft:").' '.$aircraft_item['aircraft_name'].' ('.$aircraft_item['aircraft_icao'].')<br />'._("Airline:").' '.$aircraft_item['airline_name'].'" data-html="true" width="100px" /></a>';
				}
				print '</td>';
			}
			print '<td>';
			print '<a href="'.$globalURL.'/registration/'.$aircraft_item['registration'].'">'.$aircraft_item['registration'].'</a>';
			print '</td>';
			print '<td>';
			print '<a href="'.$globalURL.'/aircraft/'.$aircraft_item['aircraft_icao'].'">'.$aircraft_item['aircraft_name'].' ('.$aircraft_item['aircraft_icao'].')</a>';
			print '</td>';
			print '<td>';
			print $aircraft_item['registration_count'];
			print '</td>';
			print '<td><a href="'.$globalURL.'/search?registration='.$aircraft_item['registration'].'&departure_airport_route='.$departure_airport.'&arrival_airport_route='.$arrival_airport.'">'._("Search flights").'</a></td>';
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