<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');

if (!isset($_GET['ident'])){
	header('Location: '.$globalURL.'');
} else {

	$page_url = $globalURL.'/ident/'.$_GET['ident'];
	
	$spotter_array = Spotter::getSpotterDataByIdent($_GET['ident'],"0,15", "");
	
	if (!empty($spotter_array))
	{
	    $title = $spotter_array[0]['ident'];
			require('header.php');
	    
	    date_default_timezone_set('America/Toronto');
	    
	      print '<div class="info column">';
	      	print '<h1>'.$spotter_array[0]['ident'].'</h1>';
	      	print '<div><span class="label">Ident</span>'.$spotter_array[0]['ident'].'</div>';
	      	print '<div><span class="label">Airline</span><a href="'.$globalURL.'/airline/'.$spotter_array[0]['airline_icao'].'">'.$spotter_array[0]['airline_name'].'</a></div>'; 
	      	print '<div><span class="label">Flight History</span><a href="http://flightaware.com/live/flight/'.$spotter_array[0]['ident'].'" target="_blank">View the Flight History of this callsign</a></div>';       
				print '</div>';
	
			include('ident-sub-menu.php');
	  
	  print '<div class="col-sm-8">';
	  
			 print '<div class="overview-statistic column">';
				 print '<h3>Latest 15 Additions</h3>';
				  
				 include('table-output-small.php');
				 
				 print '<div class="more"><a href="'.$globalURL.'/ident/detailed/'.$_GET['ident'].'">View detailed list<i class="fa fa-angle-double-right"></i></a></div>';
		   print '</div>';
	  
	  print '</div>';
	  
	  print '<div class="col-sm-4">';

	  	$airline_array = Spotter::countAllAirlinesByIdent($_GET['ident']);
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
		      print '<div class="more"><a href="'.$globalURL.'/ident/statistics/airline/'.$_GET['ident'].'">View full statistics<i class="fa fa-angle-double-right"></i></a></div>';
	      print '</div>';
	    }
	    
	    $aircraft_array = Spotter::countAllAircraftTypesByIdent($_GET['ident']);
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
		      print '<div class="more"><a href="'.$globalURL.'/ident/statistics/aircraft/'.$_GET['ident'].'">View full statistics<i class="fa fa-angle-double-right"></i></a></div>';
	      print '</div>';
	    }

	  	$airport_airport_array = Spotter::countAllDepartureAirportsByIdent($_GET['ident']);
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
		      print '<div class="more"><a href="'.$globalURL.'/ident/statistics/departure-airport/'.$_GET['ident'].'">View full statistics<i class="fa fa-angle-double-right"></i></a></div>';
	      print '</div>';
	    }
		  
		$airport_airport_array = Spotter::countAllArrivalAirportsByIdent($_GET['ident']);
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
		      print '<div class="more"><a href="'.$globalURL.'/ident/statistics/arrival-airport/'.$_GET['ident'].'">View full statistics<i class="fa fa-angle-double-right"></i></a></div>';
	      print '</div>';
	    }
	  
	  
	print '</div>';
	  
	  
	} else {
	
		$title = "Ident";
		require('header.php');
		
		print '<h1>Error</h1>';
	
	  print '<p>Sorry, this ident/callsign is not in the database. :(</p>'; 
	}
}


?>

<?php
require('footer.php');
?>