<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
require_once('require/class.SpotterArchive.php');
require_once('require/class.Language.php');
if (!isset($_GET['owner'])) {
        header('Location: '.$globalURL.'/owner');
        die();
}
$Spotter = new Spotter();
$SpotterArchive = new SpotterArchive();
$sort = filter_input(INPUT_GET,'sort',FILTER_SANITIZE_STRING);
$owner = urldecode(filter_input(INPUT_GET,'owner',FILTER_SANITIZE_STRING));
$year = filter_input(INPUT_GET,'year',FILTER_SANITIZE_NUMBER_INT);
$month = filter_input(INPUT_GET,'month',FILTER_SANITIZE_NUMBER_INT);
$filter = array();
if ($year != '') $filter = array_merge($filter,array('year' => $year));
if ($month != '') $filter = array_merge($filter,array('month' => $month));
$archive = false;
$spotter_array = $Spotter->getSpotterDataByOwner($owner,"0,1", $sort,$filter);
if (empty($spotter_array) && isset($globalArchiveResults) && $globalArchiveResults) {
	$archive = true;
	$spotter_array = $SpotterArchive->getSpotterDataByOwner($owner,"0,1", $sort,$filter);
}
if (!empty($spotter_array))
{
	$title = sprintf(_("Most Common Routes of %s"),$spotter_array[0]['aircraft_owner']);
	require_once('header.php');
	print '<div class="info column">';
	print '<h1>'.$spotter_array[0]['aircraft_owner'].'</h1>';
//	print '<div><span class="label">'._("Ident").'</span>'.$spotter_array[0]['ident'].'</div>';
//	print '<div><span class="label">'._("Airline").'</span><a href="'.$globalURL.'/airline/'.$spotter_array[0]['airline_icao'].'">'.$spotter_array[0]['airline_name'].'</a></div>'; 
	print '</div>';

	include('owner-sub-menu.php');
	print '<div class="column">';
	print '<h2>'._("Most Common Routes").'</h2>';
	print '<p>'.sprintf(_("The statistic below shows the most common routes from flights owner by <strong>%s</strong>."),$spotter_array[0]['aircraft_owner']).'</p>';
	if ($archive === false) {
		$route_array = $Spotter->countAllRoutesByOwner($owner,$filter);
	} else {
		$route_array = $SpotterArchive->countAllRoutesByOwner($owner,$filter);
	}
	if (!empty($route_array))
	{
		print '<div class="table-responsive">';
		print '<table class="common-routes table-striped">';
		print '<thead>';
		print '<th></th>';
		print '<th>'._("Departure Airport").'</th>';
		print '<th>'._("Arrival Airport").'</th>';
		print '<th>'._("# of times").'</th>';
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
			print '<a href="'.$globalURL.'/search?owner='.$owner.'&departure_airport_route='.$route_item['airport_departure_icao'].'&arrival_airport_route='.$route_item['airport_arrival_icao'].'">Search Flights</a>';
			print '</td>';
			print '<td>';
			print '<a href="'.$globalURL.'/route/'.$route_item['airport_departure_icao'].'/'.$route_item['airport_arrival_icao'].'">'._("Route Profile").'</a>';
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
	$title = _("Owner");
	require_once('header.php');
	print '<h1>'._("Error").'</h1>';
	print '<p>'._("Sorry, this owner is not in the database. :(").'</p>';
}

require_once('footer.php');
?>