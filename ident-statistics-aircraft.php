<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
if (!isset($_GET['ident'])) {
        header('Location: '.$globalURL.'/ident');
        die();
}
$Spotter = new Spotter();
if (isset($_GET['sort'])) {
	$spotter_array = $Spotter->getSpotterDataByIdent($_GET['ident'],"0,1", $_GET['sort']);
} else {
	$spotter_array = $Spotter->getSpotterDataByIdent($_GET['ident'],"0,1", '');
}

if (!empty($spotter_array))
{
	$title = 'Most Common Aircraft of '.$spotter_array[0]['ident'];
	require_once('header.php');
	print '<div class="info column">';
	print '<h1>'.$spotter_array[0]['ident'].'</h1>';
	print '<div><span class="label">Ident</span>'.$spotter_array[0]['ident'].'</div>';
	print '<div><span class="label">Airline</span><a href="'.$globalURL.'/airline/'.$spotter_array[0]['airline_icao'].'">'.$spotter_array[0]['airline_name'].'</a></div>'; 
	print '</div>';

	include('ident-sub-menu.php');
	print '<div class="column">';
	print '<h2>Most Common Aircraft</h2>';
	print '<p>The statistic below shows the most common aircrafts of flights using the ident/callsign <strong>'.$spotter_array[0]['ident'].'</strong>.</p>';

	$aircraft_array = $Spotter->countAllAircraftTypesByIdent($_GET['ident']);

	if (!empty($aircraft_array))
	{
		print '<div class="table-responsive">';
		print '<table class="common-type table-striped">';
		print '<thead>';
		print '<th></th>';
		print '<th>Aircraft Type</th>';
		print '<th># of Times</th>';
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
			print '<td><a href="'.$globalURL.'/search?aircraft='.$aircraft_item['aircraft_icao'].'&callsign='.$_GET['ident'].'">Search flights</a></td>';
			print '</tr>';
			$i++;
		}
		print '<tbody>';
		print '</table>';
		print '</div>';
	}
	print '</div>';
} else {
	$title = "Ident";
	require_once('header.php');
	print '<h1>Error</h1>';
	print '<p>Sorry, this ident/callsign is not in the database. :(</p>';
}

require_once('footer.php');
?>