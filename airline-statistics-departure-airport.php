<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
if (!isset($_GET['airline'])) {
        header('Location: '.$globalURL.'/airline');
        die();
}
$Spotter = new Spotter();
$spotter_array = $Spotter->getSpotterDataByAirline($_GET['airline'],"0,1","");

if (!empty($spotter_array))
{
	$title = 'Most Common Departure Airports from '.$spotter_array[0]['airline_name'].' ('.$spotter_array[0]['airline_icao'].')';
	require_once('header.php');
	print '<div class="select-item">';
	print '<form action="'.$globalURL.'/airline" method="post">';
	print '<select name="airline" class="selectpicker" data-live-search="true">';
	print '<option></option>';
	$airline_names = $Spotter->getAllAirlineNames();
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
			if ($globalIVAO && @getimagesize($globalURL.'/images/airlines/'.$spotter_array[0]['airline_icao'].'.gif'))
			{
				print '<img src="'.$globalURL.'/images/airlines/'.$spotter_array[0]['airline_icao'].'.gif" alt="'.$spotter_array[0]['airline_name'].' ('.$spotter_array[0]['airline_icao'].')" title="'.$spotter_array[0]['airline_name'].' ('.$spotter_array[0]['airline_icao'].')" class="logo" />';
			}
			elseif (@getimagesize($globalURL.'/images/airlines/'.$spotter_array[0]['airline_icao'].'.png'))
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
	print '<h2>Most Common Departure Airports</h2>';

  	?>
  	<p>The statistic below shows all departure airports of flights from <strong><?php print $spotter_array[0]['airline_name']; ?></strong>.</p>
  	<?php
	$airport_airport_array = $Spotter->countAllDepartureAirportsByAirline($_GET['airline']);
    	?>
    	<script>
    	google.load("visualization", "1", {packages:["geochart"]});
    	google.setOnLoadCallback(drawCharts);
    	$(window).resize(function(){
    		drawCharts();
    	});
    	function drawCharts() {
    
        var data = google.visualization.arrayToDataTable([ 
        	["Airport", "# of Times"],
        	<?php
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
	print '<table class="common-airport table-striped">';
	print '<thead>';
	print '<th></th>';
	print '<th>Airport</th>';
	print '<th>Country</th>';
	print '<th># of times</th>';
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
		print '<td><a href="'.$globalURL.'/search?departure_airport_route='.$airport_item['airport_departure_icao'].'&airline='.$_GET['airline'].'">Search flights</a></td>';
		print '</tr>';
		$i++;
	}
	print '<tbody>';
	print '</table>';
	print '</div>';
	print '</div>';
} else {
	$title = "Airline Statistic";
	require_once('header.php');
	print '<h1>Error</h1>';
	print '<p>Sorry, the airline does not exist in this database. :(</p>'; 
}

require_once('footer.php');
?>