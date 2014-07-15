<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');

if (!isset($_GET['date'])){
	header('Location: '.$globalURL.'');
} else {

	$page_url = $globalURL.'/date/'.$_GET['date'];
	
	$spotter_array = Spotter::getSpotterDataByDate($_GET['date'],"0,15", "");
	
	
	if (!empty($spotter_array))
	{
	    date_default_timezone_set('America/Toronto');
	    
	    $title = 'Flights from '.date("l F j, Y", strtotime($spotter_array[0]['date_iso_8601']));
			require('header.php');
			
		print '<div class="select-item">';
	  		print '<form action="'.$globalURL.'/date" method="post">';
	  			print '<label for="date">Select a Date</label>';
	    		print '<input type="text" id="date" name="date" value="'.$_GET['date'].'" size="8" readonly="readonly" class="custom" />';
	    		print '<button type="submit"><i class="fa fa-angle-double-right"></i></button>';
	  		print '</form>';
	  	print '</div>';
	    
	    print '<div class="info column">';
	    	print '<h1>Flights from '.date("l F j, Y", strtotime($spotter_array[0]['date_iso_8601'])).'</h1>';
	    print '</div>';
	    
	    include('date-sub-menu.php');

		print '<div class="col-sm-8">';
	  
			 print '<div class="overview-statistic column">';
				 print '<h3>Latest 15 Additions</h3>';
				  
				 include('table-output-small.php');
				 
				 print '<div class="more"><a href="'.$globalURL.'/date/detailed/'.$_GET['date'].'">View detailed list<i class="fa fa-angle-double-right"></i></a></div>';
		   print '</div>';
	  
	  print '</div>';
	  
	  print '<div class="col-sm-4">';

	  	$airline_array = Spotter::countAllAirlinesByDate($_GET['date']);
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
		      print '<div class="more"><a href="'.$globalURL.'/date/statistics/airline/'.$_GET['date'].'">View full statistics<i class="fa fa-angle-double-right"></i></a></div>';
	      print '</div>';
	    }
	    
	    $aircraft_array = Spotter::countAllAircraftTypesByDate($_GET['date']);
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
		      print '<div class="more"><a href="'.$globalURL.'/date/statistics/aircraft/'.$_GET['date'].'">View full statistics<i class="fa fa-angle-double-right"></i></a></div>';
	      print '</div>';
	    }

	  	$airport_airport_array = Spotter::countAllDepartureAirportsByDate($_GET['date']);
	  	if (!empty($airport_airport_array))
	    {
	    	print '<div class="overview-statistic column">';
		    	print '<h3>Top 5 Departure Airports</h3>';
		      print '<div class="table-responsive">';
		          print '<table class="common-airport">';
		            print '<thead>';
		              print '<th></th>';
		              print '<th>Airport</th>';
		              print '<th># of times</th>';
		            print '</thead>';
		            print '<tbody>';
		            $i = 1;
		              foreach($airport_airport_array as $airport_item)
		              {
		              	if ($i <= 5)
		              	{
			                print '<tr>';
			                	print '<td><strong>'.$i.'</strong></td>';
			                  print '<td>';
			                    print '<a href="'.$globalURL.'/airport/'.$airport_item['airport_departure_icao'].'">'.$airport_item['airport_departure_city'].', '.$airport_item['airport_departure_country'].' ('.$airport_item['airport_departure_icao'].')</a>';
			                  print '</td>';
			                  print '<td>';
			                    print $airport_item['airport_departure_icao_count'];
			                  print '</td>';
			                print '</tr>';
		                }
		                $i++;
		              }
		            print '<tbody>';
		          print '</table>';
		      print '</div>';
		      print '<div class="more"><a href="'.$globalURL.'/date/statistics/departure-airport/'.$_GET['date'].'">View full statistics<i class="fa fa-angle-double-right"></i></a></div>';
	      print '</div>';
	    }
		  
		$airport_airport_array = Spotter::countAllArrivalAirportsByDate($_GET['date']);
	  	if (!empty($airport_airport_array))
	    {
	    	print '<div class="overview-statistic column">';
		    	print '<h3>Top 5 Arrival Airports</h3>';
		      print '<div class="table-responsive">';
		          print '<table class="common-airport">';
              print '<thead>';
                print '<th></th>';
                print '<th>Airport</th>';
                print '<th># of times</th>';
              print '</thead>';
              print '<tbody>';
              $i = 1;
                foreach($airport_airport_array as $airport_item)
                {
                  if ($i <= 5)
		              {
	                  print '<tr>';
	                    print '<td><strong>'.$i.'</strong></td>';
	                    print '<td>';
	                      print '<a href="'.$globalURL.'/airport/'.$airport_item['airport_arrival_icao'].'">'.$airport_item['airport_arrival_city'].', '.$airport_item['airport_arrival_country'].' ('.$airport_item['airport_arrival_icao'].')</a>';
	                    print '</td>';
	                    print '<td>';
	                      print $airport_item['airport_arrival_icao_count'];
	                    print '</td>';
	                  print '</tr>';
                  }
                  $i++;
                }
              print '<tbody>';
            print '</table>';
		      print '</div>';
		      print '<div class="more"><a href="'.$globalURL.'/date/statistics/arrival-airport/'.$_GET['date'].'">View full statistics<i class="fa fa-angle-double-right"></i></a></div>';
	      print '</div>';
	    }
	  
	  
	print '</div>';
	  
	  
	} else {
	
		$title = "Unknown Date";
		require('header.php');
		
		print '<h1>Error</h1>';
	
	  print '<p>Sorry, this date does not exist in this database. :(</p>'; 
	}

}
?>

<?php
require('footer.php');
?>