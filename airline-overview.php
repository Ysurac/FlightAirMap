<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');

if (!isset($_GET['airline'])){
	header('Location: '.$globalURL.'/airline');
} else {
	
	$page_url = $globalURL.'/airline/'.$_GET['airline'];
	
	$spotter_array = Spotter::getSpotterDataByAirline($_GET['airline'],"0,15", "");
	
	
	if (!empty($spotter_array))
	{
	  $title = $spotter_array[0]['airline_name'].' ('.$spotter_array[0]['airline_icao'].')';
		require('header.php');
	  
	  date_default_timezone_set('America/Toronto');
	  
	  print '<div class="select-item">';
	  		print '<form action="'.$globalURL.'/airline" method="post">';
	  			print '<select name="airline" class="selectpicker" data-live-search="true">';
    		      print '<option></option>';
    		      $airline_names = Spotter::getAllAirlineNames();
    		      foreach($airline_names as $airline_name)
    		      {
    		        if($_GET['airline'] == $airline_name['airline_icao'])
    		        {
    		          print '<option value="'.$airline_name['airline_icao'].'" selected="selected">'.$airline_name['airline_name'].' ('.$airline_name['airline_icao'].')</option>';
    		        } else {
    		          print '<option value="'.$airline_name['airline_icao'].'">'.$airline_name['airline_name'].' ('.$airline_name['airline_icao'].')</option>';
    		        }
    		      }
    		    print '</select>';
	    		print '<button type="submit"><i class="fa fa-angle-double-right"></i></button>';
	  		print '</form>';
	  	print '</div>';
		
		if ($_GET['airline'] != "NA")
		{
			print '<div class="info column">';
				print '<h1>'.$spotter_array[0]['airline_name'].' ('.$spotter_array[0]['airline_icao'].')</h1>';
				if (@getimagesize($globalURL.'/images/airlines/'.$spotter_array[0]['airline_icao'].'.png'))
				{
					print '<img src="'.$globalURL.'/images/airlines/'.$spotter_array[0]['airline_icao'].'.png" alt="'.$spotter_array[0]['airline_name'].' ('.$spotter_array[0]['airline_icao'].')" title="'.$spotter_array[0]['airline_name'].' ('.$spotter_array[0]['airline_icao'].')" class="logo" />';
				}
				print '<div><span class="label">Name</span>'.$spotter_array[0]['airline_name'].'</div>';
				print '<div><span class="label">Country</span>'.$spotter_array[0]['airline_country'].'</div>';
				print '<div><span class="label">ICAO</span>'.$spotter_array[0]['airline_icao'].'</div>';
				print '<div><span class="label">IATA</span>'.$spotter_array[0]['airline_iata'].'</div>';
				print '<div><span class="label">Callsign</span>'.$spotter_array[0]['airline_callsign'].'</div>'; 
				print '<div><span class="label">Type</span>'.ucwords($spotter_array[0]['airline_type']).'</div>';        
			print '</div>';
		} else {
		print '<div class="alert alert-warning">This special airline profile shows all flights that do <u>not</u> have a airline associated with them.</div>';
		}

	  
	  include('airline-sub-menu.php');
	
	  print '<div class="col-sm-8">';
	  
			 print '<div class="overview-statistic column">';
				 print '<h3>Latest 15 Additions</h3>';
				  
				 include('table-output-small.php');
				 
				 print '<div class="more"><a href="'.$globalURL.'/airline/detailed/'.$_GET['airline'].'">View detailed list<i class="fa fa-angle-double-right"></i></a></div>';
		   print '</div>';
	  
	  print '</div>';
	  
	  print '<div class="col-sm-4">';

	  	$aircraft_array = Spotter::countAllAircraftTypesByAirline($_GET['airline']);
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
		      print '<div class="more"><a href="'.$globalURL.'/airline/statistics/aircraft/'.$_GET['airline'].'">View full statistics<i class="fa fa-angle-double-right"></i></a></div>';
	      print '</div>';
	    }

	  	$airport_airport_array = Spotter::countAllDepartureAirportsByAirline($_GET['airline']);
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
		      print '<div class="more"><a href="'.$globalURL.'/airline/statistics/departure-airport/'.$_GET['airline'].'">View full statistics<i class="fa fa-angle-double-right"></i></a></div>';
	      print '</div>';
	    }
		  
		$airport_airport_array = Spotter::countAllArrivalAirportsByAirline($_GET['airline']);
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
		      print '<div class="more"><a href="'.$globalURL.'/airline/statistics/arrival-airport/'.$_GET['airline'].'">View full statistics<i class="fa fa-angle-double-right"></i></a></div>';
	      print '</div>';
	    }
	  
	  
	print '</div>';
	  
	  
	} else {
	
		$title = "Airline";
		require('header.php');
		
		print '<h1>Error</h1>';
	
	  print '<p>Sorry, the airline does not exist in this database. :(</p>'; 
	}

}


?>

<?php
require('footer.php');
?>