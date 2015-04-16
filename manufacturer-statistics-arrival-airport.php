<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');

$manufacturer = ucwords(str_replace("-", " ", $_GET['aircraft_manufacturer']));

$spotter_array = Spotter::getSpotterDataByManufacturer($manufacturer,"0,1", $_GET['sort']);

if (!empty($spotter_array))
{
  $title = 'Most Common Arrival Airports from '.$manufacturer;
	require('header.php');
  
  
  
  print '<div class="select-item">';
	print '<form action="'.$globalURL.'/manufacturer" method="post">';
		print '<select name="aircraft_manufacturer" class="selectpicker" data-live-search="true">';
      print '<option></option>';
      $all_manufacturers = Spotter::getAllManufacturers();
      foreach($all_manufacturers as $all_manufacturer)
      {
        if($_GET['aircraft_manufacturer'] == strtolower(str_replace(" ", "-", $all_manufacturer['aircraft_manufacturer'])))
        {
          print '<option value="'.strtolower(str_replace(" ", "-", $all_manufacturer['aircraft_manufacturer'])).'" selected="selected">'.$all_manufacturer['aircraft_manufacturer'].'</option>';
        } else {
          print '<option value="'.strtolower(str_replace(" ", "-", $all_manufacturer['aircraft_manufacturer'])).'">'.$all_manufacturer['aircraft_manufacturer'].'</option>';
        }
      }
    print '</select>';
	print '<button type="submit"><i class="fa fa-angle-double-right"></i></button>';
	print '</form>';
  print '</div>';
	
	print '<div class="info column">';
  	print '<h1>'.$manufacturer.'</h1>';
  print '</div>';

  include('manufacturer-sub-menu.php');
  
  print '<div class="column">';
  	print '<h2>Most Common Arrival Airports</h2>';
  	
  	?>
  	<p>The statistic below shows all arrival airports of flights from <strong><?php print $manufacturer; ?></strong>.</p>
  	<?php
    	 $airport_airport_array = Spotter::countAllArrivalAirportsByManufacturer($manufacturer);
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
          foreach($airport_airport_array as $airport_item)
    			{
    				$name = $airport_item['airport_arrival_city'].', '.$airport_item['airport_arrival_country'].' ('.$airport_item['airport_arrival_icao'].')';
    				$name = str_replace("'", "", $name);
    				$name = str_replace('"', "", $name);
    				$airport_data .= '[ "'.$name.'",'.$airport_item['airport_arrival_icao_count'].'],';
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
                      print '<a href="'.$globalURL.'/airport/'.$airport_item['airport_arrival_icao'].'">'.$airport_item['airport_arrival_city'].', '.$airport_item['airport_arrival_country'].' ('.$airport_item['airport_arrival_icao'].')</a>';
                    print '</td>';
                    print '<td>';
                      print '<a href="'.$globalURL.'/country/'.strtolower(str_replace(" ", "-", $airport_item['airport_arrival_country'])).'">'.$airport_item['airport_arrival_country'].'</a>';
                    print '</td>';
                    print '<td>';
                      print $airport_item['airport_arrival_icao_count'];
                    print '</td>';
                    print '<td><a href="'.$globalURL.'/search?arrival_airport_route='.$airport_item['airport_arrival_icao'].'&manufacturer='.$manufacturer.'">Search flights</a></td>';
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

	$title = "Manufacturer";
	require('header.php');
	
	print '<h1>Error</h1>';

   print '<p>Sorry, the aircraft manufacturer does not exist in this database. :(</p>'; 
}


?>

<?php
require('footer.php');
?>