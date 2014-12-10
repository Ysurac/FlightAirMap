<?php
require_once('class.Scheduler.php');
$global_query = "SELECT spotter_output.* FROM spotter_output";

class Spotter{
	
	/**
	* Executes the SQL statements to get the spotter information
	*
	* @param String $query the SQL query
	* @param String $limit the limit query
	* @return Array the spotter information
	*
	*/
	public static function getDataFromDB($query, $params = array(), $limitQuery = '')
	{	
		global $globalSquawkCountry;
		date_default_timezone_set('UTC');
		
		if (!is_string($query))
		{
			return false;
		}
		
		if ($limitQuery != "")
		{
			if (!is_string($limitQuery))
			{
				return false;
			}
		}

		$Connection = new Connection();
		try {
			$sth = Connection::$db->prepare($query.$limitQuery);
			$sth->execute($params);
		} catch (PDOException $e) {
			printf("Invalid query : %s\nWhole query: %s\n",$e->getMessage(), $query.$limitQuery);
			exit();
		}
		
	//	$num_rows = count($sth->fetchAll());
		$num_rows = 0;

		$spotter_array = array();
		$temp_array = array();
		

		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$num_rows++;
			$temp_array = array();
			if (isset($row['spotter_live_id'])) {
			    $temp_array['spotter_id'] = $row['spotter_live_id'];
			} else {
			    $temp_array['spotter_id'] = $row['spotter_id'];
			}
      $temp_array['flightaware_id'] = $row['flightaware_id'];
      $temp_array['ident'] = $row['ident'];
      $temp_array['registration'] = $row['registration'];
      $temp_array['aircraft_type'] = $row['aircraft_icao'];
      $temp_array['departure_airport'] = $row['departure_airport_icao'];
      $temp_array['arrival_airport'] = $row['arrival_airport_icao'];
      $temp_array['latitude'] = $row['latitude'];
      $temp_array['longitude'] = $row['longitude'];
      $temp_array['waypoints'] = $row['waypoints'];
	if (isset($row['route_stop'])) {
		$temp_array['route_stop'] = $row['route_stop'];
		if ($row['route_stop'] != '') {
			$allroute = explode(' ',$row['route_stop']);
			
			foreach ($allroute as $route) {
				$route_airport_array = Spotter::getAllAirportInfo($route);
				if (isset($route_airport_array[0]['name'])) {
					$route_stop_details['airport_name'] = $route_airport_array[0]['name'];
					$route_stop_details['airport_city'] = $route_airport_array[0]['city'];
					$route_stop_details['airport_country'] = $route_airport_array[0]['country'];
					$route_stop_details['airport_icao'] = $route_airport_array[0]['icao'];
					$temp_array['route_stop_details'][] = $route_stop_details;
				}
			}
		}
	}
      $temp_array['altitude'] = $row['altitude'];
      $temp_array['heading'] = $row['heading'];
      $heading_direction = Spotter::parseDirection($row['heading']);
      $temp_array['heading_name'] = $heading_direction[0]['direction_fullname'];
      $temp_array['ground_speed'] = $row['ground_speed'];
      $temp_array['image'] = "";
      $temp_array['image_thumbnail'] = "";
      $temp_array['image_source'] = "";
      $temp_array['image_copyright'] = "";
      if($row['registration'] != "")
      {
	$image_array = Spotter::getSpotterImage($row['registration']);
	if (count($image_array) > 0) {
          $temp_array['image'] = $image_array[0]['image'];
          $temp_array['image_thumbnail'] = $image_array[0]['image_thumbnail'];
          $temp_array['image_source'] = $image_array[0]['image_source'];
          $temp_array['image_copyright'] = $image_array[0]['image_copyright'];
      }
			}
  
			//  $temp_array['highlight'] = $row['highlight'];
			$temp_array['highlight'] = '';
			
			$dateArray = Spotter::parseDateString($row['date']);
			if ($dateArray['seconds'] < 10)
			{
				$temp_array['date'] = "a few seconds ago";
			} elseif ($dateArray['seconds'] >= 5 && $dateArray['seconds'] < 30)
			{
				$temp_array['date'] = "half a minute ago";
			} elseif ($dateArray['seconds'] >= 30 && $dateArray['seconds'] < 60)
			{
				$temp_array['date'] = "about a minute ago";
			} elseif ($dateArray['minutes'] < 5)
			{
				$temp_array['date'] = "a few minutes ago";
			} elseif ($dateArray['minutes'] >= 5 && $dateArray['minutes'] < 60)
			{
				$temp_array['date'] = "about ".$dateArray['minutes']." minutes ago";
			} elseif ($dateArray['hours'] < 2)
			{
				$temp_array['date'] = "about an hour ago";
			} elseif ($dateArray['hours'] >= 2 && $dateArray['hours'] < 24)
			{
				$temp_array['date'] = "about ".$dateArray['hours']." hours ago";
			} else {
				$temp_array['date'] = date("M j Y, g:i a",strtotime($row['date']." UTC"));
			}
			$temp_array['date_minutes_past'] = $dateArray['minutes'];
			$temp_array['date_iso_8601'] = date("c",strtotime($row['date']." UTC"));
			$temp_array['date_rfc_2822'] = date("r",strtotime($row['date']." UTC"));
			$temp_array['date_unix'] = strtotime($row['date']." UTC");
			
			$aircraft_array = Spotter::getAllAircraftInfo($row['aircraft_icao']);
			if (count($aircraft_array) > 0) {
			$temp_array['aircraft_name'] = $aircraft_array[0]['type'];
			$temp_array['aircraft_manufacturer'] = $aircraft_array[0]['manufacturer'];
			}
			$airline_array = array();
			if (!is_numeric(substr($row['ident'], 0, 3))) {
				if (is_numeric(substr(substr($row['ident'], 0, 3), -1, 1))) {
					$airline_array = Spotter::getAllAirlineInfo(substr($row['ident'], 0, 2));
				} elseif (is_numeric(substr(substr($row['ident'], 0, 4), -1, 1))) {
				$airline_array = Spotter::getAllAirlineInfo(substr($row['ident'], 0, 3));
				} else {
					$airline_array = Spotter::getAllAirlineInfo('NA');
			}
			} else {
				$airline_array = Spotter::getAllAirlineInfo('NA');
			}
			if (count($airline_array) > 0) {
			$temp_array['airline_icao'] = $airline_array[0]['icao'];
			$temp_array['airline_iata'] = $airline_array[0]['iata'];
			$temp_array['airline_name'] = $airline_array[0]['name'];
			$temp_array['airline_country'] = $airline_array[0]['country'];
			$temp_array['airline_callsign'] = $airline_array[0]['callsign'];
			$temp_array['airline_type'] = $airline_array[0]['type'];
			}
			
			$schedule_array = Schedule::getSchedule($temp_array['ident']);
			if (count($schedule_array) > 0) {
				if ($schedule_array['departure_airport_icao'] != '') {
					$row['departure_airport_icao'] = $schedule_array['departure_airport_icao'];
					 $temp_array['departure_airport'] = $row['departure_airport_icao'];
				}
				if ($schedule_array['arrival_airport_icao'] != '') {
					$row['arrival_airport_icao'] = $schedule_array['arrival_airport_icao'];
					$temp_array['arrival_airport'] = $row['arrival_airport_icao'];
				}

				$temp_array['departure_airport_time'] = $schedule_array['departure_airport_time'];
				$temp_array['arrival_airport_time'] = $schedule_array['arrival_airport_time'];
			}
			
			if ($row['departure_airport_icao'] != '') {
				$departure_airport_array = Spotter::getAllAirportInfo($row['departure_airport_icao']);
				if (isset($departure_airport_array[0]['name'])) {
					$temp_array['departure_airport_name'] = $departure_airport_array[0]['name'];
					$temp_array['departure_airport_city'] = $departure_airport_array[0]['city'];
					$temp_array['departure_airport_country'] = $departure_airport_array[0]['country'];
					$temp_array['departure_airport_iata'] = $departure_airport_array[0]['iata'];
					$temp_array['departure_airport_icao'] = $departure_airport_array[0]['icao'];
					$temp_array['departure_airport_latitude'] = $departure_airport_array[0]['latitude'];
					$temp_array['departure_airport_longitude'] = $departure_airport_array[0]['longitude'];
					$temp_array['departure_airport_altitude'] = $departure_airport_array[0]['altitude'];
				} else $departure_airport_array = Spotter::getAllAirportInfo('NA');
			
			} else $departure_airport_array = Spotter::getAllAirportInfo('NA');
			/*
			if (isset($row['departure_airport_time'])) {
				$temp_array['departure_airport_time'] = $row['departure_airport_time'];
			}
			*/
			
			if ($row['arrival_airport_icao'] != '') {
			$arrival_airport_array = Spotter::getAllAirportInfo($row['arrival_airport_icao']);
				if (count($arrival_airport_array) > 0) {
			$temp_array['arrival_airport_name'] = $arrival_airport_array[0]['name'];
			$temp_array['arrival_airport_city'] = $arrival_airport_array[0]['city'];
			$temp_array['arrival_airport_country'] = $arrival_airport_array[0]['country'];
			$temp_array['arrival_airport_iata'] = $arrival_airport_array[0]['iata'];
			$temp_array['arrival_airport_icao'] = $arrival_airport_array[0]['icao'];
			$temp_array['arrival_airport_latitude'] = $arrival_airport_array[0]['latitude'];
			$temp_array['arrival_airport_longitude'] = $arrival_airport_array[0]['longitude'];
			$temp_array['arrival_airport_altitude'] = $arrival_airport_array[0]['altitude'];
				} else $arrival_airport_array = Spotter::getAllAirportInfo('NA');
			} else $arrival_airport_array = Spotter::getAllAirportInfo('NA');
			/*
			if (isset($row['arrival_airport_time'])) {
				$temp_array['arrival_airport_time'] = $row['arrival_airport_time'];
			}
			*/
			if (isset($row['squawk'])) {
				$temp_array['squawk'] = $row['squawk'];
				if ($row['squawk'] != '' && $globalSquawkCountry != '') {
					$temp_array['squawk_usage'] = Spotter::getSquawkUsage($row['squawk'],$globalSquawkCountry);
				}
			}
    			
			$temp_array['query_number_rows'] = $num_rows;
			
			$spotter_array[] = $temp_array;
		}
		$spotter_array[0]['query_number_rows'] = $num_rows;
		if ($num_rows == 0) return array();
		return $spotter_array;
	}	
	
	
	/**
	* Gets all the spotter information
	*
	* @return Array the spotter information
	*
	*/
	public static function searchSpotterData($q = '', $registration = '', $aircraft_icao = '', $aircraft_manufacturer = '', $highlights = '', $airline_icao = '', $airline_country = '', $airline_type = '', $airport = '', $airport_country = '', $callsign = '', $departure_airport_route = '', $arrival_airport_route = '', $altitude = '', $date_posted = '', $limit = '', $sort = '', $includegeodata = '')
	{
		date_default_timezone_set('UTC');
		
		$query_values = array();
		$additional_query = '';
		if ($q != "")
		{
			if (!is_string($q))
			{
				return false;
			} else {
			    
				$q_array = explode(" ", $q);
		    	
				foreach ($q_array as $q_item){
					$additional_query .= " AND (";
					$additional_query .= "(spotter_output.spotter_id like '%".$q_item."%') OR ";
					$additional_query .= "(spotter_output.aircraft_icao like '%".$q_item."%') OR ";
					$additional_query .= "(spotter_output.aircraft_name like '%".$q_item."%') OR ";
					$additional_query .= "(spotter_output.aircraft_manufacturer like '%".$q_item."%') OR ";
					$additional_query .= "(spotter_output.airline_icao like '%".$q_item."%') OR ";
					$additional_query .= "(spotter_output.airline_name like '%".$q_item."%') OR ";
					$additional_query .= "(spotter_output.airline_country like '%".$q_item."%') OR ";
					$additional_query .= "(spotter_output.departure_airport_icao like '%".$q_item."%') OR ";
					$additional_query .= "(spotter_output.departure_airport_name like '%".$q_item."%') OR ";
					$additional_query .= "(spotter_output.departure_airport_city like '%".$q_item."%') OR ";
					$additional_query .= "(spotter_output.departure_airport_country like '%".$q_item."%') OR ";
					$additional_query .= "(spotter_output.arrival_airport_icao like '%".$q_item."%') OR ";
					$additional_query .= "(spotter_output.arrival_airport_name like '%".$q_item."%') OR ";
					$additional_query .= "(spotter_output.arrival_airport_city like '%".$q_item."%') OR ";
					$additional_query .= "(spotter_output.arrival_airport_country like '%".$q_item."%') OR ";
					$additional_query .= "(spotter_output.registration like '%".$q_item."%') OR ";
					$additional_query .= "(spotter_output.ident like '%".$q_item."%') OR ";
					$additional_query .= "(spotter_output.highlight like '%".$q_item."%')";
					$additional_query .= ")";
				}
			}
		}
		
		if ($registration != "")
		{
			if (!is_string($registration))
			{
				return false;
			} else {
				$additional_query .= " AND (spotter_output.registration = '".$registration."')";
			}
		}
		
		if ($aircraft_icao != "")
		{
			if (!is_string($aircraft_icao))
			{
				return false;
			} else {
				$additional_query .= " AND (spotter_output.aircraft_icao = '".$aircraft_icao."')";
			}
		}
		
		if ($aircraft_manufacturer != "")
		{
			if (!is_string($aircraft_manufacturer))
			{
				return false;
			} else {
				$additional_query .= " AND (spotter_output.aircraft_manufacturer = '".$aircraft_manufacturer."')";
			}
		}
		
		if ($highlights == "true")
		{
			if (!is_string($highlights))
			{
				return false;
			} else {
				$additional_query .= " AND (spotter_output.highlight <> '')";
			}
		}
		
		if ($airline_icao != "")
		{
			if (!is_string($airline_icao))
			{
				return false;
			} else {
				$additional_query .= " AND (spotter_output.airline_icao = '".$airline_icao."')";
			}
		}
		
		if ($airline_country != "")
		{
			if (!is_string($airline_country))
			{
				return false;
			} else {
				$additional_query .= " AND (spotter_output.airline_country = '".$airline_country."')";
			}
		}
		
		if ($airline_type != "")
		{
			if (!is_string($airline_type))
			{
				return false;
			} else {
				if ($airline_type == "passenger")
				{
					$additional_query .= " AND (spotter_output.airline_type = 'passenger')";
				}
				if ($airline_type == "cargo")
				{
					$additional_query .= " AND (spotter_output.airline_type = 'cargo')";
				}
				if ($airline_type == "military")
				{
					$additional_query .= " AND (spotter_output.airline_type = 'military')";
				}
			}
		}
		
		if ($airport != "")
		{
			if (!is_string($airport))
			{
				return false;
			} else {
				$additional_query .= " AND ((spotter_output.departure_airport_icao = '".$airport."') OR (spotter_output.arrival_airport_icao = '".$airport."'))";
			}
		}
		
		if ($airport_country != "")
		{
			if (!is_string($airport_country))
			{
				return false;
			} else {
				$additional_query .= " AND ((spotter_output.departure_airport_country = '".$airport_country."') OR (spotter_output.arrival_airport_country = '".$airport_country."'))";
			}
		}
    
		if ($callsign != "")
		{
			if (!is_string($callsign))
			{
				return false;
			} else {
				$additional_query .= " AND (spotter_output.ident = '".$callsign."')";
			}
		}
		
		if ($departure_airport_route != "")
		{
			if (!is_string($departure_airport_route))
			{
				return false;
			} else {
				$additional_query .= " AND (spotter_output.departure_airport_icao = '".$departure_airport_route."')";
			}
		}
		
		if ($arrival_airport_route != "")
		{
			if (!is_string($arrival_airport_route))
			{
				return false;
			} else {
				$additional_query .= " AND (spotter_output.arrival_airport_icao = '".$arrival_airport_route."')";
			}
		}
		
		if ($altitude != "")
		{
			$altitude_array = explode(",", $altitude);
			
			$altitude_array[0] = filter_var($altitude_array[0],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
			$altitude_array[1] = filter_var($altitude_array[1],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
			

			if ($altitude_array[1] != "")
			{                
				$altitude_array[0] = substr($altitude_array[0], 0, -2);
				$altitude_array[1] = substr($altitude_array[1], 0, -2);
				$additional_query .= " AND altitude >= '".$altitude_array[0]."' AND altitude <= '".$altitude_array[1]."' ";
			} else {
				$altitude_array[0] = substr($altitude_array[0], 0, -2);
				$additional_query .= " AND altitude <= '".$altitude_array[0]."' ";
			}
		}
		
		if ($date_posted != "")
		{
			$date_array = explode(",", $date_posted);
			
			$date_array[0] = filter_var($date_array[0],FILTER_SANITIZE_STRING);
			$date_array[1] = filter_var($date_array[1],FILTER_SANITIZE_STRING);

			if ($date_array[1] != "")
			{                
				$date_array[0] = date("Y-m-d H:i:s", strtotime($date_array[0]));
				$date_array[1] = date("Y-m-d H:i:s", strtotime($date_array[1]));
				$additional_query .= " AND TIMESTAMP(CONVERT_TZ(spotter_output.date,'+00:00', '-04:00')) >= '".$date_array[0]."' AND TIMESTAMP(CONVERT_TZ(spotter_output.date,'+00:00', '-04:00')) <= '".$date_array[1]."' ";
			} else {
				$date_array[0] = date("Y-m-d H:i:s", strtotime($date_array[0]));
              
				$additional_query .= " AND TIMESTAMP(CONVERT_TZ(spotter_output.date,'+00:00', '-04:00')) >= '".$date_array[0]."' ";
              
			}
		}
		
		if ($limit != "")
		{
			$limit_array = explode(",", $limit);
			
			$limit_array[0] = filter_var($limit_array[0],FILTER_SANITIZE_NUMBER_INT);
			$limit_array[1] = filter_var($limit_array[1],FILTER_SANITIZE_NUMBER_INT);
			
			if ($limit_array[0] >= 0 && $limit_array[1] >= 0)
			{
				$limit_query = " LIMIT ".$limit_array[0].",".$limit_array[1];
			}
		}
		
		if ($sort != "")
		{
			$search_orderby_array = Spotter::getOrderBy();
			$orderby_query = $search_orderby_array[$sort]['sql'];
		} else {
			$orderby_query = " ORDER BY spotter_output.date DESC";
		}
		
		if ($includegeodata == "true")
		{
			$additional_query .= " AND (spotter_output.waypoints <> '')";
		}

		$query  = "SELECT spotter_output.* FROM spotter_output 
					WHERE spotter_output.ident <> '' 
					".$additional_query."
					".$orderby_query;

		$spotter_array = Spotter::getDataFromDB($query, array(),$limit_query);

		return $spotter_array;
	}
	
	
	/**
	* Gets all the spotter information based on the latest data entry
	*
	* @return Array the spotter information
	*
	*/
	public static function getLatestSpotterData($limit = '', $sort = '')
	{
		global $global_query;
		
		date_default_timezone_set('UTC');
		
		if ($limit != "")
		{
			$limit_array = explode(",", $limit);
			
			$limit_array[0] = filter_var($limit_array[0],FILTER_SANITIZE_NUMBER_INT);
			$limit_array[1] = filter_var($limit_array[1],FILTER_SANITIZE_NUMBER_INT);
			
			if ($limit_array[0] >= 0 && $limit_array[1] >= 0)
			{
				$limit_query = " LIMIT ".$limit_array[0].",".$limit_array[1];
			}
		}
		
		if ($sort != "")
		{
			$search_orderby_array = Spotter::getOrderBy();
			$orderby_query = $search_orderby_array[$sort]['sql'];
		} else {
			$orderby_query = " ORDER BY spotter_output.date DESC";
		}

		$query  = $global_query." ".$orderby_query;

		$spotter_array = Spotter::getDataFromDB($query, array(),$limit_query);

		return $spotter_array;
	}
    
    
    /**
	* Gets all the spotter information based on a user's latitude and longitude
	*
	* @return Array the spotter information
	*
	*/
	public static function getLatestSpotterForLayar($lat, $lng, $radius, $interval)
	{
		date_default_timezone_set('UTC');
		
		if ($lat != "")
		{
			if (!is_numeric($lat))
			{
				return false;
			}
		}
        
		if ($lng != "")
		{
			if (!is_numeric($lng))
			{
				return false;
			}
		}
		
		if ($radius != "")
		{
			if (!is_numeric($radius))
			{
				return false;
			}
		}
        
		if ($interval != "")
		{
			if (!is_string($interval))
			{
				return false;
			} else {
				if ($interval == "30m"){
					$additional_query = ' AND DATE_SUB(UTC_TIMESTAMP(),INTERVAL 30 MINUTE) <= spotter_output.date ';
				} else if ($interval == "1h"){
					$additional_query = ' AND DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 HOUR) <= spotter_output.date ';
				} else if ($interval == "3h"){
					$additional_query = ' AND DATE_SUB(UTC_TIMESTAMP(),INTERVAL 3 HOUR) <= spotter_output.date ';
				} else if ($interval == "6h"){
					$additional_query = ' AND DATE_SUB(UTC_TIMESTAMP(),INTERVAL 6 HOUR) <= spotter_output.date ';
				} else if ($interval == "12h"){
					$additional_query = ' AND DATE_SUB(UTC_TIMESTAMP(),INTERVAL 12 HOUR) <= spotter_output.date ';
				} else if ($interval == "24h"){
					$additional_query = ' AND DATE_SUB(UTC_TIMESTAMP(),INTERVAL 24 HOUR) <= spotter_output.date ';
				} else if ($interval == "7d"){
					$additional_query = ' AND DATE_SUB(UTC_TIMESTAMP(),INTERVAL 7 DAY) <= spotter_output.date ';
				} else if ($interval == "30d"){
					$additional_query = ' AND DATE_SUB(UTC_TIMESTAMP(),INTERVAL 30 DAY) <= spotter_output.date ';
				} 
			}
		}

		$query  = "SELECT spotter_output.*, ( 6371 * acos( cos( radians($lat) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians($lng) ) + sin( radians($lat) ) * sin( radians( latitude ) ) ) ) AS distance FROM spotter_output 
                   WHERE spotter_output.latitude <> '' 
				   AND spotter_output.longitude <> '' 
                   ".$additional_query."
                   HAVING distance < :radius  
				   ORDER BY distance";

		$spotter_array = Spotter::getDataFromDB($query, array(':radius' => $radius),$limit_query);

		return $spotter_array;
	}
    
    
    /**
	* Gets all the spotter information sorted by the newest aircraft type
	*
	* @return Array the spotter information
	*
	*/
	public static function getNewestSpotterDataSortedByAircraftType($limit = '', $sort = '')
	{
		global $global_query;
		
		date_default_timezone_set('UTC');
		
		if ($limit != "")
		{
			$limit_array = explode(",", $limit);
			
			$limit_array[0] = filter_var($limit_array[0],FILTER_SANITIZE_NUMBER_INT);
			$limit_array[1] = filter_var($limit_array[1],FILTER_SANITIZE_NUMBER_INT);
			
			if ($limit_array[0] >= 0 && $limit_array[1] >= 0)
			{
				$limit_query = " LIMIT ".$limit_array[0].",".$limit_array[1];
			}
		}
		
		if ($sort != "")
		{
			$search_orderby_array = Spotter::getOrderBy();
			$orderby_query = $search_orderby_array[$sort]['sql'];
		} else {
			$orderby_query = " ORDER BY spotter_output.date DESC ";
		}

		$query  = $global_query." WHERE spotter_output.aircraft_name <> '' GROUP BY spotter_output.aircraft_icao ".$orderby_query;

		$spotter_array = Spotter::getDataFromDB($query, array(), $limit_query);

		return $spotter_array;
	}
    
    
    /**
	* Gets all the spotter information sorted by the newest aircraft registration
	*
	* @return Array the spotter information
	*
	*/
	public static function getNewestSpotterDataSortedByAircraftRegistration($limit = '', $sort = '')
	{
		global $global_query;
		
		date_default_timezone_set('UTC');
		
		if ($limit != "")
		{
			$limit_array = explode(",", $limit);
			
			$limit_array[0] = filter_var($limit_array[0],FILTER_SANITIZE_NUMBER_INT);
			$limit_array[1] = filter_var($limit_array[1],FILTER_SANITIZE_NUMBER_INT);
			
			if ($limit_array[0] >= 0 && $limit_array[1] >= 0)
			{
				$limit_query = " LIMIT ".$limit_array[0].",".$limit_array[1];
			}
		}
		
		if ($sort != "")
		{
			$search_orderby_array = Spotter::getOrderBy();
			$orderby_query = $search_orderby_array[$sort]['sql'];
		} else {
			$orderby_query = " ORDER BY spotter_output.date DESC ";
		}

		$query  = $global_query." WHERE spotter_output.registration <> '' GROUP BY spotter_output.registration ".$orderby_query;

		$spotter_array = Spotter::getDataFromDB($query, array(), $limit_query);

		return $spotter_array;
	}
    
    
    /**
	* Gets all the spotter information sorted by the newest airline
	*
	* @return Array the spotter information
	*
	*/
	public static function getNewestSpotterDataSortedByAirline($limit = '', $sort = '')
	{
		global $global_query;
		
		date_default_timezone_set('UTC');
		
		if ($limit != "")
		{
			$limit_array = explode(",", $limit);
			
			$limit_array[0] = filter_var($limit_array[0],FILTER_SANITIZE_NUMBER_INT);
			$limit_array[1] = filter_var($limit_array[1],FILTER_SANITIZE_NUMBER_INT);
			
			if ($limit_array[0] >= 0 && $limit_array[1] >= 0)
			{
				$limit_query = " LIMIT ".$limit_array[0].",".$limit_array[1];
			}
		}
		
		if ($sort != "")
		{
			$search_orderby_array = Spotter::getOrderBy();
			$orderby_query = $search_orderby_array[$sort]['sql'];
		} else {
			$orderby_query = " ORDER BY spotter_output.date DESC ";
		}

		$query  = $global_query." WHERE spotter_output.airline_name <> '' GROUP BY spotter_output.airline_icao ".$orderby_query;

		$spotter_array = Spotter::getDataFromDB($query, array(), $limit_query);

		return $spotter_array;
	}
    
    
    /**
	* Gets all the spotter information sorted by the newest departure airport
	*
	* @return Array the spotter information
	*
	*/
	public static function getNewestSpotterDataSortedByDepartureAirport($limit = '', $sort = '')
	{
		global $global_query;
		
		date_default_timezone_set('UTC');
		
		if ($limit != "")
		{
			$limit_array = explode(",", $limit);
			
			$limit_array[0] = filter_var($limit_array[0],FILTER_SANITIZE_NUMBER_INT);
			$limit_array[1] = filter_var($limit_array[1],FILTER_SANITIZE_NUMBER_INT);
			
			if ($limit_array[0] >= 0 && $limit_array[1] >= 0)
			{
				$limit_query = " LIMIT ".$limit_array[0].",".$limit_array[1];
			}
		}
		
		if ($sort != "")
		{
			$search_orderby_array = Spotter::getOrderBy();
			$orderby_query = $search_orderby_array[$sort]['sql'];
		} else {
			$orderby_query = " ORDER BY spotter_output.date DESC ";
		}

		$query  = $global_query." WHERE spotter_output.departure_airport_name <> '' GROUP BY spotter_output.departure_airport_icao ".$orderby_query;

		$spotter_array = Spotter::getDataFromDB($query, array(), $limit_query);

		return $spotter_array;
	}
    
    
    /**
	* Gets all the spotter information sorted by the newest arrival airport
	*
	* @return Array the spotter information
	*
	*/
	public static function getNewestSpotterDataSortedByArrivalAirport($limit = '', $sort = '')
	{
		global $global_query;
		
		date_default_timezone_set('UTC');
		
		if ($limit != "")
		{
			$limit_array = explode(",", $limit);
			
			$limit_array[0] = filter_var($limit_array[0],FILTER_SANITIZE_NUMBER_INT);
			$limit_array[1] = filter_var($limit_array[1],FILTER_SANITIZE_NUMBER_INT);
			
			if ($limit_array[0] >= 0 && $limit_array[1] >= 0)
			{
				$limit_query = " LIMIT ".$limit_array[0].",".$limit_array[1];
			}
		}
		
		if ($sort != "")
		{
			$search_orderby_array = Spotter::getOrderBy();
			$orderby_query = $search_orderby_array[$sort]['sql'];
		} else {
			$orderby_query = " ORDER BY spotter_output.date DESC ";
		}

		$query  = $global_query." WHERE spotter_output.arrival_airport_name <> '' GROUP BY spotter_output.arrival_airport_icao ".$orderby_query;

		$spotter_array = Spotter::getDataFromDB($query, array(), $limit_query);

		return $spotter_array;
	}
	

	/**
	* Gets all the spotter information based on the spotter id
	*
	* @return Array the spotter information
	*
	*/
	public static function getSpotterDataByID($id = '')
	{
		global $global_query;
		
		date_default_timezone_set('UTC');
		
		$query_values = array();
		
		if ($id != "")
		{
			if (!is_string($id))
			{
				return false;
			} else {
				$additional_query = " AND (spotter_output.spotter_id = :id)";
				$query_values = array(':id' => $id);
			}
		}

		$query  = $global_query." WHERE spotter_output.ident <> '' ".$additional_query." ";

		$spotter_array = Spotter::getDataFromDB($query,$query_values);

		return $spotter_array;
	}

	
	
	
	/**
	* Gets all the spotter information based on the callsign
	*
	* @return Array the spotter information
	*
	*/
	public static function getSpotterDataByIdent($ident = '', $limit = '', $sort = '')
	{
		global $global_query;
		
		date_default_timezone_set('UTC');
		
		$query_values = array();
		
		if ($ident != "")
		{
			if (!is_string($ident))
			{
				return false;
			} else {
				$additional_query = " AND (spotter_output.ident = :ident)";
				$query_values = array(':ident' => $ident);
			}
		}
		
		if ($limit != "")
		{
			$limit_array = explode(",", $limit);
			
			$limit_array[0] = filter_var($limit_array[0],FILTER_SANITIZE_NUMBER_INT);
			$limit_array[1] = filter_var($limit_array[1],FILTER_SANITIZE_NUMBER_INT);
			
			if ($limit_array[0] >= 0 && $limit_array[1] >= 0)
			{
				$limit_query = " LIMIT ".$limit_array[0].",".$limit_array[1];
			}
		}

		if ($sort != "")
		{
			$search_orderby_array = Spotter::getOrderBy();
			$orderby_query = $search_orderby_array[$sort]['sql'];
		} else {
			$orderby_query = " ORDER BY spotter_output.date DESC";
		}

		$query = $global_query." WHERE spotter_output.ident <> '' ".$additional_query." ".$orderby_query;

		$spotter_array = Spotter::getDataFromDB($query, $query_values, $limit_query);

		return $spotter_array;
	}
	
	
	
	/**
	* Gets all the spotter information based on the aircraft type
	*
	* @return Array the spotter information
	*
	*/
	public static function getSpotterDataByAircraft($aircraft_type = '', $limit = '', $sort = '')
	{
		global $global_query;
		
		date_default_timezone_set('UTC');
		
		$query_values = array();
		
		if ($aircraft_type != "")
		{
			if (!is_string($aircraft_type))
			{
				return false;
			} else {
				$additional_query = " AND (spotter_output.aircraft_icao = :aircraft_type)";
				$query_values = array(':aircraft_type' => $aircraft_type);
			}
		}
		
		if ($limit != "")
		{
			$limit_array = explode(",", $limit);
			
			$limit_array[0] = filter_var($limit_array[0],FILTER_SANITIZE_NUMBER_INT);
			$limit_array[1] = filter_var($limit_array[1],FILTER_SANITIZE_NUMBER_INT);
			
			if ($limit_array[0] >= 0 && $limit_array[1] >= 0)
			{
				$limit_query = " LIMIT ".$limit_array[0].",".$limit_array[1];
			}
		}

		if ($sort != "")
		{
			$search_orderby_array = Spotter::getOrderBy();
			$orderby_query = $search_orderby_array[$sort]['sql'];
		} else {
			$orderby_query = " ORDER BY spotter_output.date DESC";
		}

		$query = $global_query." WHERE spotter_output.ident <> '' ".$additional_query." ".$orderby_query;

		$spotter_array = Spotter::getDataFromDB($query, $query_values, $limit_query);

		return $spotter_array;
	}
	
	
	/**
	* Gets all the spotter information based on the aircraft registration
	*
	* @return Array the spotter information
	*
	*/
	public static function getSpotterDataByRegistration($registration = '', $limit = '', $sort = '')
	{
		global $global_query;
		
		date_default_timezone_set('UTC');
		
		$query_values = array();
		
		if ($registration != "")
		{
			if (!is_string($registration))
			{
				return false;
			} else {
				$additional_query = " AND (spotter_output.registration = :registration)";
				$query_values = array(':registration' => $registration);
			}
		}
		
		if ($limit != "")
		{
			$limit_array = explode(",", $limit);
			
			$limit_array[0] = filter_var($limit_array[0],FILTER_SANITIZE_NUMBER_INT);
			$limit_array[1] = filter_var($limit_array[1],FILTER_SANITIZE_NUMBER_INT);
			
			if ($limit_array[0] >= 0 && $limit_array[1] >= 0)
			{
				$limit_query = " LIMIT ".$limit_array[0].",".$limit_array[1];
			}
		}

		if ($sort != "")
		{
			$search_orderby_array = Spotter::getOrderBy();
			$orderby_query = $search_orderby_array[$sort]['sql'];
		} else {
			$orderby_query = " ORDER BY spotter_output.date DESC";
		}

		$query = $global_query." WHERE spotter_output.ident <> '' ".$additional_query." ".$orderby_query;

		$spotter_array = Spotter::getDataFromDB($query, $query_values, $limit_query);

		return $spotter_array;
	}

	
	
	
	/**
	* Gets all the spotter information based on the airline
	*
	* @return Array the spotter information
	*
	*/
	public static function getSpotterDataByAirline($airline = '', $limit = '', $sort = '')
	{
		global $global_query;
		
		date_default_timezone_set('UTC');

		$query_values = array();
		
		if ($airline != "")
		{
			if (!is_string($airline))
			{
				return false;
			} else {
				$additional_query = " AND (spotter_output.airline_icao = :airline)";
				$query_values = array(':airline' => $airline);
			}
		}
		
		if ($limit != "")
		{
			$limit_array = explode(",", $limit);
			
			$limit_array[0] = filter_var($limit_array[0],FILTER_SANITIZE_NUMBER_INT);
			$limit_array[1] = filter_var($limit_array[1],FILTER_SANITIZE_NUMBER_INT);
			
			if ($limit_array[0] >= 0 && $limit_array[1] >= 0)
			{
				$limit_query = " LIMIT ".$limit_array[0].",".$limit_array[1];
			}
		}
		
		if ($sort != "")
		{
			$search_orderby_array = Spotter::getOrderBy();
			$orderby_query = $search_orderby_array[$sort]['sql'];
		} else {
			$orderby_query = " ORDER BY spotter_output.date DESC";
		}

		$query = $global_query." WHERE spotter_output.ident <> '' ".$additional_query." ".$orderby_query;
		$spotter_array = Spotter::getDataFromDB($query, $query_values, $limit_query);

		return $spotter_array;
	}
	
	
	/**
	* Gets all the spotter information based on the airport
	*
	* @return Array the spotter information
	*
	*/
	public static function getSpotterDataByAirport($airport = '', $limit = '', $sort = '')
	{
		global $global_query;
		
		date_default_timezone_set('UTC');
		$additional_query = '';
		$query_values = array();
		
		if ($airport != "")
		{
			if (!is_string($airport))
			{
				return false;
			} else {
				$additional_query .= " AND ((spotter_output.departure_airport_icao = :airport) OR (spotter_output.arrival_airport_icao = :airport))";
				$query_values = array(':airport' => $airport);
			}
		}
		
		if ($limit != "")
		{
			$limit_array = explode(",", $limit);
			
			$limit_array[0] = filter_var($limit_array[0],FILTER_SANITIZE_NUMBER_INT);
			$limit_array[1] = filter_var($limit_array[1],FILTER_SANITIZE_NUMBER_INT);
			
			if ($limit_array[0] >= 0 && $limit_array[1] >= 0)
			{
				$limit_query = " LIMIT ".$limit_array[0].",".$limit_array[1];
			}
		}
		
		if ($sort != "")
		{
			$search_orderby_array = Spotter::getOrderBy();
			$orderby_query = $search_orderby_array[$sort]['sql'];
		} else {
			$orderby_query = " ORDER BY spotter_output.date DESC";
		}

		$query = $global_query." WHERE spotter_output.ident <> '' ".$additional_query." ".$orderby_query;

		$spotter_array = Spotter::getDataFromDB($query, $query_values, $limit_query);

		return $spotter_array;
	}



	/**
	* Gets all the spotter information based on the date
	*
	* @return Array the spotter information
	*
	*/
	public static function getSpotterDataByDate($date = '', $limit = '', $sort = '')
	{
		global $global_query;
		
		date_default_timezone_set('UTC');
		
		$query_values = array();
		
		if ($date != "")
		{
			$additional_query = " AND DATE(CONVERT_TZ(spotter_output.date,'+00:00', '-04:00')) = :date ";
			$query_values = array(':date' => $date);
		}
		
		if ($limit != "")
		{
			$limit_array = explode(",", $limit);
			
			$limit_array[0] = filter_var($limit_array[0],FILTER_SANITIZE_NUMBER_INT);
			$limit_array[1] = filter_var($limit_array[1],FILTER_SANITIZE_NUMBER_INT);
			
			if ($limit_array[0] >= 0 && $limit_array[1] >= 0)
			{
				$limit_query = " LIMIT ".$limit_array[0].",".$limit_array[1];
			}
		}

		if ($sort != "")
		{
			$search_orderby_array = Spotter::getOrderBy();
			$orderby_query = $search_orderby_array[$sort]['sql'];
		} else {
			$orderby_query = " ORDER BY spotter_output.date DESC";
		}

		$query = $global_query." WHERE spotter_output.ident <> '' ".$additional_query." ".$orderby_query;
		
		$spotter_array = Spotter::getDataFromDB($query, $query_values, $limit_query);

		return $spotter_array;
	}



	/**
	* Gets all the spotter information based on the country name
	*
	* @return Array the spotter information
	*
	*/
	public static function getSpotterDataByCountry($country = '', $limit = '', $sort = '')
	{
		global $global_query;
		
		date_default_timezone_set('UTC');
		
		$query_values = array();
		$additional_query = '';
		if ($country != "")
		{
			if (!is_string($country))
			{
				return false;
			} else {
				$additional_query .= " AND ((spotter_output.departure_airport_country = :country) OR (spotter_output.arrival_airport_country = :country))";
				$additional_query .= " OR spotter_output.airline_country = :country";
				$query_values = array(':country' => $country);
			}
		}
		
		if ($limit != "")
		{
			$limit_array = explode(",", $limit);
			
			$limit_array[0] = filter_var($limit_array[0],FILTER_SANITIZE_NUMBER_INT);
			$limit_array[1] = filter_var($limit_array[1],FILTER_SANITIZE_NUMBER_INT);
			
			if ($limit_array[0] >= 0 && $limit_array[1] >= 0)
			{
				$limit_query = " LIMIT ".$limit_array[0].",".$limit_array[1];
			}
		}
					
		if ($sort != "")
		{
			$search_orderby_array = Spotter::getOrderBy();
			$orderby_query = $search_orderby_array[$sort]['sql'];
		} else {
			$orderby_query = " ORDER BY spotter_output.date DESC";
		}

		$query = $global_query." WHERE spotter_output.ident <> '' ".$additional_query." ".$orderby_query;

		$spotter_array = Spotter::getDataFromDB($query, $query_values, $limit_query);

		return $spotter_array;
	}	
	
	
	/**
	* Gets all the spotter information based on the manufacturer name
	*
	* @return Array the spotter information
	*
	*/
	public static function getSpotterDataByManufacturer($aircraft_manufacturer = '', $limit = '', $sort = '')
	{
		global $global_query;
		
		date_default_timezone_set('UTC');
		
		$query_values = array();
		$additional_query = '';
		
		if ($aircraft_manufacturer != "")
		{
			if (!is_string($aircraft_manufacturer))
			{
				return false;
			} else {
				$additional_query .= " AND (spotter_output.aircraft_manufacturer = :aircraft_manufacturer)";
				$query_values = array(':aircraft_manufacturer' => $aircraft_manufacturer);
			}
		}
		
		if ($limit != "")
		{
			$limit_array = explode(",", $limit);
			
			$limit_array[0] = filter_var($limit_array[0],FILTER_SANITIZE_NUMBER_INT);
			$limit_array[1] = filter_var($limit_array[1],FILTER_SANITIZE_NUMBER_INT);
			
			if ($limit_array[0] >= 0 && $limit_array[1] >= 0)
			{
				$limit_query = " LIMIT ".$limit_array[0].",".$limit_array[1];
			}
		}

		if ($sort != "")
		{
			$search_orderby_array = Spotter::getOrderBy();
			$orderby_query = $search_orderby_array[$sort]['sql'];
		} else {
			$orderby_query = " ORDER BY spotter_output.date DESC";
		}

		$query = $global_query." WHERE spotter_output.ident <> '' ".$additional_query." ".$orderby_query;

		$spotter_array = Spotter::getDataFromDB($query, $query_values, $limit_query);

		return $spotter_array;
	}


  
  
    /**
	* Gets a list of all aircraft with a special highlight text
	*
	* @param String $aircraft_registration the aircraft registration
	* @param String $airport_departure the departure airport
	* @return Array the spotter information
	*
	*/
	public static function getSpotterDataByRoute($departure_airport_icao = '', $arrival_airport_icao = '', $limit = '', $sort = '')
	{
		global $global_query;
		
		$query_values = array();
		$additional_query = '';
		if ($departure_airport_icao != "")
		{
			if (!is_string($departure_airport_icao))
			{
				return false;
			} else {
				$departure_airport_icao = filter_var($departure_airport_icao,FILTER_SANITIZE_STRING);
				$additional_query .= " AND (spotter_output.departure_airport_icao = :departure_airport_icao)";
				$query_values = array(':departure_airport_icao' => $departure_airport_icao);
			}
		}
		
		if ($arrival_airport_icao != "")
		{
			if (!is_string($arrival_airport_icao))
			{
				return false;
			} else {
				$arrival_airport_icao = filter_var($arrival_airport_icao,FILTER_SANITIZE_STRING);
				$additional_query .= " AND (spotter_output.arrival_airport_icao = :arrival_airport_icao)";
				$query_values = array_merge($query_values,array(':arrival_airport_icao' => $arrival_airport_icao));
			}
		}
		
		if ($limit != "")
		{
			$limit_array = explode(",", $limit);
			
			$limit_array[0] = filter_var($limit_array[0],FILTER_SANITIZE_NUMBER_INT);
			$limit_array[1] = filter_var($limit_array[1],FILTER_SANITIZE_NUMBER_INT);
			
			if ($limit_array[0] >= 0 && $limit_array[1] >= 0)
			{
				$limit_query = " LIMIT ".$limit_array[0].",".$limit_array[1];
			}
		}
	
		if ($sort != "")
		{
			$search_orderby_array = Spotter::getOrderBy();
			$orderby_query = $search_orderby_array[$sort]['sql'];
		} else {
			$orderby_query = " ORDER BY spotter_output.date DESC";
		}

		$query = $global_query." WHERE spotter_output.ident <> '' ".$additional_query." ".$orderby_query;
          
		//$result = mysqli_query($GLOBALS["___mysqli_ston"], $query);

		$spotter_array = Spotter::getDataFromDB($query, $query_values, $limit_query);

		return $spotter_array;
	}
	
	
	
	/**
	* Gets all the spotter information based on the special column in the table
	*
	* @return Array the spotter information
	*
	*/
	public static function getSpotterDataByHighlight($limit = '', $sort = '')
	{
		global $global_query;
		
		date_default_timezone_set('UTC');
		
		if ($limit != "")
		{
			$limit_array = explode(",", $limit);
			
			$limit_array[0] = filter_var($limit_array[0],FILTER_SANITIZE_NUMBER_INT);
			$limit_array[1] = filter_var($limit_array[1],FILTER_SANITIZE_NUMBER_INT);
			
			if ($limit_array[0] >= 0 && $limit_array[1] >= 0)
			{
				$limit_query = " LIMIT ".$limit_array[0].",".$limit_array[1];
			}
		}
		
		if ($sort != "")
		{
			$search_orderby_array = Spotter::getOrderBy();
			$orderby_query = $search_orderby_array[$sort]['sql'];
		} else {
			$orderby_query = " ORDER BY spotter_output.date DESC";
		}

		$query  = $global_query." WHERE spotter_output.highlight <> '' ".$orderby_query;

		$spotter_array = Spotter::getDataFromDB($query, array(), $limit_query);

		return $spotter_array;
	}
    
    
    
    /**
	* Gets all the highlight based on a aircraft registration
	*
	* @return String the highlight text
	*
	*/
	public static function getHighlightByRegistration($registration)
	{
		global $global_query;
		
		date_default_timezone_set('UTC');
		
		$registration = filter_var($registration,FILTER_SANITIZE_STRING);

		$Connection = new Connection();
		$query  = $global_query." WHERE spotter_output.highlight <> '' AND spotter_output.registration = :registration";
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':registration' => $registration));

		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$highlight = $row['highlight'];
		}
		if (isset($highlight)) return $highlight;
	}

	
	/**
	* Gets the squawk usage from squawk code
	*
	* @param String $squawk squawk code
	* @param String $country country
	* @return String usage
	*
	*/
	public static function getSquawkUsage($squawk = '',$country = 'FR')
	{
		
		$squawk = filter_var($squawk,FILTER_SANITIZE_STRING);
		$country = filter_var($country,FILTER_SANITIZE_STRING);

		$query_values = array();

		$query  = "SELECT squawk.* FROM squawk WHERE squawk.code = :squawk AND squawk.country = :country LIMIT 1";
		$query_values = array(':squawk' => $squawk, ':country' => $country);
		
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute($query_values);
    
		$temp_array = array();
		
		$row = $sth->fetch(PDO::FETCH_ASSOC);
		if (count($row) > 0) {
			return $row['usage'];
		} else return '';
	}

	/**
	* Gets the airport icao from the iata
	*
	* @param String $airport_iata the iata code of the airport
	* @return String airport iata
	*
	*/
	public static function getAirportIcao($airport_iata = '')
	{
		
		$airport_iata = filter_var($airport_iata,FILTER_SANITIZE_STRING);

		$query_values = array();

		$query  = "SELECT airport.* FROM airport WHERE airport.iata = :airport LIMIT 1";
		$query_values = array(':airport' => $airport_iata);
		
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute($query_values);
    
		$airport_array = array();
		$temp_array = array();
		
		$row = $sth->fetch(PDO::FETCH_ASSOC);
		if (count($row) > 0) {
			return $row['icao'];
		} else return '';
	}
	
	/**
	* Gets the airport info based on the icao
	*
	* @param String $airport_iata the icao code of the airport
	* @return Array airport information
	*
	*/
	public static function getAllAirportInfo($airport = '')
	{
		
		$airport = filter_var($airport,FILTER_SANITIZE_STRING);

		$query_values = array();

		if ($airport == '') {
			$query  = "SELECT airport.* FROM airport";
		} else {
			$query  = "SELECT airport.* FROM airport WHERE airport.icao = :airport";
			$query_values = array(':airport' => $airport);
		}
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute($query_values);
    
		$airport_array = array();
		$temp_array = array();
		
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['name'] = $row['name'];
			$temp_array['city'] = $row['city'];
			$temp_array['country'] = $row['country'];
			$temp_array['iata'] = $row['iata'];
			$temp_array['icao'] = $row['icao'];
			$temp_array['latitude'] = $row['latitude'];
			$temp_array['longitude'] = $row['longitude'];
			$temp_array['altitude'] = $row['altitude'];

			$airport_array[] = $temp_array;
		}

		return $airport_array;
	}
	
	/**
	* Gets the airport info based on the country
	*
	* @param Array $countries Airports countries
	* @return Array airport information
	*
	*/
	public static function getAllAirportInfobyCountry($countries)
	{
		$lst_countries = '';
		foreach ($countries as $country) {
			$country = filter_var($country,FILTER_SANITIZE_STRING);
			if ($lst_countries == '') {
				$lst_countries = "'".$country."'";
			} else {
				$lst_countries .= ",'".$country."'";
			}
		}
		$query  = "SELECT airport.* FROM airport WHERE airport.country IN (".$lst_countries.")";
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute();
    
		$airport_array = array();
		$temp_array = array();
		
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['name'] = $row['name'];
			$temp_array['city'] = $row['city'];
			$temp_array['country'] = $row['country'];
			$temp_array['iata'] = $row['iata'];
			$temp_array['icao'] = $row['icao'];
			$temp_array['latitude'] = $row['latitude'];
			$temp_array['longitude'] = $row['longitude'];
			$temp_array['altitude'] = $row['altitude'];

			$airport_array[] = $temp_array;
		}

		return $airport_array;
	}
	
	/**
	* Gets airports info based on the coord
	*
	* @param Array $coord Airports longitude min,latitude min, longitude max, latitude max
	* @return Array airport information
	*
	*/
	public static function getAllAirportInfobyCoord($coord)
	{
		$lst_countries = '';
		if (is_array($coord)) {
			$minlong = filter_var($coord[0],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
			$minlat = filter_var($coord[1],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
			$maxlong = filter_var($coord[2],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
			$maxlat = filter_var($coord[3],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
		}
		$query  = "SELECT airport.* FROM airport WHERE airport.latitude BETWEEN ".$minlat." AND ".$maxlat." AND airport.longitude BETWEEN ".$minlong." AND ".$maxlong." AND airport.type != 'closed'";
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute();
    
		$airport_array = array();
		$temp_array = array();
		
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array = $row;

			$airport_array[] = $temp_array;
		}

		return $airport_array;
	}

	/**
	* Gets waypoints info based on the coord
	*
	* @param Array $coord waypoints coord
	* @return Array airport information
	*
	*/
	public static function getAllWaypointsInfobyCoord($coord)
	{
		$lst_countries = '';
		if (is_array($coord)) {
			$minlong = filter_var($coord[0],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
			$minlat = filter_var($coord[1],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
			$maxlong = filter_var($coord[2],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
			$maxlat = filter_var($coord[3],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
		}
		//$query  = "SELECT waypoints.* FROM waypoints WHERE waypoints.latitude_begin BETWEEN ".$minlat." AND ".$maxlat." AND waypoints.longitude_begin BETWEEN ".$minlong." AND ".$maxlong;
		$query  = "SELECT waypoints.* FROM waypoints WHERE (waypoints.latitude_begin BETWEEN ".$minlat." AND ".$maxlat." AND waypoints.longitude_begin BETWEEN ".$minlong." AND ".$maxlong.") OR (waypoints.latitude_end BETWEEN ".$minlat." AND ".$maxlat." AND waypoints.longitude_end BETWEEN ".$minlong." AND ".$maxlong.")";
		//$query  = "SELECT waypoints.* FROM waypoints";
		//$query  = "SELECT waypoints.* FROM waypoints INNER JOIN (SELECT waypoints.* FROM waypoints WHERE waypoints.latitude_begin BETWEEN ".$minlat." AND ".$maxlat." AND waypoints.longitude_begin BETWEEN ".$minlong." AND ".$maxlong.") w ON w.name_end = waypoints.name_begin OR w.name_begin = waypoints.name_begin OR w.name_begin = waypoints.name_end OR w.name_end = waypoints.name_end";
		//$query = "SELECT * FROM waypoints LEFT JOIN waypoints w ON waypoints.name_end = w.name_begin WHERE waypoints.latitude_begin BETWEEN ".$minlat." AND ".$maxlat." AND waypoints.longitude_begin BETWEEN ".$minlong." AND ".$maxlong;
		//$query = "SELECT z.name_begin as name_begin, z.name_end as name_end, z.latitude_begin as latitude_begin, z.longitude_begin as longitude_begin, z.latitude_end as latitude_end, z.longitude_end as longitude_end, z.segment_name as segment_name, w.name_end as name_end_seg2, w.latitude_end as latitude_end_seg2, w.longitude_end as longitude_end_seg2, w.segment_name as segment_name_seg2 FROM waypoints z INNER JOIN waypoints w ON z.name_end = w.name_begin WHERE z.latitude_begin BETWEEN ".$minlat." AND ".$maxlat." AND z.longitude_begin BETWEEN ".$minlong." AND ".$maxlong;
		//echo $query;
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute();
    
		$waypoints_array = array();
		$temp_array = array();
		
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array = $row;

			$waypoints_array[] = $temp_array;
		}

		return $waypoints_array;
	}
	
	
	/**
	* Gets the airline info based on the icao code or iata code
	*
	* @param String $airline_icao the iata code of the airport
	* @return Array airport information
	*
	*/
	public static function getAllAirlineInfo($airline_icao)
	{
		$airline_icao = filter_var($airline_icao,FILTER_SANITIZE_STRING);

		if (strlen($airline_icao) == 2) {
		    $query  = "SELECT airlines.* FROM airlines WHERE airlines.iata = :airline_icao AND airlines.active = 'Y'";
		} else {
		    $query  = "SELECT airlines.* FROM airlines WHERE airlines.icao = :airline_icao AND airlines.active = 'Y'";
		}
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':airline_icao' => $airline_icao));

		$airline_array = array();
		$temp_array = array();
		
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['name'] = $row['name'];
			$temp_array['iata'] = $row['iata'];
			$temp_array['icao'] = $row['icao'];
			$temp_array['callsign'] = $row['callsign'];
			$temp_array['country'] = $row['country'];
			$temp_array['type'] = $row['type'];

			$airline_array[] = $temp_array;
		}
		return $airline_array;
	}
	
	
	
	/**
	* Gets the aircraft info based on the aircraft type
	*
	* @param String $aircraft_type the aircraft type
	* @return Array aircraft information
	*
	*/
	public static function getAllAircraftInfo($aircraft_type)
	{
		$aircraft_type = filter_var($aircraft_type,FILTER_SANITIZE_STRING);

		$query  = "SELECT aircraft.* FROM aircraft WHERE aircraft.icao = :aircraft_type";
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':aircraft_type' => $aircraft_type));

		$aircraft_array = array();
		$temp_array = array();
		
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array = array();
			$temp_array['icao'] = $row['icao'];
			$temp_array['type'] = $row['type'];
			$temp_array['manufacturer'] = $row['manufacturer'];

			$aircraft_array[] = $temp_array;
		}

		return $aircraft_array;
	}
	
	/**
	* Gets the aircraft info based on the aircraft ident
	*
	* @param String $aircraft_ident the aircraft ident (hex)
	* @return String aircraft type
	*
	*/
	public static function getAllAircraftType($aircraft_modes)
	{
		$aircraft_modes = filter_var($aircraft_modes,FILTER_SANITIZE_STRING);

		$query  = "SELECT aircraft_modes.ICAOTypeCode FROM aircraft_modes WHERE aircraft_modes.ModeS = :aircraft_modes LIMIT 1";
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':aircraft_modes' => $aircraft_modes));

		$row = $sth->fetch(PDO::FETCH_ASSOC);
		if (count($row) > 0) {
			return $row['ICAOTypeCode'];
		} else return '';
	}

	/**
	* Gets correct aircraft operator corde
	*
	* @param String $operator the aircraft operator code (callsign)
	* @return String aircraft operator code
	*
	*/
	public static function getOperator($operator)
	{
		$operator = filter_var($operator,FILTER_SANITIZE_STRING);
		echo "Operator 2: ".$operator."\n";
		$query  = "SELECT translation.operator_correct FROM translation WHERE translation.operator = :operator LIMIT 1";
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':operator' => $operator));

		$row = $sth->fetch(PDO::FETCH_ASSOC);
		if (isset($row['operator_correct'])) {
			return $row['operator_correct'];
		} else return $operator;
	}

	/**
	* Gets the aircraft route based on the aircraft callsign
	*
	* @param String $callsign the aircraft callsign
	* @return Array aircraft type
	*
	*/
	public static function getRouteInfo($callsign)
	{
		$callsign = filter_var($callsign,FILTER_SANITIZE_STRING);
                if ($callsign == '') return array();
		$query  = "SELECT * FROM routes WHERE CallSign = :callsign LIMIT 1";
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':callsign' => $callsign));

		$row = $sth->fetch(PDO::FETCH_ASSOC);
		if (count($row) > 0) {
			return $row;
		} else return array();
	}
	
	/**
	* Gets the aircraft info based on the aircraft registration
	*
	* @param String $aircraft_registration the aircraft registration
	* @return Array aircraft information
	*
	*/
	public static function getAircraftInfoByRegistration($registration)
	{
		$registration = filter_var($registration,FILTER_SANITIZE_STRING);

		$query  = "SELECT spotter_output.aircraft_icao, spotter_output.aircraft_name, spotter_output.aircraft_manufacturer FROM spotter_output WHERE spotter_output.registration = :registration";
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':registration' => $registration));

		$aircraft_array = array();
		$temp_array = array();
		
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['aircraft_icao'] = $row['aircraft_icao'];
			$temp_array['aircraft_name'] = $row['aircraft_name'];
			$temp_array['aircraft_manufacturer'] = $row['aircraft_manufacturer'];

			$aircraft_array[] = $temp_array;
		}

		return $aircraft_array;
	}
	
  
  /**
	* Gets all flights (but with only little info)
	*
	* @return Array basic flight information
	*
	*/
	public static function getAllFlightsforSitemap()
	{
		$query  = "SELECT spotter_output.spotter_id, spotter_output.ident, spotter_output.airline_name, spotter_output.aircraft_name, spotter_output.aircraft_icao, spotter_output.image FROM spotter_output";
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute();

		$flight_array = array();
		$temp_array = array();
		
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['spotter_id'] = $row['spotter_id'];
			$temp_array['ident'] = $row['ident'];
			$temp_array['airline_name'] = $row['airline_name'];
			$temp_array['aircraft_type'] = $row['aircraft_icao'];
			$temp_array['aircraft_name'] = $row['aircraft_name'];
			$temp_array['image'] = $row['image'];

			$flight_array[] = $temp_array;
		}

		return $flight_array;
	}
  
	/**
	* Gets a list of all aircraft manufacturers
	*
	* @return Array list of aircraft types
	*
	*/
	public static function getAllManufacturers()
	{
		if(!Connection::createDBConnection())
		{
			return false;
		}

		$query  = "SELECT DISTINCT spotter_output.aircraft_manufacturer AS aircraft_manufacturer
								FROM spotter_output
								WHERE spotter_output.aircraft_manufacturer <> '' 
								ORDER BY spotter_output.aircraft_manufacturer ASC";
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute();

		$manufacturer_array = array();
		$temp_array = array();
		
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['aircraft_manufacturer'] = $row['aircraft_manufacturer'];

			$manufacturer_array[] = $temp_array;
		}

		return $manufacturer_array;
	}
  
  
  /**
	* Gets a list of all aircraft types
	*
	* @return Array list of aircraft types
	*
	*/
	public static function getAllAircraftTypes()
	{
		$query  = "SELECT DISTINCT spotter_output.aircraft_icao AS aircraft_icao, spotter_output.aircraft_name AS aircraft_name
								FROM spotter_output  
								WHERE spotter_output.aircraft_icao <> '' 
								ORDER BY spotter_output.aircraft_name ASC";
								
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute();

		$aircraft_array = array();
		$temp_array = array();
		
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['aircraft_icao'] = $row['aircraft_icao'];
			$temp_array['aircraft_name'] = $row['aircraft_name'];

			$aircraft_array[] = $temp_array;
		}

		return $aircraft_array;
	}
	
	
	/**
	* Gets a list of all aircraft registrations
	*
	* @return Array list of aircraft registrations
	*
	*/
	public static function getAllAircraftRegistrations()
	{
		$query  = "SELECT DISTINCT spotter_output.registration 
								FROM spotter_output  
								WHERE spotter_output.registration <> '' 
								ORDER BY spotter_output.registration ASC";						
								
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute();

		$aircraft_array = array();
		$temp_array = array();
		
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['registration'] = $row['registration'];

			$aircraft_array[] = $temp_array;
		}

		return $aircraft_array;
	}



	/**
	* Gets a list of all airline names
	*
	* @return Array list of airline names
	*
	*/
	public static function getAllAirlineNames($airline_type = '')
	{
		$airline_type = filter_var($airline_type,FILTER_SANITIZE_STRING);
		if ($airline_type == '' || $airline_type == 'all') {
			$query  = "SELECT DISTINCT spotter_output.airline_icao AS airline_icao, spotter_output.airline_name AS airline_name, spotter_output.airline_type AS airline_type
								FROM spotter_output
								WHERE spotter_output.airline_icao <> '' 
								ORDER BY spotter_output.airline_name ASC";
		} else {
			$query  = "SELECT DISTINCT spotter_output.airline_icao AS airline_icao, spotter_output.airline_name AS airline_name, spotter_output.airline_type AS airline_type
								FROM spotter_output
								WHERE spotter_output.airline_icao <> '' 
								AND spotter_output.airline_type = :airline_type 
								ORDER BY spotter_output.airline_name ASC";
		}
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		if ($airline_type != '' || $airline_type == 'all') {
			$sth->execute(array(':airline_type' => $airline_type));
		} else {
			$sth->execute();
		}
    
		$airline_array = array();
		$temp_array = array();
		
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airline_icao'] = $row['airline_icao'];
			$temp_array['airline_name'] = $row['airline_name'];
			$temp_array['airline_type'] = $row['airline_type'];

			$airline_array[] = $temp_array;
		}
		return $airline_array;
	}
	
	
	/**
	* Gets a list of all airline countries
	*
	* @return Array list of airline countries
	*
	*/
	public static function getAllAirlineCountries()
	{
		$query  = "SELECT DISTINCT spotter_output.airline_country AS airline_country
								FROM spotter_output  
								WHERE spotter_output.airline_country <> '' 
								ORDER BY spotter_output.airline_country ASC";						
								
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute();

		$airline_array = array();
		$temp_array = array();
		
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airline_country'] = $row['airline_country'];

			$airline_array[] = $temp_array;
		}

		return $airline_array;
	}

	
	
	/**
	* Gets a list of all departure & arrival names
	*
	* @return Array list of airport names
	*
	*/
	public static function getAllAirportNames()
	{
		$airport_array = array();
								
		$query  = "SELECT DISTINCT spotter_output.departure_airport_icao AS airport_icao, spotter_output.departure_airport_name AS airport_name, spotter_output.departure_airport_city AS airport_city, spotter_output.departure_airport_country AS airport_country
								FROM spotter_output 
								WHERE spotter_output.departure_airport_icao <> '' 
								ORDER BY spotter_output.departure_airport_city ASC";		
					
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute();

		$temp_array = array();
		
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airport_icao'] = $row['airport_icao'];
			$temp_array['airport_name'] = $row['airport_name'];
			$temp_array['airport_city'] = $row['airport_city'];
			$temp_array['airport_country'] = $row['airport_country'];

			$airport_array[$row['airport_city'].",".$row['airport_name']] = $temp_array;
		}

		$query  = "SELECT DISTINCT spotter_output.arrival_airport_icao AS airport_icao, spotter_output.arrival_airport_name AS airport_name, spotter_output.arrival_airport_city AS airport_city, spotter_output.arrival_airport_country AS airport_country
								FROM spotter_output 
								WHERE spotter_output.arrival_airport_icao <> '' 
								ORDER BY spotter_output.arrival_airport_city ASC";
					
		$sth = Connection::$db->prepare($query);
		$sth->execute();

		while($row = $sth->fetch(PDO::FETCH_ASSOC))
			{
		//	if ($airport_array[$row['airport_city'].",".$row['airport_name']]['airport_icao'] != $row['airport_icao'])
		//	{
				$temp_array['airport_icao'] = $row['airport_icao'];
				$temp_array['airport_name'] = $row['airport_name'];
				$temp_array['airport_city'] = $row['airport_city'];
				$temp_array['airport_country'] = $row['airport_country'];
				
				$airport_array[$row['airport_city'].",".$row['airport_name']] = $temp_array;
		//	}
		}

		return $airport_array;
	} 
	
	
	/**
	* Gets a list of all departure & arrival airport countries
	*
	* @return Array list of airport countries
	*
	*/
	public static function getAllAirportCountries()
	{
		$airport_array = array();
					
		$query  = "SELECT DISTINCT spotter_output.departure_airport_country AS airport_country
								FROM spotter_output
								WHERE spotter_output.departure_airport_country <> '' 
								ORDER BY spotter_output.departure_airport_country ASC";
					
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute();
   
		$temp_array = array();
		
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airport_country'] = $row['airport_country'];

			$airport_array[$row['airport_country']] = $temp_array;
		}
								
		$query  = "SELECT DISTINCT spotter_output.arrival_airport_country AS airport_country
								FROM spotter_output
								WHERE spotter_output.arrival_airport_country <> '' 
								ORDER BY spotter_output.arrival_airport_country ASC";
					
		$sth = Connection::$db->prepare($query);
		$sth->execute();
		
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			if ($airport_array[$row['airport_country']]['airport_country'] != $row['airport_country'])
			{
				$temp_array['airport_country'] = $row['airport_country'];
				
				$airport_array[$row['airport_country']] = $temp_array;
			}
		}

		return $airport_array;
	} 
	
	
	
	
	/**
	* Gets a list of all countries (airline, departure airport & arrival airport)
	*
	* @return Array list of countries
	*
	*/
	public static function getAllCountries()
	{
		if(!Connection::createDBConnection())
		{
			return false;
		}
		
		$country_array = array();
					
		$query  = "SELECT DISTINCT spotter_output.departure_airport_country AS airport_country
								FROM spotter_output
								WHERE spotter_output.departure_airport_country <> '' 
								ORDER BY spotter_output.departure_airport_country ASC";
					
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute();
   
		$temp_array = array();
		
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['country'] = $row['airport_country'];

			$country_array[$row['airport_country']] = $temp_array;
		}
								
		$query  = "SELECT DISTINCT spotter_output.arrival_airport_country AS airport_country
								FROM spotter_output
								WHERE spotter_output.arrival_airport_country <> '' 
								ORDER BY spotter_output.arrival_airport_country ASC";
					
		$sth = Connection::$db->prepare($query);
		$sth->execute();
		
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			if ($country_array[$row['airport_country']]['country'] != $row['airport_country'])
			{
				$temp_array['country'] = $row['airport_country'];
				
				$country_array[$row['country']] = $temp_array;
			}
		}
		
		$query  = "SELECT DISTINCT spotter_output.airline_country AS airline_country
								FROM spotter_output  
								WHERE spotter_output.airline_country <> '' 
								ORDER BY spotter_output.airline_country ASC";
					
		$sth = Connection::$db->prepare($query);
		$sth->execute();
		
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			if ($country_array[$row['airline_country']]['country'] != $row['airline_country'])
			{
				$temp_array['country'] = $row['airline_country'];
				
				$country_array[$row['country']] = $temp_array;
			}
		}

		return $country_array;
	} 
	
	
	
	
	/**
	* Gets a list of all idents/callsigns
	*
	* @return Array list of ident/callsign names
	*
	*/
	public static function getAllIdents()
	{
		$query  = "SELECT DISTINCT spotter_output.ident
								FROM spotter_output
								WHERE spotter_output.ident <> '' 
								ORDER BY spotter_output.ident ASC";							
								
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute();
    
		$ident_array = array();
		$temp_array = array();
		
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['ident'] = $row['ident'];

			$ident_array[] = $temp_array;
		}

		return $ident_array;
	}



	/**
	* Gets a list of all dates
	*
	* @return Array list of date names
	*
	*/
	public static function getAllDates()
	{
		$query  = "SELECT DISTINCT DATE(CONVERT_TZ(spotter_output.date,'+00:00', '-04:00')) as date
								FROM spotter_output
								WHERE spotter_output.date <> '' 
								ORDER BY spotter_output.date ASC";							
								
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute();
    
		$date_array = array();
		$temp_array = array();
		
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['date'] = $row['date'];

			$date_array[] = $temp_array;
		}

		return $date_array;
	}
	
	
	
	/**
	* Gets all route combinations
	*
	* @return Array the route list
	*
	*/
	public static function getAllRoutes()
	{
		$query  = "SELECT DISTINCT concat(spotter_output.departure_airport_icao, ' - ',  spotter_output.arrival_airport_icao) AS route,  spotter_output.departure_airport_icao, spotter_output.arrival_airport_icao 
					FROM spotter_output
                    WHERE spotter_output.ident <> '' 
                    GROUP BY route
                    ORDER BY route ASC";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute();
      
	        $routes_array = array();
		$temp_array = array();
        
        while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['route'] = $row['route'];
			$temp_array['airport_departure_icao'] = $row['departure_airport_icao'];
            $temp_array['airport_arrival_icao'] = $row['arrival_airport_icao'];
          
            $routes_array[] = $temp_array;
		}

		return $routes_array;
	}
	
	/**
	* Adds a new spotter data
	*
	* @param String $flightaware_id the ID from flightaware
	* @param String $ident the flight ident
	* @param String $aircraft_icao the aircraft type
	* @param String $departure_airport_icao the departure airport
	* @param String $arrival_airport_icao the arrival airport
	* @return String success or false
	*
	*/	
	public static function addSpotterData($flightaware_id = '', $ident = '', $aircraft_icao = '', $departure_airport_icao = '', $arrival_airport_icao = '', $latitude = '', $longitude = '', $waypoints = '', $altitude = '', $heading = '', $groundspeed = '', $date = '', $departure_airport_time = '', $arrival_airport_time = '',$squawk = '', $route_stop = '', $highlight = '')
	{
		global $globalURL;
		
		date_default_timezone_set('UTC');
		
		//getting the registration
		$registration = '';
		
		if ($flightaware_id != "")
		{
			if (!is_string($flightaware_id))
			{
				return false;
			} else {
				$myhex = explode('-',$flightaware_id);
				if (count($myhex) > 0) {
					$registration = Spotter::getAircraftRegistrationBymodeS($myhex[0]);
				}
			}
		}
    	
    	
    	//getting the airline information
		if ($ident != "")
		{
			if (!is_string($ident))
			{
				return false;
			} else {
				if (!is_numeric(substr($ident, 0, 3)))
				{
					if (is_numeric(substr(substr($ident, 0, 3), -1, 1))) {
						$airline_array = Spotter::getAllAirlineInfo(substr($ident, 0, 2));
					} elseif (is_numeric(substr(substr($ident, 0, 4), -1, 1))) {
						$airline_array = Spotter::getAllAirlineInfo(substr($ident, 0, 3));
					} else {
						$airline_array = Spotter::getAllAirlineInfo("NA");
					}
					if (count($airline_array) == 0) {
						$airline_array = Spotter::getAllAirlineInfo("NA");
					}
					if ($airline_array[0]['icao'] == ""){
						$airline_array = Spotter::getAllAirlineInfo("NA");
					}
					
				} else {
					$airline_array = Spotter::getAllAirlineInfo("NA");
				}
			}
		}
		
		//getting the aircraft information
		if ($aircraft_icao != "")
		{
			if (!is_string($aircraft_icao))
			{
				return false;
			} else {
				if ($aircraft_icao == "" || $aircraft_icao == "XXXX")
				{
					$aircraft_array = Spotter::getAllAircraftInfo("NA");
				} else {
					$aircraft_array = Spotter::getAllAircraftInfo($aircraft_icao);
				}
			}
		} else {
			if ($flightaware_id != "") {
				$myhex = explode('-',$flightaware_id);
				if (count($myhex) > 0) {
					$aircraft_icao = Spotter::getAllAircraftType($myhex[0]);
					if ($aircraft_icao == "" || $aircraft_icao == "XXXX")
					{
						$aircraft_array = Spotter::getAllAircraftInfo("NA");
					} else {
						$aircraft_array = Spotter::getAllAircraftInfo($aircraft_icao);
					}
				}
			}
		}
		
		//getting the departure airport information
		if ($departure_airport_icao != "")
		{
			if (!is_string($departure_airport_icao))
			{
				return false;
			} else {
				$departure_airport_array = Spotter::getAllAirportInfo($departure_airport_icao);
			}
		}
		
		//getting the arrival airport information
		if ($arrival_airport_icao != "")
		{
			if (!is_string($arrival_airport_icao))
			{
				return false;
			} else {
				$arrival_airport_array = Spotter::getAllAirportInfo($arrival_airport_icao);
			}
		}

						
		if ($latitude != "")
		{
			if (!is_numeric($latitude))
			{
				return false;
			}
		}
		
		if ($longitude != "")
		{
			if (!is_numeric($longitude))
			{
				return false;
			}
		}
		
		if ($waypoints != "")
		{
			if (!is_string($waypoints))
			{
				return false;
			}
		}
		
		if ($altitude != "")
		{
			if (!is_numeric($altitude))
			{
				return false;
			}
		}
		
		if ($heading != "")
		{
			if (!is_numeric($heading))
			{
				return false;
			}
		}
		
		if ($groundspeed != "")
		{
			if (!is_numeric($groundspeed))
			{
				return false;
			}
		}

    
		if ($date == "")
		{
			$date = date("Y-m-d H:i:s", time());
		}

		//getting the aircraft image
		if ($registration != "" || $registration != 'NA')
		{
			$image_array = Spotter::getSpotterImage($registration);
			if (!isset($image_array[0]['registration']))
			{
				Spotter::addSpotterImage($registration);
			}
		}
    
		$flightaware_id = filter_var($flightaware_id,FILTER_SANITIZE_STRING);
		$ident = filter_var($ident,FILTER_SANITIZE_STRING);
		$registration = filter_var($registration,FILTER_SANITIZE_STRING);
		$aircraft_icao = filter_var($aircraft_icao,FILTER_SANITIZE_STRING);
		$departure_airport_icao = filter_var($departure_airport_icao,FILTER_SANITIZE_STRING);
		$arrival_airport_icao = filter_var($arrival_airport_icao,FILTER_SANITIZE_STRING);
		$latitude = filter_var($latitude,FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
		$longitude = filter_var($longitude,FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
		$waypoints = filter_var($waypoints,FILTER_SANITIZE_STRING);
		$altitude = filter_var($altitude,FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
		$heading = filter_var($heading,FILTER_SANITIZE_NUMBER_INT);
		$groundspeed = filter_var($groundspeed,FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
		$squawk = filter_var($squawk,FILTER_SANITIZE_NUMBER_INT);
		$route_stop = filter_var($route_stop,FILTER_SANITIZE_STRING);
	
		if (count($airline_array) == 0) 
		{
                        $airline_array = Spotter::getAllAirlineInfo('NA');
                }
                if (count($aircraft_array) == 0) 
                {
                        $aircraft_array = Spotter::getAllAircraftInfo('NA');
                }
                if (count($departure_airport_array) == 0) 
                {
                        $departure_airport_array = Spotter::getAllAirportInfo('NA');
                }
                if (count($arrival_airport_array) == 0) 
                {
                        $arrival_airport_array = Spotter::getAllAirportInfo('NA');
                }
                if ($registration == '') $registration = 'NA';
                $query  = "INSERT INTO spotter_output (flightaware_id, ident, registration, airline_name, airline_icao, airline_country, airline_type, aircraft_icao, aircraft_name, aircraft_manufacturer, departure_airport_icao, departure_airport_name, departure_airport_city, departure_airport_country, arrival_airport_icao, arrival_airport_name, arrival_airport_city, arrival_airport_country, latitude, longitude, waypoints, altitude, heading, ground_speed, date, departure_airport_time, arrival_airport_time, squawk, route_stop,highlight) 
                VALUES (:flightaware_id,:ident,:registration,:airline_name,:airline_icao,:airline_country,:airline_type,:aircraft_icao,:aircraft_type,:aircraft_manufacturer,:departure_airport_icao,:departure_airport_name,:departure_airport_city,:departure_airport_country, :arrival_airport_icao, :arrival_airport_name, :arrival_airport_city, :arrival_airport_country, :latitude,:longitude,:waypoints,:altitude,:heading,:groundspeed,:date, :departure_airport_time, :arrival_airport_time, :squawk, :route_stop, :highlight)";

                $query_values = array(':flightaware_id' => $flightaware_id,':ident' => $ident, ':registration' => $registration,':airline_name' => $airline_array[0]['name'],':airline_icao' => $airline_array[0]['icao'],':airline_country' => $airline_array[0]['country'],':airline_type' => $airline_array[0]['type'],':aircraft_icao' => $aircraft_icao,':aircraft_type' => $aircraft_array[0]['type'],':aircraft_manufacturer' => $aircraft_array[0]['manufacturer'],':departure_airport_icao' => $departure_airport_icao,':departure_airport_name' => $departure_airport_array[0]['name'],':departure_airport_city' => $departure_airport_array[0]['city'],':departure_airport_country' => $departure_airport_array[0]['country'],':arrival_airport_icao' => $arrival_airport_icao,':arrival_airport_name' => $arrival_airport_array[0]['name'],':arrival_airport_city' => $arrival_airport_array[0]['city'],':arrival_airport_country' => $arrival_airport_array[0]['country'],':latitude' => $latitude,':longitude' => $longitude, ':waypoints' => $waypoints,':altitude' => $altitude,':heading' => $heading,':groundspeed' => $groundspeed,':date' => $date,':departure_airport_time' => $departure_airport_time,':arrival_airport_time' => $arrival_airport_time, ':squawk' => $squawk, ':route_stop' => $route_stop, ':highlight' => $highlight);


		try {
		        $Connection = new Connection();
			$sth = Connection::$db->prepare($query);
			$sth->execute($query_values);
		} catch (PDOException $e) {
		    return "error : ".$e->getMessage();
		}
		
		return "success";

	}
	
  
  /**
	* Gets the aircraft ident within the last hour
	*
	* @return String the ident
	*
	*/
	public static function getIdentFromLastHour($ident)
	{
		$query  = "SELECT spotter_output.ident FROM spotter_output 
								WHERE spotter_output.ident = :ident 
								AND spotter_output.date >= DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 HOUR) 
								AND spotter_output.date < UTC_TIMESTAMP()";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':ident' => $ident));
    		$ident_result='';
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$ident_result = $row['ident'];
		}

		return $ident_result;
	}
	
	
	/**
	* Gets the aircraft data from the last 20 seconds
	*
	* @return Array the spotter data
	*
	*/
	public static function getRealTimeData($q = '')
	{
		if ($q != "")
		{
			if (!is_string($q))
			{
				return false;
			} else {
		    	$q_array = explode(" ", $q);
		    	foreach ($q_array as $q_item){
		    		$additional_query .= " AND (";
						$additional_query .= "(spotter_output.aircraft_icao like '%".$q_item."%') OR ";
		        		$additional_query .= "(spotter_output.aircraft_name like '%".$q_item."%') OR ";
		        		$additional_query .= "(spotter_output.aircraft_manufacturer like '%".$q_item."%') OR ";
		        		$additional_query .= "(spotter_output.airline_icao like '%".$q_item."%') OR ";
		        		$additional_query .= "(spotter_output.departure_airport_icao like '%".$q_item."%') OR ";
		        		$additional_query .= "(spotter_output.arrival_airport_icao like '%".$q_item."%') OR ";
		        		$additional_query .= "(spotter_output.registration like '%".$q_item."%') OR ";
		        		$additional_query .= "(spotter_output.ident like '%".$q_item."%')";
	        		$additional_query .= ")";
	      		}
			}
		}

		$query  = "SELECT spotter_output.* FROM spotter_output 
								WHERE spotter_output.date >= DATE_SUB(UTC_TIMESTAMP(),INTERVAL 20 SECOND) ".$additional_query." 
								AND spotter_output.date < UTC_TIMESTAMP()";
      
		$spotter_array = Spotter::getDataFromDB($query, array(),$limit_query);

		return $spotter_array;
	}
	
	
	
	 /**
	* Gets all airlines that have flown over
	*
	* @return Array the airline list
	*
	*/
	public static function countAllAirlines()
	{

		$query  = "SELECT DISTINCT spotter_output.airline_name, spotter_output.airline_icao, spotter_output.airline_country, COUNT(spotter_output.airline_name) AS airline_count
		 			FROM spotter_output
					WHERE spotter_output.airline_name <> '' 
          GROUP BY spotter_output.airline_name
					ORDER BY airline_count DESC
					LIMIT 0,10";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute();
      
        $airline_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airline_name'] = $row['airline_name'];
            $temp_array['airline_icao'] = $row['airline_icao'];
            $temp_array['airline_count'] = $row['airline_count'];
            $temp_array['airline_country'] = $row['airline_country'];
          
            $airline_array[] = $temp_array;
		}

		return $airline_array;
	}
	
	
	/**
	* Gets all airlines that have flown over by aircraft
	*
	* @return Array the airline list
	*
	*/
	public static function countAllAirlinesByAircraft($aircraft_icao)
	{
		$aircraft_icao = filter_var($aircraft_icao,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.airline_name, spotter_output.airline_icao, spotter_output.airline_country, COUNT(spotter_output.airline_name) AS airline_count
		 			FROM spotter_output
					WHERE spotter_output.airline_name <> '' AND spotter_output.aircraft_icao = :aircraft_icao 
                    GROUP BY spotter_output.airline_name
					ORDER BY airline_count DESC";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':aircraft_icao' => $aircraft_icao));
      
        $airline_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airline_name'] = $row['airline_name'];
            $temp_array['airline_icao'] = $row['airline_icao'];
            $temp_array['airline_count'] = $row['airline_count'];
            $temp_array['airline_country'] = $row['airline_country'];
          
            $airline_array[] = $temp_array;
		}

		return $airline_array;
	}
	
	
	/**
	* Gets all airline countries that have flown over by aircraft
	*
	* @return Array the airline country list
	*
	*/
	public static function countAllAirlineCountriesByAircraft($aircraft_icao)
	{
		$aircraft_icao = filter_var($aircraft_icao,FILTER_SANITIZE_STRING);
      
		$query  = "SELECT DISTINCT spotter_output.airline_country, COUNT(spotter_output.airline_country) AS airline_country_count
		 			FROM spotter_output
					WHERE spotter_output.airline_country <> '' AND spotter_output.aircraft_icao = :aircraft_icao
                    GROUP BY spotter_output.airline_country
					ORDER BY airline_country_count DESC
					LIMIT 0,10";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':aircraft_icao' => $aircraft_icao));
      
        $airline_country_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airline_country_count'] = $row['airline_country_count'];
            $temp_array['airline_country'] = $row['airline_country'];
          
            $airline_country_array[] = $temp_array;
		}

		return $airline_country_array;

	}


	
	
	/**
	* Gets all airlines that have flown over by airport
	*
	* @return Array the airline list
	*
	*/
	public static function countAllAirlinesByAirport($airport_icao)
	{
		$airport_icao = filter_var($airport_icao,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.airline_name, spotter_output.airline_icao, spotter_output.airline_country, COUNT(spotter_output.airline_name) AS airline_count
		 			FROM spotter_output
					WHERE spotter_output.airline_name <> '' AND (spotter_output.departure_airport_icao = :airport_icao OR spotter_output.arrival_airport_icao = :airport_icao ) 
                    GROUP BY spotter_output.airline_name
					ORDER BY airline_count DESC";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':airport_icao' => $airport_icao));
      
        $airline_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airline_name'] = $row['airline_name'];
            $temp_array['airline_icao'] = $row['airline_icao'];
            $temp_array['airline_count'] = $row['airline_count'];
            $temp_array['airline_country'] = $row['airline_country'];
          
            $airline_array[] = $temp_array;
		}

		return $airline_array;
	}
	
	
	/**
	* Gets all airline countries that have flown over by airport icao
	*
	* @return Array the airline country list
	*
	*/
	public static function countAllAirlineCountriesByAirport($airport_icao)
	{
		$airport_icao = filter_var($airport_icao,FILTER_SANITIZE_STRING);
      
		$query  = "SELECT DISTINCT spotter_output.airline_country, COUNT(spotter_output.airline_country) AS airline_country_count
		 			FROM spotter_output
					WHERE spotter_output.airline_country <> '' AND (spotter_output.departure_airport_icao = :airport_icao OR spotter_output.arrival_airport_icao = :airport_icao )
                    GROUP BY spotter_output.airline_country
					ORDER BY airline_country_count DESC
					LIMIT 0,10";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':airport_icao' => $airport_icao));
      
        $airline_country_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airline_country_count'] = $row['airline_country_count'];
            $temp_array['airline_country'] = $row['airline_country'];
          
            $airline_country_array[] = $temp_array;
		}

		return $airline_country_array;
	}
	
	
	/**
	* Gets all airlines that have flown over by aircraft manufacturer
	*
	* @return Array the airline list
	*
	*/
	public static function countAllAirlinesByManufacturer($aircraft_manufacturer)
	{
		$aircraft_manufacturer = filter_var($aircraft_manufacturer,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.airline_name, spotter_output.airline_icao, spotter_output.airline_country, COUNT(spotter_output.airline_name) AS airline_count
		 			FROM spotter_output
					WHERE spotter_output.aircraft_manufacturer = :aircraft_manufacturer 
                    GROUP BY spotter_output.airline_name
					ORDER BY airline_count DESC";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':aircraft_manufacturer' => $aircraft_manufacturer));
      
        $airline_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airline_name'] = $row['airline_name'];
            $temp_array['airline_icao'] = $row['airline_icao'];
            $temp_array['airline_count'] = $row['airline_count'];
            $temp_array['airline_country'] = $row['airline_country'];
          
            $airline_array[] = $temp_array;
		}

		return $airline_array;
	}
	
	
	
	
	/**
	* Gets all airline countries that have flown over by aircraft manufacturer
	*
	* @return Array the airline country list
	*
	*/
	public static function countAllAirlineCountriesByManufacturer($aircraft_manufacturer)
	{
		$aircraft_manufacturer = filter_var($aircraft_manufacturer,FILTER_SANITIZE_STRING);
      
		$query  = "SELECT DISTINCT spotter_output.airline_country, COUNT(spotter_output.airline_country) AS airline_country_count
		 			FROM spotter_output
					WHERE spotter_output.airline_country <> '' AND spotter_output.aircraft_manufacturer = :aircraft_manufacturer 
                    GROUP BY spotter_output.airline_country
					ORDER BY airline_country_count DESC
					LIMIT 0,10";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':aircraft_manufacturer' => $aircraft_manufacturer));
      
        $airline_country_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airline_country_count'] = $row['airline_country_count'];
            $temp_array['airline_country'] = $row['airline_country'];
          
            $airline_country_array[] = $temp_array;
		}

		return $airline_country_array;

	}
	
	
	
	/**
	* Gets all airlines that have flown over by date
	*
	* @return Array the airline list
	*
	*/
	public static function countAllAirlinesByDate($date)
	{
		$date = filter_var($date,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.airline_name, spotter_output.airline_icao, spotter_output.airline_country, COUNT(spotter_output.airline_name) AS airline_count
		 			FROM spotter_output
					WHERE DATE(CONVERT_TZ(spotter_output.date,'+00:00', '-04:00')) = :date 
           GROUP BY spotter_output.airline_name
					ORDER BY airline_count DESC";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':date' => $date));
      
        $airline_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airline_name'] = $row['airline_name'];
            $temp_array['airline_icao'] = $row['airline_icao'];
            $temp_array['airline_count'] = $row['airline_count'];
            $temp_array['airline_country'] = $row['airline_country'];
          
            $airline_array[] = $temp_array;
		}

		return $airline_array;
	}	
	
	
	/**
	* Gets all airline countries that have flown over by date
	*
	* @return Array the airline country list
	*
	*/
	public static function countAllAirlineCountriesByDate($date)
	{
		$date = filter_var($date,FILTER_SANITIZE_STRING);
      
		$query  = "SELECT DISTINCT spotter_output.airline_country, COUNT(spotter_output.airline_country) AS airline_country_count
		 			FROM spotter_output
					WHERE spotter_output.airline_country <> '' AND DATE(CONVERT_TZ(spotter_output.date,'+00:00', '-04:00')) = :date 
                    GROUP BY spotter_output.airline_country
					ORDER BY airline_country_count DESC
					LIMIT 0,10";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':date' => $date));
      
        $airline_country_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airline_country_count'] = $row['airline_country_count'];
            $temp_array['airline_country'] = $row['airline_country'];
          
            $airline_country_array[] = $temp_array;
		}

		return $airline_country_array;

	}
	
	
	/**
	* Gets all airlines that have flown over by ident/callsign
	*
	* @return Array the airline list
	*
	*/
	public static function countAllAirlinesByIdent($ident)
	{
		$ident = filter_var($ident,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.airline_name, spotter_output.airline_icao, spotter_output.airline_country, COUNT(spotter_output.airline_name) AS airline_count
		 			FROM spotter_output
					WHERE spotter_output.ident = :ident  
           GROUP BY spotter_output.airline_name
					ORDER BY airline_count DESC";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':ident' => $ident));
      
        $airline_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airline_name'] = $row['airline_name'];
            $temp_array['airline_icao'] = $row['airline_icao'];
            $temp_array['airline_count'] = $row['airline_count'];
            $temp_array['airline_country'] = $row['airline_country'];
          
            $airline_array[] = $temp_array;
		}

		return $airline_array;
	}

	
	
	
	/**
	* Gets all airlines that have flown over by route
	*
	* @return Array the airline list
	*
	*/
	public static function countAllAirlinesByRoute($departure_airport_icao, $arrival_airport_icao)
	{
		$departure_airport_icao = filter_var($departure_airport_icao,FILTER_SANITIZE_STRING);
		$arrival_airport_icao = filter_var($arrival_airport_icao,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.airline_name, spotter_output.airline_icao, spotter_output.airline_country, COUNT(spotter_output.airline_name) AS airline_count
		 			FROM spotter_output
					WHERE (spotter_output.departure_airport_icao = :departure_airport_icao) AND (spotter_output.arrival_airport_icao = :arrival_airport_icao) 
           GROUP BY spotter_output.airline_name
					ORDER BY airline_count DESC";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':departure_airport_icao' => $departure_airport_icao,':arrival_airport_icao' => $arrival_airport_icao));
      
        $airline_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airline_name'] = $row['airline_name'];
            $temp_array['airline_icao'] = $row['airline_icao'];
            $temp_array['airline_count'] = $row['airline_count'];
            $temp_array['airline_country'] = $row['airline_country'];
          
            $airline_array[] = $temp_array;
		}

		return $airline_array;
	}
	
	
	
	/**
	* Gets all airline countries that have flown over by route
	*
	* @return Array the airline country list
	*
	*/
	public static function countAllAirlineCountriesByRoute($departure_airport_icao, $arrival_airport_icao)
	{
		$departure_airport_icao = filter_var($departure_airport_icao,FILTER_SANITIZE_STRING);
		$arrival_airport_icao = filter_var($arrival_airport_icao,FILTER_SANITIZE_STRING);
      
		$query  = "SELECT DISTINCT spotter_output.airline_country, COUNT(spotter_output.airline_country) AS airline_country_count
		 			FROM spotter_output
					WHERE spotter_output.airline_country <> '' AND (spotter_output.departure_airport_icao = :departure_airport_icao) AND (spotter_output.arrival_airport_icao = :arrival_airport_icao) 
                    GROUP BY spotter_output.airline_country
					ORDER BY airline_country_count DESC
					LIMIT 0,10";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':departure_airport_icao' => $departure_airport_icao,':arrival_airport_icao' => $arrival_airport_icao));
      
        $airline_country_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airline_country_count'] = $row['airline_country_count'];
            $temp_array['airline_country'] = $row['airline_country'];
          
            $airline_country_array[] = $temp_array;
		}

		return $airline_country_array;

	}
	
	
	/**
	* Gets all airlines that have flown over by country
	*
	* @return Array the airline list
	*
	*/
	public static function countAllAirlinesByCountry($country)
	{
		$country = filter_var($country,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.airline_name, spotter_output.airline_icao, spotter_output.airline_country, COUNT(spotter_output.airline_name) AS airline_count
		 			FROM spotter_output
					WHERE ((spotter_output.departure_airport_country = :country) OR (spotter_output.arrival_airport_country = :country)) OR spotter_output.airline_country = :country  
           GROUP BY spotter_output.airline_name
					ORDER BY airline_count DESC";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':country' => $country));
      
        $airline_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airline_name'] = $row['airline_name'];
            $temp_array['airline_icao'] = $row['airline_icao'];
            $temp_array['airline_count'] = $row['airline_count'];
            $temp_array['airline_country'] = $row['airline_country'];
          
            $airline_array[] = $temp_array;
		}

		return $airline_array;
	}
	
	
	/**
	* Gets all airline countries that have flown over by country
	*
	* @return Array the airline country list
	*
	*/
	public static function countAllAirlineCountriesByCountry($country)
	{
		$country = filter_var($country,FILTER_SANITIZE_STRING);
      
		$query  = "SELECT DISTINCT spotter_output.airline_country, COUNT(spotter_output.airline_country) AS airline_country_count
		 			FROM spotter_output
					WHERE spotter_output.airline_country <> '' AND ((spotter_output.departure_airport_country = :country) OR (spotter_output.arrival_airport_country = :country)) OR spotter_output.airline_country = :country 
                    GROUP BY spotter_output.airline_country
					ORDER BY airline_country_count DESC
					LIMIT 0,10";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':country' => $country));
      
        $airline_country_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airline_country_count'] = $row['airline_country_count'];
            $temp_array['airline_country'] = $row['airline_country'];
          
            $airline_country_array[] = $temp_array;
		}

		return $airline_country_array;

	}
	
	
	
	/**
	* Gets all airlines countries
	*
	* @return Array the airline country list
	*
	*/
	public static function countAllAirlineCountries()
	{
		$query  = "SELECT DISTINCT spotter_output.airline_country, COUNT(spotter_output.airline_country) AS airline_country_count
		 			FROM spotter_output
					WHERE spotter_output.airline_country <> '' 
                    GROUP BY spotter_output.airline_country
					ORDER BY airline_country_count DESC
					LIMIT 0,10";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute();
      
        $airline_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airline_country_count'] = $row['airline_country_count'];
            $temp_array['airline_country'] = $row['airline_country'];
          
            $airline_array[] = $temp_array;
		}

		return $airline_array;
	}
	
	
	/**
	* Gets all aircraft types that have flown over
	*
	* @return Array the aircraft list
	*
	*/
	public static function countAllAircraftTypes()
	{
		$query  = "SELECT DISTINCT spotter_output.aircraft_icao, COUNT(spotter_output.aircraft_icao) AS aircraft_icao_count, spotter_output.aircraft_name  
                    FROM spotter_output
                    WHERE spotter_output.aircraft_name  <> '' 
                    GROUP BY spotter_output.aircraft_name 
					ORDER BY aircraft_icao_count DESC
					LIMIT 0,10";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute();
      
        $aircraft_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['aircraft_icao'] = $row['aircraft_icao'];
			$temp_array['aircraft_name'] = $row['aircraft_name'];
            $temp_array['aircraft_icao_count'] = $row['aircraft_icao_count'];
          
            $aircraft_array[] = $temp_array;
		}

		return $aircraft_array;
	}
	
	
	/**
	* Gets all aircraft registration that have flown over by aircaft icao
	*
	* @return Array the aircraft list
	*
	*/
	public static function countAllAircraftRegistrationByAircraft($aircraft_icao)
	{
		$aircraft_icao = filter_var($aircraft_icao,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.aircraft_icao, COUNT(spotter_output.registration) AS registration_count, spotter_output.aircraft_name, spotter_output.registration, spotter_output.airline_name  
                    FROM spotter_output
                    WHERE spotter_output.registration <> '' AND spotter_output.aircraft_icao = :aircraft_icao  
                    GROUP BY spotter_output.registration 
					ORDER BY registration_count DESC";

		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':aircraft_icao' => $aircraft_icao));
      
        $aircraft_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['aircraft_icao'] = $row['aircraft_icao'];
			$temp_array['aircraft_name'] = $row['aircraft_name'];
			$temp_array['registration'] = $row['registration'];
            $temp_array['airline_name'] = $row['airline_name'];
			$temp_array['image_thumbnail'] = "";
            if($row['registration'] != "")
              {
                  $image_array = Spotter::getSpotterImage($row['registration']);
                  $temp_array['image_thumbnail'] = $image_array[0]['image_thumbnail'];
              }
            $temp_array['registration_count'] = $row['registration_count'];
          
            $aircraft_array[] = $temp_array;
		}

		return $aircraft_array;
	}

	
	
	
	/**
	* Gets all aircraft types that have flown over by airline icao
	*
	* @return Array the aircraft list
	*
	*/
	public static function countAllAircraftTypesByAirline($airline_icao)
	{
		$airline_icao = filter_var($airline_icao,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.aircraft_icao, COUNT(spotter_output.aircraft_icao) AS aircraft_icao_count, spotter_output.aircraft_name  
                    FROM spotter_output
                    WHERE spotter_output.aircraft_icao <> '' AND spotter_output.airline_icao = :airline_icao 
                    GROUP BY spotter_output.aircraft_name 
					ORDER BY aircraft_icao_count DESC";

		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':airline_icao' => $airline_icao));
      
        $aircraft_array = array();
		$temp_array = array();
        
        while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['aircraft_icao'] = $row['aircraft_icao'];
			$temp_array['aircraft_name'] = $row['aircraft_name'];
            $temp_array['aircraft_icao_count'] = $row['aircraft_icao_count'];
          
            $aircraft_array[] = $temp_array;
		}

		return $aircraft_array;
	}
	
	
	/**
	* Gets all aircraft registration that have flown over by airline icao
	*
	* @return Array the aircraft list
	*
	*/
	public static function countAllAircraftRegistrationByAirline($airline_icao)
	{
		$airline_icao = filter_var($airline_icao,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.aircraft_icao, COUNT(spotter_output.registration) AS registration_count, spotter_output.aircraft_name, spotter_output.registration, spotter_output.airline_name   
                    FROM spotter_output
                    WHERE spotter_output.registration <> '' AND spotter_output.airline_icao = :airline_icao 
                    GROUP BY spotter_output.registration 
					ORDER BY registration_count DESC";

		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':airline_icao' => $airline_icao));
      
        $aircraft_array = array();
		$temp_array = array();
        
        while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['aircraft_icao'] = $row['aircraft_icao'];
			$temp_array['aircraft_name'] = $row['aircraft_name'];
			$temp_array['registration'] = $row['registration'];
            $temp_array['airline_name'] = $row['airline_name'];
			$temp_array['image_thumbnail'] = "";
            if($row['registration'] != "")
              {
                  $image_array = Spotter::getSpotterImage($row['registration']);
                  $temp_array['image_thumbnail'] = $image_array[0]['image_thumbnail'];
              }
            $temp_array['registration_count'] = $row['registration_count'];
          
            $aircraft_array[] = $temp_array;
		}

		return $aircraft_array;
	}
	
	
	
	/**
	* Gets all aircraft manufacturer that have flown over by airline icao
	*
	* @return Array the aircraft list
	*
	*/
	public static function countAllAircraftManufacturerByAirline($airline_icao)
	{
		$airline_icao = filter_var($airline_icao,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.aircraft_manufacturer, COUNT(spotter_output.aircraft_manufacturer) AS aircraft_manufacturer_count  
                    FROM spotter_output
                    WHERE spotter_output.aircraft_manufacturer <> '' AND spotter_output.airline_icao = :airline_icao 
                    GROUP BY spotter_output.aircraft_manufacturer 
					ORDER BY aircraft_manufacturer_count DESC";

		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':airline_icao' => $airline_icao));
      
    $aircraft_array = array();
		$temp_array = array();
        
    while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['aircraft_manufacturer'] = $row['aircraft_manufacturer'];
			$temp_array['aircraft_manufacturer_count'] = $row['aircraft_manufacturer_count'];
          
      $aircraft_array[] = $temp_array;
		}

		return $aircraft_array;
	}	
	
	
	/**
	* Gets all aircraft types that have flown over by airline icao
	*
	* @return Array the aircraft list
	*
	*/
	public static function countAllAircraftTypesByAirport($airport_icao)
	{
		$airport_icao = filter_var($airport_icao,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.aircraft_icao, COUNT(spotter_output.aircraft_icao) AS aircraft_icao_count, spotter_output.aircraft_name  
                    FROM spotter_output
                    WHERE spotter_output.aircraft_icao <> '' AND (spotter_output.departure_airport_icao = :airport_icao OR spotter_output.arrival_airport_icao = :airport_icao) 
                    GROUP BY spotter_output.aircraft_name 
					ORDER BY aircraft_icao_count DESC";
 
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':airport_icao' => $airport_icao));
      
        $aircraft_array = array();
		$temp_array = array();
        
        while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['aircraft_icao'] = $row['aircraft_icao'];
			$temp_array['aircraft_name'] = $row['aircraft_name'];
            $temp_array['aircraft_icao_count'] = $row['aircraft_icao_count'];
          
            $aircraft_array[] = $temp_array;
		}

		return $aircraft_array;
	}
	
	
	/**
	* Gets all aircraft registration that have flown over by airport icao
	*
	* @return Array the aircraft list
	*
	*/
	public static function countAllAircraftRegistrationByAirport($airport_icao)
	{
		$airport_icao = filter_var($airport_icao,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.aircraft_icao, COUNT(spotter_output.registration) AS registration_count, spotter_output.aircraft_name, spotter_output.registration, spotter_output.airline_name  
                    FROM spotter_output
                    WHERE spotter_output.registration <> '' AND (spotter_output.departure_airport_icao = :airport_icao OR spotter_output.arrival_airport_icao = :airport_icao)   
                    GROUP BY spotter_output.registration 
					ORDER BY registration_count DESC";

		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':airport_icao' => $airport_icao));
      
        $aircraft_array = array();
		$temp_array = array();
        
        while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['aircraft_icao'] = $row['aircraft_icao'];
			$temp_array['aircraft_name'] = $row['aircraft_name'];
			$temp_array['registration'] = $row['registration'];
            $temp_array['airline_name'] = $row['airline_name'];
			$temp_array['image_thumbnail'] = "";
            if($row['registration'] != "")
              {
                  $image_array = Spotter::getSpotterImage($row['registration']);
                  $temp_array['image_thumbnail'] = $image_array[0]['image_thumbnail'];
              }
            $temp_array['registration_count'] = $row['registration_count'];
          
            $aircraft_array[] = $temp_array;
		}

		return $aircraft_array;
	}
	
	
	/**
	* Gets all aircraft manufacturer that have flown over by airport icao
	*
	* @return Array the aircraft list
	*
	*/
	public static function countAllAircraftManufacturerByAirport($airport_icao)
	{
		$airport_icao = filter_var($airport_icao,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.aircraft_manufacturer, COUNT(spotter_output.aircraft_manufacturer) AS aircraft_manufacturer_count  
                    FROM spotter_output
                    WHERE spotter_output.aircraft_manufacturer <> '' AND (spotter_output.departure_airport_icao = :airport_icao OR spotter_output.arrival_airport_icao = :airport_icao)  
                    GROUP BY spotter_output.aircraft_manufacturer 
					ORDER BY aircraft_manufacturer_count DESC";

		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':airport_icao' => $airport_icao));
      
    $aircraft_array = array();
		$temp_array = array();
        
    while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['aircraft_manufacturer'] = $row['aircraft_manufacturer'];
			$temp_array['aircraft_manufacturer_count'] = $row['aircraft_manufacturer_count'];
          
      $aircraft_array[] = $temp_array;
		}

		return $aircraft_array;
	}	

	
	
	/**
	* Gets all aircraft types that have flown over by aircraft manufacturer
	*
	* @return Array the aircraft list
	*
	*/
	public static function countAllAircraftTypesByManufacturer($aircraft_manufacturer)
	{
		$aircraft_manufacturer = filter_var($aircraft_manufacturer,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.aircraft_icao, COUNT(spotter_output.aircraft_icao) AS aircraft_icao_count, spotter_output.aircraft_name  
                    FROM spotter_output
                    WHERE spotter_output.aircraft_manufacturer = :aircraft_manufacturer
                    GROUP BY spotter_output.aircraft_name 
					ORDER BY aircraft_icao_count DESC";
 
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':aircraft_manufacturer' => $aircraft_manufacturer));
      
        $aircraft_array = array();
		$temp_array = array();
        
        while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['aircraft_icao'] = $row['aircraft_icao'];
			$temp_array['aircraft_name'] = $row['aircraft_name'];
            $temp_array['aircraft_icao_count'] = $row['aircraft_icao_count'];
          
            $aircraft_array[] = $temp_array;
		}

		return $aircraft_array;
	}
	
	
	/**
	* Gets all aircraft registration that have flown over by aircaft manufacturer
	*
	* @return Array the aircraft list
	*
	*/
	public static function countAllAircraftRegistrationByManufacturer($aircraft_manufacturer)
	{
		$aircraft_manufacturer = filter_var($aircraft_manufacturer,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.aircraft_icao, COUNT(spotter_output.registration) AS registration_count, spotter_output.aircraft_name, spotter_output.registration, spotter_output.airline_name   
                    FROM spotter_output
                    WHERE spotter_output.registration <> '' AND spotter_output.aircraft_manufacturer = :aircraft_manufacturer   
                    GROUP BY spotter_output.registration 
					ORDER BY registration_count DESC";

		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':aircraft_manufacturer' => $aircraft_manufacturer));
      
        $aircraft_array = array();
		$temp_array = array();
        
        while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['aircraft_icao'] = $row['aircraft_icao'];
			$temp_array['aircraft_name'] = $row['aircraft_name'];
			$temp_array['registration'] = $row['registration'];
            $temp_array['airline_name'] = $row['airline_name'];
			$temp_array['image_thumbnail'] = "";
            if($row['registration'] != "")
              {
                  $image_array = Spotter::getSpotterImage($row['registration']);
                  $temp_array['image_thumbnail'] = $image_array[0]['image_thumbnail'];
              }
            $temp_array['registration_count'] = $row['registration_count'];
          
            $aircraft_array[] = $temp_array;
		}

		return $aircraft_array;
	}
	
	
	
	/**
	* Gets all aircraft types that have flown over by date
	*
	* @return Array the aircraft list
	*
	*/
	public static function countAllAircraftTypesByDate($date)
	{
		$date = filter_var($date,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.aircraft_icao, COUNT(spotter_output.aircraft_icao) AS aircraft_icao_count, spotter_output.aircraft_name  
                    FROM spotter_output
                    WHERE DATE(CONVERT_TZ(spotter_output.date,'+00:00', '-04:00')) = :date
                    GROUP BY spotter_output.aircraft_name 
					ORDER BY aircraft_icao_count DESC";
 
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':date' => $date));
      
        $aircraft_array = array();
		$temp_array = array();
        
        while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['aircraft_icao'] = $row['aircraft_icao'];
			$temp_array['aircraft_name'] = $row['aircraft_name'];
            $temp_array['aircraft_icao_count'] = $row['aircraft_icao_count'];
          
            $aircraft_array[] = $temp_array;
		}

		return $aircraft_array;
	}
	
	
	/**
	* Gets all aircraft registration that have flown over by date
	*
	* @return Array the aircraft list
	*
	*/
	public static function countAllAircraftRegistrationByDate($date)
	{
		$date = filter_var($date,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.aircraft_icao, COUNT(spotter_output.registration) AS registration_count, spotter_output.aircraft_name, spotter_output.registration, spotter_output.airline_name    
                    FROM spotter_output
                    WHERE spotter_output.
                    registration <> '' AND DATE(CONVERT_TZ(spotter_output.date,'+00:00', '-04:00')) = :date   
                    GROUP BY spotter_output.registration 
					ORDER BY registration_count DESC";

		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':date' => $date));
      
        $aircraft_array = array();
		$temp_array = array();
        
        while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['aircraft_icao'] = $row['aircraft_icao'];
			$temp_array['aircraft_name'] = $row['aircraft_name'];
			$temp_array['registration'] = $row['registration'];
            $temp_array['airline_name'] = $row['airline_name'];
			$temp_array['image_thumbnail'] = "";
            if($row['registration'] != "")
              {
                  $image_array = Spotter::getSpotterImage($row['registration']);
                  $temp_array['image_thumbnail'] = $image_array[0]['image_thumbnail'];
              }
            $temp_array['registration_count'] = $row['registration_count'];
          
            $aircraft_array[] = $temp_array;
		}

		return $aircraft_array;
	}
	
	
	/**
	* Gets all aircraft manufacturer that have flown over by date
	*
	* @return Array the aircraft manufacturer list
	*
	*/
	public static function countAllAircraftManufacturerByDate($date)
	{
		$date = filter_var($date,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.aircraft_manufacturer, COUNT(spotter_output.aircraft_manufacturer) AS aircraft_manufacturer_count  
                    FROM spotter_output
                    WHERE spotter_output.aircraft_manufacturer <> '' AND DATE(CONVERT_TZ(spotter_output.date,'+00:00', '-04:00')) = :date 
                    GROUP BY spotter_output.aircraft_manufacturer 
					ORDER BY aircraft_manufacturer_count DESC";

		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':date' => $date));
      
    $aircraft_array = array();
		$temp_array = array();
        
    while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['aircraft_manufacturer'] = $row['aircraft_manufacturer'];
			$temp_array['aircraft_manufacturer_count'] = $row['aircraft_manufacturer_count'];
          
      $aircraft_array[] = $temp_array;
		}

		return $aircraft_array;
	}	

	
	
	/**
	* Gets all aircraft types that have flown over by ident/callsign
	*
	* @return Array the aircraft list
	*
	*/
	public static function countAllAircraftTypesByIdent($ident)
	{
		$ident = filter_var($ident,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.aircraft_icao, COUNT(spotter_output.aircraft_icao) AS aircraft_icao_count, spotter_output.aircraft_name  
                    FROM spotter_output
                    WHERE spotter_output.ident = :ident 
                    GROUP BY spotter_output.aircraft_name 
					ORDER BY aircraft_icao_count DESC";
 
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':ident' => $ident));
      
        $aircraft_array = array();
		$temp_array = array();
        
        while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['aircraft_icao'] = $row['aircraft_icao'];
			$temp_array['aircraft_name'] = $row['aircraft_name'];
            $temp_array['aircraft_icao_count'] = $row['aircraft_icao_count'];
          
            $aircraft_array[] = $temp_array;
		}

		return $aircraft_array;
	}
	
	
	/**
	* Gets all aircraft registration that have flown over by ident/callsign
	*
	* @return Array the aircraft list
	*
	*/
	public static function countAllAircraftRegistrationByIdent($ident)
	{
		$ident = filter_var($ident,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.aircraft_icao, COUNT(spotter_output.registration) AS registration_count, spotter_output.aircraft_name, spotter_output.registration, spotter_output.airline_name  
                    FROM spotter_output
                    WHERE spotter_output.registration <> '' AND spotter_output.ident = :ident   
                    GROUP BY spotter_output.registration 
					ORDER BY registration_count DESC";

		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':ident' => $ident));
      
        $aircraft_array = array();
		$temp_array = array();
        
        while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['aircraft_icao'] = $row['aircraft_icao'];
			$temp_array['aircraft_name'] = $row['aircraft_name'];
			$temp_array['registration'] = $row['registration'];
            $temp_array['airline_name'] = $row['airline_name'];
			$temp_array['image_thumbnail'] = "";
            if($row['registration'] != "")
              {
                  $image_array = Spotter::getSpotterImage($row['registration']);
                  $temp_array['image_thumbnail'] = $image_array[0]['image_thumbnail'];
              }
            $temp_array['registration_count'] = $row['registration_count'];
          
            $aircraft_array[] = $temp_array;
		}

		return $aircraft_array;
	}
	
	
	/**
	* Gets all aircraft manufacturer that have flown over by ident/callsign
	*
	* @return Array the aircraft manufacturer list
	*
	*/
	public static function countAllAircraftManufacturerByIdent($ident)
	{
		$ident = filter_var($ident,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.aircraft_manufacturer, COUNT(spotter_output.aircraft_manufacturer) AS aircraft_manufacturer_count  
                    FROM spotter_output
                    WHERE spotter_output.aircraft_manufacturer <> '' AND spotter_output.ident = :ident  
                    GROUP BY spotter_output.aircraft_manufacturer 
					ORDER BY aircraft_manufacturer_count DESC";

		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':ident' => $ident));
      
    $aircraft_array = array();
		$temp_array = array();
        
    while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['aircraft_manufacturer'] = $row['aircraft_manufacturer'];
			$temp_array['aircraft_manufacturer_count'] = $row['aircraft_manufacturer_count'];
          
      $aircraft_array[] = $temp_array;
		}

		return $aircraft_array;
	}	
	
	
	/**
	* Gets all aircraft types that have flown over by route
	*
	* @return Array the aircraft list
	*
	*/
	public static function countAllAircraftTypesByRoute($departure_airport_icao, $arrival_airport_icao)
	{
		$departure_airport_icao = filter_var($departure_airport_icao,FILTER_SANITIZE_STRING);
		$arrival_airport_icao = filter_var($arrival_airport_icao,FILTER_SANITIZE_STRING);
		

		$query  = "SELECT DISTINCT spotter_output.aircraft_icao, COUNT(spotter_output.aircraft_icao) AS aircraft_icao_count, spotter_output.aircraft_name  
                    FROM spotter_output
                    WHERE (spotter_output.departure_airport_icao = :departure_airport_icao) AND (spotter_output.arrival_airport_icao = :arrival_airport_icao)
                    GROUP BY spotter_output.aircraft_name 
					ORDER BY aircraft_icao_count DESC";
 
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':departure_airport_icao' => $departure_airport_icao,':arrival_airport_icao' => $arrival_airport_icao));
      
        $aircraft_array = array();
		$temp_array = array();
        
        while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['aircraft_icao'] = $row['aircraft_icao'];
			$temp_array['aircraft_name'] = $row['aircraft_name'];
            $temp_array['aircraft_icao_count'] = $row['aircraft_icao_count'];
          
            $aircraft_array[] = $temp_array;
		}

		return $aircraft_array;
	}
	
	
	/**
	* Gets all aircraft registration that have flown over by route
	*
	* @return Array the aircraft list
	*
	*/
	public static function countAllAircraftRegistrationByRoute($departure_airport_icao, $arrival_airport_icao)
	{
		$departure_airport_icao = filter_var($departure_airport_icao,FILTER_SANITIZE_STRING);
		$arrival_airport_icao = filter_var($arrival_airport_icao,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.aircraft_icao, COUNT(spotter_output.registration) AS registration_count, spotter_output.aircraft_name, spotter_output.registration, spotter_output.airline_name   
                    FROM spotter_output
                    WHERE spotter_output.registration <> '' AND (spotter_output.departure_airport_icao = :departure_airport_icao) AND (spotter_output.arrival_airport_icao = :arrival_airport_icao)   
                    GROUP BY spotter_output.registration 
					ORDER BY registration_count DESC";

		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':departure_airport_icao' => $departure_airport_icao,':arrival_airport_icao' => $arrival_airport_icao));
      
		$aircraft_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['aircraft_icao'] = $row['aircraft_icao'];
			$temp_array['aircraft_name'] = $row['aircraft_name'];
			$temp_array['registration'] = $row['registration'];
			$temp_array['airline_name'] = $row['airline_name'];
			$temp_array['image_thumbnail'] = "";
			if($row['registration'] != "")
			{
				$image_array = Spotter::getSpotterImage($row['registration']);
				$temp_array['image_thumbnail'] = $image_array[0]['image_thumbnail'];
			}
			$temp_array['registration_count'] = $row['registration_count'];
          
			$aircraft_array[] = $temp_array;
		}

		return $aircraft_array;
	}
	
	
	/**
	* Gets all aircraft manufacturer that have flown over by route
	*
	* @return Array the aircraft manufacturer list
	*
	*/
	public static function countAllAircraftManufacturerByRoute($departure_airport_icao, $arrival_airport_icao)
	{
		$departure_airport_icao = filter_var($departure_airport_icao,FILTER_SANITIZE_STRING);
		$arrival_airport_icao = filter_var($arrival_airport_icao,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.aircraft_manufacturer, COUNT(spotter_output.aircraft_manufacturer) AS aircraft_manufacturer_count  
                    FROM spotter_output
                    WHERE spotter_output.aircraft_manufacturer <> '' AND (spotter_output.departure_airport_icao = :departure_airport_icao) AND (spotter_output.arrival_airport_icao = :arrival_airport_icao) 
                    GROUP BY spotter_output.aircraft_manufacturer 
					ORDER BY aircraft_manufacturer_count DESC";

		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':departure_airport_icao' => $departure_airport_icao,':arrival_airport_icao' => $arrival_airport_icao));
      
		$aircraft_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['aircraft_manufacturer'] = $row['aircraft_manufacturer'];
			$temp_array['aircraft_manufacturer_count'] = $row['aircraft_manufacturer_count'];
          
			$aircraft_array[] = $temp_array;
		}

		return $aircraft_array;
	}	

	
	
	
	/**
	* Gets all aircraft types that have flown over by country
	*
	* @return Array the aircraft list
	*
	*/
	public static function countAllAircraftTypesByCountry($country)
	{
		$country = filter_var($country,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.aircraft_icao, COUNT(spotter_output.aircraft_icao) AS aircraft_icao_count, spotter_output.aircraft_name  
                    FROM spotter_output
                    WHERE ((spotter_output.departure_airport_country = :country) OR (spotter_output.arrival_airport_country = :country)) OR spotter_output.airline_country = :country 
                    GROUP BY spotter_output.aircraft_name 
					ORDER BY aircraft_icao_count DESC";
 
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':country' => $country));
      
		$aircraft_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['aircraft_icao'] = $row['aircraft_icao'];
			$temp_array['aircraft_name'] = $row['aircraft_name'];
			$temp_array['aircraft_icao_count'] = $row['aircraft_icao_count'];
          
			$aircraft_array[] = $temp_array;
		}

		return $aircraft_array;
	}


	/**
	* Gets all aircraft registration that have flown over by country
	*
	* @return Array the aircraft list
	*
	*/
	public static function countAllAircraftRegistrationByCountry($country)
	{
		$country = filter_var($country,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.aircraft_icao, COUNT(spotter_output.registration) AS registration_count, spotter_output.aircraft_name, spotter_output.registration, spotter_output.airline_name 
                    FROM spotter_output
                    WHERE spotter_output.registration <> '' AND (((spotter_output.departure_airport_country = :country) OR (spotter_output.arrival_airport_country = :country)) OR spotter_output.airline_country = :country)    
                    GROUP BY spotter_output.registration 
					ORDER BY registration_count DESC";

		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':country' => $country));
      
		$aircraft_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['aircraft_icao'] = $row['aircraft_icao'];
			$temp_array['aircraft_name'] = $row['aircraft_name'];
			$temp_array['registration'] = $row['registration'];
			$temp_array['airline_name'] = $row['airline_name'];
			$temp_array['image_thumbnail'] = "";
			if($row['registration'] != "")
			{
				$image_array = Spotter::getSpotterImage($row['registration']);
				$temp_array['image_thumbnail'] = $image_array[0]['image_thumbnail'];
			}
			$temp_array['registration_count'] = $row['registration_count'];
          
			$aircraft_array[] = $temp_array;
		}

		return $aircraft_array;
	}
	
	
	/**
	* Gets all aircraft manufacturer that have flown over by country
	*
	* @return Array the aircraft manufacturer list
	*
	*/
	public static function countAllAircraftManufacturerByCountry($country)
	{
		$country = filter_var($country,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.aircraft_manufacturer, COUNT(spotter_output.aircraft_manufacturer) AS aircraft_manufacturer_count  
                    FROM spotter_output
                    WHERE spotter_output.aircraft_manufacturer <> '' AND (((spotter_output.departure_airport_country = :country) OR (spotter_output.arrival_airport_country = :country)) OR spotter_output.airline_country = :country) 
                    GROUP BY spotter_output.aircraft_manufacturer 
					ORDER BY aircraft_manufacturer_count DESC";

		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':country' => $country));
      
		$aircraft_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['aircraft_manufacturer'] = $row['aircraft_manufacturer'];
			$temp_array['aircraft_manufacturer_count'] = $row['aircraft_manufacturer_count'];
          
			$aircraft_array[] = $temp_array;
		}

		return $aircraft_array;
	}	
	
	
	
	/**
	* Gets all aircraft manufacturers that have flown over
	*
	* @return Array the aircraft list
	*
	*/
	public static function countAllAircraftManufacturers()
	{
		$query  = "SELECT DISTINCT spotter_output.aircraft_manufacturer, COUNT(spotter_output.aircraft_manufacturer) AS aircraft_manufacturer_count  
                    FROM spotter_output 
                    WHERE spotter_output.aircraft_manufacturer <> '' 
                    GROUP BY spotter_output.aircraft_manufacturer
					ORDER BY aircraft_manufacturer_count DESC
					LIMIT 0,10";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute();
      
		$manufacturer_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['aircraft_manufacturer'] = $row['aircraft_manufacturer'];
			$temp_array['aircraft_manufacturer_count'] = $row['aircraft_manufacturer_count'];
          
			$manufacturer_array[] = $temp_array;
		}

		return $manufacturer_array;
	}
	
	
	
	/**
	* Gets all aircraft registrations that have flown over
	*
	* @return Array the aircraft list
	*
	*/
	public static function countAllAircraftRegistrations()
	{
		$query  = "SELECT DISTINCT spotter_output.registration, COUNT(spotter_output.registration) AS aircraft_registration_count, spotter_output.aircraft_icao,  spotter_output.aircraft_name, spotter_output.airline_name    
                    FROM spotter_output 
                    WHERE spotter_output.registration <> '' 
                    GROUP BY spotter_output.registration
					ORDER BY aircraft_registration_count DESC
					LIMIT 0,10";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute();
      
		$aircraft_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['registration'] = $row['registration'];
			$temp_array['aircraft_registration_count'] = $row['aircraft_registration_count'];
			$temp_array['aircraft_icao'] = $row['aircraft_icao'];
			$temp_array['aircraft_name'] = $row['aircraft_name'];
			$temp_array['airline_name'] = $row['airline_name'];
			$temp_array['image_thumbnail'] = "";
			if($row['registration'] != "")
			{
				$image_array = Spotter::getSpotterImage($row['registration']);
				$temp_array['image_thumbnail'] = $image_array[0]['image_thumbnail'];
			}
          
			$aircraft_array[] = $temp_array;
		}

		return $aircraft_array;
	}


	
	
	/**
	* Gets all departure airports of the airplanes that have flown over
	*
	* @return Array the airport list
	*
	*/
	public static function countAllDepartureAirports()
	{
		$query  = "SELECT DISTINCT spotter_output.departure_airport_icao, COUNT(spotter_output.departure_airport_icao) AS airport_departure_icao_count, spotter_output.departure_airport_name, spotter_output.departure_airport_city, spotter_output.departure_airport_country 
								FROM spotter_output
                    WHERE spotter_output.departure_airport_name <> '' 
                    GROUP BY spotter_output.departure_airport_icao
					ORDER BY airport_departure_icao_count DESC
					LIMIT 0,10";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute();
      
		$airport_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airport_departure_icao'] = $row['departure_airport_icao'];
			$temp_array['airport_departure_icao_count'] = $row['airport_departure_icao_count'];
			$temp_array['airport_departure_name'] = $row['departure_airport_name'];
			$temp_array['airport_departure_city'] = $row['departure_airport_city'];
			$temp_array['airport_departure_country'] = $row['departure_airport_country'];
          
			$airport_array[] = $temp_array;
		}

		return $airport_array;
	}
	
	
	
	/**
	* Gets all departure airports of the airplanes that have flown over based on an airline icao
	*
	* @return Array the airport list
	*
	*/
	public static function countAllDepartureAirportsByAirline($airline_icao)
	{
		$airline_icao = filter_var($airline_icao,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.departure_airport_icao, COUNT(spotter_output.departure_airport_icao) AS airport_departure_icao_count, spotter_output.departure_airport_name, spotter_output.departure_airport_city, spotter_output.departure_airport_country 
								FROM spotter_output
                    WHERE spotter_output.departure_airport_name <> '' AND spotter_output.airline_icao = :airline_icao 
                    GROUP BY spotter_output.departure_airport_icao
					ORDER BY airport_departure_icao_count DESC";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':airline_icao' => $airline_icao));
      
		$airport_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airport_departure_icao'] = $row['departure_airport_icao'];
			$temp_array['airport_departure_icao_count'] = $row['airport_departure_icao_count'];
			$temp_array['airport_departure_name'] = $row['departure_airport_name'];
			$temp_array['airport_departure_city'] = $row['departure_airport_city'];
			$temp_array['airport_departure_country'] = $row['departure_airport_country'];
          
			$airport_array[] = $temp_array;
		}

		return $airport_array;
	}
	
	
	
	/**
	* Gets all departure airports by country of the airplanes that have flown over based on an airline icao
	*
	* @return Array the airport list
	*
	*/
	public static function countAllDepartureAirportCountriesByAirline($airline_icao)
	{
		$airline_icao = filter_var($airline_icao,FILTER_SANITIZE_STRING);
					
		$query  = "SELECT DISTINCT spotter_output.departure_airport_country, COUNT(spotter_output.departure_airport_country) AS airport_departure_country_count 
								FROM spotter_output 
                    WHERE spotter_output.departure_airport_country <> '' AND spotter_output.airline_icao = :airline_icao 
                    GROUP BY spotter_output.departure_airport_country
					ORDER BY airport_departure_country_count DESC";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':airline_icao' => $airline_icao));
      
		$airport_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['departure_airport_country'] = $row['departure_airport_country'];
			$temp_array['airport_departure_country_count'] = $row['airport_departure_country_count'];
          
			$airport_array[] = $temp_array;
		}

		return $airport_array;
	}
	
	
	
	/**
	* Gets all departure airports of the airplanes that have flown over based on an aircraft icao
	*
	* @return Array the airport list
	*
	*/
	public static function countAllDepartureAirportsByAircraft($aircraft_icao)
	{
		$aircraft_icao = filter_var($aircraft_icao,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.departure_airport_icao, COUNT(spotter_output.departure_airport_icao) AS airport_departure_icao_count, spotter_output.departure_airport_name, spotter_output.departure_airport_city, spotter_output.departure_airport_country 
								FROM spotter_output
                    WHERE spotter_output.departure_airport_name <> '' AND spotter_output.aircraft_icao = :aircraft_icao 
                    GROUP BY spotter_output.departure_airport_icao
					ORDER BY airport_departure_icao_count DESC";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':aircraft_icao' => $aircraft_icao));
      
		$airport_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airport_departure_icao'] = $row['departure_airport_icao'];
			$temp_array['airport_departure_icao_count'] = $row['airport_departure_icao_count'];
			$temp_array['airport_departure_name'] = $row['departure_airport_name'];
			$temp_array['airport_departure_city'] = $row['departure_airport_city'];
			$temp_array['airport_departure_country'] = $row['departure_airport_country'];
          
			$airport_array[] = $temp_array;
		}

		return $airport_array;
	}
	
	
	/**
	* Gets all departure airports by country of the airplanes that have flown over based on an aircraft icao
	*
	* @return Array the airport list
	*
	*/
	public static function countAllDepartureAirportCountriesByAircraft($aircraft_icao)
	{
		$aircraft_icao = filter_var($aircraft_icao,FILTER_SANITIZE_STRING);
					
		$query  = "SELECT DISTINCT spotter_output.departure_airport_country, COUNT(spotter_output.departure_airport_country) AS airport_departure_country_count 
								FROM spotter_output 
                    WHERE spotter_output.departure_airport_country <> '' AND spotter_output.aircraft_icao = :aircraft_icao
                    GROUP BY spotter_output.departure_airport_country
					ORDER BY airport_departure_country_count DESC";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':aircraft_icao' => $aircraft_icao));
      
		$airport_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['departure_airport_country'] = $row['departure_airport_country'];
			$temp_array['airport_departure_country_count'] = $row['airport_departure_country_count'];
          
			$airport_array[] = $temp_array;
		}

		return $airport_array;
	}
	
	
	/**
	* Gets all departure airports of the airplanes that have flown over based on an aircraft registration
	*
	* @return Array the airport list
	*
	*/
	public static function countAllDepartureAirportsByRegistration($registration)
	{
		$registration = filter_var($registration,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.departure_airport_icao, COUNT(spotter_output.departure_airport_icao) AS airport_departure_icao_count, spotter_output.departure_airport_name, spotter_output.departure_airport_city, spotter_output.departure_airport_country 
								FROM spotter_output
                    WHERE spotter_output.departure_airport_name <> '' AND spotter_output.registration = :registration 
                    GROUP BY spotter_output.departure_airport_icao
					ORDER BY airport_departure_icao_count DESC";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':registration' => $registration));
      
		$airport_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airport_departure_icao'] = $row['departure_airport_icao'];
			$temp_array['airport_departure_icao_count'] = $row['airport_departure_icao_count'];
			$temp_array['airport_departure_name'] = $row['departure_airport_name'];
			$temp_array['airport_departure_city'] = $row['departure_airport_city'];
			$temp_array['airport_departure_country'] = $row['departure_airport_country'];
          
			$airport_array[] = $temp_array;
		}

		return $airport_array;
	}
	
	
	/**
	* Gets all departure airports by country of the airplanes that have flown over based on an aircraft registration
	*
	* @return Array the airport list
	*
	*/
	public static function countAllDepartureAirportCountriesByRegistration($registration)
	{
		$registration = filter_var($registration,FILTER_SANITIZE_STRING);
					
		$query  = "SELECT DISTINCT spotter_output.departure_airport_country, COUNT(spotter_output.departure_airport_country) AS airport_departure_country_count 
								FROM spotter_output 
                    WHERE spotter_output.departure_airport_country <> '' AND spotter_output.registration = :registration 
                    GROUP BY spotter_output.departure_airport_country
					ORDER BY airport_departure_country_count DESC";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':registration' => $registration));
      
		$airport_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['departure_airport_country'] = $row['departure_airport_country'];
			$temp_array['airport_departure_country_count'] = $row['airport_departure_country_count'];
          
			$airport_array[] = $temp_array;
		}

		return $airport_array;
	}
	
	
	/**
	* Gets all departure airports of the airplanes that have flown over based on an arrivl airport icao
	*
	* @return Array the airport list
	*
	*/
	public static function countAllDepartureAirportsByAirport($airport_icao)
	{
		$airport_icao = filter_var($airport_icao,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.departure_airport_icao, COUNT(spotter_output.departure_airport_icao) AS airport_departure_icao_count, spotter_output.departure_airport_name, spotter_output.departure_airport_city, spotter_output.departure_airport_country 
								FROM spotter_output
                    WHERE spotter_output.departure_airport_name <> '' AND spotter_output.arrival_airport_icao = :airport_icao 
                    GROUP BY spotter_output.departure_airport_icao
					ORDER BY airport_departure_icao_count DESC";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':airport_icao' => $airport_icao));
      
		$airport_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airport_departure_icao'] = $row['departure_airport_icao'];
			$temp_array['airport_departure_icao_count'] = $row['airport_departure_icao_count'];
			$temp_array['airport_departure_name'] = $row['departure_airport_name'];
			$temp_array['airport_departure_city'] = $row['departure_airport_city'];
			$temp_array['airport_departure_country'] = $row['departure_airport_country'];
          
			$airport_array[] = $temp_array;
		}

		return $airport_array;
	}
	
	
	/**
	* Gets all departure airports by country of the airplanes that have flown over based on an airport icao
	*
	* @return Array the airport list
	*
	*/
	public static function countAllDepartureAirportCountriesByAirport($airport_icao)
	{
		$airport_icao = filter_var($airport_icao,FILTER_SANITIZE_STRING);
					
		$query  = "SELECT DISTINCT spotter_output.departure_airport_country, COUNT(spotter_output.departure_airport_country) AS airport_departure_country_count 
								FROM spotter_output 
                    WHERE spotter_output.departure_airport_country <> '' AND spotter_output.arrival_airport_icao = :airport_icao 
                    GROUP BY spotter_output.departure_airport_country
					ORDER BY airport_departure_country_count DESC";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':airport_icao' => $airport_icao));
      
		$airport_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['departure_airport_country'] = $row['departure_airport_country'];
			$temp_array['airport_departure_country_count'] = $row['airport_departure_country_count'];
          
			$airport_array[] = $temp_array;
		}

		return $airport_array;
	}
	
	
	
	/**
	* Gets all departure airports of the airplanes that have flown over based on an aircraft manufacturer
	*
	* @return Array the airport list
	*
	*/
	public static function countAllDepartureAirportsByManufacturer($aircraft_manufacturer)
	{
		$aircraft_manufacturer = filter_var($aircraft_manufacturer,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.departure_airport_icao, COUNT(spotter_output.departure_airport_icao) AS airport_departure_icao_count, spotter_output.departure_airport_name, spotter_output.departure_airport_city, spotter_output.departure_airport_country 
								FROM spotter_output
                    WHERE spotter_output.departure_airport_name <> '' AND spotter_output.aircraft_manufacturer = :aircraft_manufacturer 
                    GROUP BY spotter_output.departure_airport_icao
					ORDER BY airport_departure_icao_count DESC";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':aircraft_manufacturer' => $aircraft_manufacturer));
      
		$airport_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airport_departure_icao'] = $row['departure_airport_icao'];
			$temp_array['airport_departure_icao_count'] = $row['airport_departure_icao_count'];
			$temp_array['airport_departure_name'] = $row['departure_airport_name'];
			$temp_array['airport_departure_city'] = $row['departure_airport_city'];
			$temp_array['airport_departure_country'] = $row['departure_airport_country'];
          
			$airport_array[] = $temp_array;
		}

		return $airport_array;
	}
	
	
	/**
	* Gets all departure airports by country of the airplanes that have flown over based on an aircraft manufacturer
	*
	* @return Array the airport list
	*
	*/
	public static function countAllDepartureAirportCountriesByManufacturer($aircraft_manufacturer)
	{
		$aircraft_manufacturer = filter_var($aircraft_manufacturer,FILTER_SANITIZE_STRING);
					
		$query  = "SELECT DISTINCT spotter_output.departure_airport_country, COUNT(spotter_output.departure_airport_country) AS airport_departure_country_count 
								FROM spotter_output 
                    WHERE spotter_output.departure_airport_country <> '' AND spotter_output.aircraft_manufacturer = :aircraft_manufacturer 
                    GROUP BY spotter_output.departure_airport_country
					ORDER BY airport_departure_country_count DESC";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':aircraft_manufacturer' => $aircraft_manufacturer));
      
		$airport_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['departure_airport_country'] = $row['departure_airport_country'];
			$temp_array['airport_departure_country_count'] = $row['airport_departure_country_count'];
          
			$airport_array[] = $temp_array;
		}

		return $airport_array;
	}
	
	
	/**
	* Gets all departure airports of the airplanes that have flown over based on a date
	*
	* @return Array the airport list
	*
	*/
	public static function countAllDepartureAirportsByDate($date)
	{
		$date = filter_var($date,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.departure_airport_icao, COUNT(spotter_output.departure_airport_icao) AS airport_departure_icao_count, spotter_output.departure_airport_name, spotter_output.departure_airport_city, spotter_output.departure_airport_country 
								FROM spotter_output
                    WHERE spotter_output.departure_airport_name <> '' AND DATE(CONVERT_TZ(spotter_output.date,'+00:00', '-04:00')) = :date
                    GROUP BY spotter_output.departure_airport_icao
					ORDER BY airport_departure_icao_count DESC";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':date' => $date));
      
		$airport_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airport_departure_icao'] = $row['departure_airport_icao'];
			$temp_array['airport_departure_icao_count'] = $row['airport_departure_icao_count'];
			$temp_array['airport_departure_name'] = $row['departure_airport_name'];
			$temp_array['airport_departure_city'] = $row['departure_airport_city'];
			$temp_array['airport_departure_country'] = $row['departure_airport_country'];
          
			$airport_array[] = $temp_array;
		}

		return $airport_array;
	}
	
	
	
	/**
	* Gets all departure airports by country of the airplanes that have flown over based on a date
	*
	* @return Array the airport list
	*
	*/
	public static function countAllDepartureAirportCountriesByDate($date)
	{
		$date = filter_var($date,FILTER_SANITIZE_STRING);
					
		$query  = "SELECT DISTINCT spotter_output.departure_airport_country, COUNT(spotter_output.departure_airport_country) AS airport_departure_country_count 
								FROM spotter_output 
                    WHERE spotter_output.departure_airport_country <> '' AND DATE(CONVERT_TZ(spotter_output.date,'+00:00', '-04:00')) = :date 
                    GROUP BY spotter_output.departure_airport_country
					ORDER BY airport_departure_country_count DESC";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':date' => $date));
      
		$airport_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['departure_airport_country'] = $row['departure_airport_country'];
			$temp_array['airport_departure_country_count'] = $row['airport_departure_country_count'];
          
			$airport_array[] = $temp_array;
		}

		return $airport_array;
	}
	
	
	
	/**
	* Gets all departure airports of the airplanes that have flown over based on a ident/callsign
	*
	* @return Array the airport list
	*
	*/
	public static function countAllDepartureAirportsByIdent($ident)
	{
		$ident = filter_var($ident,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.departure_airport_icao, COUNT(spotter_output.departure_airport_icao) AS airport_departure_icao_count, spotter_output.departure_airport_name, spotter_output.departure_airport_city, spotter_output.departure_airport_country 
								FROM spotter_output
                    WHERE spotter_output.departure_airport_name <> '' AND spotter_output.ident = :ident 
                    GROUP BY spotter_output.departure_airport_icao
					ORDER BY airport_departure_icao_count DESC";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':ident' => $ident));
      
		$airport_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airport_departure_icao'] = $row['departure_airport_icao'];
			$temp_array['airport_departure_icao_count'] = $row['airport_departure_icao_count'];
			$temp_array['airport_departure_name'] = $row['departure_airport_name'];
			$temp_array['airport_departure_city'] = $row['departure_airport_city'];
			$temp_array['airport_departure_country'] = $row['departure_airport_country'];
          
			$airport_array[] = $temp_array;
		}

		return $airport_array;
	}
	
	
	
	/**
	* Gets all departure airports by country of the airplanes that have flown over based on a callsign/ident
	*
	* @return Array the airport list
	*
	*/
	public static function countAllDepartureAirportCountriesByIdent($ident)
	{
		$ident = filter_var($ident,FILTER_SANITIZE_STRING);
					
		$query  = "SELECT DISTINCT spotter_output.departure_airport_country, COUNT(spotter_output.departure_airport_country) AS airport_departure_country_count 
								FROM spotter_output 
                    WHERE spotter_output.departure_airport_country <> '' AND spotter_output.ident = :ident 
                    GROUP BY spotter_output.departure_airport_country
					ORDER BY airport_departure_country_count DESC";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':ident' => $ident));
      
		$airport_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['departure_airport_country'] = $row['departure_airport_country'];
			$temp_array['airport_departure_country_count'] = $row['airport_departure_country_count'];
          
			$airport_array[] = $temp_array;
		}

		return $airport_array;
	}
	
	
	
	/**
	* Gets all departure airports of the airplanes that have flown over based on a country
	*
	* @return Array the airport list
	*
	*/
	public static function countAllDepartureAirportsByCountry($country)
	{
		$date = filter_var($date,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.departure_airport_icao, COUNT(spotter_output.departure_airport_icao) AS airport_departure_icao_count, spotter_output.departure_airport_name, spotter_output.departure_airport_city, spotter_output.departure_airport_country 
								FROM spotter_output
                    WHERE ((spotter_output.departure_airport_country = :country) OR (spotter_output.arrival_airport_country = :country)) OR spotter_output.airline_country = :country
                    GROUP BY spotter_output.departure_airport_icao
					ORDER BY airport_departure_icao_count DESC";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':country' => $country));
      
		$airport_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airport_departure_icao'] = $row['departure_airport_icao'];
			$temp_array['airport_departure_icao_count'] = $row['airport_departure_icao_count'];
			$temp_array['airport_departure_name'] = $row['departure_airport_name'];
			$temp_array['airport_departure_city'] = $row['departure_airport_city'];
			$temp_array['airport_departure_country'] = $row['departure_airport_country'];
          
			$airport_array[] = $temp_array;
		}

		return $airport_array;
	}


	/**
	* Gets all departure airports by country of the airplanes that have flown over based on an aircraft icao
	*
	* @return Array the airport list
	*
	*/
	public static function countAllDepartureAirportCountriesByCountry($country)
	{
		$country = filter_var($country,FILTER_SANITIZE_STRING);
					
		$query  = "SELECT DISTINCT spotter_output.departure_airport_country, COUNT(spotter_output.departure_airport_country) AS airport_departure_country_count 
								FROM spotter_output 
                    WHERE spotter_output.departure_airport_country <> '' AND ((spotter_output.departure_airport_country = :country) OR (spotter_output.arrival_airport_country = :country)) OR spotter_output.airline_country = :country 
                    GROUP BY spotter_output.departure_airport_country
					ORDER BY airport_departure_country_count DESC";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':country' => $country));
      
		$airport_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['departure_airport_country'] = $row['departure_airport_country'];
			$temp_array['airport_departure_country_count'] = $row['airport_departure_country_count'];
          
			$airport_array[] = $temp_array;
		}

		return $airport_array;
	}
	

	/**
	* Gets all arrival airports of the airplanes that have flown over
	*
	* @return Array the airport list
	*
	*/
	public static function countAllArrivalAirports()
	{
		$query  = "SELECT DISTINCT spotter_output.arrival_airport_icao, COUNT(spotter_output.arrival_airport_icao) AS airport_arrival_icao_count, spotter_output.arrival_airport_name, spotter_output.arrival_airport_city, spotter_output.arrival_airport_country 
								FROM spotter_output 
                    WHERE spotter_output.arrival_airport_name <> '' 
                    GROUP BY spotter_output.arrival_airport_icao
					ORDER BY airport_arrival_icao_count DESC
					LIMIT 0,10";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute();
      
		$airport_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airport_arrival_icao'] = $row['arrival_airport_icao'];
			$temp_array['airport_arrival_icao_count'] = $row['airport_arrival_icao_count'];
			$temp_array['airport_arrival_name'] = $row['arrival_airport_name'];
			$temp_array['airport_arrival_city'] = $row['arrival_airport_city'];
			$temp_array['airport_arrival_country'] = $row['arrival_airport_country'];
          
			$airport_array[] = $temp_array;
		}

		return $airport_array;
	}
	
	
	
	/**
	* Gets all arrival airports of the airplanes that have flown over based on an airline icao
	*
	* @return Array the airport list
	*
	*/
	public static function countAllArrivalAirportsByAirline($airline_icao)
	{
		$airline_icao = filter_var($airline_icao,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.arrival_airport_icao, COUNT(spotter_output.arrival_airport_icao) AS airport_arrival_icao_count, spotter_output.arrival_airport_name, spotter_output.arrival_airport_city, spotter_output.arrival_airport_country 
								FROM spotter_output 
                    WHERE spotter_output.arrival_airport_name <> '' AND spotter_output.airline_icao = :airline_icao 
                    GROUP BY spotter_output.arrival_airport_icao
					ORDER BY airport_arrival_icao_count DESC";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':airline_icao' => $airline_icao));
      
		$airport_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airport_arrival_icao'] = $row['arrival_airport_icao'];
			$temp_array['airport_arrival_icao_count'] = $row['airport_arrival_icao_count'];
			$temp_array['airport_arrival_name'] = $row['arrival_airport_name'];
			$temp_array['airport_arrival_city'] = $row['arrival_airport_city'];
			$temp_array['airport_arrival_country'] = $row['arrival_airport_country'];
          
			$airport_array[] = $temp_array;
		}

		return $airport_array;
	}
	
	
	/**
	* Gets all arrival airports by country of the airplanes that have flown over based on an airline icao
	*
	* @return Array the airport list
	*
	*/
	public static function countAllArrivalAirportCountriesByAirline($airline_icao)
	{
		$airline_icao = filter_var($airline_icao,FILTER_SANITIZE_STRING);
					
		$query  = "SELECT DISTINCT spotter_output.arrival_airport_country, COUNT(spotter_output.arrival_airport_country) AS airport_arrival_country_count 
								FROM spotter_output 
                    WHERE spotter_output.arrival_airport_country <> '' AND spotter_output.airline_icao = :airline_icao 
                    GROUP BY spotter_output.arrival_airport_country
					ORDER BY airport_arrival_country_count DESC";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':airline_icao' => $airline_icao));
      
		$airport_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['arrival_airport_country'] = $row['arrival_airport_country'];
			$temp_array['airport_arrival_country_count'] = $row['airport_arrival_country_count'];
          
			$airport_array[] = $temp_array;
		}

		return $airport_array;
	}
	
	
	/**
	* Gets all arrival airports of the airplanes that have flown over based on an aircraft icao
	*
	* @return Array the airport list
	*
	*/
	public static function countAllArrivalAirportsByAircraft($aircraft_icao)
	{
		$aircraft_icao = filter_var($aircraft_icao,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.arrival_airport_icao, COUNT(spotter_output.arrival_airport_icao) AS airport_arrival_icao_count, spotter_output.arrival_airport_name, spotter_output.arrival_airport_city, spotter_output.arrival_airport_country 
								FROM spotter_output 
                    WHERE spotter_output.arrival_airport_name <> '' AND spotter_output.aircraft_icao = :aircraft_icao 
                    GROUP BY spotter_output.arrival_airport_icao
					ORDER BY airport_arrival_icao_count DESC";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':aircraft_icao' => $aircraft_icao));
      
		$airport_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airport_arrival_icao'] = $row['arrival_airport_icao'];
			$temp_array['airport_arrival_icao_count'] = $row['airport_arrival_icao_count'];
			$temp_array['airport_arrival_name'] = $row['arrival_airport_name'];
			$temp_array['airport_arrival_city'] = $row['arrival_airport_city'];
			$temp_array['airport_arrival_country'] = $row['arrival_airport_country'];
          
			$airport_array[] = $temp_array;
		}

		return $airport_array;
	}
	
	
	
	/**
	* Gets all arrival airports by country of the airplanes that have flown over based on an aircraft icao
	*
	* @return Array the airport list
	*
	*/
	public static function countAllArrivalAirportCountriesByAircraft($aircraft_icao)
	{
		$aircraft_icao = filter_var($aircraft_icao,FILTER_SANITIZE_STRING);
					
		$query  = "SELECT DISTINCT spotter_output.arrival_airport_country, COUNT(spotter_output.arrival_airport_country) AS airport_arrival_country_count 
								FROM spotter_output 
                    WHERE spotter_output.arrival_airport_country <> '' AND spotter_output.aircraft_icao = :aircraft_icao
                    GROUP BY spotter_output.arrival_airport_country
					ORDER BY airport_arrival_country_count DESC";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':aircraft_icao' => $aircraft_icao));
      
		$airport_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['arrival_airport_country'] = $row['arrival_airport_country'];
			$temp_array['airport_arrival_country_count'] = $row['airport_arrival_country_count'];
          
			$airport_array[] = $temp_array;
		}

		return $airport_array;
	}
	
	
	/**
	* Gets all arrival airports of the airplanes that have flown over based on an aircraft registration
	*
	* @return Array the airport list
	*
	*/
	public static function countAllArrivalAirportsByRegistration($registration)
	{
		$registration = filter_var($registration,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.arrival_airport_icao, COUNT(spotter_output.arrival_airport_icao) AS airport_arrival_icao_count, spotter_output.arrival_airport_name, spotter_output.arrival_airport_city, spotter_output.arrival_airport_country 
								FROM spotter_output 
                    WHERE spotter_output.arrival_airport_name <> '' AND spotter_output.registration = :registration 
                    GROUP BY spotter_output.arrival_airport_icao
					ORDER BY airport_arrival_icao_count DESC";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':registration' => $registration));
      
		$airport_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airport_arrival_icao'] = $row['arrival_airport_icao'];
			$temp_array['airport_arrival_icao_count'] = $row['airport_arrival_icao_count'];
			$temp_array['airport_arrival_name'] = $row['arrival_airport_name'];
			$temp_array['airport_arrival_city'] = $row['arrival_airport_city'];
			$temp_array['airport_arrival_country'] = $row['arrival_airport_country'];
          
			$airport_array[] = $temp_array;
		}

		return $airport_array;
	}
	
	
	/**
	* Gets all arrival airports by country of the airplanes that have flown over based on an aircraft registration
	*
	* @return Array the airport list
	*
	*/
	public static function countAllArrivalAirportCountriesByRegistration($registration)
	{
		$aircraft_icao = filter_var($aircraft_icao,FILTER_SANITIZE_STRING);
					
		$query  = "SELECT DISTINCT spotter_output.arrival_airport_country, COUNT(spotter_output.arrival_airport_country) AS airport_arrival_country_count 
								FROM spotter_output 
                    WHERE spotter_output.arrival_airport_country <> '' AND spotter_output.registration = :registration 
                    GROUP BY spotter_output.arrival_airport_country
					ORDER BY airport_arrival_country_count DESC";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':registration' => $registration));
      
		$airport_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['arrival_airport_country'] = $row['arrival_airport_country'];
			$temp_array['airport_arrival_country_count'] = $row['airport_arrival_country_count'];
          
			$airport_array[] = $temp_array;
		}

		return $airport_array;
	}
	
	
	
	/**
	* Gets all arrival airports of the airplanes that have flown over based on an departure airport
	*
	* @return Array the airport list
	*
	*/
	public static function countAllArrivalAirportsByAirport($airport_icao)
	{
		$airport_icao = filter_var($airport_icao,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.arrival_airport_icao, COUNT(spotter_output.arrival_airport_icao) AS airport_arrival_icao_count, spotter_output.arrival_airport_name, spotter_output.arrival_airport_city, spotter_output.arrival_airport_country 
								FROM spotter_output 
                    WHERE spotter_output.arrival_airport_name <> '' AND spotter_output.departure_airport_icao = :airport_icao 
                    GROUP BY spotter_output.arrival_airport_icao
					ORDER BY airport_arrival_icao_count DESC";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':airport_icao' => $airport_icao));
      
		$airport_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airport_arrival_icao'] = $row['arrival_airport_icao'];
			$temp_array['airport_arrival_icao_count'] = $row['airport_arrival_icao_count'];
			$temp_array['airport_arrival_name'] = $row['arrival_airport_name'];
			$temp_array['airport_arrival_city'] = $row['arrival_airport_city'];
			$temp_array['airport_arrival_country'] = $row['arrival_airport_country'];
          
			$airport_array[] = $temp_array;
		}

		return $airport_array;
	}
	
	
	/**
	* Gets all arrival airports by country of the airplanes that have flown over based on an airport icao
	*
	* @return Array the airport list
	*
	*/
	public static function countAllArrivalAirportCountriesByAirport($airport_icao)
	{
		$airport_icao = filter_var($airport_icao,FILTER_SANITIZE_STRING);
					
		$query  = "SELECT DISTINCT spotter_output.arrival_airport_country, COUNT(spotter_output.arrival_airport_country) AS airport_arrival_country_count 
								FROM spotter_output 
                    WHERE spotter_output.arrival_airport_country <> '' AND spotter_output.departure_airport_icao = :airport_icao 
                    GROUP BY spotter_output.arrival_airport_country
					ORDER BY airport_arrival_country_count DESC";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':airport_icao' => $airport_icao));
      
		$airport_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['arrival_airport_country'] = $row['arrival_airport_country'];
			$temp_array['airport_arrival_country_count'] = $row['airport_arrival_country_count'];
          
			$airport_array[] = $temp_array;
		}

		return $airport_array;
	}
	
	
	/**
	* Gets all arrival airports of the airplanes that have flown over based on a aircraft manufacturer
	*
	* @return Array the airport list
	*
	*/
	public static function countAllArrivalAirportsByManufacturer($aircraft_manufacturer)
	{
		$aircraft_manufacturer = filter_var($aircraft_manufacturer,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.arrival_airport_icao, COUNT(spotter_output.arrival_airport_icao) AS airport_arrival_icao_count, spotter_output.arrival_airport_name, spotter_output.arrival_airport_city, spotter_output.arrival_airport_country 
								FROM spotter_output 
                    WHERE spotter_output.arrival_airport_name <> '' AND spotter_output.aircraft_manufacturer = :aircraft_manufacturer 
                    GROUP BY spotter_output.arrival_airport_icao
					ORDER BY airport_arrival_icao_count DESC";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':aircraft_manufacturer' => $aircraft_manufacturer));
      
		$airport_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airport_arrival_icao'] = $row['arrival_airport_icao'];
			$temp_array['airport_arrival_icao_count'] = $row['airport_arrival_icao_count'];
			$temp_array['airport_arrival_name'] = $row['arrival_airport_name'];
			$temp_array['airport_arrival_city'] = $row['arrival_airport_city'];
			$temp_array['airport_arrival_country'] = $row['arrival_airport_country'];
          
			$airport_array[] = $temp_array;
		}

		return $airport_array;
	}
	
	
	
	/**
	* Gets all arrival airports by country of the airplanes that have flown over based on a aircraft manufacturer
	*
	* @return Array the airport list
	*
	*/
	public static function countAllArrivalAirportCountriesByManufacturer($aircraft_manufacturer)
	{
		$aircraft_manufacturer = filter_var($aircraft_manufacturer,FILTER_SANITIZE_STRING);
					
		$query  = "SELECT DISTINCT spotter_output.arrival_airport_country, COUNT(spotter_output.arrival_airport_country) AS airport_arrival_country_count 
								FROM spotter_output 
                    WHERE spotter_output.arrival_airport_country <> '' AND spotter_output.aircraft_manufacturer = :aircraft_manufacturer 
                    GROUP BY spotter_output.arrival_airport_country
					ORDER BY airport_arrival_country_count DESC";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':aircraft_manufacturer' => $aircraft_manufacturer));
      
		$airport_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['arrival_airport_country'] = $row['arrival_airport_country'];
			$temp_array['airport_arrival_country_count'] = $row['airport_arrival_country_count'];
          
			$airport_array[] = $temp_array;
		}

		return $airport_array;
	}
	
	
	
	/**
	* Gets all arrival airports of the airplanes that have flown over based on a date
	*
	* @return Array the airport list
	*
	*/
	public static function countAllArrivalAirportsByDate($date)
	{
		$date = filter_var($date,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.arrival_airport_icao, COUNT(spotter_output.arrival_airport_icao) AS airport_arrival_icao_count, spotter_output.arrival_airport_name, spotter_output.arrival_airport_city, spotter_output.arrival_airport_country 
								FROM spotter_output 
                    WHERE spotter_output.arrival_airport_name <> '' AND DATE(CONVERT_TZ(spotter_output.date,'+00:00', '-04:00')) = :date  
                    GROUP BY spotter_output.arrival_airport_icao
					ORDER BY airport_arrival_icao_count DESC";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':date' => $date));
      
		$airport_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airport_arrival_icao'] = $row['arrival_airport_icao'];
			$temp_array['airport_arrival_icao_count'] = $row['airport_arrival_icao_count'];
			$temp_array['airport_arrival_name'] = $row['arrival_airport_name'];
			$temp_array['airport_arrival_city'] = $row['arrival_airport_city'];
			$temp_array['airport_arrival_country'] = $row['arrival_airport_country'];
          
			$airport_array[] = $temp_array;
		}

		return $airport_array;
	}
	
	
	
	/**
	* Gets all arrival airports by country of the airplanes that have flown over based on a date
	*
	* @return Array the airport list
	*
	*/
	public static function countAllArrivalAirportCountriesByDate($date)
	{
		$date = filter_var($date,FILTER_SANITIZE_STRING);
					
		$query  = "SELECT DISTINCT spotter_output.arrival_airport_country, COUNT(spotter_output.arrival_airport_country) AS airport_arrival_country_count 
								FROM spotter_output 
                    WHERE spotter_output.arrival_airport_country <> '' AND DATE(CONVERT_TZ(spotter_output.date,'+00:00', '-04:00')) = :date 
                    GROUP BY spotter_output.arrival_airport_country
					ORDER BY airport_arrival_country_count DESC";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':date' => $date));
      
		$airport_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['arrival_airport_country'] = $row['arrival_airport_country'];
			$temp_array['airport_arrival_country_count'] = $row['airport_arrival_country_count'];
          
			$airport_array[] = $temp_array;
		}

		return $airport_array;
	}
	
	
	
	/**
	* Gets all arrival airports of the airplanes that have flown over based on a ident/callsign
	*
	* @return Array the airport list
	*
	*/
	public static function countAllArrivalAirportsByIdent($ident)
	{
		$ident = filter_var($ident,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.arrival_airport_icao, COUNT(spotter_output.arrival_airport_icao) AS airport_arrival_icao_count, spotter_output.arrival_airport_name, spotter_output.arrival_airport_city, spotter_output.arrival_airport_country 
								FROM spotter_output 
                    WHERE spotter_output.arrival_airport_name <> '' AND spotter_output.ident = :ident  
                    GROUP BY spotter_output.arrival_airport_icao
					ORDER BY airport_arrival_icao_count DESC";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':ident' => $ident));
      
		$airport_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airport_arrival_icao'] = $row['arrival_airport_icao'];
			$temp_array['airport_arrival_icao_count'] = $row['airport_arrival_icao_count'];
			$temp_array['airport_arrival_name'] = $row['arrival_airport_name'];
			$temp_array['airport_arrival_city'] = $row['arrival_airport_city'];
			$temp_array['airport_arrival_country'] = $row['arrival_airport_country'];
          
			$airport_array[] = $temp_array;
		}

		return $airport_array;
	}
	
	
	/**
	* Gets all arrival airports by country of the airplanes that have flown over based on a callsign/ident
	*
	* @return Array the airport list
	*
	*/
	public static function countAllArrivalAirportCountriesByIdent($ident)
	{
		$ident = filter_var($ident,FILTER_SANITIZE_STRING);
					
		$query  = "SELECT DISTINCT spotter_output.arrival_airport_country, COUNT(spotter_output.arrival_airport_country) AS airport_arrival_country_count 
								FROM spotter_output 
                    WHERE spotter_output.arrival_airport_country <> '' AND spotter_output.ident = :ident 
                    GROUP BY spotter_output.arrival_airport_country
					ORDER BY airport_arrival_country_count DESC";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':ident' => $ident));
      
		$airport_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['arrival_airport_country'] = $row['arrival_airport_country'];
			$temp_array['airport_arrival_country_count'] = $row['airport_arrival_country_count'];
          
			$airport_array[] = $temp_array;
		}

		return $airport_array;
	}
	
	
	
	/**
	* Gets all arrival airports of the airplanes that have flown over based on a country
	*
	* @return Array the airport list
	*
	*/
	public static function countAllArrivalAirportsByCountry($country)
	{
		$country = filter_var($country,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.arrival_airport_icao, COUNT(spotter_output.arrival_airport_icao) AS airport_arrival_icao_count, spotter_output.arrival_airport_name, spotter_output.arrival_airport_city, spotter_output.arrival_airport_country 
								FROM spotter_output 
                    WHERE ((spotter_output.departure_airport_country = :country) OR (spotter_output.arrival_airport_country = :country)) OR spotter_output.airline_country = :country  
                    GROUP BY spotter_output.arrival_airport_icao
					ORDER BY airport_arrival_icao_count DESC";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':country' => $country));
      
		$airport_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airport_arrival_icao'] = $row['arrival_airport_icao'];
			$temp_array['airport_arrival_icao_count'] = $row['airport_arrival_icao_count'];
			$temp_array['airport_arrival_name'] = $row['arrival_airport_name'];
			$temp_array['airport_arrival_city'] = $row['arrival_airport_city'];
			$temp_array['airport_arrival_country'] = $row['arrival_airport_country'];
          
			$airport_array[] = $temp_array;
		}

		return $airport_array;
	}
	
	
	/**
	* Gets all arrival airports by country of the airplanes that have flown over based on a country
	*
	* @return Array the airport list
	*
	*/
	public static function countAllArrivalAirportCountriesByCountry($country)
	{
		$country = filter_var($country,FILTER_SANITIZE_STRING);
					
		$query  = "SELECT DISTINCT spotter_output.arrival_airport_country, COUNT(spotter_output.arrival_airport_country) AS airport_arrival_country_count 
								FROM spotter_output 
                    WHERE spotter_output.arrival_airport_country <> '' AND ((spotter_output.departure_airport_country = :country) OR (spotter_output.arrival_airport_country = :country)) OR spotter_output.airline_country = :country 
                    GROUP BY spotter_output.arrival_airport_country
					ORDER BY airport_arrival_country_count DESC";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':country' => $country));
      
		$airport_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['arrival_airport_country'] = $row['arrival_airport_country'];
			$temp_array['airport_arrival_country_count'] = $row['airport_arrival_country_count'];
          
			$airport_array[] = $temp_array;
		}

		return $airport_array;
	}



	/**
	* Counts all airport departure countries
	*
	* @return Array the airport departure list
	*
	*/
	public static function countAllDepartureCountries()
	{
		$query  = "SELECT DISTINCT spotter_output.departure_airport_country, COUNT(spotter_output.departure_airport_country) AS airport_departure_country_count 
								FROM spotter_output 
                    WHERE spotter_output.departure_airport_country <> '' 
                    GROUP BY spotter_output.departure_airport_country
					ORDER BY airport_departure_country_count DESC
					LIMIT 0,10";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute();
      
		$airport_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airport_departure_country_count'] = $row['airport_departure_country_count'];
			$temp_array['airport_departure_country'] = $row['departure_airport_country'];
          
			$airport_array[] = $temp_array;
		}

		return $airport_array;
	}
	
	
	/**
	* Counts all airport arrival countries
	*
	* @return Array the airport arrival list
	*
	*/
	public static function countAllArrivalCountries()
	{
		$query  = "SELECT DISTINCT spotter_output.arrival_airport_country, COUNT(spotter_output.arrival_airport_country) AS airport_arrival_country_count 
								FROM spotter_output 
                    WHERE spotter_output.arrival_airport_country <> '' 
                    GROUP BY spotter_output.arrival_airport_country
					ORDER BY airport_arrival_country_count DESC
					LIMIT 0,10";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute();
      
		$airport_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airport_arrival_country_count'] = $row['airport_arrival_country_count'];
			$temp_array['airport_arrival_country'] = $row['arrival_airport_country'];
          
			$airport_array[] = $temp_array;
		}

		return $airport_array;
	}





	/**
	* Gets all route combinations
	*
	* @return Array the route list
	*
	*/
	public static function countAllRoutes()
	{
		
		$query  = "SELECT DISTINCT concat(spotter_output.departure_airport_icao, ' - ',  spotter_output.arrival_airport_icao) AS route, count(concat(spotter_output.departure_airport_icao, ' - ', spotter_output.arrival_airport_icao)) AS route_count, spotter_output.departure_airport_icao, spotter_output.departure_airport_name AS airport_departure_name, spotter_output.departure_airport_city AS airport_departure_city, spotter_output.departure_airport_country AS airport_departure_country, spotter_output.arrival_airport_icao, spotter_output.arrival_airport_name AS airport_arrival_name, spotter_output.arrival_airport_city AS airport_arrival_city, spotter_output.arrival_airport_country AS airport_arrival_country
								FROM spotter_output
                    WHERE spotter_output.ident <> '' 
                    GROUP BY route
                    ORDER BY route_count DESC
					LIMIT 0,10";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute();
      
		$routes_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['route_count'] = $row['route_count'];
			$temp_array['airport_departure_icao'] = $row['departure_airport_icao'];
			$temp_array['airport_departure_name'] = $row['airport_departure_name'];
			$temp_array['airport_departure_city'] = $row['airport_departure_city'];
			$temp_array['airport_departure_country'] = $row['airport_departure_country'];
			$temp_array['airport_arrival_icao'] = $row['arrival_airport_icao'];
			$temp_array['airport_arrival_name'] = $row['airport_arrival_name'];
			$temp_array['airport_arrival_city'] = $row['airport_arrival_city'];
			$temp_array['airport_arrival_country'] = $row['airport_arrival_country'];
          
			$routes_array[] = $temp_array;
		}

		return $routes_array;
	}
	
	
	
	
	/**
	* Gets all route combinations based on an aircraft
	*
	* @return Array the route list
	*
	*/
	public static function countAllRoutesByAircraft($aircraft_icao)
	{
		$aircraft_icao = filter_var($aircraft_icao,FILTER_SANITIZE_STRING);
		
		$query  = "SELECT DISTINCT concat(spotter_output.departure_airport_icao, ' - ',  spotter_output.arrival_airport_icao) AS route, count(concat(spotter_output.departure_airport_icao, ' - ', spotter_output.arrival_airport_icao)) AS route_count, spotter_output.departure_airport_icao, spotter_output.departure_airport_name AS airport_departure_name, spotter_output.departure_airport_city AS airport_departure_city, spotter_output.departure_airport_country AS airport_departure_country, spotter_output.arrival_airport_icao, spotter_output.arrival_airport_name AS airport_arrival_name, spotter_output.arrival_airport_city AS airport_arrival_city, spotter_output.arrival_airport_country AS airport_arrival_country
								FROM spotter_output
                    WHERE spotter_output.ident <> '' AND spotter_output.aircraft_icao = :aircraft_icao 
                    GROUP BY route
                    ORDER BY route_count DESC";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':aircraft_icao' => $aircraft_icao));
      
		$routes_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['route_count'] = $row['route_count'];
			$temp_array['airport_departure_icao'] = $row['departure_airport_icao'];
			$temp_array['airport_departure_name'] = $row['airport_departure_name'];
			$temp_array['airport_departure_city'] = $row['airport_departure_city'];
			$temp_array['airport_departure_country'] = $row['airport_departure_country'];
			$temp_array['airport_arrival_icao'] = $row['arrival_airport_icao'];
			$temp_array['airport_arrival_name'] = $row['airport_arrival_name'];
			$temp_array['airport_arrival_city'] = $row['airport_arrival_city'];
			$temp_array['airport_arrival_country'] = $row['airport_arrival_country'];
          
			$routes_array[] = $temp_array;
		}

		return $routes_array;
	}
	
	
	/**
	* Gets all route combinations based on an aircraft registration
	*
	* @return Array the route list
	*
	*/
	public static function countAllRoutesByRegistration($registration)
	{
		$registration = filter_var($registration, FILTER_SANITIZE_STRING);
		
		$query  = "SELECT DISTINCT concat(spotter_output.departure_airport_icao, ' - ',  spotter_output.arrival_airport_icao) AS route, count(concat(spotter_output.departure_airport_icao, ' - ', spotter_output.arrival_airport_icao)) AS route_count, spotter_output.departure_airport_icao, spotter_output.departure_airport_name AS airport_departure_name, spotter_output.departure_airport_city AS airport_departure_city, spotter_output.departure_airport_country AS airport_departure_country, spotter_output.arrival_airport_icao, spotter_output.arrival_airport_name AS airport_arrival_name, spotter_output.arrival_airport_city AS airport_arrival_city, spotter_output.arrival_airport_country AS airport_arrival_country
								FROM spotter_output
                    WHERE spotter_output.ident <> '' AND spotter_output.registration = :registration 
                    GROUP BY route
                    ORDER BY route_count DESC";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':registration' => $registration));
      
		$routes_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['route_count'] = $row['route_count'];
			$temp_array['airport_departure_icao'] = $row['departure_airport_icao'];
			$temp_array['airport_departure_name'] = $row['airport_departure_name'];
			$temp_array['airport_departure_city'] = $row['airport_departure_city'];
			$temp_array['airport_departure_country'] = $row['airport_departure_country'];
			$temp_array['airport_arrival_icao'] = $row['arrival_airport_icao'];
			$temp_array['airport_arrival_name'] = $row['airport_arrival_name'];
			$temp_array['airport_arrival_city'] = $row['airport_arrival_city'];
			$temp_array['airport_arrival_country'] = $row['airport_arrival_country'];
          
			$routes_array[] = $temp_array;
		}

		return $routes_array;
	}
	
	
	
	/**
	* Gets all route combinations based on an airline
	*
	* @return Array the route list
	*
	*/
	public static function countAllRoutesByAirline($airline_icao)
	{
		$airline_icao = filter_var($airline_icao,FILTER_SANITIZE_STRING);
		
		$query  = "SELECT DISTINCT concat(spotter_output.departure_airport_icao, ' - ',  spotter_output.arrival_airport_icao) AS route, count(concat(spotter_output.departure_airport_icao, ' - ', spotter_output.arrival_airport_icao)) AS route_count, spotter_output.departure_airport_icao, spotter_output.departure_airport_name AS airport_departure_name, spotter_output.departure_airport_city AS airport_departure_city, spotter_output.departure_airport_country AS airport_departure_country, spotter_output.arrival_airport_icao, spotter_output.arrival_airport_name AS airport_arrival_name, spotter_output.arrival_airport_city AS airport_arrival_city, spotter_output.arrival_airport_country AS airport_arrival_country
								FROM spotter_output
                    WHERE spotter_output.ident <> '' AND spotter_output.airline_icao = :airline_icao 
                    GROUP BY route
                    ORDER BY route_count DESC";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':airline_icao' => $airline_icao));
      
		$routes_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['route_count'] = $row['route_count'];
			$temp_array['airport_departure_icao'] = $row['departure_airport_icao'];
			$temp_array['airport_departure_name'] = $row['airport_departure_name'];
			$temp_array['airport_departure_city'] = $row['airport_departure_city'];
			$temp_array['airport_departure_country'] = $row['airport_departure_country'];
			$temp_array['airport_arrival_icao'] = $row['arrival_airport_icao'];
			$temp_array['airport_arrival_name'] = $row['airport_arrival_name'];
			$temp_array['airport_arrival_city'] = $row['airport_arrival_city'];
			$temp_array['airport_arrival_country'] = $row['airport_arrival_country'];
          
			$routes_array[] = $temp_array;
		}

		return $routes_array;
	}
	
	
	
	/**
	* Gets all route combinations based on an airport
	*
	* @return Array the route list
	*
	*/
	public static function countAllRoutesByAirport($airport_icao)
	{
		$airport_icao = filter_var($airport_icao,FILTER_SANITIZE_STRING);
		
		$query  = "SELECT DISTINCT concat(spotter_output.departure_airport_icao, ' - ',  spotter_output.arrival_airport_icao) AS route, count(concat(spotter_output.departure_airport_icao, ' - ', spotter_output.arrival_airport_icao)) AS route_count, spotter_output.departure_airport_icao, spotter_output.departure_airport_name AS airport_departure_name, spotter_output.departure_airport_city AS airport_departure_city, spotter_output.departure_airport_country AS airport_departure_country, spotter_output.arrival_airport_icao, spotter_output.arrival_airport_name AS airport_arrival_name, spotter_output.arrival_airport_city AS airport_arrival_city, spotter_output.arrival_airport_country AS airport_arrival_country
								FROM spotter_output
                    WHERE spotter_output.ident <> '' AND (spotter_output.departure_airport_icao = :airport_icao OR spotter_output.arrival_airport_icao = :airport_icao)
                    GROUP BY route
                    ORDER BY route_count DESC";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':airport_icao' => $airport_icao));
      
		$routes_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['route_count'] = $row['route_count'];
			$temp_array['airport_departure_icao'] = $row['departure_airport_icao'];
			$temp_array['airport_departure_name'] = $row['airport_departure_name'];
			$temp_array['airport_departure_city'] = $row['airport_departure_city'];
			$temp_array['airport_departure_country'] = $row['airport_departure_country'];
			$temp_array['airport_arrival_icao'] = $row['arrival_airport_icao'];
			$temp_array['airport_arrival_name'] = $row['airport_arrival_name'];
			$temp_array['airport_arrival_city'] = $row['airport_arrival_city'];
			$temp_array['airport_arrival_country'] = $row['airport_arrival_country'];
          
			$routes_array[] = $temp_array;
		}

		return $routes_array;
	}
	
	
	
	/**
	* Gets all route combinations based on an country
	*
	* @return Array the route list
	*
	*/
	public static function countAllRoutesByCountry($country)
	{
		$country = filter_var($country,FILTER_SANITIZE_STRING);
		
		$query  = "SELECT DISTINCT concat(spotter_output.departure_airport_icao, ' - ',  spotter_output.arrival_airport_icao) AS route, count(concat(spotter_output.departure_airport_icao, ' - ', spotter_output.arrival_airport_icao)) AS route_count, spotter_output.departure_airport_icao, spotter_output.departure_airport_name AS airport_departure_name, spotter_output.departure_airport_city AS airport_departure_city, spotter_output.departure_airport_country AS airport_departure_country, spotter_output.arrival_airport_icao, spotter_output.arrival_airport_name AS airport_arrival_name, spotter_output.arrival_airport_city AS airport_arrival_city, spotter_output.arrival_airport_country AS airport_arrival_country
								FROM spotter_output
                    WHERE spotter_output.ident <> '' AND ((spotter_output.departure_airport_country = :country) OR (spotter_output.arrival_airport_country = :country)) OR spotter_output.airline_country = :country 
                    GROUP BY route
                    ORDER BY route_count DESC";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':country' => $country));
      
		$routes_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['route_count'] = $row['route_count'];
			$temp_array['airport_departure_icao'] = $row['departure_airport_icao'];
			$temp_array['airport_departure_name'] = $row['airport_departure_name'];
			$temp_array['airport_departure_city'] = $row['airport_departure_city'];
			$temp_array['airport_departure_country'] = $row['airport_departure_country'];
			$temp_array['airport_arrival_icao'] = $row['arrival_airport_icao'];
			$temp_array['airport_arrival_name'] = $row['airport_arrival_name'];
			$temp_array['airport_arrival_city'] = $row['airport_arrival_city'];
			$temp_array['airport_arrival_country'] = $row['airport_arrival_country'];
          
			$routes_array[] = $temp_array;
		}

		return $routes_array;
	}


	/**
	* Gets all route combinations based on an date
	*
	* @return Array the route list
	*
	*/
	public static function countAllRoutesByDate($date)
	{
		$date = filter_var($date,FILTER_SANITIZE_STRING);
		
		$query  = "SELECT DISTINCT concat(spotter_output.departure_airport_icao, ' - ',  spotter_output.arrival_airport_icao) AS route, count(concat(spotter_output.departure_airport_icao, ' - ', spotter_output.arrival_airport_icao)) AS route_count, spotter_output.departure_airport_icao, spotter_output.departure_airport_name AS airport_departure_name, spotter_output.departure_airport_city AS airport_departure_city, spotter_output.departure_airport_country AS airport_departure_country, spotter_output.arrival_airport_icao, spotter_output.arrival_airport_name AS airport_arrival_name, spotter_output.arrival_airport_city AS airport_arrival_city, spotter_output.arrival_airport_country AS airport_arrival_country
								FROM spotter_output
                    WHERE spotter_output.ident <> '' AND DATE(CONVERT_TZ(spotter_output.date,'+00:00', '-04:00')) = :date  
                    GROUP BY route
                    ORDER BY route_count DESC";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':date' => $date));
      
		$routes_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['route_count'] = $row['route_count'];
			$temp_array['airport_departure_icao'] = $row['departure_airport_icao'];
			$temp_array['airport_departure_name'] = $row['airport_departure_name'];
			$temp_array['airport_departure_city'] = $row['airport_departure_city'];
			$temp_array['airport_departure_country'] = $row['airport_departure_country'];
			$temp_array['airport_arrival_icao'] = $row['arrival_airport_icao'];
			$temp_array['airport_arrival_name'] = $row['airport_arrival_name'];
			$temp_array['airport_arrival_city'] = $row['airport_arrival_city'];
			$temp_array['airport_arrival_country'] = $row['airport_arrival_country'];
          
			$routes_array[] = $temp_array;
		}

		return $routes_array;
	}
	
	
	/**
	* Gets all route combinations based on an ident/callsign
	*
	* @return Array the route list
	*
	*/
	public static function countAllRoutesByIdent($ident)
	{
		$ident = filter_var($ident,FILTER_SANITIZE_STRING);
		
		$query  = "SELECT DISTINCT concat(spotter_output.departure_airport_icao, ' - ',  spotter_output.arrival_airport_icao) AS route, count(concat(spotter_output.departure_airport_icao, ' - ', spotter_output.arrival_airport_icao)) AS route_count, spotter_output.departure_airport_icao, spotter_output.departure_airport_name AS airport_departure_name, spotter_output.departure_airport_city AS airport_departure_city, spotter_output.departure_airport_country AS airport_departure_country, spotter_output.arrival_airport_icao, spotter_output.arrival_airport_name AS airport_arrival_name, spotter_output.arrival_airport_city AS airport_arrival_city, spotter_output.arrival_airport_country AS airport_arrival_country
								FROM spotter_output
                    WHERE spotter_output.ident <> '' AND spotter_output.ident = :ident   
                    GROUP BY route
                    ORDER BY route_count DESC";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':ident' => $ident));
      
		$routes_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['route_count'] = $row['route_count'];
			$temp_array['airport_departure_icao'] = $row['departure_airport_icao'];
			$temp_array['airport_departure_name'] = $row['airport_departure_name'];
			$temp_array['airport_departure_city'] = $row['airport_departure_city'];
			$temp_array['airport_departure_country'] = $row['airport_departure_country'];
			$temp_array['airport_arrival_icao'] = $row['arrival_airport_icao'];
			$temp_array['airport_arrival_name'] = $row['airport_arrival_name'];
			$temp_array['airport_arrival_city'] = $row['airport_arrival_city'];
			$temp_array['airport_arrival_country'] = $row['airport_arrival_country'];
          
			$routes_array[] = $temp_array;
		}

		return $routes_array;
	}
	
	
	/**
	* Gets all route combinations based on an manufacturer
	*
	* @return Array the route list
	*
	*/
	public static function countAllRoutesByManufacturer($aircraft_manufacturer)
	{
		$aircraft_manufacturer = filter_var($aircraft_manufactuer,FILTER_SANITIZE_STRING);
		
		$query  = "SELECT DISTINCT concat(spotter_output.departure_airport_icao, ' - ',  spotter_output.arrival_airport_icao) AS route, count(concat(spotter_output.departure_airport_icao, ' - ', spotter_output.arrival_airport_icao)) AS route_count, spotter_output.departure_airport_icao, spotter_output.departure_airport_name AS airport_departure_name, spotter_output.departure_airport_city AS airport_departure_city, spotter_output.departure_airport_country AS airport_departure_country, spotter_output.arrival_airport_icao, spotter_output.arrival_airport_name AS airport_arrival_name, spotter_output.arrival_airport_city AS airport_arrival_city, spotter_output.arrival_airport_country AS airport_arrival_country
								FROM spotter_output
                    WHERE spotter_output.ident <> '' AND spotter_output.aircraft_manufacturer = :aircraft_manufacturer   
                    GROUP BY route
                    ORDER BY route_count DESC";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':aircraft_manufacturer' => $aircraft_manufacturer));
      
		$routes_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['route_count'] = $row['route_count'];
			$temp_array['airport_departure_icao'] = $row['departure_airport_icao'];
			$temp_array['airport_departure_name'] = $row['airport_departure_name'];
			$temp_array['airport_departure_city'] = $row['airport_departure_city'];
			$temp_array['airport_departure_country'] = $row['airport_departure_country'];
			$temp_array['airport_arrival_icao'] = $row['arrival_airport_icao'];
			$temp_array['airport_arrival_name'] = $row['airport_arrival_name'];
			$temp_array['airport_arrival_city'] = $row['airport_arrival_city'];
			$temp_array['airport_arrival_country'] = $row['airport_arrival_country'];
          
			$routes_array[] = $temp_array;
		}

		return $routes_array;
	}

	
	
	/**
	* Gets all route combinations with waypoints
	*
	* @return Array the route list
	*
	*/
	public static function countAllRoutesWithWaypoints()
	{
		$query  = "SELECT DISTINCT spotter_output.waypoints AS route, count(spotter_output.waypoints) AS route_count, spotter_output.spotter_id, spotter_output.departure_airport_icao, spotter_output.departure_airport_name AS airport_departure_name, spotter_output.departure_airport_city AS airport_departure_city, spotter_output.departure_airport_country AS airport_departure_country, spotter_output.arrival_airport_icao, spotter_output.arrival_airport_name AS airport_arrival_name, spotter_output.arrival_airport_city AS airport_arrival_city, spotter_output.arrival_airport_country AS airport_arrival_country
								FROM spotter_output
                    WHERE spotter_output.ident <> '' AND spotter_output.waypoints <> '' 
                    GROUP BY route
                    ORDER BY route_count DESC
					LIMIT 0,10";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute();
      
		$routes_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['spotter_id'] = $row['spotter_id'];
			$temp_array['route_count'] = $row['route_count'];
			$temp_array['airport_departure_icao'] = $row['departure_airport_icao'];
			$temp_array['airport_departure_name'] = $row['airport_departure_name'];
			$temp_array['airport_departure_city'] = $row['airport_departure_city'];
			$temp_array['airport_departure_country'] = $row['airport_departure_country'];
			$temp_array['airport_arrival_icao'] = $row['arrival_airport_icao'];
			$temp_array['airport_arrival_name'] = $row['airport_arrival_name'];
			$temp_array['airport_arrival_city'] = $row['airport_arrival_city'];
			$temp_array['airport_arrival_country'] = $row['airport_arrival_country'];
          
			$routes_array[] = $temp_array;
		}

		return $routes_array;
	}
	
	
	
	
	/**
	* Gets all callsigns that have flown over
	*
	* @return Array the callsign list
	*
	*/
	public static function countAllCallsigns()
	{
		$query  = "SELECT DISTINCT spotter_output.ident, COUNT(spotter_output.ident) AS callsign_icao_count, spotter_output.airline_name, spotter_output.airline_icao  
                    FROM spotter_output
                    WHERE spotter_output.airline_name <> '' 
                    GROUP BY spotter_output.ident
					ORDER BY callsign_icao_count DESC
					LIMIT 0,10";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute();
      
		$callsign_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['callsign_icao'] = $row['ident'];
			$temp_array['airline_name'] = $row['airline_name'];
			$temp_array['airline_icao'] = $row['airline_icao'];
			$temp_array['callsign_icao_count'] = $row['callsign_icao_count'];
          
			$callsign_array[] = $temp_array;
		}

		return $callsign_array;
	}




	/**
	* Counts all dates
	*
	* @return Array the date list
	*
	*/
	public static function countAllDates()
	{
		$query  = "SELECT DATE(CONVERT_TZ(spotter_output.date,'+00:00', '-04:00')) AS date_name, count(*) as date_count
								FROM spotter_output 
								GROUP BY date_name 
								ORDER BY date_count DESC
								LIMIT 0,10";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute();
      
		$date_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['date_name'] = $row['date_name'];
			$temp_array['date_count'] = $row['date_count'];

			$date_array[] = $temp_array;
		}

		return $date_array;
	}
	
	
	
	/**
	* Counts all dates during the last 7 days
	*
	* @return Array the date list
	*
	*/
	public static function countAllDatesLast7Days()
	{
		$query  = "SELECT DATE(CONVERT_TZ(spotter_output.date,'+00:00', '-04:00')) AS date_name, count(*) as date_count
								FROM spotter_output 
								WHERE spotter_output.date >= DATE_SUB(UTC_TIMESTAMP(),INTERVAL 7 DAY)
								GROUP BY date_name 
								ORDER BY spotter_output.date ASC";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute();
      
		$date_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['date_name'] = $row['date_name'];
			$temp_array['date_count'] = $row['date_count'];
          
			$date_array[] = $temp_array;
		}

		return $date_array;
	}
	
	
	
	/**
	* Counts all hours
	*
	* @return Array the hour list
	*
	*/
	public static function countAllHours($orderby)
	{
		if ($orderby == "hour")
		{
			$orderby_sql = "ORDER BY hour_name ASC";
		}
		if ($orderby == "count")
		{
			$orderby_sql = "ORDER BY hour_count DESC";
		}

		$query  = "SELECT HOUR(CONVERT_TZ(spotter_output.date,'+00:00', '-04:00')) AS hour_name, count(*) as hour_count
								FROM spotter_output 
								GROUP BY hour_name 
								".$orderby_sql."
								LIMIT 0,100";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute();
      
		$hour_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['hour_name'] = $row['hour_name'];
			$temp_array['hour_count'] = $row['hour_count'];
          
			$hour_array[] = $temp_array;
		}

		return $hour_array;
	}
	
	
	/**
	* Counts all hours by airline
	*
	* @return Array the hour list
	*
	*/
	public static function countAllHoursByAirline($airline_icao)
	{
		$airline_icao = filter_var($airline_icao,FILTER_SANITIZE_STRING);

		$query  = "SELECT HOUR(CONVERT_TZ(spotter_output.date,'+00:00', '-04:00')) AS hour_name, count(*) as hour_count
								FROM spotter_output 
								WHERE spotter_output.airline_icao = :airline_icao
								GROUP BY hour_name 
								ORDER BY hour_name ASC";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':airline_icao' => $airline_icao));
      
		$hour_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['hour_name'] = $row['hour_name'];
			$temp_array['hour_count'] = $row['hour_count'];
          
			$hour_array[] = $temp_array;
		}

		return $hour_array;
	}
	
	
	
	
	/**
	* Counts all hours by aircraft
	*
	* @return Array the hour list
	*
	*/
	public static function countAllHoursByAircraft($aircraft_icao)
	{
		$aircraft_icao = filter_var($aircraft_icao,FILTER_SANITIZE_STRING);

		$query  = "SELECT HOUR(CONVERT_TZ(spotter_output.date,'+00:00', '-04:00')) AS hour_name, count(*) as hour_count
								FROM spotter_output 
								WHERE spotter_output.aircraft_icao = :aircraft_icao
								GROUP BY hour_name 
								ORDER BY hour_name ASC";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':aircraft_icao' => $aircraft_icao));
      
		$hour_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['hour_name'] = $row['hour_name'];
			$temp_array['hour_count'] = $row['hour_count'];
          
			$hour_array[] = $temp_array;
		}

		return $hour_array;
	}
	
	
	/**
	* Counts all hours by aircraft registration
	*
	* @return Array the hour list
	*
	*/
	public static function countAllHoursByRegistration($registration)
	{
		$registration = filter_var($registration,FILTER_SANITIZE_STRING);

		$query  = "SELECT HOUR(CONVERT_TZ(spotter_output.date,'+00:00', '-04:00')) AS hour_name, count(*) as hour_count
								FROM spotter_output 
								WHERE spotter_output.registration = :registration
								GROUP BY hour_name 
								ORDER BY hour_name ASC";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':registration' => $registration));
      
		$hour_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['hour_name'] = $row['hour_name'];
			$temp_array['hour_count'] = $row['hour_count'];
          
			$hour_array[] = $temp_array;
		}

		return $hour_array;
	}
	
	
	/**
	* Counts all hours by airport
	*
	* @return Array the hour list
	*
	*/
	public static function countAllHoursByAirport($airport_icao)
	{
		$airport_icao = filter_var($airport_icao,FILTER_SANITIZE_STRING);

		$query  = "SELECT HOUR(CONVERT_TZ(spotter_output.date,'+00:00', '-04:00')) AS hour_name, count(*) as hour_count
								FROM spotter_output 
								WHERE (spotter_output.departure_airport_icao = :airport_icao OR spotter_output.arrival_airport_icao = :airport_icao)
								GROUP BY hour_name 
								ORDER BY hour_name ASC";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':airport_icao' => $airport_icao));
      
		$hour_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['hour_name'] = $row['hour_name'];
			$temp_array['hour_count'] = $row['hour_count'];
          
			$hour_array[] = $temp_array;
		}

		return $hour_array;
	}
	
	
	
	/**
	* Counts all hours by manufacturer
	*
	* @return Array the hour list
	*
	*/
	public static function countAllHoursByManufacturer($aircraft_manufacturer)
	{
		$aircraft_manufacturer = filter_var($aircraft_manufacturer,FILTER_SANITIZE_STRING);

		$query  = "SELECT HOUR(CONVERT_TZ(spotter_output.date,'+00:00', '-04:00')) AS hour_name, count(*) as hour_count
								FROM spotter_output 
								WHERE spotter_output.aircraft_manufacturer = :aircraft_manufacturer
								GROUP BY hour_name 
								ORDER BY hour_name ASC";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':aircraft_manufacturer' => $aircraft_manufacturer));
      
		$hour_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['hour_name'] = $row['hour_name'];
			$temp_array['hour_count'] = $row['hour_count'];
          
			$hour_array[] = $temp_array;
		}

		return $hour_array;
	}
	
	
	
	/**
	* Counts all hours by date
	*
	* @return Array the hour list
	*
	*/
	public static function countAllHoursByDate($date)
	{
		$date = filter_var($date,FILTER_SANITIZE_STRING);

		$query  = "SELECT HOUR(CONVERT_TZ(spotter_output.date,'+00:00', '-04:00')) AS hour_name, count(*) as hour_count
								FROM spotter_output 
								WHERE DATE(CONVERT_TZ(spotter_output.date,'+00:00', '-04:00')) = :date
								GROUP BY hour_name 
								ORDER BY hour_name ASC";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':date' => $date));
      
		$hour_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['hour_name'] = $row['hour_name'];
			$temp_array['hour_count'] = $row['hour_count'];
          
			$hour_array[] = $temp_array;
		}

		return $hour_array;
	}
	
	
	
	/**
	* Counts all hours by a ident/callsign
	*
	* @return Array the hour list
	*
	*/
	public static function countAllHoursByIdent($ident)
	{
		$ident = filter_var($ident,FILTER_SANITIZE_STRING);

		$query  = "SELECT HOUR(CONVERT_TZ(spotter_output.date,'+00:00', '-04:00')) AS hour_name, count(*) as hour_count
								FROM spotter_output 
								WHERE spotter_output.ident = :ident 
								GROUP BY hour_name 
								ORDER BY hour_name ASC";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':ident' => $ident));
      
		$hour_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['hour_name'] = $row['hour_name'];
			$temp_array['hour_count'] = $row['hour_count'];
          
			$hour_array[] = $temp_array;
		}

		return $hour_array;
	}
	
	
	
	/**
	* Counts all hours by route
	*
	* @return Array the hour list
	*
	*/
	public static function countAllHoursByRoute($departure_airport_icao, $arrival_airport_icao)
	{
		$departure_airport_icao = filter_var($departure_airport_icao,FILTER_SANITIZE_STRING);
		$arrival_airport_icao = filter_var($arrival_airport_icao,FILTER_SANITIZE_STRING);

		$query  = "SELECT HOUR(CONVERT_TZ(spotter_output.date,'+00:00', '-04:00')) AS hour_name, count(*) as hour_count
								FROM spotter_output 
								WHERE (spotter_output.departure_airport_icao = :departure_airport_icao) AND (spotter_output.arrival_airport_icao = :arrival_airport_icao)
								GROUP BY hour_name 
								ORDER BY hour_name ASC";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':departure_airport_icao' => $departure_airport_icao,':arrival_airport_icao' => $arrival_airport_icao));
      
		$hour_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['hour_name'] = $row['hour_name'];
			$temp_array['hour_count'] = $row['hour_count'];
          
			$hour_array[] = $temp_array;
		}

		return $hour_array;
	}
	
	
	/**
	* Counts all hours by country
	*
	* @return Array the hour list
	*
	*/
	public static function countAllHoursByCountry($country)
	{
		$country = filter_var($country,FILTER_SANITIZE_STRING);

		$query  = "SELECT HOUR(CONVERT_TZ(spotter_output.date,'+00:00', '-04:00')) AS hour_name, count(*) as hour_count
								FROM spotter_output 
								WHERE ((spotter_output.departure_airport_country = :country) OR (spotter_output.arrival_airport_country = :country)) OR spotter_output.airline_country = :country
								GROUP BY hour_name 
								ORDER BY hour_name ASC";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':country' => $country));
      
		$hour_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['hour_name'] = $row['hour_name'];
			$temp_array['hour_count'] = $row['hour_count'];
          
			$hour_array[] = $temp_array;
		}

		return $hour_array;
	}




	/**
	* Counts all aircraft that have flown over
	*
	* @return Integer the number of aircrafts
	*
	*/
	public static function countOverallAircrafts()
	{
		$query  = "SELECT COUNT(DISTINCT spotter_output.aircraft_icao) AS aircraft_count  
                    FROM spotter_output
                    WHERE spotter_output.ident <> ''";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute();
		return $sth->fetchColumn();
	}
	
	
	/**
	* Counts all flights that have flown over
	*
	* @return Integer the number of flights
	*
	*/
	public static function countOverallFlights()
	{
		$query  = "SELECT COUNT(DISTINCT spotter_output.spotter_id) AS flight_count  
                    FROM spotter_output";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute();
		return $sth->fetchColumn();
	}
	
	
	
	/**
	* Counts all airlines that have flown over
	*
	* @return Integer the number of airlines
	*
	*/
	public static function countOverallAirlines()
	{
		$query  = "SELECT COUNT(DISTINCT spotter_output.airline_name) AS airline_count 
							FROM spotter_output";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute();
		return $sth->fetchColumn();
	}

  
	/**
	* Counts all hours of today
	*
	* @return Array the hour list
	*
	*/
	public static function countAllHoursFromToday()
	{
		$query  = "SELECT HOUR(CONVERT_TZ(spotter_output.date,'+00:00', '-04:00')) AS hour_name, count(*) as hour_count
								FROM spotter_output 
								WHERE DATE(CONVERT_TZ(spotter_output.date,'+00:00', '-04:00')) = CURDATE()
								GROUP BY hour_name 
								ORDER BY hour_name ASC";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute();
      
		$hour_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['hour_name'] = $row['hour_name'];
			$temp_array['hour_count'] = $row['hour_count'];
			$hour_array[] = $temp_array;
		}

		return $hour_array;
	}
    
	/**
	* Gets all the spotter information based on calculated upcoming flights
	*
	* @return Array the spotter information
	*
	*/
	public static function getUpcomingFlights($limit = '', $sort = '')
	{
		global $global_query;
		date_default_timezone_set('UTC');
		if ($limit != "")
		{
			$limit_array = explode(",", $limit);
			$limit_array[0] = filter_var($limit_array[0],FILTER_SANITIZE_NUMBER_INT);
			$limit_array[1] = filter_var($limit_array[1],FILTER_SANITIZE_NUMBER_INT);
			if ($limit_array[0] >= 0 && $limit_array[1] >= 0)
			{
				$limit_query = " LIMIT ".$limit_array[0].",".$limit_array[1];
			}
		}
		if ($sort != "")
		{
			$search_orderby_array = Spotter::getOrderBy();
			$orderby_query = $search_orderby_array[$sort]['sql'];
		} else {
			$orderby_query = " ORDER BY HOUR(spotter_output.date) ASC";
		}
		$currentHour = date("G");
		$next3Hours = date("G", strtotime("+3 hour"));
		//if the next 3 hours is already equal to/past midnight, we limit it to stay there, otherwise the query will fail
		if ($currentHour >= 21 && $next3Hours >= 00)
		{
			$next3Hours = 24;
		}
		$currentDayofWeek = date("l");
		$query = "SELECT spotter_output.*, count(spotter_output.ident) as ident_count
		    FROM spotter_output
		    WHERE DAYNAME(spotter_output.date) = '$currentDayofWeek' AND HOUR(spotter_output.date) >= '$currentHour' AND HOUR(spotter_output.date) <= '$next3Hours'
		    GROUP BY spotter_output.ident HAVING ident_count > 10 $orderby_query";
		$spotter_array = Spotter::getDataFromDB($query.$limit_query);
		return $spotter_array;
	}
    
    /**
	* Adds the images based on the aircraft registration
	*
	* @return String either success or error
	*
	*/
	public static function addSpotterImage($registration)
	{
		$registration = filter_var($registration,FILTER_SANITIZE_STRING);
		$registration = trim($registration);
        
		//getting the aircraft image
		$image_url = Spotter::findAircraftImage($registration);
		if ($image_url['original'] != '') {
			$query  = "INSERT INTO spotter_image (registration, image, image_thumbnail, image_copyright, image_source) VALUES (:registration,:image,:image_thumbnail,:copyright,:source)";
			try {
				$Connection = new Connection();
				$sth = Connection::$db->prepare($query);
				$sth->execute(array(':registration' => $registration,':image' => $image_url['original'],':image_thumbnail' => $image_url['thumbnail'], ':copyright' => $image_url['copyright'],':source' => $image_url['source']));
			} catch(PDOException $e) {
				return "error";
			}
		}
		return "success";
	}
    
    
    /**
	* Gets the images based on the aircraft registration
	*
	* @return Array the images list
	*
	*/
	public static function getSpotterImage($registration)
	{
    		$registration = filter_var($registration,FILTER_SANITIZE_STRING);
		$registration = trim($registration);

		$query  = "SELECT spotter_image.*
								FROM spotter_image 
								WHERE spotter_image.registration = :registration";

		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':registration' => $registration));
        
        $images_array = array();
		$temp_array = array();

        while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['spotter_image_id'] = $row['spotter_image_id'];
            $temp_array['registration'] = $row['registration'];
            $temp_array['image'] = $row['image'];
            $temp_array['image_thumbnail'] = $row['image_thumbnail'];
            $temp_array['image_source'] = $row['image_source'];
            $temp_array['image_copyright'] = $row['image_copyright'];
          
            $images_array[] = $temp_array;
		}
        
        return $images_array;
	}
    
    
     /**
	* Gets the Barrie Spotter ID based on the FlightAware ID
	*
	* @return Integer the Barrie Spotter ID
	*
	*/
	public static function getBarrieSpotterIDBasedOnFlightAwareID($flightaware_id)
	{
		$flightaware_id = filter_var($flightaware_id,FILTER_SANITIZE_STRING);

		$query  = "SELECT spotter_output.spotter_id
								FROM spotter_output 
								WHERE spotter_output.flightaware_id = '".$flightaware_id."'";
        
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute();

		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			return $row['spotter_id'];
		}
	}
  
 
	/**
	* Parses a date string
	*
	* @param String $dateString the date string
	* @param String $timezone the timezone of a user
	* @return Array the time information
	*
	*/
	public static function parseDateString($dateString, $timezone = '')
	{
		$time_array = array();
	
		if ($timezone != "")
		{
			date_default_timezone_set($timezone);
		}
		
		$current_date = date("Y-m-d H:i:s");
		$date = date("Y-m-d H:i:s",strtotime($dateString." UTC"));
		
		$diff = abs(strtotime($current_date) - strtotime($date));

		$time_array['years'] = floor($diff / (365*60*60*24)); 
		$years = $time_array['years'];
		
		$time_array['months'] = floor(($diff - $years * 365*60*60*24) / (30*60*60*24));
		$months = $time_array['months'];
		
		$time_array['days'] = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24)/ (60*60*24));
		$days = $time_array['days'];
		$time_array['hours'] = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24 - $days*60*60*24)/ (60*60));
		$hours = $time_array['hours'];
		$time_array['minutes'] = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24 - $days*60*60*24 - $hours*60*60)/ 60);
		$minutes = $time_array['minutes'];
		$time_array['seconds'] = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24 - $days*60*60*24 - $hours*60*60 - $minutes*60));  
		
		return $time_array;	
	}	
	
	
	
	
	/**
	* Parses the direction degrees to working
	*
	* @param Float $direction the direction in degrees
	* @return Array the direction information
	*
	*/
	public static function parseDirection($direction)
	{

		if ($direction != "")
		{
			$direction_array = array();
			$temp_array = array();
			
			if ($direction == 360 || ($direction >= 0 && $direction < 22.5))
			{
				$temp_array['direction_degree'] = $direction;
				$temp_array['direction_shortname'] = "N";
				$temp_array['direction_fullname'] = "North";
			} elseif ($direction >= 22.5 && $direction < 45){
				$temp_array['direction_degree'] = $direction;
				$temp_array['direction_shortname'] = "NNE";
				$temp_array['direction_fullname'] = "North-Northeast";
			} elseif ($direction >= 45 && $direction < 67.5){
				$temp_array['direction_degree'] = $direction;
				$temp_array['direction_shortname'] = "NE";
				$temp_array['direction_fullname'] = "Northeast";
			} elseif ($direction >= 67.5 && $direction < 90){
				$temp_array['direction_degree'] = $direction;
				$temp_array['direction_shortname'] = "ENE";
				$temp_array['direction_fullname'] = "East-Northeast";
			} elseif ($direction >= 90 && $direction < 112.5){
				$temp_array['direction_degree'] = $direction;
				$temp_array['direction_shortname'] = "E";
				$temp_array['direction_fullname'] = "East";
			} elseif ($direction >= 112.5 && $direction < 135){
				$temp_array['direction_degree'] = $direction;
				$temp_array['direction_shortname'] = "ESE";
				$temp_array['direction_fullname'] = "East-Southeast";
			} elseif ($direction >= 135 && $direction < 157.5){
				$temp_array['direction_degree'] = $direction;
				$temp_array['direction_shortname'] = "SE";
				$temp_array['direction_fullname'] = "Southeast";
			} elseif ($direction >= 157.5 && $direction < 180){
				$temp_array['direction_degree'] = $direction;
				$temp_array['direction_shortname'] = "SSE";
				$temp_array['direction_fullname'] = "South-Southeast";
			} elseif ($direction >= 180 && $direction < 202.5){
				$temp_array['direction_degree'] = $direction;
				$temp_array['direction_shortname'] = "S";
				$temp_array['direction_fullname'] = "South";
			} elseif ($direction >= 202.5 && $direction < 225){
				$temp_array['direction_degree'] = $direction;
				$temp_array['direction_shortname'] = "SSW";
				$temp_array['direction_fullname'] = "South-Southwest";
			} elseif ($direction >= 225 && $direction < 247.5){
				$temp_array['direction_degree'] = $direction;
				$temp_array['direction_shortname'] = "SW";
				$temp_array['direction_fullname'] = "Southwest";
			} elseif ($direction >= 247.5 && $direction < 270){
				$temp_array['direction_degree'] = $direction;
				$temp_array['direction_shortname'] = "WSW";
				$temp_array['direction_fullname'] = "West-Southwest";
			} elseif ($direction >= 270 && $direction < 292.5){
				$temp_array['direction_degree'] = $direction;
				$temp_array['direction_shortname'] = "W";
				$temp_array['direction_fullname'] = "West";
			} elseif ($direction >= 292.5 && $direction < 315){
				$temp_array['direction_degree'] = $direction;
				$temp_array['direction_shortname'] = "WNW";
				$temp_array['direction_fullname'] = "West-Northwest";
			} elseif ($direction >= 315 && $direction < 337.5){
				$temp_array['direction_degree'] = $direction;
				$temp_array['direction_shortname'] = "NW";
				$temp_array['direction_fullname'] = "Northwest";
			} elseif ($direction >= 337.5 && $direction < 360){
				$temp_array['direction_degree'] = $direction;
				$temp_array['direction_shortname'] = "NNW";
				$temp_array['direction_fullname'] = "North-Northwest";
			}
			
			$direction_array[] = $temp_array;
			
			return $direction_array;
		}
	}	
	
	
	/**
	* Gets the aircraft image
	*
	* @param String $aircraft_registration the registration of the aircraft
	* @return String the aircraft url
	*
	*/
	public static function findAircraftImage($aircraft_registration)
	{
		$aircraft_registration = filter_var($aircraft_registration,FILTER_SANITIZE_STRING);
		$aircraft_registration = trim($aircraft_registration);
		if ($aircraft_registration == '') return array('thumbnail' => '','original' => '', 'copyright' => '', 'source' => '');
		// If aircraft registration is only number, also check with aircraft model
  
		if (preg_match('/^[[:digit]]+$/',$aircraft_registration)) {
			$aircraft_info = Spotter::getAircraftInfoByRegistration($aircraft_registration);
			$url= 'http://www.planespotters.net/Aviation_Photos/search.php?tag='.$aircraft_registration.'&actype=s_'.$aircraft_info['name'].'&output=rss';
		} else {
			//$url= 'http://www.planespotters.net/Aviation_Photos/search.php?tag='.$airline_aircraft_type.'&output=rss';
			$url= 'http://www.planespotters.net/Aviation_Photos/search.php?reg='.$aircraft_registration.'&output=rss';
		}
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (X11; Linux x86_64; rv:32.0) Gecko/20100101 Firefox/32.0');
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT ,100); 
		curl_setopt($ch, CURLOPT_TIMEOUT, 100); 
		curl_setopt($ch, CURLOPT_URL, $url);
		$data = curl_exec($ch);
		curl_close($ch);
		if ($xml = simplexml_load_string($data)) {
			if (isset($xml->channel->item)) {
				$thumbnail_url = trim((string)$xml->channel->item->children('http://search.yahoo.com/mrss/')->thumbnail->attributes()->url);
				$image_url['thumbnail'] = $thumbnail_url;
				$image_url['original'] = str_replace('thumbnail','original',$thumbnail_url);
				$image_url['copyright'] = trim((string)$xml->channel->item->children('http://search.yahoo.com/mrss/')->copyright);
				$image_url['source'] = 'planespotters';
				return $image_url;
			}
		} 

		if (preg_match('/^[[:digit]]+$/',$aircraft_registration)) {
			$aircraft_info = Spotter::getAircraftInfoByRegistration($aircraft_registration);
			$url = 'https://api.flickr.com/services/feeds/photos_public.gne?format=rss2&per_page=1&tags='.$aircraft_registration.','.$aircraft_info['name'].',aircraft';
		} else {
			$url = 'https://api.flickr.com/services/feeds/photos_public.gne?format=rss2&per_page=1&tags='.$aircraft_registration.',aircraft';
		}
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (X11; Linux x86_64; rv:32.0) Gecko/20100101 Firefox/32.0');
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT ,100); 
		curl_setopt($ch, CURLOPT_TIMEOUT, 100); 
		curl_setopt($ch, CURLOPT_URL, $url);
		$data = curl_exec($ch);
		curl_close($ch);
		
		if ($xml = simplexml_load_string($data)) {
			if (isset($xml->channel->item)) {
				$thumbnail_url = trim((string)$xml->channel->item->children('http://search.yahoo.com/mrss/')->thumbnail->attributes()->url);
				$image_url['thumbnail'] = $thumbnail_url;
				$original_url = trim((string)$xml->channel->item->enclosure->attributes()->url);
				//$image_url['original'] = str_replace('_s','_b',$thumbnail_url);
				$image_url['original'] = $original_url;
				$image_url['copyright'] = trim((string)$xml->channel->item->children('http://search.yahoo.com/mrss/')->credit);
				$image_url['source'] = 'flickr';
				return $image_url;
			}
		} 
		
		return array('thumbnail' => '','original' => '', 'copyright' => '','source' => '');
	}
	
	
	/**
	* Gets the aircraft registration
	*
	* @param String $flightaware_id the flight aware id
	* @return String the aircraft registration
	*
	*/
	
	public static function getAircraftRegistration($flightaware_id)
	{
		global $globalFlightAwareUsername, $globalFlightAwarePassword;
        
        $options = array(
			'trace' => true,
			'exceptions' => 0,
			'login' => $globalFlightAwareUsername,
			'password' => $globalFlightAwarePassword,
		);
		$client = new SoapClient('http://flightxml.flightaware.com/soap/FlightXML2/wsdl', $options);
		
		$params = array('faFlightID' => $flightaware_id);
		$result = $client->AirlineFlightInfo($params);
		
		if (isset($result->AirlineFlightInfoResult))
		{
			$registration = $result->AirlineFlightInfoResult->tailnumber;
		}
		
		$registration = Spotter::convertAircraftRegistration($registration);
		
		return $registration;	
	}
	
	public static function getAircraftRegistrationBymodeS($aircraft_modes)
	{
		$aircraft_modes = filter_var($aircraft_modes,FILTER_SANITIZE_STRING);
	
		$query  = "SELECT aircraft_modes.Registration FROM aircraft_modes WHERE aircraft_modes.ModeS = :aircraft_modes LIMIT 1";
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':aircraft_modes' => $aircraft_modes));
    
		$row = $sth->fetch(PDO::FETCH_ASSOC);
		if (count($row) > 0) {
		    return $row['Registration'];
		} else return '';
	
	}
	/**
	* converts the registration code using the country prefix
	*
	* @param String $registration the aircraft registration
	* @return String the aircraft registration
	*
	*/
	public static function convertAircraftRegistration($registration)
	{
		$registration = filter_var($registration,FILTER_SANITIZE_STRING);
		
		$registration_1 = substr($registration, 0, 1);
		$registration_2 = substr($registration, 0, 2);

		//first get the prefix based on two characters
		$query  = "SELECT aircraft_registration.registration_prefix FROM aircraft_registration WHERE registration_prefix = :registration_2";
      
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute(array(':registration_2' => $registration_2));
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$registration_prefix = $row['registration_prefix'];
		}

		//if we didn't find a two chracter prefix lets just search the one with one character
		if ($registration_prefix == "")
		{
			$query  = "SELECT aircraft_registration.registration_prefix FROM aircraft_registration WHERE registration_prefix = :registration_1";
	      
			$sth = Connection::$db->prepare($query);
			$sth->execute(array(':registration_1' => $registration_1));
	        
			while($row = $sth->fetch(PDO::FETCH_ASSOC))
			{
				$registration_prefix = $row['registration_prefix'];
			}
		}

		//determine which characters are being used and convert the registration code appropiately
		if (strlen($registration_prefix) == 1)
		{
			if (0 === strpos($registration, 'N')) {
                $registration = preg_replace("/^(.{1})/", "$1", $registration);
            } else {
                $registration = preg_replace("/^(.{1})/", "$1-", $registration);
            }
		} else if(strlen($registration_prefix) == 2){
            if (0 === strpos($registration, 'N')) {
                $registration = preg_replace("/^(.{2})/", "$1", $registration);
            } else {
                $registration = preg_replace("/^(.{2})/", "$1-", $registration);
            }
		}

		return $registration;	
	}
	
	
	/**
	* Gets the short url from bit.ly
	*
	* @param String $url the full url
	* @return String the bit.ly url
	*
	*/
	public static function getBitlyURL($url)
	{
		global $globalBitlyAccessToken;
		
		if ($globalBitlyAccessToken == '') return $url;
        
		$google_url = 'https://api-ssl.bitly.com/v3/shorten?access_token='.$globalBitlyAccessToken.'&longUrl='.$url;
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $google_url);
		$bitly_data = curl_exec($ch);
		curl_close($ch);
		
		$bitly_data = json_decode($bitly_data);
		
		if ($bitly_data->status_txt = "OK"){
			$bitly_url = $bitly_data->data->url;
		}

		return $bitly_url;
	}


	public static function getOrderBy()
	{
		$orderby = array("aircraft_asc" => array("key" => "aircraft_asc", "value" => "Aircraft Type - ASC", "sql" => "ORDER BY spotter_output.aircraft_icao ASC"), "aircraft_desc" => array("key" => "aircraft_desc", "value" => "Aircraft Type - DESC", "sql" => "ORDER BY spotter_output.aircraft_icao DESC"),"manufacturer_asc" => array("key" => "manufacturer_asc", "value" => "Aircraft Manufacturer - ASC", "sql" => "ORDER BY spotter_output.aircraft_manufacturer ASC"), "manufacturer_desc" => array("key" => "manufacturer_desc", "value" => "Aircraft Manufacturer - DESC", "sql" => "ORDER BY spotter_output.aircraft_manufacturer DESC"),"airline_name_asc" => array("key" => "airline_name_asc", "value" => "Airline Name - ASC", "sql" => "ORDER BY spotter_output.airline_name ASC"), "airline_name_desc" => array("key" => "airline_name_desc", "value" => "Airline Name - DESC", "sql" => "ORDER BY spotter_output.airline_name DESC"), "ident_asc" => array("key" => "ident_asc", "value" => "Ident - ASC", "sql" => "ORDER BY spotter_output.ident ASC"), "ident_desc" => array("key" => "ident_desc", "value" => "Ident - DESC", "sql" => "ORDER BY spotter_output.ident DESC"), "airport_departure_asc" => array("key" => "airport_departure_asc", "value" => "Departure Airport - ASC", "sql" => "ORDER BY spotter_output.departure_airport_city ASC"), "airport_departure_desc" => array("key" => "airport_departure_desc", "value" => "Departure Airport - DESC", "sql" => "ORDER BY spotter_output.departure_airport_city DESC"), "airport_arrival_asc" => array("key" => "airport_arrival_asc", "value" => "Arrival Airport - ASC", "sql" => "ORDER BY spotter_output.arrival_airport_city ASC"), "airport_arrival_desc" => array("key" => "airport_arrival_desc", "value" => "Arrival Airport - DESC", "sql" => "ORDER BY spotter_output.arrival_airport_city DESC"), "date_asc" => array("key" => "date_asc", "value" => "Date - ASC", "sql" => "ORDER BY spotter_output.date ASC"), "date_desc" => array("key" => "date_desc", "value" => "Date - DESC", "sql" => "ORDER BY spotter_output.date DESC"));
		
		return $orderby;
		
	}
    
    
    public static function importFromFlightAware()
    {
       global $globalFlightAwareUsername, $globalFlightAwarePassword, $globalLatitudeMax, $globalLatitudeMin, $globalLongitudeMax, $globalLongitudeMin, $globalAirportIgnore;
        
        if(!Connection::createDBConnection())
		{
			return false;
		}
        
        $options = array(
            'trace' => true,
            'exceptions' => 0,
            'login' => $globalFlightAwareUsername,
            'password' => $globalFlightAwarePassword,
        );
        $client = new SoapClient('http://flightxml.flightaware.com/soap/FlightXML2/wsdl', $options);

        $params = array('query' => '{range lat '.$globalLatitudeMin.' '.$globalLatitudeMax.'} {range lon '.$globalLongitudeMax.' '.$globalLongitudeMin.'} {true inAir}', 'howMany' => '15', 'offset' => '0');
        
        $result = $client->SearchBirdseyeInFlight($params);

        $dataFound = false;
        $ignoreImport = false;
        
        if (isset($result->SearchBirdseyeInFlightResult))
        {
            if (is_array($result->SearchBirdseyeInFlightResult->aircraft))
            {
                    foreach($result->SearchBirdseyeInFlightResult->aircraft as $aircraft)
                    {
                        if (!strstr($aircraft->origin, 'L ') && !strstr($aircraft->destination, 'L '))
                        {
                            foreach($globalAirportIgnore as $airportIgnore)
                            {
                                if ($aircraft->origin == $airportIgnore || $aircraft->destination == $airportIgnore)
                                {
                                   $ignoreImport = true; 
                                }
                            }
                            if ($ignoreImport == false)
                            {
                            $flightaware_id = $aircraft->faFlightID;
                            $ident = $aircraft->ident;
                            $aircraft_type = $aircraft->type;
                            $departure_airport = $aircraft->origin;
                            $arrival_airport = $aircraft->destination;
                            $latitude = $aircraft->latitude;
                            $longitude = $aircraft->longitude;
                            $waypoints = $aircraft->waypoints;
                            $altitude = $aircraft->altitude;
                            $heading = $aircraft->heading;
                            $groundspeed = $aircraft->groundspeed;

                            $dataFound = true;

                            //gets the callsign from the last hour
                            $last_hour_ident = Spotter::getIdentFromLastHour($ident);

                            //change the departure/arrival airport to NA if its not available
                                if ($departure_airport == "" || $departure_airport == "---" || $departure_airport == "ZZZ" || $departure_airport == "ZZZZ") { $departure_airport = "NA"; }
                                if ($arrival_airport == "" || $arrival_airport == "---" || $arrival_airport == "ZZZ" || $arrival_airport == "ZZZZ") { $arrival_airport = "NA"; }


                            //if there was no aircraft with the same callsign within the last hour and go post it into the archive
                            if($last_hour_ident == "")
                            {
                                //adds the spotter data for the archive
                                Spotter::addSpotterData($flightaware_id, $ident, $aircraft_type, $departure_airport, $arrival_airport, $latitude, $longitude, $waypoints, $altitude, $heading, $groundspeed);
                            }

                            //adds the spotter LIVE data
                            SpotterLive::addLiveSpotterData($flightaware_id, $ident, $aircraft_type, $departure_airport, $arrival_airport, $latitude, $longitude, $waypoints, $altitude, $heading, $groundspeed);
                        }
                        }
                        $ignoreImport = false;
                    }
                } else {
                    if (!strstr($result->SearchBirdseyeInFlightResult->aircraft->origin, 'L ') && !strstr($result->SearchBirdseyeInFlightResult->aircraft->destination, 'L '))
                    {
                        foreach($globalAirportIgnore as $airportIgnore)
                        {
                            foreach($globalAirportIgnore as $airportIgnore)
                            {
                                if ($aircraft->origin == $airportIgnore || $aircraft->destination == $airportIgnore)
                                {
                                   $ignoreImport = true; 
                                }
                            }
                            if ($ignoreImport == false)
                            {
                        $flightaware_id = $result->SearchBirdseyeInFlightResult->aircraft->faFlightID;
                        $ident = $result->SearchBirdseyeInFlightResult->aircraft->ident;
                        $aircraft_type = $result->SearchBirdseyeInFlightResult->aircraft->type;
                        $departure_airport = $result->SearchBirdseyeInFlightResult->aircraft->origin;
                        $arrival_airport = $result->SearchBirdseyeInFlightResult->aircraft->destination;
                        $latitude = $result->SearchBirdseyeInFlightResult->aircraft->latitude;
                        $longitude = $result->SearchBirdseyeInFlightResult->aircraft->longitude;
                        $waypoints = $result->SearchBirdseyeInFlightResult->aircraft->waypoints;
                        $altitude = $result->SearchBirdseyeInFlightResult->aircraft->altitude;
                        $heading = $result->SearchBirdseyeInFlightResult->aircraft->heading;
                        $groundspeed = $result->SearchBirdseyeInFlightResult->aircraft->groundspeed;

                        $dataFound = true;

                        //gets the callsign from the last hour
                        $last_hour_ident = Spotter::getIdentFromLastHour($ident);

                        //change the departure/arrival airport to NA if its not available
                                if ($departure_airport == "" || $departure_airport == "---" || $departure_airport == "ZZZ" || $departure_airport == "ZZZZ") { $departure_airport = "NA"; }
                                if ($arrival_airport == "" || $arrival_airport == "---" || $arrival_airport == "ZZZ" || $arrival_airport == "ZZZZ") { $arrival_airport = "NA"; }

                        //if there was no aircraft with the same callsign within the last hour and go post it into the archive
                        if($last_hour_ident == "")
                        {
                            //adds the spotter data for the archive
                            Spotter::addSpotterData($flightaware_id, $ident, $aircraft_type, $departure_airport, $arrival_airport, $latitude, $longitude, $waypoints, $altitude, $heading, $groundspeed);
                        }

                        //adds the spotter LIVE data
                        SpotterLive::addLiveSpotterData($flightaware_id, $ident, $aircraft_type, $departure_airport, $arrival_airport, $latitude, $longitude, $waypoints, $altitude, $heading, $groundspeed);
                    }
                            $ignoreImport = false;
                        }
                    }

                }
        } 
    }


	// Update flights data when new data in DB
	public static function updateFieldsFromOtherTables()
	{
		// routes
		print "Routes...\n";
		$query = "SELECT spotter_output.spotter_id, routes.FromAirport_ICAO, routes.ToAirport_ICAO FROM spotter_output, routes WHERE spotter_output.ident = routes.CallSign AND ( spotter_output.departure_airport_icao != routes.FromAirport_ICAO OR spotter_output.arrival_airport_icao != routes.ToAirport_ICAO) AND routes.FromAirport_ICAO != ''";
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$departure_airport_array = Spotter::getAllAirportInfo($row['FromAirport_ICAO']);
			$arrival_airport_array = Spotter::getAllAirportInfo($row['ToAirport_ICAO']);
			if (count($departure_airport_array) > 0 && count($arrival_airport_array) > 0) {
				$update_query="UPDATE spotter_output SET departure_airport_icao = :fromicao, arrival_airport_icao = :toicao, departure_airport_name = :departure_airport_name, departure_airport_city = :departure_airport_city, departure_airport_country = :departure_airport_country, arrival_airport_name = :arrival_airport_name, arrival_airport_city = :arrival_airport_city, arrival_airport_country = :arrival_airport_country WHERE spotter_id = :spotter_id";
				$sthu = Connection::$db->prepare($update_query);
				$sthu->execute(array(':fromicao' => $row['FromAirport_ICAO'],':toicao' => $row['ToAirport_ICAO'],':spotter_id' => $row['spotter_id'],':departure_airport_name' => $departure_airport_array[0]['name'],':departure_airport_city' => $departure_airport_array[0]['city'],':departure_airport_country' => $departure_airport_array[0]['country'],':arrival_airport_name' => $arrival_airport_array[0]['name'],':arrival_airport_city' => $arrival_airport_array[0]['city'],':arrival_airport_country' => $arrival_airport_array[0]['country']));
			}
		}
		
		print "Airlines...\n";
		//airlines
		$query  = "SELECT spotter_output.spotter_id, spotter_output.ident FROM spotter_output WHERE spotter_output.airline_name = ''";
		$sth = Connection::$db->prepare($query);
		$sth->execute();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			if (is_numeric(substr($row['ident'], -1, 1)))
			{
				$airline_array = Spotter::getAllAirlineInfo(substr($row['ident'], 0, 3));
				$update_query  = "UPDATE spotter_output SET spotter_output.airline_name = :airline_name, spotter_output.airline_icao = :airline_icao, spotter_output.airline_country = :airline_country, spotter_output.airline_type = :airline_type WHERE spotter_output.spotter_id = :spotter_id";
				$sthu = Connection::$db->prepare($update_query);
				$sthu->execute(array(':airline_name' => $airline_array[0]['name'],':airline_icao' => $airline_array[0]['icao'], ':airline_country' => $airline_array[0]['country'], ':airline_type' => $airline_array[0]['type'], ':spotter_id' => $row['spotter_id']));
			}
		}

		print "Aircraft...\n";
		//aircraft
		$query  = "SELECT spotter_output.spotter_id, spotter_output.aircraft_icao, spotter_output.registration FROM spotter_output WHERE spotter_output.aircraft_name = ''";
		$sth = Connection::$db->prepare($query);
		$sth->execute();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$aircraft_name = Spotter::getAllAircraftInfo($row['aircraft_icao']);
			if ($row['registration'] != ""){
				Spotter::addSpotterImage($row['registration']);
			}
			$update_query  = "UPDATE spotter_output SET spotter_output.aircraft_name = :aircraft_name, spotter_output.aircraft_manufacturer = :aircraft_manufacturer WHERE spotter_output.spotter_id = :spotter_id";
			$sthu = Connection::$db->prepare($update_query);
			$sthu->execute(array(':aircraft_name' => $aircraft_name[0]['type'], ':aircraft_manufacturer' => $aircraft_name[0]['manufacturer'], ':spotter_id' => $row['spotter_id']));
		}
	}	
	
}


?>