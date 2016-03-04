<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');

if (!isset($_GET['country'])){
	header('Location: '.$globalURL.'');
} else {
	$Spotter = new Spotter();
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
	
	$country = ucwords(str_replace("-", " ", $_GET['country']));
	
	$page_url = $globalURL.'/country/'.$_GET['country'];
	
	if (isset($_GET['sort'])) {
		$spotter_array = $Spotter->getSpotterDataByCountry($country, $limit_start.",".$absolute_difference, $_GET['sort']);
	} else {
		$spotter_array = $Spotter->getSpotterDataByCountry($country, $limit_start.",".$absolute_difference, '');
	}
	
	
	if (!empty($spotter_array))
	{
	  $title = 'Detailed View for Airports &amp; Airlines from '.$country;
		require_once('header.php');
	  
	  
	  
	  print '<div class="select-item">';
		print '<form action="'.$globalURL.'/country" method="post">';
			print '<select name="country" class="selectpicker" data-live-search="true">';
	      print '<option></option>';
	      $all_countries = $Spotter->getAllCountries();
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
		
	  print '<div class="table column">';
		  
		 print '<p>The table below shows the detailed information of all flights of airports (both departure &amp; arrival) OR airlines from <strong>'.$country.'</strong>.</p>';
		  
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
	
		$title = "Country";
		require_once('header.php');
		
		print '<h1>Error</h1>';
	
	  print '<p>Sorry, the country does not exist in this database. :(</p>'; 
	}
}

require_once('footer.php');
?>