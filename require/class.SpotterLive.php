<?php
$global_query = "SELECT spotter_live.* FROM spotter_live";

class SpotterLive{


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

		$query  = $global_query." WHERE DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 MINUTE) <= spotter_live.date";

		$spotter_array = Spotter::getDataFromDB($query, $limit_query);

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
		
		if(!Connection::createDBConnection())
		{
			return false;
		}
        
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
				$additional_query = ' AND DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 MINUTE) <= spotter_live.date ';
                return false;
			} else {
                if ($interval == "1m")
                {
                    $additional_query = ' AND DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 MINUTE) <= spotter_live.date ';
                } else if ($interval == "15m"){
                    $additional_query = ' AND DATE_SUB(UTC_TIMESTAMP(),INTERVAL 15 MINUTE) <= spotter_live.date ';
                } 
            }
		} else {
         $additional_query = ' AND DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 MINUTE) <= spotter_output.date ';   
        }

		$query  = "SELECT spotter_live.*, ( 6371 * acos( cos( radians($lat) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians($lng) ) + sin( radians($lat) ) * sin( radians( latitude ) ) ) ) AS distance FROM spotter_live 
                   WHERE spotter_live.latitude <> '' 
				   AND spotter_live.longitude <> '' 
                   ".$additional_query."
                   HAVING distance < $radius  
				   ORDER BY distance";

		$spotter_array = Spotter::getDataFromDB($query, $limit_query);

		return $spotter_array;
	}
    
    
    /**
	* Gets all the spotter information based on a particular callsign
	*
	* @return Array the spotter information
	*
	*/
	public static function getAllLiveSpotterDataByIdent($ident)
	{
		global $global_query;

		date_default_timezone_set('UTC');

		if(!Connection::createDBConnection())
		{
			return false;
		}
        
        $ident = mysql_real_escape_string($ident);

		$query  = $global_query." WHERE spotter_live.ident = '".$ident."'";

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

		$query  = "DELETE FROM spotter_live WHERE DATE_SUB(UTC_TIMESTAMP(),INTERVAL 30 MINUTE) >= spotter_live.date";
        
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
            $image_array = Spotter::getSpotterImage($registration);
            
            if ($image_array[0]['registration'] == "")
            {
                Spotter::addSpotterImage($registration);
            }  
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

		$query  = "INSERT INTO spotter_live (flightaware_id, ident, registration, airline_name, airline_icao, airline_country, airline_type, aircraft_icao, aircraft_name, aircraft_manufacturer, departure_airport_icao, departure_airport_name, departure_airport_city, departure_airport_country, arrival_airport_icao, arrival_airport_name, arrival_airport_city, arrival_airport_country, latitude, longitude, waypoints, altitude, heading, ground_speed, date) VALUES ('$flightaware_id','$ident','$registration','".$airline_array[0]['name']."', '".$airline_array[0]['icao']."', '".$airline_array[0]['country']."', '".$airline_array[0]['type']."', '$aircraft_icao', '".$aircraft_array[0]['type']."', '".$aircraft_array[0]['manufacturer']."', '$departure_airport_icao', '".$departure_airport_array[0]['name']."', '".$departure_airport_array[0]['city']."', '".$departure_airport_array[0]['country']."', '$arrival_airport_icao', '".$arrival_airport_array[0]['name']."', '".$arrival_airport_array[0]['city']."', '".$arrival_airport_array[0]['country']."', '$latitude', '$longitude', '$waypoints', '$altitude', '$heading', '$groundspeed', '$date')";

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
