<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
require_once('require/class.Stats.php');
require_once('require/class.Language.php');
if (!isset($_GET['airport'])) {
        header('Location: '.$globalURL.'/airport');
        die();
}
$airport = filter_input(INPUT_GET,'airport',FILTER_SANITIZE_STRING);
$Spotter = new Spotter();
$airport_array = $Spotter->getAllAirportInfo($airport);

if (!empty($airport_array))
{
	//$spotter_array = $Spotter->getSpotterDataByAirport($airport,"0,1","");
	$title = sprintf(_("Most Common Aircraft Manufacturer to/from %s, %s (%s)"),$airport_array[0]['city'],$airport_array[0]['name'],$airport_array[0]['icao']);

	require_once('header.php');
	print '<div class="select-item">';
	print '<form action="'.$globalURL.'/airport" method="post">';
	print '<select name="airport" class="selectpicker" data-live-search="true">';
	print '<option></option>';
	$Stats = new Stats();
	$airport_names = $Stats->getAllAirportNames();
	if (empty($airport_names)) $airport_names = $Spotter->getAllAirportNames();
	ksort($airport_names);
	foreach($airport_names as $airport_name)
	{
		if($_GET['airport'] == $airport_name['airport_icao'])
		{
			print '<option value="'.$airport_name['airport_icao'].'" selected="selected">'.$airport_name['airport_city'].', '.$airport_name['airport_name'].', '.$airport_name['airport_country'].' ('.$airport_name['airport_icao'].')</option>';
		} else {
			print '<option value="'.$airport_name['airport_icao'].'">'.$airport_name['airport_city'].', '.$airport_name['airport_name'].', '.$airport_name['airport_country'].' ('.$airport_name['airport_icao'].')</option>';
		}
	}
	print '</select>';
	print '<button type="submit"><i class="fa fa-angle-double-right"></i></button>';
	print '</form>';
	print '</div>';
	print '<br />';
	
	if ($airport != "NA")
	{
		print '<div class="info column">';
		print '<h1>'.$airport_array[0]['city'].', '.$airport_array[0]['name'].' ('.$airport_array[0]['icao'].')</h1>';
		print '<div><span class="label">'._("Name").'</span>'.$airport_array[0]['name'].'</div>';
		print '<div><span class="label">'._("City").'</span>'.$airport_array[0]['city'].'</div>';
		print '<div><span class="label">'._("Country").'</span>'.$airport_array[0]['country'].'</div>';
		print '<div><span class="label">'._("ICAO").'</span>'.$airport_array[0]['icao'].'</div>';
		print '<div><span class="label">'._("IATA").'</span>'.$airport_array[0]['iata'].'</div>';
		print '<div><span class="label">'._("Altitude").'</span>'.$airport_array[0]['altitude'].'</div>';
		print '<div><span class="label">'._("Coordinates").'</span><a href="http://maps.google.ca/maps?z=10&t=k&q='.$airport_array[0]['latitude'].','.$airport_array[0]['longitude'].'" target="_blank">Google Map<i class="fa fa-angle-double-right"></i></a></div>';
		print '</div>';
	} else {
		print '<div class="alert alert-warning">'._("This special airport profile shows all flights that do <u>not</u> have a departure and/or arrival airport associated with them.").'</div>';
	}

	include('airport-sub-menu.php');
	print '<div class="column">';
	print '<h2>'._("Most Common Aircraft Manufacturer").'</h2>';
	print '<p>'.sprintf(_("The statistic below shows the most common Aircraft Manufacturer of flights to/from <strong>%s, %s (%s)</strong>."),$airport_array[0]['city'],$airport_array[0]['name'],$airport_array[0]['icao']).'</p>';

	$manufacturers_array = $Spotter->countAllAircraftManufacturerByAirport($airport);
	if (!empty($manufacturers_array))
	{
		print '<div class="table-responsive">';
		print '<table class="common-manufacturer table-striped">';
		print '<thead>';
		print '<th></th>';
		print '<th>'._("Aircraft Manufacturer").'</th>';
		print '<th>'._("# of times").'</th>';
		print '<th></th>';
		print '</thead>';
		print '<tbody>';
		$i = 1;
		foreach($manufacturers_array as $manufacturer_item)
		{
			print '<tr>';
			print '<td><strong>'.$i.'</strong></td>';
			print '<td>';
			print '<a href="'.$globalURL.'/manufacturer/'.strtolower(str_replace(" ", "-", $manufacturer_item['aircraft_manufacturer'])).'">'.$manufacturer_item['aircraft_manufacturer'].'</a>';
			print '</td>';
			print '<td>';
			print $manufacturer_item['aircraft_manufacturer_count'];
			print '</td>';
			print '<td><a href="'.$globalURL.'/search?manufacturer='.strtolower(str_replace(" ", "-", $manufacturer_item['aircraft_manufacturer'])).'&airport='.$airport.'">'._("Search flights").'</a></td>';
			print '</tr>';
			$i++;
		}
		print '<tbody>';
		print '</table>';
		print '</div>';
	}
	print '</div>';
} else {
	$title = _("Airport");
	require_once('header.php');
	print '<h1>'._("Error").'</h1>';
	print '<p>'._("Sorry, the airport does not exist in this database. :(").'</p>';
}

require_once('footer.php');
?>