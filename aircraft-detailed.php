<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');

if (!isset($_GET['aircraft_type'])){
	header('Location: '.$globalURL.'/aircraft');
} else {
	//calculuation for the pagination
	if(!isset($_GET['limit']))
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
	
	$page_url = $globalURL.'/aircraft/'.$_GET['aircraft_type'];
	
	if (isset($_GET['sort'])) {
	$spotter_array = Spotter::getSpotterDataByAircraft($_GET['aircraft_type'],$limit_start.",".$absolute_difference, $_GET['sort']);
	} else {
		$spotter_array = Spotter::getSpotterDataByAircraft($_GET['aircraft_type'],$limit_start.",".$absolute_difference, '');
	}
	
	if (!empty($spotter_array))
	{
	    $title = 'Detailed View for '.$spotter_array[0]['aircraft_name'].' ('.$spotter_array[0]['aircraft_type'].')';
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
	  
	  
	  print '<div class="table column">';	  
		  print '<p>The table below shows the detailed information of all flights from <strong>'.$spotter_array[0]['aircraft_name'].' ('.$spotter_array[0]['aircraft_type'].')</strong>.</p>';
		  
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
	
		$title = "Aircraft";
		require('header.php');
		
		print '<h1>Errorss</h1>';
		
	  print '<p>Sorry, the aircraft type does not exist in this database. :(</p>'; 
	}
	
}
?>

<?php
require('footer.php');
?>