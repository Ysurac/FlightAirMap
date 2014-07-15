<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');

if (!isset($_GET['country'])){
	header('Location: '.$globalURL.'');
} else {

	//calculuation for the pagination
	if($_GET['limit'] == "")
	{
	  $limit_start = 0;
	  $limit_end = 25;
	  $absolute_difference = 25;
	}  else {
		$limit_explode = explode(",", $_GET['limit']);
		$limit_start = $limit_explode[0];
		$limit_end = $limit_explode[1];
	}
	$absolute_difference = abs($limit_start - $limit_end);
	$limit_next = $limit_end + $absolute_difference;
	$limit_previous_1 = $limit_start - $absolute_difference;
	$limit_previous_2 = $limit_end - $absolute_difference;
	
	$country = ucwords(str_replace("-", " ", $_GET['country']));
	
	$page_url = $globalURL.'/country/'.$_GET['country'];
	
	$spotter_array = Spotter::getSpotterDataByCountry($country, "0,15", "");
	
	
	if (!empty($spotter_array))
	{
	  $title = 'Airports &amp; Airlines from '.$country;
		require('header.php');
	  
	  date_default_timezone_set('America/Toronto');
	  
	  print '<div class="select-item">';
		print '<form action="'.$globalURL.'/country" method="post">';
			print '<select name="country" class="selectpicker" data-live-search="true">';
	      print '<option></option>';
	      $all_countries = Spotter::getAllCountries();
	      foreach($all_countries as $all_country)
	      {
	        if($country == $all_country['country'])
	        {
	          print '<option value="'.strtolower(str_replace(" ", "-", $all_country['country'])).'" selected="selected">'.$all_country['country'].'</option>';
	        } else {
	          print '<option value="'.strtolower(str_replace(" ", "-", $all_country['country'])).'">'.$all_country['country'].'</option>';
	        }
	      }
	    print '</select>';
		print '<button type="submit"><i class="fa fa-angle-double-right"></i></button>';
		print '</form>';
	  print '</div>';
	  
	  if ($_GET['country'] != "NA")
	  {
		print '<div class="info column">';
			print '<h1>Airports &amp; Airlines from '.$country.'</h1>';
		print '</div>';
	  } else {
		  print '<div class="alert alert-warning">This special country profile shows all flights that do <u>not</u> have a country of a airline or departure/arrival airport associated with them.</div>';
	  }
		
		include('country-sub-menu.php');
		
	  print '<div class="col-sm-8">';
	  
			 print '<div class="overview-statistic column">';
				 print '<h3>Latest 15 Additions</h3>';
				  
				 include('table-output-small.php');
				 
				 print '<div class="more"><a href="'.$globalURL.'/country/detailed/'.$_GET['country'].'">View detailed list<i class="fa fa-angle-double-right"></i></a></div>';
		   print '</div>';
	  
	  print '</div>';
	  
	  print '<div class="col-sm-4">';

	  	$airline_array = Spotter::countAllAirlinesByCountry($country);
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
		      print '<div class="more"><a href="'.$globalURL.'/country/statistics/airline/'.$_GET['country'].'">View full statistics<i class="fa fa-angle-double-right"></i></a></div>';
	      print '</div>';
	    }
	    
	    $aircraft_array = Spotter::countAllAircraftTypesByCountry($country);
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
		      print '<div class="more"><a href="'.$globalURL.'/country/statistics/aircraft/'.$_GET['country'].'">View full statistics<i class="fa fa-angle-double-right"></i></a></div>';
	      print '</div>';
	    }

	  	$airport_airport_array = Spotter::countAllDepartureAirportsByCountry($country);
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
		      print '<div class="more"><a href="'.$globalURL.'/country/statistics/departure-airport/'.$_GET['country'].'">View full statistics<i class="fa fa-angle-double-right"></i></a></div>';
	      print '</div>';
	    }
		  
		$airport_airport_array = Spotter::countAllArrivalAirportsByCountry($country);
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
		      print '<div class="more"><a href="'.$globalURL.'/country/statistics/arrival-airport/'.$_GET['country'].'">View full statistics<i class="fa fa-angle-double-right"></i></a></div>';
	      print '</div>';
	    }
	  
	  
	print '</div>';
	  
	} else {
	
		$title = "Country";
		require('header.php');
		
		print '<h1>Error</h1>';
	
	  print '<p>Sorry, the country does not exist in this database. :(</p>'; 
	}
}


?>

<?php
require('footer.php');
?>