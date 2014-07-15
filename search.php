<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');

$orderby = Spotter::getOrderBy();

$title = "Search";

$page_url = "http://".$_SERVER[HTTP_HOST].$_SERVER[REQUEST_URI];

//$title = "Search";
require('header.php');

//for the date manipulation into the query
if($_GET['start_date'] != "" && $_GET['end_date'] != ""){
	$start_date = $_GET['start_date'].":00";
	$end_date = $_GET['end_date'].":00";
  $sql_date = $start_date.",".$end_date;
} else if($_GET['start_date'] != ""){
	$start_date = $_GET['start_date'].":00";
  $sql_date = $start_date;
} else if($_GET['start_date'] == "" && $_GET['end_date'] != ""){
	$end_date = date("Y-m-d H:i:s", strtotime("2014-04-12")).",".$_GET['end_date'].":00";
  $sql_date = $end_date;
}

//for altitude manipulation
if($_GET['highest_altitude'] != "" && $_GET['lowest_altitude'] != ""){
	$end_altitude = $_GET['highest_altitude'];
	$start_altitude = $_GET['lowest_altitude'];
  $sql_altitude = $start_altitude.",".$end_altitude;
} else if($_GET['highest_altitude'] != ""){
	$end_altitude = $_GET['highest_altitude'];
	$sql_altitude = $end_altitude;
} else if($_GET['highest_altitude'] == "" && $_GET['lowest_altitude'] != ""){
	$start_altitude = $_GET['lowest_altitude'].",60000";
	$sql_altitude = $start_altitude;
}

//calculuation for the pagination
if($_GET['limit'] == "")
{
  if ($_GET['number_results'] == "")
  {
  $limit_start = 0;
  $limit_end = 25;
  $absolute_difference = 25;
  } else {
	if ($_GET['number_results'] > 100){
		$_GET['number_results'] = 100;
	}
	$limit_start = 0;
	$limit_end = $_GET['number_results'];
	$absolute_difference = $_GET['number_results'];
  }
}  else {
	$limit_explode = explode(",", $_GET['limit']);
	$limit_start = $limit_explode[0];
	$limit_end = $limit_explode[1];
}
$absolute_difference = abs($limit_start - $limit_end);
$limit_next = $limit_end + $absolute_difference;
$limit_previous_1 = $limit_start - $absolute_difference;
$limit_previous_2 = $limit_end - $absolute_difference;
?>


 <?php
  if (!empty($_GET)){  
	  $spotter_array = Spotter::searchSpotterData($_GET['q'],$_GET['registration'],$_GET['aircraft'],strtolower(str_replace("-", " ", $_GET['manufacturer'])),$_GET['highlights'],$_GET['photo'],$_GET['airline'],$_GET['airline_country'],$_GET['airline_type'],$_GET['airport'],$_GET['airport_country'],$_GET['callsign'],$_GET['departure_airport_route'],$_GET['arrival_airport_route'],$sql_altitude,$sql_date,$limit_start.",".$absolute_difference,$_GET['sort'],'');
	  
	 print '<span class="sub-menu-statistic column mobile">';
	 	print '<a href="#" onclick="showSubMenu(); return false;">Export <i class="fa fa-plus"></i></a>';
	 	print '</span>';
	 	print '<div class="sub-menu sub-menu-container">';
	 		print '<ul class="nav">';
	 			print '<li class="dropdown">';
		    	print '<a class="dropdown-toggle" data-toggle="dropdown" href="#" ><i class="fa fa-download"></i> Export <span class="caret"></span></a>';
				    print '<ul class="dropdown-menu">';
				      print '<li><a href="'.$globalURL.'/search/csv?'.htmlentities($_SERVER[QUERY_STRING]).'" target="_blank">CSV</a></li>';
				      print '<li><a href="'.$globalURL.'/search/rss?'.htmlentities($_SERVER[QUERY_STRING]).'" target="_blank">RSS</a></li>';
				      print '<li><hr /></li>';
				      print '<li><span>For Advanced Users</strong></li>';
				      print '<li><a href="'.$globalURL.'/search/json?'.htmlentities($_SERVER[QUERY_STRING]).'" target="_blank">JSON</a></li>';
				      print '<li><a href="'.$globalURL.'/search/xml?'.htmlentities($_SERVER[QUERY_STRING]).'" target="_blank">XML</a></li>';
				      print '<li><a href="'.$globalURL.'/search/yaml?'.htmlentities($_SERVER[QUERY_STRING]).'" target="_blank">YAML</a></li>';
				      print '<li><a href="'.$globalURL.'/search/php?'.htmlentities($_SERVER[QUERY_STRING]).'" target="_blank">PHP (serialized array)</a></li>';
				      print '<li><hr /></li>';
				      print '<li><span>For Geo/Map Users</span></li>';
				      print '<li><a href="'.$globalURL.'/search/kml?'.htmlentities($_SERVER[QUERY_STRING]).'" target="_blank">KML (for Google Earth)</a></li>';
				      print '<li><a href="'.$globalURL.'/search/geojson?'.htmlentities($_SERVER[QUERY_STRING]).'" target="_blank">GeoJSON</a></li>';
				      print '<li><a href="'.$globalURL.'/search/georss?'.htmlentities($_SERVER[QUERY_STRING]).'" target="_blank">GeoRSS</a></li>';
				      print '<li><a href="'.$globalURL.'/search/gpx?'.htmlentities($_SERVER[QUERY_STRING]).'" target="_blank">GPX</a></li>';
				      print '<li><a href="'.$globalURL.'/search/wkt?'.htmlentities($_SERVER[QUERY_STRING]).'" target="_blank">WKT (route data only)</a></li>';
				      print '<li><hr /></li>';
				      print '<li><a href="'.$globalURL.'/about/export" target="_blank" class="export-info">Export Info/Licence&raquo;</a></li>';
				    print '</ul>';
				print '</li>';
				print '<li class="short-url">';
				//print 'http://'.$_SERVER[HTTP_HOST].''.$_SERVER[REQUEST_URI];
					$bitly = Spotter::getBitlyURL(urlencode('http://'.$_SERVER[HTTP_HOST].''.$_SERVER[REQUEST_URI]));
					print 'Short URL: <input type="text" name="short_url" value="'.$bitly.'" readonly="readonly" />';
				print '</li>';
				print '</ul>';
		print '</div>';
	  
	  if (!empty($spotter_array))
	  {	  		
	  	 print '<div class="column">';
    	  	 print '<div class="info">';
    	  	    print '<h1>Search Results for ';
    		  	if ($_GET['q'] != ""){ print 'keyword: <span>'.$_GET['q'].'</span> '; }
    		  	if ($_GET['aircraft'] != ""){ print 'aircraft: <span>'.$_GET['aircraft'].'</span> '; }
    		  	if ($_GET['manufacturer'] != ""){ print 'manufacturer: <span>'.$_GET['manufacturer'].'</span> '; }
    		  	if ($_GET['registration'] != ""){ print 'registration: <span>'.$_GET['registration'].'</span> '; }
    		  	if ($_GET['highlights'] == "true"){ print 'highlights: <span>'.$_GET['highlights'].'</span> '; }
    		  	if ($_GET['photo'] == "true"){ print 'photo: <span>'.$_GET['photo'].'</span> '; }
				if ($_GET['airline'] != ""){ print 'airline: <span>'.$_GET['airline'].'</span> '; }
				if ($_GET['airline_country'] != ""){ print 'airline country: <span>'.$_GET['airline_country'].'</span> '; }
				if ($_GET['airline_type'] != ""){ print 'airline type: <span>'.$_GET['airline_type'].'</span> '; }
				if ($_GET['airport'] != ""){ print 'airport: <span>'.$_GET['airport'].'</span> '; }
				if ($_GET['airport_country'] != ""){ print 'airport country: <span>'.$_GET['airport_country'].'</span> '; }
				if ($_GET['callsign'] != ""){ print 'callsign: <span>'.$_GET['callsign'].'</span> '; }
				if ($_GET['departure_airport_route'] != "" && $_GET['arrival_airport_route'] == ""){ print 'route out of: <span>'.$_GET['departure_airport_route'].'</span> '; }
				if ($_GET['departure_airport_route'] == "" && $_GET['arrival_airport_route'] != ""){ print 'route into: <span>'.$_GET['arrival_airport_route'].'</span> '; }
				if ($_GET['departure_airport_route'] != "" && $_GET['arrival_airport_route'] != ""){ print 'route between: <span>'.$_GET['departure_airport_route'].'</span> and <span>'.$_GET['arrival_airport_route'].'</span> '; }
				if ($_GET['start_date'] != "" && $_GET['end_date'] == ""){ print 'date starting at: <span>'.$_GET['start_date'].'</span> '; }
				if ($_GET['start_date'] == "" && $_GET['end_date'] != ""){ print 'date ending at: <span>'.$_GET['end_date'].'</span> '; }
				if ($_GET['start_date'] != "" && $_GET['end_date'] != ""){ print 'date between: <span>'.$_GET['start_date'].'</span> and <span>'.$_GET['end_date'].'</span> '; }
				if ($_GET['lowest_altitude'] != "" && $_GET['highest_altitude'] == ""){ print 'altitude starting at: <span>'.number_format($_GET['lowest_altitude']).' feet</span> '; }
				if ($_GET['lowest_altitude'] == "" && $_GET['highest_altitude'] != ""){ print 'altitude ending at: <span>'.number_format($_GET['highest_altitude']).' feet</span> '; }
				if ($_GET['lowest_altitude'] != "" && $_GET['highest_altitude'] != ""){ print 'altitude between: <span>'.number_format($_GET['lowest_altitude']).' feet</span> and <span>'.number_format($_GET['highest_altitude']).' feet</span> '; }
				if ($_GET['number_results'] != ""){ print 'limit per page: <span>'.$_GET['number_results'].'</span> '; }
    		    print '</h1>';
    		  print '</div>';
    	  
    	  	include('table-output.php'); 
    			
    			$_SERVER['QUERY_STRING'] = preg_replace('/&?limit=[^&]*/', '', $_SERVER['QUERY_STRING']);
    			print '<div class="pagination">';
        	if ($limit_previous_1 >= 0)
        	{
        	print '<a href="'.$globalURL.'/search?'.$_SERVER['QUERY_STRING'].'&limit='.$limit_previous_1.','.$limit_previous_2.'">&laquo;Previous Page</a>';
        	}
        	if ($spotter_array[0]['query_number_rows'] == $absolute_difference)
        	{
        		print '<a href="'.$globalURL.'/search?'.$_SERVER['QUERY_STRING'].'&limit='.$limit_end.','.$limit_next.'">Next Page&raquo;</a>';
        	}
        print '</div>';
    
    print '</div>';
			
			
	  } else {
	      
	      print '<div class="column">';
    	  	 print '<div class="info">';
    	  	    print '<h1>Search</h1>';
    	  	print '</div>';
    	  	print '<p>Sorry, your search did not produce any results. :(</p>'; 
    	  print '</div>';
    	  	 
	  }
  } else {
    print '<div class="info column">';
     	print '<h1>Search</h1>';
     print '</div>';
  }
  ?>


<div class="column">
  <form action="<?php print $globalURL; ?>/search" method="get">
    <fieldset>
    	<div>
	    	<label>Keyword</label> 
		    <input type="text" id="q" name="q" value="<?php print $_GET['q']; ?>" size="10" />
		  </div>
    </fieldset>
    <div class="advanced-form">
        <fieldset>
        	<legend>Aircraft</legend>
        	<div>
    	    	<label>Manufacturer</label> 
    		    <select name="manufacturer" class="selectpicker" data-live-search="true">
    		      <option></option>
    		      <?php
    		      $manufacturers = Spotter::getAllManufacturers();
    		      foreach($manufacturers as $manufacturer)
    		      {
    		        if($_GET['manufacturer'] == strtolower(str_replace(" ", "-", $manufacturer['aircraft_manufacturer'])))
    		        {
    		          print '<option value="'.strtolower(str_replace(" ", "-", $manufacturer['aircraft_manufacturer'])).'" selected="selected">'.$manufacturer['aircraft_manufacturer'].'</option>';
    		        } else {
    		          print '<option value="'.strtolower(str_replace(" ", "-", $manufacturer['aircraft_manufacturer'])).'">'.$manufacturer['aircraft_manufacturer'].'</option>';
    		        }
    		      }
    		      ?>
    		    </select>
    		  </div>
    	    <div>
    	    	<label>Type</label> 
    		    <select name="aircraft" class="selectpicker" data-live-search="true">
    		      <option></option>
    		      <?php
    		      $aircraft_types = Spotter::getAllAircraftTypes();
    		      foreach($aircraft_types as $aircraft_type)
    		      {
    		        if($_GET['aircraft'] == $aircraft_type['aircraft_icao'])
    		        {
    		          print '<option value="'.$aircraft_type['aircraft_icao'].'" selected="selected">'.$aircraft_type['aircraft_name'].' ('.$aircraft_type['aircraft_icao'].')</option>';
    		        } else {
    		          print '<option value="'.$aircraft_type['aircraft_icao'].'">'.$aircraft_type['aircraft_name'].' ('.$aircraft_type['aircraft_icao'].')</option>';
    		        }
    		      }
    		      ?>
    		    </select>
    		  </div>
    		   <div>
    		  	<label>Registration</label> 
    		  	<input type="text" name="registration" value="<?php print $_GET['registration']; ?>" size="8" />
    			</div>
    			<div class="checkbox">
    				<div><input type="checkbox" name="highlights" value="true" id="highlights" <?php if ($_GET['highlights'] == "true"){ print 'checked="checked"'; } ?>> <label for="highlights">Include only aircrafts with special highlights (unique liveries, destinations etc.)</label></div>
    			</div>
    			<div class="checkbox">
    				<div><input type="checkbox" name="photo" value="true" id="photo" <?php if ($_GET['photo'] == "true"){ print 'checked="checked"'; } ?>> <label for="photo">Include only aircrafts who have a photo</label></div>
    			</div>
        </fieldset>
        <fieldset>
        	<legend>Airline</legend>
    		  <div>
    		  	<label>Name</label> 
    		    <select name="airline" class="selectpicker" data-live-search="true">
    		      <option></option>
    		      <?php
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
    		      ?>
    		    </select>
    		  </div>
    		  <div>
    		  	<label>Country</label> 
    		    <select name="airline_country" class="selectpicker" data-live-search="true">
    		      <option></option>
    		      <?php
    		      $airline_countries = Spotter::getAllAirlineCountries();
    		      foreach($airline_countries as $airline_country)
    		      {
    		        if($_GET['airline_country'] == $airline_country['airline_country'])
    		        {
    		          print '<option value="'.$airline_country['airline_country'].'" selected="selected">'.$airline_country['airline_country'].'</option>';
    		        } else {
    		          print '<option value="'.$airline_country['airline_country'].'">'.$airline_country['airline_country'].'</option>';
    		        }
    		      }
    		      ?>
    		    </select>
    		  </div>
    		  <div>
    		  	<label>Callsign</label> 
    		  	<input type="text" name="callsign" value="<?php print $_GET['callsign']; ?>" size="8" />
    			</div>
    			<div class="radio">
    				<div><input type="radio" name="airline_type" value="both" id="airline_type_both" <?php if (!isset($_GET['airline_type']) || $_GET['airline_type'] == "both"){ print 'checked="checked"'; } ?>> <label for="airline_type_both">Passenger &amp; Cargo airlines</label></div>
    				<div><input type="radio" name="airline_type" value="passenger" id="airline_type_passenger" <?php if ($_GET['airline_type'] == "passenger"){ print 'checked="checked"'; } ?>> <label for="airline_type_passenger">Only Passenger airlines</label></div>
    				<div><input type="radio" name="airline_type" value="cargo" id="airline_type_cargo" <?php if ( $_GET['airline_type'] == "cargo"){ print 'checked="checked"'; } ?>> <label for="airline_type_cargo">Only Cargo airlines</label></div>
    			</div>
        </fieldset>
        <fieldset>
        	<legend>Airport</legend>
    		  <div>
    		  	<label>Name</label> 
    		    <select name="airport" class="selectpicker" data-live-search="true">
    		      <option></option>
    		      <?php
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
    		      ?></select>
    		  </div>
    		  <div>
    		  	<label>Country</label> 
    		    <select name="airport_country" class="selectpicker" data-live-search="true">
    		      <option></option>
    		      <?php
    		      $airport_countries = Spotter::getAllAirportCountries();
    		      foreach($airport_countries as $airport_country)
    		      {
    		        if($_GET['airport_country'] == $airport_country['airport_country'])
    		        {
    		          print '<option value="'.$airport_country['airport_country'].'" selected="selected">'.$airport_country['airport_country'].'</option>';
    		        } else {
    		          print '<option value="'.$airport_country['airport_country'].'">'.$airport_country['airport_country'].'</option>';
    		        }
    		      }
    		      ?>
    		    </select>
    		  </div>
        </fieldset>
        
         <fieldset>
        	<legend>Route</legend>
    		  <div>
    		  	<label>Departure Airport</label> 
    		    <select name="departure_airport_route" class="selectpicker" data-live-search="true">
    		      <option></option>
    		      <?php
    		      foreach($airport_names as $airport_name)
    		      {
    		        if($_GET['departure_airport_route'] == $airport_name['airport_icao'])
    		        {
    		          print '<option value="'.$airport_name['airport_icao'].'" selected="selected">'.$airport_name['airport_city'].', '.$airport_name['airport_name'].', '.$airport_name['airport_country'].' ('.$airport_name['airport_icao'].')</option>';
    		        } else {
    		          print '<option value="'.$airport_name['airport_icao'].'">'.$airport_name['airport_city'].', '.$airport_name['airport_name'].', '.$airport_name['airport_country'].' ('.$airport_name['airport_icao'].')</option>';
    		        }
    		      }
    		      ?></select>
    		  </div>
    		  <div>
    		  	<label>Arrival Airport</label> 
    		    <select name="arrival_airport_route" class="selectpicker" data-live-search="true">
    		      <option></option>
    		      <?php
    		      foreach($airport_names as $airport_name)
    		      {
    		        if($_GET['arrival_airport_route'] == $airport_name['airport_icao'])
    		        {
    		          print '<option value="'.$airport_name['airport_icao'].'" selected="selected">'.$airport_name['airport_city'].', '.$airport_name['airport_name'].', '.$airport_name['airport_country'].' ('.$airport_name['airport_icao'].')</option>';
    		        } else {
    		          print '<option value="'.$airport_name['airport_icao'].'">'.$airport_name['airport_city'].', '.$airport_name['airport_name'].', '.$airport_name['airport_country'].' ('.$airport_name['airport_icao'].')</option>';
    		        }
    		      }
    		      ?></select>
    		  </div>
        </fieldset>
    
    		<fieldset>
        	<legend>Date</legend>
    			<div>
    				<label>Start Date</label> 
    				<input type="text" id="start_date" name="start_date" value="<?php print $_GET['start_date']; ?>" size="10" readonly="readonly" class="datepicker" />
    			</div>
    			<div>
    				<label>End Date</label> 
    				<input type="text" id="end_date" name="end_date" value="<?php print $_GET['end_date']; ?>" size="10" readonly="readonly" class="datepicker" />
    			</div>
    		</fieldset>
		</div>
		
		<fieldset>
        	<legend>Altitude</legend>
    			<div>
    				<label>Lowest Altitude</label> 
    				<select name="lowest_altitude" class="selectpicker" data-live-search="true">
	    		      <option></option>
	    		      <?php
	    		      $altitude_array = Array(1000, 5000, 10000, 15000, 20000, 25000, 30000, 35000, 40000, 45000, 50000);
	    		      foreach($altitude_array as $altitude)
	    		      {
	    		        if($_GET['lowest_altitude'] == $altitude)
	    		        {
	    		          print '<option value="'.$altitude.'" selected="selected">'.number_format($altitude).' feet</option>';
	    		        } else {
	    		          print '<option value="'.$altitude.'">'.number_format($altitude).' feet</option>';
	    		        }
	    		      }
	    		      ?></select>
    			</div>
    			<div>
    				<label>Highest Altitude</label> 
    				<select name="highest_altitude" class="selectpicker" data-live-search="true">
	    		      <option></option>
	    		      <?php
	    		      $altitude_array = Array(1000, 5000, 10000, 15000, 20000, 25000, 30000, 35000, 40000, 45000, 50000);
	    		      foreach($altitude_array as $altitude)
	    		      {
	    		        if($_GET['highest_altitude'] == $altitude)
	    		        {
	    		          print '<option value="'.$altitude.'" selected="selected">'.number_format($altitude).' feet</option>';
	    		        } else {
	    		          print '<option value="'.$altitude.'">'.number_format($altitude).' feet</option>';
	    		        }
	    		      }
	    		      ?></select>
    			</div>
    		</fieldset>
		
		 <fieldset>
        	<legend>Limit per Page</legend>
    		  <div>
    		  	<label>Number of Results</label> 
    		    <select name="number_results">
    		    <?php
    		      $number_results_array = Array(25, 50, 75, 100);
    		      foreach($number_results_array as $number)
    		      {
    		        if($_GET['number_results'] == $number)
    		        {
    		          print '<option value="'.$number.'" selected="selected">'.$number.'</option>';
    		        } else {
    		          print '<option value="'.$number.'">'.$number.'</option>';
    		        }
    		      }
    		      ?>
    		      </select>
    		  </div>
        </fieldset>
		
		<fieldset>
			<div>
				<input type="submit" value="Search" />
			</div>
		</fieldset>
  </form>
</div>

<?php
require('footer.php');
?>