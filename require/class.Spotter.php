<?php
require_once('class.Scheduler.php');
require_once('class.ACARS.php');
require_once('class.Image.php');
$global_query = "SELECT spotter_output.* FROM spotter_output";

class Spotter{
	public $db;
	
	function __construct() {
		$Connection = new Connection();
		$this->db = $Connection->db;
	}
	
	/**
	* Executes the SQL statements to get the spotter information
	*
	* @param String $query the SQL query
	* @param String $limit the limit query
	* @return Array the spotter information
	*
	*/
	public function getDataFromDB($query, $params = array(), $limitQuery = '')
	{
		global $globalSquawkCountry, $globalIVAO;
		$Image = new Image();
		$Schedule = new Schedule();
		$ACARS = new ACARS();
		if (!isset($globalIVAO)) $globalIVAO = FALSE;
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

		
		try {
			$sth = $this->db->prepare($query.$limitQuery);
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
			} elseif (isset($row['spotter_archive_id'])) {
				$temp_array['spotter_id'] = $row['spotter_archive_id'];
			} else {
				$temp_array['spotter_id'] = $row['spotter_id'];
			}
			$temp_array['flightaware_id'] = $row['flightaware_id'];
			if (isset($row['modes'])) $temp_array['modes'] = $row['modes'];
			$temp_array['ident'] = $row['ident'];
			if (isset($row['registration']) && $row['registration'] != '') {
				$temp_array['registration'] = $row['registration'];
			} elseif (isset($temp_array['modes'])) {
				$temp_array['registration'] = $this->getAircraftRegistrationBymodeS($temp_array['modes']);
			} else $temp_array['registration'] = '';
			$temp_array['aircraft_type'] = $row['aircraft_icao'];
			
			$temp_array['departure_airport'] = $row['departure_airport_icao'];
			$temp_array['arrival_airport'] = $row['arrival_airport_icao'];
			$temp_array['latitude'] = $row['latitude'];
			$temp_array['longitude'] = $row['longitude'];
			/*
			if (Connection->tableExists('countries')) {
				$country_info = Spotter->getCountryFromLatitudeLongitude($temp_array['latitude'],$temp_array['longitude']);
				if (is_array($country_info) && isset($country_info['name']) && isset($country_info['iso2'])) {
				    $temp_array['country'] = $country_info['name'];
				    $temp_array['country_iso2'] = $country_info['iso2'];
				}
			}
			*/
			$temp_array['waypoints'] = $row['waypoints'];
			if (isset($row['route_stop'])) {
				$temp_array['route_stop'] = $row['route_stop'];
				if ($row['route_stop'] != '') {
					$allroute = explode(' ',$row['route_stop']);
			
					foreach ($allroute as $route) {
						$route_airport_array = $this->getAllAirportInfo($route);
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
			$heading_direction = $this->parseDirection($row['heading']);
			if (isset($heading_direction[0]['direction_fullname'])) $temp_array['heading_name'] = $heading_direction[0]['direction_fullname'];
			$temp_array['ground_speed'] = $row['ground_speed'];
			$temp_array['image'] = "";
			$temp_array['image_thumbnail'] = "";
			$temp_array['image_source'] = "";
			$temp_array['image_copyright'] = "";
 
			if (isset($row['highlight'])) {
				$temp_array['highlight'] = $row['highlight'];
			} else $temp_array['highlight'] = '';
			
			$dateArray = $this->parseDateString($row['date']);
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
			
			if (isset($row['aircraft_name']) && $row['aircraft_name'] != '' && isset($row['aircraft_shadow']) && $row['aircraft_shadow'] != '') {
				$temp_array['aircraft_name'] = $row['aircraft_name'];
				$temp_array['aircraft_manufacturer'] = $row['aircraft_manufacturer'];
				if (isset($row['aircraft_shadow'])) {
					$temp_array['aircraft_shadow'] = $row['aircraft_shadow'];
				}
			} else {
				$aircraft_array = $this->getAllAircraftInfo($row['aircraft_icao']);
				if (count($aircraft_array) > 0) {
					$temp_array['aircraft_name'] = $aircraft_array[0]['type'];
					$temp_array['aircraft_manufacturer'] = $aircraft_array[0]['manufacturer'];
				
					if ($aircraft_array[0]['aircraft_shadow'] != NULL) {
						$temp_array['aircraft_shadow'] = $aircraft_array[0]['aircraft_shadow'];
					} else $temp_array['aircraft_shadow'] = 'default.png';
                                } else {
                            		$temp_array['aircraft_shadow'] = 'default.png';
					$temp_array['aircraft_name'] = 'N/A';
					$temp_array['aircraft_manufacturer'] = 'N/A';
                            	}
			}
			if (!isset($row['airline_name']) || $row['airline_name'] == '') {
				$airline_array = array();
				if (!is_numeric(substr($row['ident'], 0, 3))) {
					if (is_numeric(substr($row['ident'], 2, 1))) {
						$airline_array = $this->getAllAirlineInfo(substr($row['ident'], 0, 2));
					} elseif (is_numeric(substr($row['ident'], 3, 1))) {
						$airline_array = $this->getAllAirlineInfo(substr($row['ident'], 0, 3));
					} else {
						$airline_array = $this->getAllAirlineInfo('NA');
					}
				} else {
					$airline_array = $this->getAllAirlineInfo('NA');
				}
				if (count($airline_array) > 0) {
					$temp_array['airline_icao'] = $airline_array[0]['icao'];
					$temp_array['airline_iata'] = $airline_array[0]['iata'];
					$temp_array['airline_name'] = $airline_array[0]['name'];
					$temp_array['airline_country'] = $airline_array[0]['country'];
					$temp_array['airline_callsign'] = $airline_array[0]['callsign'];
					$temp_array['airline_type'] = $airline_array[0]['type'];
				}
			} else {
				$temp_array['airline_icao'] = $row['airline_icao'];
				if (isset($row['airline_iata'])) $temp_array['airline_iata'] = $row['airline_iata'];
				else $temp_array['airline_iata'] = '';
				$temp_array['airline_name'] = $row['airline_name'];
				$temp_array['airline_country'] = $row['airline_country'];
				if (isset($row['airline_callsign'])) $temp_array['airline_callsign'] = $row['airline_callsign'];
				else $temp_array['airline_callsign'] = 'N/A';
				$temp_array['airline_type'] = $row['airline_type'];
			}
			if (isset($temp_array['airline_iata']) && $temp_array['airline_iata'] != '') {
				$acars_array = $ACARS->getLiveAcarsData($temp_array['airline_iata'].substr($temp_array['ident'],3));
				//$acars_array = ACARS->getLiveAcarsData('BA40YL');
				if (count($acars_array) > 0) {
					$temp_array['acars'] = $acars_array;
					//print_r($acars_array);
				}
			}

			if($temp_array['registration'] != "" || ($globalIVAO && $temp_array['aircraft_type'] != ''))
			{
				if ($globalIVAO) {
					if (isset($temp_array['airline_icao']))	$image_array = $Image->getSpotterImage('',$temp_array['aircraft_type'],$temp_array['airline_icao']);
					else $image_array = $Image->getSpotterImage('',$temp_array['aircraft_type']);
				} else $image_array = $Image->getSpotterImage($temp_array['registration']);
				if (count($image_array) > 0) {
					$temp_array['image'] = $image_array[0]['image'];
					$temp_array['image_thumbnail'] = $image_array[0]['image_thumbnail'];
					$temp_array['image_source'] = $image_array[0]['image_source'];
					$temp_array['image_source_website'] = $image_array[0]['image_source_website'];
					if ($temp_array['image_source_website'] == '' && $temp_array['image_source'] == 'planespotters') {
						$planespotter_url_array = explode("_", $temp_array['image']);
						$planespotter_id = str_replace(".jpg", "", $planespotter_url_array[1]);
						$temp_array['image_source_website'] = 'http://www.planespotters.net/Aviation_Photos/photo.show?id='.$planespotter_id;
					 }
					$temp_array['image_copyright'] = $image_array[0]['image_copyright'];
				}
			}


			if (!isset($globalIVAO) || ! $globalIVAO) {
				$schedule_array = $Schedule->getSchedule($temp_array['ident']);
				//print_r($schedule_array);
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
			}
			
			//if ($row['departure_airport_icao'] != '' && $row['departure_airport_name'] == '') {
			if ($row['departure_airport_icao'] != '') {
				$departure_airport_array = $this->getAllAirportInfo($row['departure_airport_icao']);
				if (!isset($departure_airport_array[0]['name'])) $departure_airport_array = $this->getAllAirportInfo('NA');
			/*
			} elseif ($row['departure_airport_name'] != '') {
				$temp_array['departure_airport_name'] = $row['departure_airport_name'];
				$temp_array['departure_airport_city'] = $row['departure_airport_city'];
				$temp_array['departure_airport_country'] = $row['departure_airport_country'];
				$temp_array['departure_airport_icao'] = $row['departure_airport_icao'];
			*/
			} else $departure_airport_array = $this->getAllAirportInfo('NA');
			if (isset($departure_airport_array[0]['name'])) {
				$temp_array['departure_airport_name'] = $departure_airport_array[0]['name'];
				$temp_array['departure_airport_city'] = $departure_airport_array[0]['city'];
				$temp_array['departure_airport_country'] = $departure_airport_array[0]['country'];
				$temp_array['departure_airport_iata'] = $departure_airport_array[0]['iata'];
				$temp_array['departure_airport_icao'] = $departure_airport_array[0]['icao'];
				$temp_array['departure_airport_latitude'] = $departure_airport_array[0]['latitude'];
				$temp_array['departure_airport_longitude'] = $departure_airport_array[0]['longitude'];
				$temp_array['departure_airport_altitude'] = $departure_airport_array[0]['altitude'];
			}

			/*
			if (isset($row['departure_airport_time'])) {
				$temp_array['departure_airport_time'] = $row['departure_airport_time'];
			}
			*/
			
			if ($row['arrival_airport_icao'] != '') {
				$arrival_airport_array = $this->getAllAirportInfo($row['arrival_airport_icao']);
				if (count($arrival_airport_array) == 0) $arrival_airport_array = $this->getAllAirportInfo('NA');
			} else $arrival_airport_array = $this->getAllAirportInfo('NA');
			if (isset($arrival_airport_array[0]['name'])) {
				$temp_array['arrival_airport_name'] = $arrival_airport_array[0]['name'];
				$temp_array['arrival_airport_city'] = $arrival_airport_array[0]['city'];
				$temp_array['arrival_airport_country'] = $arrival_airport_array[0]['country'];
				$temp_array['arrival_airport_iata'] = $arrival_airport_array[0]['iata'];
				$temp_array['arrival_airport_icao'] = $arrival_airport_array[0]['icao'];
				$temp_array['arrival_airport_latitude'] = $arrival_airport_array[0]['latitude'];
				$temp_array['arrival_airport_longitude'] = $arrival_airport_array[0]['longitude'];
				$temp_array['arrival_airport_altitude'] = $arrival_airport_array[0]['altitude'];
			}
			/*
			if (isset($row['arrival_airport_time'])) {
				$temp_array['arrival_airport_time'] = $row['arrival_airport_time'];
			}
			*/
			if (isset($row['pilot_id']) && $row['pilot_id'] != '') $temp_array['pilot_id'] = $row['pilot_id'];
			if (isset($row['pilot_name']) && $row['pilot_name'] != '') $temp_array['pilot_name'] = $row['pilot_name'];
			if (isset($row['squawk'])) {
				$temp_array['squawk'] = $row['squawk'];
				if ($row['squawk'] != '' && isset($temp_array['country_iso2'])) {
					$temp_array['squawk_usage'] = $this->getSquawkUsage($row['squawk'],$temp_array['country_iso2']);
					if ($temp_array['squawk_usage'] == '' && isset($globalSquawkCountry)) $temp_array['squawk_usage'] = $this->getSquawkUsage($row['squawk'],$globalSquawkCountry);
				} elseif ($row['squawk'] != '' && isset($globalSquawkCountry)) $temp_array['squawk_usage'] = $this->getSquawkUsage($row['squawk'],$globalSquawkCountry);
			}
    			
			$temp_array['query_number_rows'] = $num_rows;
			
			$spotter_array[] = $temp_array;
		}
		if ($num_rows == 0) return array();
		$spotter_array[0]['query_number_rows'] = $num_rows;
		return $spotter_array;
	}	
	
	
	/**
	* Gets all the spotter information
	*
	* @return Array the spotter information
	*
	*/
	public function searchSpotterData($q = '', $registration = '', $aircraft_icao = '', $aircraft_manufacturer = '', $highlights = '', $airline_icao = '', $airline_country = '', $airline_type = '', $airport = '', $airport_country = '', $callsign = '', $departure_airport_route = '', $arrival_airport_route = '', $altitude = '', $date_posted = '', $limit = '', $sort = '', $includegeodata = '')
	{
		global $globalTimezone;
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
			
			if ($globalTimezone != '') {
				date_default_timezone_set($globalTimezone);
				$datetime = new DateTime();
				$offset = $datetime->format('P');
			} else $offset = '+00:00';


			if ($date_array[1] != "")
			{                
				$date_array[0] = date("Y-m-d H:i:s", strtotime($date_array[0]));
				$date_array[1] = date("Y-m-d H:i:s", strtotime($date_array[1]));
				$additional_query .= " AND TIMESTAMP(CONVERT_TZ(spotter_output.date,'+00:00', '".$offset."')) >= '".$date_array[0]."' AND TIMESTAMP(CONVERT_TZ(spotter_output.date,'+00:00', '".$offset."')) <= '".$date_array[1]."' ";
			} else {
				$date_array[0] = date("Y-m-d H:i:s", strtotime($date_array[0]));
              
				$additional_query .= " AND TIMESTAMP(CONVERT_TZ(spotter_output.date,'+00:00', '".$offset."')) >= '".$date_array[0]."' ";
              
			}
		}
		
		if ($limit != "")
		{
			$limit_array = explode(",", $limit);
			
			$limit_array[0] = filter_var($limit_array[0],FILTER_SANITIZE_NUMBER_INT);
			$limit_array[1] = filter_var($limit_array[1],FILTER_SANITIZE_NUMBER_INT);
			
			if ($limit_array[0] >= 0 && $limit_array[1] >= 0)
			{
				//$limit_query = " LIMIT ".$limit_array[0].",".$limit_array[1];
				$limit_query = " LIMIT ".$limit_array[1]." OFFSET ".$limit_array[0];
			}
		}
		
		if ($sort != "")
		{
			$search_orderby_array = $this->getOrderBy();
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

		$spotter_array = $this->getDataFromDB($query, array(),$limit_query);

		return $spotter_array;
	}
	
	
	/**
	* Gets all the spotter information based on the latest data entry
	*
	* @return Array the spotter information
	*
	*/
	public function getLatestSpotterData($limit = '', $sort = '')
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
				//$limit_query = " LIMIT ".$limit_array[0].",".$limit_array[1];
				$limit_query = " LIMIT ".$limit_array[1]." OFFSET ".$limit_array[0];
			}
		}
		
		if ($sort != "")
		{
			$search_orderby_array = $this->getOrderBy();
			$orderby_query = $search_orderby_array[$sort]['sql'];
		} else {
			$orderby_query = " ORDER BY spotter_output.date DESC";
		}

		$query  = $global_query." ".$orderby_query;

		$spotter_array = $this->getDataFromDB($query, array(),$limit_query);

		return $spotter_array;
	}
    
    
    /**
	* Gets all the spotter information based on a user's latitude and longitude
	*
	* @return Array the spotter information
	*
	*/
	public function getLatestSpotterForLayar($lat, $lng, $radius, $interval)
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
					$additional_query = ' AND DATE_SUB(UTC_TIMESTAMP(),INTERVAL 30 MINUTE) <= $this_output.date ';
				} else if ($interval == "1h"){
					$additional_query = ' AND DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 HOUR) <= $this_output.date ';
				} else if ($interval == "3h"){
					$additional_query = ' AND DATE_SUB(UTC_TIMESTAMP(),INTERVAL 3 HOUR) <= $this_output.date ';
				} else if ($interval == "6h"){
					$additional_query = ' AND DATE_SUB(UTC_TIMESTAMP(),INTERVAL 6 HOUR) <= $this_output.date ';
				} else if ($interval == "12h"){
					$additional_query = ' AND DATE_SUB(UTC_TIMESTAMP(),INTERVAL 12 HOUR) <= $this_output.date ';
				} else if ($interval == "24h"){
					$additional_query = ' AND DATE_SUB(UTC_TIMESTAMP(),INTERVAL 24 HOUR) <= $this_output.date ';
				} else if ($interval == "7d"){
					$additional_query = ' AND DATE_SUB(UTC_TIMESTAMP(),INTERVAL 7 DAY) <= $this_output.date ';
				} else if ($interval == "30d"){
					$additional_query = ' AND DATE_SUB(UTC_TIMESTAMP(),INTERVAL 30 DAY) <= $this_output.date ';
				} 
			}
		}

		$query  = "SELECT spotter_output.*, ( 6371 * acos( cos( radians($lat) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians($lng) ) + sin( radians($lat) ) * sin( radians( latitude ) ) ) ) AS distance FROM spotter_output 
                   WHERE spotter_output.latitude <> '' 
				   AND spotter_output.longitude <> '' 
                   ".$additional_query."
                   HAVING distance < :radius  
				   ORDER BY distance";

		$spotter_array = $this->getDataFromDB($query, array(':radius' => $radius),$limit_query);

		return $spotter_array;
	}
    
    
    /**
	* Gets all the spotter information sorted by the newest aircraft type
	*
	* @return Array the spotter information
	*
	*/
	public function getNewestSpotterDataSortedByAircraftType($limit = '', $sort = '')
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
				//$limit_query = " LIMIT ".$limit_array[0].",".$limit_array[1];
				$limit_query = " LIMIT ".$limit_array[1]." OFFSET ".$limit_array[0];
			}
		}
		
		if ($sort != "")
		{
			$search_orderby_array = $this->getOrderBy();
			$orderby_query = $search_orderby_array[$sort]['sql'];
		} else {
			$orderby_query = " ORDER BY spotter_output.date DESC ";
		}

		$query  = $global_query." WHERE spotter_output.aircraft_name <> '' GROUP BY spotter_output.aircraft_icao ".$orderby_query;

		$spotter_array = $this->getDataFromDB($query, array(), $limit_query);

		return $spotter_array;
	}
    
    
    /**
	* Gets all the spotter information sorted by the newest aircraft registration
	*
	* @return Array the spotter information
	*
	*/
	public function getNewestSpotterDataSortedByAircraftRegistration($limit = '', $sort = '')
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
				//$limit_query = " LIMIT ".$limit_array[0].",".$limit_array[1];
				$limit_query = " LIMIT ".$limit_array[1]." OFFSET ".$limit_array[0];
			}
		}
		
		if ($sort != "")
		{
			$search_orderby_array = $this->getOrderBy();
			$orderby_query = $search_orderby_array[$sort]['sql'];
		} else {
			$orderby_query = " ORDER BY spotter_output.date DESC ";
		}

		$query  = $global_query." WHERE spotter_output.registration <> '' GROUP BY spotter_output.registration ".$orderby_query;

		$spotter_array = $this->getDataFromDB($query, array(), $limit_query);

		return $spotter_array;
	}
    
    
    /**
	* Gets all the spotter information sorted by the newest airline
	*
	* @return Array the spotter information
	*
	*/
	public function getNewestSpotterDataSortedByAirline($limit = '', $sort = '')
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
				//$limit_query = " LIMIT ".$limit_array[0].",".$limit_array[1];
				$limit_query = " LIMIT ".$limit_array[1]." OFFSET ".$limit_array[0];
			}
		}
		
		if ($sort != "")
		{
			$search_orderby_array = $this->getOrderBy();
			$orderby_query = $search_orderby_array[$sort]['sql'];
		} else {
			$orderby_query = " ORDER BY spotter_output.date DESC ";
		}

		$query  = $global_query." WHERE spotter_output.airline_name <> '' GROUP BY spotter_output.airline_icao ".$orderby_query;

		$spotter_array = $this->getDataFromDB($query, array(), $limit_query);

		return $spotter_array;
	}
    
    
    /**
	* Gets all the spotter information sorted by the newest departure airport
	*
	* @return Array the spotter information
	*
	*/
	public function getNewestSpotterDataSortedByDepartureAirport($limit = '', $sort = '')
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
				//$limit_query = " LIMIT ".$limit_array[0].",".$limit_array[1];
				$limit_query = " LIMIT ".$limit_array[1]." OFFSET ".$limit_array[0];
			}
		}
		
		if ($sort != "")
		{
			$search_orderby_array = $this->getOrderBy();
			$orderby_query = $search_orderby_array[$sort]['sql'];
		} else {
			$orderby_query = " ORDER BY spotter_output.date DESC ";
		}

		$query  = $global_query." WHERE spotter_output.departure_airport_name <> '' AND spotter_output.departure_airport_icao <> 'NA' GROUP BY spotter_output.departure_airport_icao ".$orderby_query;

		$spotter_array = $this->getDataFromDB($query, array(), $limit_query);

		return $spotter_array;
	}
    
    
    /**
	* Gets all the spotter information sorted by the newest arrival airport
	*
	* @return Array the spotter information
	*
	*/
	public function getNewestSpotterDataSortedByArrivalAirport($limit = '', $sort = '')
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
				//$limit_query = " LIMIT ".$limit_array[0].",".$limit_array[1];
				$limit_query = " LIMIT ".$limit_array[1]." OFFSET ".$limit_array[0];
			}
		}
		
		if ($sort != "")
		{
			$search_orderby_array = $this->getOrderBy();
			$orderby_query = $search_orderby_array[$sort]['sql'];
		} else {
			$orderby_query = " ORDER BY spotter_output.date DESC ";
		}

		$query  = $global_query." WHERE spotter_output.arrival_airport_name <> '' AND spotter_output.arrival_airport_icao <> 'NA' GROUP BY spotter_output.arrival_airport_icao ".$orderby_query;

		$spotter_array = $this->getDataFromDB($query, array(), $limit_query);

		return $spotter_array;
	}
	

	/**
	* Gets all the spotter information based on the spotter id
	*
	* @return Array the spotter information
	*
	*/
	public function getSpotterDataByID($id = '')
	{
		global $global_query;
		
		date_default_timezone_set('UTC');
		
		$query_values = array();
		$additional_query = '';
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

		$spotter_array = $this->getDataFromDB($query,$query_values);

		return $spotter_array;
	}

	
	
	
	/**
	* Gets all the spotter information based on the callsign
	*
	* @return Array the spotter information
	*
	*/
	public function getSpotterDataByIdent($ident = '', $limit = '', $sort = '')
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
				//$limit_query = " LIMIT ".$limit_array[0].",".$limit_array[1];
				$limit_query = " LIMIT ".$limit_array[1]." OFFSET ".$limit_array[0];
			}
		}

		if ($sort != "")
		{
			$search_orderby_array = $this->getOrderBy();
			$orderby_query = $search_orderby_array[$sort]['sql'];
		} else {
			$orderby_query = " ORDER BY spotter_output.date DESC";
		}

		$query = $global_query." WHERE spotter_output.ident <> '' ".$additional_query." ".$orderby_query;

		$spotter_array = $this->getDataFromDB($query, $query_values, $limit_query);

		return $spotter_array;
	}
	
	
	
	/**
	* Gets all the spotter information based on the aircraft type
	*
	* @return Array the spotter information
	*
	*/
	public function getSpotterDataByAircraft($aircraft_type = '', $limit = '', $sort = '')
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
				//$limit_query = " LIMIT ".$limit_array[0].",".$limit_array[1];
				$limit_query = " LIMIT ".$limit_array[1]." OFFSET ".$limit_array[0];
			}
		}

		if ($sort != "")
		{
			$search_orderby_array = $this->getOrderBy();
			$orderby_query = $search_orderby_array[$sort]['sql'];
		} else {
			$orderby_query = " ORDER BY spotter_output.date DESC";
		}

		$query = $global_query." WHERE spotter_output.ident <> '' ".$additional_query." ".$orderby_query;

		$spotter_array = $this->getDataFromDB($query, $query_values, $limit_query);

		return $spotter_array;
	}
	
	
	/**
	* Gets all the spotter information based on the aircraft registration
	*
	* @return Array the spotter information
	*
	*/
	public function getSpotterDataByRegistration($registration = '', $limit = '', $sort = '')
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
				//$limit_query = " LIMIT ".$limit_array[0].",".$limit_array[1];
				$limit_query = " LIMIT ".$limit_array[1]." OFFSET ".$limit_array[0];
			}
		}

		if ($sort != "")
		{
			$search_orderby_array = $this->getOrderBy();
			$orderby_query = $search_orderby_array[$sort]['sql'];
		} else {
			$orderby_query = " ORDER BY spotter_output.date DESC";
		}

		$query = $global_query." WHERE spotter_output.ident <> '' ".$additional_query." ".$orderby_query;

		$spotter_array = $this->getDataFromDB($query, $query_values, $limit_query);

		return $spotter_array;
	}

	
	
	
	/**
	* Gets all the spotter information based on the airline
	*
	* @return Array the spotter information
	*
	*/
	public function getSpotterDataByAirline($airline = '', $limit = '', $sort = '')
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
				//$limit_query = " LIMIT ".$limit_array[0].",".$limit_array[1];
				$limit_query = " LIMIT ".$limit_array[1]." OFFSET ".$limit_array[0];
			}
		}
		
		if ($sort != "")
		{
			$search_orderby_array = $this->getOrderBy();
			$orderby_query = $search_orderby_array[$sort]['sql'];
		} else {
			$orderby_query = " ORDER BY spotter_output.date DESC";
		}

		$query = $global_query." WHERE spotter_output.ident <> '' ".$additional_query." ".$orderby_query;
		$spotter_array = $this->getDataFromDB($query, $query_values, $limit_query);

		return $spotter_array;
	}
	
	
	/**
	* Gets all the spotter information based on the airport
	*
	* @return Array the spotter information
	*
	*/
	public function getSpotterDataByAirport($airport = '', $limit = '', $sort = '')
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
				//$limit_query = " LIMIT ".$limit_array[0].",".$limit_array[1];
				$limit_query = " LIMIT ".$limit_array[1]." OFFSET ".$limit_array[0];
			}
		}
		
		if ($sort != "")
		{
			$search_orderby_array = $this->getOrderBy();
			$orderby_query = $search_orderby_array[$sort]['sql'];
		} else {
			$orderby_query = " ORDER BY spotter_output.date DESC";
		}

		$query = $global_query." WHERE spotter_output.ident <> '' ".$additional_query." AND ((spotter_output.departure_airport_icao <> 'NA') AND (spotter_output.arrival_airport_icao <> 'NA')) ".$orderby_query;

		$spotter_array = $this->getDataFromDB($query, $query_values, $limit_query);

		return $spotter_array;
	}



	/**
	* Gets all the spotter information based on the date
	*
	* @return Array the spotter information
	*
	*/
	public function getSpotterDataByDate($date = '', $limit = '', $sort = '')
	{
		global $global_query, $globalTimezone, $globalDBdriver;
		
		$query_values = array();
		
		if ($date != "")
		{
			if ($globalTimezone != '') {
				date_default_timezone_set($globalTimezone);
				$datetime = new DateTime($date);
				$offset = $datetime->format('P');
			} else $offset = '+00:00';
			if ($globalDBdriver == 'mysql') {
				$additional_query = " AND DATE(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) = :date ";
				$query_values = array(':date' => $datetime->format('Y-m-d'), ':offset' => $offset);
			} elseif ($globalDBdriver == 'pgsql') {
				$additional_query = " AND spotter_output.date AT TIME ZONE :timezone = :date ";
				$query_values = array(':date' => $datetime->format('Y-m-d'), ':timezone' => $globalTimezone);
			}
		}
		
		if ($limit != "")
		{
			$limit_array = explode(",", $limit);
			
			$limit_array[0] = filter_var($limit_array[0],FILTER_SANITIZE_NUMBER_INT);
			$limit_array[1] = filter_var($limit_array[1],FILTER_SANITIZE_NUMBER_INT);
			
			if ($limit_array[0] >= 0 && $limit_array[1] >= 0)
			{
				//$limit_query = " LIMIT ".$limit_array[0].",".$limit_array[1];
				$limit_query = " LIMIT ".$limit_array[1]." OFFSET ".$limit_array[0];
			}
		}

		if ($sort != "")
		{
			$search_orderby_array = $this->getOrderBy();
			$orderby_query = $search_orderby_array[$sort]['sql'];
		} else {
			$orderby_query = " ORDER BY spotter_output.date DESC";
		}

		$query = $global_query." WHERE spotter_output.ident <> '' ".$additional_query." ".$orderby_query;
		
		$spotter_array = $this->getDataFromDB($query, $query_values, $limit_query);

		return $spotter_array;
	}



	/**
	* Gets all the spotter information based on the country name
	*
	* @return Array the spotter information
	*
	*/
	public function getSpotterDataByCountry($country = '', $limit = '', $sort = '')
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
				//$limit_query = " LIMIT ".$limit_array[0].",".$limit_array[1];
				$limit_query = " LIMIT ".$limit_array[1]." OFFSET ".$limit_array[0];
			}
		}
					
		if ($sort != "")
		{
			$search_orderby_array = $this->getOrderBy();
			$orderby_query = $search_orderby_array[$sort]['sql'];
		} else {
			$orderby_query = " ORDER BY spotter_output.date DESC";
		}

		$query = $global_query." WHERE spotter_output.ident <> '' ".$additional_query." ".$orderby_query;

		$spotter_array = $this->getDataFromDB($query, $query_values, $limit_query);

		return $spotter_array;
	}	
	
	
	/**
	* Gets all the spotter information based on the manufacturer name
	*
	* @return Array the spotter information
	*
	*/
	public function getSpotterDataByManufacturer($aircraft_manufacturer = '', $limit = '', $sort = '')
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
				//$limit_query = " LIMIT ".$limit_array[0].",".$limit_array[1];
				$limit_query = " LIMIT ".$limit_array[1]." OFFSET ".$limit_array[0];
			}
		}

		if ($sort != "")
		{
			$search_orderby_array = $this->getOrderBy();
			$orderby_query = $search_orderby_array[$sort]['sql'];
		} else {
			$orderby_query = " ORDER BY spotter_output.date DESC";
		}

		$query = $global_query." WHERE spotter_output.ident <> '' ".$additional_query." ".$orderby_query;

		$spotter_array = $this->getDataFromDB($query, $query_values, $limit_query);

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
	public function getSpotterDataByRoute($departure_airport_icao = '', $arrival_airport_icao = '', $limit = '', $sort = '')
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
				//$limit_query = " LIMIT ".$limit_array[0].",".$limit_array[1];
				$limit_query = " LIMIT ".$limit_array[1]." OFFSET ".$limit_array[0];
			}
		}
	
		if ($sort != "")
		{
			$search_orderby_array = $this->getOrderBy();
			$orderby_query = $search_orderby_array[$sort]['sql'];
		} else {
			$orderby_query = " ORDER BY spotter_output.date DESC";
		}

		$query = $global_query." WHERE spotter_output.ident <> '' ".$additional_query." ".$orderby_query;
          
		//$result = mysqli_query($GLOBALS["___mysqli_ston"], $query);

		$spotter_array = $this->getDataFromDB($query, $query_values, $limit_query);

		return $spotter_array;
	}
	
	
	
	/**
	* Gets all the spotter information based on the special column in the table
	*
	* @return Array the spotter information
	*
	*/
	public function getSpotterDataByHighlight($limit = '', $sort = '')
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
				//$limit_query = " LIMIT ".$limit_array[0].",".$limit_array[1];
				$limit_query = " LIMIT ".$limit_array[1]." OFFSET ".$limit_array[0];
			}
		}
		
		if ($sort != "")
		{
			$search_orderby_array = $this->getOrderBy();
			$orderby_query = $search_orderby_array[$sort]['sql'];
		} else {
			$orderby_query = " ORDER BY spotter_output.date DESC";
		}

		$query  = $global_query." WHERE spotter_output.highlight <> '' ".$orderby_query;

		$spotter_array = $this->getDataFromDB($query, array(), $limit_query);

		return $spotter_array;
	}
    
    
    
    /**
	* Gets all the highlight based on a aircraft registration
	*
	* @return String the highlight text
	*
	*/
	public function getHighlightByRegistration($registration)
	{
		global $global_query;
		
		date_default_timezone_set('UTC');
		
		$registration = filter_var($registration,FILTER_SANITIZE_STRING);

		
		$query  = $global_query." WHERE spotter_output.highlight <> '' AND spotter_output.registration = :registration";
		$sth = $this->db->prepare($query);
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
	public function getSquawkUsage($squawk = '',$country = 'FR')
	{
		
		$squawk = filter_var($squawk,FILTER_SANITIZE_STRING);
		$country = filter_var($country,FILTER_SANITIZE_STRING);

		$query_values = array();

		$query  = "SELECT squawk.* FROM squawk WHERE squawk.code = :squawk AND squawk.country = :country LIMIT 1";
		$query_values = array(':squawk' => ltrim($squawk,'0'), ':country' => $country);
		
		$sth = $this->db->prepare($query);
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
	public function getAirportIcao($airport_iata = '')
	{
		
		$airport_iata = filter_var($airport_iata,FILTER_SANITIZE_STRING);

		$query_values = array();

		$query  = "SELECT airport.* FROM airport WHERE airport.iata = :airport LIMIT 1";
		$query_values = array(':airport' => $airport_iata);
		
		
		$sth = $this->db->prepare($query);
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
	public function getAllAirportInfo($airport = '')
	{
		
		$airport = filter_var($airport,FILTER_SANITIZE_STRING);

		$query_values = array();

		if ($airport == '') {
			$query  = "SELECT airport.* FROM airport";
		} else {
			$query  = "SELECT airport.* FROM airport WHERE airport.icao = :airport";
			$query_values = array(':airport' => $airport);
		}
		
		$sth = $this->db->prepare($query);
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
	public function getAllAirportInfobyCountry($countries)
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
		
		$sth = $this->db->prepare($query);
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
	public function getAllAirportInfobyCoord($coord)
	{
		$lst_countries = '';
		if (is_array($coord)) {
			$minlong = filter_var($coord[0],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
			$minlat = filter_var($coord[1],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
			$maxlong = filter_var($coord[2],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
			$maxlat = filter_var($coord[3],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
		}
		$query  = "SELECT airport.* FROM airport WHERE airport.latitude BETWEEN ".$minlat." AND ".$maxlat." AND airport.longitude BETWEEN ".$minlong." AND ".$maxlong." AND airport.type != 'closed'";
		
		$sth = $this->db->prepare($query);
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
	public function getAllWaypointsInfobyCoord($coord)
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
		
		$sth = $this->db->prepare($query);
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
	public function getAllAirlineInfo($airline_icao)
	{
		$airline_icao = filter_var($airline_icao,FILTER_SANITIZE_STRING);
		if ($airline_icao == 'NA') {
			$airline_array[] = array('name' => 'Not Available','iata' => 'NA', 'icao' => 'NA', 'callsign' => '', 'country' => 'NA', 'type' =>'');
			return $airline_array;
		} else {
			if (strlen($airline_icao) == 2) {
			    $query  = "SELECT airlines.* FROM airlines WHERE airlines.iata = :airline_icao AND airlines.active = 'Y'";
			} else {
			    $query  = "SELECT airlines.* FROM airlines WHERE airlines.icao = :airline_icao AND airlines.active = 'Y'";
			}
			
			$sth = $this->db->prepare($query);
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
	}
	
	
	
	/**
	* Gets the aircraft info based on the aircraft type
	*
	* @param String $aircraft_type the aircraft type
	* @return Array aircraft information
	*
	*/
	public function getAllAircraftInfo($aircraft_type)
	{
		$aircraft_type = filter_var($aircraft_type,FILTER_SANITIZE_STRING);

		$query  = "SELECT aircraft.* FROM aircraft WHERE aircraft.icao = :aircraft_type";
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':aircraft_type' => $aircraft_type));

		$aircraft_array = array();
		$temp_array = array();
		
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array = array();
			$temp_array['icao'] = $row['icao'];
			$temp_array['type'] = $row['type'];
			$temp_array['manufacturer'] = $row['manufacturer'];
			$temp_array['aircraft_shadow'] = $row['aircraft_shadow'];

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
	public function getAllAircraftType($aircraft_modes)
	{
		$aircraft_modes = filter_var($aircraft_modes,FILTER_SANITIZE_STRING);

		$query  = "SELECT aircraft_modes.ICAOTypeCode FROM aircraft_modes WHERE aircraft_modes.ModeS = :aircraft_modes LIMIT 1";
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':aircraft_modes' => $aircraft_modes));

		$row = $sth->fetch(PDO::FETCH_ASSOC);
		if (count($row) > 0) {
			return $row['icaotypecode'];
		} else return '';
	}

	/**
	* Gets correct aircraft operator corde
	*
	* @param String $operator the aircraft operator code (callsign)
	* @return String aircraft operator code
	*
	*/
	public function getOperator($operator)
	{
		$operator = filter_var($operator,FILTER_SANITIZE_STRING);
		$query  = "SELECT translation.operator_correct FROM translation WHERE translation.operator = :operator LIMIT 1";
		
		$sth = $this->db->prepare($query);
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
	public function getRouteInfo($callsign)
	{
		$callsign = filter_var($callsign,FILTER_SANITIZE_STRING);
                if ($callsign == '') return array();
		$query  = "SELECT * FROM routes WHERE CallSign = :callsign LIMIT 1";
		
		$sth = $this->db->prepare($query);
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
	public function getAircraftInfoByRegistration($registration)
	{
		$registration = filter_var($registration,FILTER_SANITIZE_STRING);

		$query  = "SELECT spotter_output.aircraft_icao, spotter_output.aircraft_name, spotter_output.aircraft_manufacturer, spotter_output.airline_icao FROM spotter_output WHERE spotter_output.registration = :registration";
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':registration' => $registration));

		$aircraft_array = array();
		$temp_array = array();
		
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airline_icao'] = $row['airline_icao'];
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
	public function getAllFlightsforSitemap()
	{
		$query  = "SELECT spotter_output.spotter_id, spotter_output.ident, spotter_output.airline_name, spotter_output.aircraft_name, spotter_output.aircraft_icao, spotter_output.image FROM spotter_output";
		
		$sth = $this->db->prepare($query);
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
	public function getAllManufacturers()
	{
		$query  = "SELECT DISTINCT spotter_output.aircraft_manufacturer AS aircraft_manufacturer
								FROM spotter_output
								WHERE spotter_output.aircraft_manufacturer <> '' 
								ORDER BY spotter_output.aircraft_manufacturer ASC";
		
		$sth = $this->db->prepare($query);
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
	public function getAllAircraftTypes()
	{
		$query  = "SELECT DISTINCT spotter_output.aircraft_icao AS aircraft_icao, spotter_output.aircraft_name AS aircraft_name
								FROM spotter_output  
								WHERE spotter_output.aircraft_icao <> '' 
								ORDER BY spotter_output.aircraft_name ASC";
								
		
		$sth = $this->db->prepare($query);
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
	public function getAllAircraftRegistrations()
	{
		$query  = "SELECT DISTINCT spotter_output.registration 
								FROM spotter_output  
								WHERE spotter_output.registration <> '' 
								ORDER BY spotter_output.registration ASC";						
								
		
		$sth = $this->db->prepare($query);
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
	public function getAllAirlineNames($airline_type = '')
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
		
		$sth = $this->db->prepare($query);
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
	public function getAllAirlineCountries()
	{
		$query  = "SELECT DISTINCT spotter_output.airline_country AS airline_country
								FROM spotter_output  
								WHERE spotter_output.airline_country <> '' 
								ORDER BY spotter_output.airline_country ASC";						
								
		
		$sth = $this->db->prepare($query);
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
	public function getAllAirportNames()
	{
		$airport_array = array();
								
		$query  = "SELECT DISTINCT spotter_output.departure_airport_icao AS airport_icao, spotter_output.departure_airport_name AS airport_name, spotter_output.departure_airport_city AS airport_city, spotter_output.departure_airport_country AS airport_country
								FROM spotter_output 
								WHERE spotter_output.departure_airport_icao <> '' AND spotter_output.departure_airport_icao <> 'NA' 
								ORDER BY spotter_output.departure_airport_city ASC";		
					
		
		$sth = $this->db->prepare($query);
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
								WHERE spotter_output.arrival_airport_icao <> '' AND spotter_output.arrival_airport_icao <> 'NA' 
								ORDER BY spotter_output.arrival_airport_city ASC";
					
		$sth = $this->db->prepare($query);
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
	public function getAllAirportCountries()
	{
		$airport_array = array();
					
		$query  = "SELECT DISTINCT spotter_output.departure_airport_country AS airport_country
								FROM spotter_output
								WHERE spotter_output.departure_airport_country <> '' 
								ORDER BY spotter_output.departure_airport_country ASC";
					
		
		$sth = $this->db->prepare($query);
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
					
		$sth = $this->db->prepare($query);
		$sth->execute();
		
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			if (isset($airport_array[$row['airport_country']]['airport_country']) && $airport_array[$row['airport_country']]['airport_country'] != $row['airport_country'])
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
	public function getAllCountries()
	{
		$Connection= new Connection();
		if ($Connection->tableExists('countries')) {
		
		$country_array = array();
					
		$query  = "SELECT countries.name AS airport_country
								FROM countries
								ORDER BY countries.name ASC";
					
		$sth = $this->db->prepare($query);
		$sth->execute();
   
		$temp_array = array();
		
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['country'] = $row['airport_country'];

			$country_array[$row['airport_country']] = $temp_array;
		}
		} else {

		$query  = "SELECT DISTINCT spotter_output.departure_airport_country AS airport_country
								FROM spotter_output
								WHERE spotter_output.departure_airport_country <> '' 
								ORDER BY spotter_output.departure_airport_country ASC";
					
		
		$sth = $this->db->prepare($query);
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
					
		$sth = $this->db->prepare($query);
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
					
		$sth = $this->db->prepare($query);
		$sth->execute();
		
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			if (isset($country_array[$row['airline_country']]['country']) && $country_array[$row['airline_country']]['country'] != $row['airline_country'])
			{
				$temp_array['country'] = $row['airline_country'];
				
				$country_array[$row['country']] = $temp_array;
			}
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
	public function getAllIdents()
	{
		$query  = "SELECT DISTINCT spotter_output.ident
								FROM spotter_output
								WHERE spotter_output.ident <> '' 
								ORDER BY spotter_output.ident ASC";							
								
		
		$sth = $this->db->prepare($query);
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
	public function getAllDates()
	{
		global $globalTimezone;
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime();
			$offset = $datetime->format('P');
		} else $offset = '+00:00';

		$query  = "SELECT DISTINCT DATE(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) as date
								FROM spotter_output
								WHERE spotter_output.date <> '' 
								ORDER BY spotter_output.date ASC";							
								
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':offset' => $offset));
    
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
	public function getAllRoutes()
	{
		$query  = "SELECT DISTINCT concat(spotter_output.departure_airport_icao, ' - ',  spotter_output.arrival_airport_icao) AS route,  spotter_output.departure_airport_icao, spotter_output.arrival_airport_icao 
					FROM spotter_output
                    WHERE spotter_output.ident <> '' 
                    GROUP BY route
                    ORDER BY route ASC";
      
		
		$sth = $this->db->prepare($query);
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
	public function addSpotterData($flightaware_id = '', $ident = '', $aircraft_icao = '', $departure_airport_icao = '', $arrival_airport_icao = '', $latitude = '', $longitude = '', $waypoints = '', $altitude = '', $heading = '', $groundspeed = '', $date = '', $departure_airport_time = '', $arrival_airport_time = '',$squawk = '', $route_stop = '', $highlight = '', $ModeS = '', $registration = '',$pilot_id = '', $pilot_name = '', $verticalrate = '')
	{
		global $globalURL, $globalIVAO;
		
		$Image = new Image();
		$Common = new Common();
		
		if (!isset($globalIVAO)) $globalIVAO = FALSE;
		date_default_timezone_set('UTC');
		
		//getting the registration
		if ($flightaware_id != "" && $registration == '')
		{
			if (!is_string($flightaware_id))
			{
				return false;
			} else {
				if ($ModeS != '') {
					$registration = $this->getAircraftRegistrationBymodeS($ModeS);
				} else {
					$myhex = explode('-',$flightaware_id);
					if (count($myhex) > 0) {
						$registration = $this->getAircraftRegistrationBymodeS($myhex[0]);
					}
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
						$airline_array = $this->getAllAirlineInfo(substr($ident, 0, 2));
					} elseif (is_numeric(substr(substr($ident, 0, 4), -1, 1))) {
						$airline_array = $this->getAllAirlineInfo(substr($ident, 0, 3));
					} else {
						$airline_array = $this->getAllAirlineInfo("NA");
					}
					if (count($airline_array) == 0) {
						$airline_array = $this->getAllAirlineInfo("NA");
					}
					if (!isset($airline_array[0]['icao']) || $airline_array[0]['icao'] == ""){
						$airline_array = $this->getAllAirlineInfo("NA");
					}
					
				} else {
					$airline_array = $this->getAllAirlineInfo("NA");
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
					$aircraft_array = $this->getAllAircraftInfo("NA");
				} else {
					$aircraft_array = $this->getAllAircraftInfo($aircraft_icao);
				}
			}
		} else {
			if ($ModeS != '') {
				$aircraft_icao = $this->getAllAircraftType($ModeS);
				if ($aircraft_icao == "" || $aircraft_icao == "XXXX")
				{
					$aircraft_array = $this->getAllAircraftInfo("NA");
				} else {
					$aircraft_array = $this->getAllAircraftInfo($aircraft_icao);
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
				$departure_airport_array = $this->getAllAirportInfo($departure_airport_icao);
			}
		}
		
		//getting the arrival airport information
		if ($arrival_airport_icao != "")
		{
			if (!is_string($arrival_airport_icao))
			{
				return false;
			} else {
				$arrival_airport_array = $this->getAllAirportInfo($arrival_airport_icao);
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
		if (($registration != "" || $registration != 'NA') && !$globalIVAO)
		{
			$image_array = $Image->getSpotterImage($registration);
			if (!isset($image_array[0]['registration']))
			{
				//echo "Add image !!!! \n";
				$Image->addSpotterImage($registration);
			}
		}
    
		if ($globalIVAO && $aircraft_icao != '')
		{
            		if (isset($airline_array[0]['icao'])) $airline_icao = $airline_array[0]['icao'];
            		else $airline_icao = '';
			$image_array = $Image->getSpotterImage('',$aircraft_icao,$airline_icao);
			if (!isset($image_array[0]['registration']))
			{
				//echo "Add image !!!! \n";
				$Image->addSpotterImage('',$aircraft_icao,$airline_icao);
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
		$ModeS = filter_var($ModeS,FILTER_SANITIZE_STRING);
		$pilot_id = filter_var($pilot_id,FILTER_SANITIZE_STRING);
		$pilot_name = filter_var($pilot_name,FILTER_SANITIZE_STRING);
		$verticalrate = filter_var($verticalrate,FILTER_SANITIZE_NUMBER_INT);
	
		if (count($airline_array) == 0) 
		{
                        $airline_array = $this->getAllAirlineInfo('NA');
                }
                if (count($aircraft_array) == 0) 
                {
                        $aircraft_array = $this->getAllAircraftInfo('NA');
                }
                if (count($departure_airport_array) == 0) 
                {
                        $departure_airport_array = $this->getAllAirportInfo('NA');
                }
                if (count($arrival_airport_array) == 0) 
                {
                        $arrival_airport_array = $this->getAllAirportInfo('NA');
                }
                if ($registration == '') $registration = 'NA';
                if ($squawk == '' || $Common->isInteger($squawk) == false) $squawk = NULL;
                if ($verticalrate == '' || $Common->isInteger($verticalrate) == false) $verticalrate = NULL;
                if ($heading == '' || $Common->isInteger($heading) == false) $heading = 0;
                if ($groundspeed == '' || $Common->isInteger($groundspeed) == false) $groundspeed = 0;
                $query  = "INSERT INTO spotter_output (flightaware_id, ident, registration, airline_name, airline_icao, airline_country, airline_type, aircraft_icao, aircraft_name, aircraft_manufacturer, departure_airport_icao, departure_airport_name, departure_airport_city, departure_airport_country, arrival_airport_icao, arrival_airport_name, arrival_airport_city, arrival_airport_country, latitude, longitude, waypoints, altitude, heading, ground_speed, date, departure_airport_time, arrival_airport_time, squawk, route_stop,highlight,ModeS, pilot_id, pilot_name, verticalrate) 
                VALUES (:flightaware_id,:ident,:registration,:airline_name,:airline_icao,:airline_country,:airline_type,:aircraft_icao,:aircraft_type,:aircraft_manufacturer,:departure_airport_icao,:departure_airport_name,:departure_airport_city,:departure_airport_country, :arrival_airport_icao, :arrival_airport_name, :arrival_airport_city, :arrival_airport_country, :latitude,:longitude,:waypoints,:altitude,:heading,:groundspeed,:date, :departure_airport_time, :arrival_airport_time, :squawk, :route_stop, :highlight, :ModeS, :pilot_id, :pilot_name, :verticalrate)";

                $airline_name = $airline_array[0]['name'];
                $airline_icao = $airline_array[0]['icao'];
                $airline_country = $airline_array[0]['country'];
                $airline_type = $airline_array[0]['type'];
                $aircraft_type = $aircraft_array[0]['type'];
                $aircraft_manufacturer = $aircraft_array[0]['manufacturer'];
                $departure_airport_name = $departure_airport_array[0]['name'];
                $departure_airport_city = $departure_airport_array[0]['city'];
                $departure_airport_country = $departure_airport_array[0]['country'];
                $arrival_airport_icao = $arrival_airport_icao;
                $arrival_airport_name = $arrival_airport_array[0]['name'];
                $arrival_airport_city = $arrival_airport_array[0]['city'];
                $arrival_airport_country = $arrival_airport_array[0]['country'];
                $query_values = array(':flightaware_id' => $flightaware_id,':ident' => $ident, ':registration' => $registration,':airline_name' => $airline_name,':airline_icao' => $airline_icao,':airline_country' => $airline_country,':airline_type' => $airline_type,':aircraft_icao' => $aircraft_icao,':aircraft_type' => $aircraft_type,':aircraft_manufacturer' => $aircraft_manufacturer,':departure_airport_icao' => $departure_airport_icao,':departure_airport_name' => $departure_airport_name,':departure_airport_city' => $departure_airport_city,':departure_airport_country' => $departure_airport_country,':arrival_airport_icao' => $arrival_airport_icao,':arrival_airport_name' => $arrival_airport_name,':arrival_airport_city' => $arrival_airport_city,':arrival_airport_country' => $arrival_airport_country,':latitude' => $latitude,':longitude' => $longitude, ':waypoints' => $waypoints,':altitude' => $altitude,':heading' => $heading,':groundspeed' => $groundspeed,':date' => $date,':departure_airport_time' => $departure_airport_time,':arrival_airport_time' => $arrival_airport_time, ':squawk' => $squawk, ':route_stop' => $route_stop, ':highlight' => $highlight, ':ModeS' => $ModeS, ':pilot_id' => $pilot_id, ':pilot_name' => $pilot_name, ':verticalrate' => $verticalrate);


		try {
		        
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
			$this->db = null;
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
	public function getIdentFromLastHour($ident)
	{
		global $globalDBdriver, $globalTimezone;
		if ($globalDBdriver == 'mysql') {
			$query  = "SELECT spotter_output.ident FROM spotter_output 
								WHERE spotter_output.ident = :ident 
								AND spotter_output.date >= DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 HOUR) 
								AND spotter_output.date < UTC_TIMESTAMP()";
			$query_data = array(':ident' => $ident);
		} elseif ($globalDBdriver == 'pgsql') {
			$query  = "SELECT spotter_output.ident FROM spotter_output 
								WHERE spotter_output.ident = :ident 
								AND spotter_output.date >= now() AT TIME ZONE 'UTC' - '1 HOUR'->INTERVAL
								AND spotter_output.date < now() AT TIME ZONE 'UTC'";
			$query_data = array(':ident' => $ident);
    		}
		
		$sth = $this->db->prepare($query);
		$sth->execute($query_data);
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
	public function getRealTimeData($q = '')
	{
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
      
		$spotter_array = $this->getDataFromDB($query, array());

		return $spotter_array;
	}
	
	
	
	 /**
	* Gets all airlines that have flown over
	*
	* @return Array the airline list
	*
	*/
	public function countAllAirlines()
	{
/*
		$query  = "SELECT DISTINCT spotter_output.airline_name, spotter_output.airline_icao, spotter_output.airline_country, COUNT(spotter_output.airline_name) AS airline_count
		 			FROM spotter_output
					WHERE spotter_output.airline_name <> '' 
          GROUP BY spotter_output.airline_name
					ORDER BY airline_count DESC
					LIMIT 0,10";
*/
		$query  = "SELECT DISTINCT spotter_output.airline_name, spotter_output.airline_icao, spotter_output.airline_country, COUNT(spotter_output.airline_name) AS airline_count
		 			FROM spotter_output
					WHERE spotter_output.airline_name <> '' AND spotter_output.airline_icao <> 'NA' 
          GROUP BY spotter_output.airline_name,spotter_output.airline_icao, spotter_output.airline_country
					ORDER BY airline_count DESC
					LIMIT 10";
      
		
		$sth = $this->db->prepare($query);
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
	public function countAllAirlinesByAircraft($aircraft_icao)
	{
		$aircraft_icao = filter_var($aircraft_icao,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.airline_name, spotter_output.airline_icao, spotter_output.airline_country, COUNT(spotter_output.airline_name) AS airline_count
		 			FROM spotter_output
					WHERE spotter_output.airline_name <> '' AND spotter_output.aircraft_icao = :aircraft_icao 
                    GROUP BY spotter_output.airline_name
					ORDER BY airline_count DESC";
      
		
		$sth = $this->db->prepare($query);
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
	public function countAllAirlineCountriesByAircraft($aircraft_icao)
	{
		$aircraft_icao = filter_var($aircraft_icao,FILTER_SANITIZE_STRING);
      
		$query  = "SELECT DISTINCT spotter_output.airline_country, COUNT(spotter_output.airline_country) AS airline_country_count
		 			FROM spotter_output
					WHERE spotter_output.airline_country <> '' AND spotter_output.aircraft_icao = :aircraft_icao
                    GROUP BY spotter_output.airline_country
					ORDER BY airline_country_count DESC
					LIMIT 0,10";
      
		
		$sth = $this->db->prepare($query);
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
	public function countAllAirlinesByAirport($airport_icao)
	{
		$airport_icao = filter_var($airport_icao,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.airline_name, spotter_output.airline_icao, spotter_output.airline_country, COUNT(spotter_output.airline_name) AS airline_count
		 			FROM spotter_output
					WHERE spotter_output.airline_name <> '' AND (spotter_output.departure_airport_icao = :airport_icao OR spotter_output.arrival_airport_icao = :airport_icao ) 
                    GROUP BY spotter_output.airline_name
					ORDER BY airline_count DESC";
      
		
		$sth = $this->db->prepare($query);
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
	public function countAllAirlineCountriesByAirport($airport_icao)
	{
		$airport_icao = filter_var($airport_icao,FILTER_SANITIZE_STRING);
      
		$query  = "SELECT DISTINCT spotter_output.airline_country, COUNT(spotter_output.airline_country) AS airline_country_count
		 			FROM spotter_output
					WHERE spotter_output.airline_country <> '' AND (spotter_output.departure_airport_icao = :airport_icao OR spotter_output.arrival_airport_icao = :airport_icao )
                    GROUP BY spotter_output.airline_country
					ORDER BY airline_country_count DESC
					LIMIT 0,10";
      
		
		$sth = $this->db->prepare($query);
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
	public function countAllAirlinesByManufacturer($aircraft_manufacturer)
	{
		$aircraft_manufacturer = filter_var($aircraft_manufacturer,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.airline_name, spotter_output.airline_icao, spotter_output.airline_country, COUNT(spotter_output.airline_name) AS airline_count
		 			FROM spotter_output
					WHERE spotter_output.aircraft_manufacturer = :aircraft_manufacturer 
                    GROUP BY spotter_output.airline_name
					ORDER BY airline_count DESC";
      
		
		$sth = $this->db->prepare($query);
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
	public function countAllAirlineCountriesByManufacturer($aircraft_manufacturer)
	{
		$aircraft_manufacturer = filter_var($aircraft_manufacturer,FILTER_SANITIZE_STRING);
      
		$query  = "SELECT DISTINCT spotter_output.airline_country, COUNT(spotter_output.airline_country) AS airline_country_count
		 			FROM spotter_output
					WHERE spotter_output.airline_country <> '' AND spotter_output.aircraft_manufacturer = :aircraft_manufacturer 
                    GROUP BY spotter_output.airline_country
					ORDER BY airline_country_count DESC
					LIMIT 0,10";
      
		
		$sth = $this->db->prepare($query);
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
	public function countAllAirlinesByDate($date)
	{
		global $globalTimezone;
		$date = filter_var($date,FILTER_SANITIZE_STRING);

		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime($date);
			$offset = $datetime->format('P');
		} else $offset = '+00:00';


		$query  = "SELECT DISTINCT spotter_output.airline_name, spotter_output.airline_icao, spotter_output.airline_country, COUNT(spotter_output.airline_name) AS airline_count
		 			FROM spotter_output
					WHERE DATE(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) = :date 
           GROUP BY spotter_output.airline_name
					ORDER BY airline_count DESC";
      
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':date' => $date, ':offset' => $offset));
      
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
	public function countAllAirlineCountriesByDate($date)
	{
		global $globalTimezone;
		$date = filter_var($date,FILTER_SANITIZE_STRING);
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime($date);
			$offset = $datetime->format('P');
		} else $offset = '+00:00';
		
		$query  = "SELECT DISTINCT spotter_output.airline_country, COUNT(spotter_output.airline_country) AS airline_country_count
		 			FROM spotter_output
					WHERE spotter_output.airline_country <> '' AND DATE(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) = :date 
                    GROUP BY spotter_output.airline_country
					ORDER BY airline_country_count DESC
					LIMIT 0,10";
      
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':date' => $date, ':offset' => $offset));
      
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
	public function countAllAirlinesByIdent($ident)
	{
		$ident = filter_var($ident,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.airline_name, spotter_output.airline_icao, spotter_output.airline_country, COUNT(spotter_output.airline_name) AS airline_count
		 			FROM spotter_output
					WHERE spotter_output.ident = :ident  
           GROUP BY spotter_output.airline_name
					ORDER BY airline_count DESC";
      
		
		$sth = $this->db->prepare($query);
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
	public function countAllAirlinesByRoute($departure_airport_icao, $arrival_airport_icao)
	{
		$departure_airport_icao = filter_var($departure_airport_icao,FILTER_SANITIZE_STRING);
		$arrival_airport_icao = filter_var($arrival_airport_icao,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.airline_name, spotter_output.airline_icao, spotter_output.airline_country, COUNT(spotter_output.airline_name) AS airline_count
		 			FROM spotter_output
					WHERE (spotter_output.departure_airport_icao = :departure_airport_icao) AND (spotter_output.arrival_airport_icao = :arrival_airport_icao) 
           GROUP BY spotter_output.airline_name
					ORDER BY airline_count DESC";
      
		
		$sth = $this->db->prepare($query);
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
	public function countAllAirlineCountriesByRoute($departure_airport_icao, $arrival_airport_icao)
	{
		$departure_airport_icao = filter_var($departure_airport_icao,FILTER_SANITIZE_STRING);
		$arrival_airport_icao = filter_var($arrival_airport_icao,FILTER_SANITIZE_STRING);
      
		$query  = "SELECT DISTINCT spotter_output.airline_country, COUNT(spotter_output.airline_country) AS airline_country_count
		 			FROM spotter_output
					WHERE spotter_output.airline_country <> '' AND (spotter_output.departure_airport_icao = :departure_airport_icao) AND (spotter_output.arrival_airport_icao = :arrival_airport_icao) 
                    GROUP BY spotter_output.airline_country
					ORDER BY airline_country_count DESC
					LIMIT 0,10";
      
		
		$sth = $this->db->prepare($query);
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
	public function countAllAirlinesByCountry($country)
	{
		$country = filter_var($country,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.airline_name, spotter_output.airline_icao, spotter_output.airline_country, COUNT(spotter_output.airline_name) AS airline_count
		 			FROM spotter_output
					WHERE ((spotter_output.departure_airport_country = :country) OR (spotter_output.arrival_airport_country = :country)) OR spotter_output.airline_country = :country  
           GROUP BY spotter_output.airline_name
					ORDER BY airline_count DESC";
      
		
		$sth = $this->db->prepare($query);
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
	public function countAllAirlineCountriesByCountry($country)
	{
		$country = filter_var($country,FILTER_SANITIZE_STRING);
      
		$query  = "SELECT DISTINCT spotter_output.airline_country, COUNT(spotter_output.airline_country) AS airline_country_count
		 			FROM spotter_output
					WHERE spotter_output.airline_country <> '' AND ((spotter_output.departure_airport_country = :country) OR (spotter_output.arrival_airport_country = :country)) OR spotter_output.airline_country = :country 
                    GROUP BY spotter_output.airline_country
					ORDER BY airline_country_count DESC
					LIMIT 0,10";
      
		
		$sth = $this->db->prepare($query);
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
	public function countAllAirlineCountries()
	{
		$query  = "SELECT DISTINCT spotter_output.airline_country, COUNT(spotter_output.airline_country) AS airline_country_count
		 			FROM spotter_output
					WHERE spotter_output.airline_country <> '' AND spotter_output.airline_country <> 'NA' 
                    GROUP BY spotter_output.airline_country
					ORDER BY airline_country_count DESC
					LIMIT 0,10";
      
		
		$sth = $this->db->prepare($query);
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
	public function countAllAircraftTypes()
	{
/*
		$query  = "SELECT DISTINCT spotter_output.aircraft_icao, COUNT(spotter_output.aircraft_icao) AS aircraft_icao_count, spotter_output.aircraft_name  
                    FROM spotter_output
                    WHERE spotter_output.aircraft_name  <> '' 
                    GROUP BY spotter_output.aircraft_name 
					ORDER BY aircraft_icao_count DESC
					LIMIT 0,10";
*/
		$query  = "SELECT DISTINCT spotter_output.aircraft_icao, COUNT(spotter_output.aircraft_icao) AS aircraft_icao_count, spotter_output.aircraft_name  
                    FROM spotter_output
                    WHERE spotter_output.aircraft_name  <> '' AND spotter_output.aircraft_icao  <> '' 
                    GROUP BY spotter_output.aircraft_name,spotter_output.aircraft_icao 
					ORDER BY aircraft_icao_count DESC
					LIMIT 10 OFFSET 0";
      
		
		$sth = $this->db->prepare($query);
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
	public function countAllAircraftRegistrationByAircraft($aircraft_icao)
	{
		$Image = new Image();
		$aircraft_icao = filter_var($aircraft_icao,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.aircraft_icao, COUNT(spotter_output.registration) AS registration_count, spotter_output.aircraft_name, spotter_output.registration, spotter_output.airline_name  
                    FROM spotter_output
                    WHERE spotter_output.registration <> '' AND spotter_output.aircraft_icao = :aircraft_icao  
                    GROUP BY spotter_output.registration 
					ORDER BY registration_count DESC";

		
		$sth = $this->db->prepare($query);
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
                  $image_array = $Image->getSpotterImage($row['registration']);
                  if (isset($image_array[0]['image_thumbnail'])) $temp_array['image_thumbnail'] = $image_array[0]['image_thumbnail'];
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
	public function countAllAircraftTypesByAirline($airline_icao)
	{
		$airline_icao = filter_var($airline_icao,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.aircraft_icao, COUNT(spotter_output.aircraft_icao) AS aircraft_icao_count, spotter_output.aircraft_name  
                    FROM spotter_output
                    WHERE spotter_output.aircraft_icao <> '' AND spotter_output.airline_icao = :airline_icao 
                    GROUP BY spotter_output.aircraft_name 
					ORDER BY aircraft_icao_count DESC";

		
		$sth = $this->db->prepare($query);
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
	public function countAllAircraftRegistrationByAirline($airline_icao)
	{
		$Image = new Image();
		$airline_icao = filter_var($airline_icao,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.aircraft_icao, COUNT(spotter_output.registration) AS registration_count, spotter_output.aircraft_name, spotter_output.registration, spotter_output.airline_name   
                    FROM spotter_output
                    WHERE spotter_output.registration <> '' AND spotter_output.airline_icao = :airline_icao 
                    GROUP BY spotter_output.registration 
					ORDER BY registration_count DESC";

		
		$sth = $this->db->prepare($query);
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
                  $image_array = $Image->getSpotterImage($row['registration']);
                  if (isset($image_array[0]['image_thumbnail'])) $temp_array['image_thumbnail'] = $image_array[0]['image_thumbnail'];
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
	public function countAllAircraftManufacturerByAirline($airline_icao)
	{
		$airline_icao = filter_var($airline_icao,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.aircraft_manufacturer, COUNT(spotter_output.aircraft_manufacturer) AS aircraft_manufacturer_count  
                    FROM spotter_output
                    WHERE spotter_output.aircraft_manufacturer <> '' AND spotter_output.airline_icao = :airline_icao 
                    GROUP BY spotter_output.aircraft_manufacturer 
					ORDER BY aircraft_manufacturer_count DESC";

		
		$sth = $this->db->prepare($query);
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
	public function countAllAircraftTypesByAirport($airport_icao)
	{
		$airport_icao = filter_var($airport_icao,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.aircraft_icao, COUNT(spotter_output.aircraft_icao) AS aircraft_icao_count, spotter_output.aircraft_name  
                    FROM spotter_output
                    WHERE spotter_output.aircraft_icao <> '' AND (spotter_output.departure_airport_icao = :airport_icao OR spotter_output.arrival_airport_icao = :airport_icao) 
                    GROUP BY spotter_output.aircraft_name 
					ORDER BY aircraft_icao_count DESC";
 
		
		$sth = $this->db->prepare($query);
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
	public function countAllAircraftRegistrationByAirport($airport_icao)
	{
		$Image = new Image();
		$airport_icao = filter_var($airport_icao,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.aircraft_icao, COUNT(spotter_output.registration) AS registration_count, spotter_output.aircraft_name, spotter_output.registration, spotter_output.airline_name  
                    FROM spotter_output
                    WHERE spotter_output.registration <> '' AND (spotter_output.departure_airport_icao = :airport_icao OR spotter_output.arrival_airport_icao = :airport_icao)   
                    GROUP BY spotter_output.registration 
					ORDER BY registration_count DESC";

		
		$sth = $this->db->prepare($query);
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
                  $image_array = $Image->getSpotterImage($row['registration']);
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
	public function countAllAircraftManufacturerByAirport($airport_icao)
	{
		$airport_icao = filter_var($airport_icao,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.aircraft_manufacturer, COUNT(spotter_output.aircraft_manufacturer) AS aircraft_manufacturer_count  
                    FROM spotter_output
                    WHERE spotter_output.aircraft_manufacturer <> '' AND (spotter_output.departure_airport_icao = :airport_icao OR spotter_output.arrival_airport_icao = :airport_icao)  
                    GROUP BY spotter_output.aircraft_manufacturer 
					ORDER BY aircraft_manufacturer_count DESC";

		
		$sth = $this->db->prepare($query);
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
	public function countAllAircraftTypesByManufacturer($aircraft_manufacturer)
	{
		$aircraft_manufacturer = filter_var($aircraft_manufacturer,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.aircraft_icao, COUNT(spotter_output.aircraft_icao) AS aircraft_icao_count, spotter_output.aircraft_name  
                    FROM spotter_output
                    WHERE spotter_output.aircraft_manufacturer = :aircraft_manufacturer
                    GROUP BY spotter_output.aircraft_name 
					ORDER BY aircraft_icao_count DESC";
 
		
		$sth = $this->db->prepare($query);
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
	public function countAllAircraftRegistrationByManufacturer($aircraft_manufacturer)
	{
		$Image = new Image();
		$aircraft_manufacturer = filter_var($aircraft_manufacturer,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.aircraft_icao, COUNT(spotter_output.registration) AS registration_count, spotter_output.aircraft_name, spotter_output.registration, spotter_output.airline_name   
                    FROM spotter_output
                    WHERE spotter_output.registration <> '' AND spotter_output.aircraft_manufacturer = :aircraft_manufacturer   
                    GROUP BY spotter_output.registration 
					ORDER BY registration_count DESC";

		
		$sth = $this->db->prepare($query);
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
                  $image_array = $Image->getSpotterImage($row['registration']);
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
	public function countAllAircraftTypesByDate($date)
	{
		global $globalTimezone;
		$date = filter_var($date,FILTER_SANITIZE_STRING);
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime($date);
			$offset = $datetime->format('P');
		} else $offset = '+00:00';
		
		$query  = "SELECT DISTINCT spotter_output.aircraft_icao, COUNT(spotter_output.aircraft_icao) AS aircraft_icao_count, spotter_output.aircraft_name  
                    FROM spotter_output
                    WHERE DATE(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) = :date
                    GROUP BY spotter_output.aircraft_name 
					ORDER BY aircraft_icao_count DESC";
 
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':date' => $date, ':offset' => $offset));
      
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
	public function countAllAircraftRegistrationByDate($date)
	{
		global $globalTimezone;
		$Image = new Image();
		$date = filter_var($date,FILTER_SANITIZE_STRING);
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime($date);
			$offset = $datetime->format('P');
		} else $offset = '+00:00';


		$query  = "SELECT DISTINCT spotter_output.aircraft_icao, COUNT(spotter_output.registration) AS registration_count, spotter_output.aircraft_name, spotter_output.registration, spotter_output.airline_name    
                    FROM spotter_output
                    WHERE spotter_output.
                    registration <> '' AND DATE(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) = :date   
                    GROUP BY spotter_output.registration 
					ORDER BY registration_count DESC";

		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':date' => $date, ':offset' => $offset));
      
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
                  $image_array = $Image->getSpotterImage($row['registration']);
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
	public function countAllAircraftManufacturerByDate($date)
	{
		global $globalTimezone;
		$date = filter_var($date,FILTER_SANITIZE_STRING);
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime($date);
			$offset = $datetime->format('P');
		} else $offset = '+00:00';


		$query  = "SELECT DISTINCT spotter_output.aircraft_manufacturer, COUNT(spotter_output.aircraft_manufacturer) AS aircraft_manufacturer_count  
                    FROM spotter_output
                    WHERE spotter_output.aircraft_manufacturer <> '' AND DATE(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) = :date 
                    GROUP BY spotter_output.aircraft_manufacturer 
					ORDER BY aircraft_manufacturer_count DESC";

		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':date' => $date, ':offset' => $offset));
      
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
	public function countAllAircraftTypesByIdent($ident)
	{
		$ident = filter_var($ident,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.aircraft_icao, COUNT(spotter_output.aircraft_icao) AS aircraft_icao_count, spotter_output.aircraft_name  
                    FROM spotter_output
                    WHERE spotter_output.ident = :ident 
                    GROUP BY spotter_output.aircraft_name 
					ORDER BY aircraft_icao_count DESC";
 
		
		$sth = $this->db->prepare($query);
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
	public function countAllAircraftRegistrationByIdent($ident)
	{
		$Image = new Image();
		$ident = filter_var($ident,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.aircraft_icao, COUNT(spotter_output.registration) AS registration_count, spotter_output.aircraft_name, spotter_output.registration, spotter_output.airline_name  
                    FROM spotter_output
                    WHERE spotter_output.registration <> '' AND spotter_output.ident = :ident   
                    GROUP BY spotter_output.registration 
					ORDER BY registration_count DESC";

		
		$sth = $this->db->prepare($query);
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
				$image_array = $Image->getSpotterImage($row['registration']);
				if (isset($image_array[0]['image_thumbnail'])) $temp_array['image_thumbnail'] = $image_array[0]['image_thumbnail'];
				else $temp_array['image_thumbnail'] = '';
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
	public function countAllAircraftManufacturerByIdent($ident)
	{
		$ident = filter_var($ident,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.aircraft_manufacturer, COUNT(spotter_output.aircraft_manufacturer) AS aircraft_manufacturer_count  
                    FROM spotter_output
                    WHERE spotter_output.aircraft_manufacturer <> '' AND spotter_output.ident = :ident  
                    GROUP BY spotter_output.aircraft_manufacturer 
					ORDER BY aircraft_manufacturer_count DESC";

		
		$sth = $this->db->prepare($query);
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
	public function countAllAircraftTypesByRoute($departure_airport_icao, $arrival_airport_icao)
	{
		$departure_airport_icao = filter_var($departure_airport_icao,FILTER_SANITIZE_STRING);
		$arrival_airport_icao = filter_var($arrival_airport_icao,FILTER_SANITIZE_STRING);
		

		$query  = "SELECT DISTINCT spotter_output.aircraft_icao, COUNT(spotter_output.aircraft_icao) AS aircraft_icao_count, spotter_output.aircraft_name  
                    FROM spotter_output
                    WHERE (spotter_output.departure_airport_icao = :departure_airport_icao) AND (spotter_output.arrival_airport_icao = :arrival_airport_icao)
                    GROUP BY spotter_output.aircraft_name 
					ORDER BY aircraft_icao_count DESC";
 
		
		$sth = $this->db->prepare($query);
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
	public function countAllAircraftRegistrationByRoute($departure_airport_icao, $arrival_airport_icao)
	{
		$Image = new Image();
		$departure_airport_icao = filter_var($departure_airport_icao,FILTER_SANITIZE_STRING);
		$arrival_airport_icao = filter_var($arrival_airport_icao,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.aircraft_icao, COUNT(spotter_output.registration) AS registration_count, spotter_output.aircraft_name, spotter_output.registration, spotter_output.airline_name   
                    FROM spotter_output
                    WHERE spotter_output.registration <> '' AND (spotter_output.departure_airport_icao = :departure_airport_icao) AND (spotter_output.arrival_airport_icao = :arrival_airport_icao)   
                    GROUP BY spotter_output.registration 
					ORDER BY registration_count DESC";

		
		$sth = $this->db->prepare($query);
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
				$image_array = $Image->getSpotterImage($row['registration']);
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
	public function countAllAircraftManufacturerByRoute($departure_airport_icao, $arrival_airport_icao)
	{
		$departure_airport_icao = filter_var($departure_airport_icao,FILTER_SANITIZE_STRING);
		$arrival_airport_icao = filter_var($arrival_airport_icao,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.aircraft_manufacturer, COUNT(spotter_output.aircraft_manufacturer) AS aircraft_manufacturer_count  
                    FROM spotter_output
                    WHERE spotter_output.aircraft_manufacturer <> '' AND (spotter_output.departure_airport_icao = :departure_airport_icao) AND (spotter_output.arrival_airport_icao = :arrival_airport_icao) 
                    GROUP BY spotter_output.aircraft_manufacturer 
					ORDER BY aircraft_manufacturer_count DESC";

		
		$sth = $this->db->prepare($query);
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
	public function countAllAircraftTypesByCountry($country)
	{
		$country = filter_var($country,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.aircraft_icao, COUNT(spotter_output.aircraft_icao) AS aircraft_icao_count, spotter_output.aircraft_name  
                    FROM spotter_output
                    WHERE ((spotter_output.departure_airport_country = :country) OR (spotter_output.arrival_airport_country = :country)) OR spotter_output.airline_country = :country 
                    GROUP BY spotter_output.aircraft_name 
					ORDER BY aircraft_icao_count DESC";
 
		
		$sth = $this->db->prepare($query);
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
	public function countAllAircraftRegistrationByCountry($country)
	{
		$Image = new Image();
		$country = filter_var($country,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.aircraft_icao, COUNT(spotter_output.registration) AS registration_count, spotter_output.aircraft_name, spotter_output.registration, spotter_output.airline_name 
                    FROM spotter_output
                    WHERE spotter_output.registration <> '' AND (((spotter_output.departure_airport_country = :country) OR (spotter_output.arrival_airport_country = :country)) OR spotter_output.airline_country = :country)    
                    GROUP BY spotter_output.registration 
					ORDER BY registration_count DESC";

		
		$sth = $this->db->prepare($query);
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
				$image_array = $Image->getSpotterImage($row['registration']);
				if (isset($image_array[0]['image_thumbnail'])) $temp_array['image_thumbnail'] = $image_array[0]['image_thumbnail'];
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
	public function countAllAircraftManufacturerByCountry($country)
	{
		$country = filter_var($country,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.aircraft_manufacturer, COUNT(spotter_output.aircraft_manufacturer) AS aircraft_manufacturer_count  
                    FROM spotter_output
                    WHERE spotter_output.aircraft_manufacturer <> '' AND (((spotter_output.departure_airport_country = :country) OR (spotter_output.arrival_airport_country = :country)) OR spotter_output.airline_country = :country) 
                    GROUP BY spotter_output.aircraft_manufacturer 
					ORDER BY aircraft_manufacturer_count DESC";

		
		$sth = $this->db->prepare($query);
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
	public function countAllAircraftManufacturers()
	{
		$query  = "SELECT DISTINCT spotter_output.aircraft_manufacturer, COUNT(spotter_output.aircraft_manufacturer) AS aircraft_manufacturer_count  
                    FROM spotter_output 
                    WHERE spotter_output.aircraft_manufacturer <> '' AND spotter_output.aircraft_manufacturer <> 'Not Available' 
                    GROUP BY spotter_output.aircraft_manufacturer
					ORDER BY aircraft_manufacturer_count DESC
					LIMIT 10";
      
		
		$sth = $this->db->prepare($query);
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
	public function countAllAircraftRegistrations()
	{
		$Image = new Image();
		$query  = "SELECT DISTINCT spotter_output.registration, COUNT(spotter_output.registration) AS aircraft_registration_count, spotter_output.aircraft_icao,  spotter_output.aircraft_name, spotter_output.airline_name    
                    FROM spotter_output 
                    WHERE spotter_output.registration <> '' AND spotter_output.registration <> 'NA' 
                    GROUP BY spotter_output.registration
					ORDER BY aircraft_registration_count DESC
					LIMIT 0,10";
      
		
		$sth = $this->db->prepare($query);
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
				$image_array = $Image->getSpotterImage($row['registration']);
				if (isset($image_array[0]['image_thumbnail'])) $temp_array['image_thumbnail'] = $image_array[0]['image_thumbnail'];
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
	public function countAllDepartureAirports()
	{
/*
		$query  = "SELECT DISTINCT spotter_output.departure_airport_icao, COUNT(spotter_output.departure_airport_icao) AS airport_departure_icao_count, spotter_output.departure_airport_name, spotter_output.departure_airport_city, spotter_output.departure_airport_country 
								FROM spotter_output
                    WHERE spotter_output.departure_airport_name <> '' 
                    GROUP BY spotter_output.departure_airport_icao
					ORDER BY airport_departure_icao_count DESC
					LIMIT 0,10";
*/
		$query  = "SELECT DISTINCT spotter_output.departure_airport_icao, COUNT(spotter_output.departure_airport_icao) AS airport_departure_icao_count, spotter_output.departure_airport_name, spotter_output.departure_airport_city, spotter_output.departure_airport_country 
								FROM spotter_output
                    WHERE spotter_output.departure_airport_name <> '' AND spotter_output.departure_airport_icao <> 'NA' 
                    GROUP BY spotter_output.departure_airport_icao, spotter_output.departure_airport_name, spotter_output.departure_airport_city, spotter_output.departure_airport_country
					ORDER BY airport_departure_icao_count DESC
					LIMIT 10";
      
		
		$sth = $this->db->prepare($query);
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
	public function countAllDepartureAirportsByAirline($airline_icao)
	{
		$airline_icao = filter_var($airline_icao,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.departure_airport_icao, COUNT(spotter_output.departure_airport_icao) AS airport_departure_icao_count, spotter_output.departure_airport_name, spotter_output.departure_airport_city, spotter_output.departure_airport_country 
								FROM spotter_output
                    WHERE spotter_output.departure_airport_name <> '' AND spotter_output.departure_airport_icao <> 'NA' AND spotter_output.airline_icao = :airline_icao 
                    GROUP BY spotter_output.departure_airport_icao
					ORDER BY airport_departure_icao_count DESC";
      
		
		$sth = $this->db->prepare($query);
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
	public function countAllDepartureAirportCountriesByAirline($airline_icao)
	{
		$airline_icao = filter_var($airline_icao,FILTER_SANITIZE_STRING);
					
		$query  = "SELECT DISTINCT spotter_output.departure_airport_country, COUNT(spotter_output.departure_airport_country) AS airport_departure_country_count 
								FROM spotter_output 
                    WHERE spotter_output.departure_airport_country <> '' AND spotter_output.airline_icao = :airline_icao 
                    GROUP BY spotter_output.departure_airport_country
					ORDER BY airport_departure_country_count DESC";
      
		
		$sth = $this->db->prepare($query);
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
	public function countAllDepartureAirportsByAircraft($aircraft_icao)
	{
		$aircraft_icao = filter_var($aircraft_icao,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.departure_airport_icao, COUNT(spotter_output.departure_airport_icao) AS airport_departure_icao_count, spotter_output.departure_airport_name, spotter_output.departure_airport_city, spotter_output.departure_airport_country 
								FROM spotter_output
                    WHERE spotter_output.departure_airport_name <> '' AND spotter_output.departure_airport_icao <> 'NA' AND spotter_output.aircraft_icao = :aircraft_icao 
                    GROUP BY spotter_output.departure_airport_icao
					ORDER BY airport_departure_icao_count DESC";
      
		
		$sth = $this->db->prepare($query);
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
	public function countAllDepartureAirportCountriesByAircraft($aircraft_icao)
	{
		$aircraft_icao = filter_var($aircraft_icao,FILTER_SANITIZE_STRING);
					
		$query  = "SELECT DISTINCT spotter_output.departure_airport_country, COUNT(spotter_output.departure_airport_country) AS airport_departure_country_count 
								FROM spotter_output 
                    WHERE spotter_output.departure_airport_country <> '' AND spotter_output.aircraft_icao = :aircraft_icao
                    GROUP BY spotter_output.departure_airport_country
					ORDER BY airport_departure_country_count DESC";
      
		
		$sth = $this->db->prepare($query);
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
	public function countAllDepartureAirportsByRegistration($registration)
	{
		$registration = filter_var($registration,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.departure_airport_icao, COUNT(spotter_output.departure_airport_icao) AS airport_departure_icao_count, spotter_output.departure_airport_name, spotter_output.departure_airport_city, spotter_output.departure_airport_country 
								FROM spotter_output
                    WHERE spotter_output.departure_airport_name <> '' AND spotter_output.departure_airport_icao <> 'NA' AND spotter_output.registration = :registration 
                    GROUP BY spotter_output.departure_airport_icao
					ORDER BY airport_departure_icao_count DESC";
      
		
		$sth = $this->db->prepare($query);
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
	public function countAllDepartureAirportCountriesByRegistration($registration)
	{
		$registration = filter_var($registration,FILTER_SANITIZE_STRING);
					
		$query  = "SELECT DISTINCT spotter_output.departure_airport_country, COUNT(spotter_output.departure_airport_country) AS airport_departure_country_count 
								FROM spotter_output 
                    WHERE spotter_output.departure_airport_country <> '' AND spotter_output.registration = :registration 
                    GROUP BY spotter_output.departure_airport_country
					ORDER BY airport_departure_country_count DESC";
      
		
		$sth = $this->db->prepare($query);
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
	public function countAllDepartureAirportsByAirport($airport_icao)
	{
		$airport_icao = filter_var($airport_icao,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.departure_airport_icao, COUNT(spotter_output.departure_airport_icao) AS airport_departure_icao_count, spotter_output.departure_airport_name, spotter_output.departure_airport_city, spotter_output.departure_airport_country 
								FROM spotter_output
                    WHERE spotter_output.departure_airport_name <> '' AND spotter_output.departure_airport_icao <> 'NA' AND spotter_output.arrival_airport_icao = :airport_icao 
                    GROUP BY spotter_output.departure_airport_icao
					ORDER BY airport_departure_icao_count DESC";
      
		
		$sth = $this->db->prepare($query);
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
	public function countAllDepartureAirportCountriesByAirport($airport_icao)
	{
		$airport_icao = filter_var($airport_icao,FILTER_SANITIZE_STRING);
					
		$query  = "SELECT DISTINCT spotter_output.departure_airport_country, COUNT(spotter_output.departure_airport_country) AS airport_departure_country_count 
								FROM spotter_output 
                    WHERE spotter_output.departure_airport_country <> '' AND spotter_output.arrival_airport_icao = :airport_icao 
                    GROUP BY spotter_output.departure_airport_country
					ORDER BY airport_departure_country_count DESC";
      
		
		$sth = $this->db->prepare($query);
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
	public function countAllDepartureAirportsByManufacturer($aircraft_manufacturer)
	{
		$aircraft_manufacturer = filter_var($aircraft_manufacturer,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.departure_airport_icao, COUNT(spotter_output.departure_airport_icao) AS airport_departure_icao_count, spotter_output.departure_airport_name, spotter_output.departure_airport_city, spotter_output.departure_airport_country 
								FROM spotter_output
                    WHERE spotter_output.departure_airport_name <> '' AND spotter_output.departure_airport_icao <> 'NA' AND spotter_output.aircraft_manufacturer = :aircraft_manufacturer 
                    GROUP BY spotter_output.departure_airport_icao
					ORDER BY airport_departure_icao_count DESC";
      
		
		$sth = $this->db->prepare($query);
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
	public function countAllDepartureAirportCountriesByManufacturer($aircraft_manufacturer)
	{
		$aircraft_manufacturer = filter_var($aircraft_manufacturer,FILTER_SANITIZE_STRING);
					
		$query  = "SELECT DISTINCT spotter_output.departure_airport_country, COUNT(spotter_output.departure_airport_country) AS airport_departure_country_count 
								FROM spotter_output 
                    WHERE spotter_output.departure_airport_country <> '' AND spotter_output.aircraft_manufacturer = :aircraft_manufacturer 
                    GROUP BY spotter_output.departure_airport_country
					ORDER BY airport_departure_country_count DESC";
      
		
		$sth = $this->db->prepare($query);
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
	public function countAllDepartureAirportsByDate($date)
	{
		global $globalTimezone;
		$date = filter_var($date,FILTER_SANITIZE_STRING);

		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime($date);
			$offset = $datetime->format('P');
		} else $offset = '+00:00';


		$query  = "SELECT DISTINCT spotter_output.departure_airport_icao, COUNT(spotter_output.departure_airport_icao) AS airport_departure_icao_count, spotter_output.departure_airport_name, spotter_output.departure_airport_city, spotter_output.departure_airport_country 
								FROM spotter_output
                    WHERE spotter_output.departure_airport_name <> '' AND spotter_output.departure_airport_icao <> 'NA' AND DATE(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) = :date
                    GROUP BY spotter_output.departure_airport_icao
					ORDER BY airport_departure_icao_count DESC";
      
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':date' => $date, ':offset' => $offset));
      
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
	public function countAllDepartureAirportCountriesByDate($date)
	{
		global $globalTimezone;
		$date = filter_var($date,FILTER_SANITIZE_STRING);
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime($date);
			$offset = $datetime->format('P');
		} else $offset = '+00:00';
					
		$query  = "SELECT DISTINCT spotter_output.departure_airport_country, COUNT(spotter_output.departure_airport_country) AS airport_departure_country_count 
								FROM spotter_output 
                    WHERE spotter_output.departure_airport_country <> '' AND DATE(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) = :date 
                    GROUP BY spotter_output.departure_airport_country
					ORDER BY airport_departure_country_count DESC";
      
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':date' => $date, ':offset' => $offset));
      
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
	public function countAllDepartureAirportsByIdent($ident)
	{
		$ident = filter_var($ident,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.departure_airport_icao, COUNT(spotter_output.departure_airport_icao) AS airport_departure_icao_count, spotter_output.departure_airport_name, spotter_output.departure_airport_city, spotter_output.departure_airport_country 
								FROM spotter_output
                    WHERE spotter_output.departure_airport_name <> '' AND spotter_output.departure_airport_icao <> 'NA' AND spotter_output.ident = :ident 
                    GROUP BY spotter_output.departure_airport_icao
					ORDER BY airport_departure_icao_count DESC";
      
		
		$sth = $this->db->prepare($query);
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
	public function countAllDepartureAirportCountriesByIdent($ident)
	{
		$ident = filter_var($ident,FILTER_SANITIZE_STRING);
					
		$query  = "SELECT DISTINCT spotter_output.departure_airport_country, COUNT(spotter_output.departure_airport_country) AS airport_departure_country_count 
								FROM spotter_output 
                    WHERE spotter_output.departure_airport_country <> '' AND spotter_output.ident = :ident 
                    GROUP BY spotter_output.departure_airport_country
					ORDER BY airport_departure_country_count DESC";
      
		
		$sth = $this->db->prepare($query);
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
	public function countAllDepartureAirportsByCountry($country)
	{
		$date = filter_var($date,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.departure_airport_icao, COUNT(spotter_output.departure_airport_icao) AS airport_departure_icao_count, spotter_output.departure_airport_name, spotter_output.departure_airport_city, spotter_output.departure_airport_country 
								FROM spotter_output
                    WHERE ((spotter_output.departure_airport_country = :country) OR (spotter_output.arrival_airport_country = :country)) OR spotter_output.airline_country = :country
                    GROUP BY spotter_output.departure_airport_icao
					ORDER BY airport_departure_icao_count DESC";
      
		
		$sth = $this->db->prepare($query);
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
	public function countAllDepartureAirportCountriesByCountry($country)
	{
		$country = filter_var($country,FILTER_SANITIZE_STRING);
					
		$query  = "SELECT DISTINCT spotter_output.departure_airport_country, COUNT(spotter_output.departure_airport_country) AS airport_departure_country_count 
								FROM spotter_output 
                    WHERE spotter_output.departure_airport_country <> '' AND ((spotter_output.departure_airport_country = :country) OR (spotter_output.arrival_airport_country = :country)) OR spotter_output.airline_country = :country 
                    GROUP BY spotter_output.departure_airport_country
					ORDER BY airport_departure_country_count DESC";
      
		
		$sth = $this->db->prepare($query);
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
	public function countAllArrivalAirports()
	{
/*
		$query  = "SELECT DISTINCT spotter_output.arrival_airport_icao, COUNT(spotter_output.arrival_airport_icao) AS airport_arrival_icao_count, spotter_output.arrival_airport_name, spotter_output.arrival_airport_city, spotter_output.arrival_airport_country 
								FROM spotter_output 
                    WHERE spotter_output.arrival_airport_name <> '' 
                    GROUP BY spotter_output.arrival_airport_icao
					ORDER BY airport_arrival_icao_count DESC
					LIMIT 0,10";
*/
		$query  = "SELECT DISTINCT spotter_output.arrival_airport_icao, COUNT(spotter_output.arrival_airport_icao) AS airport_arrival_icao_count, spotter_output.arrival_airport_name, spotter_output.arrival_airport_city, spotter_output.arrival_airport_country 
								FROM spotter_output 
                    WHERE spotter_output.arrival_airport_name <> '' AND spotter_output.arrival_airport_icao <> 'NA' 
                    GROUP BY spotter_output.arrival_airport_icao, spotter_output.arrival_airport_name, spotter_output.arrival_airport_city, spotter_output.arrival_airport_country
					ORDER BY airport_arrival_icao_count DESC
					LIMIT 10";
      
		
		$sth = $this->db->prepare($query);
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
	public function countAllArrivalAirportsByAirline($airline_icao)
	{
		$airline_icao = filter_var($airline_icao,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.arrival_airport_icao, COUNT(spotter_output.arrival_airport_icao) AS airport_arrival_icao_count, spotter_output.arrival_airport_name, spotter_output.arrival_airport_city, spotter_output.arrival_airport_country 
								FROM spotter_output 
                    WHERE spotter_output.arrival_airport_name <> '' AND spotter_output.arrival_airport_icao <> 'NA' AND spotter_output.airline_icao = :airline_icao 
                    GROUP BY spotter_output.arrival_airport_icao
					ORDER BY airport_arrival_icao_count DESC";
      
		
		$sth = $this->db->prepare($query);
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
	public function countAllArrivalAirportCountriesByAirline($airline_icao)
	{
		$airline_icao = filter_var($airline_icao,FILTER_SANITIZE_STRING);
					
		$query  = "SELECT DISTINCT spotter_output.arrival_airport_country, COUNT(spotter_output.arrival_airport_country) AS airport_arrival_country_count 
								FROM spotter_output 
                    WHERE spotter_output.arrival_airport_country <> '' AND spotter_output.airline_icao = :airline_icao 
                    GROUP BY spotter_output.arrival_airport_country
					ORDER BY airport_arrival_country_count DESC";
      
		
		$sth = $this->db->prepare($query);
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
	public function countAllArrivalAirportsByAircraft($aircraft_icao)
	{
		$aircraft_icao = filter_var($aircraft_icao,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.arrival_airport_icao, COUNT(spotter_output.arrival_airport_icao) AS airport_arrival_icao_count, spotter_output.arrival_airport_name, spotter_output.arrival_airport_city, spotter_output.arrival_airport_country 
								FROM spotter_output 
                    WHERE spotter_output.arrival_airport_name <> '' AND spotter_output.arrival_airport_icao <> 'NA' AND spotter_output.aircraft_icao = :aircraft_icao 
                    GROUP BY spotter_output.arrival_airport_icao
					ORDER BY airport_arrival_icao_count DESC";
      
		
		$sth = $this->db->prepare($query);
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
	public function countAllArrivalAirportCountriesByAircraft($aircraft_icao)
	{
		$aircraft_icao = filter_var($aircraft_icao,FILTER_SANITIZE_STRING);
					
		$query  = "SELECT DISTINCT spotter_output.arrival_airport_country, COUNT(spotter_output.arrival_airport_country) AS airport_arrival_country_count 
								FROM spotter_output 
                    WHERE spotter_output.arrival_airport_country <> '' AND spotter_output.aircraft_icao = :aircraft_icao
                    GROUP BY spotter_output.arrival_airport_country
					ORDER BY airport_arrival_country_count DESC";
      
		
		$sth = $this->db->prepare($query);
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
	public function countAllArrivalAirportsByRegistration($registration)
	{
		$registration = filter_var($registration,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.arrival_airport_icao, COUNT(spotter_output.arrival_airport_icao) AS airport_arrival_icao_count, spotter_output.arrival_airport_name, spotter_output.arrival_airport_city, spotter_output.arrival_airport_country 
								FROM spotter_output 
                    WHERE spotter_output.arrival_airport_name <> '' AND spotter_output.arrival_airport_icao <> 'NA' AND spotter_output.registration = :registration 
                    GROUP BY spotter_output.arrival_airport_icao
					ORDER BY airport_arrival_icao_count DESC";
      
		
		$sth = $this->db->prepare($query);
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
	public function countAllArrivalAirportCountriesByRegistration($registration)
	{
		$registration = filter_var($registration,FILTER_SANITIZE_STRING);
					
		$query  = "SELECT DISTINCT spotter_output.arrival_airport_country, COUNT(spotter_output.arrival_airport_country) AS airport_arrival_country_count 
								FROM spotter_output 
                    WHERE spotter_output.arrival_airport_country <> '' AND spotter_output.registration = :registration 
                    GROUP BY spotter_output.arrival_airport_country
					ORDER BY airport_arrival_country_count DESC";
      
		
		$sth = $this->db->prepare($query);
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
	public function countAllArrivalAirportsByAirport($airport_icao)
	{
		$airport_icao = filter_var($airport_icao,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.arrival_airport_icao, COUNT(spotter_output.arrival_airport_icao) AS airport_arrival_icao_count, spotter_output.arrival_airport_name, spotter_output.arrival_airport_city, spotter_output.arrival_airport_country 
								FROM spotter_output 
                    WHERE spotter_output.arrival_airport_name <> '' AND spotter_output.arrival_airport_icao <> 'NA' AND spotter_output.departure_airport_icao = :airport_icao 
                    GROUP BY spotter_output.arrival_airport_icao
					ORDER BY airport_arrival_icao_count DESC";
      
		
		$sth = $this->db->prepare($query);
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
	public function countAllArrivalAirportCountriesByAirport($airport_icao)
	{
		$airport_icao = filter_var($airport_icao,FILTER_SANITIZE_STRING);
					
		$query  = "SELECT DISTINCT spotter_output.arrival_airport_country, COUNT(spotter_output.arrival_airport_country) AS airport_arrival_country_count 
								FROM spotter_output 
                    WHERE spotter_output.arrival_airport_country <> '' AND spotter_output.departure_airport_icao = :airport_icao 
                    GROUP BY spotter_output.arrival_airport_country
					ORDER BY airport_arrival_country_count DESC";
      
		
		$sth = $this->db->prepare($query);
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
	public function countAllArrivalAirportsByManufacturer($aircraft_manufacturer)
	{
		$aircraft_manufacturer = filter_var($aircraft_manufacturer,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.arrival_airport_icao, COUNT(spotter_output.arrival_airport_icao) AS airport_arrival_icao_count, spotter_output.arrival_airport_name, spotter_output.arrival_airport_city, spotter_output.arrival_airport_country 
								FROM spotter_output 
                    WHERE spotter_output.arrival_airport_name <> '' AND spotter_output.arrival_airport_icao <> 'NA' AND spotter_output.aircraft_manufacturer = :aircraft_manufacturer 
                    GROUP BY spotter_output.arrival_airport_icao
					ORDER BY airport_arrival_icao_count DESC";
      
		
		$sth = $this->db->prepare($query);
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
	public function countAllArrivalAirportCountriesByManufacturer($aircraft_manufacturer)
	{
		$aircraft_manufacturer = filter_var($aircraft_manufacturer,FILTER_SANITIZE_STRING);
					
		$query  = "SELECT DISTINCT spotter_output.arrival_airport_country, COUNT(spotter_output.arrival_airport_country) AS airport_arrival_country_count 
								FROM spotter_output 
                    WHERE spotter_output.arrival_airport_country <> '' AND spotter_output.aircraft_manufacturer = :aircraft_manufacturer 
                    GROUP BY spotter_output.arrival_airport_country
					ORDER BY airport_arrival_country_count DESC";
      
		
		$sth = $this->db->prepare($query);
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
	public function countAllArrivalAirportsByDate($date)
	{
		global $globalTimezone;
		$date = filter_var($date,FILTER_SANITIZE_STRING);
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime($date);
			$offset = $datetime->format('P');
		} else $offset = '+00:00';

		$query  = "SELECT DISTINCT spotter_output.arrival_airport_icao, COUNT(spotter_output.arrival_airport_icao) AS airport_arrival_icao_count, spotter_output.arrival_airport_name, spotter_output.arrival_airport_city, spotter_output.arrival_airport_country 
								FROM spotter_output 
                    WHERE spotter_output.arrival_airport_name <> '' AND spotter_output.arrival_airport_icao <> 'NA' AND DATE(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) = :date  
                    GROUP BY spotter_output.arrival_airport_icao
					ORDER BY airport_arrival_icao_count DESC";
      
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':date' => $date, ':offset' => $offset));
      
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
	public function countAllArrivalAirportCountriesByDate($date)
	{
		global $globalTimezone;
		$date = filter_var($date,FILTER_SANITIZE_STRING);
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime($date);
			$offset = $datetime->format('P');
		} else $offset = '+00:00';

		$query  = "SELECT DISTINCT spotter_output.arrival_airport_country, COUNT(spotter_output.arrival_airport_country) AS airport_arrival_country_count 
								FROM spotter_output 
                    WHERE spotter_output.arrival_airport_country <> '' AND DATE(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) = :date 
                    GROUP BY spotter_output.arrival_airport_country
					ORDER BY airport_arrival_country_count DESC";
      
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':date' => $date, ':offset' => $offset));
      
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
	public function countAllArrivalAirportsByIdent($ident)
	{
		$ident = filter_var($ident,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.arrival_airport_icao, COUNT(spotter_output.arrival_airport_icao) AS airport_arrival_icao_count, spotter_output.arrival_airport_name, spotter_output.arrival_airport_city, spotter_output.arrival_airport_country 
								FROM spotter_output 
                    WHERE spotter_output.arrival_airport_name <> '' AND spotter_output.arrival_airport_icao <> 'NA' AND spotter_output.ident = :ident  
                    GROUP BY spotter_output.arrival_airport_icao
					ORDER BY airport_arrival_icao_count DESC";
      
		
		$sth = $this->db->prepare($query);
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
	public function countAllArrivalAirportCountriesByIdent($ident)
	{
		$ident = filter_var($ident,FILTER_SANITIZE_STRING);
					
		$query  = "SELECT DISTINCT spotter_output.arrival_airport_country, COUNT(spotter_output.arrival_airport_country) AS airport_arrival_country_count 
								FROM spotter_output 
                    WHERE spotter_output.arrival_airport_country <> '' AND spotter_output.ident = :ident 
                    GROUP BY spotter_output.arrival_airport_country
					ORDER BY airport_arrival_country_count DESC";
      
		
		$sth = $this->db->prepare($query);
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
	public function countAllArrivalAirportsByCountry($country)
	{
		$country = filter_var($country,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.arrival_airport_icao, COUNT(spotter_output.arrival_airport_icao) AS airport_arrival_icao_count, spotter_output.arrival_airport_name, spotter_output.arrival_airport_city, spotter_output.arrival_airport_country 
								FROM spotter_output 
                    WHERE ((spotter_output.departure_airport_country = :country) OR (spotter_output.arrival_airport_country = :country)) OR spotter_output.airline_country = :country  
                    GROUP BY spotter_output.arrival_airport_icao
					ORDER BY airport_arrival_icao_count DESC";
      
		
		$sth = $this->db->prepare($query);
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
	public function countAllArrivalAirportCountriesByCountry($country)
	{
		$country = filter_var($country,FILTER_SANITIZE_STRING);
					
		$query  = "SELECT DISTINCT spotter_output.arrival_airport_country, COUNT(spotter_output.arrival_airport_country) AS airport_arrival_country_count 
								FROM spotter_output 
                    WHERE spotter_output.arrival_airport_country <> '' AND ((spotter_output.departure_airport_country = :country) OR (spotter_output.arrival_airport_country = :country)) OR spotter_output.airline_country = :country 
                    GROUP BY spotter_output.arrival_airport_country
					ORDER BY airport_arrival_country_count DESC";
      
		
		$sth = $this->db->prepare($query);
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
	public function countAllDepartureCountries()
	{
		$query  = "SELECT DISTINCT spotter_output.departure_airport_country, COUNT(spotter_output.departure_airport_country) AS airport_departure_country_count 
								FROM spotter_output 
                    WHERE spotter_output.departure_airport_country <> '' AND spotter_output.departure_airport_icao <> 'NA' 
                    GROUP BY spotter_output.departure_airport_country
					ORDER BY airport_departure_country_count DESC
					LIMIT 0,10";
      
		
		$sth = $this->db->prepare($query);
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
	public function countAllArrivalCountries()
	{
		$query  = "SELECT DISTINCT spotter_output.arrival_airport_country, COUNT(spotter_output.arrival_airport_country) AS airport_arrival_country_count 
								FROM spotter_output 
                    WHERE spotter_output.arrival_airport_country <> '' AND spotter_output.arrival_airport_icao <> 'NA' 
                    GROUP BY spotter_output.arrival_airport_country
					ORDER BY airport_arrival_country_count DESC
					LIMIT 0,10";
      
		
		$sth = $this->db->prepare($query);
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
	public function countAllRoutes()
	{
		
		$query  = "SELECT DISTINCT concat(spotter_output.departure_airport_icao, ' - ',  spotter_output.arrival_airport_icao) AS route, count(concat(spotter_output.departure_airport_icao, ' - ', spotter_output.arrival_airport_icao)) AS route_count, spotter_output.departure_airport_icao, spotter_output.departure_airport_name AS airport_departure_name, spotter_output.departure_airport_city AS airport_departure_city, spotter_output.departure_airport_country AS airport_departure_country, spotter_output.arrival_airport_icao, spotter_output.arrival_airport_name AS airport_arrival_name, spotter_output.arrival_airport_city AS airport_arrival_city, spotter_output.arrival_airport_country AS airport_arrival_country
								FROM spotter_output
                    WHERE spotter_output.ident <> '' AND spotter_output.departure_airport_icao <> 'NA' AND spotter_output.arrival_airport_icao <> 'NA'
                    GROUP BY route
                    ORDER BY route_count DESC
					LIMIT 0,10";
      
		
		$sth = $this->db->prepare($query);
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
	public function countAllRoutesByAircraft($aircraft_icao)
	{
		$aircraft_icao = filter_var($aircraft_icao,FILTER_SANITIZE_STRING);
		
		$query  = "SELECT DISTINCT concat(spotter_output.departure_airport_icao, ' - ',  spotter_output.arrival_airport_icao) AS route, count(concat(spotter_output.departure_airport_icao, ' - ', spotter_output.arrival_airport_icao)) AS route_count, spotter_output.departure_airport_icao, spotter_output.departure_airport_name AS airport_departure_name, spotter_output.departure_airport_city AS airport_departure_city, spotter_output.departure_airport_country AS airport_departure_country, spotter_output.arrival_airport_icao, spotter_output.arrival_airport_name AS airport_arrival_name, spotter_output.arrival_airport_city AS airport_arrival_city, spotter_output.arrival_airport_country AS airport_arrival_country
								FROM spotter_output
                    WHERE spotter_output.ident <> '' AND spotter_output.aircraft_icao = :aircraft_icao 
                    GROUP BY route
                    ORDER BY route_count DESC";
      
		
		$sth = $this->db->prepare($query);
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
	public function countAllRoutesByRegistration($registration)
	{
		$registration = filter_var($registration, FILTER_SANITIZE_STRING);
		
		$query  = "SELECT DISTINCT concat(spotter_output.departure_airport_icao, ' - ',  spotter_output.arrival_airport_icao) AS route, count(concat(spotter_output.departure_airport_icao, ' - ', spotter_output.arrival_airport_icao)) AS route_count, spotter_output.departure_airport_icao, spotter_output.departure_airport_name AS airport_departure_name, spotter_output.departure_airport_city AS airport_departure_city, spotter_output.departure_airport_country AS airport_departure_country, spotter_output.arrival_airport_icao, spotter_output.arrival_airport_name AS airport_arrival_name, spotter_output.arrival_airport_city AS airport_arrival_city, spotter_output.arrival_airport_country AS airport_arrival_country
								FROM spotter_output
                    WHERE spotter_output.ident <> '' AND spotter_output.registration = :registration 
                    GROUP BY route
                    ORDER BY route_count DESC";
      
		
		$sth = $this->db->prepare($query);
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
	public function countAllRoutesByAirline($airline_icao)
	{
		$airline_icao = filter_var($airline_icao,FILTER_SANITIZE_STRING);
		
		$query  = "SELECT DISTINCT concat(spotter_output.departure_airport_icao, ' - ',  spotter_output.arrival_airport_icao) AS route, count(concat(spotter_output.departure_airport_icao, ' - ', spotter_output.arrival_airport_icao)) AS route_count, spotter_output.departure_airport_icao, spotter_output.departure_airport_name AS airport_departure_name, spotter_output.departure_airport_city AS airport_departure_city, spotter_output.departure_airport_country AS airport_departure_country, spotter_output.arrival_airport_icao, spotter_output.arrival_airport_name AS airport_arrival_name, spotter_output.arrival_airport_city AS airport_arrival_city, spotter_output.arrival_airport_country AS airport_arrival_country
								FROM spotter_output
                    WHERE spotter_output.ident <> '' AND spotter_output.airline_icao = :airline_icao 
                    GROUP BY route
                    ORDER BY route_count DESC";
      
		
		$sth = $this->db->prepare($query);
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
	public function countAllRoutesByAirport($airport_icao)
	{
		$airport_icao = filter_var($airport_icao,FILTER_SANITIZE_STRING);
		
		$query  = "SELECT DISTINCT concat(spotter_output.departure_airport_icao, ' - ',  spotter_output.arrival_airport_icao) AS route, count(concat(spotter_output.departure_airport_icao, ' - ', spotter_output.arrival_airport_icao)) AS route_count, spotter_output.departure_airport_icao, spotter_output.departure_airport_name AS airport_departure_name, spotter_output.departure_airport_city AS airport_departure_city, spotter_output.departure_airport_country AS airport_departure_country, spotter_output.arrival_airport_icao, spotter_output.arrival_airport_name AS airport_arrival_name, spotter_output.arrival_airport_city AS airport_arrival_city, spotter_output.arrival_airport_country AS airport_arrival_country
								FROM spotter_output
                    WHERE spotter_output.ident <> '' AND (spotter_output.departure_airport_icao = :airport_icao OR spotter_output.arrival_airport_icao = :airport_icao)
                    GROUP BY route
                    ORDER BY route_count DESC";
      
		
		$sth = $this->db->prepare($query);
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
	public function countAllRoutesByCountry($country)
	{
		$country = filter_var($country,FILTER_SANITIZE_STRING);
		
		$query  = "SELECT DISTINCT concat(spotter_output.departure_airport_icao, ' - ',  spotter_output.arrival_airport_icao) AS route, count(concat(spotter_output.departure_airport_icao, ' - ', spotter_output.arrival_airport_icao)) AS route_count, spotter_output.departure_airport_icao, spotter_output.departure_airport_name AS airport_departure_name, spotter_output.departure_airport_city AS airport_departure_city, spotter_output.departure_airport_country AS airport_departure_country, spotter_output.arrival_airport_icao, spotter_output.arrival_airport_name AS airport_arrival_name, spotter_output.arrival_airport_city AS airport_arrival_city, spotter_output.arrival_airport_country AS airport_arrival_country
								FROM spotter_output
                    WHERE spotter_output.ident <> '' AND ((spotter_output.departure_airport_country = :country) OR (spotter_output.arrival_airport_country = :country)) OR spotter_output.airline_country = :country 
                    GROUP BY route
                    ORDER BY route_count DESC";
      
		
		$sth = $this->db->prepare($query);
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
	public function countAllRoutesByDate($date)
	{
		global $globalTimezone;
		$date = filter_var($date,FILTER_SANITIZE_STRING);
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime($date);
			$offset = $datetime->format('P');
		} else $offset = '+00:00';
		
		$query  = "SELECT DISTINCT concat(spotter_output.departure_airport_icao, ' - ',  spotter_output.arrival_airport_icao) AS route, count(concat(spotter_output.departure_airport_icao, ' - ', spotter_output.arrival_airport_icao)) AS route_count, spotter_output.departure_airport_icao, spotter_output.departure_airport_name AS airport_departure_name, spotter_output.departure_airport_city AS airport_departure_city, spotter_output.departure_airport_country AS airport_departure_country, spotter_output.arrival_airport_icao, spotter_output.arrival_airport_name AS airport_arrival_name, spotter_output.arrival_airport_city AS airport_arrival_city, spotter_output.arrival_airport_country AS airport_arrival_country
								FROM spotter_output
                    WHERE spotter_output.ident <> '' AND DATE(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) = :date  
                    GROUP BY route
                    ORDER BY route_count DESC";
      
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':date' => $date, ':offset' => $offset));
      
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
	public function countAllRoutesByIdent($ident)
	{
		$ident = filter_var($ident,FILTER_SANITIZE_STRING);
		
		$query  = "SELECT DISTINCT concat(spotter_output.departure_airport_icao, ' - ',  spotter_output.arrival_airport_icao) AS route, count(concat(spotter_output.departure_airport_icao, ' - ', spotter_output.arrival_airport_icao)) AS route_count, spotter_output.departure_airport_icao, spotter_output.departure_airport_name AS airport_departure_name, spotter_output.departure_airport_city AS airport_departure_city, spotter_output.departure_airport_country AS airport_departure_country, spotter_output.arrival_airport_icao, spotter_output.arrival_airport_name AS airport_arrival_name, spotter_output.arrival_airport_city AS airport_arrival_city, spotter_output.arrival_airport_country AS airport_arrival_country
								FROM spotter_output
                    WHERE spotter_output.ident <> '' AND spotter_output.ident = :ident   
                    GROUP BY route
                    ORDER BY route_count DESC";
      
		
		$sth = $this->db->prepare($query);
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
	public function countAllRoutesByManufacturer($aircraft_manufacturer)
	{
		$aircraft_manufacturer = filter_var($aircraft_manufactuer,FILTER_SANITIZE_STRING);
		
		$query  = "SELECT DISTINCT concat(spotter_output.departure_airport_icao, ' - ',  spotter_output.arrival_airport_icao) AS route, count(concat(spotter_output.departure_airport_icao, ' - ', spotter_output.arrival_airport_icao)) AS route_count, spotter_output.departure_airport_icao, spotter_output.departure_airport_name AS airport_departure_name, spotter_output.departure_airport_city AS airport_departure_city, spotter_output.departure_airport_country AS airport_departure_country, spotter_output.arrival_airport_icao, spotter_output.arrival_airport_name AS airport_arrival_name, spotter_output.arrival_airport_city AS airport_arrival_city, spotter_output.arrival_airport_country AS airport_arrival_country
								FROM spotter_output
                    WHERE spotter_output.ident <> '' AND spotter_output.aircraft_manufacturer = :aircraft_manufacturer   
                    GROUP BY route
                    ORDER BY route_count DESC";
      
		
		$sth = $this->db->prepare($query);
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
	public function countAllRoutesWithWaypoints()
	{
		$query  = "SELECT DISTINCT spotter_output.waypoints AS route, count(spotter_output.waypoints) AS route_count, spotter_output.spotter_id, spotter_output.departure_airport_icao, spotter_output.departure_airport_name AS airport_departure_name, spotter_output.departure_airport_city AS airport_departure_city, spotter_output.departure_airport_country AS airport_departure_country, spotter_output.arrival_airport_icao, spotter_output.arrival_airport_name AS airport_arrival_name, spotter_output.arrival_airport_city AS airport_arrival_city, spotter_output.arrival_airport_country AS airport_arrival_country
								FROM spotter_output
                    WHERE spotter_output.ident <> '' AND spotter_output.waypoints <> '' 
                    GROUP BY route
                    ORDER BY route_count DESC
					LIMIT 0,10";
      
		
		$sth = $this->db->prepare($query);
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
	public function countAllCallsigns()
	{
		$query  = "SELECT DISTINCT spotter_output.ident, COUNT(spotter_output.ident) AS callsign_icao_count, spotter_output.airline_name, spotter_output.airline_icao  
                    FROM spotter_output
                    WHERE spotter_output.airline_name <> '' 
                    GROUP BY spotter_output.ident
					ORDER BY callsign_icao_count DESC
					LIMIT 0,10";
      
		
		$sth = $this->db->prepare($query);
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
	public function countAllDates()
	{
		global $globalTimezone;
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime();
			$offset = $datetime->format('P');
		} else $offset = '+00:00';

		$query  = "SELECT DATE(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) AS date_name, count(*) as date_count
								FROM spotter_output 
								GROUP BY date_name 
								ORDER BY date_count DESC
								LIMIT 0,10";
      
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':offset' => $offset));
      
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
	public function countAllDatesLast7Days()
	{
		global $globalTimezone, $globalDBdriver;
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime();
			$offset = $datetime->format('P');
		} else $offset = '+00:00';
		
		if ($globalDBdriver == 'mysql') {
			$query  = "SELECT DATE(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) AS date_name, count(*) as date_count
								FROM spotter_output 
								WHERE spotter_output.date >= DATE_SUB(UTC_TIMESTAMP(),INTERVAL 7 DAY)
								GROUP BY date_name 
								ORDER BY spotter_output.date ASC";
			$query_data = array(':offset' => $offset);
		} elseif ($globalDBdriver == 'pgsql') {
			// FIXME : not working
			$query  = "SELECT spotter_output.date AT TIME ZONE :timezone AS date_name, count(*) as date_count
								FROM spotter_output 
								WHERE spotter_output.date >= NOW() AT TIME ZONE :timezone - '7 DAYS'->INTERVAL
								GROUP BY date_name 
								ORDER BY date_name ASC";
			$query_data = array(':timezone' => $globalTimezone);
    		}
		
		$sth = $this->db->prepare($query);
		$sth->execute($query_data);
      
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
	public function countAllHours($orderby)
	{
		global $globalTimezone, $globalDBdriver;
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime();
			$offset = $datetime->format('P');
		} else $offset = '+00:00';

		if ($orderby == "hour")
		{
			$orderby_sql = "ORDER BY hour_name ASC";
		}
		if ($orderby == "count")
		{
			$orderby_sql = "ORDER BY hour_count DESC";
		}
		
		if ($globalDBdriver == 'mysql') {
			$query  = "SELECT HOUR(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) AS hour_name, count(*) as hour_count
								FROM spotter_output 
								GROUP BY hour_name 
								".$orderby_sql."
								LIMIT 0,100";

/*		$query  = "SELECT HOUR(spotter_output.date) AS hour_name, count(*) as hour_count
								FROM spotter_output 
								GROUP BY hour_name 
								".$orderby_sql."
								LIMIT 0,100";
  */    
		$query_data = array(':offset' => $offset);
		} elseif ($globalDBdriver == 'pgsql') {
			$query  = "SELECT EXTRACT (HOUR FROM spotter_output.date AT TIME ZONE :timezone) AS hour_name, count(*) as hour_count
								FROM spotter_output 
								GROUP BY hour_name 
								".$orderby_sql."
								LIMIT 100";
		$query_data = array(':timezone' => $globalTimezone);
		}
		
		$sth = $this->db->prepare($query);
		$sth->execute($query_data);
      
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
	public function countAllHoursByAirline($airline_icao)
	{
		global $globalTimezone;
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime();
			$offset = $datetime->format('P');
		} else $offset = '+00:00';

		$airline_icao = filter_var($airline_icao,FILTER_SANITIZE_STRING);

		$query  = "SELECT HOUR(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) AS hour_name, count(*) as hour_count
								FROM spotter_output 
								WHERE spotter_output.airline_icao = :airline_icao
								GROUP BY hour_name 
								ORDER BY hour_name ASC";
      
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':airline_icao' => $airline_icao,':offset' => $offset));
      
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
	public function countAllHoursByAircraft($aircraft_icao)
	{
		global $globalTimezone;
		$aircraft_icao = filter_var($aircraft_icao,FILTER_SANITIZE_STRING);
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime();
			$offset = $datetime->format('P');
		} else $offset = '+00:00';

		$query  = "SELECT HOUR(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) AS hour_name, count(*) as hour_count
								FROM spotter_output 
								WHERE spotter_output.aircraft_icao = :aircraft_icao
								GROUP BY hour_name 
								ORDER BY hour_name ASC";
      
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':aircraft_icao' => $aircraft_icao,':offset' => $offset));
      
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
	public function countAllHoursByRegistration($registration)
	{
		global $globalTimezone;
		$registration = filter_var($registration,FILTER_SANITIZE_STRING);
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime();
			$offset = $datetime->format('P');
		} else $offset = '+00:00';

		$query  = "SELECT HOUR(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) AS hour_name, count(*) as hour_count
								FROM spotter_output 
								WHERE spotter_output.registration = :registration
								GROUP BY hour_name 
								ORDER BY hour_name ASC";
      
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':registration' => $registration,':offset' => $offset));
      
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
	public function countAllHoursByAirport($airport_icao)
	{
		global $globalTimezone;
		$airport_icao = filter_var($airport_icao,FILTER_SANITIZE_STRING);
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime();
			$offset = $datetime->format('P');
		} else $offset = '+00:00';

		$query  = "SELECT HOUR(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) AS hour_name, count(*) as hour_count
								FROM spotter_output 
								WHERE (spotter_output.departure_airport_icao = :airport_icao OR spotter_output.arrival_airport_icao = :airport_icao)
								GROUP BY hour_name 
								ORDER BY hour_name ASC";
      
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':airport_icao' => $airport_icao,':offset' => $offset));
      
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
	public function countAllHoursByManufacturer($aircraft_manufacturer)
	{
		global $globalTimezone;
		$aircraft_manufacturer = filter_var($aircraft_manufacturer,FILTER_SANITIZE_STRING);
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime();
			$offset = $datetime->format('P');
		} else $offset = '+00:00';

		$query  = "SELECT HOUR(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) AS hour_name, count(*) as hour_count
								FROM spotter_output 
								WHERE spotter_output.aircraft_manufacturer = :aircraft_manufacturer
								GROUP BY hour_name 
								ORDER BY hour_name ASC";
      
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':aircraft_manufacturer' => $aircraft_manufacturer,':offset' => $offset));
      
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
	public function countAllHoursByDate($date)
	{
		global $globalTimezone;
		$date = filter_var($date,FILTER_SANITIZE_STRING);
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime($date);
			$offset = $datetime->format('P');
		} else $offset = '+00:00';

		$query  = "SELECT HOUR(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) AS hour_name, count(*) as hour_count
								FROM spotter_output 
								WHERE DATE(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) = :date
								GROUP BY hour_name 
								ORDER BY hour_name ASC";
      
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':date' => $date, ':offset' => $offset));
      
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
	public function countAllHoursByIdent($ident)
	{
		global $globalTimezone;
		$ident = filter_var($ident,FILTER_SANITIZE_STRING);
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime();
			$offset = $datetime->format('P');
		} else $offset = '+00:00';

		$query  = "SELECT HOUR(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) AS hour_name, count(*) as hour_count
								FROM spotter_output 
								WHERE spotter_output.ident = :ident 
								GROUP BY hour_name 
								ORDER BY hour_name ASC";
      
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':ident' => $ident,':offset' => $offset));
      
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
	public function countAllHoursByRoute($departure_airport_icao, $arrival_airport_icao)
	{
		global $globalTimezone;
		$departure_airport_icao = filter_var($departure_airport_icao,FILTER_SANITIZE_STRING);
		$arrival_airport_icao = filter_var($arrival_airport_icao,FILTER_SANITIZE_STRING);
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime();
			$offset = $datetime->format('P');
		} else $offset = '+00:00';

		$query  = "SELECT HOUR(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) AS hour_name, count(*) as hour_count
								FROM spotter_output 
								WHERE (spotter_output.departure_airport_icao = :departure_airport_icao) AND (spotter_output.arrival_airport_icao = :arrival_airport_icao)
								GROUP BY hour_name 
								ORDER BY hour_name ASC";
      
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':departure_airport_icao' => $departure_airport_icao,':arrival_airport_icao' => $arrival_airport_icao,':offset' => $offset));
      
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
	public function countAllHoursByCountry($country)
	{
		global $globalTimezone;
		$country = filter_var($country,FILTER_SANITIZE_STRING);
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime();
			$offset = $datetime->format('P');
		} else $offset = '+00:00';

		$query  = "SELECT HOUR(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) AS hour_name, count(*) as hour_count
								FROM spotter_output 
								WHERE ((spotter_output.departure_airport_country = :country) OR (spotter_output.arrival_airport_country = :country)) OR spotter_output.airline_country = :country
								GROUP BY hour_name 
								ORDER BY hour_name ASC";
      
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':country' => $country,':offset' => $offset));
      
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
	public function countOverallAircrafts()
	{
		$query  = "SELECT COUNT(DISTINCT spotter_output.aircraft_icao) AS aircraft_count  
                    FROM spotter_output
                    WHERE spotter_output.ident <> ''";
      
		
		$sth = $this->db->prepare($query);
		$sth->execute();
		return $sth->fetchColumn();
	}
	
	
	/**
	* Counts all flights that have flown over
	*
	* @return Integer the number of flights
	*
	*/
	public function countOverallFlights()
	{
		$query  = "SELECT COUNT(DISTINCT spotter_output.spotter_id) AS flight_count  
                    FROM spotter_output";
      
		
		$sth = $this->db->prepare($query);
		$sth->execute();
		return $sth->fetchColumn();
	}
	
	
	
	/**
	* Counts all airlines that have flown over
	*
	* @return Integer the number of airlines
	*
	*/
	public function countOverallAirlines()
	{
		$query  = "SELECT COUNT(DISTINCT spotter_output.airline_name) AS airline_count 
							FROM spotter_output";
      
		
		$sth = $this->db->prepare($query);
		$sth->execute();
		return $sth->fetchColumn();
	}

  
	/**
	* Counts all hours of today
	*
	* @return Array the hour list
	*
	*/
	public function countAllHoursFromToday()
	{
		global $globalTimezone;
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime();
			$offset = $datetime->format('P');
		} else $offset = '+00:00';

		$query  = "SELECT HOUR(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) AS hour_name, count(*) as hour_count
								FROM spotter_output 
								WHERE DATE(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) = CURDATE()
								GROUP BY hour_name 
								ORDER BY hour_name ASC";
      
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':offset' => $offset));
      
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
	public function getUpcomingFlights($limit = '', $sort = '')
	{
		global $global_query, $globalDBdriver, $globalTimezone;
		date_default_timezone_set('UTC');
		if ($limit != "")
		{
			$limit_array = explode(",", $limit);
			$limit_array[0] = filter_var($limit_array[0],FILTER_SANITIZE_NUMBER_INT);
			$limit_array[1] = filter_var($limit_array[1],FILTER_SANITIZE_NUMBER_INT);
			if ($limit_array[0] >= 0 && $limit_array[1] >= 0)
			{
				//$limit_query = " LIMIT ".$limit_array[0].",".$limit_array[1];
				$limit_query = " LIMIT ".$limit_array[1]." OFFSET ".$limit_array[0];
			}
		}
		$currentHour = date("G");
		$next3Hours = date("G", strtotime("+3 hour"));
		//if the next 3 hours is already equal to/past midnight, we limit it to stay there, otherwise the query will fail
		if ($currentHour >= 21 && $next3Hours >= 00)
		{
			$next3Hours = 24;
		}
		$currentDayofWeek = date("l");
		if ($globalDBdriver == 'mysql') {
			if ($sort != "")
			{
				$search_orderby_array = $this->getOrderBy();
				$orderby_query = $search_orderby_array[$sort]['sql'];
			} else {
				$orderby_query = " ORDER BY HOUR(spotter_output.date) ASC";
			}
			$query = "SELECT spotter_output.*, count(spotter_output.ident) as ident_count
			    FROM spotter_output
			    WHERE DAYNAME(spotter_output.date) = '$currentDayofWeek' AND HOUR(spotter_output.date) >= '$currentHour' AND HOUR(spotter_output.date) <= '$next3Hours'
			    GROUP BY spotter_output.ident HAVING ident_count > 10 $orderby_query";
			$spotter_array = $this->getDataFromDB($query.$limit_query);
		} else if ($globalDBdriver == 'pgsql') {
			if ($sort != "")
			{
				$search_orderby_array = $this->getOrderBy();
				$orderby_query = $search_orderby_array[$sort]['sql'];
			} else {
				$orderby_query = " ORDER BY EXTRACT (HOUR FROM spotter_output.date) ASC";
			}
			$query = "SELECT spotter_output.*, count(spotter_output.ident) as ident_count
			    FROM spotter_output
			    WHERE DATE_PART('dow', spotter_output.date) = DATE_PART('dow', date 'now' AT TIME ZONE :timezone) AND EXTRACT (HOUR FROM spotter_output.date AT TIME ZONE :timezone) >= '$currentHour' AND EXTRACT (HOUR FROM spotter_output.date AT TIME ZONE :timezone) <= '$next3Hours'
			    GROUP BY spotter_output.ident, spotter_output.spotter_id HAVING count(spotter_output.ident) > 10 $orderby_query";
		$spotter_array = $this->getDataFromDB($query.$limit_query,array(':timezone' => $globalTimezone));
		}
		return $spotter_array;
	}
    
    /**
	* Adds the images based on the aircraft registration
	*
	* @return String either success or error
	*
	*/
/*
public function addSpotterImage($registration)
	{
		$registration = filter_var($registration,FILTER_SANITIZE_STRING);
		$registration = trim($registration);
		//getting the aircraft image
		$image_url = $this->findAircraftImage($registration);
		//echo "Image :\n";
		//print_r($image_url);
		if ($image_url['original'] != '') {
			$query  = "INSERT INTO spotter_image (registration, image, image_thumbnail, image_copyright, image_source) VALUES (:registration,:image,:image_thumbnail,:copyright,:source)";
			try {
				
				$sth = $this->db->prepare($query);
				$sth->execute(array(':registration' => $registration,':image' => $image_url['original'],':image_thumbnail' => $image_url['thumbnail'], ':copyright' => $image_url['copyright'],':source' => $image_url['source']));
			} catch(PDOException $e) {
				echo $e->message;
				return "error";
			}
		}
		return "success";
	}
    */
    
    /**
	* Gets the images based on the aircraft registration
	*
	* @return Array the images list
	*
	*/
	/*
	public function getSpotterImage($registration)
	{
    		$registration = filter_var($registration,FILTER_SANITIZE_STRING);
		$registration = trim($registration);

		$query  = "SELECT spotter_image.*
								FROM spotter_image 
								WHERE spotter_image.registration = :registration";

		
		$sth = $this->db->prepare($query);
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
    */
    
     /**
	* Gets the Barrie Spotter ID based on the FlightAware ID
	*
	* @return Integer the Barrie Spotter ID
	*
	*/
	public function getBarrieSpotterIDBasedOnFlightAwareID($flightaware_id)
	{
		$flightaware_id = filter_var($flightaware_id,FILTER_SANITIZE_STRING);

		$query  = "SELECT spotter_output.spotter_id
								FROM spotter_output 
								WHERE spotter_output.flightaware_id = '".$flightaware_id."'";
        
		
		$sth = $this->db->prepare($query);
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
	public function parseDateString($dateString, $timezone = '')
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
	public function parseDirection($direction)
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
	/*
	public function findAircraftImage($aircraft_registration)
	{
		$aircraft_registration = filter_var($aircraft_registration,FILTER_SANITIZE_STRING);
		$aircraft_registration = trim($aircraft_registration);
		if ($aircraft_registration == '') return array('thumbnail' => '','original' => '', 'copyright' => '', 'source' => '');
		// If aircraft registration is only number, also check with aircraft model
  
		if (preg_match('/^[[:digit]]+$/',$aircraft_registration)) {
			$aircraft_info = $this->getAircraftInfoByRegistration($aircraft_registration);
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
			$aircraft_info = $this->getAircraftInfoByRegistration($aircraft_registration);
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
	*/
	
	
	/**
	* Gets the aircraft registration
	*
	* @param String $flightaware_id the flight aware id
	* @return String the aircraft registration
	*
	*/
	
	public function getAircraftRegistration($flightaware_id)
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
		
		$registration = $this->convertAircraftRegistration($registration);
		
		return $registration;	
	}


	/**
	* Gets the aircraft registration from ModeS
	*
	* @param String $aircraft_modes the flight ModeS in hex
	* @return String the aircraft registration
	*
	*/
	
	public function getAircraftRegistrationBymodeS($aircraft_modes)
	{
		$aircraft_modes = filter_var($aircraft_modes,FILTER_SANITIZE_STRING);
	
		$query  = "SELECT aircraft_modes.Registration FROM aircraft_modes WHERE aircraft_modes.ModeS = :aircraft_modes LIMIT 1";
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':aircraft_modes' => $aircraft_modes));
    
		$row = $sth->fetch(PDO::FETCH_ASSOC);
		if (count($row) > 0) {
		    //return $row['Registration'];
		    return $row['registration'];
		} else return '';
	
	}

	/**
	* Gets Countrie from latitude/longitude
	*
	* @param String $aircraft_modes the flight ModeS in hex
	* @return String the aircraft registration
	*
	*/
	
	public function getCountryFromLatitudeLongitude($latitude,$longitude)
	{
		$latitude = filter_var($latitude,FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
		$longitude = filter_var($longitude,FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
	
//		$query  = "SELECT name, iso2, iso3 FROM countries WHERE Within(GeomFromText('POINT(:latitude :longitude)'), ogc_geom) LIMIT 1";
		$query  = "SELECT name, iso2, iso3 FROM countries WHERE Within(GeomFromText('POINT(".$longitude.' '.$latitude.")'), ogc_geom) LIMIT 1";
		
		$sth = $this->db->prepare($query);
		//$sth->execute(array(':latitude' => $latitude,':longitude' => $longitude));
		$sth->execute();
    
		$row = $sth->fetch(PDO::FETCH_ASSOC);
		if (count($row) > 0) {
		    return $row;
		} else return '';
	
	}

	/**
	* converts the registration code using the country prefix
	*
	* @param String $registration the aircraft registration
	* @return String the aircraft registration
	*
	*/
	public function convertAircraftRegistration($registration)
	{
		$registration = filter_var($registration,FILTER_SANITIZE_STRING);
		$registration_prefix = '';
		$registration_1 = substr($registration, 0, 1);
		$registration_2 = substr($registration, 0, 2);

		//first get the prefix based on two characters
		$query  = "SELECT aircraft_registration.registration_prefix FROM aircraft_registration WHERE registration_prefix = :registration_2";
      
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':registration_2' => $registration_2));
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$registration_prefix = $row['registration_prefix'];
		}

		//if we didn't find a two chracter prefix lets just search the one with one character
		if ($registration_prefix == "")
		{
			$query  = "SELECT aircraft_registration.registration_prefix FROM aircraft_registration WHERE registration_prefix = :registration_1";
	      
			$sth = $this->db->prepare($query);
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
	* Country from the registration code
	*
	* @param String $registration the aircraft registration
	* @return String the country
	*
	*/
	public function countryFromAircraftRegistration($registration)
	{
		$registration = filter_var($registration,FILTER_SANITIZE_STRING);
		
		$registration_prefix = '';
		
		$registration_test = explode('-',$registration);
		$country = '';
		if ($registration_test[0] != $registration) {
			$query  = "SELECT aircraft_registration.registration_prefix, aircraft_registration.country FROM aircraft_registration WHERE registration_prefix = :registration_1 LIMIT 1";
	      
			$sth = $this->db->prepare($query);
			$sth->execute(array(':registration_1' => $registration_test[0]));
	        
			while($row = $sth->fetch(PDO::FETCH_ASSOC))
			{
				$registration_prefix = $row['registration_prefix'];
				$country = $row['country'];
			}
		} else {
    			$registration_1 = substr($registration, 0, 1);
		        $registration_2 = substr($registration, 0, 2);

			$country = '';
			//first get the prefix based on two characters
			$query  = "SELECT aircraft_registration.registration_prefix, aircraft_registration.country FROM aircraft_registration WHERE registration_prefix = :registration_2 LIMIT 1";
      
			
			$sth = $this->db->prepare($query);
			$sth->execute(array(':registration_2' => $registration_2));
        
			while($row = $sth->fetch(PDO::FETCH_ASSOC))
			{
				$registration_prefix = $row['registration_prefix'];
				$country = $row['country'];
			}

			//if we didn't find a two chracter prefix lets just search the one with one character
			if ($registration_prefix == "")
			{
				$query  = "SELECT aircraft_registration.registration_prefix, aircraft_registration.country FROM aircraft_registration WHERE registration_prefix = :registration_1 LIMIT 1";
	      
				$sth = $this->db->prepare($query);
				$sth->execute(array(':registration_1' => $registration_1));
	        
				while($row = $sth->fetch(PDO::FETCH_ASSOC))
				{
					$registration_prefix = $row['registration_prefix'];
					$country = $row['country'];
				}
			}
		}
    
		return $country;
	}
	
	/**
	* Set a new highlight value for a flight
	*
	* @param String $flightaware_id flightaware_id from spotter_output table
	* @param String $highlight New highlight value
	*/
	public function setHighlightFlight($flightaware_id,$highlight) {
		
		$query  = "UPDATE spotter_output SET highlight = :highlight WHERE flightaware_id = :flightaware_id";
		$sth = $this->db->prepare($query);
		$sth->execute(array(':flightaware_id' => $flightaware_id, ':highlight' => $highlight));
	}
	
	/**
	* Gets the short url from bit.ly
	*
	* @param String $url the full url
	* @return String the bit.ly url
	*
	*/
	public function getBitlyURL($url)
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


	public function getOrderBy()
	{
		$orderby = array("aircraft_asc" => array("key" => "aircraft_asc", "value" => "Aircraft Type - ASC", "sql" => "ORDER BY spotter_output.aircraft_icao ASC"), "aircraft_desc" => array("key" => "aircraft_desc", "value" => "Aircraft Type - DESC", "sql" => "ORDER BY spotter_output.aircraft_icao DESC"),"manufacturer_asc" => array("key" => "manufacturer_asc", "value" => "Aircraft Manufacturer - ASC", "sql" => "ORDER BY spotter_output.aircraft_manufacturer ASC"), "manufacturer_desc" => array("key" => "manufacturer_desc", "value" => "Aircraft Manufacturer - DESC", "sql" => "ORDER BY spotter_output.aircraft_manufacturer DESC"),"airline_name_asc" => array("key" => "airline_name_asc", "value" => "Airline Name - ASC", "sql" => "ORDER BY spotter_output.airline_name ASC"), "airline_name_desc" => array("key" => "airline_name_desc", "value" => "Airline Name - DESC", "sql" => "ORDER BY spotter_output.airline_name DESC"), "ident_asc" => array("key" => "ident_asc", "value" => "Ident - ASC", "sql" => "ORDER BY spotter_output.ident ASC"), "ident_desc" => array("key" => "ident_desc", "value" => "Ident - DESC", "sql" => "ORDER BY spotter_output.ident DESC"), "airport_departure_asc" => array("key" => "airport_departure_asc", "value" => "Departure Airport - ASC", "sql" => "ORDER BY spotter_output.departure_airport_city ASC"), "airport_departure_desc" => array("key" => "airport_departure_desc", "value" => "Departure Airport - DESC", "sql" => "ORDER BY spotter_output.departure_airport_city DESC"), "airport_arrival_asc" => array("key" => "airport_arrival_asc", "value" => "Arrival Airport - ASC", "sql" => "ORDER BY spotter_output.arrival_airport_city ASC"), "airport_arrival_desc" => array("key" => "airport_arrival_desc", "value" => "Arrival Airport - DESC", "sql" => "ORDER BY spotter_output.arrival_airport_city DESC"), "date_asc" => array("key" => "date_asc", "value" => "Date - ASC", "sql" => "ORDER BY spotter_output.date ASC"), "date_desc" => array("key" => "date_desc", "value" => "Date - DESC", "sql" => "ORDER BY spotter_output.date DESC"));
		
		return $orderby;
		
	}
    
    
    public function importFromFlightAware()
    {
       global $globalFlightAwareUsername, $globalFlightAwarePassword, $globalLatitudeMax, $globalLatitudeMin, $globalLongitudeMax, $globalLongitudeMin, $globalAirportIgnore;
	$Spotter = new Spotter();
	$SPotterLive = new SpotterLive();
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
                            $last_hour_ident = $this->getIdentFromLastHour($ident);

                            //change the departure/arrival airport to NA if its not available
                                if ($departure_airport == "" || $departure_airport == "---" || $departure_airport == "ZZZ" || $departure_airport == "ZZZZ") { $departure_airport = "NA"; }
                                if ($arrival_airport == "" || $arrival_airport == "---" || $arrival_airport == "ZZZ" || $arrival_airport == "ZZZZ") { $arrival_airport = "NA"; }


                            //if there was no aircraft with the same callsign within the last hour and go post it into the archive
                            if($last_hour_ident == "")
                            {
                                //adds the spotter data for the archive
                                $Spotter->addSpotterData($flightaware_id, $ident, $aircraft_type, $departure_airport, $arrival_airport, $latitude, $longitude, $waypoints, $altitude, $heading, $groundspeed);
                            }

                            //adds the spotter LIVE data
                            $SpotterLive->addLiveSpotterData($flightaware_id, $ident, $aircraft_type, $departure_airport, $arrival_airport, $latitude, $longitude, $waypoints, $altitude, $heading, $groundspeed);
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
                        $last_hour_ident = $this->getIdentFromLastHour($ident);

                        //change the departure/arrival airport to NA if its not available
                                if ($departure_airport == "" || $departure_airport == "---" || $departure_airport == "ZZZ" || $departure_airport == "ZZZZ") { $departure_airport = "NA"; }
                                if ($arrival_airport == "" || $arrival_airport == "---" || $arrival_airport == "ZZZ" || $arrival_airport == "ZZZZ") { $arrival_airport = "NA"; }

                        //if there was no aircraft with the same callsign within the last hour and go post it into the archive
                        if($last_hour_ident == "")
                        {
                            //adds the spotter data for the archive
                            $Spotter->addSpotterData($flightaware_id, $ident, $aircraft_type, $departure_airport, $arrival_airport, $latitude, $longitude, $waypoints, $altitude, $heading, $groundspeed);
                        }

                        //adds the spotter LIVE data
                        $SpotterLive->addLiveSpotterData($flightaware_id, $ident, $aircraft_type, $departure_airport, $arrival_airport, $latitude, $longitude, $waypoints, $altitude, $heading, $groundspeed);
                    }
                            $ignoreImport = false;
                        }
                    }

                }
        } 
    }


	// Update flights data when new data in DB
	public function updateFieldsFromOtherTables()
	{
		global $globalDebug;
		$Image = new Image();
		

		// routes
		if ($globalDebug) print "Routes...\n";
		$query = "SELECT spotter_output.spotter_id, routes.FromAirport_ICAO, routes.ToAirport_ICAO FROM spotter_output, routes WHERE spotter_output.ident = routes.CallSign AND ( spotter_output.departure_airport_icao != routes.FromAirport_ICAO OR spotter_output.arrival_airport_icao != routes.ToAirport_ICAO) AND routes.FromAirport_ICAO != ''";
		$sth = $this->db->prepare($query);
		$sth->execute();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$departure_airport_array = $this->getAllAirportInfo($row['fromairport_icao']);
			$arrival_airport_array = $this->getAllAirportInfo($row['toairport_icao']);
			if (count($departure_airport_array) > 0 && count($arrival_airport_array) > 0) {
				$update_query="UPDATE spotter_output SET departure_airport_icao = :fromicao, arrival_airport_icao = :toicao, departure_airport_name = :departure_airport_name, departure_airport_city = :departure_airport_city, departure_airport_country = :departure_airport_country, arrival_airport_name = :arrival_airport_name, arrival_airport_city = :arrival_airport_city, arrival_airport_country = :arrival_airport_country WHERE spotter_id = :spotter_id";
				$sthu = $this->db->prepare($update_query);
				$sthu->execute(array(':fromicao' => $row['fromairport_icao'],':toicao' => $row['toairport_icao'],':spotter_id' => $row['spotter_id'],':departure_airport_name' => $departure_airport_array[0]['name'],':departure_airport_city' => $departure_airport_array[0]['city'],':departure_airport_country' => $departure_airport_array[0]['country'],':arrival_airport_name' => $arrival_airport_array[0]['name'],':arrival_airport_city' => $arrival_airport_array[0]['city'],':arrival_airport_country' => $arrival_airport_array[0]['country']));
			}
		}
		
		if ($globalDebug) print "Airlines...\n";
		//airlines
		$query  = "SELECT spotter_output.spotter_id, spotter_output.ident FROM spotter_output WHERE spotter_output.airline_name = '' OR spotter_output.airline_name = 'Not Available'";
		$sth = $this->db->prepare($query);
		$sth->execute();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			if (is_numeric(substr($row['ident'], -1, 1)))
			{
				$airline_array = $this->getAllAirlineInfo(substr($row['ident'], 0, 3));
				$update_query  = "UPDATE spotter_output SET spotter_output.airline_name = :airline_name, spotter_output.airline_icao = :airline_icao, spotter_output.airline_country = :airline_country, spotter_output.airline_type = :airline_type WHERE spotter_output.spotter_id = :spotter_id";
				$sthu = $this->db->prepare($update_query);
				$sthu->execute(array(':airline_name' => $airline_array[0]['name'],':airline_icao' => $airline_array[0]['icao'], ':airline_country' => $airline_array[0]['country'], ':airline_type' => $airline_array[0]['type'], ':spotter_id' => $row['spotter_id']));
			}
		}

		if ($globalDebug) print "Remove Duplicate in aircraft_modes...\n";
		//duplicate modes
		$query = "DELETE aircraft_modes FROM aircraft_modes LEFT OUTER JOIN (SELECT max(`AircraftID`) as `AircraftID`,`ModeS` FROM `aircraft_modes` group by ModeS) as KeepRows ON aircraft_modes.AircraftID = KeepRows.AircraftID WHERE KeepRows.AircraftID IS NULL";
		$sth = $this->db->prepare($query);
		$sth->execute();
		
		if ($globalDebug) print "Aircraft...\n";
		//aircraft
		$query  = "SELECT spotter_output.spotter_id, spotter_output.aircraft_icao, spotter_output.registration FROM spotter_output WHERE spotter_output.aircraft_name = '' OR spotter_output.aircraft_name = 'Not Available'";
		$sth = $this->db->prepare($query);
		$sth->execute();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			if ($row['aircraft_icao'] != '') {
				$aircraft_name = $this->getAllAircraftInfo($row['aircraft_icao']);
				if ($row['registration'] != ""){
					$Image->addSpotterImage($row['registration']);
				}
				if (count($aircraft_name) > 0) {
					$update_query  = "UPDATE spotter_output SET spotter_output.aircraft_name = :aircraft_name, spotter_output.aircraft_manufacturer = :aircraft_manufacturer WHERE spotter_output.spotter_id = :spotter_id";
					$sthu = $this->db->prepare($update_query);
					$sthu->execute(array(':aircraft_name' => $aircraft_name[0]['type'], ':aircraft_manufacturer' => $aircraft_name[0]['manufacturer'], ':spotter_id' => $row['spotter_id']));
				}
			}
		}
	}	
	
}
?>