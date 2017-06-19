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
	$title = sprintf(_("Most Common Time of Day between %s (%s), %s - %s (%s), %s"),$spotter_array[0]['departure_airport_name'],$spotter_array[0]['departure_airport_icao'],$spotter_array[0]['departure_airport_country'],$spotter_array[0]['arrival_airport_name'],$spotter_array[0]['arrival_airport_icao'],$spotter_array[0]['arrival_airport_country']);
	require_once('header.php');
	print '<div class="info column">';
	print '<h1>'._("Flights between").' '.$spotter_array[0]['departure_airport_name'].' ('.$spotter_array[0]['departure_airport_icao'].'), '.$spotter_array[0]['departure_airport_country'].' - '.$spotter_array[0]['arrival_airport_name'].' ('.$spotter_array[0]['arrival_airport_icao'].'), '.$spotter_array[0]['arrival_airport_country'].'</h1>';
	print '<div><span class="label">'._("Coming From").'</span><a href="'.$globalURL.'/airport/'.$spotter_array[0]['departure_airport_icao'].'">'.$spotter_array[0]['departure_airport_name'].' ('.$spotter_array[0]['departure_airport_icao'].'), '.$spotter_array[0]['departure_airport_country'].'</a></div>';
	print '<div><span class="label">'._("Flying To").'</span><a href="'.$globalURL.'/airport/'.$spotter_array[0]['arrival_airport_icao'].'">'.$spotter_array[0]['arrival_airport_name'].' ('.$spotter_array[0]['arrival_airport_icao'].'), '.$spotter_array[0]['arrival_airport_country'].'</a></div>';
	print '</div>';

	include('route-sub-menu.php');
	print '<div class="column">';
	print '<h2>'._("Most Common Time of Day").'</h2>';
	print '<p>'.sprintf(_("The statistic below shows the most common time of day of flights between <strong>%s (%s), %s</strong> and <strong>%s (%s), %s</strong>."),$spotter_array[0]['departure_airport_name'],$spotter_array[0]['departure_airport_icao'],$spotter_array[0]['departure_airport_country'],$spotter_array[0]['arrival_airport_name'],$spotter_array[0]['arrival_airport_icao'],$spotter_array[0]['arrival_airport_country']).'</p>';

	$hour_array = $Spotter->countAllHoursByRoute($departure_airport, $arrival_airport);
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
	$hour_data = "['x',".substr($hour_data, 0, -1)."]";
	$hour_cnt = "['flights',".substr($hour_cnt,0,-1)."]";
	print 'c3.generate({
	    bindto: "#chartHour",
	    data: {
		x : "x",
		xFormat: "%H:%M",
		columns: ['.$hour_cnt.','.$hour_data.'], types: { flights: "area"}, colors: { flights: "#1a3151"}
	    },
	    axis: { 
		x: { type: "timeseries", tick: { format: "%H:%M" }},
		y: { label: "# of Flights",tick: { format: d3.format("d") }}
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
	$title = _("Unknown Route");
	require_once('header.php');
	print '<h1>'._("Error").'</h1>';
	print '<p>'._("Sorry, this route does not exist in this database. :(").'</p>'; 
}

require_once('footer.php');
?>