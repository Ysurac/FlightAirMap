<?php
/**
 * This class is part of FlightAirmap. It's used for trackers live data
 *
 * Copyright (c) Ycarus (Yannick Chabanois) <support@flightairmap.com>
 * Licensed under AGPL license.
 * For more information see: https://www.flightairmap.com/
*/
//$global_query = "SELECT tracker_live.* FROM tracker_live";

class TrackerLive {
	public $db;
	static $global_query = "SELECT tracker_live.* FROM tracker_live";

	public function __construct($dbc = null) {
		$Connection = new Connection($dbc);
		$this->db = $Connection->db();
		if ($this->db === null) die('Error: No DB connection. (TrackerLive)');
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
					$filter_query_join .= " INNER JOIN (SELECT famtrackid FROM tracker_output WHERE tracker_output.ident IN ('".implode("','",$flt['idents'])."') AND tracker_output.format_source IN ('".implode("','",$flt['source'])."')) spid ON spid.famtrackid = tracker_live.famtrackid";
				} else {
					$filter_query_join .= " INNER JOIN (SELECT famtrackid FROM tracker_output WHERE tracker_output.ident IN ('".implode("','",$flt['idents'])."')) spid ON spid.famtrackid = tracker_live.famtrackid";
				}
			}
		}
		if (isset($filter['source']) && !empty($filter['source'])) {
			$filter_query_where .= " AND format_source IN ('".implode("','",$filter['source'])."')";
		}
		if (isset($filter['ident']) && !empty($filter['ident'])) {
			$filter_query_where .= " AND ident = '".$filter['ident']."'";
		}
		if (isset($filter['id']) && !empty($filter['id'])) {
			$filter_query_where .= " AND famtrackid = '".$filter['id']."'";
		}
		if ((isset($filter['year']) && $filter['year'] != '') || (isset($filter['month']) && $filter['month'] != '') || (isset($filter['day']) && $filter['day'] != '')) {
			$filter_query_date = '';
			
			if (isset($filter['year']) && $filter['year'] != '') {
				if ($globalDBdriver == 'mysql') {
					$filter_query_date .= " AND YEAR(tracker_output.date) = '".$filter['year']."'";
				} else {
					$filter_query_date .= " AND EXTRACT(YEAR FROM tracker_output.date) = '".$filter['year']."'";
				}
			}
			if (isset($filter['month']) && $filter['month'] != '') {
				if ($globalDBdriver == 'mysql') {
					$filter_query_date .= " AND MONTH(tracker_output.date) = '".$filter['month']."'";
				} else {
					$filter_query_date .= " AND EXTRACT(MONTH FROM tracker_output.date) = '".$filter['month']."'";
				}
			}
			if (isset($filter['day']) && $filter['day'] != '') {
				if ($globalDBdriver == 'mysql') {
					$filter_query_date .= " AND DAY(tracker_output.date) = '".$filter['day']."'";
				} else {
					$filter_query_date .= " AND EXTRACT(DAY FROM tracker_output.date) = '".$filter['day']."'";
				}
			}
			$filter_query_join .= " INNER JOIN (SELECT famtrackid FROM tracker_output".preg_replace('/^ AND/',' WHERE',$filter_query_date).") sd ON sd.famtrackid = tracker_live.famtrackid";
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
	public function getLiveTrackerData($limit = '', $sort = '', $filter = array())
	{
		global $globalDBdriver, $globalLiveInterval;
		$Tracker = new Tracker($this->db);
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

		if (!isset($globalLiveInterval)) $globalLiveInterval = '200';
		if ($globalDBdriver == 'mysql') {
			//$query  = "SELECT tracker_live.* FROM tracker_live INNER JOIN (SELECT l.famtrackid, max(l.date) as maxdate FROM tracker_live l WHERE DATE_SUB(UTC_TIMESTAMP(),INTERVAL 30 SECOND) <= l.date GROUP BY l.famtrackid) s on tracker_live.famtrackid = s.famtrackid AND tracker_live.date = s.maxdate";
			$query  = 'SELECT tracker_live.* FROM tracker_live INNER JOIN (SELECT l.famtrackid, max(l.date) as maxdate FROM tracker_live l WHERE DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.$globalLiveInterval.' SECOND) <= l.date GROUP BY l.famtrackid) s on tracker_live.famtrackid = s.famtrackid AND tracker_live.date = s.maxdate'.$filter_query.$orderby_query;
		} else {
			$query  = "SELECT tracker_live.* FROM tracker_live INNER JOIN (SELECT l.famtrackid, max(l.date) as maxdate FROM tracker_live l WHERE CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$globalLiveInterval." SECONDS' <= l.date GROUP BY l.famtrackid) s on tracker_live.famtrackid = s.famtrackid AND tracker_live.date = s.maxdate".$filter_query.$orderby_query;
		}
		$spotter_array = $Tracker->getDataFromDB($query.$limit_query,array(),'',true);

		return $spotter_array;
	}

    /**
     * Gets Minimal Live Spotter data
     *
     * @param array $filter
     * @return array the spotter information
     */
	public function getMinLiveTrackerData($filter = array())
	{
		global $globalDBdriver, $globalLiveInterval;
		date_default_timezone_set('UTC');

		$filter_query = $this->getFilter($filter,true,true);

		if (!isset($globalLiveInterval)) $globalLiveInterval = '200';
		if ($globalDBdriver == 'mysql') {
			$query  = 'SELECT tracker_live.ident, tracker_live.type,tracker_live.famtrackid, tracker_live.latitude, tracker_live.longitude, tracker_live.altitude, tracker_live.heading, tracker_live.ground_speed, tracker_live.date, tracker_live.format_source 
			FROM tracker_live INNER JOIN (SELECT l.famtrackid, max(l.date) as maxdate FROM tracker_live l WHERE DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.$globalLiveInterval.' SECOND) <= l.date GROUP BY l.famtrackid) s on tracker_live.famtrackid = s.famtrackid AND tracker_live.date = s.maxdate'.$filter_query." tracker_live.latitude <> 0 AND tracker_live.longitude <> 0";
		} else {
			$query  = "SELECT tracker_live.ident, tracker_live.type,tracker_live.famtrackid, tracker_live.latitude, tracker_live.longitude, tracker_live.altitude, tracker_live.heading, tracker_live.ground_speed, tracker_live.date, tracker_live.format_source 
			FROM tracker_live INNER JOIN (SELECT l.famtrackid, max(l.date) as maxdate FROM tracker_live l WHERE CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$globalLiveInterval." SECONDS' <= l.date GROUP BY l.famtrackid) s on tracker_live.famtrackid = s.famtrackid AND tracker_live.date = s.maxdate".$filter_query." tracker_live.latitude <> '0' AND tracker_live.longitude <> '0'";


		}
//		$spotter_array = Spotter->getDataFromDB($query.$limit_query);
//		echo $query;

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
     * @param $coord
     * @param array $filter
     * @param bool $limit
     * @return array the spotter information
     */
	public function getMinLastLiveTrackerData($coord,$filter = array(),$limit = false)
	{
		global $globalDBdriver, $globalLiveInterval, $globalArchive, $globalMap3DTrackersLimit;
		date_default_timezone_set('UTC');
		$usecoord = false;
		if (is_array($coord) && !empty($coord)) {
			$minlong = filter_var($coord[0],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
			$minlat = filter_var($coord[1],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
			$maxlong = filter_var($coord[2],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
			$maxlat = filter_var($coord[3],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
			$usecoord = true;
		}
		$filter_query = $this->getFilter($filter,true,true);

		if (!isset($globalLiveInterval)) $globalLiveInterval = '200';
		if (!isset($globalMap3DTrackersLimit) || $globalMap3DTrackersLimit == '') $globalMap3DTrackersLimit = '300';
		if ($globalDBdriver == 'mysql') {
			if (isset($globalArchive) && $globalArchive) {
				$query  = "SELECT * FROM (
					SELECT tracker_archive.ident, tracker_archive.famtrackid,tracker_archive.type,tracker_archive.latitude, tracker_archive.longitude, tracker_archive.altitude, tracker_archive.heading, tracker_archive.ground_speed, tracker_archive.date, tracker_archive.format_source 
					FROM tracker_archive INNER JOIN (SELECT famtrackid FROM tracker_live".$filter_query.' DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.$globalLiveInterval." SECOND) <= tracker_live.date) l ON l.famtrackid = tracker_archive.famtrackid ";
				if ($usecoord) $query .= "AND tracker_archive.latitude BETWEEN ".$minlat." AND ".$maxlat." AND tracker_archive.longitude BETWEEN ".$minlong." AND ".$maxlong." ";
				$query .= "UNION
					SELECT tracker_live.ident, tracker_live.famtrackid, tracker_live.type,tracker_live.latitude, tracker_live.longitude, tracker_live.altitude, tracker_live.heading, tracker_live.ground_speed, tracker_live.date, tracker_live.format_source 
					FROM tracker_live".$filter_query.' DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.$globalLiveInterval." SECOND) <= tracker_live.date ";
				if ($usecoord) $query .= "AND tracker_live.latitude BETWEEN ".$minlat." AND ".$maxlat." AND tracker_live.longitude BETWEEN ".$minlong." AND ".$maxlong;
				$query .= ") AS tracker
				    WHERE latitude <> '0' AND longitude <> '0' 
				    ORDER BY famtrackid, date";
				if ($limit) $query .= " LIMIT ".$globalMap3DTrackersLimit;
			} else {
				$query  = 'SELECT tracker_live.ident, tracker_live.famtrackid,tracker_live.type, tracker_live.latitude, tracker_live.longitude, tracker_live.altitude, tracker_live.heading, tracker_live.ground_speed, tracker_live.date, tracker_live.format_source 
				    FROM tracker_live'.$filter_query.' DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.$globalLiveInterval." SECOND) <= tracker_live.date ";
				if ($usecoord) $query .= "AND tracker_live.latitude BETWEEN ".$minlat." AND ".$maxlat." AND tracker_live.longitude BETWEEN ".$minlong." AND ".$maxlong." ";
				$query .= "AND tracker_live.latitude <> '0' AND tracker_live.longitude <> '0' 
				    ORDER BY tracker_live.famtrackid, tracker_live.date";
				if ($limit) $query .= " LIMIT ".$globalMap3DTrackersLimit;
			}
		} else {
			if (isset($globalArchive) && $globalArchive) {
				$query  = "SELECT * FROM (
					SELECT tracker_archive.ident, tracker_archive.famtrackid,tracker_archive.type,tracker_archive.latitude, tracker_archive.longitude, tracker_archive.altitude, tracker_archive.heading, tracker_archive.ground_speed, tracker_archive.date, tracker_archive.format_source 
					FROM tracker_archive INNER JOIN (SELECT famtrackid FROM tracker_live".$filter_query." CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$globalLiveInterval." SECONDS' <= tracker_live.date) l ON l.famtrackid = tracker_archive.famtrackid ";
				if ($usecoord) $query .= "AND tracker_archive.latitude BETWEEN ".$minlat." AND ".$maxlat." AND tracker_archive.longitude BETWEEN ".$minlong." AND ".$maxlong." ";
				$query .= "UNION
					SELECT tracker_live.ident, tracker_live.famtrackid, tracker_live.type,tracker_live.latitude, tracker_live.longitude, tracker_live.altitude, tracker_live.heading, tracker_live.ground_speed, tracker_live.date, tracker_live.format_source 
					FROM tracker_live".$filter_query." CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$globalLiveInterval." SECONDS' <= tracker_live.date";
				if ($usecoord) $query .= " AND tracker_live.latitude BETWEEN ".$minlat." AND ".$maxlat." AND tracker_live.longitude BETWEEN ".$minlong." AND ".$maxlong;
				$query .= ") AS tracker
				    WHERE latitude <> '0' AND longitude <> '0' 
				    ORDER BY famtrackid, date";
				if ($limit) $query .= " LIMIT ".$globalMap3DTrackersLimit;
			} else {
				$query  = "SELECT tracker_live.ident, tracker_live.famtrackid, tracker_live.type,tracker_live.latitude, tracker_live.longitude, tracker_live.altitude, tracker_live.heading, tracker_live.ground_speed, tracker_live.date, tracker_live.format_source 
				    FROM tracker_live".$filter_query." CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$globalLiveInterval." SECONDS' <= tracker_live.date AND tracker_live.latitude <> '0' AND tracker_live.longitude <> '0' ";
				if ($usecoord) $query .= "AND tracker_live.latitude BETWEEN ".$minlat." AND ".$maxlat." AND tracker_live.longitude BETWEEN ".$minlong." AND ".$maxlong." ";
				$query .= "ORDER BY tracker_live.famtrackid, tracker_live.date";
				if ($limit) $query .= " LIMIT ".$globalMap3DTrackersLimit;
			}
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
     * Gets number of latest data entry
     *
     * @param array $filter
     * @return String number of entry
     */
	public function getLiveTrackerCount($filter = array())
	{
		global $globalDBdriver, $globalLiveInterval;
		$filter_query = $this->getFilter($filter,true,true);

		if (!isset($globalLiveInterval)) $globalLiveInterval = '200';
		if ($globalDBdriver == 'mysql') {
			$query = 'SELECT COUNT(DISTINCT tracker_live.famtrackid) as nb FROM tracker_live'.$filter_query.' DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.$globalLiveInterval.' SECOND) <= date';
		} else {
			$query = "SELECT COUNT(DISTINCT tracker_live.famtrackid) as nb FROM tracker_live".$filter_query." CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$globalLiveInterval." SECONDS' <= date";
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
	public function getLiveTrackerDatabyCoord($coord, $filter = array())
	{
		global $globalDBdriver, $globalLiveInterval;
		$Tracker = new Tracker($this->db);
		if (!isset($globalLiveInterval)) $globalLiveInterval = '200';
		$filter_query = $this->getFilter($filter);

		if (is_array($coord)) {
			$minlong = filter_var($coord[0],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
			$minlat = filter_var($coord[1],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
			$maxlong = filter_var($coord[2],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
			$maxlat = filter_var($coord[3],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
		} else return array();
		if ($globalDBdriver == 'mysql') {
			$query  = 'SELECT tracker_live.* FROM tracker_live INNER JOIN (SELECT l.famtrackid, max(l.date) as maxdate FROM tracker_live l WHERE DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.$globalLiveInterval.' SECOND) <= l.date GROUP BY l.famtrackid) s on tracker_live.famtrackid = s.famtrackid AND tracker_live.date = s.maxdate AND tracker_live.latitude BETWEEN '.$minlat.' AND '.$maxlat.' AND tracker_live.longitude BETWEEN '.$minlong.' AND '.$maxlong.' GROUP BY tracker_live.famtrackid'.$filter_query;
		} else {
			$query  = "SELECT tracker_live.* FROM tracker_live INNER JOIN (SELECT l.famtrackid, max(l.date) as maxdate FROM tracker_live l WHERE NOW() at time zone 'UTC'  - INTERVAL '".$globalLiveInterval." SECONDS' <= l.date GROUP BY l.famtrackid) s on tracker_live.famtrackid = s.famtrackid AND tracker_live.date = s.maxdate AND tracker_live.latitude BETWEEN ".$minlat." AND ".$maxlat." AND tracker_live.longitude BETWEEN ".$minlong." AND ".$maxlong." GROUP BY tracker_live.famtrackid".$filter_query;
		}
		$spotter_array = $Tracker->getDataFromDB($query);
		return $spotter_array;
	}

    /**
     * Gets all the spotter information based on the latest data entry and coord
     *
     * @param $coord
     * @param array $filter
     * @return array the spotter information
     */
	public function getMinLiveTrackerDatabyCoord($coord, $filter = array())
	{
		global $globalDBdriver, $globalLiveInterval, $globalArchive;
		$Tracker = new Tracker($this->db);
		if (!isset($globalLiveInterval)) $globalLiveInterval = '200';
		$filter_query = $this->getFilter($filter,true,true);

		if (is_array($coord)) {
			$minlong = filter_var($coord[0],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
			$minlat = filter_var($coord[1],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
			$maxlong = filter_var($coord[2],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
			$maxlat = filter_var($coord[3],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
		} else return array();
		/*
		if ($globalDBdriver == 'mysql') {
			$query  = 'SELECT tracker_live.ident, tracker_live.famtrackid,tracker_live.type, tracker_live.latitude, tracker_live.longitude, tracker_live.altitude, tracker_live.heading, tracker_live.ground_speed, tracker_live.date, tracker_live.format_source 
			FROM tracker_live'.$filter_query.' DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.$globalLiveInterval." SECOND) <= tracker_live.date AND tracker_live.latitude <> '0' AND tracker_live.longitude <> '0' AND tracker_live.latitude BETWEEN ".$minlat.' AND '.$maxlat.' AND tracker_live.longitude BETWEEN '.$minlong.' AND '.$maxlong."
			ORDER BY tracker_live.famtrackid, tracker_live.date";
		} else {
			$query  = "SELECT tracker_live.ident, tracker_live.type,tracker_live.famtrackid, tracker_live.latitude, tracker_live.longitude, tracker_live.altitude, tracker_live.heading, tracker_live.ground_speed, tracker_live.date, tracker_live.format_source 
			FROM tracker_live INNER JOIN (SELECT l.famtrackid, max(l.date) as maxdate FROM tracker_live l WHERE CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$globalLiveInterval." SECONDS' <= l.date l.latitude BETWEEN ".$minlat." AND ".$maxlat." AND l.longitude BETWEEN ".$minlong." AND ".$maxlong." GROUP BY l.famtrackid) s on tracker_live.famtrackid = s.famtrackid AND tracker_live.date = s.maxdate".$filter_query." tracker_live.latitude <> '0' AND tracker_live.longitude <> '0'";
		}
		*/
		if ($globalDBdriver == 'mysql') {
			if (isset($globalArchive) && $globalArchive === TRUE) {
				$query  = 'SELECT tracker_live.ident, tracker_live.famtrackid,tracker_live.type, tracker_live.latitude, tracker_live.longitude, tracker_live.altitude, tracker_live.heading, tracker_live.ground_speed, tracker_live.date, tracker_live.format_source 
				    FROM tracker_live 
				    '.$filter_query.' DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.$globalLiveInterval.' SECOND) <= tracker_live.date 
				    AND tracker_live.latitude BETWEEN '.$minlat.' AND '.$maxlat.' AND tracker_live.longitude BETWEEN '.$minlong.' AND '.$maxlong.'
				    AND tracker_live.latitude <> 0 AND tracker_live.longitude <> 0 ORDER BY date DESC';
			} else {
				$query  = 'SELECT tracker_live.ident, tracker_live.famtrackid,tracker_live.type, tracker_live.latitude, tracker_live.longitude, tracker_live.altitude, tracker_live.heading, tracker_live.ground_speed, tracker_live.date, tracker_live.format_source 
				    FROM tracker_live 
				    INNER JOIN (SELECT l.famtrackid, max(l.date) as maxdate 
					FROM tracker_live l 
					WHERE DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.$globalLiveInterval.' SECOND) <= l.date 
					AND l.latitude BETWEEN '.$minlat.' AND '.$maxlat.' AND l.longitude BETWEEN '.$minlong.' AND '.$maxlong.'
					GROUP BY l.famtrackid
				    ) s on tracker_live.famtrackid = s.famtrackid 
				    AND tracker_live.date = s.maxdate'.$filter_query.' tracker_live.latitude <> 0 AND tracker_live.longitude <> 0 ORDER BY date DESC';
			}
		} else {
			if (isset($globalArchive) && $globalArchive === TRUE) {
				$query  = "SELECT tracker_live.ident, tracker_live.famtrackid,tracker_live.type, tracker_live.latitude, tracker_live.longitude, tracker_live.altitude, tracker_live.heading, tracker_live.ground_speed, tracker_live.date, tracker_live.format_source 
				    FROM tracker_live 
				    ".$filter_query." CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$globalLiveInterval." SECONDS' <= tracker_live.date 
				    AND tracker_live.latitude BETWEEN ".$minlat." AND ".$maxlat." 
				    AND tracker_live.longitude BETWEEN ".$minlong." AND ".$maxlong." 
				    AND tracker_live.latitude <> '0' AND tracker_live.longitude <> '0' ORDER BY date DESC";
			} else {
				$query  = "SELECT tracker_live.ident, tracker_live.famtrackid,tracker_live.type, tracker_live.latitude, tracker_live.longitude, tracker_live.altitude, tracker_live.heading, tracker_live.ground_speed, tracker_live.date, tracker_live.format_source 
				    FROM tracker_live 
				    INNER JOIN (SELECT l.famtrackid, max(l.date) as maxdate 
					FROM tracker_live l 
					WHERE CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$globalLiveInterval." SECONDS' <= l.date 
					AND l.latitude BETWEEN ".$minlat." AND ".$maxlat." 
					AND l.longitude BETWEEN ".$minlong." AND ".$maxlong." 
					GROUP BY l.famtrackid
				    ) s on tracker_live.famtrackid = s.famtrackid 
				    AND tracker_live.date = s.maxdate".$filter_query." tracker_live.latitude <> '0' AND tracker_live.longitude <> '0' ORDER BY date DESC";
			}
		}
		$spotter_array = $Tracker->getDataFromDB($query);
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
	public function getLatestTrackerForLayar($lat, $lng, $radius, $interval)
	{
		$Tracker = new Tracker($this->db);
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
				//$additional_query = ' AND DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 MINUTE) <= tracker_live.date ';
				return array();
			} else {
				if ($interval == '1m')
				{
					$additional_query = ' AND DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 MINUTE) <= tracker_live.date ';
				} else if ($interval == '15m'){
					$additional_query = ' AND DATE_SUB(UTC_TIMESTAMP(),INTERVAL 15 MINUTE) <= tracker_live.date ';
				}
			}
		} else {
			$additional_query = ' AND DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 MINUTE) <= tracker_live.date ';
		}

		$query  = "SELECT tracker_live.*, ( 6371 * acos( cos( radians(:lat) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(:lng) ) + sin( radians(:lat) ) * sin( radians( latitude ) ) ) ) AS distance FROM tracker_live 
                   WHERE tracker_live.latitude <> '' 
                                   AND tracker_live.longitude <> '' 
                   ".$additional_query."
                   HAVING distance < :radius  
                                   ORDER BY distance";

		$spotter_array = $Tracker->getDataFromDB($query, array(':lat' => $lat, ':lng' => $lng,':radius' => $radius));

		return $spotter_array;
	}


    /**
     * Gets all the spotter information based on a particular callsign
     *
     * @param $ident
     * @return array the spotter information
     */
	public function getLastLiveTrackerDataByIdent($ident)
	{
		$Tracker = new Tracker($this->db);
		date_default_timezone_set('UTC');

		$ident = filter_var($ident, FILTER_SANITIZE_STRING);
                $query  = 'SELECT tracker_live.* FROM tracker_live INNER JOIN (SELECT l.famtrackid, max(l.date) as maxdate FROM tracker_live l WHERE l.ident = :ident GROUP BY l.famtrackid) s on tracker_live.famtrackid = s.famtrackid AND tracker_live.date = s.maxdate ORDER BY tracker_live.date DESC';

		$spotter_array = $Tracker->getDataFromDB($query,array(':ident' => $ident),'',true);

		return $spotter_array;
	}

    /**
     * Gets all the spotter information based on a particular callsign
     *
     * @param $ident
     * @param $date
     * @return array the spotter information
     */
	public function getDateLiveTrackerDataByIdent($ident,$date)
	{
		$Tracker = new Tracker($this->db);
		date_default_timezone_set('UTC');

		$ident = filter_var($ident, FILTER_SANITIZE_STRING);
                $query  = 'SELECT tracker_live.* FROM tracker_live INNER JOIN (SELECT l.famtrackid, max(l.date) as maxdate FROM tracker_live l WHERE l.ident = :ident AND l.date <= :date GROUP BY l.famtrackid) s on tracker_live.famtrackid = s.famtrackid AND tracker_live.date = s.maxdate ORDER BY tracker_live.date DESC';

                $date = date('c',$date);
		$spotter_array = $Tracker->getDataFromDB($query,array(':ident' => $ident,':date' => $date));

		return $spotter_array;
	}

    /**
     * Gets last spotter information based on a particular callsign
     *
     * @param $id
     * @return array the spotter information
     */
	public function getLastLiveTrackerDataById($id)
	{
		$Tracker = new Tracker($this->db);
		date_default_timezone_set('UTC');

		$id = filter_var($id, FILTER_SANITIZE_STRING);
                $query  = 'SELECT tracker_live.* FROM tracker_live INNER JOIN (SELECT l.famtrackid, max(l.date) as maxdate FROM tracker_live l WHERE l.famtrackid = :id GROUP BY l.famtrackid) s on tracker_live.famtrackid = s.famtrackid AND tracker_live.date = s.maxdate ORDER BY tracker_live.date DESC';

		$spotter_array = $Tracker->getDataFromDB($query,array(':id' => $id),'',true);

		return $spotter_array;
	}

    /**
     * Gets last spotter information based on a particular callsign
     *
     * @param $id
     * @param $date
     * @return array the spotter information
     */
	public function getDateLiveTrackerDataById($id,$date)
	{
		$Tracker = new Tracker($this->db);
		date_default_timezone_set('UTC');

		$id = filter_var($id, FILTER_SANITIZE_STRING);
                $query  = 'SELECT tracker_live.* FROM tracker_live INNER JOIN (SELECT l.famtrackid, max(l.date) as maxdate FROM tracker_live l WHERE l.famtrackid = :id AND l.date <= :date GROUP BY l.famtrackid) s on tracker_live.famtrackid = s.famtrackid AND tracker_live.date = s.maxdate ORDER BY tracker_live.date DESC';
                $date = date('c',$date);
		$spotter_array = $Tracker->getDataFromDB($query,array(':id' => $id,':date' => $date),'',true);

		return $spotter_array;
	}

    /**
     * Gets altitude information based on a particular callsign
     *
     * @param $ident
     * @return array the spotter information
     */
	public function getAltitudeLiveTrackerDataByIdent($ident)
	{

		date_default_timezone_set('UTC');

		$ident = filter_var($ident, FILTER_SANITIZE_STRING);
                $query  = 'SELECT tracker_live.altitude, tracker_live.date FROM tracker_live WHERE tracker_live.ident = :ident';

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
	public function getAllLiveTrackerDataById($id,$liveinterval = false)
	{
		global $globalDBdriver, $globalLiveInterval;
		date_default_timezone_set('UTC');
		$id = filter_var($id, FILTER_SANITIZE_STRING);
		//$query  = self::$global_query.' WHERE tracker_live.famtrackid = :id ORDER BY date';
		if ($globalDBdriver == 'mysql') {
			$query = 'SELECT tracker_live.* FROM tracker_live WHERE tracker_live.famtrackid = :id';
			if ($liveinterval === true) $query .= ' AND DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.$globalLiveInterval.' SECOND) <= date';
			elseif ($liveinterval !== false) $query .= " AND date <= '".date('c',$liveinterval)."'";
			$query .= ' ORDER BY date';
		} else {
			$query = 'SELECT tracker_live.* FROM tracker_live WHERE tracker_live.famtrackid = :id';
			if ($liveinterval === true) $query .= " AND CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$globalLiveInterval." SECONDS' <= date";
			elseif ($liveinterval !== false) $query .= " AND date <= '".date('c',$liveinterval)."'";
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
	public function getAllLiveTrackerDataByIdent($ident)
	{
		date_default_timezone_set('UTC');
		$ident = filter_var($ident, FILTER_SANITIZE_STRING);
		$query  = self::$global_query.' WHERE tracker_live.ident = :ident';
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
	public function deleteLiveTrackerData()
	{
		global $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			//$query  = "DELETE FROM tracker_live WHERE DATE_SUB(UTC_TIMESTAMP(),INTERVAL 30 MINUTE) >= tracker_live.date";
			$query  = 'DELETE FROM tracker_live WHERE DATE_SUB(UTC_TIMESTAMP(),INTERVAL 9 HOUR) >= tracker_live.date';
            		//$query  = "DELETE FROM tracker_live WHERE tracker_live.id IN (SELECT tracker_live.id FROM tracker_live INNER JOIN (SELECT l.famtrackid, max(l.date) as maxdate FROM tracker_live l GROUP BY l.famtrackid) s on tracker_live.famtrackid = s.famtrackid AND tracker_live.date = s.maxdate AND DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 HOUR) >= tracker_live.date)";
		} else {
			$query  = "DELETE FROM tracker_live WHERE NOW() AT TIME ZONE 'UTC' - INTERVAL '9 HOURS' >= tracker_live.date";
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
	public function deleteLiveTrackerDataNotUpdated()
	{
		global $globalDBdriver, $globalDebug;
		if ($globalDBdriver == 'mysql') {
			//$query = 'SELECT famtrackid FROM tracker_live WHERE DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 HOUR) >= tracker_live.date AND tracker_live.famtrackid NOT IN (SELECT famtrackid FROM tracker_live WHERE DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 HOUR) < tracker_live.date) LIMIT 800 OFFSET 0';
    			$query = "SELECT tracker_live.famtrackid FROM tracker_live INNER JOIN (SELECT famtrackid,MAX(date) as max_date FROM tracker_live GROUP BY famtrackid) s ON s.famtrackid = tracker_live.famtrackid AND DATE_SUB(UTC_TIMESTAMP(), INTERVAL 2 HOUR) >= s.max_date LIMIT 1200 OFFSET 0";
    			try {
				
				$sth = $this->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error";
			}
			$query_delete = 'DELETE FROM tracker_live WHERE famtrackid IN (';
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
                                	$query_delete = 'DELETE FROM tracker_live WHERE famtrackid IN (';
                                	$j = 0;
				}
				$query_delete .= "'".$row['famtrackid']."',";
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
			//$query = "SELECT famtrackid FROM tracker_live WHERE NOW() AT TIME ZONE 'UTC' - INTERVAL '9 HOURS' >= tracker_live.date AND tracker_live.famtrackid NOT IN (SELECT famtrackid FROM tracker_live WHERE NOW() AT TIME ZONE 'UTC' - INTERVAL '9 HOURS' < tracker_live.date) LIMIT 800 OFFSET 0";
    			//$query = "SELECT tracker_live.famtrackid FROM tracker_live INNER JOIN (SELECT famtrackid,MAX(date) as max_date FROM tracker_live GROUP BY famtrackid) s ON s.famtrackid = tracker_live.famtrackid AND NOW() AT TIME ZONE 'UTC' - INTERVAL '2 HOURS' >= s.max_date LIMIT 800 OFFSET 0";
    			$query = "DELETE FROM tracker_live WHERE famtrackid IN (SELECT tracker_live.famtrackid FROM tracker_live INNER JOIN (SELECT famtrackid,MAX(date) as max_date FROM tracker_live GROUP BY famtrackid) s ON s.famtrackid = tracker_live.famtrackid AND NOW() AT TIME ZONE 'UTC' - INTERVAL '2 HOURS' >= s.max_date LIMIT 800 OFFSET 0)";
    			try {
				
				$sth = $this->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error";
			}
/*			$query_delete = "DELETE FROM tracker_live WHERE famtrackid IN (";
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
                                	$query_delete = "DELETE FROM tracker_live WHERE famtrackid IN (";
                                	$j = 0;
				}
				$query_delete .= "'".$row['famtrackid']."',";
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
	public function deleteLiveTrackerDataByIdent($ident)
	{
		$ident = filter_var($ident, FILTER_SANITIZE_STRING);
		$query  = 'DELETE FROM tracker_live WHERE ident = :ident';
        
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
	public function deleteLiveTrackerDataById($id)
	{
		$id = filter_var($id, FILTER_SANITIZE_STRING);
		$query  = 'DELETE FROM tracker_live WHERE famtrackid = :id';
        
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
			$query  = 'SELECT tracker_live.ident FROM tracker_live 
				WHERE tracker_live.ident = :ident 
				AND tracker_live.date >= DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 HOUR) 
				AND tracker_live.date < UTC_TIMESTAMP()';
			$query_data = array(':ident' => $ident);
		} else {
			$query  = "SELECT tracker_live.ident FROM tracker_live 
				WHERE tracker_live.ident = :ident 
				AND tracker_live.date >= now() AT TIME ZONE 'UTC' - INTERVAL '1 HOURS'
				AND tracker_live.date < now() AT TIME ZONE 'UTC'";
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
			$query  = 'SELECT tracker_live.ident, tracker_live.famtrackid FROM tracker_live 
				WHERE tracker_live.ident = :ident 
				AND tracker_live.date >= DATE_SUB(UTC_TIMESTAMP(),INTERVAL 30 MINUTE)'; 
//				AND tracker_live.date < UTC_TIMESTAMP()";
			$query_data = array(':ident' => $ident);
		} else {
			$query  = "SELECT tracker_live.ident, tracker_live.famtrackid FROM tracker_live 
				WHERE tracker_live.ident = :ident 
				AND tracker_live.date >= now() AT TIME ZONE 'UTC' - INTERVAL '30 MINUTES'";
//				AND tracker_live.date < now() AT TIME ZONE 'UTC'";
			$query_data = array(':ident' => $ident);
		}
		
		$sth = $this->db->prepare($query);
		$sth->execute($query_data);
		$ident_result='';
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$ident_result = $row['famtrackid'];
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
			$query  = 'SELECT tracker_live.ident, tracker_live.famtrackid FROM tracker_live 
				WHERE tracker_live.famtrackid = :id 
				AND tracker_live.date >= DATE_SUB(UTC_TIMESTAMP(),INTERVAL 10 HOUR)'; 
//				AND tracker_live.date < UTC_TIMESTAMP()";
			$query_data = array(':id' => $id);
		} else {
			$query  = "SELECT tracker_live.ident, tracker_live.famtrackid FROM tracker_live 
				WHERE tracker_live.famtrackid = :id 
				AND tracker_live.date >= now() AT TIME ZONE 'UTC' - INTERVAL '10 HOUR'";
//				AND tracker_live.date < now() AT TIME ZONE 'UTC'";
			$query_data = array(':id' => $id);
		}
		
		$sth = $this->db->prepare($query);
		$sth->execute($query_data);
		$ident_result='';
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$ident_result = $row['famtrackid'];
		}
		return $ident_result;
        }

    /**
     * Adds a new spotter data
     *
     * @param String $famtrackid the ID from flightaware
     * @param String $ident the flight ident
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
     * @return String success or false
     */
	public function addLiveTrackerData($famtrackid = '', $ident = '', $latitude = '', $longitude = '', $altitude = '', $heading = '', $groundspeed = '', $date = '', $putinarchive = false, $comment = '', $type = '',$noarchive = false,$format_source = '', $source_name = '', $over_country = '')
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

		if ($altitude != '')
		{
			if (!is_numeric($altitude))
			{
				return false;
			}
		} else $altitude = 0;

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

        
		$famtrackid = filter_var($famtrackid,FILTER_SANITIZE_STRING);
		$ident = filter_var($ident,FILTER_SANITIZE_STRING);
		$latitude = filter_var($latitude,FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
		$longitude = filter_var($longitude,FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
		$altitude = filter_var($altitude,FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
		$heading = filter_var($heading,FILTER_SANITIZE_NUMBER_INT);
		$groundspeed = filter_var($groundspeed,FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
		$format_source = filter_var($format_source,FILTER_SANITIZE_STRING);
		$source_name = filter_var($source_name,FILTER_SANITIZE_STRING);
		$over_country = filter_var($over_country,FILTER_SANITIZE_STRING);
		$comment = filter_var($comment,FILTER_SANITIZE_STRING);
		$type = filter_var($type,FILTER_SANITIZE_STRING);

            	if ($groundspeed == '' || $Common->isInteger($groundspeed) === false ) $groundspeed = 0;
            	if ($heading == '' || $Common->isInteger($heading) === false ) $heading = 0;
            	
		$query = '';
		if ($globalArchive) {
			if ($globalDebug) echo '-- Delete previous data -- ';
			$query .= 'DELETE FROM tracker_live WHERE famtrackid = :famtrackid;';
		}
		$query  .= 'INSERT INTO tracker_live (famtrackid, ident, latitude, longitude, altitude, heading, ground_speed, date, format_source, source_name, over_country, comment, type) 
		VALUES (:famtrackid,:ident,:latitude,:longitude,:altitude,:heading,:groundspeed,:date,:format_source, :source_name, :over_country,:comment,:type)';

		$query_values = array(':famtrackid' => $famtrackid,':ident' => $ident,':latitude' => $latitude,':longitude' => $longitude,':altitude' => $altitude,':heading' => $heading,':groundspeed' => $groundspeed,':date' => $date, ':format_source' => $format_source, ':source_name' => $source_name, ':over_country' => $over_country,':comment' => $comment,':type' => $type);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
			$sth->closeCursor();
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
                /*
                echo 'putinarchive : '.$putinarchive."\n";
                echo 'noarchive : '.$noarchive."\n";
                */
		if (isset($globalArchive) && $globalArchive && $putinarchive && $noarchive !== true) {
		    if ($globalDebug) echo '(Add to Tracker archive '.$famtrackid.' : ';
		    $TrackerArchive = new TrackerArchive($this->db);
		    $result =  $TrackerArchive->addTrackerArchiveData($famtrackid, $ident,$latitude, $longitude, $altitude, $heading, $groundspeed, $date, $putinarchive, $comment, $type,$noarchive,$format_source, $source_name, $over_country);
		    if ($globalDebug) echo $result.')';
		}

		return "success";

	}

	public function getOrderBy()
	{
		$orderby = array("aircraft_asc" => array("key" => "aircraft_asc", "value" => "Aircraft Type - ASC", "sql" => "ORDER BY tracker_live.aircraft_icao ASC"), "aircraft_desc" => array("key" => "aircraft_desc", "value" => "Aircraft Type - DESC", "sql" => "ORDER BY tracker_live.aircraft_icao DESC"),"manufacturer_asc" => array("key" => "manufacturer_asc", "value" => "Aircraft Manufacturer - ASC", "sql" => "ORDER BY tracker_live.aircraft_manufacturer ASC"), "manufacturer_desc" => array("key" => "manufacturer_desc", "value" => "Aircraft Manufacturer - DESC", "sql" => "ORDER BY tracker_live.aircraft_manufacturer DESC"),"airline_name_asc" => array("key" => "airline_name_asc", "value" => "Airline Name - ASC", "sql" => "ORDER BY tracker_live.airline_name ASC"), "airline_name_desc" => array("key" => "airline_name_desc", "value" => "Airline Name - DESC", "sql" => "ORDER BY tracker_live.airline_name DESC"), "ident_asc" => array("key" => "ident_asc", "value" => "Ident - ASC", "sql" => "ORDER BY tracker_live.ident ASC"), "ident_desc" => array("key" => "ident_desc", "value" => "Ident - DESC", "sql" => "ORDER BY tracker_live.ident DESC"), "airport_departure_asc" => array("key" => "airport_departure_asc", "value" => "Departure Airport - ASC", "sql" => "ORDER BY tracker_live.departure_airport_city ASC"), "airport_departure_desc" => array("key" => "airport_departure_desc", "value" => "Departure Airport - DESC", "sql" => "ORDER BY tracker_live.departure_airport_city DESC"), "airport_arrival_asc" => array("key" => "airport_arrival_asc", "value" => "Arrival Airport - ASC", "sql" => "ORDER BY tracker_live.arrival_airport_city ASC"), "airport_arrival_desc" => array("key" => "airport_arrival_desc", "value" => "Arrival Airport - DESC", "sql" => "ORDER BY tracker_live.arrival_airport_city DESC"), "date_asc" => array("key" => "date_asc", "value" => "Date - ASC", "sql" => "ORDER BY tracker_live.date ASC"), "date_desc" => array("key" => "date_desc", "value" => "Date - DESC", "sql" => "ORDER BY tracker_live.date DESC"));
		return $orderby;
	}

}


?>
