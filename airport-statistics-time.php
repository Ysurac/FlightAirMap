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
//$spotter_array = $Spotter->getSpotterDataByAirport($airport,"0,1","");
$airport_array = $Spotter->getAllAirportInfo($airport);

if (!empty($airport_array))
{
	$title = sprintf(_("Most Common Time of Day to/from %s, %s (%s)"),$airport_array[0]['city'],$airport_array[0]['name'],$airport_array[0]['icao']);

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
		if($airport == $airport_name['airport_icao'])
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
	print '<h2>'._("Most Common Time of Day").'</h2>';
	print '<p>'.sprintf(_("The statistic below shows the most common time of day to/from <strong>%s, %s (%s)</strong>."),$airport_array[0]['city'],$airport_array[0]['name'],$airport_array[0]['icao']).'</p>';

	$hour_array = $Spotter->countAllHoursByAirport($airport);
	print '<link href="'.$globalURL.'/css/c3.min.css" rel="stylesheet" type="text/css">';
	print '<script type="text/javascript" src="'.$globalURL.'/js/d3.min.js"></script>';
	print '<script type="text/javascript" src="'.$globalURL.'/js/c3.min.js"></script>';
	print '<div id="chartHour" class="chart" width="100%"></div><script>';
	$hour_data = '';
	$hour_cnt = '';
	$last = 0;
	foreach($hour_array as $hour_item)
	{
		while($last != $hour_item['hour_name']) {
			$hour_data .= '"'.$last.':00",';
			$hour_cnt .= '0,';
			$last++;
		}
		$last++;
		$hour_data .= '"'.$hour_item['hour_name'].':00",';
		$hour_cnt .= $hour_item['hour_count'].',';
	}
	$hour_data = "[".substr($hour_data, 0, -1)."]";
	$hour_cnt = "['flights',".substr($hour_cnt,0,-1)."]";
	print 'c3.generate({
	    bindto: "#chartHour",
	    data: {
		columns: ['.$hour_cnt.'], types: { flights: "area"}, colors: { flights: "#1a3151"}
	    },
	    axis: {
		x: { type: "category", categories: '.$hour_data.'},
		y: { label: "# of Flights"}
	    },
	    legend: { show: false }
	});';
	print '</script>';
	if (!empty($hour_array))
	{
		print '<div class="table-responsive">';
		print '<table class="common-hour table-striped">';
		print '<thead>';
		print '<th>'._("Hour").'</th>';
		print '<th>'._("Number").'</th>';
		print '</thead>';
		print '<tbody>';
		$i = 1;
		foreach($hour_array as $hour_item)
		{
			print '<tr>';
			print '<td>'.$hour_item['hour_name'].':00</td>';
			print '<td>'.$hour_item['hour_count'].'</td>';
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