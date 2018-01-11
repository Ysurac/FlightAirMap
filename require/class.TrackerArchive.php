<?php
/**
 * This class is part of FlightAirmap. It's used for trackers archive data
 *
 * Copyright (c) Ycarus (Yannick Chabanois) <support@flightairmap.com>
 * Licensed under AGPL license.
 * For more information see: https://www.flightairmap.com/
*/
class TrackerArchive {
	public $global_query = "SELECT tracker_archive.* FROM tracker_archive";
	public $db;

	public function __construct($dbc = null) {
		$Connection = new Connection($dbc);
		$this->db = $Connection->db;
		if ($this->db === null) die('Error: No DB connection. (TrackerArchive)');
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
					$filter_query_join .= " INNER JOIN (SELECT famtrackid FROM tracker_archive_output WHERE tracker_archive_output.ident IN ('".implode("','",$flt['idents'])."') AND tracker_archive_output.format_source IN ('".implode("','",$flt['source'])."')) spid ON spid.famtrackid = tracker_archive.famtrackid";
				} else {
					$filter_query_join .= " INNER JOIN (SELECT famtrackid FROM tracker_archive_output WHERE tracker_archive_output.ident IN ('".implode("','",$flt['idents'])."')) spid ON spid.famtrackid = tracker_archive.famtrackid";
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
					$filter_query_date .= " AND YEAR(tracker_archive_output.date) = '".$filter['year']."'";
				} else {
					$filter_query_date .= " AND EXTRACT(YEAR FROM tracker_archive_output.date) = '".$filter['year']."'";
				}
			}
			if (isset($filter['month']) && $filter['month'] != '') {
				if ($globalDBdriver == 'mysql') {
					$filter_query_date .= " AND MONTH(tracker_archive_output.date) = '".$filter['month']."'";
				} else {
					$filter_query_date .= " AND EXTRACT(MONTH FROM tracker_archive_output.date) = '".$filter['month']."'";
				}
			}
			if (isset($filter['day']) && $filter['day'] != '') {
				if ($globalDBdriver == 'mysql') {
					$filter_query_date .= " AND DAY(tracker_archive_output.date) = '".$filter['day']."'";
				} else {
					$filter_query_date .= " AND EXTRACT(DAY FROM tracker_archive_output.date) = '".$filter['day']."'";
				}
			}
			$filter_query_join .= " INNER JOIN (SELECT famtrackid FROM tracker_archive_output".preg_replace('/^ AND/',' WHERE',$filter_query_date).") sd ON sd.famtrackid = tracker_archive.famtrackid";
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
     * Add to tracker_archive
     *
     * @param string $famtrackid
     * @param string $ident
     * @param string $latitude
     * @param string $longitude
     * @param string $altitude
     * @param string $heading
     * @param string $groundspeed
     * @param string $date
     * @param bool $putinarchive
     * @param string $comment
     * @param string $type
     * @param bool $noarchive
     * @param string $format_source
     * @param string $source_name
     * @param string $over_country
     * @return string
     */
    public function addTrackerArchiveData($famtrackid = '', $ident = '', $latitude = '', $longitude = '', $altitude = '', $heading = '', $groundspeed = '', $date = '', $putinarchive = false, $comment = '', $type = '', $noarchive = false, $format_source = '', $source_name = '', $over_country = '') {
		require_once(dirname(__FILE__).'/class.Tracker.php');
		if ($over_country == '') {
			$Tracker = new Tracker($this->db);
			$data_country = $Tracker->getCountryFromLatitudeLongitude($latitude,$longitude);
			if (!empty($data_country)) $country = $data_country['iso2'];
			else $country = '';
		} else $country = $over_country;
		// Route is not added in tracker_archive
		$query  = 'INSERT INTO tracker_archive (famtrackid, ident, latitude, longitude, altitude, heading, ground_speed, date, format_source, source_name, over_country, comment, type) 
		    VALUES (:famtrackid,:ident,:latitude,:longitude,:altitude,:heading,:groundspeed,:date,:format_source, :source_name, :over_country,:comment,:type)';
		$query_values = array(':famtrackid' => $famtrackid,':ident' => $ident,':latitude' => $latitude,':longitude' => $longitude,':altitude' => $altitude,':heading' => $heading,':groundspeed' => $groundspeed,':date' => $date, ':format_source' => $format_source, ':source_name' => $source_name, ':over_country' => $country,':comment' => $comment,':type' => $type);
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
    public function getLastArchiveTrackerDataByIdent($ident)
    {
        $Tracker = new Tracker($this->db);
        date_default_timezone_set('UTC');
        $ident = filter_var($ident, FILTER_SANITIZE_STRING);
        $query  = "SELECT tracker_archive.* FROM tracker_archive WHERE ident = :ident ORDER BY date DESC LIMIT 1";
        $spotter_array = $Tracker->getDataFromDB($query,array(':ident' => $ident));
        return $spotter_array;
    }


    /**
     * Gets last the spotter information based on a particular id
     *
     * @param $id
     * @return array the spotter information
     */
    public function getLastArchiveTrackerDataById($id)
    {
        $Tracker = new Tracker($this->db);
        date_default_timezone_set('UTC');
        $id = filter_var($id, FILTER_SANITIZE_STRING);
        //$query  = TrackerArchive->$global_query." WHERE tracker_archive.famtrackid = :id";
        //$query  = "SELECT tracker_archive.* FROM tracker_archive INNER JOIN (SELECT l.famtrackid, max(l.date) as maxdate FROM tracker_archive l WHERE l.famtrackid = :id GROUP BY l.famtrackid) s on tracker_archive.famtrackid = s.famtrackid AND tracker_archive.date = s.maxdate LIMIT 1";
        $query  = "SELECT * FROM tracker_archive WHERE famtrackid = :id ORDER BY date DESC LIMIT 1";

//              $spotter_array = Tracker->getDataFromDB($query,array(':id' => $id));
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
        $spotter_array = $Tracker->getDataFromDB($query,array(':id' => $id));

        return $spotter_array;
    }

    /**
     * Gets all the spotter information based on a particular id
     *
     * @param $id
     * @param string $date
     * @return array the spotter information
     */
    public function getAllArchiveTrackerDataById($id,$date = '')
    {
        date_default_timezone_set('UTC');
        $id = filter_var($id, FILTER_SANITIZE_STRING);
        if ($date == '') $query  = $this->global_query." WHERE tracker_archive.famtrackid = :id ORDER BY date";
        else $query  = $this->global_query." WHERE tracker_archive.famtrackid = :id AND date < '".date('c',$date)."' ORDER BY date";

//              $spotter_array = Tracker->getDataFromDB($query,array(':id' => $id));

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
    public function getCoordArchiveTrackerDataById($id)
    {
        date_default_timezone_set('UTC');
        $id = filter_var($id, FILTER_SANITIZE_STRING);
        $query  = "SELECT tracker_archive.latitude, tracker_archive.longitude, tracker_archive.date FROM tracker_archive WHERE tracker_archive.famtrackid = :id";

//              $spotter_array = Tracker->getDataFromDB($query,array(':id' => $id));

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
    public function getAltitudeArchiveTrackerDataByIdent($ident)
    {

        date_default_timezone_set('UTC');

        $ident = filter_var($ident, FILTER_SANITIZE_STRING);
        $query  = "SELECT tracker_archive.altitude, tracker_archive.date FROM tracker_archive WHERE tracker_archive.ident = :ident AND tracker_archive.latitude <> 0 AND tracker_archive.longitude <> 0 ORDER BY date";

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
    public function getAltitudeArchiveTrackerDataById($id)
    {

        date_default_timezone_set('UTC');

        $id = filter_var($id, FILTER_SANITIZE_STRING);
        $query  = "SELECT tracker_archive.altitude, tracker_archive.date FROM tracker_archive WHERE tracker_archive.famtrackid = :id AND tracker_archive.latitude <> 0 AND tracker_archive.longitude <> 0 ORDER BY date";

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
    public function getAltitudeSpeedArchiveTrackerDataById($id)
    {

        date_default_timezone_set('UTC');

        $id = filter_var($id, FILTER_SANITIZE_STRING);
        $query  = "SELECT tracker_archive.altitude, tracker_archive.ground_speed, tracker_archive.date FROM tracker_archive WHERE tracker_archive.famtrackid = :id ORDER BY date";

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
    public function getLastAltitudeArchiveTrackerDataByIdent($ident)
    {

        date_default_timezone_set('UTC');

        $ident = filter_var($ident, FILTER_SANITIZE_STRING);
        $query  = "SELECT tracker_archive.altitude, tracker_archive.date FROM tracker_archive INNER JOIN (SELECT l.famtrackid, max(l.date) as maxdate FROM tracker_archive l WHERE l.ident = :ident GROUP BY l.famtrackid) s on tracker_archive.famtrackid = s.famtrackid AND tracker_archive.date = s.maxdate LIMIT 1";
//                $query  = "SELECT tracker_archive.altitude, tracker_archive.date FROM tracker_archive WHERE tracker_archive.ident = :ident";

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
     * @param $famtrackid
     * @param $date
     * @return array the spotter information
     */
    public function getTrackerArchiveData($ident,$famtrackid,$date)
    {
        $Tracker = new Tracker($this->db);
        $ident = filter_var($ident, FILTER_SANITIZE_STRING);
        $query  = "SELECT spotter_live.* FROM spotter_live INNER JOIN (SELECT l.famtrackid, max(l.date) as maxdate FROM spotter_live l WHERE l.ident = :ident AND l.famtrackid = :famtrackid AND l.date LIKE :date GROUP BY l.famtrackid) s on spotter_live.famtrackid = s.famtrackid AND spotter_live.date = s.maxdate";

        $spotter_array = $Tracker->getDataFromDB($query,array(':ident' => $ident,':famtrackid' => $famtrackid,':date' => $date.'%'));

        return $spotter_array;
    }

    public function deleteTrackerArchiveTrackData()
    {
        global $globalArchiveKeepTrackMonths, $globalDBdriver;
        if ($globalDBdriver == 'mysql') {
            $query = 'DELETE FROM tracker_archive WHERE tracker_archive.date < DATE_SUB(UTC_TIMESTAMP(), INTERVAL '.$globalArchiveKeepTrackMonths.' MONTH)';
        } else {
            $query = "DELETE FROM tracker_archive WHERE tracker_archive_id IN (SELECT tracker_archive_id FROM tracker_archive WHERE tracker_archive.date < CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$globalArchiveKeepTrackMonths." MONTH' LIMIT 10000)";
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
     * Gets Minimal Live Tracker data
     *
     * @param $begindate
     * @param $enddate
     * @param array $filter
     * @return array the spotter information
     */
    public function getMinLiveTrackerData($begindate,$enddate,$filter = array())
    {
        global $globalDBdriver;
        date_default_timezone_set('UTC');

        $filter_query = '';
        if (isset($filter['source']) && !empty($filter['source'])) {
            $filter_query .= " AND format_source IN ('".implode("','",$filter['source'])."') ";
        }
        // Use spotter_output also ?
        if (isset($filter['airlines']) && !empty($filter['airlines'])) {
            $filter_query .= " INNER JOIN (SELECT famtrackid FROM tracker_archive_output WHERE tracker_archive_output.airline_icao IN ('".implode("','",$filter['airlines'])."')) so ON so.famtrackid = tracker_archive.famtrackid ";
        }
        if (isset($filter['airlinestype']) && !empty($filter['airlinestype'])) {
            $filter_query .= " INNER JOIN (SELECT famtrackid FROM tracker_archive_output WHERE tracker_archive_output.airline_type = '".$filter['airlinestype']."') sa ON sa.famtrackid = tracker_archive.famtrackid ";
        }
        if (isset($filter['source_aprs']) && !empty($filter['source_aprs'])) {
            $filter_query = " AND format_source = 'aprs' AND source_name IN ('".implode("','",$filter['source_aprs'])."')";
        }

        //if (!isset($globalLiveInterval)) $globalLiveInterval = '200';
        if ($globalDBdriver == 'mysql') {
            /*
                        $query  = 'SELECT a.aircraft_shadow, tracker_archive.ident, tracker_archive.famtrackid, tracker_archive.aircraft_icao, tracker_archive.departure_airport_icao as departure_airport, tracker_archive.arrival_airport_icao as arrival_airport, tracker_archive.latitude, tracker_archive.longitude, tracker_archive.altitude, tracker_archive.heading, tracker_archive.ground_speed, tracker_archive.squawk 
                    		    FROM tracker_archive 
                    		    INNER JOIN (SELECT l.famtrackid, max(l.date) as maxdate FROM tracker_archive l WHERE (l.date BETWEEN '."'".$begindate."'".' AND '."'".$enddate."'".') GROUP BY l.famtrackid) s on tracker_archive.famtrackid = s.famtrackid AND tracker_archive.date = s.maxdate '.$filter_query.'LEFT JOIN (SELECT aircraft_shadow,icao FROM aircraft) a ON tracker_archive.aircraft_icao = a.icao';
			*/
            /*
			$query  = 'SELECT a.aircraft_shadow, tracker_archive.ident, tracker_archive.famtrackid, tracker_archive.aircraft_icao, tracker_archive.departure_airport_icao as departure_airport, tracker_archive.arrival_airport_icao as arrival_airport, tracker_archive.latitude, tracker_archive.longitude, tracker_archive.altitude, tracker_archive.heading, tracker_archive.ground_speed, tracker_archive.squawk 
				    FROM tracker_archive 
				    INNER JOIN (SELECT l.famtrackid, max(l.date) as maxdate 
						FROM tracker_archive l 
						WHERE (l.date BETWEEN DATE_SUB('."'".$begindate."'".',INTERVAL '.$globalLiveInterval.' SECOND) AND '."'".$begindate."'".') 
						GROUP BY l.famtrackid) s on tracker_archive.famtrackid = s.famtrackid 
				    AND tracker_archive.date = s.maxdate '.$filter_query.'LEFT JOIN (SELECT aircraft_shadow,icao FROM aircraft) a ON tracker_archive.aircraft_icao = a.icao';
*/
            $query  = 'SELECT tracker_archive.date,tracker_archive.famtrackid, tracker_archive.ident, tracker_archive.aircraft_icao, tracker_archive.departure_airport_icao as departure_airport, tracker_archive.arrival_airport_icao as arrival_airport, tracker_archive.latitude, tracker_archive.longitude, tracker_archive.altitude, tracker_archive.heading, tracker_archive.ground_speed, tracker_archive.squawk, a.aircraft_shadow,a.engine_type, a.engine_count, a.wake_category 
				    FROM tracker_archive 
				    INNER JOIN (SELECT * FROM aircraft) a on tracker_archive.aircraft_icao = a.icao
				    WHERE tracker_archive.date BETWEEN '."'".$begindate."'".' AND '."'".$begindate."'".' 
                        	    '.$filter_query.' ORDER BY famtrackid';
        } else {
            //$query  = 'SELECT tracker_archive.ident, tracker_archive.famtrackid, tracker_archive.aircraft_icao, tracker_archive.departure_airport_icao as departure_airport, tracker_archive.arrival_airport_icao as arrival_airport, tracker_archive.latitude, tracker_archive.longitude, tracker_archive.altitude, tracker_archive.heading, tracker_archive.ground_speed, tracker_archive.squawk, a.aircraft_shadow FROM tracker_archive INNER JOIN (SELECT l.famtrackid, max(l.date) as maxdate FROM tracker_archive l WHERE DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.$globalLiveInterval.' SECOND) <= l.date GROUP BY l.famtrackid) s on tracker_archive.famtrackid = s.famtrackid AND tracker_archive.date = s.maxdate '.$filter_query.'INNER JOIN (SELECT * FROM aircraft) a on tracker_archive.aircraft_icao = a.icao';
            $query  = 'SELECT tracker_archive.date,tracker_archive.famtrackid, tracker_archive.ident, tracker_archive.aircraft_icao, tracker_archive.departure_airport_icao as departure_airport, tracker_archive.arrival_airport_icao as arrival_airport, tracker_archive.latitude, tracker_archive.longitude, tracker_archive.altitude, tracker_archive.heading, tracker_archive.ground_speed, tracker_archive.squawk, a.aircraft_shadow,a.engine_type, a.engine_count, a.wake_category 
                        	    FROM tracker_archive 
                        	    INNER JOIN (SELECT * FROM aircraft) a on tracker_archive.aircraft_icao = a.icao
                        	    WHERE tracker_archive.date >= '."'".$begindate."'".' AND tracker_archive.date <= '."'".$enddate."'".'
                        	    '.$filter_query.' ORDER BY famtrackid';
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
     * Gets Minimal Live Tracker data
     *
     * @param $begindate
     * @param $enddate
     * @param array $filter
     * @return array the spotter information
     */
    public function getMinLiveTrackerDataPlayback($begindate,$enddate,$filter = array())
    {
        global $globalDBdriver;
        date_default_timezone_set('UTC');

        $filter_query = '';
        if (isset($filter['source']) && !empty($filter['source'])) {
            $filter_query .= " AND format_source IN ('".implode("','",$filter['source'])."') ";
        }
        // Should use spotter_output also ?
        if (isset($filter['airlines']) && !empty($filter['airlines'])) {
            $filter_query .= " INNER JOIN (SELECT famtrackid FROM tracker_archive_output WHERE tracker_archive_output.airline_icao IN ('".implode("','",$filter['airlines'])."')) so ON so.famtrackid = tracker_archive.famtrackid ";
        }
        if (isset($filter['airlinestype']) && !empty($filter['airlinestype'])) {
            $filter_query .= " INNER JOIN (SELECT famtrackid FROM tracker_archive_output WHERE tracker_archive_output.airline_type = '".$filter['airlinestype']."') sa ON sa.famtrackid = tracker_archive.famtrackid ";
        }
        if (isset($filter['source_aprs']) && !empty($filter['source_aprs'])) {
            $filter_query = " AND format_source = 'aprs' AND source_name IN ('".implode("','",$filter['source_aprs'])."')";
        }

        //if (!isset($globalLiveInterval)) $globalLiveInterval = '200';
        if ($globalDBdriver == 'mysql') {
            /*
                        $query  = 'SELECT a.aircraft_shadow, tracker_archive.ident, tracker_archive.famtrackid, tracker_archive.aircraft_icao, tracker_archive.departure_airport_icao as departure_airport, tracker_archive.arrival_airport_icao as arrival_airport, tracker_archive.latitude, tracker_archive.longitude, tracker_archive.altitude, tracker_archive.heading, tracker_archive.ground_speed, tracker_archive.squawk 
                    		    FROM tracker_archive 
                    		    INNER JOIN (SELECT l.famtrackid, max(l.date) as maxdate FROM tracker_archive l WHERE (l.date BETWEEN '."'".$begindate."'".' AND '."'".$enddate."'".') GROUP BY l.famtrackid) s on tracker_archive.famtrackid = s.famtrackid AND tracker_archive.date = s.maxdate '.$filter_query.'LEFT JOIN (SELECT aircraft_shadow,icao FROM aircraft) a ON tracker_archive.aircraft_icao = a.icao';
			*/
            $query  = 'SELECT a.aircraft_shadow, tracker_archive_output.ident, tracker_archive_output.famtrackid, tracker_archive_output.aircraft_icao, tracker_archive_output.departure_airport_icao as departure_airport, tracker_archive_output.arrival_airport_icao as arrival_airport, tracker_archive_output.latitude, tracker_archive_output.longitude, tracker_archive_output.altitude, tracker_archive_output.heading, tracker_archive_output.ground_speed, tracker_archive_output.squawk 
				    FROM tracker_archive_output 
				    LEFT JOIN (SELECT aircraft_shadow,icao FROM aircraft) a ON tracker_archive_output.aircraft_icao = a.icao 
				    WHERE (tracker_archive_output.date BETWEEN '."'".$begindate."'".' AND '."'".$enddate."'".') 
                        	    '.$filter_query.' GROUP BY tracker_archive_output.famtrackid, tracker_archive_output.ident, tracker_archive_output.aircraft_icao, tracker_archive_output.departure_airport_icao, tracker_archive_output.arrival_airport_icao, tracker_archive_output.latitude, tracker_archive_output.longitude, tracker_archive_output.altitude, tracker_archive_output.heading, tracker_archive_output.ground_speed, tracker_archive_output.squawk, a.aircraft_shadow';

        } else {
            //$query  = 'SELECT tracker_archive_output.ident, tracker_archive_output.famtrackid, tracker_archive_output.aircraft_icao, tracker_archive_output.departure_airport_icao as departure_airport, tracker_archive_output.arrival_airport_icao as arrival_airport, tracker_archive_output.latitude, tracker_archive_output.longitude, tracker_archive_output.altitude, tracker_archive_output.heading, tracker_archive_output.ground_speed, tracker_archive_output.squawk, a.aircraft_shadow FROM tracker_archive_output INNER JOIN (SELECT l.famtrackid, max(l.date) as maxdate FROM tracker_archive_output l WHERE DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.$globalLiveInterval.' SECOND) <= l.date GROUP BY l.famtrackid) s on tracker_archive_output.famtrackid = s.famtrackid AND tracker_archive_output.date = s.maxdate '.$filter_query.'INNER JOIN (SELECT * FROM aircraft) a on tracker_archive_output.aircraft_icao = a.icao';
            /*
                        $query  = 'SELECT tracker_archive_output.ident, tracker_archive_output.famtrackid, tracker_archive_output.aircraft_icao, tracker_archive_output.departure_airport_icao as departure_airport, tracker_archive_output.arrival_airport_icao as arrival_airport, tracker_archive_output.latitude, tracker_archive_output.longitude, tracker_archive_output.altitude, tracker_archive_output.heading, tracker_archive_output.ground_speed, tracker_archive_output.squawk, a.aircraft_shadow
                        	    FROM tracker_archive_output 
                        	    INNER JOIN (SELECT * FROM aircraft) a on tracker_archive_output.aircraft_icao = a.icao
                        	    WHERE tracker_archive_output.date >= '."'".$begindate."'".' AND tracker_archive_output.date <= '."'".$enddate."'".'
                        	    '.$filter_query.' GROUP BY tracker_archive_output.famtrackid, tracker_archive_output.ident, tracker_archive_output.aircraft_icao, tracker_archive_output.departure_airport_icao, tracker_archive_output.arrival_airport_icao, tracker_archive_output.latitude, tracker_archive_output.longitude, tracker_archive_output.altitude, tracker_archive_output.heading, tracker_archive_output.ground_speed, tracker_archive_output.squawk, a.aircraft_shadow';
                        */
            $query  = 'SELECT DISTINCT tracker_archive_output.famtrackid, tracker_archive_output.ident, tracker_archive_output.aircraft_icao, tracker_archive_output.departure_airport_icao as departure_airport, tracker_archive_output.arrival_airport_icao as arrival_airport, tracker_archive_output.latitude, tracker_archive_output.longitude, tracker_archive_output.altitude, tracker_archive_output.heading, tracker_archive_output.ground_speed, tracker_archive_output.squawk, a.aircraft_shadow
                        	    FROM tracker_archive_output 
                        	    INNER JOIN (SELECT * FROM aircraft) a on tracker_archive_output.aircraft_icao = a.icao
                        	    WHERE tracker_archive_output.date >= '."'".$begindate."'".' AND tracker_archive_output.date <= '."'".$enddate."'".'
                        	    '.$filter_query.' LIMIT 200 OFFSET 0';
//                        	    .' GROUP BY spotter_output.famtrackid, spotter_output.ident, spotter_output.aircraft_icao, spotter_output.departure_airport_icao, spotter_output.arrival_airport_icao, spotter_output.latitude, spotter_output.longitude, spotter_output.altitude, spotter_output.heading, spotter_output.ground_speed, spotter_output.squawk, a.aircraft_shadow';
                        	    
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
     * Gets count Live Tracker data
     *
     * @param $begindate
     * @param $enddate
     * @param array $filter
     * @return array the spotter information
     */
    public function getLiveTrackerCount($begindate,$enddate,$filter = array())
    {
        global $globalDBdriver, $globalLiveInterval;
        date_default_timezone_set('UTC');

        $filter_query = '';
        if (isset($filter['source']) && !empty($filter['source'])) {
            $filter_query .= " AND format_source IN ('".implode("','",$filter['source'])."') ";
        }
        if (isset($filter['airlines']) && !empty($filter['airlines'])) {
            $filter_query .= " INNER JOIN (SELECT famtrackid FROM spotter_output WHERE spotter_output.airline_icao IN ('".implode("','",$filter['airlines'])."')) so ON so.famtrackid = tracker_archive.famtrackid ";
        }
        if (isset($filter['airlinestype']) && !empty($filter['airlinestype'])) {
            $filter_query .= " INNER JOIN (SELECT famtrackid FROM spotter_output WHERE spotter_output.airline_type = '".$filter['airlinestype']."') sa ON sa.famtrackid = tracker_archive.famtrackid ";
        }
        if (isset($filter['source_aprs']) && !empty($filter['source_aprs'])) {
            $filter_query = " AND format_source = 'aprs' AND source_name IN ('".implode("','",$filter['source_aprs'])."')";
        }

        //if (!isset($globalLiveInterval)) $globalLiveInterval = '200';
        if ($globalDBdriver == 'mysql') {
            $query = 'SELECT COUNT(DISTINCT famtrackid) as nb 
			FROM tracker_archive l 
			WHERE (l.date BETWEEN DATE_SUB('."'".$begindate."'".',INTERVAL '.$globalLiveInterval.' SECOND) AND '."'".$begindate."'".')'.$filter_query;
        } else {
            $query = 'SELECT COUNT(DISTINCT famtrackid) as nb FROM tracker_archive l WHERE (l.date BETWEEN '."'".$begindate."' - INTERVAL '".$globalLiveInterval." SECONDS' AND "."'".$enddate."'".')'.$filter_query;
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



    // tracker_archive_output

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
    public function searchTrackerData($q = '', $registration = '', $aircraft_icao = '', $aircraft_manufacturer = '', $highlights = '', $airline_icao = '', $airline_country = '', $airline_type = '', $airport = '', $airport_country = '', $callsign = '', $departure_airport_route = '', $arrival_airport_route = '', $owner = '',$pilot_id = '',$pilot_name = '',$altitude = '', $date_posted = '', $limit = '', $sort = '', $includegeodata = '',$origLat = '',$origLon = '',$dist = '', $filters=array())
    {
        global $globalTimezone, $globalDBdriver;
        require_once(dirname(__FILE__).'/class.Translation.php');
        $Translation = new Translation($this->db);
        $Tracker = new Tracker($this->db);

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
                    $additional_query .= "(tracker_archive_output.spotter_id like '%".$q_item."%') OR ";
                    $additional_query .= "(tracker_archive_output.aircraft_icao like '%".$q_item."%') OR ";
                    $additional_query .= "(tracker_archive_output.aircraft_name like '%".$q_item."%') OR ";
                    $additional_query .= "(tracker_archive_output.aircraft_manufacturer like '%".$q_item."%') OR ";
                    $additional_query .= "(tracker_archive_output.airline_icao like '%".$q_item."%') OR ";
                    $additional_query .= "(tracker_archive_output.airline_name like '%".$q_item."%') OR ";
                    $additional_query .= "(tracker_archive_output.airline_country like '%".$q_item."%') OR ";
                    $additional_query .= "(tracker_archive_output.departure_airport_icao like '%".$q_item."%') OR ";
                    $additional_query .= "(tracker_archive_output.departure_airport_name like '%".$q_item."%') OR ";
                    $additional_query .= "(tracker_archive_output.departure_airport_city like '%".$q_item."%') OR ";
                    $additional_query .= "(tracker_archive_output.departure_airport_country like '%".$q_item."%') OR ";
                    $additional_query .= "(tracker_archive_output.arrival_airport_icao like '%".$q_item."%') OR ";
                    $additional_query .= "(tracker_archive_output.arrival_airport_name like '%".$q_item."%') OR ";
                    $additional_query .= "(tracker_archive_output.arrival_airport_city like '%".$q_item."%') OR ";
                    $additional_query .= "(tracker_archive_output.arrival_airport_country like '%".$q_item."%') OR ";
                    $additional_query .= "(tracker_archive_output.registration like '%".$q_item."%') OR ";
                    $additional_query .= "(tracker_archive_output.owner_name like '%".$q_item."%') OR ";
                    $additional_query .= "(tracker_archive_output.pilot_id like '%".$q_item."%') OR ";
                    $additional_query .= "(tracker_archive_output.pilot_name like '%".$q_item."%') OR ";
                    $additional_query .= "(tracker_archive_output.ident like '%".$q_item."%') OR ";
                    $translate = $Translation->ident2icao($q_item);
                    if ($translate != $q_item) $additional_query .= "(tracker_archive_output.ident like '%".$translate."%') OR ";
                    $additional_query .= "(tracker_archive_output.highlight like '%".$q_item."%')";
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
                $additional_query .= " AND (tracker_archive_output.registration = '".$registration."')";
            }
        }
	
        if ($aircraft_icao != "")
        {
            $aircraft_icao = filter_var($aircraft_icao,FILTER_SANITIZE_STRING);
            if (!is_string($aircraft_icao))
            {
                return array();
            } else {
                $additional_query .= " AND (tracker_archive_output.aircraft_icao = '".$aircraft_icao."')";
            }
        }
	
        if ($aircraft_manufacturer != "")
        {
            $aircraft_manufacturer = filter_var($aircraft_manufacturer,FILTER_SANITIZE_STRING);
            if (!is_string($aircraft_manufacturer))
            {
                return array();
            } else {
                $additional_query .= " AND (tracker_archive_output.aircraft_manufacturer = '".$aircraft_manufacturer."')";
            }
        }
	
        if ($highlights == "true")
        {
            if (!is_string($highlights))
            {
                return array();
            } else {
                $additional_query .= " AND (tracker_archive_output.highlight <> '')";
            }
        }
	
        if ($airline_icao != "")
        {
            $airline_icao = filter_var($airline_icao,FILTER_SANITIZE_STRING);
            if (!is_string($airline_icao))
            {
                return array();
            } else {
                $additional_query .= " AND (tracker_archive_output.airline_icao = '".$airline_icao."')";
            }
        }
	
        if ($airline_country != "")
        {
            $airline_country = filter_var($airline_country,FILTER_SANITIZE_STRING);
            if (!is_string($airline_country))
            {
                return array();
            } else {
                $additional_query .= " AND (tracker_archive_output.airline_country = '".$airline_country."')";
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
                    $additional_query .= " AND (tracker_archive_output.airline_type = 'passenger')";
                }
                if ($airline_type == "cargo")
                {
                    $additional_query .= " AND (tracker_archive_output.airline_type = 'cargo')";
                }
                if ($airline_type == "military")
                {
                    $additional_query .= " AND (tracker_archive_output.airline_type = 'military')";
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
                $additional_query .= " AND ((tracker_archive_output.departure_airport_icao = '".$airport."') OR (tracker_archive_output.arrival_airport_icao = '".$airport."'))";
            }
        }
	
        if ($airport_country != "")
        {
            $airport_country = filter_var($airport_country,FILTER_SANITIZE_STRING);
            if (!is_string($airport_country))
            {
                return array();
            } else {
                $additional_query .= " AND ((tracker_archive_output.departure_airport_country = '".$airport_country."') OR (tracker_archive_output.arrival_airport_country = '".$airport_country."'))";
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
                    $additional_query .= " AND (tracker_archive_output.ident = :callsign OR tracker_archive_output.ident = :translate)";
                    $query_values = array_merge($query_values,array(':callsign' => $callsign,':translate' => $translate));
                } else {
                    $additional_query .= " AND (tracker_archive_output.ident = '".$callsign."')";
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
                $additional_query .= " AND (tracker_archive_output.owner_name = '".$owner."')";
            }
        }

        if ($pilot_name != "")
        {
            $pilot_name = filter_var($pilot_name,FILTER_SANITIZE_STRING);
            if (!is_string($pilot_name))
            {
                return array();
            } else {
                $additional_query .= " AND (tracker_archive_output.pilot_name = '".$pilot_name."')";
            }
        }
	
        if ($pilot_id != "")
        {
            $pilot_id = filter_var($pilot_id,FILTER_SANITIZE_NUMBER_INT);
            if (!is_string($pilot_id))
            {
                return array();
            } else {
                $additional_query .= " AND (tracker_archive_output.pilot_id = '".$pilot_id."')";
            }
        }
	
        if ($departure_airport_route != "")
        {
            $departure_airport_route = filter_var($departure_airport_route,FILTER_SANITIZE_STRING);
            if (!is_string($departure_airport_route))
            {
                return array();
            } else {
                $additional_query .= " AND (tracker_archive_output.departure_airport_icao = '".$departure_airport_route."')";
            }
        }
	
        if ($arrival_airport_route != "")
        {
            $arrival_airport_route = filter_var($arrival_airport_route,FILTER_SANITIZE_STRING);
            if (!is_string($arrival_airport_route))
            {
                return array();
            } else {
                $additional_query .= " AND (tracker_archive_output.arrival_airport_icao = '".$arrival_airport_route."')";
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
                    $additional_query .= " AND TIMESTAMP(CONVERT_TZ(tracker_archive_output.date,'+00:00', '".$offset."')) >= '".$date_array[0]."' AND TIMESTAMP(CONVERT_TZ(tracker_archive_output.date,'+00:00', '".$offset."')) <= '".$date_array[1]."' ";
                } else {
                    $additional_query .= " AND tracker_archive_output.date::timestamp AT TIME ZONE INTERVAL ".$offset." >= CAST('".$date_array[0]."' AS TIMESTAMP) AND tracker_archive_output.date::timestamp AT TIME ZONE INTERVAL ".$offset." <= CAST('".$date_array[1]."' AS TIMESTAMP) ";
                }
            } else {
                $date_array[0] = date("Y-m-d H:i:s", strtotime($date_array[0]));
                if ($globalDBdriver == 'mysql') {
                    $additional_query .= " AND TIMESTAMP(CONVERT_TZ(tracker_archive_output.date,'+00:00', '".$offset."')) >= '".$date_array[0]."' ";
                } else {
                    $additional_query .= " AND tracker_archive_output.date::timestamp AT TIME ZONE INTERVAL ".$offset." >= CAST('".$date_array[0]."' AS TIMESTAMP) ";
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
            $query="SELECT tracker_archive_output.*, 3956 * 2 * ASIN(SQRT( POWER(SIN(($origLat - ABS(CAST(tracker_archive.latitude as double precision)))*pi()/180/2),2)+COS( $origLat *pi()/180)*COS(ABS(CAST(tracker_archive.latitude as double precision))*pi()/180)*POWER(SIN(($origLon-CAST(tracker_archive.longitude as double precision))*pi()/180/2),2))) as distance 
                          FROM tracker_archive_output, tracker_archive WHERE spotter_output_archive.famtrackid = tracker_archive.famtrackid AND spotter_output.ident <> '' ".$additional_query."AND CAST(tracker_archive.longitude as double precision) between ($origLon-$dist/ABS(cos(radians($origLat))*69)) and ($origLon+$dist/ABS(cos(radians($origLat))*69)) and CAST(tracker_archive.latitude as double precision) between ($origLat-($dist/69)) and ($origLat+($dist/69)) 
                          AND (3956 * 2 * ASIN(SQRT( POWER(SIN(($origLat - ABS(CAST(tracker_archive.latitude as double precision)))*pi()/180/2),2)+COS( $origLat *pi()/180)*COS(ABS(CAST(tracker_archive.latitude as double precision))*pi()/180)*POWER(SIN(($origLon-CAST(tracker_archive.longitude as double precision))*pi()/180/2),2)))) < $dist".$filter_query." ORDER BY distance";
        } else {
            if ($sort != "")
            {
                $search_orderby_array = $Tracker->getOrderBy();
                $orderby_query = $search_orderby_array[$sort]['sql'];
            } else {
                $orderby_query = " ORDER BY tracker_archive_output.date DESC";
            }
	
            if ($includegeodata == "true")
            {
                $additional_query .= " AND (tracker_archive_output.waypoints <> '')";
            }

            $query  = "SELECT tracker_archive_output.* FROM tracker_archive_output 
		    WHERE tracker_archive_output.ident <> '' 
		    ".$additional_query."
		    ".$filter_query.$orderby_query;
        }
        $spotter_array = $Tracker->getDataFromDB($query, $query_values,$limit_query);

        return $spotter_array;
    }

    public function deleteTrackerArchiveData()
    {
        global $globalArchiveKeepMonths, $globalDBdriver;
        date_default_timezone_set('UTC');
        if ($globalDBdriver == 'mysql') {
            $query = 'DELETE FROM tracker_archive_output WHERE tracker_archive_output.date < DATE_SUB(UTC_TIMESTAMP(), INTERVAL '.$globalArchiveKeepMonths.' MONTH)';
        } else {
            $query = "DELETE FROM tracker_archive_output WHERE tracker_archive_output.date < CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$globalArchiveKeepMonths." MONTH'";
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
    public function getTrackerDataByIdent($ident = '', $limit = '', $sort = '')
    {
        $global_query = "SELECT tracker_archive_output.* FROM tracker_archive_output";
	
        date_default_timezone_set('UTC');
        $Tracker = new Tracker($this->db);
	
        $query_values = array();
        $limit_query = '';
        $additional_query = '';
	
        if ($ident != "")
        {
            if (!is_string($ident))
            {
                return array();
            } else {
                $additional_query = " AND (tracker_archive_output.ident = :ident)";
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
            $search_orderby_array = $Tracker->getOrderBy();
            $orderby_query = $search_orderby_array[$sort]['sql'];
        } else {
            $orderby_query = " ORDER BY tracker_archive_output.date DESC";
        }

        $query = $global_query." WHERE tracker_archive_output.ident <> '' ".$additional_query." ".$orderby_query;

        $spotter_array = $Tracker->getDataFromDB($query, $query_values, $limit_query);

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
    public function getTrackerDataByOwner($owner = '', $limit = '', $sort = '', $filter = array())
    {
        $global_query = "SELECT tracker_archive_output.* FROM tracker_archive_output";
	
        date_default_timezone_set('UTC');
        $Tracker = new Tracker($this->db);
	
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
                $additional_query = " AND (tracker_archive_output.owner_name = :owner)";
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
            $search_orderby_array = $Tracker->getOrderBy();
            $orderby_query = $search_orderby_array[$sort]['sql'];
        } else {
            $orderby_query = " ORDER BY tracker_archive_output.date DESC";
        }

        $query = $global_query.$filter_query." tracker_archive_output.owner_name <> '' ".$additional_query." ".$orderby_query;

        $spotter_array = $Tracker->getDataFromDB($query, $query_values, $limit_query);

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
    public function getTrackerDataByPilot($pilot = '', $limit = '', $sort = '', $filter = array())
    {
        $global_query = "SELECT tracker_archive_output.* FROM tracker_archive_output";
	
        date_default_timezone_set('UTC');
        $Tracker = new Tracker($this->db);
	
        $query_values = array();
        $limit_query = '';
        $additional_query = '';
        $filter_query = $this->getFilter($filter,true,true);
	
        if ($pilot != "")
        {
            $additional_query = " AND (tracker_archive_output.pilot_id = :pilot OR tracker_archive_output.pilot_name = :pilot)";
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
            $search_orderby_array = $Tracker->getOrderBy();
            $orderby_query = $search_orderby_array[$sort]['sql'];
        } else {
            $orderby_query = " ORDER BY tracker_archive_output.date DESC";
        }

        $query = $global_query.$filter_query." tracker_archive_output.pilot_name <> '' ".$additional_query." ".$orderby_query;

        $spotter_array = $Tracker->getDataFromDB($query, $query_values, $limit_query);

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
		    FROM countries c, tracker_archive s
		    WHERE Within(GeomFromText(CONCAT('POINT(',s.longitude,' ',s.latitude,')')), ogc_geom) ";
	*/
        $query = "SELECT c.name, c.iso3, c.iso2, count(c.name) as nb
		    FROM countries c, tracker_archive s
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
		    FROM countries c, tracker_archive s
		    WHERE Within(GeomFromText(CONCAT('POINT(',s.longitude,' ',s.latitude,')')), ogc_geom) ";
	*/
        $query = "SELECT o.airline_icao,c.name, c.iso3, c.iso2, count(c.name) as nb
		    FROM countries c, tracker_archive s, spotter_output o
		    WHERE c.iso2 = s.over_country AND o.airline_icao <> '' AND o.famtrackid = s.famtrackid ";
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
    public function getDateArchiveTrackerDataById($id,$date)
    {
        $Tracker = new Tracker($this->db);
        date_default_timezone_set('UTC');
        $id = filter_var($id, FILTER_SANITIZE_STRING);
        $query  = 'SELECT tracker_archive.* FROM tracker_archive INNER JOIN (SELECT l.famtrackid, max(l.date) as maxdate FROM tracker_archive l WHERE l.famtrackid = :id AND l.date <= :date GROUP BY l.famtrackid) s on tracker_archive.famtrackid = s.famtrackid AND tracker_archive.date = s.maxdate ORDER BY tracker_archive.date DESC';
        $date = date('c',$date);
        $spotter_array = $Tracker->getDataFromDB($query,array(':id' => $id,':date' => $date));
        return $spotter_array;
    }

    /**
     * Gets all the spotter information based on a particular callsign
     *
     * @param $ident
     * @param $date
     * @return array the spotter information
     */
    public function getDateArchiveTrackerDataByIdent($ident,$date)
    {
        $Tracker = new Tracker($this->db);
        date_default_timezone_set('UTC');
        $ident = filter_var($ident, FILTER_SANITIZE_STRING);
        $query  = 'SELECT tracker_archive.* FROM tracker_archive INNER JOIN (SELECT l.famtrackid, max(l.date) as maxdate FROM tracker_archive l WHERE l.ident = :ident AND l.date <= :date GROUP BY l.famtrackid) s on tracker_archive.famtrackid = s.famtrackid AND tracker_archive.date = s.maxdate ORDER BY tracker_archive.date DESC';
        $date = date('c',$date);
        $spotter_array = $Tracker->getDataFromDB($query,array(':ident' => $ident,':date' => $date));
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
    public function getTrackerDataByAirport($airport = '', $limit = '', $sort = '',$filters = array())
    {
        global $global_query;
        $Tracker = new Tracker($this->db);
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
                $additional_query .= " AND ((tracker_archive_output.departure_airport_icao = :airport) OR (tracker_archive_output.arrival_airport_icao = :airport))";
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
            $search_orderby_array = $Tracker->getOrderBy();
            $orderby_query = $search_orderby_array[$sort]['sql'];
        } else {
            $orderby_query = " ORDER BY tracker_archive_output.date DESC";
        }

        $query = $global_query.$filter_query." tracker_archive_output.ident <> '' ".$additional_query." AND ((tracker_archive_output.departure_airport_icao <> 'NA') AND (tracker_archive_output.arrival_airport_icao <> 'NA')) ".$orderby_query;

        $spotter_array = $Tracker->getDataFromDB($query, $query_values, $limit_query);

        return $spotter_array;
    }
}
?>