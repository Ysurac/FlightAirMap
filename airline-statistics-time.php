<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');

$spotter_array = Spotter::getSpotterDataByAirline($_GET['airline'],"0,1","");


if (!empty($spotter_array))
{
  $title = 'Most Common Time of Day from '.$spotter_array[0]['airline_name'].' ('.$spotter_array[0]['airline_icao'].')';
	require('header.php');
  
  date_default_timezone_set('America/Toronto');
  
  print '<div class="select-item">';
	print '<form action="'.$globalURL.'/airline" method="post">';
		print '<select name="airline" class="selectpicker" data-live-search="true">';
      print '<option></option>';
      $airline_names = Spotter::getAllAirlineNames();
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
			if (@getimagesize($globalURL.'/images/airlines/'.$spotter_array[0]['airline_icao'].'.png'))
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
  	print '<h2>Most Common Time of Day</h2>';
  	print '<p>The statistic below shows the most common time of day from <strong>'.$spotter_array[0]['airline_name'].'</strong>.</p>';
  	
      $hour_array = Spotter::countAllHoursByAirline($_GET['airline']);
      
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

	$title = "Airline Statistic";
	require('header.php');
	
	print '<h1>Error</h1>';

  print '<p>Sorry, the airline does not exist in this database. :(</p>'; 
}


?>

<?php
require('footer.php');
?>