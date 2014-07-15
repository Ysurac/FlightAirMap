<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');

if (!isset($_GET['aircraft_type'])){
	header('Location: '.$globalURL.'/aircraft');
} else {
	
	$page_url = $globalURL.'/aircraft/'.$_GET['aircraft_type'];
	
	$spotter_array = Spotter::getSpotterDataByAircraft($_GET['aircraft_type'],"0,15", "");
	
	
	if (!empty($spotter_array))
	{
	    $title = $spotter_array[0]['aircraft_name'].' ('.$spotter_array[0]['aircraft_type'].')';
			require('header.php');
	    
	    date_default_timezone_set('America/Toronto');

	    print '<div class="select-item">';
	  		print '<form action="'.$globalURL.'/aircraft" method="post">';
	  			print '<select name="aircraft_type" class="selectpicker" data-live-search="true">';
    		      print '<option></option>';
    		      $aircraft_types = Spotter::getAllAircraftTypes();
    		      foreach($aircraft_types as $aircraft_type)
    		      {
    		        if($_GET['aircraft_type'] == $aircraft_type['aircraft_icao'])
    		        {
    		          print '<option value="'.$aircraft_type['aircraft_icao'].'" selected="selected">'.$aircraft_type['aircraft_name'].' ('.$aircraft_type['aircraft_icao'].')</option>';
    		        } else {
    		          print '<option value="'.$aircraft_type['aircraft_icao'].'">'.$aircraft_type['aircraft_name'].' ('.$aircraft_type['aircraft_icao'].')</option>';
    		        }
    		      }
    		    print '</select>';
	    		print '<button type="submit"><i class="fa fa-angle-double-right"></i></button>';
	  		print '</form>';
	  	print '</div>';
	    	
	    if ($_GET['aircraft_type'] != "NA")	
		  {
		    print '<div class="info column">';
		    	print '<h1>'.$spotter_array[0]['aircraft_name'].' ('.$spotter_array[0]['aircraft_type'].')</h1>';
		    	print '<div><span class="label">Name</span>'.$spotter_array[0]['aircraft_name'].'</div>';
		    	print '<div><span class="label">ICAO</span>'.$spotter_array[0]['aircraft_type'].'</div>'; 
		    	print '<div><span class="label">Manufacturer</span><a href="'.$globalURL.'/manufacturer/'.strtolower(str_replace(" ", "-", $spotter_array[0]['aircraft_manufacturer'])).'">'.$spotter_array[0]['aircraft_manufacturer'].'</a></div>';
		    print '</div>';
		  } else {
		    print '<div class="alert alert-warning">This special aircraft profile shows all flights in where the aircraft type is unknown.</div>';
		  }
 
	    include('aircraft-sub-menu.php');
	  
	  print '<div class="col-sm-8">';
	  
			 print '<div class="overview-statistic column">';
				 print '<h3>Latest 15 Additions</h3>';
				  
				 include('table-output-small.php');
				 
				 print '<div class="more"><a href="'.$globalURL.'/aircraft/detailed/'.$_GET['aircraft_type'].'">View detailed list<i class="fa fa-angle-double-right"></i></a></div>';
		   print '</div>';
	  
	  print '</div>';
	  
	  print '<div class="col-sm-4">';

	  	$airline_array = Spotter::countAllAirlinesByAircraft($_GET['aircraft_type']);
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
		      print '<div class="more"><a href="'.$globalURL.'/aircraft/statistics/airline/'.$_GET['aircraft_type'].'">View full statistics<i class="fa fa-angle-double-right"></i></a></div>';
	      print '</div>';
	    }

	  	$airport_airport_array = Spotter::countAllDepartureAirportsByAircraft($_GET['aircraft_type']);
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
		      print '<div class="more"><a href="'.$globalURL.'/aircraft/statistics/departure-airport/'.$_GET['aircraft_type'].'">View full statistics<i class="fa fa-angle-double-right"></i></a></div>';
	      print '</div>';
	    }
		  
		  $airport_airport_array = Spotter::countAllArrivalAirportsByAircraft($_GET['aircraft_type']);
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
		      print '<div class="more"><a href="'.$globalURL.'/aircraft/statistics/arrival-airport/'.$_GET['aircraft_type'].'">View full statistics<i class="fa fa-angle-double-right"></i></a></div>';
	      print '</div>';
	    }
	  
	  
	print '</div>';
	  
	  
	  
	} else {
	
		$title = "Aircraft";
		require('header.php');
		
		print '<h1>Error</h1>';
	
	  print '<p>Sorry, the aircraft type does not exist in this database. :(</p>'; 
	}
}	
?>

<?php
require('footer.php');
?>