<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');

if (!isset($_GET['airport'])){
	header('Location: '.$globalURL.'/airport');
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
	
	$page_url = $globalURL.'/airport/detailed/'.$_GET['airport'];
	
	$spotter_array = Spotter::getSpotterDataByAirport($_GET['airport'],$limit_start.",".$absolute_difference, $_GET['sort']);
	$airport_array = Spotter::getAllAirportInfo($_GET['airport']);
	
	if (!empty($airport_array))
	{
	  $title = 'Detailed View for '.$airport_array[0]['city'].', '.$airport_array[0]['name'].' ('.$airport_array[0]['icao'].')';
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
	  
	  print '<div class="table column">';
		  
		  if ($airport_array[0]['iata'] != "NA")
			{
		  	print '<p>The table below shows the detailed information of all flights to/from <strong>'.$airport_array[0]['city'].', '.$airport_array[0]['name'].' ('.$airport_array[0]['icao'].')</strong>.</p>';
		  }
		  
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