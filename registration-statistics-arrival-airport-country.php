<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');

$spotter_array = Spotter::getSpotterDataByRegistration($_GET['registration'], "0,1", $_GET['sort']);
$aircraft_array = Spotter::getAircraftInfoByRegistration($_GET['registration']);


if (!empty($spotter_array))
{
  $title = 'Most Common Arrival Airports by Country of aircraft with registration '.$_GET['registration'];
	require('header.php');
  
  date_default_timezone_set('America/Toronto');
  
	print '<div class="info column">';
		print '<h1>'.$_GET['registration'].' - '.$aircraft_array[0]['aircraft_name'].' ('.$aircraft_array[0]['aircraft_icao'].')</h1>';
		print '<div><span class="label">Name</span><a href="'.$globalURL.'/aircraft/'.$aircraft_array[0]['aircraft_icao'].'">'.$aircraft_array[0]['aircraft_name'].'</a></div>';
		print '<div><span class="label">ICAO</span><a href="'.$globalURL.'/aircraft/'.$aircraft_array[0]['aircraft_icao'].'">'.$aircraft_array[0]['aircraft_icao'].'</a></div>'; 
		print '<div><span class="label">Manufacturer</span><a href="'.$globalURL.'/manufacturer/'.strtolower(str_replace(" ", "-", $aircraft_array[0]['aircraft_manufacturer'])).'">'.$aircraft_array[0]['aircraft_manufacturer'].'</a></div>';
	print '</div>';
	
	if ($spotter_array[0]['highlight'] != "")
	{
		print '<div class="alert alert-warning">'.$spotter_array[0]['highlight'].'</div>';
	}
	
	include('registration-sub-menu.php');
  
  print '<div class="column">';
  	print '<h2>Most Common Arrival Airports by Country</h2>';
  	
  	?>
  	 <p>The statistic below shows all arrival airports by Country of origin of flights with aircraft registration <strong><?php print $_GET['registration']; ?></strong>.</p>
  	<?php
    	 $airport_country_array = Spotter::countAllArrivalAirportCountriesByRegistration($_GET['registration']);
      
      print '<div id="chartCountry" class="chart" width="100%"></div>
      	<script> 
      		google.load("visualization", "1", {packages:["geochart"]});
          google.setOnLoadCallback(drawChart);
          function drawChart() {
            var data = google.visualization.arrayToDataTable([
            	["Country", "# of Times"], ';
              foreach($airport_country_array as $airport_item)
    					{
    						$country_data .= '[ "'.$airport_item['arrival_airport_country'].'",'.$airport_item['airport_arrival_country_count'].'],';
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

      if (!empty($airport_country_array))
      {
        print '<div class="table-responsive">';
            print '<table class="common-country">';
              print '<thead>';
              	print '<th></th>';
                print '<th>Country</th>';
                print '<th># of times</th>';
              print '</thead>';
              print '<tbody>';
              $i = 1;
                foreach($airport_country_array as $airport_item)
                {
                  print '<tr>';
                  	print '<td><strong>'.$i.'</strong></td>';
                    print '<td>';
                      print '<a href="'.$globalURL.'/country/'.strtolower(str_replace(" ", "-", $airport_item['arrival_airport_country'])).'">'.$airport_item['arrival_airport_country'].'</a>';
                    print '</td>';
                    print '<td>';
                      print $airport_item['airport_arrival_country_count'];
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

	$title = "Registration";
	require('header.php');
	
	print '<h1>Error</h1>';

  print '<p>Sorry, this registration does not exist in this database. :(</p>';  
}


?>

<?php
require('footer.php');
?>