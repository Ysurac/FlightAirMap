<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');

if (!isset($_GET['registration'])){
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
	
	$spotter_array = Spotter::getSpotterDataByRegistration($_GET['registration'], "0,15", "");
	$aircraft_array = Spotter::getAircraftInfoByRegistration($_GET['registration']);
	
	
	if (!empty($spotter_array))
	{
	  $title = 'Aircraft Registration '.$_GET['registration'];
		require('header.php');
	  
	  date_default_timezone_set('America/Toronto');
	  
		print '<div class="info column">';
			print '<h1>'.$_GET['registration'].' - '.$aircraft_array[0]['aircraft_name'].' ('.$aircraft_array[0]['aircraft_icao'].')</h1>';
			print '<div><span class="label">Name</span><a href="'.$globalURL.'/aircraft/'.$aircraft_array[0]['aircraft_icao'].'">'.$aircraft_array[0]['aircraft_name'].'</a></div>';
			print '<div><span class="label">ICAO</span><a href="'.$globalURL.'/aircraft/'.$aircraft_array[0]['aircraft_icao'].'">'.$aircraft_array[0]['aircraft_icao'].'</a></div>'; 
			print '<div><span class="label">Manufacturer</span><a href="'.$globalURL.'/manufacturer/'.strtolower(str_replace(" ", "-", $aircraft_array[0]['aircraft_manufacturer'])).'">'.$aircraft_array[0]['aircraft_manufacturer'].'</a></div>';
		print '</div>';
		
		include('registration-sub-menu.php');
		
	  print '<div class="col-sm-8">';
	  
			 print '<div class="overview-statistic column">';
				 print '<h3>Latest 15 Additions</h3>';
				  
				 include('table-output-small.php');
				 
				 print '<div class="more"><a href="'.$globalURL.'/registration/detailed/'.$_GET['registration'].'">View detailed list<i class="fa fa-angle-double-right"></i></a></div>';
		   print '</div>';
	  
	  print '</div>';
	  
	  print '<div class="col-sm-4">';

	  	$airport_airport_array = Spotter::countAllDepartureAirportsByRegistration($_GET['registration']);
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
		      print '<div class="more"><a href="'.$globalURL.'/registration/statistics/departure-airport/'.$_GET['registration'].'">View full statistics<i class="fa fa-angle-double-right"></i></a></div>';
	      print '</div>';
	    }
		  
		$airport_airport_array = Spotter::countAllArrivalAirportsByRegistration($_GET['registration']);
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
		      print '<div class="more"><a href="'.$globalURL.'/registration/statistics/arrival-airport/'.$_GET['registration'].'">View full statistics<i class="fa fa-angle-double-right"></i></a></div>';
	      print '</div>';
	    }
	  
	  
	print '</div>';
	  
	} else {
	
		$title = "Registration";
		require('header.php');
		
		print '<h1>Error</h1>';
	
	  print '<p>Sorry, this registration does not exist in this database. :(</p>';  
	}
}


?>

<?php
require('footer.php');
?>