<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
$Spotter = new Spotter();
if (isset($_GET['sort'])) $spotter_array = $Spotter->getSpotterDataByRegistration($_GET['registration'], "0,1", $_GET['sort']);
else $spotter_array = $Spotter->getSpotterDataByRegistration($_GET['registration'], "0,1");
$aircraft_array = $Spotter->getAircraftInfoByRegistration($_GET['registration']);


if (!empty($spotter_array))
{
  $title = 'Most Common Departure Airports of aircraft with registration '.$_GET['registration'];
	require_once('header.php');
  
  
  
	print '<div class="info column">';
		print '<h1>'.$_GET['registration'].' - '.$aircraft_array[0]['aircraft_name'].' ('.$aircraft_array[0]['aircraft_icao'].')</h1>';
		print '<div><span class="label">Name</span><a href="'.$globalURL.'/aircraft/'.$aircraft_array[0]['aircraft_icao'].'">'.$aircraft_array[0]['aircraft_name'].'</a></div>';
		print '<div><span class="label">ICAO</span><a href="'.$globalURL.'/aircraft/'.$aircraft_array[0]['aircraft_icao'].'">'.$aircraft_array[0]['aircraft_icao'].'</a></div>'; 
		print '<div><span class="label">Manufacturer</span><a href="'.$globalURL.'/manufacturer/'.strtolower(str_replace(" ", "-", $aircraft_array[0]['aircraft_manufacturer'])).'">'.$aircraft_array[0]['aircraft_manufacturer'].'</a></div>';
	print '</div>';
	
	include('registration-sub-menu.php');
  
  print '<div class="column">';
  	print '<h2>Most Common Departure Airports</h2>';
  	
  	?>
  	<p>The statistic below shows all departure airports of flights with aircraft registration <strong><?php print $_GET['registration']; ?></strong>.</p>
  	<?php
    	 $airport_airport_array = $Spotter->countAllDepartureAirportsByRegistration($_GET['registration']);
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
                print '</tr>';
                $i++;
              }
            print '<tbody>';
          print '</table>';
      print '</div>';
      ?>
  	<?php
  print '</div>';
  
  
} else {

	$title = "Registration";
	require_once('header.php');
	
	print '<h1>Error</h1>';

  print '<p>Sorry, this registration does not exist in this database. :(</p>';  
}


?>

<?php
require_once('footer.php');
?>