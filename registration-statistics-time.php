<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');
$Spotter = new Spotter();
$spotter_array = $Spotter->getSpotterDataByRegistration($_GET['registration'], "0,1", $_GET['sort']);
$aircraft_array = $Spotter->getAircraftInfoByRegistration($_GET['registration']);

if (!empty($spotter_array))
{
	$title = 'Most Common Time of Day of aircraft with registration '.$_GET['registration'];
	require('header.php');
  
	print '<div class="info column">';
		print '<h1>'.$_GET['registration'].' - '.$aircraft_array[0]['aircraft_name'].' ('.$aircraft_array[0]['aircraft_icao'].')</h1>';
		print '<div><span class="label">Name</span><a href="'.$globalURL.'/aircraft/'.$aircraft_array[0]['aircraft_icao'].'">'.$aircraft_array[0]['aircraft_name'].'</a></div>';
		print '<div><span class="label">ICAO</span><a href="'.$globalURL.'/aircraft/'.$aircraft_array[0]['aircraft_icao'].'">'.$aircraft_array[0]['aircraft_icao'].'</a></div>'; 
		print '<div><span class="label">Manufacturer</span><a href="'.$globalURL.'/manufacturer/'.strtolower(str_replace(" ", "-", $aircraft_array[0]['aircraft_manufacturer'])).'">'.$aircraft_array[0]['aircraft_manufacturer'].'</a></div>';
	print '</div>';
	
	include('registration-sub-menu.php');
  
	print '<div class="column">';
	print '<h2>Most Common Time of Day</h2>';
	print '<p>The statistic below shows the most common time of day from aircraft with registration <strong>'.$_GET['registration'].'</strong>.</p>';

	$hour_array = $Spotter->countAllHoursByRegistration($_GET['registration']);

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

	$title = "Registration";
	require('header.php');
	
	print '<h1>Error</h1>';

  print '<p>Sorry, this registration does not exist in this database. :(</p>';  
}


require('footer.php');
?>