<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
require_once('require/class.Stats.php');
require_once('require/class.Language.php');
if (!isset($_GET['aircraft_type'])) {
        header('Location: '.$globalURL.'/aircraft');
        die();
}

$aircraft_type = filter_input(INPUT_GET,'aircraft_type',FILTER_SANITIZE_STRING);

$Spotter = new Spotter();
$spotter_array = $Spotter->getSpotterDataByAircraft($aircraft_type,"0,1","");


if (!empty($spotter_array))
{
	$title = sprintf(_("Most Common Time of Day from %s (%s)"),$spotter_array[0]['aircraft_name'],$spotter_array[0]['aircraft_type']);
	require_once('header.php');
	print '<div class="select-item">';
	print '<form action="'.$globalURL.'/aircraft" method="get">';
	print '<select name="aircraft_type" class="selectpicker" data-live-search="true">';
	print '<option></option>';
	$Stats = new Stats();
	$aircraft_types = $Stats->getAllAircraftTypes();
	if (empty($aircraft_types)) $aircraft_types = $Spotter->getAllAircraftTypes();
	foreach($aircraft_types as $aircrafttype)
	{
		if($aircraft_type == $aircrafttype['aircraft_icao'])
		{
			print '<option value="'.$aircrafttype['aircraft_icao'].'" selected="selected">'.$aircrafttype['aircraft_name'].' ('.$aircrafttype['aircraft_icao'].')</option>';
		} else {
			print '<option value="'.$aircrafttype['aircraft_icao'].'">'.$aircrafttype['aircraft_name'].' ('.$aircrafttype['aircraft_icao'].')</option>';
		}
	}
	print '</select>';
	print '<button type="submit"><i class="fa fa-angle-double-right"></i></button>';
	print '</form>';
	print '</div>';
	print '<br />';

	if ($aircraft_type != "NA")
	{
		print '<div class="info column">';
		print '<h1>'.$spotter_array[0]['aircraft_name'].' ('.$spotter_array[0]['aircraft_type'].')</h1>';
		print '<div><span class="label">Name</span>'.$spotter_array[0]['aircraft_name'].'</div>';
		print '<div><span class="label">ICAO</span>'.$spotter_array[0]['aircraft_type'].'</div>'; 
		print '<div><span class="label">Manufacturer</span><a href="'.$globalURL.'/manufacturer/'.strtolower(str_replace(" ", "-", $spotter_array[0]['aircraft_manufacturer'])).'">'.$spotter_array[0]['aircraft_manufacturer'].'</a></div>';
		print '</div>';
	} else {
		print '<div class="alert alert-warning">'._("This special aircraft profile shows all flights in where the aircraft type is unknown.").'</div>';
	}
	include('aircraft-sub-menu.php');
	print '<div class="column">';
	print '<h2>'._("Most Common Time of Day").'</h2>';
	print '<p>'.sprintf(_("The statistic below shows the most common time of day from <strong>%s</strong>."),$spotter_array[0]['aircraft_name'],$spotter_array[0]['aircraft_type']).'</p>';

	$hour_array = $Spotter->countAllHoursByAircraft($aircraft_type);
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
	$title = _("Aircraft Type");
	require_once('header.php');
	print '<h1>'._("Error").'</h1>';
	print '<p>'._("Sorry, the aircraft type does not exist in this database. :(").'</p>'; 
}
require_once('footer.php');
?>