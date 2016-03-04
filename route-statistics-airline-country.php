<?php
if ($_GET['departure_airport'] == "" || $_GET['arrival_airport'] == "")
{
	header('Location: /');
}

require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
$Spotter = new Spotter();
$sort = filter_input(INPUT_GET,'sort',FILTER_SANITIZE_STRING);
$spotter_array = $Spotter->getSpotterDataByRoute($_GET['departure_airport'], $_GET['arrival_airport'], "0,1", $sort);
  
  if (!empty($spotter_array))
  {
  
	  $title = 'Most Common Airlines by Country between '.$spotter_array[0]['departure_airport_name'].' ('.$spotter_array[0]['departure_airport_icao'].'), '.$spotter_array[0]['departure_airport_country'].' - '.$spotter_array[0]['arrival_airport_name'].' ('.$spotter_array[0]['arrival_airport_icao'].'), '.$spotter_array[0]['arrival_airport_country'];
		require_once('header.php');
	  
			print '<div class="info column">';
				print '<h1>Flights between '.$spotter_array[0]['departure_airport_name'].' ('.$spotter_array[0]['departure_airport_icao'].'), '.$spotter_array[0]['departure_airport_country'].' - '.$spotter_array[0]['arrival_airport_name'].' ('.$spotter_array[0]['arrival_airport_icao'].'), '.$spotter_array[0]['arrival_airport_country'].'</h1>';
        	print '<div><span class="label">Coming From</span><a href="'.$globalURL.'/airport/'.$spotter_array[0]['departure_airport_icao'].'">'.$spotter_array[0]['departure_airport_name'].' ('.$spotter_array[0]['departure_airport_icao'].'), '.$spotter_array[0]['departure_airport_country'].'</a></div>';
        	print '<div><span class="label">Flying To</span><a href="'.$globalURL.'/airport/'.$spotter_array[0]['arrival_airport_icao'].'">'.$spotter_array[0]['arrival_airport_name'].' ('.$spotter_array[0]['arrival_airport_icao'].'), '.$spotter_array[0]['arrival_airport_country'].'</a></div>';
      print '</div>';
    
    include('route-sub-menu.php');
  
  print '<div class="column">';
  	print '<h2>Most Common Airlines by Country</h2>';
  	print '<p>The statistic below shows the most common airlines by Country of origin of flights between <strong>'.$spotter_array[0]['departure_airport_name'].' ('.$spotter_array[0]['departure_airport_icao'].'), '.$spotter_array[0]['departure_airport_country'].'</strong> and <strong>'.$spotter_array[0]['arrival_airport_name'].' ('.$spotter_array[0]['arrival_airport_icao'].'), '.$spotter_array[0]['arrival_airport_country'].'</strong>.</p>';

	 $airline_array = $Spotter->countAllAirlineCountriesByRoute($_GET['departure_airport'], $_GET['arrival_airport']);
      
      print '<div id="chartCountry" class="chart" width="100%"></div>
      	<script> 
      		google.load("visualization", "1", {packages:["geochart"]});
          google.setOnLoadCallback(drawChart);
          function drawChart() {
            var data = google.visualization.arrayToDataTable([
            	["Country", "# of Times"], ';
            	$country_data = '';
              foreach($airline_array as $airline_item)
    					{
    						$country_data .= '[ "'.$airline_item['airline_country'].'",'.$airline_item['airline_country_count'].'],';
    					}
    					$country_data = substr($country_data, 0, -1);
    					print $country_data;
            print ']);
    
            var options = {
            	legend: {position: "none"},
            	chartArea: {"width": "80%", "height": "60%"},
            	height:500,
            	colors: ["#8BA9D0","#1a3151"]
            };
    
            var chart = new google.visualization.GeoChart(document.getElementById("chartCountry"));
            chart.draw(data, options);
          }
          $(window).resize(function(){
    			  drawChart();
    			});
      </script>';

      if (!empty($airline_array))
      {
        print '<div class="table-responsive">';
            print '<table class="common-country table-striped">';
              print '<thead>';
              	print '<th></th>';
                print '<th>Country</th>';
                print '<th># of times</th>';
              print '</thead>';
              print '<tbody>';
              $i = 1;
                foreach($airline_array as $airline_item)
                {
                  print '<tr>';
                  	print '<td><strong>'.$i.'</strong></td>';
                    print '<td>';
                      print '<a href="'.$globalURL.'/country/'.strtolower(str_replace(" ", "-", $airline_item['airline_country'])).'">'.$airline_item['airline_country'].'</a>';
                    print '</td>';
                    print '<td>';
                      print $airline_item['airline_country_count'];
                    print '</td>';
                  print '</tr>';
                  $i++;
                }
               print '<tbody>';
            print '</table>';
        print '</div>';
      }
  print '</div>';  
  
} else {

	$title = "Unknown Route";
	require_once('header.php');
	
	print '<h1>Error</h1>';

  print '<p>Sorry, this route does not exist in this database. :(</p>'; 
}


?>

<?php
require_once('footer.php');
?>