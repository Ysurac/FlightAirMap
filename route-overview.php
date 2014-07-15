<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');

if (!isset($_GET['departure_airport']) || !isset($_GET['arrival_airport'])){
	header('Location: '.$globalURL.'');
} else {

	$page_url = $globalURL.'/route/'.$_GET['departure_airport'].'/'.$_GET['arrival_airport'];
	
	
	  $spotter_array = Spotter::getSpotterDataByRoute($_GET['departure_airport'], $_GET['arrival_airport'], "0,15", "");
	  
	  if (!empty($spotter_array))
	  {
	  
		  $title = 'Flights between '.$spotter_array[0]['departure_airport_name'].' ('.$spotter_array[0]['departure_airport_icao'].'), '.$spotter_array[0]['departure_airport_country'].' - '.$spotter_array[0]['arrival_airport_name'].' ('.$spotter_array[0]['arrival_airport_icao'].'), '.$spotter_array[0]['arrival_airport_country'];
			require('header.php');
		  
				print '<div class="info column">';
					print '<h1>Flights between '.$spotter_array[0]['departure_airport_name'].' ('.$spotter_array[0]['departure_airport_icao'].'), '.$spotter_array[0]['departure_airport_country'].' - '.$spotter_array[0]['arrival_airport_name'].' ('.$spotter_array[0]['arrival_airport_icao'].'), '.$spotter_array[0]['arrival_airport_country'].'</h1>';
	        	print '<div><span class="label">Coming From</span><a href="'.$globalURL.'/airport/'.$spotter_array[0]['departure_airport_icao'].'">'.$spotter_array[0]['departure_airport_name'].' ('.$spotter_array[0]['departure_airport_icao'].'), '.$spotter_array[0]['departure_airport_country'].'</a></div>';
	        	print '<div><span class="label">Flying To</span><a href="'.$globalURL.'/airport/'.$spotter_array[0]['arrival_airport_icao'].'">'.$spotter_array[0]['arrival_airport_name'].' ('.$spotter_array[0]['arrival_airport_icao'].'), '.$spotter_array[0]['arrival_airport_country'].'</a></div>';
	        	print '<div><span class="label">Flight Schedule</span><a href="http://flightaware.com/live/findflight/'.$spotter_array[0]['departure_airport_icao'].'/'.$spotter_array[0]['arrival_airport_icao'].'/" target="_blank">Upcoming Flight Schedules for this route</a></div>';
	      print '</div>';
	    
	    include('route-sub-menu.php');
	    
		 print '<div class="col-sm-8">';
		  
				 print '<div class="overview-statistic column">';
					 print '<h3>Latest 15 Additions</h3>';
					  
					 include('table-output-small.php');
					 
					 print '<div class="more"><a href="'.$globalURL.'/route/detailed/'.$_GET['departure_airport'].'/'.$_GET['arrival_airport'].'">View detailed list<i class="fa fa-angle-double-right"></i></a></div>';
			   print '</div>';
		  
		  print '</div>';
		  
		  print '<div class="col-sm-4">';
	
		  	$airline_array = Spotter::countAllAirlinesByRoute($_GET['departure_airport'], $_GET['arrival_airport']);
		  	if (!empty($airline_array))
		    {
		    	print '<div class="overview-statistic column">';
						print '<h3>Top 5 Airlines</h3>';
			      print '<div class="table-responsive">';
			          print '<table class="common-airline">';
			            print '<thead>';
			            	print '<th></th>';
			            	print '<th>Airline</th>';
			            	print '<th># of times</th>';
			            print '</thead>';
			            print '<tbody>';
			            $i = 1;
			              foreach($airline_array as $airline_item)
			              {
			              	if ($i <= 5)
			              	{
				                print '<tr>';
					                print '<td><strong>'.$i.'</strong></td>';
					                print '<td class="logo">';
		      			      		if (@getimagesize($globalURL.'/images/airlines/'.$airline_item['airline_icao'].'.png'))
		      			      		{
		      			      			print '<a href="'.$globalURL.'/airline/'.$airline_item['airline_icao'].'"><img src="'.$globalURL.'/images/airlines/'.$airline_item['airline_icao'].'.png" />';
		      				      	} else {
		      				      		print '<a href="'.$globalURL.'/airline/'.$airline_item['airline_icao'].'">'.$airline_item['airline_name'].' ('.$airline_item['airline_icao'].')</a>';
		      				      	}
		      			      	print '</td>';
		      			      	print '<td>';
			                    print $airline_item['airline_count'];
			                  print '</td>';
				               print '</tr>';
			                }
			                $i++;
			              }
			             print '<tbody>';
			          print '</table>';
			      print '</div>';
			      print '<div class="more"><a href="'.$globalURL.'/route/statistics/airline/'.$_GET['departure_airport'].'/'.$_GET['arrival_airport'].'">View full statistics<i class="fa fa-angle-double-right"></i></a></div>';
		      print '</div>';
		    }
		    
		    $aircraft_array = Spotter::countAllAircraftTypesByRoute($_GET['departure_airport'], $_GET['arrival_airport']);
		  	if (!empty($aircraft_array))
		    {
		    	print '<div class="overview-statistic column">';
						print '<h3>Top 5 Aircrafts</h3>';
			      print '<div class="table-responsive">';
			          print '<table class="common-type">';
			             print '<thead>';
					        print '<th></th>';
					        print '<th>Aircraft</th>';
					        print '<th># of Times</th>';
					    print '</thead>';
			            print '<tbody>';
			            $i = 1;
			              foreach($aircraft_array as $aircraft_item)
			              {
			              	if ($i <= 5)
			              	{
				                print '<tr>';
						            print '<td><strong>'.$i.'</strong></td>';
						            print '<td>';
						              print '<a href="'.$globalURL.'/aircraft/'.$aircraft_item['aircraft_icao'].'">'.$aircraft_item['aircraft_name'].' ('.$aircraft_item['aircraft_icao'].')</a>';
						            print '</td>';
						            print '<td>';
						              print $aircraft_item['aircraft_icao_count'];
						            print '</td>';
						         print '</tr>';
			                }
			                $i++;
			              }
			             print '<tbody>';
			          print '</table>';
			      print '</div>';
			      print '<div class="more"><a href="'.$globalURL.'/route/statistics/aircraft/'.$_GET['departure_airport'].'/'.$_GET['arrival_airport'].'">View full statistics<i class="fa fa-angle-double-right"></i></a></div>';
		      print '</div>';
		    }
		  
		print '</div>';
	
	
	} else {
	
		$title = "Unknown Route";
		require('header.php');
		
		print '<h1>Error</h1>';
	
	  print '<p>Sorry, this route does not exist in this database. :(</p>'; 
	}
}
?>

<?php
require('footer.php');
?>