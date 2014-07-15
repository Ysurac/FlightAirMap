<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');

if (!isset($_GET['airport'])){
	header('Location: '.$globalURL.'/airport');
} else {

	$page_url = $globalURL.'/airport/'.$_GET['airport'];
	
	$spotter_array = Spotter::getSpotterDataByAirport($_GET['airport'],"0,15", "");
	$airport_array = Spotter::getAllAirportInfo($_GET['airport']);
	
	if (!empty($airport_array))
	{
	  $title = $airport_array[0]['city'].', '.$airport_array[0]['name'].' ('.$airport_array[0]['icao'].')';
		require('header.php');
	  
	  date_default_timezone_set('America/Toronto');
	  
	  print '<div class="select-item">';
  		print '<form action="'.$globalURL.'/airport" method="post">';
  			print '<select name="airport" class="selectpicker" data-live-search="true">';
		      print '<option></option>';
		      $airport_names = Spotter::getAllAirportNames();
		      ksort($airport_names);
		      foreach($airport_names as $airport_name)
		      {
		        if($_GET['airport'] == $airport_name['airport_icao'])
		        {
		          print '<option value="'.$airport_name['airport_icao'].'" selected="selected">'.$airport_name['airport_city'].', '.$airport_name['airport_name'].', '.$airport_name['airport_country'].' ('.$airport_name['airport_icao'].')</option>';
		        } else {
		          print '<option value="'.$airport_name['airport_icao'].'">'.$airport_name['airport_city'].', '.$airport_name['airport_name'].', '.$airport_name['airport_country'].' ('.$airport_name['airport_icao'].')</option>';
		        }
		      }
		    print '</select>';
    		print '<button type="submit"><i class="fa fa-angle-double-right"></i></button>';
  		print '</form>';
  	print '</div>';
		
		if ($_GET['airport'] != "NA")
		{
	    print '<div class="info column">';
	    	print '<h1>'.$airport_array[0]['city'].', '.$airport_array[0]['name'].' ('.$airport_array[0]['icao'].')</h1>';
	    	print '<div><span class="label">Name</span>'.$airport_array[0]['name'].'</div>';
    	print '<div><span class="label">City</span>'.$airport_array[0]['city'].'</div>';
    	print '<div><span class="label">Country</span>'.$airport_array[0]['country'].'</div>';
    	print '<div><span class="label">ICAO</span>'.$airport_array[0]['icao'].'</div>';
    	print '<div><span class="label">IATA</span>'.$airport_array[0]['iata'].'</div>';
    	print '<div><span class="label">Altitude</span>'.$airport_array[0]['altitude'].'</div>';
    	print '<div><span class="label">Coordinates</span><a href="http://maps.google.ca/maps?z=10&t=k&q='.$airport_array[0]['latitude'].','.$airport_array[0]['longitude'].'" target="_blank">Google Map<i class="fa fa-angle-double-right"></i></a></div>';
	    print '</div>';
	  } else {
	    print '<div class="alert alert-warning">This special airport profile shows all flights that do <u>not</u> have a departure and/or arrival airport associated with them.</div>';
	  }
	  
	  include('airport-sub-menu.php');
	  
	  print '<div class="col-sm-8">';
	  
			 print '<div class="overview-statistic column">';
				 print '<h3>Latest 15 Additions</h3>';
				  
				 include('table-output-small.php');
				 
				 print '<div class="more"><a href="'.$globalURL.'/airport/detailed/'.$_GET['airport'].'">View detailed list<i class="fa fa-angle-double-right"></i></a></div>';
		   print '</div>';
	  
	  print '</div>';
	  
	  print '<div class="col-sm-4">';

	  	$airline_array = Spotter::countAllAirlinesByAirport($_GET['airport']);
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
		      print '<div class="more"><a href="'.$globalURL.'/airport/statistics/airline/'.$_GET['airport'].'">View full statistics<i class="fa fa-angle-double-right"></i></a></div>';
	      print '</div>';
	    }
	    
	    $aircraft_array = Spotter::countAllAircraftTypesByAirport($_GET['airport']);
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
		      print '<div class="more"><a href="'.$globalURL.'/airport/statistics/aircraft/'.$_GET['airport'].'">View full statistics<i class="fa fa-angle-double-right"></i></a></div>';
	      print '</div>';
	    }

	  	$airport_airport_array = Spotter::countAllDepartureAirportsByAirport($_GET['airport']);
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
		      print '<div class="more"><a href="'.$globalURL.'/airport/statistics/departure-airport/'.$_GET['airport'].'">View full statistics<i class="fa fa-angle-double-right"></i></a></div>';
	      print '</div>';
	    }
		  
		$airport_airport_array = Spotter::countAllArrivalAirportsByAirport($_GET['airport']);
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
		      print '<div class="more"><a href="'.$globalURL.'/airport/statistics/arrival-airport/'.$_GET['airport'].'">View full statistics<i class="fa fa-angle-double-right"></i></a></div>';
	      print '</div>';
	    }
	  
	  
	print '</div>';
	
	  
	} else {
	
		$title = "Airport";
		require('header.php');
		
		print '<h1>Error</h1>';
	
	  print '<p>Sorry, the airport does not exist in this database. :(</p>'; 
	}
	
}
?>

<?php
require('footer.php');
?>