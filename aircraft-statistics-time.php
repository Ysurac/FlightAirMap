<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');

$spotter_array = Spotter::getSpotterDataByAircraft($_GET['aircraft_type'],"0,1","");


if (!empty($spotter_array))
{
	$title = 'Most Common Time of Day from '.$spotter_array[0]['aircraft_name'].' ('.$spotter_array[0]['aircraft_type'].')';
	require('header.php');
	print '<div class="select-item">';
	print '<form action="'.$globalURL.'/aircraft" method="post">';
	print '<select name="aircraft_type" class="selectpicker" data-live-search="true">';
	print '<option></option>';
	$aircraft_types = Spotter::getAllAircraftTypes();
	foreach($aircraft_types as $aircraft_type)
	{
		if($_GET['aircraft_type'] == $aircraft_type['aircraft_icao'])
		{
			print '<option value="'.$aircraft_type['aircraft_icao'].'" selected="selected">'.$aircraft_type['aircraft_name'].' ('.$aircraft_type['aircraft_icao'].')</option>';
		} else {
			print '<option value="'.$aircraft_type['aircraft_icao'].'">'.$aircraft_type['aircraft_name'].' ('.$aircraft_type['aircraft_icao'].')</option>';
		}
	}
	print '</select>';
	print '<button type="submit"><i class="fa fa-angle-double-right"></i></button>';
	print '</form>';
	print '</div>';

	if ($_GET['aircraft_type'] != "NA")
	{
		print '<div class="info column">';
		print '<h1>'.$spotter_array[0]['aircraft_name'].' ('.$spotter_array[0]['aircraft_type'].')</h1>';
		print '<div><span class="label">Name</span>'.$spotter_array[0]['aircraft_name'].'</div>';
		print '<div><span class="label">ICAO</span>'.$spotter_array[0]['aircraft_type'].'</div>'; 
		print '<div><span class="label">Manufacturer</span><a href="'.$globalURL.'/manufacturer/'.strtolower(str_replace(" ", "-", $spotter_array[0]['aircraft_manufacturer'])).'">'.$spotter_array[0]['aircraft_manufacturer'].'</a></div>';
		print '</div>';
	} else {
		print '<div class="alert alert-warning">This special aircraft profile shows all flights in where the aircraft type is unknown.</div>';
	}
	include('aircraft-sub-menu.php');
	print '<div class="column">';
	print '<h2>Most Common Time of Day</h2>';
	print '<p>The statistic below shows the most common time of day from <strong>'.$spotter_array[0]['aircraft_name'].' ('.$spotter_array[0]['aircraft_type'].')</strong>.</p>';

	$hour_array = Spotter::countAllHoursByAircraft($_GET['aircraft_type']);

	print '<div id="chartHour" class="chart" width="100%"></div>
      	<script> 
      		google.load("visualization", "1", {packages:["corechart"]});
          google.setOnLoadCallback(drawChart);
          function drawChart() {
            var data = google.visualization.arrayToDataTable([
            	["Hour", "# of Flights"], ';
	foreach($hour_array as $hour_item)
	{
		$hour_data .= '[ "'.date("ga", strtotime($hour_item['hour_name'].":00")).'",'.$hour_item['hour_count'].'],';
	}
	$hour_data = substr($hour_data, 0, -1);
	print $hour_data;
        print ']);
    
            var options = {
            	legend: {position: "none"},
            	chartArea: {"width": "80%", "height": "60%"},
            	vAxis: {title: "# of Flights"},
            	hAxis: {showTextEvery: 2},
            	height:300,
            	colors: ["#1a3151"]
            };
    
            var chart = new google.visualization.AreaChart(document.getElementById("chartHour"));
            chart.draw(data, options);
          }
          $(window).resize(function(){
    			  drawChart();
    			});
      </script>';
	print '</div>';
} else {
	$title = "Aircraft Type";
	require('header.php');
	print '<h1>Error</h1>';
	print '<p>Sorry, the aircraft type does not exist in this database. :(</p>'; 
}
require('footer.php');
?>