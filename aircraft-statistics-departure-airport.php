<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
if (!isset($_GET['aircraft_type'])) {
        header('Location: '.$globalURL.'/aircraft');
        die();
}
$aircraft_type = filter_input(INPUT_GET,'aircraft_type',FILTER_SANITIZE_STRING);
$Spotter = new Spotter();
$spotter_array = $Spotter->getSpotterDataByAircraft($aircraft_type,"0,1","");


if (!empty($spotter_array))
{
	$title = _("Most Common Departure Airports for").' '.$spotter_array[0]['aircraft_name'].' ('.$spotter_array[0]['aircraft_type'].')';
	require_once('header.php');
	print '<div class="select-item">';
	print '<form action="'.$globalURL.'/aircraft" method="post">';
	print '<select name="aircraft_type" class="selectpicker" data-live-search="true">';
	print '<option></option>';
	$aircraft_types = $Spotter->getAllAircraftTypes();
	foreach($aircraft_types as $aircraft_type)
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
	print '<h2>'._("Most Common Departure Airports").'</h2>';
	print '<p>'._("The statistic below shows all departure airports of flights from").' <strong>'.$spotter_array[0]['aircraft_name'].' ('.$spotter_array[0]['aircraft_type'].')</strong>.</p>';
	$airport_airport_array = $Spotter->countAllDepartureAirportsByAircraft($aircraft_type);
    	print '
    	<script type="text/javascript" src="https://www.google.com/jsapi"></script>
    	<script>
    	google.load("visualization", "1", {packages:["geochart"]});
    	google.setOnLoadCallback(drawCharts);
    	$(window).resize(function(){
    		drawCharts();
    	});
    	function drawCharts() {
    
        var data = google.visualization.arrayToDataTable([ 
        	["'._("Airport").'", "'._("# of Times").'"],';
        $airport_data = '';
	foreach($airport_airport_array as $airport_item)
	{
		$name = $airport_item['airport_departure_city'].', '.$airport_item['airport_departure_country'].' ('.$airport_item['airport_departure_icao'].')';
		$name = str_replace("'", "", $name);
		$name = str_replace('"', "", $name);
		$airport_data .= '[ "'.$name.'",'.$airport_item['airport_departure_icao_count'].'],';
	}
	$airport_data = substr($airport_data, 0, -1);
	print $airport_data;
	?>
        ]);
    
        var options = {
        	legend: {position: "none"},
        	chartArea: {"width": "80%", "height": "60%"},
        	height:500,
        	displayMode: "markers",
        	colors: ["#8BA9D0","#1a3151"]
        };
        
        var chart = new google.visualization.GeoChart(document.getElementById("chartAirport"));
        chart.draw(data, options);
      }
    	</script>
      
    	<div id="chartAirport" class="chart" width="100%"></div>

    	<?php
	print '<div class="table-responsive">';
	print '<table class="common-airport">';
	print '<thead>';
	print '<th></th>';
	print '<th>'._("Airport").'</th>';
	print '<th>'._("Country").'</th>';
	print '<th>'._("# of times").'</th>';
	print '<th></th>';
	print '</thead>';
	print '<tbody>';
	$i = 1;
	foreach($airport_airport_array as $airport_item)
	{
		print '<tr>';
		print '<td><strong>'.$i.'</strong></td>';
		print '<td>';
		print '<a href="'.$globalURL.'/airport/'.$airport_item['airport_departure_icao'].'">'.$airport_item['airport_departure_city'].', '.$airport_item['airport_departure_country'].' ('.$airport_item['airport_departure_icao'].')</a>';
		print '</td>';
		print '<td>';
		print '<a href="'.$globalURL.'/country/'.strtolower(str_replace(" ", "-", $airport_item['airport_departure_country'])).'">'.$airport_item['airport_departure_country'].'</a>';
		print '</td>';
		print '<td>';
		print $airport_item['airport_departure_icao_count'];
		print '</td>';
		print '<td><a href="'.$globalURL.'/search?departure_airport_route='.$airport_item['airport_departure_icao'].'&aircraft='.$aircraft_type.'">Search flights</a></td>';
		print '</tr>';
		$i++;
	}
	print '<tbody>';
	print '</table>';
	print '</div>';
	print '</div>';
} else {
	$title = _("Aircraft Type");
	require_once('header.php');
	print '<h1>'._("Error").'</h1>';
	print '<p>'._("Sorry, the aircraft type does not exist in this database. :(").'</p>';  
}

require_once('footer.php');
?>