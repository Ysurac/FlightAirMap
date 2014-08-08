<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');

if (!isset($_GET['departure_airport']) || !isset($_GET['arrival_airport'])){
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
		
		$page_url = $globalURL.'/route/detailed/'.$_GET['departure_airport'].'/'.$_GET['arrival_airport'];
	
	
	  $spotter_array = Spotter::getSpotterDataByRoute($_GET['departure_airport'], $_GET['arrival_airport'], $limit_start.",".$absolute_difference, $_GET['sort']);
	  
	  if (!empty($spotter_array))
	  {
	  
		  $title = 'Detailed View for flights between '.$spotter_array[0]['departure_airport_name'].' ('.$spotter_array[0]['departure_airport_icao'].'), '.$spotter_array[0]['departure_airport_country'].' - '.$spotter_array[0]['arrival_airport_name'].' ('.$spotter_array[0]['arrival_airport_icao'].'), '.$spotter_array[0]['arrival_airport_country'];
			require('header.php');
		  
				print '<div class="info column">';
					print '<h1>Flights between '.$spotter_array[0]['departure_airport_name'].' ('.$spotter_array[0]['departure_airport_icao'].'), '.$spotter_array[0]['departure_airport_country'].' - '.$spotter_array[0]['arrival_airport_name'].' ('.$spotter_array[0]['arrival_airport_icao'].'), '.$spotter_array[0]['arrival_airport_country'].'</h1>';
	        	print '<div><span class="label">Coming From</span><a href="'.$globalURL.'/airport/'.$spotter_array[0]['departure_airport_icao'].'">'.$spotter_array[0]['departure_airport_name'].' ('.$spotter_array[0]['departure_airport_icao'].'), '.$spotter_array[0]['departure_airport_country'].'</a></div>';
	        	print '<div><span class="label">Flying To</span><a href="'.$globalURL.'/airport/'.$spotter_array[0]['arrival_airport_icao'].'">'.$spotter_array[0]['arrival_airport_name'].' ('.$spotter_array[0]['arrival_airport_icao'].'), '.$spotter_array[0]['arrival_airport_country'].'</a></div>';
	      print '</div>';
	    
	    include('route-sub-menu.php');
	    
	    print '<div class="table column">';
	    
		    print '<p>The table below shows the detailed information of all flights that used the route <strong>'.$spotter_array[0]['departure_airport_icao'].' - '.$spotter_array[0]['arrival_airport_icao'].'</strong>.</p>';
		    
		    include('table-output.php'); 
		    
		    print '<div class="pagination">';
		  	if ($limit_previous_1 >= 0)
		  	{
		  	print '<a href="'.$page_url.'/'.$limit_previous_1.','.$limit_previous_2.'/'.$_GET['sort'].'">&laquo;Previous Page</a>';
		  	}
		  	if ($spotter_array[0]['query_number_rows'] == $absolute_difference)
		  	{
		  		print '<a href="'.$page_url.'/'.$limit_end.','.$limit_next.'/'.$_GET['sort'].'">Next Page&raquo;</a>';
		  	}
		  print '</div>';
	  
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