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
  
	  $title = 'Most Common Aircraft Manufacturer between '.$spotter_array[0]['departure_airport_name'].' ('.$spotter_array[0]['departure_airport_icao'].'), '.$spotter_array[0]['departure_airport_country'].' - '.$spotter_array[0]['arrival_airport_name'].' ('.$spotter_array[0]['arrival_airport_icao'].'), '.$spotter_array[0]['arrival_airport_country'];
		require('header.php');
	  
			print '<div class="info column">';
				print '<h1>Flights between '.$spotter_array[0]['departure_airport_name'].' ('.$spotter_array[0]['departure_airport_icao'].'), '.$spotter_array[0]['departure_airport_country'].' - '.$spotter_array[0]['arrival_airport_name'].' ('.$spotter_array[0]['arrival_airport_icao'].'), '.$spotter_array[0]['arrival_airport_country'].'</h1>';
        	print '<div><span class="label">Coming From</span><a href="'.$globalURL.'/airport/'.$spotter_array[0]['departure_airport_icao'].'">'.$spotter_array[0]['departure_airport_name'].' ('.$spotter_array[0]['departure_airport_icao'].'), '.$spotter_array[0]['departure_airport_country'].'</a></div>';
        	print '<div><span class="label">Flying To</span><a href="'.$globalURL.'/airport/'.$spotter_array[0]['arrival_airport_icao'].'">'.$spotter_array[0]['arrival_airport_name'].' ('.$spotter_array[0]['arrival_airport_icao'].'), '.$spotter_array[0]['arrival_airport_country'].'</a></div>';
      print '</div>';
    
    include('route-sub-menu.php');
  
  print '<div class="column">';
  	print '<h2>Most Common Aircraft Manufacturer</h2>';
  	print '<p>The statistic below shows the most common Aircraft Manufacturer of flights between <strong>'.$spotter_array[0]['departure_airport_name'].' ('.$spotter_array[0]['departure_airport_icao'].'), '.$spotter_array[0]['departure_airport_country'].'</strong> and <strong>'.$spotter_array[0]['arrival_airport_name'].' ('.$spotter_array[0]['arrival_airport_icao'].'), '.$spotter_array[0]['arrival_airport_country'].'</strong>.</p>';
	  
	 $manufacturers_array = Spotter::countAllAircraftManufacturerByRoute($_GET['departure_airport'], $_GET['arrival_airport']);
	
	  if (!empty($manufacturers_array))
	  {
	    print '<div class="table-responsive">';
		    print '<table class="common-manufacturer">';
		      print '<thead>';
		        print '<th></th>';
		        print '<th>Aircraft Manufacturer</th>';
		        print '<th># of Times</th>';
		        print '<th></th>';
		      print '</thead>';
		      print '<tbody>';
		      $i = 1;
		        foreach($manufacturers_array as $manufacturer_item)
		        {
		          print '<tr>';
		            print '<td><strong>'.$i.'</strong></td>';
		            print '<td>';
		              print '<a href="'.$globalURL.'/manufacturer/'.strtolower(str_replace(" ", "-", $manufacturer_item['aircraft_manufacturer'])).'">'.$manufacturer_item['aircraft_manufacturer'].'</a>';
		            print '</td>';
		            print '<td>';
		              print $manufacturer_item['aircraft_manufacturer_count'];
		            print '</td>';
		            print '<td><a href="'.$globalURL.'/search?manufacturer='.strtolower(str_replace(" ", "-", $manufacturer_item['aircraft_manufacturer'])).'&departure_airport_route='.$_GET['departure_airport'].'&arrival_airport_route='.$_GET['arrival_airport'].'">Search flights</a></td>';
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
	require('header.php');
	
	print '<h1>Error</h1>';

  print '<p>Sorry, this route does not exist in this database. :(</p>'; 
}


?>

<?php
require('footer.php');
?>