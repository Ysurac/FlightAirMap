<?php
class SpotterArchive {
	public $global_query = "SELECT spotter_archive.* FROM spotter_archive";
	public $db;

	public function __construct($dbc = null) {
		$Connection = new Connection($dbc);
		$this->db = $Connection->db;
		if ($this->db === null) die('Error: No DB connection. (SpotterArchive)');
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
			if (isset($flt['airlines']) && !empty($flt['airlines'])) {
				if ($flt['airlines'][0] != '' && $flt['airlines'][0] != 'all') {
					if (isset($flt['source'])) {
						$filter_query_join .= " INNER JOIN (SELECT flightaware_id FROM spotter_archive_output WHERE spotter_archive_output.airline_icao IN ('".implode("','",$flt['airlines'])."') AND spotter_archive_output.format_source IN ('".implode("','",$flt['source'])."')) saff ON saff.flightaware_id = spotter_archive_output.flightaware_id";
					} else {
						$filter_query_join .= " INNER JOIN (SELECT flightaware_id FROM spotter_archive_output WHERE spotter_archive_output.airline_icao IN ('".implode("','",$flt['airlines'])."')) saff ON saff.flightaware_id = spotter_archive_output.flightaware_id";
					}
				}
			}
			if (isset($flt['pilots_id']) && !empty($flt['pilots_id'])) {
				if (isset($flt['source'])) {
					$filter_query_join .= " INNER JOIN (SELECT flightaware_id FROM spotter_archive_output WHERE spotter_archive_output.pilot_id IN ('".implode("','",$flt['pilots_id'])."') AND spotter_archive_output.format_source IN ('".implode("','",$flt['source'])."')) sp ON sp.flightaware_id = spotter_archive_output.flightaware_id";
				} else {
					$filter_query_join .= " INNER JOIN (SELECT flightaware_id FROM spotter_archive_output WHERE spotter_archive_output.pilot_id IN ('".implode("','",$flt['pilots_id'])."')) sp ON sp.flightaware_id = spotter_archive_output.flightaware_id";
				}
			}
			if (isset($flt['idents']) && !empty($flt['idents'])) {
				if (isset($flt['source'])) {
					$filter_query_join .= " INNER JOIN (SELECT flightaware_id FROM spotter_archive_output WHERE spotter_archive_output.ident IN ('".implode("','",$flt['idents'])."') AND spotter_archive_output.format_source IN ('".implode("','",$flt['source'])."')) spi ON spi.flightaware_id = spotter_archive_output.flightaware_id";
				} else {
					$filter_query_join .= " INNER JOIN (SELECT flightaware_id FROM spotter_archive_output WHERE spotter_archive_output.ident IN ('".implode("','",$flt['idents'])."')) spi ON spi.flightaware_id = spotter_archive_output.flightaware_id";
				}
			}
			if (isset($flt['registrations']) && !empty($flt['registrations'])) {
				if (isset($flt['source'])) {
					$filter_query_join .= " INNER JOIN (SELECT flightaware_id FROM spotter_archive_output WHERE spotter_archive_output.registration IN ('".implode("','",$flt['registrations'])."') AND spotter_archive_output.format_source IN ('".implode("','",$flt['source'])."')) sre ON sre.flightaware_id = spotter_archive_output.flightaware_id";
				} else {
					$filter_query_join .= " INNER JOIN (SELECT flightaware_id FROM spotter_archive_output WHERE spotter_archive_output.registration IN ('".implode("','",$flt['registrations'])."')) sre ON sre.flightaware_id = spotter_archive_output.flightaware_id";
				}
			}
			if ((isset($flt['airlines']) && empty($flt['airlines']) && isset($flt['pilots_id']) && empty($flt['pilots_id']) && isset($flt['idents']) && empty($flt['idents']) && isset($flt['registrations']) && empty($flt['registrations'])) || (!isset($flt['airlines']) && !isset($flt['pilots_id']) && !isset($flt['idents']) && !isset($flt['registrations']))) {
				if (isset($flt['source'])) {
					$filter_query_join .= " INNER JOIN (SELECT flightaware_id FROM spotter_archive_output WHERE spotter_output.format_source IN ('".implode("','",$flt['source'])."')) saa ON saa.flightaware_id = spotter_archive_output.flightaware_id";
				}
			}
		}
		if (isset($filter['airlines']) && !empty($filter['airlines'])) {
			if ($filter['airlines'][0] != '' && $filter['airlines'][0] != 'all') {
				$filter_query_join .= " INNER JOIN (SELECT flightaware_id FROM spotter_archive_output WHERE spotter_archive_output.airline_icao IN ('".implode("','",$filter['airlines'])."')) saf ON saf.flightaware_id = spotter_archive_output.flightaware_id";
			}
		}
		if (isset($filter['airlinestype']) && !empty($filter['airlinestype'])) {
			$filter_query_join .= " INNER JOIN (SELECT flightaware_id FROM spotter_archive_output WHERE spotter_archive_output.airline_type = '".$filter['airlinestype']."') sa ON sa.flightaware_id = spotter_archive_output.flightaware_id ";
		}
		if (isset($filter['pilots_id']) && !empty($filter['pilots_id'])) {
			$filter_query_join .= " INNER JOIN (SELECT flightaware_id FROM spotter_archive_output WHERE spotter_archive_output.pilot_id IN ('".implode("','",$filter['pilots_id'])."')) spi ON spi.flightaware_id = spotter_archive_output.flightaware_id";
		}
		if (isset($filter['source']) && !empty($filter['source'])) {
			if (count($filter['source']) == 1) {
				$filter_query_where .= " AND format_source = '".$filter['source'][0]."'";
			} else {
				$filter_query_where .= " AND format_source IN ('".implode("','",$filter['source'])."')";
			}
		}
		if (isset($filter['ident']) && !empty($filter['ident'])) {
			$filter_query_where .= " AND ident = '".$filter['ident']."'";
		}
		if (isset($filter['id']) && !empty($filter['id'])) {
			$filter_query_where .= " AND flightaware_id = '".$filter['id']."'";
		}
		if (isset($filter['source_aprs']) && !empty($filter['source_aprs'])) {
			$filter_query_where .= " AND format_source = 'aprs' AND source_name IN ('".implode("','",$filter['source_aprs'])."')";
		}
		if ((isset($filter['year']) && $filter['year'] != '') || (isset($filter['month']) && $filter['month'] != '') || (isset($filter['day']) && $filter['day'] != '')) {
			$filter_query_date = '';
			if (isset($filter['year']) && $filter['year'] != '') {
				if ($globalDBdriver == 'mysql') {
					$filter_query_date .= " AND YEAR(spotter_archive_output.date) = '".$filter['year']."'";
				} else {
					$filter_query_date .= " AND EXTRACT(YEAR FROM spotter_archive_output.date) = '".$filter['year']."'";
				}
			}
			if (isset($filter['month']) && $filter['month'] != '') {
				if ($globalDBdriver == 'mysql') {
					$filter_query_date .= " AND MONTH(spotter_archive_output.date) = '".$filter['month']."'";
				} else {
					$filter_query_date .= " AND EXTRACT(MONTH FROM spotter_archive_output.date) = '".$filter['month']."'";
				}
			}
			if (isset($filter['day']) && $filter['day'] != '') {
				if ($globalDBdriver == 'mysql') {
					$filter_query_date .= " AND DAY(spotter_archive_output.date) = '".$filter['day']."'";
				} else {
					$filter_query_date .= " AND EXTRACT(DAY FROM spotter_archive_output.date) = '".$filter['day']."'";
				}
			}
			$filter_query_join .= " INNER JOIN (SELECT flightaware_id FROM spotter_archive_output".preg_replace('/^ AND/',' WHERE',$filter_query_date).") sd ON sd.flightaware_id = spotter_archive_output.flightaware_id";
		}
		if ($filter_query_where == '' && $where) $filter_query_where = ' WHERE';
		elseif ($filter_query_where != '' && $and) $filter_query_where .= ' AND';
		if ($filter_query_where != '') {
			$filter_query_where = preg_replace('/^ AND/',' WHERE',$filter_query_where);
		}
		$filter_query = $filter_query_join.$filter_query_where;
		return $filter_query;
	}

	// Spotter_archive
	public function addSpotterArchiveData($flightaware_id = '', $ident = '', $registration = '', $airline_name = '', $airline_icao = '', $airline_country = '', $airline_type = '', $aircraft_icao = '', $aircraft_shadow = '', $aircraft_name = '', $aircraft_manufacturer = '', $departure_airport_icao = '', $departure_airport_name = '', $departure_airport_city = '', $departure_airport_country = '', $departure_airport_time = '',$arrival_airport_icao = '', $arrival_airport_name = '', $arrival_airport_city ='', $arrival_airport_country = '', $arrival_airport_time = '', $route_stop = '', $date = '',$latitude = '', $longitude = '', $waypoints = '', $altitude = '', $real_altitude = '',$heading = '', $ground_speed = '', $squawk = '', $ModeS = '', $pilot_id = '', $pilot_name = '',$verticalrate = '',$format_source = '', $source_name = '', $over_country = '') {
		require_once(dirname(__FILE__).'/class.Spotter.php');
		if ($over_country == '') {
			$Spotter = new Spotter($this->db);
			$data_country = $Spotter->getCountryFromLatitudeLongitude($latitude,$longitude);
			if (!empty($data_country)) $country = $data_country['iso2'];
			else $country = '';
		} else $country = $over_country;
		if ($airline_type === NULL) $airline_type ='';

		//if ($country == '') echo "\n".'************ UNKNOW COUNTRY ****************'."\n";
		//else echo "\n".'*/*/*/*/*/*/*/ Country : '.$country.' */*/*/*/*/*/*/*/*/'."\n";

		// Route is not added in spotter_archive
		$query  = "INSERT INTO spotter_archive (flightaware_id, ident, registration, airline_name, airline_icao, airline_country, airline_type, aircraft_icao, aircraft_shadow, aircraft_name, aircraft_manufacturer, departure_airport_icao, departure_airport_name, departure_airport_city, departure_airport_country, departure_airport_time,arrival_airport_icao, arrival_airport_name, arrival_airport_city, arrival_airport_country, arrival_airport_time, route_stop, date,latitude, longitude, waypoints, altitude, heading, ground_speed, squawk, ModeS, pilot_id, pilot_name, verticalrate,format_source,over_country,source_name,real_altitude)
		          VALUES (:flightaware_id, :ident, :registration, :airline_name, :airline_icao, :airline_country, :airline_type, :aircraft_icao, :aircraft_shadow, :aircraft_name, :aircraft_manufacturer, :departure_airport_icao, :departure_airport_name, :departure_airport_city, :departure_airport_country, :departure_airport_time,:arrival_airport_icao, :arrival_airport_name, :arrival_airport_city, :arrival_airport_country, :arrival_airport_time, :route_stop, :date,:latitude, :longitude, :waypoints, :altitude, :heading, :ground_speed, :squawk, :ModeS, :pilot_id, :pilot_name, :verticalrate, :format_source, :over_country, :source_name,:real_altitude)";

		$query_values = array(':flightaware_id' => $flightaware_id, ':ident' => $ident, ':registration' => $registration, ':airline_name' => $airline_name, ':airline_icao' => $airline_icao, ':airline_country' => $airline_country, ':airline_type' => $airline_type, ':aircraft_icao' => $aircraft_icao, ':aircraft_shadow' => $aircraft_shadow, ':aircraft_name' => $aircraft_name, ':aircraft_manufacturer' => $aircraft_manufacturer, ':departure_airport_icao' => $departure_airport_icao, ':departure_airport_name' => $departure_airport_name, ':departure_airport_city' => $departure_airport_city, ':departure_airport_country' => $departure_airport_country, ':departure_airport_time' => $departure_airport_time,':arrival_airport_icao' => $arrival_airport_icao, ':arrival_airport_name' => $arrival_airport_name, ':arrival_airport_city' => $arrival_airport_city, ':arrival_airport_country' => $arrival_airport_country, ':arrival_airport_time' => $arrival_airport_time, ':route_stop' => $route_stop, ':date' => $date,':latitude' => $latitude, ':longitude' => $longitude, ':waypoints' => $waypoints, ':altitude' => $altitude, ':heading' => $heading, ':ground_speed' => $ground_speed, ':squawk' => $squawk, ':ModeS' => $ModeS, ':pilot_id' => $pilot_id, ':pilot_name' => $pilot_name, ':verticalrate' => $verticalrate, ':format_source' => $format_source, ':over_country' => $country, ':source_name' => $source_name,':real_altitude' => $real_altitude);
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
	public function getLastArchiveSpotterDataByIdent($ident) {
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
     * @param $id
     * @return array the spotter information
     */
	public function getLastArchiveSpotterDataById($id) {
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
     * @param $id
     * @return array the spotter information
     */
	public function getAllArchiveSpotterDataById($id) {
		date_default_timezone_set('UTC');
		$id = filter_var($id, FILTER_SANITIZE_STRING);
		$query  = $this->global_query." WHERE spotter_archive.flightaware_id = :id ORDER BY date";

//              $spotter_array = Spotter->getDataFromDB($query,array(':id' => $id));

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
	public function getCoordArchiveSpotterDataById($id) {
		date_default_timezone_set('UTC');
		$id = filter_var($id, FILTER_SANITIZE_STRING);
		$query  = "SELECT spotter_archive.latitude, spotter_archive.longitude, spotter_archive.date FROM spotter_archive WHERE spotter_archive.flightaware_id = :id ORDER by spotter_archive.date ASC";
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
     * @param $begindate
     * @param $enddate
     * @return array the spotter information
     */
	public function getCoordArchiveSpotterDataByIdDate($id,$begindate,$enddate) {
		date_default_timezone_set('UTC');
		$id = filter_var($id, FILTER_SANITIZE_STRING);
		$query  = "SELECT spotter_archive.latitude, spotter_archive.longitude, spotter_archive.date FROM spotter_archive WHERE spotter_archive.flightaware_id = :id AND spotter_archive.date BETWEEN :begindate AND :enddate ORDER by spotter_archive.date ASC";
		try {
			$sth = $this->db->prepare($query);
			$sth->execute(array(':id' => $id,':begindate' => $begindate,':enddate' => $enddate));
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
	public function getAltitudeArchiveSpotterDataByIdent($ident) {

		date_default_timezone_set('UTC');

		$ident = filter_var($ident, FILTER_SANITIZE_STRING);
		$query  = "SELECT spotter_archive.altitude, spotter_archive.date FROM spotter_archive WHERE spotter_archive.ident = :ident AND spotter_archive.latitude <> 0 AND spotter_archive.longitude <> 0 ORDER BY date";

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
	public function getAltitudeArchiveSpotterDataById($id) {

		date_default_timezone_set('UTC');

		$id = filter_var($id, FILTER_SANITIZE_STRING);
		$query  = "SELECT spotter_archive.altitude, spotter_archive.date FROM spotter_archive WHERE spotter_archive.flightaware_id = :id AND spotter_archive.latitude <> 0 AND spotter_archive.longitude <> 0 ORDER BY date";

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
	public function getAltitudeSpeedArchiveSpotterDataById($id) {
		date_default_timezone_set('UTC');
		$id = filter_var($id, FILTER_SANITIZE_STRING);
		$query  = "SELECT spotter_archive.altitude, spotter_archive.real_altitude,spotter_archive.ground_speed, spotter_archive.date FROM spotter_archive WHERE spotter_archive.flightaware_id = :id ORDER BY date";
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
	public function getLastAltitudeArchiveSpotterDataByIdent($ident) {
		date_default_timezone_set('UTC');
		$ident = filter_var($ident, FILTER_SANITIZE_STRING);
		$query  = "SELECT spotter_archive.altitude, spotter_archive.date FROM spotter_archive INNER JOIN (SELECT l.flightaware_id, max(l.date) as maxdate FROM spotter_archive l WHERE l.ident = :ident GROUP BY l.flightaware_id) s on spotter_archive.flightaware_id = s.flightaware_id AND spotter_archive.date = s.maxdate LIMIT 1";
//                $query  = "SELECT spotter_archive.altitude, spotter_archive.date FROM spotter_archive WHERE spotter_archive.ident = :ident";
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
     * @param $flightaware_id
     * @param $date
     * @return array the spotter information
     */
	public function getSpotterArchiveData($ident,$flightaware_id,$date) {
		$Spotter = new Spotter($this->db);
		$ident = filter_var($ident, FILTER_SANITIZE_STRING);
		$query  = "SELECT spotter_live.* FROM spotter_live INNER JOIN (SELECT l.flightaware_id, max(l.date) as maxdate FROM spotter_live l WHERE l.ident = :ident AND l.flightaware_id = :flightaware_id AND l.date LIKE :date GROUP BY l.flightaware_id) s on spotter_live.flightaware_id = s.flightaware_id AND spotter_live.date = s.maxdate";
		$spotter_array = $Spotter->getDataFromDB($query,array(':ident' => $ident,':flightaware_id' => $flightaware_id,':date' => $date.'%'));
		return $spotter_array;
	}

	public function deleteSpotterArchiveTrackData() {
		global $globalArchiveKeepTrackMonths, $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			$query = 'DELETE FROM spotter_archive WHERE spotter_archive.date < DATE_SUB(UTC_TIMESTAMP(), INTERVAL '.$globalArchiveKeepTrackMonths.' MONTH) LIMIT 10000';
		} else {
			$query = "DELETE FROM spotter_archive WHERE spotter_archive_id IN (SELECT spotter_archive_id FROM spotter_archive WHERE spotter_archive.date < CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$globalArchiveKeepTrackMonths." MONTH' LIMIT 10000)";
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
     * Delete row in spotter_archive table based on flightaware_id
     * @param $id flightaware_id
     */
    public function deleteSpotterArchiveTrackDataByID($id) {
		global $globalArchiveKeepTrackMonths, $globalDBdriver;
		$query = 'DELETE FROM spotter_archive WHERE spotter_archive.flightaware_id = :id';
		try {
			$sth = $this->db->prepare($query);
			$sth->execute(array(':id' => $id));
		} catch(PDOException $e) {
			echo $e->getMessage();
			die;
		}
	}

    /**
     * Gets Minimal Live Spotter data
     *
     * @param $begindate
     * @param $enddate
     * @param array $filter
     * @param int $part
     * @return array the spotter information
     */
	public function getMinLiveSpotterData($begindate,$enddate,$filter = array(),$part = 0) {
		global $globalDBdriver;
		date_default_timezone_set('UTC');
		//$filter_query = $this->getFilter($filter,true,true);

		$filter_query = '';
		if (isset($filter['source']) && !empty($filter['source'])) {
			$filter_query .= " AND format_source IN ('".implode("','",$filter['source'])."') ";
		}
		// Use spotter_output also ?
		if (isset($filter['airlines']) && !empty($filter['airlines'])) {
			$filter_query .= " INNER JOIN (SELECT flightaware_id FROM spotter_archive_output WHERE spotter_archive_output.airline_icao IN ('".implode("','",$filter['airlines'])."')) so ON so.flightaware_id = spotter_archive.flightaware_id ";
		}
		if (isset($filter['airlinestype']) && !empty($filter['airlinestype'])) {
			$filter_query .= " INNER JOIN (SELECT flightaware_id FROM spotter_archive_output WHERE spotter_archive_output.airline_type = '".$filter['airlinestype']."') sa ON sa.flightaware_id = spotter_archive.flightaware_id ";
		}
		if (isset($filter['source_aprs']) && !empty($filter['source_aprs'])) {
			$filter_query = " AND format_source = 'aprs' AND source_name IN ('".implode("','",$filter['source_aprs'])."')";
		}

		$limit = '';
		if ($part != 0) {
			$limit = ' LIMIT 100 OFFSET '.($part-1)*100;
		}
		if ($globalDBdriver == 'mysql') {
			$query  = 'SELECT spotter_archive.date,spotter_archive.flightaware_id, spotter_archive.ident, spotter_archive.aircraft_icao, spotter_archive.departure_airport_icao as departure_airport, spotter_archive.arrival_airport_icao as arrival_airport, spotter_archive.latitude, spotter_archive.longitude, spotter_archive.altitude, spotter_archive.heading, spotter_archive.ground_speed, spotter_archive.squawk, a.aircraft_shadow,a.engine_type, a.engine_count, a.wake_category
			          FROM spotter_archive
			          INNER JOIN aircraft a on spotter_archive.aircraft_icao = a.icao
			          WHERE spotter_archive.date BETWEEN '."'".$begindate."'".' AND '."'".$enddate."'".'
			          '.$filter_query.' ORDER BY flightaware_id'.$limit;
		} else {
			
			$query  = 'SELECT spotter_archive.flightaware_id, spotter_archive.date, spotter_archive.ident, spotter_archive.aircraft_icao, spotter_archive.departure_airport_icao as departure_airport, spotter_archive.arrival_airport_icao as arrival_airport, spotter_archive.latitude, spotter_archive.longitude, spotter_archive.altitude, spotter_archive.heading, spotter_archive.ground_speed, spotter_archive.squawk, a.aircraft_shadow,a.engine_type, a.engine_count, a.wake_category
			          FROM spotter_archive
			          INNER JOIN aircraft a on spotter_archive.aircraft_icao = a.icao
			          WHERE spotter_archive.date BETWEEN '."'".$begindate."'".' AND '."'".$enddate."'".'
			          '.$filter_query.' ORDER BY flightaware_id'.$limit;
		}

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
     * Gets Minimal Live Spotter data
     *
     * @param $begindate
     * @param $enddate
     * @param array $filter
     * @return array the spotter information
     */
	public function getMinLiveSpotterDataPlayback($begindate,$enddate,$filter = array()) {
		global $globalDBdriver;
		date_default_timezone_set('UTC');

		//$filter_query = $this->getFilter($filter,true,true);

		$filter_query = '';
		if (isset($filter['source']) && !empty($filter['source'])) {
			$filter_query .= " AND format_source IN ('".implode("','",$filter['source'])."') ";
		}
		// Use spotter_output also ?
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
			$query  = 'SELECT a.aircraft_shadow, spotter_archive_output.ident, spotter_archive_output.flightaware_id, spotter_archive_output.aircraft_icao, spotter_archive_output.departure_airport_icao as departure_airport, spotter_archive_output.arrival_airport_icao as arrival_airport, spotter_archive_output.latitude, spotter_archive_output.longitude, spotter_archive_output.altitude, spotter_archive_output.heading, spotter_archive_output.ground_speed, spotter_archive_output.squawk
			          FROM spotter_archive_output
			          LEFT JOIN (SELECT aircraft_shadow,icao FROM aircraft) a ON spotter_archive_output.aircraft_icao = a.icao
			          WHERE (spotter_archive_output.date BETWEEN '."'".$begindate."'".' AND '."'".$enddate."'".')
			          '.$filter_query.' GROUP BY spotter_archive_output.flightaware_id, spotter_archive_output.ident, spotter_archive_output.aircraft_icao, spotter_archive_output.departure_airport_icao, spotter_archive_output.arrival_airport_icao, spotter_archive_output.latitude, spotter_archive_output.longitude, spotter_archive_output.altitude, spotter_archive_output.heading, spotter_archive_output.ground_speed, spotter_archive_output.squawk, a.aircraft_shadow';

		} else {
			//$query  = 'SELECT spotter_archive_output.ident, spotter_archive_output.flightaware_id, spotter_archive_output.aircraft_icao, spotter_archive_output.departure_airport_icao as departure_airport, spotter_archive_output.arrival_airport_icao as arrival_airport, spotter_archive_output.latitude, spotter_archive_output.longitude, spotter_archive_output.altitude, spotter_archive_output.heading, spotter_archive_output.ground_speed, spotter_archive_output.squawk, a.aircraft_shadow FROM spotter_archive_output INNER JOIN (SELECT l.flightaware_id, max(l.date) as maxdate FROM spotter_archive_output l WHERE DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.$globalLiveInterval.' SECOND) <= l.date GROUP BY l.flightaware_id) s on spotter_archive_output.flightaware_id = s.flightaware_id AND spotter_archive_output.date = s.maxdate '.$filter_query.'INNER JOIN (SELECT * FROM aircraft) a on spotter_archive_output.aircraft_icao = a.icao';
			/*
			 $query  = 'SELECT spotter_archive_output.ident, spotter_archive_output.flightaware_id, spotter_archive_output.aircraft_icao, spotter_archive_output.departure_airport_icao as departure_airport, spotter_archive_output.arrival_airport_icao as arrival_airport, spotter_archive_output.latitude, spotter_archive_output.longitude, spotter_archive_output.altitude, spotter_archive_output.heading, spotter_archive_output.ground_speed, spotter_archive_output.squawk, a.aircraft_shadow
			 	    FROM spotter_archive_output
			 	    INNER JOIN (SELECT * FROM aircraft) a on spotter_archive_output.aircraft_icao = a.icao
			 	    WHERE spotter_archive_output.date >= '."'".$begindate."'".' AND spotter_archive_output.date <= '."'".$enddate."'".'
			 	    '.$filter_query.' GROUP BY spotter_archive_output.flightaware_id, spotter_archive_output.ident, spotter_archive_output.aircraft_icao, spotter_archive_output.departure_airport_icao, spotter_archive_output.arrival_airport_icao, spotter_archive_output.latitude, spotter_archive_output.longitude, spotter_archive_output.altitude, spotter_archive_output.heading, spotter_archive_output.ground_speed, spotter_archive_output.squawk, a.aircraft_shadow';
			 */
			$query  = 'SELECT DISTINCT spotter_archive_output.flightaware_id, spotter_archive_output.ident, spotter_archive_output.aircraft_icao, spotter_archive_output.departure_airport_icao as departure_airport, spotter_archive_output.arrival_airport_icao as arrival_airport, spotter_archive_output.latitude, spotter_archive_output.longitude, spotter_archive_output.altitude, spotter_archive_output.heading, spotter_archive_output.ground_speed, spotter_archive_output.squawk, a.aircraft_shadow
			          FROM spotter_archive_output
			          INNER JOIN (SELECT * FROM aircraft) a on spotter_archive_output.aircraft_icao = a.icao
			          WHERE spotter_archive_output.date >= '."'".$begindate."'".' AND spotter_archive_output.date <= '."'".$enddate."'".'
			          '.$filter_query.' LIMIT 200 OFFSET 0';
//                        	    .' GROUP BY spotter_output.flightaware_id, spotter_output.ident, spotter_output.aircraft_icao, spotter_output.departure_airport_icao, spotter_output.arrival_airport_icao, spotter_output.latitude, spotter_output.longitude, spotter_output.altitude, spotter_output.heading, spotter_output.ground_speed, spotter_output.squawk, a.aircraft_shadow';

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
     * Gets count Live Spotter data
     *
     * @param $begindate
     * @param $enddate
     * @param array $filter
     * @return array the spotter information
     */
	public function getLiveSpotterCount($begindate,$enddate,$filter = array()) {
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
		} else {
			$query = 'SELECT COUNT(DISTINCT flightaware_id) as nb FROM spotter_archive l WHERE (l.date BETWEEN '."'".$begindate."' - INTERVAL '".$globalLiveInterval." SECONDS' AND "."'".$enddate."'".')'.$filter_query;
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



	// Spotter_Archive_output

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
	public function searchSpotterData($q = '', $registration = '', $aircraft_icao = '', $aircraft_manufacturer = '', $highlights = '', $airline_icao = '', $airline_country = '', $airline_type = '', $airport = '', $airport_country = '', $callsign = '', $departure_airport_route = '', $arrival_airport_route = '', $owner = '',$pilot_id = '',$pilot_name = '',$altitude = '', $date_posted = '', $limit = '', $sort = '', $includegeodata = '',$origLat = '',$origLon = '',$dist = '', $filters=array()) {
		global $globalTimezone, $globalDBdriver;
		require_once(dirname(__FILE__).'/class.Translation.php');
		$Translation = new Translation($this->db);
		$Spotter = new Spotter($this->db);

		date_default_timezone_set('UTC');

		$query_values = array();
		$additional_query = '';
		$limit_query = '';
		$filter_query = $this->getFilter($filters);
		if ($q != "") {
			if (!is_string($q)) {
				return array();
			} else {

				$q_array = explode(" ", $q);

				foreach ($q_array as $q_item) {
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
					$translate = $Translation->ident2icao($q_item);
					if ($translate != $q_item) $additional_query .= "(spotter_archive_output.ident like '%".$translate."%') OR ";
					$additional_query .= "(spotter_archive_output.highlight like '%".$q_item."%')";
					$additional_query .= ")";
				}
			}
		}

		if ($registration != "") {
			$registration = filter_var($registration,FILTER_SANITIZE_STRING);
			if (!is_string($registration)) {
				return array();
			} else {
				$additional_query .= " AND (spotter_archive_output.registration = '".$registration."')";
			}
		}

		if ($aircraft_icao != "") {
			$aircraft_icao = filter_var($aircraft_icao,FILTER_SANITIZE_STRING);
			if (!is_string($aircraft_icao)) {
				return array();
			} else {
				$additional_query .= " AND (spotter_archive_output.aircraft_icao = '".$aircraft_icao."')";
			}
		}

		if ($aircraft_manufacturer != "") {
			$aircraft_manufacturer = filter_var($aircraft_manufacturer,FILTER_SANITIZE_STRING);
			if (!is_string($aircraft_manufacturer)) {
				return array();
			} else {
				$additional_query .= " AND (spotter_archive_output.aircraft_manufacturer = '".$aircraft_manufacturer."')";
			}
		}

		if ($highlights == "true") {
			if (!is_string($highlights)) {
				return array();
			} else {
				$additional_query .= " AND (spotter_archive_output.highlight <> '')";
			}
		}

		if ($airline_icao != "") {
			$airline_icao = filter_var($airline_icao,FILTER_SANITIZE_STRING);
			if (!is_string($airline_icao)) {
				return array();
			} else {
				$additional_query .= " AND (spotter_archive_output.airline_icao = '".$airline_icao."')";
			}
		}

		if ($airline_country != "") {
			$airline_country = filter_var($airline_country,FILTER_SANITIZE_STRING);
			if (!is_string($airline_country)) {
				return array();
			} else {
				$additional_query .= " AND (spotter_archive_output.airline_country = '".$airline_country."')";
			}
		}

		if ($airline_type != "") {
			$airline_type = filter_var($airline_type,FILTER_SANITIZE_STRING);
			if (!is_string($airline_type)) {
				return array();
			} else {
				if ($airline_type == "passenger") {
					$additional_query .= " AND (spotter_archive_output.airline_type = 'passenger')";
				}
				if ($airline_type == "cargo") {
					$additional_query .= " AND (spotter_archive_output.airline_type = 'cargo')";
				}
				if ($airline_type == "military") {
					$additional_query .= " AND (spotter_archive_output.airline_type = 'military')";
				}
			}
		}

		if ($airport != "") {
			$airport = filter_var($airport,FILTER_SANITIZE_STRING);
			if (!is_string($airport)) {
				return array();
			} else {
				$additional_query .= " AND ((spotter_archive_output.departure_airport_icao = '".$airport."') OR (spotter_archive_output.arrival_airport_icao = '".$airport."'))";
			}
		}

		if ($airport_country != "") {
			$airport_country = filter_var($airport_country,FILTER_SANITIZE_STRING);
			if (!is_string($airport_country)) {
				return array();
			} else {
				$additional_query .= " AND ((spotter_archive_output.departure_airport_country = '".$airport_country."') OR (spotter_archive_output.arrival_airport_country = '".$airport_country."'))";
			}
		}

		if ($callsign != "") {
			$callsign = filter_var($callsign,FILTER_SANITIZE_STRING);
			if (!is_string($callsign)) {
				return array();
			} else {
				$translate = $Translation->ident2icao($callsign);
				if ($translate != $callsign) {
					$additional_query .= " AND (spotter_archive_output.ident = :callsign OR spotter_archive_output.ident = :translate)";
					$query_values = array_merge($query_values,array(':callsign' => $callsign,':translate' => $translate));
				} else {
					$additional_query .= " AND (spotter_archive_output.ident = '".$callsign."')";
				}
			}
		}

		if ($owner != "") {
			$owner = filter_var($owner,FILTER_SANITIZE_STRING);
			if (!is_string($owner)) {
				return array();
			} else {
				$additional_query .= " AND (spotter_archive_output.owner_name = '".$owner."')";
			}
		}

		if ($pilot_name != "") {
			$pilot_name = filter_var($pilot_name,FILTER_SANITIZE_STRING);
			if (!is_string($pilot_name)) {
				return array();
			} else {
				$additional_query .= " AND (spotter_archive_output.pilot_name = '".$pilot_name."')";
			}
		}

		if ($pilot_id != "") {
			$pilot_id = filter_var($pilot_id,FILTER_SANITIZE_NUMBER_INT);
			if (!is_string($pilot_id)) {
				return array();
			} else {
				$additional_query .= " AND (spotter_archive_output.pilot_id = '".$pilot_id."')";
			}
		}

		if ($departure_airport_route != "") {
			$departure_airport_route = filter_var($departure_airport_route,FILTER_SANITIZE_STRING);
			if (!is_string($departure_airport_route)) {
				return array();
			} else {
				$additional_query .= " AND (spotter_archive_output.departure_airport_icao = '".$departure_airport_route."')";
			}
		}

		if ($arrival_airport_route != "") {
			$arrival_airport_route = filter_var($arrival_airport_route,FILTER_SANITIZE_STRING);
			if (!is_string($arrival_airport_route)) {
				return array();
			} else {
				$additional_query .= " AND (spotter_archive_output.arrival_airport_icao = '".$arrival_airport_route."')";
			}
		}

		if ($altitude != "") {
			$altitude_array = explode(",", $altitude);

			$altitude_array[0] = filter_var($altitude_array[0],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
			$altitude_array[1] = filter_var($altitude_array[1],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);


			if ($altitude_array[1] != "") {
				$altitude_array[0] = substr($altitude_array[0], 0, -2);
				$altitude_array[1] = substr($altitude_array[1], 0, -2);
				$additional_query .= " AND altitude BETWEEN '".$altitude_array[0]."' AND '".$altitude_array[1]."' ";
			} else {
				$altitude_array[0] = substr($altitude_array[0], 0, -2);
				$additional_query .= " AND altitude <= '".$altitude_array[0]."' ";
			}
		}

		if ($date_posted != "") {
			$date_array = explode(",", $date_posted);
			$date_array[0] = filter_var($date_array[0],FILTER_SANITIZE_STRING);
			$date_array[1] = filter_var($date_array[1],FILTER_SANITIZE_STRING);
			if ($globalTimezone != '') {
				date_default_timezone_set($globalTimezone);
				$datetime = new DateTime();
				$offset = $datetime->format('P');
			} else $offset = '+00:00';
			if ($date_array[1] != "") {
				$date_array[0] = date("Y-m-d H:i:s", strtotime($date_array[0]));
				$date_array[1] = date("Y-m-d H:i:s", strtotime($date_array[1]));
				if ($globalDBdriver == 'mysql') {
					$additional_query .= " AND TIMESTAMP(CONVERT_TZ(spotter_archive_output.date,'+00:00', '".$offset."')) >= '".$date_array[0]."' AND TIMESTAMP(CONVERT_TZ(spotter_archive_output.date,'+00:00', '".$offset."')) <= '".$date_array[1]."' ";
				} else {
					$additional_query .= " AND spotter_archive_output.date::timestamp AT TIME ZONE INTERVAL ".$offset." >= CAST('".$date_array[0]."' AS TIMESTAMP) AND spotter_archive_output.date::timestamp AT TIME ZONE INTERVAL ".$offset." <= CAST('".$date_array[1]."' AS TIMESTAMP) ";
				}
			} else {
				$date_array[0] = date("Y-m-d H:i:s", strtotime($date_array[0]));
				if ($globalDBdriver == 'mysql') {
					$additional_query .= " AND TIMESTAMP(CONVERT_TZ(spotter_archive_output.date,'+00:00', '".$offset."')) >= '".$date_array[0]."' ";
				} else {
					$additional_query .= " AND spotter_archive_output.date::timestamp AT TIME ZONE INTERVAL ".$offset." >= CAST('".$date_array[0]."' AS TIMESTAMP) ";
				}
			}
		}
		if ($limit != "") {
			$limit_array = explode(",", $limit);
			$limit_array[0] = filter_var($limit_array[0],FILTER_SANITIZE_NUMBER_INT);
			$limit_array[1] = filter_var($limit_array[1],FILTER_SANITIZE_NUMBER_INT);
			if ($limit_array[0] >= 0 && $limit_array[1] >= 0) {
				//$limit_query = " LIMIT ".$limit_array[0].",".$limit_array[1];
				$limit_query = " LIMIT ".$limit_array[1]." OFFSET ".$limit_array[0];
			}
		}
		if ($origLat != "" && $origLon != "" && $dist != "") {
			$dist = number_format($dist*0.621371,2,'.','');
			$query="SELECT spotter_archive_output.*, 3956 * 2 * ASIN(SQRT( POWER(SIN(($origLat - ABS(CAST(spotter_archive.latitude as double precision)))*pi()/180/2),2)+COS( $origLat *pi()/180)*COS(ABS(CAST(spotter_archive.latitude as double precision))*pi()/180)*POWER(SIN(($origLon-CAST(spotter_archive.longitude as double precision))*pi()/180/2),2))) as distance
			       FROM spotter_archive_output, spotter_archive WHERE spotter_output_archive.flightaware_id = spotter_archive.flightaware_id AND spotter_output.ident <> '' ".$additional_query."AND CAST(spotter_archive.longitude as double precision) between ($origLon-$dist/ABS(cos(radians($origLat))*69)) and ($origLon+$dist/ABS(cos(radians($origLat))*69)) and CAST(spotter_archive.latitude as double precision) between ($origLat-($dist/69)) and ($origLat+($dist/69))
			       AND (3956 * 2 * ASIN(SQRT( POWER(SIN(($origLat - ABS(CAST(spotter_archive.latitude as double precision)))*pi()/180/2),2)+COS( $origLat *pi()/180)*COS(ABS(CAST(spotter_archive.latitude as double precision))*pi()/180)*POWER(SIN(($origLon-CAST(spotter_archive.longitude as double precision))*pi()/180/2),2)))) < $dist".$filter_query." ORDER BY distance";
		} else {
			if ($sort != "") {
				$search_orderby_array = $Spotter->getOrderBy();
				$orderby_query = $search_orderby_array[$sort]['sql'];
			} else {
				$orderby_query = " ORDER BY spotter_archive_output.date DESC";
			}
			if ($includegeodata == "true") {
				$additional_query .= " AND (spotter_archive_output.waypoints <> '')";
			}

			$query  = "SELECT spotter_archive_output.* FROM spotter_archive_output
			          WHERE spotter_archive_output.ident <> ''
			          ".$additional_query."
			          ".$filter_query.$orderby_query;
		}
		$spotter_array = $Spotter->getDataFromDB($query, $query_values,$limit_query);
		return $spotter_array;
	}

	public function deleteSpotterArchiveData() {
		global $globalArchiveKeepMonths, $globalDBdriver;
		date_default_timezone_set('UTC');
		if ($globalDBdriver == 'mysql') {
			$query = 'DELETE FROM spotter_archive_output WHERE spotter_archive_output.date < DATE_SUB(UTC_TIMESTAMP(), INTERVAL '.$globalArchiveKeepMonths.' MONTH) LIMIT 10000';
		} else {
			$query = "DELETE FROM spotter_archive_output WHERE spotter_archive_id IN (SELECT spotter_archive_id FROM spotter_archive_output WHERE spotter_archive_output.date < CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$globalArchiveKeepMonths." MONTH' LIMIT 10000)";
		}
		try {
			$sth = $this->db->prepare($query);
			$sth->execute();
			return '';
		} catch(PDOException $e) {
			return "error";
		}
	}

    /**
     * Gets all the spotter information based on the callsign
     *
     * @param string $ident
     * @param string $limit
     * @param string $sort
     * @return array the spotter information
     */
	public function getSpotterDataByIdent($ident = '', $limit = '', $sort = '') {
		$global_query = "SELECT spotter_archive_output.* FROM spotter_archive_output";

		date_default_timezone_set('UTC');
		$Spotter = new Spotter($this->db);

		$query_values = array();
		$limit_query = '';
		$additional_query = '';

		if ($ident != "") {
			if (!is_string($ident)) {
				return array();
			} else {
				$additional_query = " AND spotter_archive_output.ident = :ident";
				$query_values = array(':ident' => $ident);
			}
		}

		if ($limit != "") {
			$limit_array = explode(",", $limit);

			$limit_array[0] = filter_var($limit_array[0],FILTER_SANITIZE_NUMBER_INT);
			$limit_array[1] = filter_var($limit_array[1],FILTER_SANITIZE_NUMBER_INT);

			if ($limit_array[0] >= 0 && $limit_array[1] >= 0) {
				//$limit_query = " LIMIT ".$limit_array[0].",".$limit_array[1];
				$limit_query = " LIMIT ".$limit_array[1]." OFFSET ".$limit_array[0];
			}
		}

		if ($sort != "") {
			$search_orderby_array = $Spotter->getOrderBy();
			$orderby_query = $search_orderby_array[$sort]['sql'];
		} else {
			$orderby_query = " ORDER BY spotter_archive_output.date DESC";
		}

		$query = $global_query." WHERE spotter_archive_output.ident <> '' ".$additional_query." ".$orderby_query;

		$spotter_array = $Spotter->getDataFromDB($query, $query_values, $limit_query);

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
	public function getSpotterDataByOwner($owner = '', $limit = '', $sort = '', $filter = array()) {
		$global_query = "SELECT spotter_archive_output.* FROM spotter_archive_output";

		date_default_timezone_set('UTC');
		$Spotter = new Spotter($this->db);

		$query_values = array();
		$limit_query = '';
		$additional_query = '';
		$filter_query = $this->getFilter($filter,true,true);

		if ($owner != "") {
			if (!is_string($owner)) {
				return array();
			} else {
				$additional_query = " AND (spotter_archive_output.owner_name = :owner)";
				$query_values = array(':owner' => $owner);
			}
		}

		if ($limit != "") {
			$limit_array = explode(",", $limit);

			$limit_array[0] = filter_var($limit_array[0],FILTER_SANITIZE_NUMBER_INT);
			$limit_array[1] = filter_var($limit_array[1],FILTER_SANITIZE_NUMBER_INT);

			if ($limit_array[0] >= 0 && $limit_array[1] >= 0 && $limit_array[0] != '' && $limit_array[1] != '') {
				//$limit_query = " LIMIT ".$limit_array[0].",".$limit_array[1];
				$limit_query = " LIMIT ".$limit_array[1]." OFFSET ".$limit_array[0];
			}
		}

		if ($sort != "") {
			$search_orderby_array = $Spotter->getOrderBy();
			$orderby_query = $search_orderby_array[$sort]['sql'];
		} else {
			$orderby_query = " ORDER BY spotter_archive_output.date DESC";
		}

		$query = $global_query.$filter_query." spotter_archive_output.owner_name <> '' ".$additional_query." ".$orderby_query;

		$spotter_array = $Spotter->getDataFromDB($query, $query_values, $limit_query);

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
	public function getSpotterDataByPilot($pilot = '', $limit = '', $sort = '', $filter = array()) {
		$global_query = "SELECT spotter_archive_output.* FROM spotter_archive_output";

		date_default_timezone_set('UTC');
		$Spotter = new Spotter($this->db);

		$query_values = array();
		$limit_query = '';
		$additional_query = '';
		$filter_query = $this->getFilter($filter,true,true);

		if ($pilot != "") {
			$additional_query = " AND (spotter_archive_output.pilot_id = :pilot OR spotter_archive_output.pilot_name = :pilot)";
			$query_values = array(':pilot' => $pilot);
		}

		if ($limit != "") {
			$limit_array = explode(",", $limit);

			$limit_array[0] = filter_var($limit_array[0],FILTER_SANITIZE_NUMBER_INT);
			$limit_array[1] = filter_var($limit_array[1],FILTER_SANITIZE_NUMBER_INT);

			if ($limit_array[0] >= 0 && $limit_array[1] >= 0) {
				//$limit_query = " LIMIT ".$limit_array[0].",".$limit_array[1];
				$limit_query = " LIMIT ".$limit_array[1]." OFFSET ".$limit_array[0];
			}
		}

		if ($sort != "") {
			$search_orderby_array = $Spotter->getOrderBy();
			$orderby_query = $search_orderby_array[$sort]['sql'];
		} else {
			$orderby_query = " ORDER BY spotter_archive_output.date DESC";
		}

		$query = $global_query.$filter_query." spotter_archive_output.pilot_name <> '' ".$additional_query." ".$orderby_query;

		$spotter_array = $Spotter->getDataFromDB($query, $query_values, $limit_query);

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
	public function countAllFlightOverCountries($limit = true,$olderthanmonths = 0,$sincedate = '') {
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

		while($row = $sth->fetch(PDO::FETCH_ASSOC)) {
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
	public function countAllFlightOverCountriesByAirlines($limit = true,$olderthanmonths = 0,$sincedate = '') {
		global $globalDBdriver;
		/*
		$query = "SELECT c.name, c.iso3, c.iso2, count(c.name) as nb
			    FROM countries c, spotter_archive s
			    WHERE Within(GeomFromText(CONCAT('POINT(',s.longitude,' ',s.latitude,')')), ogc_geom) ";
		*/
		$query = "SELECT o.airline_icao,c.name, c.iso3, c.iso2, count(c.name) as nb
		         FROM countries c, spotter_archive s, spotter_output o
		         WHERE c.iso2 = s.over_country AND o.airline_icao <> '' AND o.flightaware_id = s.flightaware_id ";
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

		while($row = $sth->fetch(PDO::FETCH_ASSOC)) {
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
     * Gets all aircraft types that have flown over by owner
     *
     * @param $owner
     * @param array $filters
     * @return array the aircraft list
     */
	public function countAllAircraftTypesByOwner($owner,$filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$owner = filter_var($owner,FILTER_SANITIZE_STRING);
		$query  = "SELECT DISTINCT spotter_archive_output.aircraft_icao, COUNT(spotter_archive_output.aircraft_icao) AS aircraft_icao_count, spotter_archive_output.aircraft_name, spotter_archive_output.aircraft_manufacturer 
		    FROM spotter_archive_output".$filter_query." spotter_archive_output.owner_name = :owner";
		$query_values = array();
		$query .= " GROUP BY spotter_archive_output.aircraft_name, spotter_archive_output.aircraft_manufacturer, spotter_archive_output.aircraft_icao
		    ORDER BY aircraft_icao_count DESC";
		$query_values = array_merge($query_values,array(':owner' => $owner));
		$sth = $this->db->prepare($query);
		$sth->execute($query_values);
		return $sth->fetchAll(PDO::FETCH_ASSOC);
	}

    /**
     * Gets all airlines by owner
     *
     * @param $owner
     * @param array $filters
     * @return array the airline list
     */
	public function countAllAirlinesByOwner($owner,$filters = array())
	{
		$owner = filter_var($owner,FILTER_SANITIZE_STRING);
		$filter_query = $this->getFilter($filters,true,true);
		$query  = "SELECT DISTINCT spotter_archive_output.airline_name, spotter_archive_output.airline_icao, spotter_archive_output.airline_country, COUNT(spotter_archive_output.airline_name) AS airline_count
		    FROM spotter_archive_output".$filter_query." spotter_archive_output.owner_name = :owner  
		    GROUP BY spotter_archive_output.airline_icao, spotter_archive_output.airline_name, spotter_archive_output.airline_country
		    ORDER BY airline_count DESC";
	
		$sth = $this->db->prepare($query);
		$sth->execute(array(':owner' => $owner));
		return $sth->fetchAll(PDO::FETCH_ASSOC);
	}

    /**
     * Gets all arrival airports by country of the airplanes that have flown over based on a owner
     *
     * @param $owner
     * @param array $filters
     * @return array the airport list
     */
	public function countAllArrivalAirportCountriesByOwner($owner, $filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$owner = filter_var($owner,FILTER_SANITIZE_STRING);
		$query  = "SELECT DISTINCT spotter_archive_output.arrival_airport_country, COUNT(spotter_archive_output.arrival_airport_country) AS airport_arrival_country_count, countries.iso3 AS airport_arrival_country_iso3 
		    FROM countries, spotter_archive_output".$filter_query." spotter_archive_output.arrival_airport_country <> '' AND spotter_archive_output.owner_name = :owner AND countries.name = spotter_archive_output.arrival_airport_country 
		    GROUP BY spotter_archive_output.arrival_airport_country, countries.iso3
		    ORDER BY airport_arrival_country_count DESC";
		$sth = $this->db->prepare($query);
		$sth->execute(array(':owner' => $owner));
		return $sth->fetchAll(PDO::FETCH_ASSOC);
	}

    /**
     * Gets all arrival airports of the airplanes that have flown over based on a owner
     *
     * @param $owner
     * @param array $filters
     * @return array the airport list
     */
	public function countAllArrivalAirportsByOwner($owner,$filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$owner = filter_var($owner,FILTER_SANITIZE_STRING);
		$query  = "SELECT DISTINCT spotter_archive_output.arrival_airport_icao, COUNT(spotter_archive_output.arrival_airport_icao) AS airport_arrival_icao_count, spotter_archive_output.arrival_airport_name, spotter_archive_output.arrival_airport_city, spotter_archive_output.arrival_airport_country 
		    FROM spotter_archive_output".$filter_query." spotter_archive_output.arrival_airport_name <> '' AND spotter_archive_output.arrival_airport_icao <> 'NA' AND spotter_archive_output.arrival_airport_icao <> '' AND spotter_archive_output.owner_name = :owner 
		    GROUP BY spotter_archive_output.arrival_airport_icao, spotter_archive_output.arrival_airport_name, spotter_archive_output.arrival_airport_city, spotter_archive_output.arrival_airport_country
		    ORDER BY airport_arrival_icao_count DESC";
		$sth = $this->db->prepare($query);
		$sth->execute(array(':owner' => $owner));
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
     * Gets all departure airports by country of the airplanes that have flown over based on owner
     *
     * @param $owner
     * @param array $filters
     * @return array the airport list
     */
	public function countAllDepartureAirportCountriesByOwner($owner,$filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$owner = filter_var($owner,FILTER_SANITIZE_STRING);
		$query  = "SELECT DISTINCT spotter_archive_output.departure_airport_country, COUNT(spotter_archive_output.departure_airport_country) AS airport_departure_country_count, countries.iso3 AS airport_departure_country_iso3
		    FROM spotter_archive_output,countries".$filter_query." spotter_archive_output.departure_airport_country <> '' AND spotter_archive_output.owner_name = :owner  AND countries.name = spotter_archive_output.departure_airport_country 
		    GROUP BY spotter_archive_output.departure_airport_country, countries.iso3
		    ORDER BY airport_departure_country_count DESC";
		$sth = $this->db->prepare($query);
		$sth->execute(array(':owner' => $owner));
		return $sth->fetchAll(PDO::FETCH_ASSOC);
	}

    /**
     * Gets all departure airports of the airplanes that have flown over based on a owner
     *
     * @param $owner
     * @param array $filters
     * @return array the airport list
     */
	public function countAllDepartureAirportsByOwner($owner,$filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$owner = filter_var($owner,FILTER_SANITIZE_STRING);
		$query  = "SELECT DISTINCT spotter_archive_output.departure_airport_icao, COUNT(spotter_archive_output.departure_airport_icao) AS airport_departure_icao_count, spotter_archive_output.departure_airport_name, spotter_archive_output.departure_airport_city, spotter_archive_output.departure_airport_country, airport.latitude, airport.longitude 
		    FROM spotter_archive_output,airport".$filter_query." spotter_archive_output.departure_airport_name <> '' AND spotter_archive_output.departure_airport_icao <> 'NA' AND spotter_archive_output.departure_airport_icao <> '' AND spotter_archive_output.owner_name = :owner AND airport.icao = spotter_archive_output.departure_airport_icao 
		    GROUP BY spotter_archive_output.departure_airport_icao, spotter_archive_output.departure_airport_name, spotter_archive_output.departure_airport_city, spotter_archive_output.departure_airport_country, airport.latitude, airport.longitude
		    ORDER BY airport_departure_icao_count DESC";
		$sth = $this->db->prepare($query);
		$sth->execute(array(':owner' => $owner));
		$airport_array = array();
		$temp_array = array();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airport_departure_icao'] = $row['departure_airport_icao'];
			$temp_array['airport_departure_icao_count'] = $row['airport_departure_icao_count'];
			$temp_array['airport_departure_name'] = $row['departure_airport_name'];
			$temp_array['airport_departure_city'] = $row['departure_airport_city'];
			$temp_array['airport_departure_country'] = $row['departure_airport_country'];
			$temp_array['airport_departure_latitude'] = $row['latitude'];
			$temp_array['airport_departure_longitude'] = $row['longitude'];
			$airport_array[] = $temp_array;
		}
		return $airport_array;
	}

    /**
     * Gets all aircraft manufacturer that have flown over by owner
     *
     * @param $owner
     * @param array $filters
     * @return array the aircraft manufacturer list
     */
	public function countAllAircraftManufacturerByOwner($owner,$filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$owner = filter_var($owner,FILTER_SANITIZE_STRING);
		$query  = "SELECT DISTINCT spotter_archive_output.aircraft_manufacturer, COUNT(spotter_archive_output.aircraft_manufacturer) AS aircraft_manufacturer_count  
		    FROM spotter_archive_output".$filter_query." spotter_archive_output.aircraft_manufacturer <> '' AND spotter_archive_output.owner_name = :owner";
		$query_values = array();
		$query_values = array_merge($query_values,array(':owner' => $owner));
		$query .= " GROUP BY spotter_archive_output.aircraft_manufacturer 
		    ORDER BY aircraft_manufacturer_count DESC";
		$sth = $this->db->prepare($query);
		$sth->execute($query_values);
		return $sth->fetchAll(PDO::FETCH_ASSOC);
	}

    /**
     * Gets all aircraft registration that have flown over by owner
     *
     * @param $owner
     * @param array $filters
     * @return array the aircraft list
     */
	public function countAllAircraftRegistrationByOwner($owner,$filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$Image = new Image($this->db);
		$owner = filter_var($owner,FILTER_SANITIZE_STRING);
		$query  = "SELECT DISTINCT spotter_archive_output.aircraft_icao, COUNT(spotter_archive_output.registration) AS registration_count, spotter_archive_output.aircraft_name, spotter_archive_output.aircraft_manufacturer, spotter_archive_output.registration, spotter_archive_output.airline_name  
		    FROM spotter_archive_output".$filter_query." spotter_archive_output.registration <> '' AND spotter_archive_output.owner_name = :owner";
		$query_values = array();
		$query_values = array_merge($query_values,array(':owner' => $owner));
		$query .= " GROUP BY spotter_archive_output.registration,spotter_archive_output.aircraft_icao, spotter_archive_output.aircraft_name, spotter_archive_output.aircraft_manufacturer, spotter_archive_output.airline_name
		    ORDER BY registration_count DESC";
		$sth = $this->db->prepare($query);
		$sth->execute($query_values);
		$aircraft_array = array();
		$temp_array = array();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['aircraft_icao'] = $row['aircraft_icao'];
			$temp_array['aircraft_name'] = $row['aircraft_name'];
			$temp_array['aircraft_manufacturer'] = $row['aircraft_manufacturer'];
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
     * Gets all route combinations based on an owner
     *
     * @param $owner
     * @param array $filters
     * @return array the route list
     */
	public function countAllRoutesByOwner($owner,$filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$owner = filter_var($owner,FILTER_SANITIZE_STRING);
		$query  = "SELECT DISTINCT concat(spotter_archive_output.departure_airport_icao, ' - ',  spotter_archive_output.arrival_airport_icao) AS route, count(concat(spotter_archive_output.departure_airport_icao, ' - ', spotter_archive_output.arrival_airport_icao)) AS route_count, spotter_archive_output.departure_airport_icao, spotter_archive_output.departure_airport_name AS airport_departure_name, spotter_archive_output.departure_airport_city AS airport_departure_city, spotter_archive_output.departure_airport_country AS airport_departure_country, spotter_archive_output.arrival_airport_icao, spotter_archive_output.arrival_airport_name AS airport_arrival_name, spotter_archive_output.arrival_airport_city AS airport_arrival_city, spotter_archive_output.arrival_airport_country AS airport_arrival_country
		    FROM spotter_archive_output".$filter_query." spotter_archive_output.ident <> '' AND spotter_archive_output.owner_name = :owner 
		    GROUP BY route, spotter_archive_output.departure_airport_icao, spotter_archive_output.departure_airport_name, spotter_archive_output.departure_airport_city, spotter_archive_output.departure_airport_country, spotter_archive_output.arrival_airport_icao, spotter_archive_output.arrival_airport_name, spotter_archive_output.arrival_airport_city, spotter_archive_output.arrival_airport_country
		    ORDER BY route_count DESC";
		$sth = $this->db->prepare($query);
		$sth->execute(array(':owner' => $owner));
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
     * Counts all hours by a owner
     *
     * @param $owner
     * @param array $filters
     * @return array the hour list
     */
	public function countAllHoursByOwner($owner, $filters = array())
	{
		global $globalTimezone, $globalDBdriver;
		$filter_query = $this->getFilter($filters,true,true);
		$owner = filter_var($owner,FILTER_SANITIZE_STRING);
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime();
			$offset = $datetime->format('P');
		} else $offset = '+00:00';
		if ($globalDBdriver == 'mysql') {
			$query  = "SELECT HOUR(CONVERT_TZ(spotter_archive_output.date,'+00:00', :offset)) AS hour_name, count(*) as hour_count
			    FROM spotter_archive_output".$filter_query." spotter_archive_output.owner_name = :owner 
			    GROUP BY hour_name 
			    ORDER BY hour_name ASC";
		} else {
			$query  = "SELECT EXTRACT(HOUR FROM spotter_archive_output.date AT TIME ZONE INTERVAL :offset) AS hour_name, count(*) as hour_count
			    FROM spotter_archive_output".$filter_query." spotter_archive_output.owner_name = :owner 
			    GROUP BY hour_name 
			    ORDER BY hour_name ASC";
		}
		$sth = $this->db->prepare($query);
		$sth->execute(array(':owner' => $owner,':offset' => $offset));
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
     * Gets last spotter information based on a particular callsign
     *
     * @param $id
     * @param $date
     * @return array the spotter information
     */
	public function getDateArchiveSpotterDataById($id,$date) {
		$Spotter = new Spotter($this->db);
		date_default_timezone_set('UTC');
		$id = filter_var($id, FILTER_SANITIZE_STRING);
		$query  = 'SELECT spotter_archive.* FROM spotter_archive INNER JOIN (SELECT l.flightaware_id, max(l.date) as maxdate FROM spotter_archive l WHERE l.flightaware_id = :id AND l.date <= :date GROUP BY l.flightaware_id) s on spotter_archive.flightaware_id = s.flightaware_id AND spotter_archive.date = s.maxdate ORDER BY spotter_archive.date DESC';
		$date = date('c',$date);
		$spotter_array = $Spotter->getDataFromDB($query,array(':id' => $id,':date' => $date));
		return $spotter_array;
	}

    /**
     * Gets all the spotter information based on a particular callsign
     *
     * @param $ident
     * @param $date
     * @return array the spotter information
     */
	public function getDateArchiveSpotterDataByIdent($ident,$date) {
		$Spotter = new Spotter($this->db);
		date_default_timezone_set('UTC');
		$ident = filter_var($ident, FILTER_SANITIZE_STRING);
		$query  = 'SELECT spotter_archive.* FROM spotter_archive INNER JOIN (SELECT l.flightaware_id, max(l.date) as maxdate FROM spotter_archive l WHERE l.ident = :ident AND l.date <= :date GROUP BY l.flightaware_id) s on spotter_archive.flightaware_id = s.flightaware_id AND spotter_archive.date = s.maxdate ORDER BY spotter_archive.date DESC';
		$date = date('c',$date);
		$spotter_array = $Spotter->getDataFromDB($query,array(':ident' => $ident,':date' => $date));
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
	public function getSpotterDataByAirport($airport = '', $limit = '', $sort = '',$filters = array()) {
		global $global_query;
		$Spotter = new Spotter($this->db);
		date_default_timezone_set('UTC');
		$query_values = array();
		$limit_query = '';
		$additional_query = '';
		$filter_query = $this->getFilter($filters,true,true);

		if ($airport != "") {
			if (!is_string($airport)) {
				return array();
			} else {
				$additional_query .= " AND ((spotter_archive_output.departure_airport_icao = :airport) OR (spotter_archive_output.arrival_airport_icao = :airport))";
				$query_values = array(':airport' => $airport);
			}
		}

		if ($limit != "") {
			$limit_array = explode(",", $limit);

			$limit_array[0] = filter_var($limit_array[0],FILTER_SANITIZE_NUMBER_INT);
			$limit_array[1] = filter_var($limit_array[1],FILTER_SANITIZE_NUMBER_INT);

			if ($limit_array[0] >= 0 && $limit_array[1] >= 0) {
				//$limit_query = " LIMIT ".$limit_array[0].",".$limit_array[1];
				$limit_query = " LIMIT ".$limit_array[1]." OFFSET ".$limit_array[0];
			}
		}

		if ($sort != "") {
			$search_orderby_array = $Spotter->getOrderBy();
			$orderby_query = $search_orderby_array[$sort]['sql'];
		} else {
			$orderby_query = " ORDER BY spotter_archive_output.date DESC";
		}

		$query = $global_query.$filter_query." spotter_archive_output.ident <> '' ".$additional_query." AND ((spotter_archive_output.departure_airport_icao <> 'NA') AND (spotter_archive_output.arrival_airport_icao <> 'NA')) ".$orderby_query;

		$spotter_array = $Spotter->getDataFromDB($query, $query_values, $limit_query);

		return $spotter_array;
	}
}
?>