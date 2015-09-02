<?php
//$global_query = "SELECT spotter_live.* FROM spotter_live";

class SpotterLive {
	static $global_query = "SELECT spotter_live.* FROM spotter_live";

	/**
	* Gets all the spotter information based on the latest data entry
	*
	* @return Array the spotter information
	*
	*/
	public static function getLiveSpotterData($limit = '', $sort = '')
	{
		global $globalDBdriver, $globalLiveInterval;
		date_default_timezone_set('UTC');
		
		$limit_query = '';
		if ($limit != "")
		{
			$limit_array = explode(",", $limit);
			$limit_array[0] = filter_var($limit_array[0],FILTER_SANITIZE_NUMBER_INT);
			$limit_array[1] = filter_var($limit_array[1],FILTER_SANITIZE_NUMBER_INT);
			if ($limit_array[0] >= 0 && $limit_array[1] >= 0)
			{
				$limit_query = " LIMIT ".$limit_array[1]." OFFSET ".$limit_array[0];
			}
		}
		$orderby_query = '';
		if ($sort != "")
		{
			$search_orderby_array = SpotterLive::getOrderBy();
			$orderby_query = ' '.$search_orderby_array[$sort]['sql'];
		}

		if (!isset($globalLiveInterval)) $globalLiveInterval = '200';
		if ($globalDBdriver == 'mysql') {
			//$query  = "SELECT spotter_live.* FROM spotter_live INNER JOIN (SELECT l.flightaware_id, max(l.date) as maxdate FROM spotter_live l WHERE DATE_SUB(UTC_TIMESTAMP(),INTERVAL 30 SECOND) <= l.date GROUP BY l.flightaware_id) s on spotter_live.flightaware_id = s.flightaware_id AND spotter_live.date = s.maxdate";
			$query  = "SELECT spotter_live.* FROM spotter_live INNER JOIN (SELECT l.flightaware_id, max(l.date) as maxdate FROM spotter_live l WHERE DATE_SUB(UTC_TIMESTAMP(),INTERVAL ".$globalLiveInterval." SECOND) <= l.date GROUP BY l.flightaware_id) s on spotter_live.flightaware_id = s.flightaware_id AND spotter_live.date = s.maxdate$orderby_query";
                } else if ($globalDBdriver == 'pgsql') {
            		//$query  = "SELECT spotter_live.* FROM spotter_live INNER JOIN (SELECT l.flightaware_id, max(l.date) as maxdate FROM spotter_live l WHERE NOW() AT TIME ZONE 'UTC' - '30 SECONDS'::INTERVAL <= l.date GROUP BY l.flightaware_id) s on spotter_live.flightaware_id = s.flightaware_id AND spotter_live.date = s.maxdate";
            		$query  = "SELECT spotter_live.* FROM spotter_live INNER JOIN (SELECT l.flightaware_id, max(l.date) as maxdate FROM spotter_live l WHERE NOW() AT TIME ZONE 'UTC' - '".$globalLiveInterval." SECONDS'::INTERVAL <= l.date GROUP BY l.flightaware_id) s on spotter_live.flightaware_id = s.flightaware_id AND spotter_live.date = s.maxdate$orderby_query";
		}
		$spotter_array = Spotter::getDataFromDB($query.$limit_query);

		return $spotter_array;
	}

	/**
	* Gets Minimal Live Spotter data
	*
	* @return Array the spotter information
	*
	*/
	public static function getMinLiveSpotterData()
	{
		global $globalDBdriver, $globalLiveInterval;
		date_default_timezone_set('UTC');
		
		if (!isset($globalLiveInterval)) $globalLiveInterval = '200';
		if ($globalDBdriver == 'mysql') {
//			$query  = "SELECT spotter_live.* FROM spotter_live INNER JOIN (SELECT l.flightaware_id, max(l.date) as maxdate FROM spotter_live l WHERE DATE_SUB(UTC_TIMESTAMP(),INTERVAL ".$globalLiveInterval." SECOND) <= l.date GROUP BY l.flightaware_id) s on spotter_live.flightaware_id = s.flightaware_id AND spotter_live.date = s.maxdate$orderby_query";
			$query  = "SELECT spotter_live.ident, spotter_live.flightaware_id, spotter_live.aircraft_icao, spotter_live.departure_airport_icao as departure_airport, spotter_live.arrival_airport_icao as arrival_airport, spotter_live.latitude, spotter_live.longitude, spotter_live.altitude, spotter_live.heading, spotter_live.ground_speed, spotter_live.squawk, a.aircraft_shadow FROM spotter_live INNER JOIN (SELECT l.flightaware_id, max(l.date) as maxdate FROM spotter_live l WHERE DATE_SUB(UTC_TIMESTAMP(),INTERVAL ".$globalLiveInterval." SECOND) <= l.date GROUP BY l.flightaware_id) s on spotter_live.flightaware_id = s.flightaware_id AND spotter_live.date = s.maxdate INNER JOIN (SELECT * FROM aircraft) a on spotter_live.aircraft_icao = a.icao";

                } else if ($globalDBdriver == 'pgsql') {
            		//$query  = "SELECT spotter_live.* FROM spotter_live INNER JOIN (SELECT l.flightaware_id, max(l.date) as maxdate FROM spotter_live l WHERE NOW() AT TIME ZONE 'UTC' - '30 SECONDS'::INTERVAL <= l.date GROUP BY l.flightaware_id) s on spotter_live.flightaware_id = s.flightaware_id AND spotter_live.date = s.maxdate";
            		$query  = "SELECT spotter_live.* FROM spotter_live INNER JOIN (SELECT l.flightaware_id, max(l.date) as maxdate FROM spotter_live l WHERE NOW() AT TIME ZONE 'UTC' - '".$globalLiveInterval." SECONDS'::INTERVAL <= l.date GROUP BY l.flightaware_id) s on spotter_live.flightaware_id = s.flightaware_id AND spotter_live.date = s.maxdate$orderby_query";
		}
//		$spotter_array = Spotter::getDataFromDB($query.$limit_query);

    		try {
			$Connection = new Connection();
			$sth = Connection::$db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			return "error";
		}
		$spotter_array = $sth->fetchAll(PDO::FETCH_ASSOC);

		return $spotter_array;
	}

	/**
	* Gets number of latest data entry
	*
	* @return String number of entry
	*
	*/
	public static function getLiveSpotterCount()
	{
		global $globalDBdriver, $globalLiveInterval;
		if (!isset($globalLiveInterval)) $globalLiveInterval = '200';
		if ($globalDBdriver == 'mysql') {
            		$query  = "SELECT COUNT(*) as nb FROM spotter_live INNER JOIN (SELECT l.flightaware_id, max(l.date) as maxdate FROM spotter_live l WHERE DATE_SUB(UTC_TIMESTAMP(),INTERVAL ".$globalLiveInterval." SECOND) <= l.date GROUP BY l.flightaware_id) s on spotter_live.flightaware_id = s.flightaware_id AND spotter_live.date = s.maxdate";
            	} elseif ($globalDBdriver == 'pgsql') {
	                $query  = "SELECT COUNT(*) as nb FROM spotter_live INNER JOIN (SELECT l.flightaware_id, max(l.date) as maxdate FROM spotter_live l WHERE NOW() AT TIME ZONE 'UTC' - '".$globalLiveInterval." SECONDS'::INTERVAL <= l.date GROUP BY l.flightaware_id) s on spotter_live.flightaware_id = s.flightaware_id AND spotter_live.date = s.maxdate";
                }
    		try {
			$Connection = new Connection();
			$sth = Connection::$db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			return "error";
		}
		$result = $sth->fetch(PDO::FETCH_ASSOC);
		return $result['nb'];
	}

	/**
	* Gets all the spotter information based on the latest data entry and coord
	*
	* @return Array the spotter information
	*
	*/
	public static function getLiveSpotterDatabyCoord($coord)
	{
		global $globalDBdriver, $globalLiveInterval;
		if (!isset($globalLiveInterval)) $globalLiveInterval = '200';
		if (is_array($coord)) {
                        $minlong = filter_var($coord[0],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
                        $minlat = filter_var($coord[1],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
                        $maxlong = filter_var($coord[2],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
                        $maxlat = filter_var($coord[3],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
                } else return array();
                if ($globalDBdriver == 'mysql') {
        		//$query  = "SELECT spotter_output.* FROM spotter_output WHERE spotter_output.flightaware_id IN (SELECT spotter_live.flightaware_id FROM spotter_live INNER JOIN (SELECT l.flightaware_id, max(l.date) as maxdate FROM spotter_live l WHERE DATE_SUB(UTC_TIMESTAMP(),INTERVAL ".$globalLiveInterval." SECOND) <= l.date GROUP BY l.flightaware_id) s on spotter_live.flightaware_id = s.flightaware_id AND spotter_live.date = s.maxdate AND spotter_live.latitude BETWEEN ".$minlat." AND ".$maxlat." AND spotter_live.longitude BETWEEN ".$minlong." AND ".$maxlong.")";
        		$query  = "SELECT spotter_live.* FROM spotter_live INNER JOIN (SELECT l.flightaware_id, max(l.date) as maxdate FROM spotter_live l WHERE DATE_SUB(UTC_TIMESTAMP(),INTERVAL ".$globalLiveInterval." SECOND) <= l.date GROUP BY l.flightaware_id) s on spotter_live.flightaware_id = s.flightaware_id AND spotter_live.date = s.maxdate AND spotter_live.latitude BETWEEN ".$minlat." AND ".$maxlat." AND spotter_live.longitude BETWEEN ".$minlong." AND ".$maxlong." GROUP BY spotter_live.flightaware_id";
        	} else if ($globalDBdriver == 'pgsql') {
            		$query  = "SELECT spotter_live.* FROM spotter_live INNER JOIN (SELECT l.flightaware_id, max(l.date) as maxdate FROM spotter_live l WHERE NOW() at time zone 'UTC'  - '".$globalLiveInterval." SECONDS'::INTERVAL <= l.date GROUP BY l.flightaware_id) s on spotter_live.flightaware_id = s.flightaware_id AND spotter_live.date = s.maxdate AND spotter_live.latitude BETWEEN ".$minlat." AND ".$maxlat." AND spotter_live.longitude BETWEEN ".$minlong." AND ".$maxlong." GROUP BY spotter_live.flightaware_id";
                }
                $spotter_array = Spotter::getDataFromDB($query);
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
         $additional_query = ' AND DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 MINUTE) <= spotter_live.date ';   
        }

                $query  = "SELECT spotter_live.*, ( 6371 * acos( cos( radians(:lat) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(:lng) ) + sin( radians(:lat) ) * sin( radians( latitude ) ) ) ) AS distance FROM spotter_live 
                   WHERE spotter_live.latitude <> '' 
                                   AND spotter_live.longitude <> '' 
                   ".$additional_query."
                   HAVING distance < :radius  
                                   ORDER BY distance";

                $spotter_array = Spotter::getDataFromDB($query, array(':lat' => $lat, ':lng' => $lng,':radius' => $radius),$limit_query);

                return $spotter_array;
        }

    
        /**
	* Gets all the spotter information based on a particular callsign
	*
	* @return Array the spotter information
	*
	*/
	public static function getLastLiveSpotterDataByIdent($ident)
	{

		date_default_timezone_set('UTC');

		$ident = filter_var($ident, FILTER_SANITIZE_STRING);
                $query  = "SELECT spotter_live.* FROM spotter_live INNER JOIN (SELECT l.flightaware_id, max(l.date) as maxdate FROM spotter_live l WHERE l.ident = :ident GROUP BY l.flightaware_id) s on spotter_live.flightaware_id = s.flightaware_id AND spotter_live.date = s.maxdate ORDER BY spotter_live.date DESC";

		$spotter_array = Spotter::getDataFromDB($query,array(':ident' => $ident));

		return $spotter_array;
	}

        /**
	* Gets last spotter information based on a particular callsign
	*
	* @return Array the spotter information
	*
	*/
	public static function getLastLiveSpotterDataById($id)
	{

		date_default_timezone_set('UTC');

		$id = filter_var($id, FILTER_SANITIZE_STRING);
                $query  = "SELECT spotter_live.* FROM spotter_live INNER JOIN (SELECT l.flightaware_id, max(l.date) as maxdate FROM spotter_live l WHERE l.flightaware_id = :id GROUP BY l.flightaware_id) s on spotter_live.flightaware_id = s.flightaware_id AND spotter_live.date = s.maxdate ORDER BY spotter_live.date DESC";

		$spotter_array = Spotter::getDataFromDB($query,array(':id' => $id));

		return $spotter_array;
	}

        /**
	* Gets altitude information based on a particular callsign
	*
	* @return Array the spotter information
	*
	*/
	public static function getAltitudeLiveSpotterDataByIdent($ident)
	{

		date_default_timezone_set('UTC');

		$ident = filter_var($ident, FILTER_SANITIZE_STRING);
                $query  = "SELECT spotter_live.altitude, spotter_live.date FROM spotter_live WHERE spotter_live.ident = :ident";

    		try {
			$Connection = new Connection();
			$sth = Connection::$db->prepare($query);
			$sth->execute(array(':ident' => $ident));
		} catch(PDOException $e) {
			return "error";
		}
		$spotter_array = $sth->fetchAll(PDO::FETCH_ASSOC);

		return $spotter_array;
	}

        /**
	* Gets all the spotter information based on a particular id
	*
	* @return Array the spotter information
	*
	*/
	public static function getAllLiveSpotterDataById($id)
	{
		date_default_timezone_set('UTC');
		$id = filter_var($id, FILTER_SANITIZE_STRING);
		$query  = SpotterLive::$global_query." WHERE spotter_live.flightaware_id = :id";
//		$spotter_array = Spotter::getDataFromDB($query,array(':id' => $id));

    		try {
			$Connection = new Connection();
			$sth = Connection::$db->prepare($query);
			$sth->execute(array(':id' => $id));
		} catch(PDOException $e) {
			return "error";
		}
		$spotter_array = $sth->fetchAll(PDO::FETCH_ASSOC);

		return $spotter_array;
	}

        /**
	* Gets all the spotter information based on a particular ident
	*
	* @return Array the spotter information
	*
	*/
	public static function getAllLiveSpotterDataByIdent($ident)
	{
		date_default_timezone_set('UTC');
		$ident = filter_var($ident, FILTER_SANITIZE_STRING);
		$query  = SpotterLive::$global_query." WHERE spotter_live.ident = :ident";
    		try {
			$Connection = new Connection();
			$sth = Connection::$db->prepare($query);
			$sth->execute(array(':ident' => $ident));
		} catch(PDOException $e) {
			return "error";
		}
		$spotter_array = $sth->fetchAll(PDO::FETCH_ASSOC);
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
		global $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			//$query  = "DELETE FROM spotter_live WHERE DATE_SUB(UTC_TIMESTAMP(),INTERVAL 30 MINUTE) >= spotter_live.date";
			$query  = "DELETE FROM spotter_live WHERE DATE_SUB(UTC_TIMESTAMP(),INTERVAL 9 HOUR) >= spotter_live.date";
            		//$query  = "DELETE FROM spotter_live WHERE spotter_live.id IN (SELECT spotter_live.id FROM spotter_live INNER JOIN (SELECT l.flightaware_id, max(l.date) as maxdate FROM spotter_live l GROUP BY l.flightaware_id) s on spotter_live.flightaware_id = s.flightaware_id AND spotter_live.date = s.maxdate AND DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 HOUR) >= spotter_live.date)";

		} elseif ($globalDBdriver == 'pgsql') {
			$query  = "DELETE FROM spotter_live WHERE NOW() AT TIME ZONE 'UTC' - '9 HOUR'::INTERVAL >= spotter_live.date";
		}
        
    		try {
			$Connection = new Connection();
			$sth = Connection::$db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			return "error";
		}

		return "success";
	}

	/**
	* Deletes all info in the table for an ident
	*
	* @return String success or false
	*
	*/
	public static function deleteLiveSpotterDataByIdent($ident)
	{
		$ident = filter_var($ident, FILTER_SANITIZE_STRING);
		$query  = "DELETE FROM spotter_live WHERE `ident` = :ident";
        
    		try {
			$Connection = new Connection();
			$sth = Connection::$db->prepare($query);
			$sth->execute(array(':ident' => $ident));
		} catch(PDOException $e) {
			return "error";
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
		global $globalDBdriver, $globalTimezone;
		if ($globalDBdriver == 'mysql') {
			$query  = "SELECT spotter_live.ident FROM spotter_live 
				WHERE spotter_live.ident = :ident 
				AND spotter_live.date >= DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 HOUR) 
				AND spotter_live.date < UTC_TIMESTAMP()";
			$query_data = array(':ident' => $ident);
		} elseif ($globalDBdriver == 'pgsql') {
			$query  = "SELECT spotter_live.ident FROM spotter_live 
				WHERE spotter_live.ident = :ident 
				AND spotter_live.date >= now() AT TIME ZONE 'UTC' - '1 HOUR'::INTERVAL
				AND spotter_live.date < now() AT TIME ZONE 'UTC'";
			$query_data = array(':ident' => $ident);
		}
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute($query_data);
		$ident_result='';
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$ident_result = $row['ident'];
		}
		return $ident_result;
        }

	/**
	* Check recent aircraft
	*
	* @return String the ident
	*
	*/
	public static function checkIdentRecent($ident)
	{
		global $globalDBdriver, $globalTimezone;
		if ($globalDBdriver == 'mysql') {
			$query  = "SELECT spotter_live.ident, spotter_live.spotter_live_id FROM spotter_live 
				WHERE spotter_live.ident = :ident 
				AND spotter_live.date >= DATE_SUB(UTC_TIMESTAMP(),INTERVAL 30 MINUTE)"; 
//				AND spotter_live.date < UTC_TIMESTAMP()";
			$query_data = array(':ident' => $ident);
		} elseif ($globalDBdriver == 'pgsql') {
			$query  = "SELECT spotter_live.ident, spotter_live.spotter_live_id FROM spotter_live 
				WHERE spotter_live.ident = :ident 
				AND spotter_live.date >= now() AT TIME ZONE 'UTC' - '30 MINUTE'::INTERVAL";
//				AND spotter_live.date < now() AT TIME ZONE 'UTC'";
			$query_data = array(':ident' => $ident);
		}
		$Connection = new Connection();
		$sth = Connection::$db->prepare($query);
		$sth->execute($query_data);
		$ident_result='';
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$ident_result = $row['spotter_live_id'];
		}
		return $ident_result;
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
	public static function addLiveSpotterData($flightaware_id = '', $ident = '', $aircraft_icao = '', $departure_airport_icao = '', $arrival_airport_icao = '', $latitude = '', $longitude = '', $waypoints = '', $altitude = '', $heading = '', $groundspeed = '', $departure_airport_time = '', $arrival_airport_time = '', $squawk = '', $route_stop = '', $ModeS = '', $putinarchive = false,$registration = '',$pilot_id = '', $pilot_name = '', $verticalrate = '')
	{
		global $globalURL, $globalArchive, $globalDebug;

		date_default_timezone_set('UTC');

		$registration = '';
		//getting the registration
		

//		if ($ModeS != '') $registration = Spotter::getAircraftRegistrationBymodeS($ModeS);
		

		//getting the airline information
		if ($ident != "")
		{
			if (!is_string($ident))
			{
				return false;
			} 
			/*
			else {
				//if (!is_numeric(substr($ident, -1, 1)))
				if (!is_numeric(substr($ident, 0, 3)))
				{
					if (is_numeric(substr(substr($ident, 0, 3), -1, 1))) {
						$airline_array = Spotter::getAllAirlineInfo(substr($ident, 0, 2));
					} elseif (is_numeric(substr(substr($ident, 0, 4), -1, 1))) {
						$airline_array = Spotter::getAllAirlineInfo(substr($ident, 0, 3));
					} else {
						$airline_array = Spotter::getAllAirlineInfo("NA");
					}
					//print_r($airline_array);
					if (count($airline_array) == 0) {
					    $airline_array = Spotter::getAllAirlineInfo("NA");
					} elseif ($airline_array[0]['icao'] == ""){
					    $airline_array = Spotter::getAllAirlineInfo("NA");
					}

				} else {
					//echo "\n arg numeric : ".substr($ident, -1, 1)." - ".substr($ident, 0, 3)."\n";
					$airline_array = Spotter::getAllAirlineInfo("NA");
				}
			}
		*/
		}

		//getting the aircraft information
		if ($aircraft_icao != "")
		{
			if (!is_string($aircraft_icao))
			{
				return false;
			} 
			/*
			else {
				if ($aircraft_icao == "" || $aircraft_icao == "XXXX")
				{
					$aircraft_array = Spotter::getAllAircraftInfo("NA");
				} else {
					$aircraft_array = Spotter::getAllAircraftInfo($aircraft_icao);
				}
			}
			*/
		} 
		//getting the departure airport information
		if ($departure_airport_icao != "")
		{
			if (!is_string($departure_airport_icao))
			{
				return false;
			} 
			/*
			else {
				$departure_airport_array = Spotter::getAllAirportInfo($departure_airport_icao);
			}
			*/
		}

		//getting the arrival airport information
		if ($arrival_airport_icao != "")
		{
			if (!is_string($arrival_airport_icao))
			{
				return false;
			}
			/*
			
			 else {
				$arrival_airport_array = Spotter::getAllAirportInfo($arrival_airport_icao);
			}
			*/
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
		date_default_timezone_set('UTC');

		$date = date("Y-m-d H:i:s", time());

/*
		//getting the aircraft image
		if ($registration != "")
		{
			$image_array = Image::getSpotterImage($registration);
			if (!isset($image_array[0]['registration']))
			{
				Image::addSpotterImage($registration);
			}
		}
  */
        
		$flightaware_id = filter_var($flightaware_id,FILTER_SANITIZE_STRING);
		$ident = filter_var($ident,FILTER_SANITIZE_STRING);
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

/*
		if (!isset($airline_array) || count($airline_array) == 0) {
			$airline_array = Spotter::getAllAirlineInfo('NA');
		}
		if (!isset($aircraft_array) || count($aircraft_array) == 0) {
			$aircraft_array = Spotter::getAllAircraftInfo('NA');
            	}
            	if ($registration == '') $registration = 'NA';
		$airline_name = $airline_array[0]['name'];
		$airline_icao = $airline_array[0]['icao'];
		$airline_country = $airline_array[0]['country'];
		$airline_type = $airline_array[0]['type'];
		$aircraft_shadow = $aircraft_array[0]['aircraft_shadow'];
		$aircraft_type = $aircraft_array[0]['type'];
		$aircraft_manufacturer = $aircraft_array[0]['manufacturer'];
*/
		$airline_name = '';
		$airline_icao = '';
		$airline_country = '';
		$airline_type = '';
		$aircraft_shadow = '';
		$aircraft_type = '';
		$aircraft_manufacturer = '';



		$aircraft_name = '';
		if (isset($departure_airport_array[0])) {
			$departure_airport_name = $departure_airport_array[0]['name'];
			$departure_airport_city = $departure_airport_array[0]['city'];
			$departure_airport_country = $departure_airport_array[0]['country'];
		} else {
			$departure_airport_name = '';
			$departure_airport_city = '';
			$departure_airport_country = '';
		}
		if (isset($arrival_airport_array[0])) {
			$arrival_airport_name = $arrival_airport_array[0]['name'];
			$arrival_airport_city = $arrival_airport_array[0]['city'];
			$arrival_airport_country = $arrival_airport_array[0]['country'];
		} else {
			$arrival_airport_name = '';
			$arrival_airport_city = '';
			$arrival_airport_country = '';
		}
            	
		$query  = "INSERT INTO spotter_live (flightaware_id, ident, registration, airline_name, airline_icao, airline_country, airline_type, aircraft_icao, aircraft_shadow, aircraft_name, aircraft_manufacturer, departure_airport_icao, departure_airport_name, departure_airport_city, departure_airport_country, arrival_airport_icao, arrival_airport_name, arrival_airport_city, arrival_airport_country, latitude, longitude, waypoints, altitude, heading, ground_speed, date, departure_airport_time, arrival_airport_time, squawk, route_stop, ModeS, pilot_id, pilot_name, verticalrate) 
		VALUES (:flightaware_id,:ident,:registration,:airline_name,:airline_icao,:airline_country,:airline_type,:aircraft_icao,:aircraft_shadow,:aircraft_type,:aircraft_manufacturer,:departure_airport_icao,:departure_airport_name, :departure_airport_city, :departure_airport_country, :arrival_airport_icao, :arrival_airport_name, :arrival_airport_city, :arrival_airport_country, :latitude,:longitude,:waypoints,:altitude,:heading,:groundspeed,:date,:departure_airport_time,:arrival_airport_time,:squawk,:route_stop,:ModeS, :pilot_id, :pilot_name, :verticalrate)";

		$query_values = array(':flightaware_id' => $flightaware_id,':ident' => $ident, ':registration' => $registration,':airline_name' => $airline_name,':airline_icao' => $airline_icao,':airline_country' => $airline_country,':airline_type' => $airline_type,':aircraft_icao' => $aircraft_icao,':aircraft_shadow' => $aircraft_shadow,':aircraft_type' => $aircraft_type,':aircraft_manufacturer' => $aircraft_manufacturer,':departure_airport_icao' => $departure_airport_icao,':departure_airport_name' => $departure_airport_name,':departure_airport_city' => $departure_airport_city,':departure_airport_country' => $departure_airport_country,':arrival_airport_icao' => $arrival_airport_icao,':arrival_airport_name' => $arrival_airport_name,':arrival_airport_city' => $arrival_airport_city,':arrival_airport_country' => $arrival_airport_country,':latitude' => $latitude,':longitude' => $longitude, ':waypoints' => $waypoints,':altitude' => $altitude,':heading' => $heading,':groundspeed' => $groundspeed,':date' => $date, ':departure_airport_time' => $departure_airport_time,':arrival_airport_time' => $arrival_airport_time, ':squawk' => $squawk,':route_stop' => $route_stop,':ModeS' => $ModeS, ':pilot_id' => $pilot_id, ':pilot_name' => $pilot_name, ':verticalrate' => $verticalrate);
		//$query_values = array(':flightaware_id' => $flightaware_id,':ident' => $ident, ':registration' => $registration,':airline_name' => $airline_array[0]['name'],':airline_icao' => $airline_array[0]['icao'],':airline_country' => $airline_array[0]['country'],':airline_type' => $airline_array[0]['type'],':aircraft_icao' => $aircraft_icao,':aircraft_type' => $aircraft_array[0]['type'],':aircraft_manufacturer' => $aircraft_array[0]['manufacturer'],':departure_airport_icao' => $departure_airport_icao,':arrival_airport_icao' => $arrival_airport_icao,':latitude' => $latitude,':longitude' => $longitude, ':waypoints' => $waypoints,':altitude' => $altitude,':heading' => $heading,':groundspeed' => $groundspeed,':date' => $date);
		try {
			$Connection = new Connection();
			$sth = Connection::$db->prepare($query);
			$sth->execute($query_values);
//			Connection::$db = null;
                } catch(PDOException $e) {
                	return "error : ".$e->getMessage();
                }
		if (isset($globalArchive) && $globalArchive && $putinarchive) {
		    if ($globalDebug) echo "(Add to SBS archive : ";
		    $result =  SpotterArchive::addSpotterArchiveData($flightaware_id, $ident, $registration, $airline_name, $airline_icao, $airline_country, $airline_type, $aircraft_icao, $aircraft_shadow, $aircraft_name, $aircraft_manufacturer, $departure_airport_icao, $departure_airport_name, $departure_airport_city, $departure_airport_country, $departure_airport_time,$arrival_airport_icao, $arrival_airport_name, $arrival_airport_city, $arrival_airport_country, $arrival_airport_time, $route_stop, $date,$latitude, $longitude, $waypoints, $altitude, $heading, $groundspeed, $squawk, $ModeS, $pilot_id, $pilot_name);
		    if ($globalDebug) echo $result.')';
		}
		return "success";

	}

	public static function getOrderBy()
	{
		$orderby = array("aircraft_asc" => array("key" => "aircraft_asc", "value" => "Aircraft Type - ASC", "sql" => "ORDER BY spotter_live.aircraft_icao ASC"), "aircraft_desc" => array("key" => "aircraft_desc", "value" => "Aircraft Type - DESC", "sql" => "ORDER BY spotter_live.aircraft_icao DESC"),"manufacturer_asc" => array("key" => "manufacturer_asc", "value" => "Aircraft Manufacturer - ASC", "sql" => "ORDER BY spotter_live.aircraft_manufacturer ASC"), "manufacturer_desc" => array("key" => "manufacturer_desc", "value" => "Aircraft Manufacturer - DESC", "sql" => "ORDER BY spotter_live.aircraft_manufacturer DESC"),"airline_name_asc" => array("key" => "airline_name_asc", "value" => "Airline Name - ASC", "sql" => "ORDER BY spotter_live.airline_name ASC"), "airline_name_desc" => array("key" => "airline_name_desc", "value" => "Airline Name - DESC", "sql" => "ORDER BY spotter_live.airline_name DESC"), "ident_asc" => array("key" => "ident_asc", "value" => "Ident - ASC", "sql" => "ORDER BY spotter_live.ident ASC"), "ident_desc" => array("key" => "ident_desc", "value" => "Ident - DESC", "sql" => "ORDER BY spotter_live.ident DESC"), "airport_departure_asc" => array("key" => "airport_departure_asc", "value" => "Departure Airport - ASC", "sql" => "ORDER BY spotter_live.departure_airport_city ASC"), "airport_departure_desc" => array("key" => "airport_departure_desc", "value" => "Departure Airport - DESC", "sql" => "ORDER BY spotter_live.departure_airport_city DESC"), "airport_arrival_asc" => array("key" => "airport_arrival_asc", "value" => "Arrival Airport - ASC", "sql" => "ORDER BY spotter_live.arrival_airport_city ASC"), "airport_arrival_desc" => array("key" => "airport_arrival_desc", "value" => "Arrival Airport - DESC", "sql" => "ORDER BY spotter_live.arrival_airport_city DESC"), "date_asc" => array("key" => "date_asc", "value" => "Date - ASC", "sql" => "ORDER BY spotter_live.date ASC"), "date_desc" => array("key" => "date_desc", "value" => "Date - DESC", "sql" => "ORDER BY spotter_live.date DESC"));
		return $orderby;
	}

}


?>
