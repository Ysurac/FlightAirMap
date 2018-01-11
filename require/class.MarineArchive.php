<?php
/**
 * This class is part of FlightAirmap. It's used for marine archive data
 *
 * Copyright (c) Ycarus (Yannick Chabanois) <support@flightairmap.com>
 * Licensed under AGPL license.
 * For more information see: https://www.flightairmap.com/
*/
class MarineArchive {
	public $global_query = "SELECT marine_archive.* FROM marine_archive";
	public $db;

	public function __construct($dbc = null) {
		$Connection = new Connection($dbc);
		$this->db = $Connection->db;
		if ($this->db === null) die('Error: No DB connection. (MarineArchive)');
	}

    /**
     * Get SQL query part for filter used
     * @param array $filter the filter
     * @param bool $where
     * @param bool $and
     * @return string the SQL part
     */
	public function getFilter($filter = array(),$where = false,$and = false) {
		global $globalFilter, $globalStatsFilters, $globalFilterName, $globalDBdriver;
		$filters = array();
		if (is_array($globalStatsFilters) && isset($globalStatsFilters[$globalFilterName])) {
			if (isset($globalStatsFilters[$globalFilterName][0]['source'])) {
				$filters = $globalStatsFilters[$globalFilterName];
			} else {
				$filter = array_merge($filter,$globalStatsFilters[$globalFilterName]);
			}
		}
		if (isset($filter[0]['source'])) {
			$filters = array_merge($filters,$filter);
		}
		if (is_array($globalFilter)) $filter = array_merge($filter,$globalFilter);
		$filter_query_join = '';
		$filter_query_where = '';
		foreach($filters as $flt) {
			if (isset($flt['idents']) && !empty($flt['idents'])) {
				if (isset($flt['source'])) {
					$filter_query_join .= " INNER JOIN (SELECT fammarine_id FROM marine_archive_output WHERE marine_archive_output.ident IN ('".implode("','",$flt['idents'])."') AND marine_archive_output.format_source IN ('".implode("','",$flt['source'])."')) spid ON spid.fammarine_id = marine_archive.fammarine_id";
				} else {
					$filter_query_join .= " INNER JOIN (SELECT fammarine_id FROM marine_archive_output WHERE marine_archive_output.ident IN ('".implode("','",$flt['idents'])."')) spid ON spid.fammarine_id = marine_archive.fammarine_id";
				}
			}
		}
		if (isset($filter['source']) && !empty($filter['source'])) {
			$filter_query_where .= " AND format_source IN ('".implode("','",$filter['source'])."')";
		}
		if (isset($filter['ident']) && !empty($filter['ident'])) {
			$filter_query_where .= " AND ident = '".$filter['ident']."'";
		}
		if ((isset($filter['year']) && $filter['year'] != '') || (isset($filter['month']) && $filter['month'] != '') || (isset($filter['day']) && $filter['day'] != '')) {
			$filter_query_date = '';
			if (isset($filter['year']) && $filter['year'] != '') {
				if ($globalDBdriver == 'mysql') {
					$filter_query_date .= " AND YEAR(marine_archive_output.date) = '".$filter['year']."'";
				} else {
					$filter_query_date .= " AND EXTRACT(YEAR FROM marine_archive_output.date) = '".$filter['year']."'";
				}
			}
			if (isset($filter['month']) && $filter['month'] != '') {
				if ($globalDBdriver == 'mysql') {
					$filter_query_date .= " AND MONTH(marine_archive_output.date) = '".$filter['month']."'";
				} else {
					$filter_query_date .= " AND EXTRACT(MONTH FROM marine_archive_output.date) = '".$filter['month']."'";
				}
			}
			if (isset($filter['day']) && $filter['day'] != '') {
				if ($globalDBdriver == 'mysql') {
					$filter_query_date .= " AND DAY(marine_archive_output.date) = '".$filter['day']."'";
				} else {
					$filter_query_date .= " AND EXTRACT(DAY FROM marine_archive_output.date) = '".$filter['day']."'";
				}
			}
			$filter_query_join .= " INNER JOIN (SELECT fammarine_id FROM marine_archive_output".preg_replace('/^ AND/',' WHERE',$filter_query_date).") sd ON sd.fammarine_id = marine_archive.fammarine_id";
		}
		if (isset($filter['source_aprs']) && !empty($filter['source_aprs'])) {
			$filter_query_where .= " AND format_source = 'aprs' AND source_name IN ('".implode("','",$filter['source_aprs'])."')";
		}
		if ($filter_query_where == '' && $where) $filter_query_where = ' WHERE';
		elseif ($filter_query_where != '' && $and) $filter_query_where .= ' AND';
		if ($filter_query_where != '') {
			$filter_query_where = preg_replace('/^ AND/',' WHERE',$filter_query_where);
		}
		$filter_query = $filter_query_join.$filter_query_where;
		return $filter_query;
	}

    /**
     * Add to Mariche archive
     *
     * @param string $fammarine_id
     * @param string $ident
     * @param string $latitude
     * @param string $longitude
     * @param string $heading
     * @param string $groundspeed
     * @param string $date
     * @param bool $putinarchive
     * @param string $mmsi
     * @param string $type
     * @param string $typeid
     * @param string $imo
     * @param string $callsign
     * @param string $arrival_code
     * @param string $arrival_date
     * @param string $status
     * @param string $statusid
     * @param bool $noarchive
     * @param string $format_source
     * @param string $source_name
     * @param string $over_country
     * @param string $captain_id
     * @param string $captain_name
     * @param string $race_id
     * @param string $race_name
     * @param string $distance
     * @param string $race_rank
     * @param string $race_time
     * @return string
     */
    public function addMarineArchiveData($fammarine_id = '', $ident = '', $latitude = '', $longitude = '', $heading = '', $groundspeed = '', $date = '', $putinarchive = false, $mmsi = '', $type = '', $typeid = '', $imo = '', $callsign = '', $arrival_code = '', $arrival_date = '', $status = '', $statusid = '', $noarchive = false, $format_source = '', $source_name = '', $over_country = '', $captain_id = '', $captain_name = '', $race_id = '', $race_name = '', $distance = '', $race_rank = '', $race_time = '') {
		require_once(dirname(__FILE__).'/class.Marine.php');
		if ($over_country == '') {
			$Marine = new Marine($this->db);
			$data_country = $Marine->getCountryFromLatitudeLongitude($latitude,$longitude);
			if (!empty($data_country)) $country = $data_country['iso2'];
			else $country = '';
		} else $country = $over_country;
		
		//$country = $over_country;
		// Route is not added in marine_archive
		$query  = 'INSERT INTO marine_archive (fammarine_id, ident, latitude, longitude, heading, ground_speed, date, format_source, source_name, over_country, mmsi, type,type_id,status,status_id,imo,arrival_port_name,arrival_port_date,captain_id,captain_name,race_id,race_name,distance,race_rank,race_time) 
		    VALUES (:fammarine_id,:ident,:latitude,:longitude,:heading,:groundspeed,:date,:format_source, :source_name, :over_country,:mmsi,:type,:type_id,:status,:status_id,:imo,:arrival_port_name,:arrival_port_date,:captain_id,:captain_name,:race_id,:race_name,:distance,:race_rank,:race_time)';
		$query_values = array(':fammarine_id' => $fammarine_id,':ident' => $ident,':latitude' => $latitude,':longitude' => $longitude,':heading' => $heading,':groundspeed' => $groundspeed,':date' => $date, ':format_source' => $format_source, ':source_name' => $source_name, ':over_country' => $country,':mmsi' => $mmsi,':type' => $type,':type_id' => $typeid,':status' => $status,':status_id' => $statusid,':imo' => $imo,':arrival_port_name' => $arrival_code,':arrival_port_date' => $arrival_date,':captain_id' => $captain_id,':captain_name' => $captain_name,':race_id' => $race_id,':race_name' => $race_name,':distance' => $distance,':race_rank' => $race_rank,':race_time' => $race_time);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
			$sth->closeCursor();
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
		return "success";
	}


    /**
     * Gets all the spotter information based on a particular callsign
     *
     * @param $ident
     * @return array the spotter information
     */
    public function getLastArchiveMarineDataByIdent($ident)
    {
	    $Marine = new Marine($this->db);
        date_default_timezone_set('UTC');

        $ident = filter_var($ident, FILTER_SANITIZE_STRING);
        //$query  = "SELECT marine_archive.* FROM marine_archive INNER JOIN (SELECT l.fammarine_id, max(l.date) as maxdate FROM marine_archive l WHERE l.ident = :ident GROUP BY l.fammarine_id) s on marine_archive.fammarine_id = s.fammarine_id AND marine_archive.date = s.maxdate LIMIT 1";
        $query  = "SELECT marine_archive.* FROM marine_archive WHERE ident = :ident ORDER BY date DESC LIMIT 1";
        $spotter_array = $Marine->getDataFromDB($query,array(':ident' => $ident));
        return $spotter_array;
    }


    /**
     * Gets last the spotter information based on a particular id
     *
     * @param $id
     * @return array the spotter information
     */
    public function getLastArchiveMarineDataById($id)
    {
        $Marine = new Marine($this->db);
        date_default_timezone_set('UTC');
        $id = filter_var($id, FILTER_SANITIZE_STRING);
        //$query  = MarineArchive->$global_query." WHERE marine_archive.fammarine_id = :id";
        //$query  = "SELECT marine_archive.* FROM marine_archive INNER JOIN (SELECT l.fammarine_id, max(l.date) as maxdate FROM marine_archive l WHERE l.fammarine_id = :id GROUP BY l.fammarine_id) s on marine_archive.fammarine_id = s.fammarine_id AND marine_archive.date = s.maxdate LIMIT 1";
        $query  = "SELECT * FROM marine_archive WHERE fammarine_id = :id ORDER BY date DESC LIMIT 1";

//              $spotter_array = Marine->getDataFromDB($query,array(':id' => $id));
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
        $spotter_array = $Marine->getDataFromDB($query,array(':id' => $id));
        return $spotter_array;
    }

    /**
     * Gets all the spotter information based on a particular id
     *
     * @param $id
     * @return array the spotter information
     */
    public function getAllArchiveMarineDataById($id)
	{
        date_default_timezone_set('UTC');
        $id = filter_var($id, FILTER_SANITIZE_STRING);
        $query  = $this->global_query." WHERE marine_archive.fammarine_id = :id ORDER BY date";

//              $spotter_array = Marine->getDataFromDB($query,array(':id' => $id));

        try {
            $sth = $this->db->prepare($query);
            $sth->execute(array(':id' => $id));
        } catch(PDOException $e) {
            echo $e->getMessage();
            die;
        }
        $spotter_array = $sth->fetchAll(PDO::FETCH_ASSOC);
        return $spotter_array;
	}

    /**
     * Gets coordinate & time spotter information based on a particular id
     *
     * @param $id
     * @return array the spotter information
     */
    public function getCoordArchiveMarineDataById($id)
    {
        date_default_timezone_set('UTC');
        $id = filter_var($id, FILTER_SANITIZE_STRING);
        $query  = "SELECT marine_archive.latitude, marine_archive.longitude, marine_archive.date FROM marine_archive WHERE marine_archive.fammarine_id = :id";

//              $spotter_array = Marine->getDataFromDB($query,array(':id' => $id));

        try {
            $sth = $this->db->prepare($query);
            $sth->execute(array(':id' => $id));
        } catch(PDOException $e) {
            echo $e->getMessage();
            die;
        }
        $spotter_array = $sth->fetchAll(PDO::FETCH_ASSOC);

        return $spotter_array;
    }


    /**
     * Gets altitude information based on a particular callsign
     *
     * @param $ident
     * @return array the spotter information
     */
    public function getAltitudeArchiveMarineDataByIdent($ident)
    {

        date_default_timezone_set('UTC');

        $ident = filter_var($ident, FILTER_SANITIZE_STRING);
        $query  = "SELECT marine_archive.altitude, marine_archive.date FROM marine_archive WHERE marine_archive.ident = :ident AND marine_archive.latitude <> 0 AND marine_archive.longitude <> 0 ORDER BY date";

        try {
            $sth = $this->db->prepare($query);
            $sth->execute(array(':ident' => $ident));
        } catch(PDOException $e) {
            echo $e->getMessage();
            die;
        }
        $spotter_array = $sth->fetchAll(PDO::FETCH_ASSOC);

        return $spotter_array;
    }

    /**
     * Gets altitude information based on a particular id
     *
     * @param $id
     * @return array the spotter information
     */
    public function getAltitudeArchiveMarineDataById($id)
    {

        date_default_timezone_set('UTC');

        $id = filter_var($id, FILTER_SANITIZE_STRING);
        $query  = "SELECT marine_archive.altitude, marine_archive.date FROM marine_archive WHERE marine_archive.fammarine_id = :id AND marine_archive.latitude <> 0 AND marine_archive.longitude <> 0 ORDER BY date";

        try {
            $sth = $this->db->prepare($query);
            $sth->execute(array(':id' => $id));
        } catch(PDOException $e) {
            echo $e->getMessage();
            die;
        }
        $spotter_array = $sth->fetchAll(PDO::FETCH_ASSOC);

        return $spotter_array;
    }

    /**
     * Gets altitude & speed information based on a particular id
     *
     * @param $id
     * @return array the spotter information
     */
    public function getAltitudeSpeedArchiveMarineDataById($id)
    {
        date_default_timezone_set('UTC');

        $id = filter_var($id, FILTER_SANITIZE_STRING);
        $query  = "SELECT marine_archive.altitude, marine_archive.ground_speed, marine_archive.date FROM marine_archive WHERE marine_archive.fammarine_id = :id ORDER BY date";

        try {
            $sth = $this->db->prepare($query);
            $sth->execute(array(':id' => $id));
        } catch(PDOException $e) {
            echo $e->getMessage();
            die;
        }
        $spotter_array = $sth->fetchAll(PDO::FETCH_ASSOC);

        return $spotter_array;
    }


    /**
     * Gets altitude information based on a particular callsign
     *
     * @param $ident
     * @return array the spotter information
     */
    public function getLastAltitudeArchiveMarineDataByIdent($ident)
    {

        date_default_timezone_set('UTC');

        $ident = filter_var($ident, FILTER_SANITIZE_STRING);
        $query  = "SELECT marine_archive.altitude, marine_archive.date FROM marine_archive INNER JOIN (SELECT l.fammarine_id, max(l.date) as maxdate FROM marine_archive l WHERE l.ident = :ident GROUP BY l.fammarine_id) s on marine_archive.fammarine_id = s.fammarine_id AND marine_archive.date = s.maxdate LIMIT 1";
//                $query  = "SELECT marine_archive.altitude, marine_archive.date FROM marine_archive WHERE marine_archive.ident = :ident";

        try {
            $sth = $this->db->prepare($query);
            $sth->execute(array(':ident' => $ident));
        } catch(PDOException $e) {
            echo $e->getMessage();
            die;
        }
        $spotter_array = $sth->fetchAll(PDO::FETCH_ASSOC);

        return $spotter_array;
    }


    /**
     * Gets all the archive spotter information
     *
     * @param $ident
     * @param $fammarine_id
     * @param $date
     * @return array the spotter information
     */
    public function getMarineArchiveData($ident,$fammarine_id,$date)
    {
        $Marine = new Marine($this->db);
        $ident = filter_var($ident, FILTER_SANITIZE_STRING);
        $query  = "SELECT spotter_live.* FROM spotter_live INNER JOIN (SELECT l.fammarine_id, max(l.date) as maxdate FROM spotter_live l WHERE l.ident = :ident AND l.fammarine_id = :fammarine_id AND l.date LIKE :date GROUP BY l.fammarine_id) s on spotter_live.fammarine_id = s.fammarine_id AND spotter_live.date = s.maxdate";
        $spotter_array = $Marine->getDataFromDB($query,array(':ident' => $ident,':fammarine_id' => $fammarine_id,':date' => $date.'%'));
        return $spotter_array;
    }

    /**
     * Delete all tracking data
     *
     */
    public function deleteMarineArchiveTrackData()
    {
        global $globalArchiveKeepTrackMonths, $globalDBdriver;
        if ($globalDBdriver == 'mysql') {
            $query = 'DELETE FROM marine_archive WHERE marine_archive.date < DATE_SUB(UTC_TIMESTAMP(), INTERVAL '.$globalArchiveKeepTrackMonths.' MONTH)';
        } else {
            $query = "DELETE FROM marine_archive WHERE marine_archive_id IN (SELECT marine_archive_id FROM marine_archive WHERE marine_archive.date < CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$globalArchiveKeepTrackMonths." MONTH' LIMIT 10000)";
		}
		try {
			$sth = $this->db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			echo $e->getMessage();
			die;
		}
	}

    /**
     * Gets Minimal Live Marine data
     *
     * @param $begindate
     * @param $enddate
     * @param array $filter
     * @return array the spotter information
     */
    public function getMinLiveMarineData($begindate,$enddate,$filter = array())
    {
        global $globalDBdriver;
        date_default_timezone_set('UTC');

        $filter_query = '';
        if (isset($filter['source']) && !empty($filter['source'])) {
            $filter_query .= " AND format_source IN ('".implode("','",$filter['source'])."') ";
        }
        // Use spotter_output also ?
        if (isset($filter['airlines']) && !empty($filter['airlines'])) {
            $filter_query .= " INNER JOIN (SELECT fammarine_id FROM marine_archive_output WHERE marine_archive_output.airline_icao IN ('".implode("','",$filter['airlines'])."')) so ON so.fammarine_id = marine_archive.fammarine_id ";
        }
        if (isset($filter['airlinestype']) && !empty($filter['airlinestype'])) {
            $filter_query .= " INNER JOIN (SELECT fammarine_id FROM marine_archive_output WHERE marine_archive_output.airline_type = '".$filter['airlinestype']."') sa ON sa.fammarine_id = marine_archive.fammarine_id ";
        }
        if (isset($filter['source_aprs']) && !empty($filter['source_aprs'])) {
            $filter_query = " AND format_source = 'aprs' AND source_name IN ('".implode("','",$filter['source_aprs'])."')";
        }

        //if (!isset($globalLiveInterval)) $globalLiveInterval = '200';
        if ($globalDBdriver == 'mysql') {
            /*
                        $query  = 'SELECT a.aircraft_shadow, marine_archive.ident, marine_archive.fammarine_id, marine_archive.aircraft_icao, marine_archive.departure_airport_icao as departure_airport, marine_archive.arrival_airport_icao as arrival_airport, marine_archive.latitude, marine_archive.longitude, marine_archive.altitude, marine_archive.heading, marine_archive.ground_speed, marine_archive.squawk 
                    		    FROM marine_archive 
                    		    INNER JOIN (SELECT l.fammarine_id, max(l.date) as maxdate FROM marine_archive l WHERE (l.date BETWEEN '."'".$begindate."'".' AND '."'".$enddate."'".') GROUP BY l.fammarine_id) s on marine_archive.fammarine_id = s.fammarine_id AND marine_archive.date = s.maxdate '.$filter_query.'LEFT JOIN (SELECT aircraft_shadow,icao FROM aircraft) a ON marine_archive.aircraft_icao = a.icao';
			*/
            /*
			$query  = 'SELECT a.aircraft_shadow, marine_archive.ident, marine_archive.fammarine_id, marine_archive.aircraft_icao, marine_archive.departure_airport_icao as departure_airport, marine_archive.arrival_airport_icao as arrival_airport, marine_archive.latitude, marine_archive.longitude, marine_archive.altitude, marine_archive.heading, marine_archive.ground_speed, marine_archive.squawk 
				    FROM marine_archive 
				    INNER JOIN (SELECT l.fammarine_id, max(l.date) as maxdate 
						FROM marine_archive l 
						WHERE (l.date BETWEEN DATE_SUB('."'".$begindate."'".',INTERVAL '.$globalLiveInterval.' SECOND) AND '."'".$begindate."'".') 
						GROUP BY l.fammarine_id) s on marine_archive.fammarine_id = s.fammarine_id 
				    AND marine_archive.date = s.maxdate '.$filter_query.'LEFT JOIN (SELECT aircraft_shadow,icao FROM aircraft) a ON marine_archive.aircraft_icao = a.icao';
*/
            $query  = 'SELECT marine_archive.date,marine_archive.fammarine_id, marine_archive.ident, marine_archive.aircraft_icao, marine_archive.departure_airport_icao as departure_airport, marine_archive.arrival_airport_icao as arrival_airport, marine_archive.latitude, marine_archive.longitude, marine_archive.altitude, marine_archive.heading, marine_archive.ground_speed, marine_archive.squawk, a.aircraft_shadow,a.engine_type, a.engine_count, a.wake_category 
				    FROM marine_archive 
				    INNER JOIN (SELECT * FROM aircraft) a on marine_archive.aircraft_icao = a.icao
				    WHERE marine_archive.date BETWEEN '."'".$begindate."'".' AND '."'".$begindate."'".' 
                        	    '.$filter_query.' ORDER BY fammarine_id';
        } else {
            //$query  = 'SELECT marine_archive.ident, marine_archive.fammarine_id, marine_archive.aircraft_icao, marine_archive.departure_airport_icao as departure_airport, marine_archive.arrival_airport_icao as arrival_airport, marine_archive.latitude, marine_archive.longitude, marine_archive.altitude, marine_archive.heading, marine_archive.ground_speed, marine_archive.squawk, a.aircraft_shadow FROM marine_archive INNER JOIN (SELECT l.fammarine_id, max(l.date) as maxdate FROM marine_archive l WHERE DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.$globalLiveInterval.' SECOND) <= l.date GROUP BY l.fammarine_id) s on marine_archive.fammarine_id = s.fammarine_id AND marine_archive.date = s.maxdate '.$filter_query.'INNER JOIN (SELECT * FROM aircraft) a on marine_archive.aircraft_icao = a.icao';
            $query  = 'SELECT marine_archive.date,marine_archive.fammarine_id, marine_archive.ident, marine_archive.aircraft_icao, marine_archive.departure_airport_icao as departure_airport, marine_archive.arrival_airport_icao as arrival_airport, marine_archive.latitude, marine_archive.longitude, marine_archive.altitude, marine_archive.heading, marine_archive.ground_speed, marine_archive.squawk, a.aircraft_shadow,a.engine_type, a.engine_count, a.wake_category 
                        	    FROM marine_archive 
                        	    INNER JOIN (SELECT * FROM aircraft) a on marine_archive.aircraft_icao = a.icao
                        	    WHERE marine_archive.date >= '."'".$begindate."'".' AND marine_archive.date <= '."'".$enddate."'".'
                        	    '.$filter_query.' ORDER BY fammarine_id';
        }
        //echo $query;
        try {
            $sth = $this->db->prepare($query);
            $sth->execute();
        } catch(PDOException $e) {
            echo $e->getMessage();
            die;
        }
        $spotter_array = $sth->fetchAll(PDO::FETCH_ASSOC);

        return $spotter_array;
    }

    /**
     * Gets Minimal Live Marine data
     *
     * @param $begindate
     * @param $enddate
     * @param array $filter
     * @return array the spotter information
     */
    public function getMinLiveMarineDataPlayback($begindate,$enddate,$filter = array())
    {
        global $globalDBdriver;
        date_default_timezone_set('UTC');

        $filter_query = '';
        if (isset($filter['source']) && !empty($filter['source'])) {
            $filter_query .= " AND format_source IN ('".implode("','",$filter['source'])."') ";
        }
        // Should use spotter_output also ?
        if (isset($filter['airlines']) && !empty($filter['airlines'])) {
            $filter_query .= " INNER JOIN (SELECT fammarine_id FROM marine_archive_output WHERE marine_archive_output.airline_icao IN ('".implode("','",$filter['airlines'])."')) so ON so.fammarine_id = marine_archive.fammarine_id ";
        }
        if (isset($filter['airlinestype']) && !empty($filter['airlinestype'])) {
            $filter_query .= " INNER JOIN (SELECT fammarine_id FROM marine_archive_output WHERE marine_archive_output.airline_type = '".$filter['airlinestype']."') sa ON sa.fammarine_id = marine_archive.fammarine_id ";
        }
        if (isset($filter['source_aprs']) && !empty($filter['source_aprs'])) {
            $filter_query = " AND format_source = 'aprs' AND source_name IN ('".implode("','",$filter['source_aprs'])."')";
        }

        //if (!isset($globalLiveInterval)) $globalLiveInterval = '200';
        if ($globalDBdriver == 'mysql') {
            /*
                        $query  = 'SELECT a.aircraft_shadow, marine_archive.ident, marine_archive.fammarine_id, marine_archive.aircraft_icao, marine_archive.departure_airport_icao as departure_airport, marine_archive.arrival_airport_icao as arrival_airport, marine_archive.latitude, marine_archive.longitude, marine_archive.altitude, marine_archive.heading, marine_archive.ground_speed, marine_archive.squawk 
                    		    FROM marine_archive 
                    		    INNER JOIN (SELECT l.fammarine_id, max(l.date) as maxdate FROM marine_archive l WHERE (l.date BETWEEN '."'".$begindate."'".' AND '."'".$enddate."'".') GROUP BY l.fammarine_id) s on marine_archive.fammarine_id = s.fammarine_id AND marine_archive.date = s.maxdate '.$filter_query.'LEFT JOIN (SELECT aircraft_shadow,icao FROM aircraft) a ON marine_archive.aircraft_icao = a.icao';
			*/
            $query  = 'SELECT a.aircraft_shadow, marine_archive_output.ident, marine_archive_output.fammarine_id, marine_archive_output.aircraft_icao, marine_archive_output.departure_airport_icao as departure_airport, marine_archive_output.arrival_airport_icao as arrival_airport, marine_archive_output.latitude, marine_archive_output.longitude, marine_archive_output.altitude, marine_archive_output.heading, marine_archive_output.ground_speed, marine_archive_output.squawk 
				    FROM marine_archive_output 
				    LEFT JOIN (SELECT aircraft_shadow,icao FROM aircraft) a ON marine_archive_output.aircraft_icao = a.icao 
				    WHERE (marine_archive_output.date BETWEEN '."'".$begindate."'".' AND '."'".$enddate."'".') 
                        	    '.$filter_query.' GROUP BY marine_archive_output.fammarine_id, marine_archive_output.ident, marine_archive_output.aircraft_icao, marine_archive_output.departure_airport_icao, marine_archive_output.arrival_airport_icao, marine_archive_output.latitude, marine_archive_output.longitude, marine_archive_output.altitude, marine_archive_output.heading, marine_archive_output.ground_speed, marine_archive_output.squawk, a.aircraft_shadow';

        } else {
            //$query  = 'SELECT marine_archive_output.ident, marine_archive_output.fammarine_id, marine_archive_output.aircraft_icao, marine_archive_output.departure_airport_icao as departure_airport, marine_archive_output.arrival_airport_icao as arrival_airport, marine_archive_output.latitude, marine_archive_output.longitude, marine_archive_output.altitude, marine_archive_output.heading, marine_archive_output.ground_speed, marine_archive_output.squawk, a.aircraft_shadow FROM marine_archive_output INNER JOIN (SELECT l.fammarine_id, max(l.date) as maxdate FROM marine_archive_output l WHERE DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.$globalLiveInterval.' SECOND) <= l.date GROUP BY l.fammarine_id) s on marine_archive_output.fammarine_id = s.fammarine_id AND marine_archive_output.date = s.maxdate '.$filter_query.'INNER JOIN (SELECT * FROM aircraft) a on marine_archive_output.aircraft_icao = a.icao';
            /*
                        $query  = 'SELECT marine_archive_output.ident, marine_archive_output.fammarine_id, marine_archive_output.aircraft_icao, marine_archive_output.departure_airport_icao as departure_airport, marine_archive_output.arrival_airport_icao as arrival_airport, marine_archive_output.latitude, marine_archive_output.longitude, marine_archive_output.altitude, marine_archive_output.heading, marine_archive_output.ground_speed, marine_archive_output.squawk, a.aircraft_shadow
                        	    FROM marine_archive_output 
                        	    INNER JOIN (SELECT * FROM aircraft) a on marine_archive_output.aircraft_icao = a.icao
                        	    WHERE marine_archive_output.date >= '."'".$begindate."'".' AND marine_archive_output.date <= '."'".$enddate."'".'
                        	    '.$filter_query.' GROUP BY marine_archive_output.fammarine_id, marine_archive_output.ident, marine_archive_output.aircraft_icao, marine_archive_output.departure_airport_icao, marine_archive_output.arrival_airport_icao, marine_archive_output.latitude, marine_archive_output.longitude, marine_archive_output.altitude, marine_archive_output.heading, marine_archive_output.ground_speed, marine_archive_output.squawk, a.aircraft_shadow';
                        */
            $query  = 'SELECT DISTINCT marine_archive_output.fammarine_id, marine_archive_output.ident, marine_archive_output.aircraft_icao, marine_archive_output.departure_airport_icao as departure_airport, marine_archive_output.arrival_airport_icao as arrival_airport, marine_archive_output.latitude, marine_archive_output.longitude, marine_archive_output.altitude, marine_archive_output.heading, marine_archive_output.ground_speed, marine_archive_output.squawk, a.aircraft_shadow
                        	    FROM marine_archive_output 
                        	    INNER JOIN (SELECT * FROM aircraft) a on marine_archive_output.aircraft_icao = a.icao
                        	    WHERE marine_archive_output.date >= '."'".$begindate."'".' AND marine_archive_output.date <= '."'".$enddate."'".'
                        	    '.$filter_query.' LIMIT 200 OFFSET 0';
//                        	    .' GROUP BY spotter_output.fammarine_id, spotter_output.ident, spotter_output.aircraft_icao, spotter_output.departure_airport_icao, spotter_output.arrival_airport_icao, spotter_output.latitude, spotter_output.longitude, spotter_output.altitude, spotter_output.heading, spotter_output.ground_speed, spotter_output.squawk, a.aircraft_shadow';
                        	    
        }
        //echo $query;
        try {
            $sth = $this->db->prepare($query);
            $sth->execute();
        } catch(PDOException $e) {
            echo $e->getMessage();
            die;
        }
        $spotter_array = $sth->fetchAll(PDO::FETCH_ASSOC);

        return $spotter_array;
    }

    /**
     * Gets count Live Marine data
     *
     * @param $begindate
     * @param $enddate
     * @param array $filter
     * @return array the spotter information
     */
    public function getLiveMarineCount($begindate,$enddate,$filter = array())
    {
        global $globalDBdriver, $globalLiveInterval;
        date_default_timezone_set('UTC');

        $filter_query = '';
        if (isset($filter['source']) && !empty($filter['source'])) {
            $filter_query .= " AND format_source IN ('".implode("','",$filter['source'])."') ";
        }
        if (isset($filter['airlines']) && !empty($filter['airlines'])) {
            $filter_query .= " INNER JOIN (SELECT fammarine_id FROM spotter_output WHERE spotter_output.airline_icao IN ('".implode("','",$filter['airlines'])."')) so ON so.fammarine_id = marine_archive.fammarine_id ";
        }
        if (isset($filter['airlinestype']) && !empty($filter['airlinestype'])) {
            $filter_query .= " INNER JOIN (SELECT fammarine_id FROM spotter_output WHERE spotter_output.airline_type = '".$filter['airlinestype']."') sa ON sa.fammarine_id = marine_archive.fammarine_id ";
        }
        if (isset($filter['source_aprs']) && !empty($filter['source_aprs'])) {
            $filter_query = " AND format_source = 'aprs' AND source_name IN ('".implode("','",$filter['source_aprs'])."')";
        }

        //if (!isset($globalLiveInterval)) $globalLiveInterval = '200';
        if ($globalDBdriver == 'mysql') {
            $query = 'SELECT COUNT(DISTINCT fammarine_id) as nb 
			FROM marine_archive l 
			WHERE (l.date BETWEEN DATE_SUB('."'".$begindate."'".',INTERVAL '.$globalLiveInterval.' SECOND) AND '."'".$begindate."'".')'.$filter_query;
        } else {
            $query = 'SELECT COUNT(DISTINCT fammarine_id) as nb FROM marine_archive l WHERE (l.date BETWEEN '."'".$begindate."' - INTERVAL '".$globalLiveInterval." SECONDS' AND "."'".$enddate."'".')'.$filter_query;
        }
        //echo $query;
        try {
            $sth = $this->db->prepare($query);
            $sth->execute();
        } catch(PDOException $e) {
            echo $e->getMessage();
            die;
        }
        $result = $sth->fetch(PDO::FETCH_ASSOC);
        $sth->closeCursor();
        return $result['nb'];
    }



	// marine_archive_output

    /**
     * Gets all the spotter information
     *
     * @param string $q
     * @param string $registration
     * @param string $aircraft_icao
     * @param string $aircraft_manufacturer
     * @param string $highlights
     * @param string $airline_icao
     * @param string $airline_country
     * @param string $airline_type
     * @param string $airport
     * @param string $airport_country
     * @param string $callsign
     * @param string $departure_airport_route
     * @param string $arrival_airport_route
     * @param string $owner
     * @param string $pilot_id
     * @param string $pilot_name
     * @param string $altitude
     * @param string $date_posted
     * @param string $limit
     * @param string $sort
     * @param string $includegeodata
     * @param string $origLat
     * @param string $origLon
     * @param string $dist
     * @param array $filters
     * @return array the spotter information
     */
    public function searchMarineData($q = '', $registration = '', $aircraft_icao = '', $aircraft_manufacturer = '', $highlights = '', $airline_icao = '', $airline_country = '', $airline_type = '', $airport = '', $airport_country = '', $callsign = '', $departure_airport_route = '', $arrival_airport_route = '', $owner = '',$pilot_id = '',$pilot_name = '',$altitude = '', $date_posted = '', $limit = '', $sort = '', $includegeodata = '',$origLat = '',$origLon = '',$dist = '', $filters=array())
    {
        global $globalTimezone, $globalDBdriver;
        require_once(dirname(__FILE__).'/class.Translation.php');
        $Translation = new Translation($this->db);
        $Marine = new Marine($this->db);

        date_default_timezone_set('UTC');
	
        $query_values = array();
        $additional_query = '';
        $limit_query = '';
        $filter_query = $this->getFilter($filters);
        if ($q != "")
        {
            if (!is_string($q))
            {
                return array();
            } else {
                $q_array = explode(" ", $q);
		
                foreach ($q_array as $q_item){
                    $additional_query .= " AND (";
                    $additional_query .= "(marine_archive_output.spotter_id like '%".$q_item."%') OR ";
                    $additional_query .= "(marine_archive_output.aircraft_icao like '%".$q_item."%') OR ";
                    $additional_query .= "(marine_archive_output.aircraft_name like '%".$q_item."%') OR ";
                    $additional_query .= "(marine_archive_output.aircraft_manufacturer like '%".$q_item."%') OR ";
                    $additional_query .= "(marine_archive_output.airline_icao like '%".$q_item."%') OR ";
                    $additional_query .= "(marine_archive_output.airline_name like '%".$q_item."%') OR ";
                    $additional_query .= "(marine_archive_output.airline_country like '%".$q_item."%') OR ";
                    $additional_query .= "(marine_archive_output.departure_airport_icao like '%".$q_item."%') OR ";
                    $additional_query .= "(marine_archive_output.departure_airport_name like '%".$q_item."%') OR ";
                    $additional_query .= "(marine_archive_output.departure_airport_city like '%".$q_item."%') OR ";
                    $additional_query .= "(marine_archive_output.departure_airport_country like '%".$q_item."%') OR ";
                    $additional_query .= "(marine_archive_output.arrival_airport_icao like '%".$q_item."%') OR ";
                    $additional_query .= "(marine_archive_output.arrival_airport_name like '%".$q_item."%') OR ";
                    $additional_query .= "(marine_archive_output.arrival_airport_city like '%".$q_item."%') OR ";
                    $additional_query .= "(marine_archive_output.arrival_airport_country like '%".$q_item."%') OR ";
                    $additional_query .= "(marine_archive_output.registration like '%".$q_item."%') OR ";
                    $additional_query .= "(marine_archive_output.owner_name like '%".$q_item."%') OR ";
                    $additional_query .= "(marine_archive_output.pilot_id like '%".$q_item."%') OR ";
                    $additional_query .= "(marine_archive_output.pilot_name like '%".$q_item."%') OR ";
                    $additional_query .= "(marine_archive_output.ident like '%".$q_item."%') OR ";
                    $translate = $Translation->ident2icao($q_item);
                    if ($translate != $q_item) $additional_query .= "(marine_archive_output.ident like '%".$translate."%') OR ";
                    $additional_query .= "(marine_archive_output.highlight like '%".$q_item."%')";
                    $additional_query .= ")";
                }
            }
        }
	
        if ($registration != "")
        {
            $registration = filter_var($registration,FILTER_SANITIZE_STRING);
            if (!is_string($registration))
            {
                return array();
            } else {
                $additional_query .= " AND (marine_archive_output.registration = '".$registration."')";
            }
        }
	
        if ($aircraft_icao != "")
        {
            $aircraft_icao = filter_var($aircraft_icao,FILTER_SANITIZE_STRING);
            if (!is_string($aircraft_icao))
            {
                return array();
            } else {
                $additional_query .= " AND (marine_archive_output.aircraft_icao = '".$aircraft_icao."')";
            }
        }
	
        if ($aircraft_manufacturer != "")
        {
            $aircraft_manufacturer = filter_var($aircraft_manufacturer,FILTER_SANITIZE_STRING);
            if (!is_string($aircraft_manufacturer))
            {
                return array();
	    } else {
                $additional_query .= " AND (marine_archive_output.aircraft_manufacturer = '".$aircraft_manufacturer."')";
            }
        }
	
        if ($highlights == "true")
        {
            if (!is_string($highlights))
            {
                return array();
            } else {
                $additional_query .= " AND (marine_archive_output.highlight <> '')";
            }
        }
	
        if ($airline_icao != "")
        {
            $airline_icao = filter_var($airline_icao,FILTER_SANITIZE_STRING);
            if (!is_string($airline_icao))
            {
                return array();
            } else {
                $additional_query .= " AND (marine_archive_output.airline_icao = '".$airline_icao."')";
            }
        }
	
        if ($airline_country != "")
        {
            $airline_country = filter_var($airline_country,FILTER_SANITIZE_STRING);
            if (!is_string($airline_country))
            {
                return array();
            } else {
                $additional_query .= " AND (marine_archive_output.airline_country = '".$airline_country."')";
            }
        }
	
        if ($airline_type != "")
        {
            $airline_type = filter_var($airline_type,FILTER_SANITIZE_STRING);
            if (!is_string($airline_type))
            {
                return array();
            } else {
                if ($airline_type == "passenger")
                {
                    $additional_query .= " AND (marine_archive_output.airline_type = 'passenger')";
                }
                if ($airline_type == "cargo")
                {
                    $additional_query .= " AND (marine_archive_output.airline_type = 'cargo')";
                }
                if ($airline_type == "military")
                {
                    $additional_query .= " AND (marine_archive_output.airline_type = 'military')";
                }
            }
        }
	
        if ($airport != "")
        {
            $airport = filter_var($airport,FILTER_SANITIZE_STRING);
            if (!is_string($airport))
            {
                return array();
            } else {
                $additional_query .= " AND ((marine_archive_output.departure_airport_icao = '".$airport."') OR (marine_archive_output.arrival_airport_icao = '".$airport."'))";
            }
        }
	
        if ($airport_country != "")
        {
            $airport_country = filter_var($airport_country,FILTER_SANITIZE_STRING);
            if (!is_string($airport_country))
            {
                return array();
            } else {
                $additional_query .= " AND ((marine_archive_output.departure_airport_country = '".$airport_country."') OR (marine_archive_output.arrival_airport_country = '".$airport_country."'))";
            }
        }
    
        if ($callsign != "")
        {
            $callsign = filter_var($callsign,FILTER_SANITIZE_STRING);
            if (!is_string($callsign))
            {
                return array();
            } else {
                $translate = $Translation->ident2icao($callsign);
                if ($translate != $callsign) {
                    $additional_query .= " AND (marine_archive_output.ident = :callsign OR marine_archive_output.ident = :translate)";
                    $query_values = array_merge($query_values,array(':callsign' => $callsign,':translate' => $translate));
                } else {
                    $additional_query .= " AND (marine_archive_output.ident = '".$callsign."')";
                }
            }
        }

        if ($owner != "")
        {
            $owner = filter_var($owner,FILTER_SANITIZE_STRING);
            if (!is_string($owner))
            {
                return array();
            } else {
                $additional_query .= " AND (marine_archive_output.owner_name = '".$owner."')";
            }
        }

        if ($pilot_name != "")
        {
            $pilot_name = filter_var($pilot_name,FILTER_SANITIZE_STRING);
            if (!is_string($pilot_name))
            {
                return array();
            } else {
                $additional_query .= " AND (marine_archive_output.pilot_name = '".$pilot_name."')";
            }
        }
	
        if ($pilot_id != "")
        {
            $pilot_id = filter_var($pilot_id,FILTER_SANITIZE_NUMBER_INT);
            if (!is_string($pilot_id))
            {
                return array();
            } else {
                $additional_query .= " AND (marine_archive_output.pilot_id = '".$pilot_id."')";
            }
        }
	
        if ($departure_airport_route != "")
        {
            $departure_airport_route = filter_var($departure_airport_route,FILTER_SANITIZE_STRING);
            if (!is_string($departure_airport_route))
            {
                return array();
            } else {
                $additional_query .= " AND (marine_archive_output.departure_airport_icao = '".$departure_airport_route."')";
            }
        }
	
        if ($arrival_airport_route != "")
        {
            $arrival_airport_route = filter_var($arrival_airport_route,FILTER_SANITIZE_STRING);
            if (!is_string($arrival_airport_route))
            {
                return array();
            } else {
                $additional_query .= " AND (marine_archive_output.arrival_airport_icao = '".$arrival_airport_route."')";
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
                $additional_query .= " AND altitude BETWEEN '".$altitude_array[0]."' AND '".$altitude_array[1]."' ";
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
                if ($globalDBdriver == 'mysql') {
                    $additional_query .= " AND TIMESTAMP(CONVERT_TZ(marine_archive_output.date,'+00:00', '".$offset."')) >= '".$date_array[0]."' AND TIMESTAMP(CONVERT_TZ(marine_archive_output.date,'+00:00', '".$offset."')) <= '".$date_array[1]."' ";
                } else {
                    $additional_query .= " AND marine_archive_output.date::timestamp AT TIME ZONE INTERVAL ".$offset." >= CAST('".$date_array[0]."' AS TIMESTAMP) AND marine_archive_output.date::timestamp AT TIME ZONE INTERVAL ".$offset." <= CAST('".$date_array[1]."' AS TIMESTAMP) ";
                }
            } else {
                $date_array[0] = date("Y-m-d H:i:s", strtotime($date_array[0]));
                if ($globalDBdriver == 'mysql') {
                    $additional_query .= " AND TIMESTAMP(CONVERT_TZ(marine_archive_output.date,'+00:00', '".$offset."')) >= '".$date_array[0]."' ";
                } else {
                    $additional_query .= " AND marine_archive_output.date::timestamp AT TIME ZONE INTERVAL ".$offset." >= CAST('".$date_array[0]."' AS TIMESTAMP) ";
                }
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
	

        if ($origLat != "" && $origLon != "" && $dist != "") {
            $dist = number_format($dist*0.621371,2,'.','');
            $query="SELECT marine_archive_output.*, 3956 * 2 * ASIN(SQRT( POWER(SIN(($origLat - ABS(CAST(marine_archive.latitude as double precision)))*pi()/180/2),2)+COS( $origLat *pi()/180)*COS(ABS(CAST(marine_archive.latitude as double precision))*pi()/180)*POWER(SIN(($origLon-CAST(marine_archive.longitude as double precision))*pi()/180/2),2))) as distance 
                          FROM marine_archive_output, marine_archive WHERE spotter_output_archive.fammarine_id = marine_archive.fammarine_id AND spotter_output.ident <> '' ".$additional_query."AND CAST(marine_archive.longitude as double precision) between ($origLon-$dist/ABS(cos(radians($origLat))*69)) and ($origLon+$dist/ABS(cos(radians($origLat))*69)) and CAST(marine_archive.latitude as double precision) between ($origLat-($dist/69)) and ($origLat+($dist/69)) 
                          AND (3956 * 2 * ASIN(SQRT( POWER(SIN(($origLat - ABS(CAST(marine_archive.latitude as double precision)))*pi()/180/2),2)+COS( $origLat *pi()/180)*COS(ABS(CAST(marine_archive.latitude as double precision))*pi()/180)*POWER(SIN(($origLon-CAST(marine_archive.longitude as double precision))*pi()/180/2),2)))) < $dist".$filter_query." ORDER BY distance";
        } else {
            if ($sort != "")
            {
                $search_orderby_array = $Marine->getOrderBy();
                $orderby_query = $search_orderby_array[$sort]['sql'];
            } else {
                $orderby_query = " ORDER BY marine_archive_output.date DESC";
            }
	
            if ($includegeodata == "true")
            {
                $additional_query .= " AND (marine_archive_output.waypoints <> '')";
            }

            $query  = "SELECT marine_archive_output.* FROM marine_archive_output 
		    WHERE marine_archive_output.ident <> '' 
		    ".$additional_query."
		    ".$filter_query.$orderby_query;
        }
        $spotter_array = $Marine->getDataFromDB($query, $query_values,$limit_query);

        return $spotter_array;
    }

    public function deleteMarineArchiveData()
    {
        global $globalArchiveKeepMonths, $globalDBdriver;
        date_default_timezone_set('UTC');
        if ($globalDBdriver == 'mysql') {
            $query = 'DELETE FROM marine_archive_output WHERE marine_archive_output.date < DATE_SUB(UTC_TIMESTAMP(), INTERVAL '.$globalArchiveKeepMonths.' MONTH)';
        } else {
            $query = "DELETE FROM marine_archive_output WHERE marine_archive_output.date < CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$globalArchiveKeepMonths." MONTH'";
        }
        try {
            $sth = $this->db->prepare($query);
            $sth->execute();
        } catch(PDOException $e) {
            return "error";
        }
        return '';
    }

    /**
     * Gets all the spotter information based on the callsign
     *
     * @param string $ident
     * @param string $limit
     * @param string $sort
     * @return array the spotter information
     */
    public function getMarineDataByIdent($ident = '', $limit = '', $sort = '')
    {
	$global_query = "SELECT marine_archive_output.* FROM marine_archive_output";
	
	date_default_timezone_set('UTC');
	$Marine = new Marine($this->db);
	
	$query_values = array();
	$limit_query = '';
	$additional_query = '';
	
	if ($ident != "")
	{
	    if (!is_string($ident))
	    {
            return array();
        } else {
            $additional_query = " AND (marine_archive_output.ident = :ident)";
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
	    $search_orderby_array = $Marine->getOrderBy();
	    $orderby_query = $search_orderby_array[$sort]['sql'];
	} else {
	    $orderby_query = " ORDER BY marine_archive_output.date DESC";
	}

	$query = $global_query." WHERE marine_archive_output.ident <> '' ".$additional_query." ".$orderby_query;

	$spotter_array = $Marine->getDataFromDB($query, $query_values, $limit_query);

	return $spotter_array;
    }


    /**
     * Gets all the spotter information based on the owner
     *
     * @param string $owner
     * @param string $limit
     * @param string $sort
     * @param array $filter
     * @return array the spotter information
     */
    public function getMarineDataByOwner($owner = '', $limit = '', $sort = '', $filter = array())
    {
	$global_query = "SELECT marine_archive_output.* FROM marine_archive_output";
	
	date_default_timezone_set('UTC');
	$Marine = new Marine($this->db);
	
	$query_values = array();
	$limit_query = '';
	$additional_query = '';
	$filter_query = $this->getFilter($filter,true,true);
	
	if ($owner != "")
	{
	    if (!is_string($owner))
	    {
		return array();
	    } else {
		$additional_query = " AND (marine_archive_output.owner_name = :owner)";
		$query_values = array(':owner' => $owner);
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
	    $search_orderby_array = $Marine->getOrderBy();
	    $orderby_query = $search_orderby_array[$sort]['sql'];
	} else {
	    $orderby_query = " ORDER BY marine_archive_output.date DESC";
	}

	$query = $global_query.$filter_query." marine_archive_output.owner_name <> '' ".$additional_query." ".$orderby_query;

	$spotter_array = $Marine->getDataFromDB($query, $query_values, $limit_query);

	return $spotter_array;
    }

    /**
     * Gets all the spotter information based on the pilot
     *
     * @param string $pilot
     * @param string $limit
     * @param string $sort
     * @param array $filter
     * @return array the spotter information
     */
    public function getMarineDataByPilot($pilot = '', $limit = '', $sort = '', $filter = array())
    {
	$global_query = "SELECT marine_archive_output.* FROM marine_archive_output";
	
	date_default_timezone_set('UTC');
	$Marine = new Marine($this->db);
	
	$query_values = array();
	$limit_query = '';
	$additional_query = '';
	$filter_query = $this->getFilter($filter,true,true);
	
	if ($pilot != "")
	{
		$additional_query = " AND (marine_archive_output.pilot_id = :pilot OR marine_archive_output.pilot_name = :pilot)";
		$query_values = array(':pilot' => $pilot);
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
	    $search_orderby_array = $Marine->getOrderBy();
	    $orderby_query = $search_orderby_array[$sort]['sql'];
	} else {
	    $orderby_query = " ORDER BY marine_archive_output.date DESC";
	}

	$query = $global_query.$filter_query." marine_archive_output.pilot_name <> '' ".$additional_query." ".$orderby_query;

	$spotter_array = $Marine->getDataFromDB($query, $query_values, $limit_query);

	return $spotter_array;
    }

    /**
     * Gets all number of flight over countries
     *
     * @param bool $limit
     * @param int $olderthanmonths
     * @param string $sincedate
     * @return array the airline country list
     */
    public function countAllFlightOverCountries($limit = true,$olderthanmonths = 0,$sincedate = '')
    {
	global $globalDBdriver;
	/*
	$query = "SELECT c.name, c.iso3, c.iso2, count(c.name) as nb 
		    FROM countries c, marine_archive s
		    WHERE Within(GeomFromText(CONCAT('POINT(',s.longitude,' ',s.latitude,')')), ogc_geom) ";
	*/
	$query = "SELECT c.name, c.iso3, c.iso2, count(c.name) as nb
		    FROM countries c, marine_archive s
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

    /**
     * Gets all number of flight over countries
     *
     * @param bool $limit
     * @param int $olderthanmonths
     * @param string $sincedate
     * @return array the airline country list
     */
    public function countAllFlightOverCountriesByAirlines($limit = true,$olderthanmonths = 0,$sincedate = '')
    {
	global $globalDBdriver;
	/*
	$query = "SELECT c.name, c.iso3, c.iso2, count(c.name) as nb 
		    FROM countries c, marine_archive s
		    WHERE Within(GeomFromText(CONCAT('POINT(',s.longitude,' ',s.latitude,')')), ogc_geom) ";
	*/
	$query = "SELECT o.airline_icao,c.name, c.iso3, c.iso2, count(c.name) as nb
		    FROM countries c, marine_archive s, spotter_output o
		    WHERE c.iso2 = s.over_country AND o.airline_icao <> '' AND o.fammarine_id = s.fammarine_id ";
                if ($olderthanmonths > 0) {
            		if ($globalDBdriver == 'mysql') {
				$query .= 'AND s.date < DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.$olderthanmonths.' MONTH) ';
			} else {
				$query .= "AND s.date < CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$olderthanmonths." MONTHS'";
			}
		}
                if ($sincedate != '') $query .= "AND s.date > '".$sincedate."' ";
	$query .= "GROUP BY o.airline_icao,c.name, c.iso3, c.iso2 ORDER BY nb DESC";
	if ($limit) $query .= " LIMIT 0,10";
      
	
	$sth = $this->db->prepare($query);
	$sth->execute();
 
	$flight_array = array();
	$temp_array = array();
        
	while($row = $sth->fetch(PDO::FETCH_ASSOC))
	{
	    $temp_array['airline_icao'] = $row['airline_icao'];
	    $temp_array['flight_count'] = $row['nb'];
	    $temp_array['flight_country'] = $row['name'];
	    $temp_array['flight_country_iso3'] = $row['iso3'];
	    $temp_array['flight_country_iso2'] = $row['iso2'];
	    $flight_array[] = $temp_array;
	}
	return $flight_array;
    }

    /**
     * Gets last spotter information based on a particular callsign
     *
     * @param $id
     * @param $date
     * @return array the spotter information
     */
    public function getDateArchiveMarineDataById($id,$date)
    {
	$Marine = new Marine($this->db);
	date_default_timezone_set('UTC');
	$id = filter_var($id, FILTER_SANITIZE_STRING);
	$query  = 'SELECT marine_archive.* FROM marine_archive INNER JOIN (SELECT l.fammarine_id, max(l.date) as maxdate FROM marine_archive l WHERE l.fammarine_id = :id AND l.date <= :date GROUP BY l.fammarine_id) s on marine_archive.fammarine_id = s.fammarine_id AND marine_archive.date = s.maxdate ORDER BY marine_archive.date DESC';
	$date = date('c',$date);
	$spotter_array = $Marine->getDataFromDB($query,array(':id' => $id,':date' => $date));
	return $spotter_array;
    }

    /**
     * Gets all the spotter information based on a particular callsign
     *
     * @param $ident
     * @param $date
     * @return array the spotter information
     */
    public function getDateArchiveMarineDataByIdent($ident,$date)
    {
	$Marine = new Marine($this->db);
	date_default_timezone_set('UTC');
	$ident = filter_var($ident, FILTER_SANITIZE_STRING);
	$query  = 'SELECT marine_archive.* FROM marine_archive INNER JOIN (SELECT l.fammarine_id, max(l.date) as maxdate FROM marine_archive l WHERE l.ident = :ident AND l.date <= :date GROUP BY l.fammarine_id) s on marine_archive.fammarine_id = s.fammarine_id AND marine_archive.date = s.maxdate ORDER BY marine_archive.date DESC';
	$date = date('c',$date);
	$spotter_array = $Marine->getDataFromDB($query,array(':ident' => $ident,':date' => $date));
	return $spotter_array;
    }

    /**
     * Gets all the spotter information based on the airport
     *
     * @param string $airport
     * @param string $limit
     * @param string $sort
     * @param array $filters
     * @return array the spotter information
     */
    public function getMarineDataByAirport($airport = '', $limit = '', $sort = '',$filters = array())
    {
        global $global_query;
        $Marine = new Marine($this->db);
        date_default_timezone_set('UTC');
        $query_values = array();
        $limit_query = '';
        $additional_query = '';
        $filter_query = $this->getFilter($filters,true,true);
	
        if ($airport != "")
        {
            if (!is_string($airport))
            {
                return array();
            } else {
                $additional_query .= " AND ((marine_archive_output.departure_airport_icao = :airport) OR (marine_archive_output.arrival_airport_icao = :airport))";
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
            $search_orderby_array = $Marine->getOrderBy();
            $orderby_query = $search_orderby_array[$sort]['sql'];
        } else {
            $orderby_query = " ORDER BY marine_archive_output.date DESC";
        }

        $query = $global_query.$filter_query." marine_archive_output.ident <> '' ".$additional_query." AND ((marine_archive_output.departure_airport_icao <> 'NA') AND (marine_archive_output.arrival_airport_icao <> 'NA')) ".$orderby_query;

        $spotter_array = $Marine->getDataFromDB($query, $query_values, $limit_query);

        return $spotter_array;
    }
}
?>