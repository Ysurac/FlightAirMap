<?php
class SpotterArchive {
    public $global_query = "SELECT spotter_archive.* FROM spotter_archive";
    public $db;

    function __construct($dbc = null) {
            $Connection = new Connection($dbc);
            $this->db = $Connection->db;
    }


    // Spotter_archive
    
    function addSpotterArchiveData($flightaware_id = '', $ident = '', $registration = '', $airline_name = '', $airline_icao = '', $airline_country = '', $airline_type = '', $aircraft_icao = '', $aircraft_shadow = '', $aircraft_name = '', $aircraft_manufacturer = '', $departure_airport_icao = '', $departure_airport_name = '', $departure_airport_city = '', $departure_airport_country = '', $departure_airport_time = '',$arrival_airport_icao = '', $arrival_airport_name = '', $arrival_airport_city ='', $arrival_airport_country = '', $arrival_airport_time = '', $route_stop = '', $date = '',$latitude = '', $longitude = '', $waypoints = '', $altitude = '', $heading = '', $ground_speed = '', $squawk = '', $ModeS = '', $pilot_id = '', $pilot_name = '',$verticalrate = '',$format_source = '', $source_name = '', $over_country = '') {
	require_once(dirname(__FILE__).'/class.Spotter.php');
        if ($over_country == '') {
	        $Spotter = new Spotter($this->db);
	        $data_country = $Spotter->getCountryFromLatitudeLongitude($latitude,$longitude);
		if (!empty($data_country)) $country = $data_country['iso2'];
		else $country = '';
	} else $country = $over_country;
	if ($airline_type == null) $airline_type ='';
	
	//if ($country == '') echo "\n".'************ UNKNOW COUNTRY ****************'."\n";
	//else echo "\n".'*/*/*/*/*/*/*/ Country : '.$country.' */*/*/*/*/*/*/*/*/'."\n";

	// Route is not added in spotter_archive
	$query  = "INSERT INTO spotter_archive (flightaware_id, ident, registration, airline_name, airline_icao, airline_country, airline_type, aircraft_icao, aircraft_shadow, aircraft_name, aircraft_manufacturer, departure_airport_icao, departure_airport_name, departure_airport_city, departure_airport_country, departure_airport_time,arrival_airport_icao, arrival_airport_name, arrival_airport_city, arrival_airport_country, arrival_airport_time, route_stop, date,latitude, longitude, waypoints, altitude, heading, ground_speed, squawk, ModeS, pilot_id, pilot_name, verticalrate,format_source,over_country,source_name)
                VALUES (:flightaware_id, :ident, :registration, :airline_name, :airline_icao, :airline_country, :airline_type, :aircraft_icao, :aircraft_shadow, :aircraft_name, :aircraft_manufacturer, :departure_airport_icao, :departure_airport_name, :departure_airport_city, :departure_airport_country, :departure_airport_time,:arrival_airport_icao, :arrival_airport_name, :arrival_airport_city, :arrival_airport_country, :arrival_airport_time, :route_stop, :date,:latitude, :longitude, :waypoints, :altitude, :heading, :ground_speed, :squawk, :ModeS, :pilot_id, :pilot_name, :verticalrate, :format_source, :over_country, :source_name)";

        $query_values = array(':flightaware_id' => $flightaware_id, ':ident' => $ident, ':registration' => $registration, ':airline_name' => $airline_name, ':airline_icao' => $airline_icao, ':airline_country' => $airline_country, ':airline_type' => $airline_type, ':aircraft_icao' => $aircraft_icao, ':aircraft_shadow' => $aircraft_shadow, ':aircraft_name' => $aircraft_name, ':aircraft_manufacturer' => $aircraft_manufacturer, ':departure_airport_icao' => $departure_airport_icao, ':departure_airport_name' => $departure_airport_name, ':departure_airport_city' => $departure_airport_city, ':departure_airport_country' => $departure_airport_country, ':departure_airport_time' => $departure_airport_time,':arrival_airport_icao' => $arrival_airport_icao, ':arrival_airport_name' => $arrival_airport_name, ':arrival_airport_city' => $arrival_airport_city, ':arrival_airport_country' => $arrival_airport_country, ':arrival_airport_time' => $arrival_airport_time, ':route_stop' => $route_stop, ':date' => $date,':latitude' => $latitude, ':longitude' => $longitude, ':waypoints' => $waypoints, ':altitude' => $altitude, ':heading' => $heading, ':ground_speed' => $ground_speed, ':squawk' => $squawk, ':ModeS' => $ModeS, ':pilot_id' => $pilot_id, ':pilot_name' => $pilot_name, ':verticalrate' => $verticalrate, ':format_source' => $format_source, ':over_country' => $country, ':source_name' => $source_name);
        try {
            $sth = $this->db->prepare($query);
            $sth->execute($query_values);
        } catch(PDOException $e) {
            return "error : ".$e->getMessage();
        }
        return "success";
    }


        /**
        * Gets all the spotter information based on a particular callsign
        *
        * @return Array the spotter information
        *
        */
        public function getLastArchiveSpotterDataByIdent($ident)
        {
		$Spotter = new Spotter($this->db);
                date_default_timezone_set('UTC');

                $ident = filter_var($ident, FILTER_SANITIZE_STRING);
                //$query  = "SELECT spotter_archive.* FROM spotter_archive INNER JOIN (SELECT l.flightaware_id, max(l.date) as maxdate FROM spotter_archive l WHERE l.ident = :ident GROUP BY l.flightaware_id) s on spotter_archive.flightaware_id = s.flightaware_id AND spotter_archive.date = s.maxdate LIMIT 1";
                $query  = "SELECT spotter_archive.* FROM spotter_archive WHERE ident = :ident ORDER BY date DESC LIMIT 1";

                $spotter_array = $Spotter->getDataFromDB($query,array(':ident' => $ident));

                return $spotter_array;
        }


        /**
        * Gets last the spotter information based on a particular id
        *
        * @return Array the spotter information
        *
        */
        public function getLastArchiveSpotterDataById($id)
        {
    		$Spotter = new Spotter($this->db);
                date_default_timezone_set('UTC');
                $id = filter_var($id, FILTER_SANITIZE_STRING);
                //$query  = SpotterArchive->$global_query." WHERE spotter_archive.flightaware_id = :id";
                //$query  = "SELECT spotter_archive.* FROM spotter_archive INNER JOIN (SELECT l.flightaware_id, max(l.date) as maxdate FROM spotter_archive l WHERE l.flightaware_id = :id GROUP BY l.flightaware_id) s on spotter_archive.flightaware_id = s.flightaware_id AND spotter_archive.date = s.maxdate LIMIT 1";
                $query  = "SELECT * FROM spotter_archive WHERE flightaware_id = :id ORDER BY date DESC LIMIT 1";

//              $spotter_array = Spotter->getDataFromDB($query,array(':id' => $id));
                  /*
                try {
                        $Connection = new Connection();
                        $sth = Connection->$db->prepare($query);
                        $sth->execute(array(':id' => $id));
                } catch(PDOException $e) {
                        return "error";
                }
                $spotter_array = $sth->fetchAll(PDO->FETCH_ASSOC);
                */
                $spotter_array = $Spotter->getDataFromDB($query,array(':id' => $id));

                return $spotter_array;
        }

        /**
        * Gets all the spotter information based on a particular id
        *
        * @return Array the spotter information
        *
        */
        public function getAllArchiveSpotterDataById($id)
        {
                date_default_timezone_set('UTC');
                $id = filter_var($id, FILTER_SANITIZE_STRING);
                $query  = $this->global_query." WHERE spotter_archive.flightaware_id = :id";

//              $spotter_array = Spotter->getDataFromDB($query,array(':id' => $id));

                try {
                        $sth = $this->db->prepare($query);
                        $sth->execute(array(':id' => $id));
                } catch(PDOException $e) {
                        return "error";
                }
                $spotter_array = $sth->fetchAll(PDO::FETCH_ASSOC);

                return $spotter_array;
        }


        /**
        * Gets altitude information based on a particular callsign
        *
        * @return Array the spotter information
        *
        */
        public function getAltitudeArchiveSpotterDataByIdent($ident)
        {

                date_default_timezone_set('UTC');

                $ident = filter_var($ident, FILTER_SANITIZE_STRING);
                $query  = "SELECT spotter_archive.altitude, spotter_archive.date FROM spotter_archive WHERE spotter_archive.ident = :ident";

                try {
                        $sth = $this->db->prepare($query);
                        $sth->execute(array(':ident' => $ident));
                } catch(PDOException $e) {
                        return "error";
                }
                $spotter_array = $sth->fetchAll(PDO::FETCH_ASSOC);

                return $spotter_array;
        }

        /**
        * Gets altitude information based on a particular id
        *
        * @return Array the spotter information
        *
        */
        public function getAltitudeArchiveSpotterDataById($id)
        {

                date_default_timezone_set('UTC');

                $id = filter_var($id, FILTER_SANITIZE_STRING);
                $query  = "SELECT spotter_archive.altitude, spotter_archive.date FROM spotter_archive WHERE spotter_archive.flightaware_id = :id";

                try {
                        $sth = $this->db->prepare($query);
                        $sth->execute(array(':id' => $id));
                } catch(PDOException $e) {
                        return "error";
                }
                $spotter_array = $sth->fetchAll(PDO::FETCH_ASSOC);

                return $spotter_array;
        }

        /**
        * Gets altitude & speed information based on a particular id
        *
        * @return Array the spotter information
        *
        */
        public function getAltitudeSpeedArchiveSpotterDataById($id)
        {

                date_default_timezone_set('UTC');

                $id = filter_var($id, FILTER_SANITIZE_STRING);
                $query  = "SELECT spotter_archive.altitude, spotter_archive.ground_speed, spotter_archive.date FROM spotter_archive WHERE spotter_archive.flightaware_id = :id";

                try {
                        $sth = $this->db->prepare($query);
                        $sth->execute(array(':id' => $id));
                } catch(PDOException $e) {
		        return "error : ".$e->getMessage();
                }
                $spotter_array = $sth->fetchAll(PDO::FETCH_ASSOC);

                return $spotter_array;
        }


        /**
        * Gets altitude information based on a particular callsign
        *
        * @return Array the spotter information
        *
        */
        public function getLastAltitudeArchiveSpotterDataByIdent($ident)
        {

                date_default_timezone_set('UTC');

                $ident = filter_var($ident, FILTER_SANITIZE_STRING);
                $query  = "SELECT spotter_archive.altitude, spotter_archive.date FROM spotter_archive INNER JOIN (SELECT l.flightaware_id, max(l.date) as maxdate FROM spotter_archive l WHERE l.ident = :ident GROUP BY l.flightaware_id) s on spotter_archive.flightaware_id = s.flightaware_id AND spotter_archive.date = s.maxdate LIMIT 1";
//                $query  = "SELECT spotter_archive.altitude, spotter_archive.date FROM spotter_archive WHERE spotter_archive.ident = :ident";

                try {
                        $sth = $this->db->prepare($query);
                        $sth->execute(array(':ident' => $ident));
                } catch(PDOException $e) {
                        return "error";
                }
                $spotter_array = $sth->fetchAll(PDO::FETCH_ASSOC);

                return $spotter_array;
        }



       /**
        * Gets all the archive spotter information
        *
        * @return Array the spotter information
        *
        */
        public function getSpotterArchiveData($ident,$flightaware_id,$date)
        {
    		$Spotter = new Spotter($this->db);
                $ident = filter_var($ident, FILTER_SANITIZE_STRING);
                $query  = "SELECT spotter_live.* FROM spotter_live INNER JOIN (SELECT l.flightaware_id, max(l.date) as maxdate FROM spotter_live l WHERE l.ident = :ident AND l.flightaware_id = :flightaware_id AND l.date LIKE :date GROUP BY l.flightaware_id) s on spotter_live.flightaware_id = s.flightaware_id AND spotter_live.date = s.maxdate";

                $spotter_array = $Spotter->getDataFromDB($query,array(':ident' => $ident,':flightaware_id' => $flightaware_id,':date' => $date.'%'));

                return $spotter_array;
        }
        
        public function deleteSpotterArchiveTrackData()
        {
		global $globalArchiveKeepTrackMonths;
                date_default_timezone_set('UTC');
		$query = 'DELETE FROM spotter_archive WHERE spotter_archive.date < DATE_SUB(UTC_TIMESTAMP(), INTERVAL '.$globalArchiveKeepTrackMonths.' MONTH)';
                try {
                        $sth = $this->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        return "error";
                }
	}

	 /**
        * Gets Minimal Live Spotter data
        *
        * @return Array the spotter information
        *
        */
        public function getMinLiveSpotterData($begindate,$enddate,$filter = array())
        {
                global $globalDBdriver, $globalLiveInterval;
                date_default_timezone_set('UTC');

                $filter_query = '';
                if (isset($filter['source']) && !empty($filter['source'])) {
                        $filter_query .= " AND format_source IN ('".implode("','",$filter['source'])."') ";
                }
                // FIXME : use spotter_output also
                if (isset($filter['airlines']) && !empty($filter['airlines'])) {
                        $filter_query .= " INNER JOIN (SELECT flightaware_id FROM spotter_archive_output WHERE spotter_archive_output.airline_icao IN ('".implode("','",$filter['airlines'])."')) so ON so.flightaware_id = spotter_archive.flightaware_id ";
                }
                if (isset($filter['airlinestype']) && !empty($filter['airlinestype'])) {
                        $filter_query .= " INNER JOIN (SELECT flightaware_id FROM spotter_archive_output WHERE spotter_archive_output.airline_type = '".$filter['airlinestype']."') sa ON sa.flightaware_id = spotter_archive.flightaware_id ";
                }
                if (isset($filter['source_aprs']) && !empty($filter['source_aprs'])) {
                        $filter_query = " AND format_source = 'aprs' AND source_name IN ('".implode("','",$filter['source_aprs'])."')";
                }

                //if (!isset($globalLiveInterval)) $globalLiveInterval = '200';
                if ($globalDBdriver == 'mysql') {
                        /*
                        $query  = 'SELECT a.aircraft_shadow, spotter_archive.ident, spotter_archive.flightaware_id, spotter_archive.aircraft_icao, spotter_archive.departure_airport_icao as departure_airport, spotter_archive.arrival_airport_icao as arrival_airport, spotter_archive.latitude, spotter_archive.longitude, spotter_archive.altitude, spotter_archive.heading, spotter_archive.ground_speed, spotter_archive.squawk 
                    		    FROM spotter_archive 
                    		    INNER JOIN (SELECT l.flightaware_id, max(l.date) as maxdate FROM spotter_archive l WHERE (l.date BETWEEN '."'".$begindate."'".' AND '."'".$enddate."'".') GROUP BY l.flightaware_id) s on spotter_archive.flightaware_id = s.flightaware_id AND spotter_archive.date = s.maxdate '.$filter_query.'LEFT JOIN (SELECT aircraft_shadow,icao FROM aircraft) a ON spotter_archive.aircraft_icao = a.icao';
			*/
			$query  = 'SELECT a.aircraft_shadow, spotter_archive.ident, spotter_archive.flightaware_id, spotter_archive.aircraft_icao, spotter_archive.departure_airport_icao as departure_airport, spotter_archive.arrival_airport_icao as arrival_airport, spotter_archive.latitude, spotter_archive.longitude, spotter_archive.altitude, spotter_archive.heading, spotter_archive.ground_speed, spotter_archive.squawk 
				    FROM spotter_archive 
				    INNER JOIN (SELECT l.flightaware_id, max(l.date) as maxdate 
						FROM spotter_archive l 
						WHERE (l.date BETWEEN DATE_SUB('."'".$begindate."'".',INTERVAL '.$globalLiveInterval.' SECOND) AND '."'".$begindate."'".') 
						GROUP BY l.flightaware_id) s on spotter_archive.flightaware_id = s.flightaware_id 
				    AND spotter_archive.date = s.maxdate '.$filter_query.'LEFT JOIN (SELECT aircraft_shadow,icao FROM aircraft) a ON spotter_archive.aircraft_icao = a.icao';
                } else if ($globalDBdriver == 'pgsql') {
                        $query  = 'SELECT spotter_archive.ident, spotter_archive.flightaware_id, spotter_archive.aircraft_icao, spotter_archive.departure_airport_icao as departure_airport, spotter_archive.arrival_airport_icao as arrival_airport, spotter_archive.latitude, spotter_archive.longitude, spotter_archive.altitude, spotter_archive.heading, spotter_archive.ground_speed, spotter_archive.squawk, a.aircraft_shadow FROM spotter_archive INNER JOIN (SELECT l.flightaware_id, max(l.date) as maxdate FROM spotter_archive l WHERE DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.$globalLiveInterval.' SECOND) <= l.date GROUP BY l.flightaware_id) s on spotter_archive.flightaware_id = s.flightaware_id AND spotter_archive.date = s.maxdate '.$filter_query.'INNER JOIN (SELECT * FROM aircraft) a on spotter_archive.aircraft_icao = a.icao';
                }
                //echo $query;
                try {
                        $sth = $this->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        return "error";
                }
                $spotter_array = $sth->fetchAll(PDO::FETCH_ASSOC);

                return $spotter_array;
        }

	 /**
        * Gets count Live Spotter data
        *
        * @return Array the spotter information
        *
        */
        public function getLiveSpotterCount($begindate,$enddate,$filter = array())
        {
                global $globalDBdriver, $globalLiveInterval;
                date_default_timezone_set('UTC');

                $filter_query = '';
                if (isset($filter['source']) && !empty($filter['source'])) {
                        $filter_query .= " AND format_source IN ('".implode("','",$filter['source'])."') ";
                }
                if (isset($filter['airlines']) && !empty($filter['airlines'])) {
                        $filter_query .= " INNER JOIN (SELECT flightaware_id FROM spotter_output WHERE spotter_output.airline_icao IN ('".implode("','",$filter['airlines'])."')) so ON so.flightaware_id = spotter_archive.flightaware_id ";
                }
                if (isset($filter['airlinestype']) && !empty($filter['airlinestype'])) {
                        $filter_query .= " INNER JOIN (SELECT flightaware_id FROM spotter_output WHERE spotter_output.airline_type = '".$filter['airlinestype']."') sa ON sa.flightaware_id = spotter_archive.flightaware_id ";
                }
                if (isset($filter['source_aprs']) && !empty($filter['source_aprs'])) {
                        $filter_query = " AND format_source = 'aprs' AND source_name IN ('".implode("','",$filter['source_aprs'])."')";
                }

                //if (!isset($globalLiveInterval)) $globalLiveInterval = '200';
                if ($globalDBdriver == 'mysql') {
			$query = 'SELECT COUNT(DISTINCT flightaware_id) as nb 
			FROM spotter_archive l 
			WHERE (l.date BETWEEN DATE_SUB('."'".$begindate."'".',INTERVAL '.$globalLiveInterval.' SECOND) AND '."'".$begindate."'".')'.$filter_query;

                } else if ($globalDBdriver == 'pgsql') {
			$query = 'SELECT COUNT(DISTINCT flightaware_id) as nb FROM spotter_archive l WHERE (l.date BETWEEN '."'".$begindate."' - INTERVAL '".$globalLiveInterval." SECONDS' AND "."'".$enddate."'".')'.$filter_query;
                }
                //echo $query;
                try {
                        $sth = $this->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        return "error";
                }
		$result = $sth->fetch(PDO::FETCH_ASSOC);
                return $result['nb'];

        }



	// Spotter_Archive_output
	
    /**
    * Gets all the spotter information
    *
    * @return Array the spotter information
    *
    */
    public function searchSpotterData($q = '', $registration = '', $aircraft_icao = '', $aircraft_manufacturer = '', $highlights = '', $airline_icao = '', $airline_country = '', $airline_type = '', $airport = '', $airport_country = '', $callsign = '', $departure_airport_route = '', $arrival_airport_route = '', $owner = '',$pilot_id = '',$pilot_name = '',$altitude = '', $date_posted = '', $limit = '', $sort = '', $includegeodata = '')
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
		    $additional_query .= "(spotter_archive_output.spotter_id like '%".$q_item."%') OR ";
		    $additional_query .= "(spotter_archive_output.aircraft_icao like '%".$q_item."%') OR ";
		    $additional_query .= "(spotter_archive_output.aircraft_name like '%".$q_item."%') OR ";
		    $additional_query .= "(spotter_archive_output.aircraft_manufacturer like '%".$q_item."%') OR ";
		    $additional_query .= "(spotter_archive_output.airline_icao like '%".$q_item."%') OR ";
		    $additional_query .= "(spotter_archive_output.airline_name like '%".$q_item."%') OR ";
		    $additional_query .= "(spotter_archive_output.airline_country like '%".$q_item."%') OR ";
		    $additional_query .= "(spotter_archive_output.departure_airport_icao like '%".$q_item."%') OR ";
		    $additional_query .= "(spotter_archive_output.departure_airport_name like '%".$q_item."%') OR ";
		    $additional_query .= "(spotter_archive_output.departure_airport_city like '%".$q_item."%') OR ";
		    $additional_query .= "(spotter_archive_output.departure_airport_country like '%".$q_item."%') OR ";
		    $additional_query .= "(spotter_archive_output.arrival_airport_icao like '%".$q_item."%') OR ";
		    $additional_query .= "(spotter_archive_output.arrival_airport_name like '%".$q_item."%') OR ";
		    $additional_query .= "(spotter_archive_output.arrival_airport_city like '%".$q_item."%') OR ";
		    $additional_query .= "(spotter_archive_output.arrival_airport_country like '%".$q_item."%') OR ";
		    $additional_query .= "(spotter_archive_output.registration like '%".$q_item."%') OR ";
		    $additional_query .= "(spotter_archive_output.owner_name like '%".$q_item."%') OR ";
		    $additional_query .= "(spotter_archive_output.pilot_id like '%".$q_item."%') OR ";
		    $additional_query .= "(spotter_archive_output.pilot_name like '%".$q_item."%') OR ";
		    $additional_query .= "(spotter_archive_output.ident like '%".$q_item."%') OR ";
		    $additional_query .= "(spotter_archive_output.highlight like '%".$q_item."%')";
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
		$additional_query .= " AND (spotter_archive_output.registration = '".$registration."')";
	    }
	}
	
	if ($aircraft_icao != "")
	{
	    if (!is_string($aircraft_icao))
	    {
		return false;
	    } else {
		$additional_query .= " AND (spotter_archive_output.aircraft_icao = '".$aircraft_icao."')";
	    }
	}
	
	if ($aircraft_manufacturer != "")
	{
	    if (!is_string($aircraft_manufacturer))
	    {
		return false;
	    } else {
		$additional_query .= " AND (spotter_archive_output.aircraft_manufacturer = '".$aircraft_manufacturer."')";
	    }
	}
	
	if ($highlights == "true")
	{
	    if (!is_string($highlights))
	    {
		return false;
	    } else {
		$additional_query .= " AND (spotter_archive_output.highlight <> '')";
	    }
	}
	
	if ($airline_icao != "")
	{
	    if (!is_string($airline_icao))
	    {
		return false;
	    } else {
		$additional_query .= " AND (spotter_archive_output.airline_icao = '".$airline_icao."')";
	    }
	}
	
	if ($airline_country != "")
	{
	    if (!is_string($airline_country))
	    {
		return false;
	    } else {
		$additional_query .= " AND (spotter_archive_output.airline_country = '".$airline_country."')";
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
		    $additional_query .= " AND (spotter_archive_output.airline_type = 'passenger')";
		}
		if ($airline_type == "cargo")
		{
		    $additional_query .= " AND (spotter_archive_output.airline_type = 'cargo')";
		}
		if ($airline_type == "military")
		{
		    $additional_query .= " AND (spotter_archive_output.airline_type = 'military')";
		}
	    }
	}
	
	if ($airport != "")
	{
	    if (!is_string($airport))
	    {
		return false;
	    } else {
		$additional_query .= " AND ((spotter_archive_output.departure_airport_icao = '".$airport."') OR (spotter_archive_output.arrival_airport_icao = '".$airport."'))";
	    }
	}
	
	if ($airport_country != "")
	{
	    if (!is_string($airport_country))
	    {
		return false;
	    } else {
		$additional_query .= " AND ((spotter_archive_output.departure_airport_country = '".$airport_country."') OR (spotter_archive_output.arrival_airport_country = '".$airport_country."'))";
	    }
	}
    
	if ($callsign != "")
	{
	    if (!is_string($callsign))
	    {
		return false;
	    } else {
		$additional_query .= " AND (spotter_archive_output.ident = '".$callsign."')";
	    }
	}

	if ($owner != "")
	{
	    if (!is_string($owner))
	    {
		return false;
	    } else {
		$additional_query .= " AND (spotter_archive_output.owner_name = '".$owner."')";
	    }
	}

	if ($pilot_name != "")
	{
	    if (!is_string($pilot_name))
	    {
		return false;
	    } else {
		$additional_query .= " AND (spotter_archive_output.pilot_name = '".$pilot_name."')";
	    }
	}
	
	if ($pilot_id != "")
	{
	    if (!is_string($pilot_id))
	    {
		return false;
	    } else {
		$additional_query .= " AND (spotter_archive_output.pilot_id = '".$pilot_id."')";
	    }
	}
	
	if ($departure_airport_route != "")
	{
	    if (!is_string($departure_airport_route))
	    {
		return false;
	    } else {
		$additional_query .= " AND (spotter_archive_output.departure_airport_icao = '".$departure_airport_route."')";
	    }
	}
	
	if ($arrival_airport_route != "")
	{
	    if (!is_string($arrival_airport_route))
	    {
		return false;
	    } else {
		$additional_query .= " AND (spotter_archive_output.arrival_airport_icao = '".$arrival_airport_route."')";
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
		$additional_query .= " AND TIMESTAMP(CONVERT_TZ(spotter_archive_output.date,'+00:00', '".$offset."')) >= '".$date_array[0]."' AND TIMESTAMP(CONVERT_TZ(spotter_archive_output.date,'+00:00', '".$offset."')) <= '".$date_array[1]."' ";
	    } else {
		$date_array[0] = date("Y-m-d H:i:s", strtotime($date_array[0]));
              
		$additional_query .= " AND TIMESTAMP(CONVERT_TZ(spotter_archive_output.date,'+00:00', '".$offset."')) >= '".$date_array[0]."' ";
              
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
	    $orderby_query = " ORDER BY spotter_archive_output.date DESC";
	}
	
	if ($includegeodata == "true")
	{
	    $additional_query .= " AND (spotter_archive_output.waypoints <> '')";
	}

	$query  = "SELECT spotter_archive_output.* FROM spotter_archive_output 
		    WHERE spotter_archive_output.ident <> '' 
		    ".$additional_query."
		    ".$orderby_query;

	$Spotter = new Spotter($this->db);
	$spotter_array = $Spotter->getDataFromDB($query, array(),$limit_query);

	return $spotter_array;
    }

    public function deleteSpotterArchiveData()
    {
		global $globalArchiveKeepMonths, $globalDBdriver;
                date_default_timezone_set('UTC');
                if ($globalDBdriver == 'mysql') {
			$query = 'DELETE FROM spotter_archive_output WHERE spotter_archive_output.date < DATE_SUB(UTC_TIMESTAMP(), INTERVAL '.$globalArchiveKeepMonths.' MONTH)';
		} else {
			$query = "DELETE FROM spotter_archive_output WHERE spotter_archive_output.date < CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$globalArchiveKeepMonths." MONTH'";
		}
                try {
                        $sth = $this->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        return "error";
                }
	}

    /**
    * Gets all the spotter information based on the callsign
    *
    * @return Array the spotter information
    *
    */
    public function getSpotterDataByIdent($ident = '', $limit = '', $sort = '')
    {
	$global_query = "SELECT spotter_archive_output.* FROM spotter_archive_output";
	
	date_default_timezone_set('UTC');
	
	$query_values = array();
	
	if ($ident != "")
	{
	    if (!is_string($ident))
	    {
		return false;
	    } else {
		$additional_query = " AND (spotter_archive_output.ident = :ident)";
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
	    $orderby_query = " ORDER BY spotter_archive_output.date DESC";
	}

	$query = $global_query." WHERE spotter_archive_output.ident <> '' ".$additional_query." ".$orderby_query;

	$Spotter = new Spotter($this->db);
	$spotter_array = $Spotter->getDataFromDB($query, $query_values, $limit_query);

	return $spotter_array;
    }

    /**
    * Gets all number of flight over countries
    *
    * @return Array the airline country list
    *
    */
    public function countAllFlightOverCountries($limit = true,$olderthanmonths = 0,$sincedate = '')
    {
	global $globalDBdriver;
	/*
	$query = "SELECT c.name, c.iso3, c.iso2, count(c.name) as nb 
		    FROM countries c, spotter_archive s
		    WHERE Within(GeomFromText(CONCAT('POINT(',s.longitude,' ',s.latitude,')')), ogc_geom) ";
	*/
	$query = "SELECT c.name, c.iso3, c.iso2, count(c.name) as nb
		    FROM countries c, spotter_archive s
		    WHERE c.iso2 = s.over_country ";
                if ($olderthanmonths > 0) {
            		if ($globalDBdriver == 'mysql') {
				$query .= 'AND date < DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.$olderthanmonths.' MONTH) ';
			} else {
				$query .= "AND date < CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$olderthanmonths." MONTHS'";
			}
		}
                if ($sincedate != '') $query .= "AND date > '".$sincedate."' ";
	$query .= "GROUP BY c.name, c.iso3, c.iso2 ORDER BY nb DESC";
	if ($limit) $query .= " LIMIT 0,10";
      
	
	$sth = $this->db->prepare($query);
	$sth->execute();
 
	$flight_array = array();
	$temp_array = array();
        
	while($row = $sth->fetch(PDO::FETCH_ASSOC))
	{
	    $temp_array['flight_count'] = $row['nb'];
	    $temp_array['flight_country'] = $row['name'];
	    $temp_array['flight_country_iso3'] = $row['iso3'];
	    $temp_array['flight_country_iso2'] = $row['iso2'];
	    $flight_array[] = $temp_array;
	}
	return $flight_array;
    }

}
?>