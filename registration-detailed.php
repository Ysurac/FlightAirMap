<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');

if (!isset($_GET['registration'])){
	header('Location: '.$globalURL.'');
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
	
	$page_url = $globalURL.'/registration/'.$_GET['registration'];
	
	if (isset($_GET['sort'])) {
		$spotter_array = Spotter::getSpotterDataByRegistration($_GET['registration'], $limit_start.",".$absolute_difference, $_GET['sort']);
	} else {
		$spotter_array = Spotter::getSpotterDataByRegistration($_GET['registration'], $limit_start.",".$absolute_difference, '');
	}
	$aircraft_array = Spotter::getAircraftInfoByRegistration($_GET['registration']);
	
	if (!empty($spotter_array))
	{
	  $title = 'Detailed View of aircraft with registration '.$_GET['registration'];
		require('header.php');
	  
	  
	  
		print '<div class="info column">';
			print '<h1>'.$_GET['registration'].' - '.$aircraft_array[0]['aircraft_name'].' ('.$aircraft_array[0]['aircraft_icao'].')</h1>';
			print '<div><span class="label">Name</span><a href="'.$globalURL.'/aircraft/'.$aircraft_array[0]['aircraft_icao'].'">'.$aircraft_array[0]['aircraft_name'].'</a></div>';
			print '<div><span class="label">ICAO</span><a href="'.$globalURL.'/aircraft/'.$aircraft_array[0]['aircraft_icao'].'">'.$aircraft_array[0]['aircraft_icao'].'</a></div>'; 
			print '<div><span class="label">Manufacturer</span><a href="'.$globalURL.'/manufacturer/'.strtolower(str_replace(" ", "-", $aircraft_array[0]['aircraft_manufacturer'])).'">'.$aircraft_array[0]['aircraft_manufacturer'].'</a></div>';
		print '</div>';
		
		include('registration-sub-menu.php');
		
	  print '<div class="table column">';
		  
		 print '<p>The table below shows the detailed information of all flights of aircraft with the registration <strong>'.$_GET['registration'].'</strong>.</p>';
		  
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