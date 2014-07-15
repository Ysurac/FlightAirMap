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
  
	  $title = 'Most Common Airlines between '.$spotter_array[0]['departure_airport_name'].' ('.$spotter_array[0]['departure_airport_icao'].'), '.$spotter_array[0]['departure_airport_country'].' - '.$spotter_array[0]['arrival_airport_name'].' ('.$spotter_array[0]['arrival_airport_icao'].'), '.$spotter_array[0]['arrival_airport_country'];
		require('header.php');
	  
			print '<div class="info column">';
				print '<h1>Flights between '.$spotter_array[0]['departure_airport_name'].' ('.$spotter_array[0]['departure_airport_icao'].'), '.$spotter_array[0]['departure_airport_country'].' - '.$spotter_array[0]['arrival_airport_name'].' ('.$spotter_array[0]['arrival_airport_icao'].'), '.$spotter_array[0]['arrival_airport_country'].'</h1>';
        	print '<div><span class="label">Coming From</span><a href="'.$globalURL.'/airport/'.$spotter_array[0]['departure_airport_icao'].'">'.$spotter_array[0]['departure_airport_name'].' ('.$spotter_array[0]['departure_airport_icao'].'), '.$spotter_array[0]['departure_airport_country'].'</a></div>';
        	print '<div><span class="label">Flying To</span><a href="'.$globalURL.'/airport/'.$spotter_array[0]['arrival_airport_icao'].'">'.$spotter_array[0]['arrival_airport_name'].' ('.$spotter_array[0]['arrival_airport_icao'].'), '.$spotter_array[0]['arrival_airport_country'].'</a></div>';
        	print '<div><span class="label">Flight Schedule</span><a href="http://flightaware.com/live/findflight/'.$spotter_array[0]['departure_airport_icao'].'/'.$spotter_array[0]['arrival_airport_icao'].'/" target="_blank">Upcoming Flight Schedules for this route</a></div>';
      print '</div>';
    
    include('route-sub-menu.php');
  
  print '<div class="column">';
  	print '<h2>Most Common Airlines</h2>';
  	print '<p>The statistic below shows the most common airlines of flights between <strong>'.$spotter_array[0]['departure_airport_name'].' ('.$spotter_array[0]['departure_airport_icao'].'), '.$spotter_array[0]['departure_airport_country'].'</strong> and <strong>'.$spotter_array[0]['arrival_airport_name'].' ('.$spotter_array[0]['arrival_airport_icao'].'), '.$spotter_array[0]['arrival_airport_country'].'</strong>.</p>';

	  $airline_array = Spotter::countAllAirlinesByRoute($_GET['departure_airport'], $_GET['arrival_airport']);

	  if (!empty($airline_array))
    {
      print '<div class="table-responsive">';
          print '<table class="common-airline">';
            print '<thead>';
            	print '<th></th>';
            	print '<th></th>';
              print '<th>Airline</th>';
              print '<th>Country</th>';
              print '<th># of times</th>';
              print '<th></th>';
            print '</thead>';
            print '<tbody>';
            $i = 1;
              foreach($airline_array as $airline_item)
              {
                print '<tr>';
                print '<td><strong>'.$i.'</strong></td>';
                print '<td class="logo">';
      			      		print '<a href="'.$globalURL.'/airline/'.$airline_item['airline_icao'].'"><img src="';
      				      	if (@getimagesize($globalURL.'/images/airlines/'.$airline_item['airline_icao'].'.png'))
      				      	{
      				      		print $globalURL.'/images/airlines/'.$airline_item['airline_icao'].'.png';
      				      	} else {
      				      		print $globalURL.'/images/airlines/placeholder.png';
      				      	}
      				      	print '" /></a>';
      			      	print '</td>';
                  print '<td>';
                    print '<a href="'.$globalURL.'/airline/'.$airline_item['airline_icao'].'">'.$airline_item['airline_name'].' ('.$airline_item['airline_icao'].')</a>';
                  print '</td>';
                  print '<td>';
                    print '<a href="'.$globalURL.'/country/'.strtolower(str_replace(" ", "-", $airline_item['airline_country'])).'">'.$airline_item['airline_country'].'</a>';
                  print '</td>';
                  print '<td>';
                    print $airline_item['airline_count'];
                  print '</td>';
                  print '<td><a href="'.$globalURL.'/search?airline='.$airline_item['airline_icao'].'&departure_airport_route='.$_GET['departure_airport'].'&arrival_airport_route='.$_GET['arrival_airport'].'">Search flights</a></td>';
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