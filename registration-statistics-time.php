<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
require_once('require/class.Language.php');
$Spotter = new Spotter();
$sort = filter_input(INPUT_GET,'sort',FILTER_SANITIZE_STRING);
$registration = filter_input(INPUT_GET,'registration',FILTER_SANITIZE_STRING);
$spotter_array = $Spotter->getSpotterDataByRegistration($registration, "0,1", $sort);
$aircraft_array = $Spotter->getAircraftInfoByRegistration($registration);

if (!empty($spotter_array))
{
	$title = sprintf(_("Most Common Time of Day of aircraft with registration %s"),$registration);
	require_once('header.php');
  
	print '<div class="info column">';
	print '<h1>'.$registration.' - '.$aircraft_array[0]['aircraft_name'].' ('.$aircraft_array[0]['aircraft_icao'].')</h1>';
	print '<div><span class="label">'._("Name").'</span><a href="'.$globalURL.'/aircraft/'.$aircraft_array[0]['aircraft_icao'].'">'.$aircraft_array[0]['aircraft_name'].'</a></div>';
	print '<div><span class="label">'._("ICAO").'</span><a href="'.$globalURL.'/aircraft/'.$aircraft_array[0]['aircraft_icao'].'">'.$aircraft_array[0]['aircraft_icao'].'</a></div>'; 
	print '<div><span class="label">'._("Manufacturer").'</span><a href="'.$globalURL.'/manufacturer/'.strtolower(str_replace(" ", "-", $aircraft_array[0]['aircraft_manufacturer'])).'">'.$aircraft_array[0]['aircraft_manufacturer'].'</a></div>';
	print '</div>';

	include('registration-sub-menu.php');
	print '<div class="column">';
	print '<h2>'._("Most Common Time of Day").'</h2>';
	print '<p>'.sprintf(_("The statistic below shows the most common time of day from aircraft with registration <strong>%s</strong>."),$registration).'</p>';

	$hour_array = $Spotter->countAllHoursByRegistration($registration);
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
	$title = _("Registration");
	require_once('header.php');
	print '<h1>'._("Error").'</h1>';
	print '<p>'._("Sorry, this registration does not exist in this database. :(").'</p>';  
}

require_once('footer.php');
?>