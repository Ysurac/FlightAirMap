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
  
	  $title = 'Most Common Aircraft between '.$spotter_array[0]['departure_airport_name'].' ('.$spotter_array[0]['departure_airport_icao'].'), '.$spotter_array[0]['departure_airport_country'].' - '.$spotter_array[0]['arrival_airport_name'].' ('.$spotter_array[0]['arrival_airport_icao'].'), '.$spotter_array[0]['arrival_airport_country'];
		require('header.php');
	  
			print '<div class="info column">';
				print '<h1>Flights between '.$spotter_array[0]['departure_airport_name'].' ('.$spotter_array[0]['departure_airport_icao'].'), '.$spotter_array[0]['departure_airport_country'].' - '.$spotter_array[0]['arrival_airport_name'].' ('.$spotter_array[0]['arrival_airport_icao'].'), '.$spotter_array[0]['arrival_airport_country'].'</h1>';
        	print '<div><span class="label">Coming From</span><a href="'.$globalURL.'/airport/'.$spotter_array[0]['departure_airport_icao'].'">'.$spotter_array[0]['departure_airport_name'].' ('.$spotter_array[0]['departure_airport_icao'].'), '.$spotter_array[0]['departure_airport_country'].'</a></div>';
        	print '<div><span class="label">Flying To</span><a href="'.$globalURL.'/airport/'.$spotter_array[0]['arrival_airport_icao'].'">'.$spotter_array[0]['arrival_airport_name'].' ('.$spotter_array[0]['arrival_airport_icao'].'), '.$spotter_array[0]['arrival_airport_country'].'</a></div>';
        	print '<div><span class="label">Flight Schedule</span><a href="http://flightaware.com/live/findflight/'.$spotter_array[0]['departure_airport_icao'].'/'.$spotter_array[0]['arrival_airport_icao'].'/" target="_blank">Upcoming Flight Schedules for this route</a></div>';
      print '</div>';
    
    include('route-sub-menu.php');
  
  print '<div class="column">';
  	print '<h2>Most Common Aircraft</h2>';
  	print '<p>The statistic below shows the most common aircrafts of flights between <strong>'.$spotter_array[0]['departure_airport_name'].' ('.$spotter_array[0]['departure_airport_icao'].'), '.$spotter_array[0]['departure_airport_country'].'</strong> and <strong>'.$spotter_array[0]['arrival_airport_name'].' ('.$spotter_array[0]['arrival_airport_icao'].'), '.$spotter_array[0]['arrival_airport_country'].'</strong>.</p>';

	  $aircraft_array = Spotter::countAllAircraftTypesByRoute($_GET['departure_airport'], $_GET['arrival_airport']);
	  
	  if (!empty($aircraft_array))
	  {
	    print '<div class="table-responsive">';
		    print '<table class="common-type">';
		      print '<thead>';
		        print '<th></th>';
		        print '<th>Aircraft Type</th>';
		        print '<th># of Times</th>';
		        print '<th></th>';
		      print '</thead>';
		      print '<tbody>';
		      $i = 1;
		        foreach($aircraft_array as $aircraft_item)
		        {
		          print '<tr>';
		          	print '<td><strong>'.$i.'</strong></td>';
		            print '<td>';
		              print '<a href="'.$globalURL.'/aircraft/'.$aircraft_item['aircraft_icao'].'">'.$aircraft_item['aircraft_name'].' ('.$aircraft_item['aircraft_icao'].')</a>';
		            print '</td>';
		            print '<td>';
		              print $aircraft_item['aircraft_icao_count'];
		            print '</td>';
		            print '<td><a href="'.$globalURL.'/search?aircraft='.$aircraft_item['aircraft_icao'].'&departure_airport_route='.$_GET['departure_airport'].'&arrival_airport_route='.$_GET['arrival_airport'].'">Search flights</a></td>';
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