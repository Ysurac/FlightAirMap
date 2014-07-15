<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');

if (!isset($_GET['date'])){
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
	
	$page_url = $globalURL.'/date/detailed/'.$_GET['date'];
	
	$spotter_array = Spotter::getSpotterDataByDate($_GET['date'],$limit_start.",".$absolute_difference, $_GET['sort']);
	
	
	if (!empty($spotter_array))
	{
	    date_default_timezone_set('America/Toronto');
	    
	    $title = 'Detailed View for flights from '.date("l F j, Y", strtotime($spotter_array[0]['date_iso_8601']));
			require('header.php');
			
		print '<div class="select-item">';
	  		print '<form action="'.$globalURL.'/date" method="post">';
	  			print '<label for="date">Select a Date</label>';
	    		print '<input type="text" id="date" name="date" value="'.$_GET['date'].'" size="8" readonly="readonly" class="custom" />';
	    		print '<button type="submit"><i class="fa fa-angle-double-right"></i></button>';
	  		print '</form>';
	  	print '</div>';
	    
	    print '<div class="info column">';
	    	print '<h1>Flights from '.date("l F j, Y", strtotime($spotter_array[0]['date_iso_8601'])).'</h1>';
	    print '</div>';
	    
	    include('date-sub-menu.php');
	  
	  print '<div class="table column">';
	  
		  print '<p>The table below shows the detailed information of all flights on <strong>'.date("l M j, Y", strtotime($spotter_array[0]['date_iso_8601'])).'</strong>.</p>';
		  
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
	
		$title = "Unknown Date";
		require('header.php');
		
		print '<h1>Error</h1>';
	
	  print '<p>Sorry, this date does not exist in this database. :(</p>'; 
	}

}
?>

<?php
require('footer.php');
?>