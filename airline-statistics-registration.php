<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');

$spotter_array = Spotter::getSpotterDataByAirline($_GET['airline'],"0,1","");

if (!empty($spotter_array))
{
	$title = 'Most Common Aircraft by Registration from '.$spotter_array[0]['airline_name'].' ('.$spotter_array[0]['airline_icao'].')';
	require('header.php');

	print '<div class="select-item">';
	print '<form action="'.$globalURL.'/airline" method="post">';
	print '<select name="airline" class="selectpicker" data-live-search="true">';
	print '<option></option>';
	$airline_names = Spotter::getAllAirlineNames();
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

	if ($_GET['airline'] != "NA")
	{
		print '<div class="info column">';
			print '<h1>'.$spotter_array[0]['airline_name'].' ('.$spotter_array[0]['airline_icao'].')</h1>';
			if (@getimagesize($globalURL.'/images/airlines/'.$spotter_array[0]['airline_icao'].'.png'))
			{
				print '<img src="'.$globalURL.'/images/airlines/'.$spotter_array[0]['airline_icao'].'.png" alt="'.$spotter_array[0]['airline_name'].' ('.$spotter_array[0]['airline_icao'].')" title="'.$spotter_array[0]['airline_name'].' ('.$spotter_array[0]['airline_icao'].')" class="logo" />';
			}
			print '<div><span class="label">Name</span>'.$spotter_array[0]['airline_name'].'</div>';
			print '<div><span class="label">Country</span>'.$spotter_array[0]['airline_country'].'</div>';
			print '<div><span class="label">ICAO</span>'.$spotter_array[0]['airline_icao'].'</div>';
			print '<div><span class="label">IATA</span>'.$spotter_array[0]['airline_iata'].'</div>';
			print '<div><span class="label">Callsign</span>'.$spotter_array[0]['airline_callsign'].'</div>'; 
			print '<div><span class="label">Type</span>'.ucwords($spotter_array[0]['airline_type']).'</div>';        
		print '</div>';
	} else {
		print '<div class="alert alert-warning">This special airline profile shows all flights that do <u>not</u> have a airline associated with them.</div>';
	}

	include('airline-sub-menu.php');
	print '<div class="column">';
	print '<h2>Most Common Aircraft by Registration</h2>';
	print '<p>The statistic below shows the most common aircraft by their registration of flights from <strong>'.$spotter_array[0]['airline_name'].'</strong>.</p>';

	$aircraft_array = Spotter::countAllAircraftRegistrationByAirline($_GET['airline']);
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
			print '<td><a href="'.$globalURL.'/search?registration='.$aircraft_item['registration'].'&airline='.$_GET['airline'].'">Search flights</a></td>';
			print '</tr>';
			$i++;
		}
		print '<tbody>';
		print '</table>';
		print '</div>';
	}
	print '</div>';
} else {
	$title = "Airline Statistic";
	require('header.php');
	print '<h1>Error</h1>';
	print '<p>Sorry, the airline does not exist in this database. :(</p>'; 
}

require('footer.php');
?>