<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');
if (!isset($_GET['ident'])) {
        header('Location: '.$globalURL.'/ident');
        die();
}
$Spotter = new Spotter();
$ident = '';
$sort = '';
if (isset($_GET['ident'])) $ident = $_GET['ident'];
if (isset($_GET['sort'])) $sort = $_GET['sort'];
$spotter_array = $Spotter->getSpotterDataByIdent($ident,"0,1", $sort);

if (!empty($spotter_array))
{
	$title = 'Most Common Aircraft by Registration of '.$spotter_array[0]['ident'];
	require('header.php');
	print '<div class="info column">';
	print '<h1>'.$spotter_array[0]['ident'].'</h1>';
	print '<div><span class="label">Ident</span>'.$spotter_array[0]['ident'].'</div>';
	print '<div><span class="label">Airline</span><a href="'.$globalURL.'/airline/'.$spotter_array[0]['airline_icao'].'">'.$spotter_array[0]['airline_name'].'</a></div>'; 
	print '<div><span class="label">Flight History</span><a href="http://flightaware.com/live/flight/'.$spotter_array[0]['ident'].'" target="_blank">View the Flight History of this callsign</a></div>';       
	print '</div>';

	include('ident-sub-menu.php');
	print '<div class="column">';
	print '<h2>Most Common Aircraft by Registration</h2>';
	print '<p>The statistic below shows the most common aircraft by Registration of flights using the ident/callsign <strong>'.$spotter_array[0]['ident'].'</strong>.</p>';

	$aircraft_array = $Spotter->countAllAircraftRegistrationByIdent($_GET['ident']);
	
	if (!empty($aircraft_array))
	{
		print '<div class="table-responsive">';
		print '<table class="common-type table-striped">';
		print '<thead>';
		print '<th></th>';
		print '<th></th>';
		print '<th>Registration</th>';
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
			if ($aircraft_item['image_thumbnail'] != "")
			{
				print '<td class="aircraft_thumbnail">';
				print '<a href="'.$globalURL.'/registration/'.$aircraft_item['registration'].'"><img src="'.$aircraft_item['image_thumbnail'].'" class="img-rounded" data-toggle="popover" title="'.$aircraft_item['registration'].' - '.$aircraft_item['aircraft_icao'].' - '.$aircraft_item['airline_name'].'" alt="'.$aircraft_item['registration'].' - '.$aircraft_item['aircraft_type'].' - '.$aircraft_item['airline_name'].'" data-content="Registration: '.$aircraft_item['registration'].'<br />Aircraft: '.$aircraft_item['aircraft_name'].' ('.$aircraft_item['aircraft_icao'].')<br />Airline: '.$aircraft_item['airline_name'].'" data-html="true" width="100px" /></a>';
				print '</td>';
			} else {
				print '<td class="aircraft_thumbnail">';
				print '<a href="'.$globalURL.'/registration/'.$aircraft_item['registration'].'"><img src="'.$globalURL.'/images/placeholder_thumb.png" class="img-rounded" data-toggle="popover" title="'.$aircraft_item['registration'].' - '.$aircraft_item['aircraft_icao'].' - '.$aircraft_item['airline_name'].'" alt="'.$aircraft_item['registration'].' - '.$aircraft_item['aircraft_type'].' - '.$aircraft_item['airline_name'].'" data-content="Registration: '.$aircraft_item['registration'].'<br />Aircraft: '.$aircraft_item['aircraft_name'].' ('.$aircraft_item['aircraft_icao'].')<br />Airline: '.$aircraft_item['airline_name'].'" data-html="true" width="100px" /></a>';
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
			print '<td><a href="'.$globalURL.'/search?registration='.$aircraft_item['registration'].'&callsign='.$_GET['ident'].'">Search flights</a></td>';
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
	require('header.php');
	print '<h1>Error</h1>';
	print '<p>Sorry, this ident/callsign is not in the database. :(</p>';
}

require('footer.php');
?>