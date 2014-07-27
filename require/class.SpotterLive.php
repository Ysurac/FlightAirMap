<?php
$global_query = "SELECT spotter_live.* FROM spotter_live";

class SpotterLive{

	/**
	* Executes the SQL statements to get the spotter information
	*
	* @param String $query the SQL query
	* @param String $limit the limit query
	* @return Array the spotter information
	*
	*/
	public static function getDataFromDB($query, $limitQuery = '')
	{
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

		$result = mysql_query($query.$limitQuery);
		$num_rows = mysql_num_rows($result);

		$spotter_array = array();
		$temp_array = array();


		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			$temp_array['spotter_live_id'] = $row['spotter_live_id'];
      $temp_array['flightaware_id'] = $row['flightaware_id'];
      $temp_array['ident'] = $row['ident'];
      $temp_array['registration'] = $row['registration'];
      $temp_array['aircraft_type'] = $row['aircraft_icao'];
      $temp_array['departure_airport'] = $row['departure_airport_icao'];
      $temp_array['arrival_airport'] = $row['arrival_airport_icao'];
      $temp_array['latitude'] = $row['latitude'];
      $temp_array['longitude'] = $row['longitude'];
      $temp_array['waypoints'] = $row['waypoints'];
      $temp_array['altitude'] = $row['altitude'];
      $temp_array['heading'] = $row['heading'];
      $heading_direction = Spotter::parseDirection($row['heading']);
      $temp_array['heading_name'] = $heading_direction[0]['direction_fullname'];
      $temp_array['ground_speed'] = $row['ground_speed'];
      $temp_array['image_thumbnail'] = $row['image_thumbnail'];

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
			$temp_array['aircraft_name'] = $aircraft_array[0]['type'];
			$temp_array['aircraft_manufacturer'] = $aircraft_array[0]['manufacturer'];

			$airline_array = array();
			if (is_numeric(substr($row['ident'], -1, 1)))
			{
				$airline_array = Spotter::getAllAirlineInfo(substr($row['ident'], 0, 3));
			}
			$temp_array['airline_icao'] = $airline_array[0]['icao'];
			$temp_array['airline_iata'] = $airline_array[0]['iata'];
			$temp_array['airline_name'] = $airline_array[0]['name'];
			$temp_array['airline_country'] = $airline_array[0]['country'];
			$temp_array['airline_callsign'] = $airline_array[0]['callsign'];
			$temp_array['airline_type'] = $airline_array[0]['type'];

			$departure_airport_array = Spotter::getAllAirportInfo($row['departure_airport_icao']);
			$temp_array['departure_airport_name'] = $departure_airport_array[0]['name'];
			$temp_array['departure_airport_city'] = $departure_airport_array[0]['city'];
			$temp_array['departure_airport_country'] = $departure_airport_array[0]['country'];
			$temp_array['departure_airport_iata'] = $departure_airport_array[0]['iata'];
			$temp_array['departure_airport_icao'] = $departure_airport_array[0]['icao'];
			$temp_array['departure_airport_latitude'] = $departure_airport_array[0]['latitude'];
			$temp_array['departure_airport_longitude'] = $departure_airport_array[0]['longitude'];
			$temp_array['departure_airport_altitude'] = $departure_airport_array[0]['altitude'];

			$arrival_airport_array = Spotter::getAllAirportInfo($row['arrival_airport_icao']);
			$temp_array['arrival_airport_name'] = $arrival_airport_array[0]['name'];
			$temp_array['arrival_airport_city'] = $arrival_airport_array[0]['city'];
			$temp_array['arrival_airport_country'] = $arrival_airport_array[0]['country'];
			$temp_array['arrival_airport_iata'] = $arrival_airport_array[0]['iata'];
			$temp_array['arrival_airport_icao'] = $arrival_airport_array[0]['icao'];
			$temp_array['arrival_airport_latitude'] = $arrival_airport_array[0]['latitude'];
			$temp_array['arrival_airport_longitude'] = $arrival_airport_array[0]['longitude'];
			$temp_array['arrival_airport_altitude'] = $arrival_airport_array[0]['altitude'];

			$temp_array['query_number_rows'] = $num_rows;

			$spotter_array[] = $temp_array;
		}

		return $spotter_array;
	}


	/**
	* Gets all the spotter information based on the latest data entry
	*
	* @return Array the spotter information
	*
	*/
	public static function getLiveSpotterData()
	{
		global $global_query;

		date_default_timezone_set('UTC');

		if(!Connection::createDBConnection())
		{
			return false;
		}

		$query  = $global_query;

		$spotter_array = Spotter::getDataFromDB($query, $limit_query);

		return $spotter_array;
	}


	/**
	* Deletes all info in the table
	*
	* @return String success or false
	*
	*/
	public static function deleteLiveSpotterData()
	{

		if(!Connection::createDBConnection())
		{
			return false;
		}

		$query  = "DELETE FROM spotter_live";
		$result = mysql_query($query);

		if ($result == 1)
		{
			return "success";
		} else {
			return "error";
		}
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
	public static function addLiveSpotterData($flightaware_id = '', $ident = '', $aircraft_icao = '', $departure_airport_icao = '', $arrival_airport_icao = '', $latitude = '', $longitude = '', $waypoints = '', $altitude = '', $heading = '', $groundspeed = '')
	{
		global $globalURL;

		date_default_timezone_set('UTC');

		if(!Connection::createDBConnection())
		{
			return false;
		}

		//getting the registration
		if ($flightaware_id != "")
		{
			if (!is_string($flightaware_id))
			{
				return false;
			} else {
				$registration = Spotter::getAircraftRegistration($flightaware_id);
			}
		}

    	//getting the airline information
		if ($ident != "")
		{
			if (!is_string($ident))
			{
				return false;
			} else {
				if (is_numeric(substr($ident, -1, 1)))
				{
					$airline_array = Spotter::getAllAirlineInfo(substr($ident, 0, 3));

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

		$date = date("Y-m-d H:i:s", time());

		//getting the aircraft image
		if ($registration != "")
		{
			$image_url = Spotter::findAircraftImage($registration);
		}

		$flightaware_id = mysql_real_escape_string($flightaware_id);
	    $ident = mysql_real_escape_string($ident);
	    $aircraft_icao = mysql_real_escape_string($aircraft_icao);
	    $departure_airport_icao = mysql_real_escape_string($departure_airport_icao);
	    $arrival_airport_icao = mysql_real_escape_string($arrival_airport_icao);
	    $latitude = mysql_real_escape_string($latitude);
	    $longitude = mysql_real_escape_string($longitude);
	    $waypoints = mysql_real_escape_string($waypoints);
	    $altitude = mysql_real_escape_string($altitude);
	    $heading = mysql_real_escape_string($heading);
	    $groundspeed = mysql_real_escape_string($groundspeed);

		$query  = "INSERT INTO spotter_live (flightaware_id, ident, registration, airline_name, airline_icao, airline_country, airline_type, aircraft_icao, aircraft_name, aircraft_manufacturer, departure_airport_icao, departure_airport_name, departure_airport_city, departure_airport_country, arrival_airport_icao, arrival_airport_name, arrival_airport_city, arrival_airport_country, latitude, longitude, waypoints, altitude, heading, ground_speed, image, image_thumbnail, date) VALUES ('$flightaware_id','$ident','$registration','".$airline_array[0]['name']."', '".$airline_array[0]['icao']."', '".$airline_array[0]['country']."', '".$airline_array[0]['type']."', '$aircraft_icao', '".$aircraft_array[0]['type']."', '".$aircraft_array[0]['manufacturer']."', '$departure_airport_icao', '".$departure_airport_array[0]['name']."', '".$departure_airport_array[0]['city']."', '".$departure_airport_array[0]['country']."', '$arrival_airport_icao', '".$arrival_airport_array[0]['name']."', '".$arrival_airport_array[0]['city']."', '".$arrival_airport_array[0]['country']."', '$latitude', '$longitude', '$waypoints', '$altitude', '$heading', '$groundspeed', '".$image_url['original']."', '".$image_url['thumbnail']."', '$date')";

		print $query."<br /><br />";

		$result = mysql_query($query);

		if ($result == 1)
		{
			return "success";
		} else {
			return "error";
		}

	}


}


?>
