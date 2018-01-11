<?php
//$global_query = "SELECT spotter_live.* FROM spotter_live";

class SpotterLive {
	public $db;
	static $global_query = "SELECT spotter_live.* FROM spotter_live";

	public function __construct($dbc = null) {
		$Connection = new Connection($dbc);
		$this->db = $Connection->db();
		if ($this->db === null) die('Error: No DB connection. (SpotterLive)');
	}


	/**
	* Get SQL query part for filter used
	* @param array $filter the filter
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
						$filter_query_join .= " INNER JOIN (SELECT flightaware_id FROM spotter_output WHERE spotter_output.airline_icao IN ('".implode("','",$flt['airlines'])."') AND spotter_output.format_source IN ('".implode("','",$flt['source'])."')) saf ON saf.flightaware_id = spotter_live.flightaware_id";
					} else {
						$filter_query_join .= " INNER JOIN (SELECT flightaware_id FROM spotter_output WHERE spotter_output.airline_icao IN ('".implode("','",$flt['airlines'])."')) saf ON saf.flightaware_id = spotter_live.flightaware_id";
					}
				}
			}
			if (isset($flt['pilots_id']) && !empty($flt['pilots_id'])) {
				if (isset($flt['source'])) {
					$filter_query_join .= " INNER JOIN (SELECT flightaware_id FROM spotter_output WHERE spotter_output.pilot_id IN ('".implode("','",$flt['pilots_id'])."') AND spotter_output.format_source IN ('".implode("','",$flt['source'])."')) spi ON spi.flightaware_id = spotter_live.flightaware_id";
				} else {
					$filter_query_join .= " INNER JOIN (SELECT flightaware_id FROM spotter_output WHERE spotter_output.pilot_id IN ('".implode("','",$flt['pilots_id'])."')) spi ON spi.flightaware_id = spotter_live.flightaware_id";
				}
			}
			if (isset($flt['idents']) && !empty($flt['idents'])) {
				if (isset($flt['source'])) {
					$filter_query_join .= " INNER JOIN (SELECT flightaware_id FROM spotter_output WHERE spotter_output.ident IN ('".implode("','",$flt['idents'])."') AND spotter_output.format_source IN ('".implode("','",$flt['source'])."')) spid ON spid.flightaware_id = spotter_live.flightaware_id";
				} else {
					$filter_query_join .= " INNER JOIN (SELECT flightaware_id FROM spotter_output WHERE spotter_output.ident IN ('".implode("','",$flt['idents'])."')) spid ON spid.flightaware_id = spotter_live.flightaware_id";
				}
			}
			if (isset($flt['registrations']) && !empty($flt['registrations'])) {
				if (isset($flt['source'])) {
					$filter_query_join .= " INNER JOIN (SELECT flightaware_id FROM spotter_output WHERE spotter_output.registration IN ('".implode("','",$flt['registrations'])."') AND spotter_output.format_source IN ('".implode("','",$flt['source'])."')) sre ON sre.flightaware_id = spotter_live.flightaware_id";
				} else {
					$filter_query_join .= " INNER JOIN (SELECT flightaware_id FROM spotter_output WHERE spotter_output.registration IN ('".implode("','",$flt['registrations'])."')) sre ON sre.flightaware_id = spotter_live.flightaware_id";
				}
			}
			if ((isset($flt['airlines']) && empty($flt['airlines']) && isset($flt['pilots_id']) && empty($flt['pilots_id']) && isset($flt['idents']) && empty($flt['idents'])) || (!isset($flt['airlines']) && !isset($flt['pilots_id']) && !isset($flt['idents']) && !isset($flt['registrations']))) {
				if (isset($flt['source'])) {
					$filter_query_join .= " INNER JOIN (SELECT flightaware_id FROM spotter_output WHERE spotter_output.format_source IN ('".implode("','",$flt['source'])."')) ssf ON ssf.flightaware_id = spotter_live.flightaware_id";
				}
			}
		}
		if (isset($filter['airlines']) && !empty($filter['airlines'])) {
			if ($filter['airlines'][0] != '' && $filter['airlines'][0] != 'all') {
				$filter_query_join .= " INNER JOIN (SELECT flightaware_id FROM spotter_output WHERE spotter_output.airline_icao IN ('".implode("','",$filter['airlines'])."')) sai ON sai.flightaware_id = spotter_live.flightaware_id";
			}
		}
		if (isset($filter['alliance']) && !empty($filter['alliance'])) {
			$filter_query_join .= " INNER JOIN (SELECT flightaware_id FROM spotter_output WHERE spotter_output.airline_icao IN (SELECT icao FROM airlines WHERE alliance = '".$filter['alliance']."')) sal ON sal.flightaware_id = spotter_live.flightaware_id ";
		}
		if (isset($filter['airlinestype']) && !empty($filter['airlinestype'])) {
			$filter_query_join .= " INNER JOIN (SELECT flightaware_id FROM spotter_output WHERE spotter_output.airline_type = '".$filter['airlinestype']."') sa ON sa.flightaware_id = spotter_live.flightaware_id ";
		}
		if (isset($filter['pilots_id']) && !empty($filter['pilots_id'])) {
			$filter_query_join .= " INNER JOIN (SELECT flightaware_id FROM spotter_output WHERE spotter_output.pilot_id IN ('".implode("','",$filter['pilots_id'])."')) sp ON sp.flightaware_id = spotter_live.flightaware_id";
		}
		if (isset($filter['blocked']) && $filter['blocked'] == true) {
			$filter_query_join .= " INNER JOIN (SELECT callsign FROM aircraft_block) cblk ON cblk.callsign = spotter_live.ident";
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
		if ((isset($filter['year']) && $filter['year'] != '') || (isset($filter['month']) && $filter['month'] != '') || (isset($filter['day']) && $filter['day'] != '')) {
			$filter_query_date = '';
			
			if (isset($filter['year']) && $filter['year'] != '') {
				if ($globalDBdriver == 'mysql') {
					$filter_query_date .= " AND YEAR(spotter_output.date) = '".$filter['year']."'";
				} else {
					$filter_query_date .= " AND EXTRACT(YEAR FROM spotter_output.date) = '".$filter['year']."'";
				}
			}
			if (isset($filter['month']) && $filter['month'] != '') {
				if ($globalDBdriver == 'mysql') {
					$filter_query_date .= " AND MONTH(spotter_output.date) = '".$filter['month']."'";
				} else {
					$filter_query_date .= " AND EXTRACT(MONTH FROM spotter_output.date) = '".$filter['month']."'";
				}
			}
			if (isset($filter['day']) && $filter['day'] != '') {
				if ($globalDBdriver == 'mysql') {
					$filter_query_date .= " AND DAY(spotter_output.date) = '".$filter['day']."'";
				} else {
					$filter_query_date .= " AND EXTRACT(DAY FROM spotter_output.date) = '".$filter['day']."'";
				}
			}
			$filter_query_join .= " INNER JOIN (SELECT flightaware_id FROM spotter_output".preg_replace('/^ AND/',' WHERE',$filter_query_date).") sd ON sd.flightaware_id = spotter_live.flightaware_id";
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
     * Gets all the spotter information based on the latest data entry
     *
     * @param string $limit
     * @param string $sort
     * @param array $filter
     * @return array the spotter information
     */
	public function getLiveSpotterData($limit = '', $sort = '', $filter = array())
	{
		global $globalDBdriver, $globalLiveInterval;
		$Spotter = new Spotter($this->db);
		date_default_timezone_set('UTC');

		$filter_query = $this->getFilter($filter);
		$limit_query = '';
		if ($limit != '')
		{
			$limit_array = explode(',', $limit);
			$limit_array[0] = filter_var($limit_array[0],FILTER_SANITIZE_NUMBER_INT);
			$limit_array[1] = filter_var($limit_array[1],FILTER_SANITIZE_NUMBER_INT);
			if ($limit_array[0] >= 0 && $limit_array[1] >= 0)
			{
				$limit_query = ' LIMIT '.$limit_array[1].' OFFSET '.$limit_array[0];
			}
		}
		$orderby_query = '';
		if ($sort != '')
		{
			$search_orderby_array = $this->getOrderBy();
			if (isset($search_orderby_array[$sort]['sql'])) 
			{
				$orderby_query = ' '.$search_orderby_array[$sort]['sql'];
			}
		}
		if ($orderby_query == '') $orderby_query = ' ORDER BY date DESC';

		if (!isset($globalLiveInterval)) $globalLiveInterval = '200';
		if ($globalDBdriver == 'mysql') {
			//$query  = "SELECT spotter_live.* FROM spotter_live INNER JOIN (SELECT l.flightaware_id, max(l.date) as maxdate FROM spotter_live l WHERE DATE_SUB(UTC_TIMESTAMP(),INTERVAL 30 SECOND) <= l.date GROUP BY l.flightaware_id) s on spotter_live.flightaware_id = s.flightaware_id AND spotter_live.date = s.maxdate";
			$query  = 'SELECT spotter_live.* FROM spotter_live INNER JOIN (SELECT l.flightaware_id, max(l.date) as maxdate FROM spotter_live l WHERE DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.$globalLiveInterval.' SECOND) <= l.date GROUP BY l.flightaware_id) s on spotter_live.flightaware_id = s.flightaware_id AND spotter_live.date = s.maxdate'.$filter_query.$orderby_query;
		} else {
			$query  = "SELECT spotter_live.* FROM spotter_live INNER JOIN (SELECT l.flightaware_id, max(l.date) as maxdate FROM spotter_live l WHERE CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$globalLiveInterval." SECONDS' <= l.date GROUP BY l.flightaware_id) s on spotter_live.flightaware_id = s.flightaware_id AND spotter_live.date = s.maxdate".$filter_query.$orderby_query;
		}
		$spotter_array = $Spotter->getDataFromDB($query.$limit_query,array(),'',true);

		return $spotter_array;
	}

    /**
     * Gets Minimal Live Spotter data
     *
     * @param int $limit
     * @param array $filter
     * @return array the spotter information
     */
	public function getMinLiveSpotterData($limit = 0,$filter = array())
	{
		global $globalDBdriver, $globalLiveInterval, $globalArchive, $globalMap2DAircraftsLimit;
		date_default_timezone_set('UTC');
		$filter_query = $this->getFilter($filter,true,true);
		if (!isset($globalLiveInterval)) $globalLiveInterval = '200';
		if ($globalDBdriver == 'mysql') {
			if (isset($globalArchive) && $globalArchive === TRUE) {
			//	$query  = 'SELECT spotter_live.ident, spotter_live.flightaware_id, spotter_live.aircraft_icao, spotter_live.departure_airport_icao as departure_airport, spotter_live.arrival_airport_icao as arrival_airport, spotter_live.latitude, spotter_live.longitude, spotter_live.altitude, spotter_live.real_altitude, spotter_live.heading, spotter_live.ground_speed, spotter_live.squawk, spotter_live.date, spotter_live.format_source, spotter_live.registration 
			//	FROM spotter_live'.$filter_query.' DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.$globalLiveInterval.' SECOND) <= spotter_live.date AND'." spotter_live.latitude <> 0 AND spotter_live.longitude <> 0 ORDER BY date DESC";
				$query  = 'SELECT spotter_live.ident, spotter_live.flightaware_id, spotter_live.aircraft_icao, spotter_live.departure_airport_icao as departure_airport, spotter_live.arrival_airport_icao as arrival_airport, spotter_live.latitude, spotter_live.longitude, spotter_live.altitude, spotter_live.real_altitude, spotter_live.heading, spotter_live.ground_speed, spotter_live.squawk, spotter_live.date, spotter_live.format_source, spotter_live.registration 
				FROM spotter_live'.$filter_query.' DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.$globalLiveInterval.' SECOND) <= spotter_live.date AND'." spotter_live.latitude <> 0 AND spotter_live.longitude <> 0";
			} else {
			//	$query  = 'SELECT spotter_live.ident, spotter_live.flightaware_id, spotter_live.aircraft_icao, spotter_live.departure_airport_icao as departure_airport, spotter_live.arrival_airport_icao as arrival_airport, spotter_live.latitude, spotter_live.longitude, spotter_live.altitude, spotter_live.real_altitude, spotter_live.heading, spotter_live.ground_speed, spotter_live.squawk, spotter_live.date, spotter_live.format_source, spotter_live.registration 
			//	FROM spotter_live INNER JOIN (SELECT l.flightaware_id, max(l.date) as maxdate FROM spotter_live l WHERE DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.$globalLiveInterval.' SECOND) <= l.date GROUP BY l.flightaware_id) s on spotter_live.flightaware_id = s.flightaware_id AND spotter_live.date = s.maxdate'.$filter_query." spotter_live.latitude <> 0 AND spotter_live.longitude <> 0 ORDER BY date DESC";
				$query  = 'SELECT spotter_live.ident, spotter_live.flightaware_id, spotter_live.aircraft_icao, spotter_live.departure_airport_icao as departure_airport, spotter_live.arrival_airport_icao as arrival_airport, spotter_live.latitude, spotter_live.longitude, spotter_live.altitude, spotter_live.real_altitude, spotter_live.heading, spotter_live.ground_speed, spotter_live.squawk, spotter_live.date, spotter_live.format_source, spotter_live.registration 
				FROM spotter_live INNER JOIN (SELECT l.flightaware_id, max(l.date) as maxdate FROM spotter_live l WHERE DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.$globalLiveInterval.' SECOND) <= l.date GROUP BY l.flightaware_id) s on spotter_live.flightaware_id = s.flightaware_id AND spotter_live.date = s.maxdate'.$filter_query." spotter_live.latitude <> 0 AND spotter_live.longitude <> 0";
			}
		} else {
			if (isset($globalArchive) && $globalArchive === TRUE) {
			//	$query  = "SELECT spotter_live.ident, spotter_live.flightaware_id, spotter_live.aircraft_icao, spotter_live.departure_airport_icao as departure_airport, spotter_live.arrival_airport_icao as arrival_airport, spotter_live.latitude, spotter_live.longitude, spotter_live.altitude, spotter_live.real_altitude, spotter_live.heading, spotter_live.ground_speed, spotter_live.squawk, spotter_live.date, spotter_live.format_source, spotter_live.registration 
			//	FROM spotter_live".$filter_query." CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$globalLiveInterval." SECONDS' <= spotter_live.date AND  spotter_live.latitude <> '0' AND spotter_live.longitude <> '0' ORDER BY date DESC";
				$query  = "SELECT spotter_live.ident, spotter_live.flightaware_id, spotter_live.aircraft_icao, spotter_live.departure_airport_icao as departure_airport, spotter_live.arrival_airport_icao as arrival_airport, spotter_live.latitude, spotter_live.longitude, spotter_live.altitude, spotter_live.real_altitude, spotter_live.heading, spotter_live.ground_speed, spotter_live.squawk, spotter_live.date, spotter_live.format_source, spotter_live.registration 
				FROM spotter_live".$filter_query." CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$globalLiveInterval." SECONDS' <= spotter_live.date AND  spotter_live.latitude <> '0' AND spotter_live.longitude <> '0'";
			} else {
			//	$query  = "SELECT spotter_live.ident, spotter_live.flightaware_id, spotter_live.aircraft_icao, spotter_live.departure_airport_icao as departure_airport, spotter_live.arrival_airport_icao as arrival_airport, spotter_live.latitude, spotter_live.longitude, spotter_live.altitude, spotter_live.real_altitude, spotter_live.heading, spotter_live.ground_speed, spotter_live.squawk, spotter_live.date, spotter_live.format_source, spotter_live.registration 
			//	FROM spotter_live INNER JOIN (SELECT l.flightaware_id, max(l.date) as maxdate FROM spotter_live l WHERE CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$globalLiveInterval." SECONDS' <= l.date GROUP BY l.flightaware_id) s on spotter_live.flightaware_id = s.flightaware_id AND spotter_live.date = s.maxdate".$filter_query." spotter_live.latitude <> '0' AND spotter_live.longitude <> '0' ORDER BY date DESC";
				$query  = "SELECT spotter_live.ident, spotter_live.flightaware_id, spotter_live.aircraft_icao, spotter_live.departure_airport_icao as departure_airport, spotter_live.arrival_airport_icao as arrival_airport, spotter_live.latitude, spotter_live.longitude, spotter_live.altitude, spotter_live.real_altitude, spotter_live.heading, spotter_live.ground_speed, spotter_live.squawk, spotter_live.date, spotter_live.format_source, spotter_live.registration 
				FROM spotter_live INNER JOIN (SELECT l.flightaware_id, max(l.date) as maxdate FROM spotter_live l WHERE CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$globalLiveInterval." SECONDS' <= l.date GROUP BY l.flightaware_id) s on spotter_live.flightaware_id = s.flightaware_id AND spotter_live.date = s.maxdate".$filter_query." spotter_live.latitude <> '0' AND spotter_live.longitude <> '0'";
			}
		}

		if ($limit == 0 && isset($globalMap2DAircraftsLimit) && $globalMap2DAircraftsLimit != '') {
			$limit = $globalMap2DAircraftsLimit;
		}
		if ($limit != 0 && filter_var($limit,FILTER_VALIDATE_INT)) {
			$query .= ' LIMIT '.$limit;
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
     * Gets Minimal Live Spotter data since xx seconds
     *
     * @param array $coord
     * @param array $filter
     * @param int $limit
     * @param string $id
     * @return array the spotter information
     */
	public function getMinLastLiveSpotterData($coord = array(),$filter = array(), $limit = 0, $id = '')
	{
		global $globalDBdriver, $globalLiveInterval, $globalArchive, $globalMap3DAircraftsLimit;
		date_default_timezone_set('UTC');
		$usecoord = false;
		if (is_array($coord) && !empty($coord)) {
			$minlong = filter_var($coord[0],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
			$minlat = filter_var($coord[1],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
			$maxlong = filter_var($coord[2],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
			$maxlat = filter_var($coord[3],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
			if ($minlong != '' && $minlat != '' && $maxlong != '' && $maxlat != '') $usecoord = true;
		}
		$id = filter_var($id,FILTER_SANITIZE_STRING);
		$filter_query = $this->getFilter($filter,true,true);

		if (!isset($globalLiveInterval) || $globalLiveInterval == '') $globalLiveInterval = '200';
		if (!isset($globalMap3DAircraftsLimit) || $globalMap3DAircraftsLimit == '') $globalMap3DAircraftsLimit = '300';
		if ($limit == 0 || $limit == '') $limit = $globalMap3DAircraftsLimit;
		if ($globalDBdriver == 'mysql') {
			if (isset($globalArchive) && $globalArchive === TRUE) {
				/*
				$query  = 'SELECT spotter_archive.ident, spotter_archive.flightaware_id, spotter_archive.aircraft_icao, spotter_archive.departure_airport_icao as departure_airport, spotter_archive.arrival_airport_icao as arrival_airport, spotter_archive.latitude, spotter_archive.longitude, spotter_archive.altitude, spotter_archive.heading, spotter_archive.ground_speed, spotter_archive.squawk, spotter_archive.date, spotter_archive.format_source 
				FROM spotter_archive INNER JOIN (SELECT flightaware_id FROM spotter_live'.$filter_query.' DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.$globalLiveInterval." SECOND) <= spotter_live.date) l ON l.flightaware_id = spotter_archive.flightaware_id 
				WHERE spotter_archive.latitude <> '0' AND spotter_archive.longitude <> '0' 
				ORDER BY spotter_archive.flightaware_id, spotter_archive.date";
				*/
				$query  = 'SELECT * FROM (SELECT spotter_archive.ident, spotter_archive.flightaware_id, spotter_archive.aircraft_icao, spotter_archive.departure_airport_icao as departure_airport, spotter_archive.arrival_airport_icao as arrival_airport, spotter_archive.latitude, spotter_archive.longitude, spotter_archive.altitude, spotter_archive.heading, spotter_archive.ground_speed, spotter_archive.squawk, spotter_archive.date, spotter_archive.format_source, spotter_archive.registration 
				FROM spotter_archive INNER JOIN (SELECT flightaware_id FROM spotter_live'.$filter_query.' DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.$globalLiveInterval." SECOND) <= spotter_live.date";
				//if ($limit > 0) $query .= " LIMIT ".$limit;
				$query .= ") l ON l.flightaware_id = spotter_archive.flightaware_id ";
				if ($usecoord) $query .= "AND (spotter_archive.latitude BETWEEN ".$minlat." AND ".$maxlat." AND spotter_archive.longitude BETWEEN ".$minlong." AND ".$maxlong.") ";
				if ($id != '') $query .= "OR spotter_archive.flightaware_id = :id ";
				$query .= "UNION
				SELECT spotter_live.ident, spotter_live.flightaware_id, spotter_live.aircraft_icao, spotter_live.departure_airport_icao as departure_airport, spotter_live.arrival_airport_icao as arrival_airport, spotter_live.latitude, spotter_live.longitude, spotter_live.altitude, spotter_live.heading, spotter_live.ground_speed, spotter_live.squawk, spotter_live.date, spotter_live.format_source, spotter_live.registration 
				FROM spotter_live".$filter_query.' DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.$globalLiveInterval." SECOND) <= spotter_live.date";
				if ($usecoord) $query .= " AND (spotter_live.latitude BETWEEN ".$minlat." AND ".$maxlat." AND spotter_live.longitude BETWEEN ".$minlong." AND ".$maxlong.")";
				if ($id != '') $query .= " OR spotter_live.flightaware_id = :id";
				//if ($limit > 0) $query .= " LIMIT ".$limit;
				$query .= ") AS spotter 
				WHERE latitude <> '0' AND longitude <> '0' 
				ORDER BY flightaware_id, date";
				if ($limit > 0) $query .= " LIMIT ".$limit;
			} else {
				$query  = 'SELECT spotter_live.ident, spotter_live.flightaware_id, spotter_live.aircraft_icao, spotter_live.departure_airport_icao as departure_airport, spotter_live.arrival_airport_icao as arrival_airport, spotter_live.latitude, spotter_live.longitude, spotter_live.altitude, spotter_live.heading, spotter_live.ground_speed, spotter_live.squawk, spotter_live.date, spotter_live.format_source, spotter_live.registration 
				FROM spotter_live'.$filter_query.' DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.$globalLiveInterval." SECOND) <= spotter_live.date ";
				if ($usecoord) $query .= "AND (spotter_live.latitude BETWEEN ".$minlat." AND ".$maxlat." AND spotter_live.longitude BETWEEN ".$minlong." AND ".$maxlong.") ";
				if ($id != '') $query .= "OR spotter_live.flightaware_id = :id ";
				$query .= "AND spotter_live.latitude <> '0' AND spotter_live.longitude <> '0' 
				ORDER BY spotter_live.flightaware_id, spotter_live.date";
				if ($limit > 0) $query .= " LIMIT ".$limit;
			}
		} else {
			if (isset($globalArchive) && $globalArchive === TRUE) {
				/*
				$query  = "SELECT spotter_archive.ident, spotter_archive.flightaware_id, spotter_archive.aircraft_icao, spotter_archive.departure_airport_icao as departure_airport, spotter_archive.arrival_airport_icao as arrival_airport, spotter_archive.latitude, spotter_archive.longitude, spotter_archive.altitude, spotter_archive.heading, spotter_archive.ground_speed, spotter_archive.squawk, spotter_archive.date, spotter_archive.format_source 
				FROM spotter_archive INNER JOIN (SELECT flightaware_id FROM spotter_live".$filter_query." CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$globalLiveInterval." SECONDS' <= spotter_live.date) l ON l.flightaware_id = spotter_archive.flightaware_id 
				WHERE spotter_archive.latitude <> '0' AND spotter_archive.longitude <> '0' 
				ORDER BY spotter_archive.flightaware_id, spotter_archive.date";
                               */
				$query = "SELECT * FROM (
				SELECT spotter_archive.ident, spotter_archive.flightaware_id, spotter_archive.aircraft_icao, spotter_archive.departure_airport_icao as departure_airport, spotter_archive.arrival_airport_icao as arrival_airport, spotter_archive.latitude, spotter_archive.longitude, spotter_archive.altitude, spotter_archive.heading, spotter_archive.ground_speed, spotter_archive.squawk, spotter_archive.date, spotter_archive.format_source, spotter_archive.registration 
				FROM spotter_archive 
				INNER JOIN (
				    SELECT flightaware_id 
				    FROM spotter_live".$filter_query." CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$globalLiveInterval." SECONDS' <= spotter_live.date";
				//if ($limit > 0) $query .= " ORDER BY spotter_live.date ASC LIMIT ".$limit;
				$query .= ") l ON l.flightaware_id = spotter_archive.flightaware_id ";
				if ($usecoord) $query .= "AND (spotter_archive.latitude BETWEEN ".$minlat." AND ".$maxlat." AND spotter_archive.longitude BETWEEN ".$minlong." AND ".$maxlong.") ";
				if ($id != '') $query .= "OR spotter_archive.flightaware_id = :id ";
				$query .= "UNION
				    SELECT spotter_live.ident, spotter_live.flightaware_id, spotter_live.aircraft_icao, spotter_live.departure_airport_icao as departure_airport, spotter_live.arrival_airport_icao as arrival_airport, spotter_live.latitude, spotter_live.longitude, spotter_live.altitude, spotter_live.heading, spotter_live.ground_speed, spotter_live.squawk, spotter_live.date, spotter_live.format_source, spotter_live.registration 
				    FROM spotter_live".$filter_query." CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$globalLiveInterval." SECONDS' <= spotter_live.date";
				if ($usecoord) $query .= " AND (spotter_live.latitude BETWEEN ".$minlat." AND ".$maxlat." AND spotter_live.longitude BETWEEN ".$minlong." AND ".$maxlong.")";
				if ($id != '') $query .= " OR spotter_live.flightaware_id = :id";
				//if ($limit > 0) $query .= " ORDER BY date ASC LIMIT ".$limit;
				$query .= ") AS spotter WHERE latitude <> '0' AND longitude <> '0' ";
				$query .= "ORDER BY flightaware_id, date";
				if ($limit > 0) $query .= " LIMIT ".$limit;
			} else {
				$query  = "SELECT spotter_live.ident, spotter_live.flightaware_id, spotter_live.aircraft_icao, spotter_live.departure_airport_icao as departure_airport, spotter_live.arrival_airport_icao as arrival_airport, spotter_live.latitude, spotter_live.longitude, spotter_live.altitude, spotter_live.heading, spotter_live.ground_speed, spotter_live.squawk, spotter_live.date, spotter_live.format_source, spotter_live.registration 
				FROM spotter_live".$filter_query." CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$globalLiveInterval." SECONDS' <= spotter_live.date ";
				if ($usecoord) $query .= "AND (spotter_live.latitude BETWEEN ".$minlat." AND ".$maxlat." AND spotter_live.longitude BETWEEN ".$minlong." AND ".$maxlong.") ";
				if ($id != '') $query .= "OR spotter_live.flightaware_id = :id ";
				$query .= "AND spotter_live.latitude <> '0' AND spotter_live.longitude <> '0' 
				ORDER BY spotter_live.flightaware_id, spotter_live.date";
				if ($limit > 0) $query .= " LIMIT ".$limit;
			}
		}
		$query_values = array();
		if ($id != '') $query_values = array(':id' => $id);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			echo $e->getMessage();
			die;
		}
		$spotter_array = $sth->fetchAll(PDO::FETCH_ASSOC);
		return $spotter_array;
	}

    /**
     * Gets Minimal Live Spotter data since xx seconds
     *
     * @param string $id
     * @param array $filter
     * @param int $limit
     * @return array the spotter information
     */
	public function getMinLastLiveSpotterDataByID($id = '',$filter = array(), $limit = 0)
	{
		global $globalDBdriver, $globalLiveInterval, $globalArchive, $globalMap3DAircraftsLimit;
		date_default_timezone_set('UTC');
		$id = filter_var($id,FILTER_SANITIZE_STRING);
		$filter_query = $this->getFilter($filter,true,true);

		if (!isset($globalLiveInterval) || $globalLiveInterval == '') $globalLiveInterval = '200';
		if (!isset($globalMap3DAircraftsLimit) || $globalMap3DAircraftsLimit == '') $globalMap3DAircraftsLimit = '300';
		if ($limit == 0 || $limit == '') $limit = $globalMap3DAircraftsLimit;
		if ($globalDBdriver == 'mysql') {
			if (isset($globalArchive) && $globalArchive === TRUE) {
				$query  = 'SELECT * FROM (SELECT spotter_archive.ident, spotter_archive.flightaware_id, spotter_archive.aircraft_icao, spotter_archive.departure_airport_icao as departure_airport, spotter_archive.arrival_airport_icao as arrival_airport, spotter_archive.latitude, spotter_archive.longitude, spotter_archive.altitude, spotter_archive.heading, spotter_archive.ground_speed, spotter_archive.squawk, spotter_archive.date, spotter_archive.format_source, spotter_archive.registration 
				FROM spotter_archive INNER JOIN (SELECT flightaware_id FROM spotter_live'.$filter_query.' spotter_live.flightaware_id = :id) l ON l.flightaware_id = spotter_archive.flightaware_id ';
				$query .= "UNION
				SELECT spotter_live.ident, spotter_live.flightaware_id, spotter_live.aircraft_icao, spotter_live.departure_airport_icao as departure_airport, spotter_live.arrival_airport_icao as arrival_airport, spotter_live.latitude, spotter_live.longitude, spotter_live.altitude, spotter_live.heading, spotter_live.ground_speed, spotter_live.squawk, spotter_live.date, spotter_live.format_source, spotter_live.registration 
				FROM spotter_live".$filter_query.' spotter_live.flightaware_id = :id';
				$query .= ") AS spotter 
				WHERE latitude <> '0' AND longitude <> '0' 
				ORDER BY flightaware_id, date";
				if ($limit > 0) $query .= " LIMIT ".$limit;
			} else {
				$query  = 'SELECT spotter_live.ident, spotter_live.flightaware_id, spotter_live.aircraft_icao, spotter_live.departure_airport_icao as departure_airport, spotter_live.arrival_airport_icao as arrival_airport, spotter_live.latitude, spotter_live.longitude, spotter_live.altitude, spotter_live.heading, spotter_live.ground_speed, spotter_live.squawk, spotter_live.date, spotter_live.format_source, spotter_live.registration 
				FROM spotter_live'.$filter_query.' spotter_live.flightaware_id = :id ';
				$query .= "AND spotter_live.latitude <> '0' AND spotter_live.longitude <> '0' 
				ORDER BY spotter_live.flightaware_id, spotter_live.date";
				if ($limit > 0) $query .= " LIMIT ".$limit;
			}
		} else {
			if (isset($globalArchive) && $globalArchive === TRUE) {
				$query  = "SELECT * FROM (
				SELECT spotter_archive.ident, spotter_archive.flightaware_id, spotter_archive.aircraft_icao, spotter_archive.departure_airport_icao as departure_airport, spotter_archive.arrival_airport_icao as arrival_airport, spotter_archive.latitude, spotter_archive.longitude, spotter_archive.altitude, spotter_archive.heading, spotter_archive.ground_speed, spotter_archive.squawk, spotter_archive.date, spotter_archive.format_source, spotter_archive.registration 
				FROM spotter_archive 
				INNER JOIN (
				    SELECT flightaware_id 
				    FROM spotter_live".$filter_query." spotter_live.flightaware_id = :id";
				$query.= ") l ON l.flightaware_id = spotter_archive.flightaware_id ";
				$query .= "UNION
				    SELECT spotter_live.ident, spotter_live.flightaware_id, spotter_live.aircraft_icao, spotter_live.departure_airport_icao as departure_airport, spotter_live.arrival_airport_icao as arrival_airport, spotter_live.latitude, spotter_live.longitude, spotter_live.altitude, spotter_live.heading, spotter_live.ground_speed, spotter_live.squawk, spotter_live.date, spotter_live.format_source, spotter_live.registration 
				    FROM spotter_live".$filter_query." spotter_live.flightaware_id = :id";
				$query .= ") AS spotter WHERE latitude <> '0' AND longitude <> '0' ";
				$query .= "ORDER BY flightaware_id, date";
				if ($limit > 0) $query .= " LIMIT ".$limit;
			} else {
				$query  = "SELECT spotter_live.ident, spotter_live.flightaware_id, spotter_live.aircraft_icao, spotter_live.departure_airport_icao as departure_airport, spotter_live.arrival_airport_icao as arrival_airport, spotter_live.latitude, spotter_live.longitude, spotter_live.altitude, spotter_live.heading, spotter_live.ground_speed, spotter_live.squawk, spotter_live.date, spotter_live.format_source, spotter_live.registration 
				FROM spotter_live".$filter_query." spotter_live.flightaware_id = :id ";
				$query .= "AND spotter_live.latitude <> '0' AND spotter_live.longitude <> '0' 
				ORDER BY spotter_live.flightaware_id, spotter_live.date";
				if ($limit > 0) $query .= " LIMIT ".$limit;
			}
		}
		$query_values = array(':id' => $id);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			echo $e->getMessage();
			die;
		}
		$spotter_array = $sth->fetchAll(PDO::FETCH_ASSOC);
		return $spotter_array;
	}

    /**
     * Gets number of latest data entry
     *
     * @param array $filter
     * @return String number of entry
     */
	public function getLiveSpotterCount($filter = array())
	{
		global $globalDBdriver, $globalLiveInterval;
		$filter_query = $this->getFilter($filter,true,true);

		if (!isset($globalLiveInterval)) $globalLiveInterval = '200';
		if ($globalDBdriver == 'mysql') {
			//$query  = 'SELECT COUNT(*) as nb FROM spotter_live INNER JOIN (SELECT l.flightaware_id, max(l.date) as maxdate FROM spotter_live l WHERE DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.$globalLiveInterval.' SECOND) <= l.date GROUP BY l.flightaware_id) s on spotter_live.flightaware_id = s.flightaware_id AND spotter_live.date = s.maxdate'.$filter_query;
			$query = 'SELECT COUNT(DISTINCT spotter_live.flightaware_id) as nb FROM spotter_live'.$filter_query.' DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.$globalLiveInterval.' SECOND) <= date';
		} else {
			//$query  = "SELECT COUNT(*) as nb FROM spotter_live INNER JOIN (SELECT l.flightaware_id, max(l.date) as maxdate FROM spotter_live l WHERE NOW() AT TIME ZONE 'UTC' - '".$globalLiveInterval." SECONDS'->INTERVAL <= l.date GROUP BY l.flightaware_id) s on spotter_live.flightaware_id = s.flightaware_id AND spotter_live.date = s.maxdate".$filter_query;
			$query = "SELECT COUNT(DISTINCT spotter_live.flightaware_id) as nb FROM spotter_live".$filter_query." CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$globalLiveInterval." SECONDS' <= date";
		}
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

    /**
     * Gets all the spotter information based on the latest data entry and coord
     *
     * @param $coord
     * @param array $filter
     * @return array the spotter information
     */
	public function getLiveSpotterDatabyCoord($coord, $filter = array())
	{
		global $globalDBdriver, $globalLiveInterval,$globalMap2DAircraftsLimit;
		$Spotter = new Spotter($this->db);
		if (!isset($globalLiveInterval)) $globalLiveInterval = '200';
		$filter_query = $this->getFilter($filter);

		if (is_array($coord)) {
			$minlong = filter_var($coord[0],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
			$minlat = filter_var($coord[1],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
			$maxlong = filter_var($coord[2],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
			$maxlat = filter_var($coord[3],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
		} else return array();
		if ($globalDBdriver == 'mysql') {
			$query  = 'SELECT spotter_live.* FROM spotter_live INNER JOIN (SELECT l.flightaware_id, max(l.date) as maxdate FROM spotter_live l WHERE DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.$globalLiveInterval.' SECOND) <= l.date GROUP BY l.flightaware_id) s on spotter_live.flightaware_id = s.flightaware_id AND spotter_live.date = s.maxdate AND spotter_live.latitude BETWEEN '.$minlat.' AND '.$maxlat.' AND spotter_live.longitude BETWEEN '.$minlong.' AND '.$maxlong.' GROUP BY spotter_live.flightaware_id'.$filter_query;
		} else {
			$query  = "SELECT spotter_live.* FROM spotter_live INNER JOIN (SELECT l.flightaware_id, max(l.date) as maxdate FROM spotter_live l WHERE NOW() at time zone 'UTC'  - INTERVAL '".$globalLiveInterval." SECONDS' <= l.date GROUP BY l.flightaware_id) s on spotter_live.flightaware_id = s.flightaware_id AND spotter_live.date = s.maxdate AND spotter_live.latitude BETWEEN ".$minlat." AND ".$maxlat." AND spotter_live.longitude BETWEEN ".$minlong." AND ".$maxlong." GROUP BY spotter_live.flightaware_id".$filter_query;
		}
		if (isset($globalMap2DAircraftsLimit) && $globalMap2DAircraftsLimit != '') {
			$query .= ' LIMIT '.$globalMap2DAircraftsLimit;
		}

		$spotter_array = $Spotter->getDataFromDB($query);
		return $spotter_array;
	}

    /**
     * Gets all the spotter information based on the latest data entry and coord
     *
     * @param $coord
     * @param int $limit
     * @param array $filter
     * @return array the spotter information
     */
	public function getMinLiveSpotterDatabyCoord($coord,$limit = 0, $filter = array())
	{
		global $globalDBdriver, $globalLiveInterval, $globalArchive,$globalMap2DAircraftsLimit;
		if (!isset($globalLiveInterval)) $globalLiveInterval = '200';
		$filter_query = $this->getFilter($filter,true,true);

		if (is_array($coord)) {
			$minlong = filter_var($coord[0],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
			$minlat = filter_var($coord[1],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
			$maxlong = filter_var($coord[2],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
			$maxlat = filter_var($coord[3],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
		} else return array();
		if ($globalDBdriver == 'mysql') {
			if (isset($globalArchive) && $globalArchive === TRUE) {
				$query  = 'SELECT spotter_live.ident, spotter_live.flightaware_id, spotter_live.aircraft_icao, spotter_live.departure_airport_icao as departure_airport, spotter_live.arrival_airport_icao as arrival_airport, spotter_live.latitude, spotter_live.longitude, spotter_live.altitude, spotter_live.real_altitude, spotter_live.heading, spotter_live.ground_speed, spotter_live.squawk, spotter_live.date, spotter_live.format_source, spotter_live.registration 
				FROM spotter_live 
				'.$filter_query.' DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.$globalLiveInterval.' SECOND) <= spotter_live.date 
				AND spotter_live.latitude BETWEEN '.$minlat.' AND '.$maxlat.' AND spotter_live.longitude BETWEEN '.$minlong.' AND '.$maxlong.'
				AND spotter_live.latitude <> 0 AND spotter_live.longitude <> 0 ORDER BY date DESC';
			} else {
				$query  = 'SELECT spotter_live.ident, spotter_live.flightaware_id, spotter_live.aircraft_icao, spotter_live.departure_airport_icao as departure_airport, spotter_live.arrival_airport_icao as arrival_airport, spotter_live.latitude, spotter_live.longitude, spotter_live.altitude, spotter_live.real_altitude, spotter_live.heading, spotter_live.ground_speed, spotter_live.squawk, spotter_live.date, spotter_live.format_source, spotter_live.registration 
				FROM spotter_live 
				INNER JOIN (SELECT l.flightaware_id, max(l.date) as maxdate 
				    FROM spotter_live l 
				    WHERE DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.$globalLiveInterval.' SECOND) <= l.date 
				    AND l.latitude BETWEEN '.$minlat.' AND '.$maxlat.' AND l.longitude BETWEEN '.$minlong.' AND '.$maxlong.'
				    GROUP BY l.flightaware_id
				) s on spotter_live.flightaware_id = s.flightaware_id 
				AND spotter_live.date = s.maxdate'.$filter_query.' spotter_live.latitude <> 0 AND spotter_live.longitude <> 0 ORDER BY date DESC';
			}
		} else {
			if (isset($globalArchive) && $globalArchive === TRUE) {
				/*
				$query  = "SELECT spotter_live.ident, spotter_live.flightaware_id, spotter_live.aircraft_icao, spotter_live.departure_airport_icao as departure_airport, spotter_live.arrival_airport_icao as arrival_airport, spotter_live.latitude, spotter_live.longitude, spotter_live.altitude, spotter_live.real_altitude, spotter_live.heading, spotter_live.ground_speed, spotter_live.squawk, spotter_live.date, spotter_live.format_source, spotter_live.registration 
				FROM spotter_live 
				".$filter_query." CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$globalLiveInterval." SECONDS' <= spotter_live.date 
				AND spotter_live.latitude BETWEEN ".$minlat." AND ".$maxlat." 
				AND spotter_live.longitude BETWEEN ".$minlong." AND ".$maxlong." 
				AND spotter_live.latitude <> '0' AND spotter_live.longitude <> '0' ORDER BY date DESC";
				*/
				$query  = "SELECT spotter_live.ident, spotter_live.flightaware_id, spotter_live.aircraft_icao, spotter_live.departure_airport_icao as departure_airport, spotter_live.arrival_airport_icao as arrival_airport, spotter_live.latitude, spotter_live.longitude, spotter_live.altitude, spotter_live.real_altitude, spotter_live.heading, spotter_live.ground_speed, spotter_live.squawk, spotter_live.date, spotter_live.format_source, spotter_live.registration 
				FROM spotter_live 
				".$filter_query." CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$globalLiveInterval." SECONDS' <= spotter_live.date 
				AND spotter_live.latitude BETWEEN ".$minlat." AND ".$maxlat." 
				AND spotter_live.longitude BETWEEN ".$minlong." AND ".$maxlong." 
				AND spotter_live.latitude <> '0' AND spotter_live.longitude <> '0'";
			} else {
				/*
				$query  = "SELECT spotter_live.ident, spotter_live.flightaware_id, spotter_live.aircraft_icao, spotter_live.departure_airport_icao as departure_airport, spotter_live.arrival_airport_icao as arrival_airport, spotter_live.latitude, spotter_live.longitude, spotter_live.altitude, spotter_live.real_altitude, spotter_live.heading, spotter_live.ground_speed, spotter_live.squawk, spotter_live.date, spotter_live.format_source, spotter_live.registration 
				FROM spotter_live 
				INNER JOIN (SELECT l.flightaware_id, max(l.date) as maxdate 
				    FROM spotter_live l 
				    WHERE CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$globalLiveInterval." SECONDS' <= l.date 
				    AND l.latitude BETWEEN ".$minlat." AND ".$maxlat." 
				    AND l.longitude BETWEEN ".$minlong." AND ".$maxlong." 
				    GROUP BY l.flightaware_id
				) s on spotter_live.flightaware_id = s.flightaware_id 
				AND spotter_live.date = s.maxdate".$filter_query." spotter_live.latitude <> '0' AND spotter_live.longitude <> '0' ORDER BY date DESC";
				*/
				$query  = "SELECT spotter_live.ident, spotter_live.flightaware_id, spotter_live.aircraft_icao, spotter_live.departure_airport_icao as departure_airport, spotter_live.arrival_airport_icao as arrival_airport, spotter_live.latitude, spotter_live.longitude, spotter_live.altitude, spotter_live.real_altitude, spotter_live.heading, spotter_live.ground_speed, spotter_live.squawk, spotter_live.date, spotter_live.format_source, spotter_live.registration 
				FROM spotter_live 
				INNER JOIN (SELECT l.flightaware_id, max(l.date) as maxdate 
				    FROM spotter_live l 
				    WHERE CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$globalLiveInterval." SECONDS' <= l.date 
				    AND l.latitude BETWEEN ".$minlat." AND ".$maxlat." 
				    AND l.longitude BETWEEN ".$minlong." AND ".$maxlong." 
				    GROUP BY l.flightaware_id
				) s on spotter_live.flightaware_id = s.flightaware_id 
				AND spotter_live.date = s.maxdate".$filter_query." spotter_live.latitude <> '0' AND spotter_live.longitude <> '0'";
			}
		}
		if ($limit == 0 && isset($globalMap2DAircraftsLimit) && $globalMap2DAircraftsLimit != '') {
			$limit = $globalMap2DAircraftsLimit;
		}
		if ($limit != 0 && filter_var($limit,FILTER_VALIDATE_INT)) {
			$query .= ' LIMIT '.$limit;
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
     * Gets all the spotter information based on a user's latitude and longitude
     *
     * @param $lat
     * @param $lng
     * @param $radius
     * @param $interval
     * @return array the spotter information
     */
	public function getLatestSpotterForLayar($lat, $lng, $radius, $interval)
	{
		$Spotter = new Spotter($this->db);
		date_default_timezone_set('UTC');
		if ($lat != '') {
			if (!is_numeric($lat)) {
				return array();
			}
		}
        if ($lng != '')
        {
        	if (!is_numeric($lng))
            {
            	return array();
            }
        }
        if ($radius != '')
        {
        	if (!is_numeric($radius))
        	{
        		return array();
        	}
        }
		$additional_query = '';
        if ($interval != '')
        {
        	if (!is_string($interval))
        	{
        		return array();
        	} else {
                if ($interval == '1m')
                {
                    $additional_query = ' AND DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 MINUTE) <= spotter_live.date ';
                } else if ($interval == '15m'){
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

            $spotter_array = $Spotter->getDataFromDB($query, array(':lat' => $lat, ':lng' => $lng,':radius' => $radius));
            return $spotter_array;
        }


    /**
     * Gets all the spotter information based on a particular callsign
     *
     * @param $ident
     * @return array the spotter information
     */
	public function getLastLiveSpotterDataByIdent($ident)
	{
		$Spotter = new Spotter($this->db);
		date_default_timezone_set('UTC');

		$ident = filter_var($ident, FILTER_SANITIZE_STRING);
                $query  = 'SELECT spotter_live.* FROM spotter_live INNER JOIN (SELECT l.flightaware_id, max(l.date) as maxdate FROM spotter_live l WHERE l.ident = :ident GROUP BY l.flightaware_id) s on spotter_live.flightaware_id = s.flightaware_id AND spotter_live.date = s.maxdate ORDER BY spotter_live.date DESC';

		$spotter_array = $Spotter->getDataFromDB($query,array(':ident' => $ident),'',true);

		return $spotter_array;
	}

    /**
     * Gets all the spotter information based on a particular callsign
     *
     * @param $ident
     * @param $date
     * @return array the spotter information
     */
	public function getDateLiveSpotterDataByIdent($ident,$date)
	{
		$Spotter = new Spotter($this->db);
		date_default_timezone_set('UTC');

		$ident = filter_var($ident, FILTER_SANITIZE_STRING);
                $query  = 'SELECT spotter_live.* FROM spotter_live INNER JOIN (SELECT l.flightaware_id, max(l.date) as maxdate FROM spotter_live l WHERE l.ident = :ident AND l.date <= :date GROUP BY l.flightaware_id) s on spotter_live.flightaware_id = s.flightaware_id AND spotter_live.date = s.maxdate ORDER BY spotter_live.date DESC';

                $date = date('c',$date);
		$spotter_array = $Spotter->getDataFromDB($query,array(':ident' => $ident,':date' => $date));

		return $spotter_array;
	}

    /**
     * Gets last spotter information based on a particular callsign
     *
     * @param $id
     * @return array the spotter information
     */
	public function getLastLiveSpotterDataById($id)
	{
		$Spotter = new Spotter($this->db);
		date_default_timezone_set('UTC');
		$id = filter_var($id, FILTER_SANITIZE_STRING);
		$query  = 'SELECT spotter_live.* FROM spotter_live INNER JOIN (SELECT l.flightaware_id, max(l.date) as maxdate FROM spotter_live l WHERE l.flightaware_id = :id GROUP BY l.flightaware_id) s on spotter_live.flightaware_id = s.flightaware_id AND spotter_live.date = s.maxdate ORDER BY spotter_live.date DESC';
		$spotter_array = $Spotter->getDataFromDB($query,array(':id' => $id),'',true);
		return $spotter_array;
	}

    /**
     * Gets last spotter information based on a particular callsign
     *
     * @param $id
     * @param $date
     * @return array the spotter information
     */
	public function getDateLiveSpotterDataById($id,$date)
	{
		$Spotter = new Spotter($this->db);
		date_default_timezone_set('UTC');

		$id = filter_var($id, FILTER_SANITIZE_STRING);
		$query  = 'SELECT spotter_live.* FROM spotter_live INNER JOIN (SELECT l.flightaware_id, max(l.date) as maxdate FROM spotter_live l WHERE l.flightaware_id = :id AND l.date <= :date GROUP BY l.flightaware_id) s on spotter_live.flightaware_id = s.flightaware_id AND spotter_live.date = s.maxdate ORDER BY spotter_live.date DESC';
		$date = date('c',$date);
		$spotter_array = $Spotter->getDataFromDB($query,array(':id' => $id,':date' => $date),'',true);
		return $spotter_array;
	}

    /**
     * Gets altitude information based on a particular callsign
     *
     * @param $ident
     * @return array the spotter information
     */
	public function getAltitudeLiveSpotterDataByIdent($ident)
	{

		date_default_timezone_set('UTC');

		$ident = filter_var($ident, FILTER_SANITIZE_STRING);
                $query  = 'SELECT spotter_live.altitude, spotter_live.date FROM spotter_live WHERE spotter_live.ident = :ident';

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
     * Gets all the spotter information based on a particular id
     *
     * @param $id
     * @param bool $liveinterval
     * @return array the spotter information
     */
	public function getAllLiveSpotterDataById($id,$liveinterval = false)
	{
		global $globalDBdriver, $globalLiveInterval;
		date_default_timezone_set('UTC');
		$id = filter_var($id, FILTER_SANITIZE_STRING);
		//$query  = self::$global_query.' WHERE spotter_live.flightaware_id = :id ORDER BY date';
		if ($globalDBdriver == 'mysql') {
			$query = 'SELECT spotter_live.* FROM spotter_live WHERE spotter_live.flightaware_id = :id';
			if ($liveinterval) $query .= ' AND DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.$globalLiveInterval.' SECOND) <= date';
			$query .= ' ORDER BY date';
		} else {
			$query = 'SELECT spotter_live.* FROM spotter_live WHERE spotter_live.flightaware_id = :id';
			if ($liveinterval) $query .= " AND CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$globalLiveInterval." SECONDS' <= date";
			$query .= ' ORDER BY date';
		}

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
     * Gets all the spotter information based on a particular ident
     *
     * @param $ident
     * @return array the spotter information
     */
	public function getAllLiveSpotterDataByIdent($ident)
	{
		date_default_timezone_set('UTC');
		$ident = filter_var($ident, FILTER_SANITIZE_STRING);
		$query  = self::$global_query.' WHERE spotter_live.ident = :ident';
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
	* Deletes all info in the table
	*
	* @return String success or false
	*
	*/
	public function deleteLiveSpotterData()
	{
		global $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			//$query  = "DELETE FROM spotter_live WHERE DATE_SUB(UTC_TIMESTAMP(),INTERVAL 30 MINUTE) >= spotter_live.date";
			$query  = 'DELETE FROM spotter_live WHERE DATE_SUB(UTC_TIMESTAMP(),INTERVAL 9 HOUR) >= spotter_live.date';
            		//$query  = "DELETE FROM spotter_live WHERE spotter_live.id IN (SELECT spotter_live.id FROM spotter_live INNER JOIN (SELECT l.flightaware_id, max(l.date) as maxdate FROM spotter_live l GROUP BY l.flightaware_id) s on spotter_live.flightaware_id = s.flightaware_id AND spotter_live.date = s.maxdate AND DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 HOUR) >= spotter_live.date)";
		} else {
			$query  = "DELETE FROM spotter_live WHERE NOW() AT TIME ZONE 'UTC' - INTERVAL '9 HOURS' >= spotter_live.date";
		}
        
    		try {
			
			$sth = $this->db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			return "error";
		}

		return "success";
	}

	/**
	* Deletes all info in the table for aircraft not seen since 2 HOUR
	*
	* @return String success or false
	*
	*/
	public function deleteLiveSpotterDataNotUpdated()
	{
		global $globalDBdriver, $globalDebug;
		if ($globalDBdriver == 'mysql') {
			//$query = 'SELECT flightaware_id FROM spotter_live WHERE DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 HOUR) >= spotter_live.date AND spotter_live.flightaware_id NOT IN (SELECT flightaware_id FROM spotter_live WHERE DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 HOUR) < spotter_live.date) LIMIT 800 OFFSET 0';
    			$query = "SELECT spotter_live.flightaware_id FROM spotter_live INNER JOIN (SELECT flightaware_id,MAX(date) as max_date FROM spotter_live GROUP BY flightaware_id) s ON s.flightaware_id = spotter_live.flightaware_id AND DATE_SUB(UTC_TIMESTAMP(), INTERVAL 2 HOUR) >= s.max_date LIMIT 2000 OFFSET 0";
    			try {
				
				$sth = $this->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error";
			}
			$query_delete = 'DELETE FROM spotter_live WHERE flightaware_id IN (';
                        $i = 0;
                        $j =0;
			$all = $sth->fetchAll(PDO::FETCH_ASSOC);
			foreach($all as $row)
			{
				$i++;
				$j++;
				if ($j == 30) {
					if ($globalDebug) echo ".";
				    	try {
						
						$sth = $this->db->prepare(substr($query_delete,0,-1).")");
						$sth->execute();
					} catch(PDOException $e) {
						return "error";
					}
                                	$query_delete = 'DELETE FROM spotter_live WHERE flightaware_id IN (';
                                	$j = 0;
				}
				$query_delete .= "'".$row['flightaware_id']."',";
			}
			if ($i > 0) {
    				try {
					
					$sth = $this->db->prepare(substr($query_delete,0,-1).")");
					$sth->execute();
				} catch(PDOException $e) {
					return "error";
				}
			}
			return "success";
		} elseif ($globalDBdriver == 'pgsql') {
			//$query = "SELECT flightaware_id FROM spotter_live WHERE NOW() AT TIME ZONE 'UTC' - INTERVAL '9 HOURS' >= spotter_live.date AND spotter_live.flightaware_id NOT IN (SELECT flightaware_id FROM spotter_live WHERE NOW() AT TIME ZONE 'UTC' - INTERVAL '9 HOURS' < spotter_live.date) LIMIT 800 OFFSET 0";
    			//$query = "SELECT spotter_live.flightaware_id FROM spotter_live INNER JOIN (SELECT flightaware_id,MAX(date) as max_date FROM spotter_live GROUP BY flightaware_id) s ON s.flightaware_id = spotter_live.flightaware_id AND NOW() AT TIME ZONE 'UTC' - INTERVAL '2 HOURS' >= s.max_date LIMIT 800 OFFSET 0";
    			$query = "DELETE FROM spotter_live WHERE flightaware_id IN (SELECT spotter_live.flightaware_id FROM spotter_live INNER JOIN (SELECT flightaware_id,MAX(date) as max_date FROM spotter_live GROUP BY flightaware_id) s ON s.flightaware_id = spotter_live.flightaware_id AND NOW() AT TIME ZONE 'UTC' - INTERVAL '2 HOURS' >= s.max_date LIMIT 2000 OFFSET 0)";
    			try {
				
				$sth = $this->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error";
			}
/*			$query_delete = "DELETE FROM spotter_live WHERE flightaware_id IN (";
                        $i = 0;
                        $j =0;
			$all = $sth->fetchAll(PDO::FETCH_ASSOC);
			foreach($all as $row)
			{
				$i++;
				$j++;
				if ($j == 100) {
					if ($globalDebug) echo ".";
				    	try {
						
						$sth = $this->db->query(substr($query_delete,0,-1).")");
						//$sth->execute();
					} catch(PDOException $e) {
						return "error";
					}
                                	$query_delete = "DELETE FROM spotter_live WHERE flightaware_id IN (";
                                	$j = 0;
				}
				$query_delete .= "'".$row['flightaware_id']."',";
			}
			if ($i > 0) {
    				try {
					
					$sth = $this->db->query(substr($query_delete,0,-1).")");
					//$sth->execute();
				} catch(PDOException $e) {
					return "error";
				}
			}
*/
			return "success";
		}
	}

    /**
     * Deletes all info in the table for an ident
     *
     * @param $ident
     * @return String success or false
     */
	public function deleteLiveSpotterDataByIdent($ident)
	{
		$ident = filter_var($ident, FILTER_SANITIZE_STRING);
		$query  = 'DELETE FROM spotter_live WHERE ident = :ident';
        
    		try {
			
			$sth = $this->db->prepare($query);
			$sth->execute(array(':ident' => $ident));
		} catch(PDOException $e) {
			return "error";
		}

		return "success";
	}

    /**
     * Deletes all info in the table for an id
     *
     * @param $id
     * @return String success or false
     */
	public function deleteLiveSpotterDataById($id)
	{
		$id = filter_var($id, FILTER_SANITIZE_STRING);
		$query  = 'DELETE FROM spotter_live WHERE flightaware_id = :id';
        
    		try {
			
			$sth = $this->db->prepare($query);
			$sth->execute(array(':id' => $id));
		} catch(PDOException $e) {
			return "error";
		}

		return "success";
	}


    /**
     * Gets the aircraft ident within the last hour
     *
     * @param $ident
     * @return String the ident
     */
	public function getIdentFromLastHour($ident)
	{
		global $globalDBdriver, $globalTimezone;
		if ($globalDBdriver == 'mysql') {
			$query  = 'SELECT spotter_live.ident FROM spotter_live 
				WHERE spotter_live.ident = :ident 
				AND spotter_live.date >= DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 HOUR) 
				AND spotter_live.date < UTC_TIMESTAMP()';
			$query_data = array(':ident' => $ident);
		} else {
			$query  = "SELECT spotter_live.ident FROM spotter_live 
				WHERE spotter_live.ident = :ident 
				AND spotter_live.date >= now() AT TIME ZONE 'UTC' - INTERVAL '1 HOURS'
				AND spotter_live.date < now() AT TIME ZONE 'UTC'";
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
     * Check recent aircraft
     *
     * @param $ident
     * @return String the ident
     */
	public function checkIdentRecent($ident)
	{
		global $globalDBdriver, $globalTimezone;
		if ($globalDBdriver == 'mysql') {
			$query  = 'SELECT spotter_live.ident, spotter_live.flightaware_id FROM spotter_live 
				WHERE spotter_live.ident = :ident 
				AND spotter_live.date >= DATE_SUB(UTC_TIMESTAMP(),INTERVAL 20 MINUTE)'; 
//				AND spotter_live.date < UTC_TIMESTAMP()";
			$query_data = array(':ident' => $ident);
		} else {
			$query  = "SELECT spotter_live.ident, spotter_live.flightaware_id FROM spotter_live 
				WHERE spotter_live.ident = :ident 
				AND spotter_live.date >= now() AT TIME ZONE 'UTC' - INTERVAL '20 MINUTES'";
//				AND spotter_live.date < now() AT TIME ZONE 'UTC'";
			$query_data = array(':ident' => $ident);
		}
		
		$sth = $this->db->prepare($query);
		$sth->execute($query_data);
		$ident_result='';
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$ident_result = $row['flightaware_id'];
		}
		return $ident_result;
        }

    /**
     * Check recent aircraft by id
     *
     * @param $id
     * @return String the ident
     */
	public function checkIdRecent($id)
	{
		global $globalDBdriver, $globalTimezone;
		if ($globalDBdriver == 'mysql') {
			$query  = 'SELECT spotter_live.ident, spotter_live.flightaware_id FROM spotter_live 
				WHERE spotter_live.flightaware_id = :id 
				AND spotter_live.date >= DATE_SUB(UTC_TIMESTAMP(),INTERVAL 10 HOUR)'; 
//				AND spotter_live.date < UTC_TIMESTAMP()";
			$query_data = array(':id' => $id);
		} else {
			$query  = "SELECT spotter_live.ident, spotter_live.flightaware_id FROM spotter_live 
				WHERE spotter_live.flightaware_id = :id 
				AND spotter_live.date >= now() AT TIME ZONE 'UTC' - INTERVAL '10 HOUR'";
//				AND spotter_live.date < now() AT TIME ZONE 'UTC'";
			$query_data = array(':id' => $id);
		}
		
		$sth = $this->db->prepare($query);
		$sth->execute($query_data);
		$ident_result='';
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$ident_result = $row['flightaware_id'];
		}
		return $ident_result;
        }

    /**
     * Check recent aircraft by ModeS
     *
     * @param $modes
     * @return String the ModeS
     */
	public function checkModeSRecent($modes)
	{
		global $globalDBdriver, $globalTimezone;
		if ($globalDBdriver == 'mysql') {
			$query  = 'SELECT spotter_live.ModeS, spotter_live.flightaware_id FROM spotter_live 
				WHERE spotter_live.ModeS = :modes 
				AND spotter_live.date >= DATE_SUB(UTC_TIMESTAMP(),INTERVAL 20 MINUTE)'; 
//				AND spotter_live.date < UTC_TIMESTAMP()";
			$query_data = array(':modes' => $modes);
		} else {
			$query  = "SELECT spotter_live.ModeS, spotter_live.flightaware_id FROM spotter_live 
				WHERE spotter_live.ModeS = :modes 
				AND spotter_live.date >= CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '20 MINUTE'";
//			//	AND spotter_live.date < CURRENT_TIMESTAMP AT TIME ZONE 'UTC'";
			$query_data = array(':modes' => $modes);
		}
		
		$sth = $this->db->prepare($query);
		$sth->execute($query_data);
		$ident_result='';
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			//$ident_result = $row['spotter_live_id'];
			$ident_result = $row['flightaware_id'];
		}
		return $ident_result;
	}

    /**
     * Gets the aircraft data from the last 20 seconds
     *
     * @param string $q
     * @return array the spotter data
     */
	public function getRealTimeData($q = '')
	{
		global $globalDBdriver;
		$additional_query = '';
		if ($q != "")
		{
			if (!is_string($q))
			{
				return array();
			} else {
				$q_array = explode(" ", $q);
				foreach ($q_array as $q_item){
					$q_item = filter_var($q_item,FILTER_SANITIZE_STRING);
					$additional_query .= " AND (";
					$additional_query .= "(spotter_live.aircraft_icao like '%".$q_item."%') OR ";
					$additional_query .= "(spotter_live.aircraft_name like '%".$q_item."%') OR ";
					$additional_query .= "(spotter_live.aircraft_manufacturer like '%".$q_item."%') OR ";
					$additional_query .= "(spotter_live.airline_icao like '%".$q_item."%') OR ";
					$additional_query .= "(spotter_live.departure_airport_icao like '%".$q_item."%') OR ";
					$additional_query .= "(spotter_live.arrival_airport_icao like '%".$q_item."%') OR ";
					$additional_query .= "(spotter_live.registration like '%".$q_item."%') OR ";
					$additional_query .= "(spotter_live.ident like '%".$q_item."%')";
					$additional_query .= ")";
				}
			}
		}
		if ($globalDBdriver == 'mysql') {
			$query  = "SELECT spotter_live.* FROM spotter_live 
			    WHERE spotter_live.date >= DATE_SUB(UTC_TIMESTAMP(),INTERVAL 20 SECOND) ".$additional_query." 
			    AND spotter_live.date < UTC_TIMESTAMP()";
		} else {
			$query  = "SELECT spotter_live.* FROM spotter_live 
			    WHERE spotter_live.date::timestamp >= CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '20 SECONDS' ".$additional_query." 
			    AND spotter_live.date::timestamp < CURRENT_TIMESTAMP AT TIME ZONE 'UTC'";
		}
		$Spotter = new Spotter();
		$spotter_array = $Spotter->getDataFromDB($query, array());
		return $spotter_array;
	}

    /**
     * Adds a new spotter data
     *
     * @param String $flightaware_id the ID from flightaware
     * @param String $ident the flight ident
     * @param String $aircraft_icao the aircraft type
     * @param String $departure_airport_icao the departure airport
     * @param String $arrival_airport_icao the arrival airport
     * @param string $latitude
     * @param string $longitude
     * @param string $waypoints
     * @param string $altitude
     * @param string $altitude_real
     * @param string $heading
     * @param string $groundspeed
     * @param string $date
     * @param string $departure_airport_time
     * @param string $arrival_airport_time
     * @param string $squawk
     * @param string $route_stop
     * @param string $ModeS
     * @param bool $putinarchive
     * @param string $registration
     * @param string $pilot_id
     * @param string $pilot_name
     * @param string $verticalrate
     * @param bool $noarchive
     * @param bool $ground
     * @param string $format_source
     * @param string $source_name
     * @param string $over_country
     * @return String success or false
     */
	public function addLiveSpotterData($flightaware_id = '', $ident = '', $aircraft_icao = '', $departure_airport_icao = '', $arrival_airport_icao = '', $latitude = '', $longitude = '', $waypoints = '', $altitude = '', $altitude_real = '',$heading = '', $groundspeed = '', $date = '',$departure_airport_time = '', $arrival_airport_time = '', $squawk = '', $route_stop = '', $ModeS = '', $putinarchive = false,$registration = '',$pilot_id = '', $pilot_name = '', $verticalrate = '', $noarchive = false, $ground = false,$format_source = '', $source_name = '', $over_country = '')
	{
		global $globalURL, $globalArchive, $globalDebug;
		$Common = new Common();
		date_default_timezone_set('UTC');

		//getting the airline information
		if ($ident != '')
		{
			if (!is_string($ident))
			{
				return false;
			} 
		}

		//getting the aircraft information
		if ($aircraft_icao != '')
		{
			if (!is_string($aircraft_icao))
			{
				return false;
			} 
		} 
		//getting the departure airport information
		if ($departure_airport_icao != '')
		{
			if (!is_string($departure_airport_icao))
			{
				return false;
			} 
		}

		//getting the arrival airport information
		if ($arrival_airport_icao != '')
		{
			if (!is_string($arrival_airport_icao))
			{
				return false;
			}
		}


		if ($latitude != '')
		{
			if (!is_numeric($latitude))
			{
				return false;
			}
		} else return '';

		if ($longitude != '')
		{
			if (!is_numeric($longitude))
			{
				return false;
			}
		} else return '';

		if ($waypoints != '')
		{
			if (!is_string($waypoints))
			{
				return false;
			}
		}

		if ($altitude != '')
		{
			if (!is_numeric($altitude))
			{
				return false;
			}
		} else $altitude = 0;
		if ($altitude_real != '')
		{
			if (!is_numeric($altitude_real))
			{
				return false;
			}
		} else $altitude_real = 0;

		if ($heading != '')
		{
			if (!is_numeric($heading))
			{
				return false;
			}
		} else $heading = 0;

		if ($groundspeed != '')
		{
			if (!is_numeric($groundspeed))
			{
				return false;
			}
		} else $groundspeed = 0;
		date_default_timezone_set('UTC');
		if ($date == '') $date = date("Y-m-d H:i:s", time());

        
		$flightaware_id = filter_var($flightaware_id,FILTER_SANITIZE_STRING);
		$ident = filter_var($ident,FILTER_SANITIZE_STRING);
		$aircraft_icao = filter_var($aircraft_icao,FILTER_SANITIZE_STRING);
		$departure_airport_icao = filter_var($departure_airport_icao,FILTER_SANITIZE_STRING);
		$arrival_airport_icao = filter_var($arrival_airport_icao,FILTER_SANITIZE_STRING);
		$latitude = filter_var($latitude,FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
		$longitude = filter_var($longitude,FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
		$waypoints = filter_var($waypoints,FILTER_SANITIZE_STRING);
		$altitude = filter_var($altitude,FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
		$altitude_real = filter_var($altitude_real,FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
		$heading = filter_var($heading,FILTER_SANITIZE_NUMBER_INT);
		$groundspeed = filter_var($groundspeed,FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
		$squawk = filter_var($squawk,FILTER_SANITIZE_NUMBER_INT);
		$route_stop = filter_var($route_stop,FILTER_SANITIZE_STRING);
		$ModeS = filter_var($ModeS,FILTER_SANITIZE_STRING);
		$pilot_id = filter_var($pilot_id,FILTER_SANITIZE_STRING);
		$pilot_name = filter_var($pilot_name,FILTER_SANITIZE_STRING);
		$format_source = filter_var($format_source,FILTER_SANITIZE_STRING);
		$source_name = filter_var($source_name,FILTER_SANITIZE_STRING);
		$over_country = filter_var($over_country,FILTER_SANITIZE_STRING);
		$verticalrate = filter_var($verticalrate,FILTER_SANITIZE_NUMBER_INT);

		$airline_name = '';
		$airline_icao = '';
		$airline_country = '';
		$airline_type = '';
		$aircraft_shadow = '';
		$aircraft_type = '';
		$aircraft_manufacturer = '';



		$aircraft_name = '';
		$departure_airport_name = '';
		$departure_airport_city = '';
		$departure_airport_country = '';
		
		$arrival_airport_name = '';
		$arrival_airport_city = '';
		$arrival_airport_country = '';
		
            	
            	if ($squawk == '' || $Common->isInteger($squawk) === false ) $squawk = NULL;
            	if ($verticalrate == '' || $Common->isInteger($verticalrate) === false ) $verticalrate = NULL;
            	if ($groundspeed == '' || $Common->isInteger($groundspeed) === false ) $groundspeed = 0;
            	if ($heading == '' || $Common->isInteger($heading) === false ) $heading = 0;
		
		$query = '';
		if ($globalArchive) {
			if ($globalDebug) echo '-- Delete previous data -- ';
			$query .= 'DELETE FROM spotter_live WHERE flightaware_id = :flightaware_id;';
		}

		$query  .= 'INSERT INTO spotter_live (flightaware_id, ident, registration, airline_name, airline_icao, airline_country, airline_type, aircraft_icao, aircraft_shadow, aircraft_name, aircraft_manufacturer, departure_airport_icao, departure_airport_name, departure_airport_city, departure_airport_country, arrival_airport_icao, arrival_airport_name, arrival_airport_city, arrival_airport_country, latitude, longitude, waypoints, altitude, heading, ground_speed, date, departure_airport_time, arrival_airport_time, squawk, route_stop, ModeS, pilot_id, pilot_name, verticalrate, ground, format_source, source_name, over_country, real_altitude) 
		VALUES (:flightaware_id,:ident,:registration,:airline_name,:airline_icao,:airline_country,:airline_type,:aircraft_icao,:aircraft_shadow,:aircraft_type,:aircraft_manufacturer,:departure_airport_icao,:departure_airport_name, :departure_airport_city, :departure_airport_country, :arrival_airport_icao, :arrival_airport_name, :arrival_airport_city, :arrival_airport_country, :latitude,:longitude,:waypoints,:altitude,:heading,:groundspeed,:date,:departure_airport_time,:arrival_airport_time,:squawk,:route_stop,:ModeS, :pilot_id, :pilot_name, :verticalrate, :ground, :format_source, :source_name, :over_country, :real_altitude)';

		$query_values = array(':flightaware_id' => $flightaware_id,':ident' => $ident, ':registration' => $registration,':airline_name' => $airline_name,':airline_icao' => $airline_icao,':airline_country' => $airline_country,':airline_type' => $airline_type,':aircraft_icao' => $aircraft_icao,':aircraft_shadow' => $aircraft_shadow,':aircraft_type' => $aircraft_type,':aircraft_manufacturer' => $aircraft_manufacturer,':departure_airport_icao' => $departure_airport_icao,':departure_airport_name' => $departure_airport_name,':departure_airport_city' => $departure_airport_city,':departure_airport_country' => $departure_airport_country,':arrival_airport_icao' => $arrival_airport_icao,':arrival_airport_name' => $arrival_airport_name,':arrival_airport_city' => $arrival_airport_city,':arrival_airport_country' => $arrival_airport_country,':latitude' => $latitude,':longitude' => $longitude, ':waypoints' => $waypoints,':altitude' => $altitude,':heading' => $heading,':groundspeed' => $groundspeed,':date' => $date, ':departure_airport_time' => $departure_airport_time,':arrival_airport_time' => $arrival_airport_time, ':squawk' => $squawk,':route_stop' => $route_stop,':ModeS' => $ModeS, ':pilot_id' => $pilot_id, ':pilot_name' => $pilot_name, ':verticalrate' => $verticalrate, ':format_source' => $format_source,':ground' => $ground, ':source_name' => $source_name, ':over_country' => $over_country,':real_altitude' => $altitude_real);
		try {
			
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
			$sth->closeCursor();
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
		if (isset($globalArchive) && $globalArchive && $putinarchive && $noarchive !== true) {
		    if ($globalDebug) echo '(Add to Spotter archive : ';
		    $SpotterArchive = new SpotterArchive($this->db);
		    $result =  $SpotterArchive->addSpotterArchiveData($flightaware_id, $ident, $registration, $airline_name, $airline_icao, $airline_country, $airline_type, $aircraft_icao, $aircraft_shadow, $aircraft_name, $aircraft_manufacturer, $departure_airport_icao, $departure_airport_name, $departure_airport_city, $departure_airport_country, $departure_airport_time,$arrival_airport_icao, $arrival_airport_name, $arrival_airport_city, $arrival_airport_country, $arrival_airport_time, $route_stop, $date,$latitude, $longitude, $waypoints, $altitude, $altitude_real,$heading, $groundspeed, $squawk, $ModeS, $pilot_id, $pilot_name,$verticalrate,$format_source,$source_name, $over_country);
		    if ($globalDebug) echo $result.')';
		} elseif ($globalDebug && $putinarchive !== true) {
			echo '(Not adding to archive)';
		} elseif ($globalDebug && $noarchive === true) {
			echo '(No archive)';
		}
		return "success";

	}

	public function getOrderBy()
	{
		$orderby = array("aircraft_asc" => array("key" => "aircraft_asc", "value" => "Aircraft Type - ASC", "sql" => "ORDER BY spotter_live.aircraft_icao ASC"), "aircraft_desc" => array("key" => "aircraft_desc", "value" => "Aircraft Type - DESC", "sql" => "ORDER BY spotter_live.aircraft_icao DESC"),"manufacturer_asc" => array("key" => "manufacturer_asc", "value" => "Aircraft Manufacturer - ASC", "sql" => "ORDER BY spotter_live.aircraft_manufacturer ASC"), "manufacturer_desc" => array("key" => "manufacturer_desc", "value" => "Aircraft Manufacturer - DESC", "sql" => "ORDER BY spotter_live.aircraft_manufacturer DESC"),"airline_name_asc" => array("key" => "airline_name_asc", "value" => "Airline Name - ASC", "sql" => "ORDER BY spotter_live.airline_name ASC"), "airline_name_desc" => array("key" => "airline_name_desc", "value" => "Airline Name - DESC", "sql" => "ORDER BY spotter_live.airline_name DESC"), "ident_asc" => array("key" => "ident_asc", "value" => "Ident - ASC", "sql" => "ORDER BY spotter_live.ident ASC"), "ident_desc" => array("key" => "ident_desc", "value" => "Ident - DESC", "sql" => "ORDER BY spotter_live.ident DESC"), "airport_departure_asc" => array("key" => "airport_departure_asc", "value" => "Departure Airport - ASC", "sql" => "ORDER BY spotter_live.departure_airport_city ASC"), "airport_departure_desc" => array("key" => "airport_departure_desc", "value" => "Departure Airport - DESC", "sql" => "ORDER BY spotter_live.departure_airport_city DESC"), "airport_arrival_asc" => array("key" => "airport_arrival_asc", "value" => "Arrival Airport - ASC", "sql" => "ORDER BY spotter_live.arrival_airport_city ASC"), "airport_arrival_desc" => array("key" => "airport_arrival_desc", "value" => "Arrival Airport - DESC", "sql" => "ORDER BY spotter_live.arrival_airport_city DESC"), "date_asc" => array("key" => "date_asc", "value" => "Date - ASC", "sql" => "ORDER BY spotter_live.date ASC"), "date_desc" => array("key" => "date_desc", "value" => "Date - DESC", "sql" => "ORDER BY spotter_live.date DESC"));
		return $orderby;
	}

}


?>
