<?php
if ($_GET['departure_airport'] == "" || $_GET['arrival_airport'] == "")
{
	header('Location: /');
}

require('require/class.Connection.php');
require('require/class.Spotter.php');


  $spotter_array = Spotter::getSpotterDataByRoute($_GET['departure_airport'], $_GET['arrival_airport'], "0,1", $_GET['sort']);
  
  if (!empty($spotter_array))
  {
  
	  $title = 'Most Common Time of Day between '.$spotter_array[0]['departure_airport_name'].' ('.$spotter_array[0]['departure_airport_icao'].'), '.$spotter_array[0]['departure_airport_country'].' - '.$spotter_array[0]['arrival_airport_name'].' ('.$spotter_array[0]['arrival_airport_icao'].'), '.$spotter_array[0]['arrival_airport_country'];
		require('header.php');
	  
			print '<div class="info column">';
				print '<h1>Flights between '.$spotter_array[0]['departure_airport_name'].' ('.$spotter_array[0]['departure_airport_icao'].'), '.$spotter_array[0]['departure_airport_country'].' - '.$spotter_array[0]['arrival_airport_name'].' ('.$spotter_array[0]['arrival_airport_icao'].'), '.$spotter_array[0]['arrival_airport_country'].'</h1>';
        	print '<div><span class="label">Coming From</span><a href="'.$globalURL.'/airport/'.$spotter_array[0]['departure_airport_icao'].'">'.$spotter_array[0]['departure_airport_name'].' ('.$spotter_array[0]['departure_airport_icao'].'), '.$spotter_array[0]['departure_airport_country'].'</a></div>';
        	print '<div><span class="label">Flying To</span><a href="'.$globalURL.'/airport/'.$spotter_array[0]['arrival_airport_icao'].'">'.$spotter_array[0]['arrival_airport_name'].' ('.$spotter_array[0]['arrival_airport_icao'].'), '.$spotter_array[0]['arrival_airport_country'].'</a></div>';
      print '</div>';
    
    include('route-sub-menu.php');
  
  print '<div class="column">';
  	print '<h2>Most Common Time of Day</h2>';
  	print '<p>The statistic below shows the most common time of day of flights between <strong>'.$spotter_array[0]['departure_airport_name'].' ('.$spotter_array[0]['departure_airport_icao'].'), '.$spotter_array[0]['departure_airport_country'].'</strong> and <strong>'.$spotter_array[0]['arrival_airport_name'].' ('.$spotter_array[0]['arrival_airport_icao'].'), '.$spotter_array[0]['arrival_airport_country'].'</strong>.</p>';
  	
      $hour_array = Spotter::countAllHoursByRoute($_GET['departure_airport'], $_GET['arrival_airport']);
      
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

	$title = "Unknown Route";
	require('header.php');
	
	print '<h1>Error</h1>';

  print '<p>Sorry, this route does not exist in this database. :(</p>'; 
}


?>

<?php
require('footer.php');
?>