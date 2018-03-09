<?php
/**
 * This class is part of FlightAirmap. It's used for marine live data
 *
 * Copyright (c) Ycarus (Yannick Chabanois) <support@flightairmap.com>
 * Licensed under AGPL license.
 * For more information see: https://www.flightairmap.com/
*/
//$global_query = "SELECT marine_live.* FROM marine_live";

class MarineLive {
	/** @var $db PDO */
	public $db;
	static $global_query = "SELECT marine_live.* FROM marine_live";

	public function __construct($dbc = null) {
		$Connection = new Connection($dbc);
		$this->db = $Connection->db();
		if ($this->db === null) die('Error: No DB connection. (MarineLive)');
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
					$filter_query_join .= " INNER JOIN (SELECT fammarine_id FROM marine_output WHERE marine_output.ident IN ('".implode("','",$flt['idents'])."') AND marine_output.format_source IN ('".implode("','",$flt['source'])."')) spid ON spid.fammarine_id = marine_live.fammarine_id";
				} else {
					$filter_query_join .= " INNER JOIN (SELECT fammarine_id FROM marine_output WHERE marine_output.ident IN ('".implode("','",$flt['idents'])."')) spid ON spid.fammarine_id = marine_live.fammarine_id";
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
			$filter_query_where .= " AND fammarine_id = '".$filter['id']."'";
		}
		if (isset($filter['mmsi']) && !empty($filter['mmsi'])) {
			$filter_query_where .= " AND mmsi = '".$filter['mmsi']."'";
		}
		if (isset($filter['race']) && !empty($filter['race'])) {
			$filter_query_where .= " AND race_id = '".$filter['race']."'";
		}
		if ((isset($filter['year']) && $filter['year'] != '') || (isset($filter['month']) && $filter['month'] != '') || (isset($filter['day']) && $filter['day'] != '')) {
			$filter_query_date = '';
			
			if (isset($filter['year']) && $filter['year'] != '') {
				if ($globalDBdriver == 'mysql') {
					$filter_query_date .= " AND YEAR(marine_output.date) = '".$filter['year']."'";
				} else {
					$filter_query_date .= " AND EXTRACT(YEAR FROM marine_output.date) = '".$filter['year']."'";
				}
			}
			if (isset($filter['month']) && $filter['month'] != '') {
				if ($globalDBdriver == 'mysql') {
					$filter_query_date .= " AND MONTH(marine_output.date) = '".$filter['month']."'";
				} else {
					$filter_query_date .= " AND EXTRACT(MONTH FROM marine_output.date) = '".$filter['month']."'";
				}
			}
			if (isset($filter['day']) && $filter['day'] != '') {
				if ($globalDBdriver == 'mysql') {
					$filter_query_date .= " AND DAY(marine_output.date) = '".$filter['day']."'";
				} else {
					$filter_query_date .= " AND EXTRACT(DAY FROM marine_output.date) = '".$filter['day']."'";
				}
			}
			$filter_query_join .= " INNER JOIN (SELECT fammarine_id FROM marine_output".preg_replace('/^ AND/',' WHERE',$filter_query_date).") sd ON sd.fammarine_id = marine_live.fammarine_id";
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
	public function getLiveMarineData($limit = '', $sort = '', $filter = array())
	{
		global $globalDBdriver, $globalLiveInterval;
		$Marine = new Marine($this->db);
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
		if ($orderby_query == '') $orderby_query= ' ORDER BY date DESC';

		if (!isset($globalLiveInterval)) $globalLiveInterval = '200';
		if ($globalDBdriver == 'mysql') {
			//$query  = "SELECT marine_live.* FROM marine_live INNER JOIN (SELECT l.fammarine_id, max(l.date) as maxdate FROM marine_live l WHERE DATE_SUB(UTC_TIMESTAMP(),INTERVAL 30 SECOND) <= l.date GROUP BY l.fammarine_id) s on marine_live.fammarine_id = s.fammarine_id AND marine_live.date = s.maxdate";
			$query  = 'SELECT marine_live.* FROM marine_live INNER JOIN (SELECT l.fammarine_id, max(l.date) as maxdate FROM marine_live l WHERE DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.$globalLiveInterval.' SECOND) <= l.date GROUP BY l.fammarine_id) s on marine_live.fammarine_id = s.fammarine_id AND marine_live.date = s.maxdate'.$filter_query.$orderby_query;
		} else {
			$query  = "SELECT marine_live.* FROM marine_live INNER JOIN (SELECT l.fammarine_id, max(l.date) as maxdate FROM marine_live l WHERE CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$globalLiveInterval." SECONDS' <= l.date GROUP BY l.fammarine_id) s on marine_live.fammarine_id = s.fammarine_id AND marine_live.date = s.maxdate".$filter_query.$orderby_query;
		}
		$spotter_array = $Marine->getDataFromDB($query.$limit_query,array(),'',true);

		return $spotter_array;
	}

    /**
     * Gets Minimal Live Spotter data
     *
     * @param array $filter
     * @return array the spotter information
     */
	public function getMinLiveMarineData($filter = array())
	{
		global $globalDBdriver, $globalLiveInterval;
		date_default_timezone_set('UTC');

		$filter_query = $this->getFilter($filter,true,true);

		if (!isset($globalLiveInterval)) $globalLiveInterval = '200';
		if ($globalDBdriver == 'mysql') {
			$query  = 'SELECT marine_live.mmsi, marine_live.ident, marine_live.type,marine_live.fammarine_id, marine_live.latitude, marine_live.longitude, marine_live.heading, marine_live.ground_speed, marine_live.date, marine_live.format_source, marine_live.captain_name, marine_live.race_id, marine_live.race_rank, marine_live.race_name 
			FROM marine_live INNER JOIN (SELECT l.fammarine_id, max(l.date) as maxdate FROM marine_live l WHERE DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.$globalLiveInterval.' SECOND) <= l.date GROUP BY l.fammarine_id) s on marine_live.fammarine_id = s.fammarine_id AND marine_live.date = s.maxdate'.$filter_query." marine_live.latitude <> 0 AND marine_live.longitude <> 0 ORDER BY marine_live.race_rank";
		} else {
			$query  = "SELECT marine_live.mmsi, marine_live.ident, marine_live.type,marine_live.fammarine_id, marine_live.latitude, marine_live.longitude, marine_live.heading, marine_live.ground_speed, marine_live.date, marine_live.format_source, marine_live.captain_name, marine_live.race_id, marine_live.race_rank, marine_live.race_name 
			FROM marine_live INNER JOIN (SELECT l.fammarine_id, max(l.date) as maxdate FROM marine_live l WHERE CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$globalLiveInterval." SECONDS' <= l.date GROUP BY l.fammarine_id) s on marine_live.fammarine_id = s.fammarine_id AND marine_live.date = s.maxdate".$filter_query." marine_live.latitude <> '0' AND marine_live.longitude <> '0' ORDER BY marine_live.race_rank";
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
     * @param bool $limit
     * @param string $id
     * @return array the spotter information
     */
	public function getMinLastLiveMarineData($coord = array(),$filter = array(), $limit = false, $id = '')
	{
		global $globalDBdriver, $globalLiveInterval, $globalMap3DMarinesLimit, $globalArchive;
		date_default_timezone_set('UTC');
		$usecoord = false;
		if (is_array($coord) && !empty($coord)) {
			$minlong = filter_var($coord[0],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
			$minlat = filter_var($coord[1],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
			$maxlong = filter_var($coord[2],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
			$maxlat = filter_var($coord[3],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
			$usecoord = true;
		}
		$id = filter_var($id,FILTER_SANITIZE_STRING);
		$filter_query = $this->getFilter($filter,true,true);

		if (!isset($globalLiveInterval)) $globalLiveInterval = '200';
		if (!isset($globalMap3DMarinesLimit) || $globalMap3DMarinesLimit == '') $globalMap3DMarinesLimit = '300';
		if ($globalDBdriver == 'mysql') {
			if (isset($globalArchive) && $globalArchive === TRUE) {
				$query  = 'SELECT * FROM (SELECT marine_archive.ident, marine_archive.fammarine_id,marine_archive.type_id,marine_archive.type, marine_archive.latitude, marine_archive.longitude, marine_archive.heading, marine_archive.ground_speed, marine_archive.date, marine_archive.format_source, marine_archive.captain_name, marine_archive.race_id, marine_archive.race_rank, marine_archive.race_name 
				    FROM marine_archive INNER JOIN (SELECT fammarine_id FROM marine_live'.$filter_query.' DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.$globalLiveInterval." SECOND) <= marine_live.date) l ON l.fammarine_id = marine_archive.fammarine_id ";
				if ($usecoord) $query .= "AND marine_archive.latitude BETWEEN ".$minlat." AND ".$maxlat." AND marine_archive.longitude BETWEEN ".$minlong." AND ".$maxlong." ";
				if ($id != '') $query .= "OR marine_archive.fammarine_id = :id ";
				$query .= "UNION
				    SELECT marine_live.ident, marine_live.fammarine_id,marine_live.type_id,marine_live.type, marine_live.latitude, marine_live.longitude, marine_live.heading, marine_live.ground_speed, marine_live.date, marine_live.format_source, marine_live.captain_name, marine_live.race_id, marine_live.race_rank, marine_live.race_name 
				    FROM marine_live".$filter_query.' DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.$globalLiveInterval." SECOND) <= marine_live.date";
				if ($usecoord) $query .= " AND marine_live.latitude BETWEEN ".$minlat." AND ".$maxlat." AND marine_live.longitude BETWEEN ".$minlong." AND ".$maxlong;
				if ($id != '') $query .= "OR marine_live.fammarine_id = :id ";
				$query .= ") AS marine 
				    WHERE latitude <> '0' AND longitude <> '0' 
				    ORDER BY fammarine_id, date";
				if ($limit) $query .= " LIMIT ".$globalMap3DMarinesLimit;
			} else {
				$query  = 'SELECT marine_live.ident, marine_live.fammarine_id,marine_live.type_id,marine_live.type, marine_live.latitude, marine_live.longitude, marine_live.heading, marine_live.ground_speed, marine_live.date, marine_live.format_source, marine_live.captain_name, marine_live.race_id, marine_live.race_rank, marine_live.race_name 
				    FROM marine_live'.$filter_query.' DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.$globalLiveInterval." SECOND) <= marine_live.date ";
				if ($usecoord) $query .= "AND marine_live.latitude BETWEEN ".$minlat." AND ".$maxlat." AND marine_live.longitude BETWEEN ".$minlong." AND ".$maxlong." ";
				if ($id != '') $query .= "OR marine_live.fammarine_id = :id ";
				$query .= "AND marine_live.latitude <> '0' AND marine_live.longitude <> '0' 
				ORDER BY marine_live.fammarine_id, marine_live.date";
				if ($limit) $query .= " LIMIT ".$globalMap3DMarinesLimit;
			}
		} else {
			if (isset($globalArchive) && $globalArchive === TRUE) {
				$query  = "SELECT * FROM (SELECT marine_archive.ident, marine_archive.fammarine_id, marine_archive.type_id, marine_archive.type,marine_archive.latitude, marine_archive.longitude, marine_archive.heading, marine_archive.ground_speed, marine_archive.date, marine_archive.format_source, marine_archive.captain_name, marine_archive.race_id, marine_archive.race_rank, marine_archive.race_name 
				    FROM marine_archive INNER JOIN (SELECT fammarine_id FROM marine_live".$filter_query." CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$globalLiveInterval." SECONDS' <= marine_live.date) l ON l.fammarine_id = marine_archive.fammarine_id ";
				if ($usecoord) $query .= "AND (marine_archive.latitude BETWEEN ".$minlat." AND ".$maxlat." AND marine_archive.longitude BETWEEN ".$minlong." AND ".$maxlong.") ";
				if ($id != '') $query .= "OR marine_archive.fammarine_id = :id ";
				$query .= "UNION
				    SELECT marine_live.ident, marine_live.fammarine_id, marine_live.type_id, marine_live.type,marine_live.latitude, marine_live.longitude, marine_live.heading, marine_live.ground_speed, marine_live.date, marine_live.format_source, marine_live.captain_name, marine_live.race_id, marine_live.race_rank, marine_live.race_name 
				    FROM marine_live".$filter_query." CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$globalLiveInterval." SECONDS' <= marine_live.date";
				if ($usecoord) $query .= " AND (marine_live.latitude BETWEEN ".$minlat." AND ".$maxlat." AND marine_live.longitude BETWEEN ".$minlong." AND ".$maxlong.")";
				if ($id != '') $query .= " OR marine_live.fammarine_id = :id";
				$query .= ") AS marine WHERE latitude <> '0' AND longitude <> '0' ";
				$query .= "ORDER BY fammarine_id, date";
				if ($limit) $query .= " LIMIT ".$globalMap3DMarinesLimit;
			} else {
				$query  = "SELECT marine_live.ident, marine_live.fammarine_id, marine_live.type_id, marine_live.type,marine_live.latitude, marine_live.longitude, marine_live.heading, marine_live.ground_speed, marine_live.date, marine_live.format_source, marine_live.captain_name, marine_live.race_id, marine_live.race_rank, marine_live.race_name 
				    FROM marine_live".$filter_query." CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$globalLiveInterval." SECONDS' <= marine_live.date ";
				if ($usecoord) $query .= "AND (marine_live.latitude BETWEEN ".$minlat." AND ".$maxlat." AND marine_live.longitude BETWEEN ".$minlong." AND ".$maxlong.") ";
				if ($id != '') $query .= "OR marine_live.fammarine_id = :id ";
				$query .= "AND marine_live.latitude <> '0' AND marine_live.longitude <> '0' 
				ORDER BY marine_live.fammarine_id, marine_live.date";
				if ($limit) $query .= " LIMIT ".$globalMap3DMarinesLimit;
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
     * @param bool $limit
     * @return array the spotter information
     */
	public function getMinLastLiveMarineDataByID($id = '',$filter = array(), $limit = false)
	{
		global $globalDBdriver, $globalLiveInterval, $globalMap3DMarinesLimit, $globalArchive;
		date_default_timezone_set('UTC');
		$id = filter_var($id,FILTER_SANITIZE_STRING);
		$filter_query = $this->getFilter($filter,true,true);

		if (!isset($globalLiveInterval)) $globalLiveInterval = '200';
		if (!isset($globalMap3DMarinesLimit) || $globalMap3DMarinesLimit == '') $globalMap3DMarinesLimit = '300';
		if ($globalDBdriver == 'mysql') {
			if (isset($globalArchive) && $globalArchive === TRUE) {
				$query  = 'SELECT * FROM (SELECT marine_archive.ident, marine_archive.fammarine_id,marine_archive.type_id,marine_archive.type, marine_archive.latitude, marine_archive.longitude, marine_archive.heading, marine_archive.ground_speed, marine_archive.date, marine_archive.format_source, marine_archive.captain_name, marine_archive.race_id, marine_archive.race_rank, marine_archive.race_name 
				    FROM marine_archive INNER JOIN (SELECT fammarine_id FROM marine_live'.$filter_query.' marine_live.fammarine_id = :id) l ON l.fammarine_id = marine_archive.fammarine_id ';
				$query .= "UNION
				    SELECT marine_live.ident, marine_live.fammarine_id,marine_live.type_id,marine_live.type, marine_live.latitude, marine_live.longitude, marine_live.heading, marine_live.ground_speed, marine_live.date, marine_live.format_source, marine_live.captain_name, marine_live.race_id, marine_live.race_rank, marine_live.race_name 
				    FROM marine_live".$filter_query.' marine_live.fammarine_id = :id';
				$query .= ") AS marine 
				    WHERE latitude <> '0' AND longitude <> '0' 
				    ORDER BY fammarine_id, date";
				if ($limit) $query .= " LIMIT ".$globalMap3DMarinesLimit;
			} else {
				$query  = 'SELECT marine_live.ident, marine_live.fammarine_id,marine_live.type_id,marine_live.type, marine_live.latitude, marine_live.longitude, marine_live.heading, marine_live.ground_speed, marine_live.date, marine_live.format_source, marine_live.captain_name, marine_live.race_id, marine_live.race_rank, marine_live.race_name 
				    FROM marine_live'.$filter_query.' marine_live.fammarine_id = :id ';
				$query .= "AND marine_live.latitude <> '0' AND marine_live.longitude <> '0' 
				ORDER BY marine_live.fammarine_id, marine_live.date";
				if ($limit) $query .= " LIMIT ".$globalMap3DMarinesLimit;
			}
		} else {
			if (isset($globalArchive) && $globalArchive === TRUE) {
				$query  = "SELECT * FROM (SELECT marine_archive.ident, marine_archive.fammarine_id, marine_archive.type_id, marine_archive.type,marine_archive.latitude, marine_archive.longitude, marine_archive.heading, marine_archive.ground_speed, marine_archive.date, marine_archive.format_source, marine_archive.captain_name, marine_archive.race_id, marine_archive.race_rank, marine_archive.race_name 
				    FROM marine_archive INNER JOIN (SELECT fammarine_id FROM marine_live".$filter_query." marine_live.fammarine_id = :id) l ON l.fammarine_id = marine_archive.fammarine_id ";
				$query .= "UNION
				    SELECT marine_live.ident, marine_live.fammarine_id, marine_live.type_id, marine_live.type,marine_live.latitude, marine_live.longitude, marine_live.heading, marine_live.ground_speed, marine_live.date, marine_live.format_source, marine_live.captain_name, marine_live.race_id, marine_live.race_rank, marine_live.race_name 
				    FROM marine_live".$filter_query." marine_live.fammarine_id = :id";
				$query .= ") AS marine 
				    WHERE latitude <> '0' AND longitude <> '0' 
				    ORDER BY fammarine_id, date";
				if ($limit) $query .= " LIMIT ".$globalMap3DMarinesLimit;
			} else {
				$query  = "SELECT marine_live.ident, marine_live.fammarine_id, marine_live.type_id, marine_live.type,marine_live.latitude, marine_live.longitude, marine_live.heading, marine_live.ground_speed, marine_live.date, marine_live.format_source, marine_live.captain_name, marine_live.race_id, marine_live.race_rank, marine_live.race_name 
				    FROM marine_live".$filter_query." marine_live.fammarine_id = :id ";
				$query .= "AND marine_live.latitude <> '0' AND marine_live.longitude <> '0' 
				ORDER BY marine_live.fammarine_id, marine_live.date";
				if ($limit) $query .= " LIMIT ".$globalMap3DMarinesLimit;
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
	public function getLiveMarineCount($filter = array())
	{
		global $globalDBdriver, $globalLiveInterval;
		$filter_query = $this->getFilter($filter,true,true);

		if (!isset($globalLiveInterval)) $globalLiveInterval = '200';
		if ($globalDBdriver == 'mysql') {
			$query = 'SELECT COUNT(DISTINCT marine_live.fammarine_id) as nb FROM marine_live'.$filter_query.' DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.$globalLiveInterval.' SECOND) <= date';
		} else {
			$query = "SELECT COUNT(DISTINCT marine_live.fammarine_id) as nb FROM marine_live".$filter_query." CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$globalLiveInterval." SECONDS' <= date";
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
	public function getLiveMarineDatabyCoord($coord, $filter = array())
	{
		global $globalDBdriver, $globalLiveInterval;
		$Marine = new Marine($this->db);
		if (!isset($globalLiveInterval)) $globalLiveInterval = '200';
		$filter_query = $this->getFilter($filter);

		if (is_array($coord)) {
			$minlong = filter_var($coord[0],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
			$minlat = filter_var($coord[1],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
			$maxlong = filter_var($coord[2],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
			$maxlat = filter_var($coord[3],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
		} else return array();
		if ($globalDBdriver == 'mysql') {
			$query  = 'SELECT marine_live.* FROM marine_live INNER JOIN (SELECT l.fammarine_id, max(l.date) as maxdate FROM marine_live l WHERE DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.$globalLiveInterval.' SECOND) <= l.date GROUP BY l.fammarine_id) s on marine_live.fammarine_id = s.fammarine_id AND marine_live.date = s.maxdate AND marine_live.latitude BETWEEN '.$minlat.' AND '.$maxlat.' AND marine_live.longitude BETWEEN '.$minlong.' AND '.$maxlong.' GROUP BY marine_live.fammarine_id ORDER BY date DESC'.$filter_query;
		} else {
			$query  = "SELECT marine_live.* FROM marine_live INNER JOIN (SELECT l.fammarine_id, max(l.date) as maxdate FROM marine_live l WHERE NOW() at time zone 'UTC'  - INTERVAL '".$globalLiveInterval." SECONDS' <= l.date GROUP BY l.fammarine_id) s on marine_live.fammarine_id = s.fammarine_id AND marine_live.date = s.maxdate AND marine_live.latitude BETWEEN ".$minlat." AND ".$maxlat." AND marine_live.longitude BETWEEN ".$minlong." AND ".$maxlong." GROUP BY marine_live.fammarine_id ORDEr BY date DESC".$filter_query;
		}
		$spotter_array = $Marine->getDataFromDB($query);
		return $spotter_array;
	}

    /**
     * Gets all the spotter information based on the latest data entry and coord
     *
     * @param $coord
     * @param array $filter
     * @return array the spotter information
     */
	public function getMinLiveMarineDatabyCoord($coord, $filter = array())
	{
		global $globalDBdriver, $globalLiveInterval, $globalArchive;
		$Marine = new Marine($this->db);
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
			$query  = 'SELECT marine_live.ident, marine_live.fammarine_id,marine_live.type, marine_live.latitude, marine_live.longitude, marine_live.heading, marine_live.ground_speed, marine_live.date, marine_live.format_source 
			FROM marine_live'.$filter_query.' DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.$globalLiveInterval." SECOND) <= marine_live.date AND marine_live.latitude <> '0' AND marine_live.longitude <> '0' AND marine_live.latitude BETWEEN ".$minlat.' AND '.$maxlat.' AND marine_live.longitude BETWEEN '.$minlong.' AND '.$maxlong."
			ORDER BY marine_live.fammarine_id, marine_live.date";
		} else {
			$query  = "SELECT marine_live.ident, marine_live.fammarine_id, marine_live.type,marine_live.latitude, marine_live.longitude, marine_live.heading, marine_live.ground_speed, marine_live.date, marine_live.format_source 
			FROM marine_live".$filter_query." CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$globalLiveInterval." SECONDS' <= marine_live.date AND marine_live.latitude <> '0' AND marine_live.longitude <> '0' AND marine_live.latitude BETWEEN ".$minlat." AND ".$maxlat." AND marine_live.longitude BETWEEN ".$minlong." AND ".$maxlong."
			ORDER BY marine_live.fammarine_id, marine_live.date";
		}
		*/
		if ($globalDBdriver == 'mysql') {
			if (isset($globalArchive) && $globalArchive === TRUE) {
				$query  = 'SELECT marine_live.ident, marine_live.fammarine_id,marine_live.type, marine_live.latitude, marine_live.longitude, marine_live.heading, marine_live.ground_speed, marine_live.date, marine_live.format_source, marine_live.captain_name, marine_live.race_id, marine_live.race_rank, marine_live.race_name 
				    FROM marine_live 
				    '.$filter_query.' DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.$globalLiveInterval.' SECOND) <= marine_live.date 
				    AND marine_live.latitude BETWEEN '.$minlat.' AND '.$maxlat.' AND marine_live.longitude BETWEEN '.$minlong.' AND '.$maxlong.'
				    AND marine_live.latitude <> 0 AND marine_live.longitude <> 0 ORDER BY race_rank,date DESC';
			} else {
				$query  = 'SELECT marine_live.ident, marine_live.fammarine_id,marine_live.type, marine_live.latitude, marine_live.longitude, marine_live.heading, marine_live.ground_speed, marine_live.date, marine_live.format_source, marine_live.captain_name, marine_live.race_id, marine_live.race_rank, marine_live.race_name 
				    FROM marine_live 
				    INNER JOIN (SELECT l.fammarine_id, max(l.date) as maxdate 
				    FROM marine_live l 
				    WHERE DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.$globalLiveInterval.' SECOND) <= l.date 
				    AND l.latitude BETWEEN '.$minlat.' AND '.$maxlat.' AND l.longitude BETWEEN '.$minlong.' AND '.$maxlong.'
				    GROUP BY l.fammarine_id
				    ) s on marine_live.fammarine_id = s.fammarine_id 
				    AND marine_live.date = s.maxdate'.$filter_query.' marine_live.latitude <> 0 AND marine_live.longitude <> 0 ORDER BY race_rank, date DESC';
			}
		} else {
			if (isset($globalArchive) && $globalArchive === TRUE) {
				$query  = "SELECT marine_live.ident, marine_live.fammarine_id,marine_live.type, marine_live.latitude, marine_live.longitude, marine_live.heading, marine_live.ground_speed, marine_live.date, marine_live.format_source, marine_live.captain_name, marine_live.race_id, marine_live.race_rank, marine_live.race_name 
				    FROM marine_live 
				    ".$filter_query." CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$globalLiveInterval." SECONDS' <= marine_live.date 
				    AND marine_live.latitude BETWEEN ".$minlat." AND ".$maxlat." 
				    AND marine_live.longitude BETWEEN ".$minlong." AND ".$maxlong." 
				    AND marine_live.latitude <> '0' AND marine_live.longitude <> '0' ORDER BY race_rank, date DESC";
			} else {
				$query  = "SELECT marine_live.ident, marine_live.fammarine_id,marine_live.type, marine_live.latitude, marine_live.longitude, marine_live.heading, marine_live.ground_speed, marine_live.date, marine_live.format_source, marine_live.captain_name, marine_live.race_id, marine_live.race_rank, marine_live.race_name 
				    FROM marine_live 
				    INNER JOIN (SELECT l.fammarine_id, max(l.date) as maxdate 
				    FROM marine_live l 
				    WHERE CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$globalLiveInterval." SECONDS' <= l.date 
				    AND l.latitude BETWEEN ".$minlat." AND ".$maxlat." 
				    AND l.longitude BETWEEN ".$minlong." AND ".$maxlong." 
				    GROUP BY l.fammarine_id
				    ) s on marine_live.fammarine_id = s.fammarine_id 
				    AND marine_live.date = s.maxdate".$filter_query." marine_live.latitude <> '0' AND marine_live.longitude <> '0' ORDER BY race_rank,date DESC";
			}
		}
		$spotter_array = $Marine->getDataFromDB($query);
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
	public function getLatestMarineForLayar($lat, $lng, $radius, $interval)
	{
		$Marine = new Marine($this->db);
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
				//$additional_query = ' AND DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 MINUTE) <= marine_live.date ';
				return array();
			} else {
				if ($interval == '1m')
				{
					$additional_query = ' AND DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 MINUTE) <= marine_live.date ';
				} else if ($interval == '15m'){
					$additional_query = ' AND DATE_SUB(UTC_TIMESTAMP(),INTERVAL 15 MINUTE) <= marine_live.date ';
				}
			}
		} else {
			$additional_query = ' AND DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 MINUTE) <= marine_live.date ';
		}

		$query  = "SELECT marine_live.*, ( 6371 * acos( cos( radians(:lat) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(:lng) ) + sin( radians(:lat) ) * sin( radians( latitude ) ) ) ) AS distance FROM marine_live 
                   WHERE marine_live.latitude <> '' 
                                   AND marine_live.longitude <> '' 
                   ".$additional_query."
                   HAVING distance < :radius  
                                   ORDER BY distance";

		$spotter_array = $Marine->getDataFromDB($query, array(':lat' => $lat, ':lng' => $lng,':radius' => $radius));

		return $spotter_array;
	}


    /**
     * Gets all the spotter information based on a particular callsign
     *
     * @param $ident
     * @return array the spotter information
     */
	public function getLastLiveMarineDataByIdent($ident)
	{
		$Marine = new Marine($this->db);
		date_default_timezone_set('UTC');

		$ident = filter_var($ident, FILTER_SANITIZE_STRING);
                $query  = 'SELECT marine_live.* FROM marine_live INNER JOIN (SELECT l.fammarine_id, max(l.date) as maxdate FROM marine_live l WHERE l.ident = :ident GROUP BY l.fammarine_id) s on marine_live.fammarine_id = s.fammarine_id AND marine_live.date = s.maxdate ORDER BY marine_live.date DESC';

		$spotter_array = $Marine->getDataFromDB($query,array(':ident' => $ident),'',true);

		return $spotter_array;
	}

    /**
     * Gets all the spotter information based on a particular callsign
     *
     * @param $ident
     * @param $date
     * @return array the spotter information
     */
	public function getDateLiveMarineDataByIdent($ident,$date)
	{
		$Marine = new Marine($this->db);
		date_default_timezone_set('UTC');
		$ident = filter_var($ident, FILTER_SANITIZE_STRING);
		$query  = 'SELECT marine_live.* FROM marine_live INNER JOIN (SELECT l.fammarine_id, max(l.date) as maxdate FROM marine_live l WHERE l.ident = :ident AND l.date <= :date GROUP BY l.fammarine_id) s on marine_live.fammarine_id = s.fammarine_id AND marine_live.date = s.maxdate ORDER BY marine_live.date DESC';
		$date = date('c',$date);
		$spotter_array = $Marine->getDataFromDB($query,array(':ident' => $ident,':date' => $date));
		return $spotter_array;
	}

    /**
     * Gets all the spotter information based on a particular MMSI
     *
     * @param $mmsi
     * @param $date
     * @return array the spotter information
     */
	public function getDateLiveMarineDataByMMSI($mmsi,$date)
	{
		$Marine = new Marine($this->db);
		date_default_timezone_set('UTC');
		$mmsi = filter_var($mmsi, FILTER_SANITIZE_NUMBER_INT);
		$query  = 'SELECT marine_live.* FROM marine_live INNER JOIN (SELECT l.fammarine_id, max(l.date) as maxdate FROM marine_live l WHERE l.mmsi = :mmsi AND l.date <= :date GROUP BY l.fammarine_id) s on marine_live.fammarine_id = s.fammarine_id AND marine_live.date = s.maxdate ORDER BY marine_live.date DESC';
		$date = date('c',$date);
		$spotter_array = $Marine->getDataFromDB($query,array(':mmsi' => $mmsi,':date' => $date));
		return $spotter_array;
	}

    /**
     * Gets last spotter information based on a particular callsign
     *
     * @param $id
     * @return array the spotter information
     */
	public function getLastLiveMarineDataById($id)
	{
		$Marine = new Marine($this->db);
		date_default_timezone_set('UTC');

		$id = filter_var($id, FILTER_SANITIZE_STRING);
                $query  = 'SELECT marine_live.* FROM marine_live INNER JOIN (SELECT l.fammarine_id, max(l.date) as maxdate FROM marine_live l WHERE l.fammarine_id = :id GROUP BY l.fammarine_id) s on marine_live.fammarine_id = s.fammarine_id AND marine_live.date = s.maxdate ORDER BY marine_live.date DESC';

		$spotter_array = $Marine->getDataFromDB($query,array(':id' => $id),'',true);

		return $spotter_array;
	}

    /**
     * Gets last spotter information based on a particular callsign
     *
     * @param $id
     * @param $date
     * @return array the spotter information
     */
	public function getDateLiveMarineDataById($id,$date)
	{
		$Marine = new Marine($this->db);
		date_default_timezone_set('UTC');

		$id = filter_var($id, FILTER_SANITIZE_STRING);
                $query  = 'SELECT marine_live.* FROM marine_live INNER JOIN (SELECT l.fammarine_id, max(l.date) as maxdate FROM marine_live l WHERE l.fammarine_id = :id AND l.date <= :date GROUP BY l.fammarine_id) s on marine_live.fammarine_id = s.fammarine_id AND marine_live.date = s.maxdate ORDER BY marine_live.date DESC';
                $date = date('c',$date);
		$spotter_array = $Marine->getDataFromDB($query,array(':id' => $id,':date' => $date),'',true);

		return $spotter_array;
	}


    /**
     * Gets all the spotter information based on a particular id
     *
     * @param $id
     * @param bool $liveinterval
     * @return array the spotter information
     */
	public function getAllLiveMarineDataById($id,$liveinterval = false)
	{
		global $globalDBdriver, $globalLiveInterval;
		date_default_timezone_set('UTC');
		$id = filter_var($id, FILTER_SANITIZE_STRING);
		//$query  = self::$global_query.' WHERE marine_live.fammarine_id = :id ORDER BY date';
		if ($globalDBdriver == 'mysql') {
			$query = 'SELECT marine_live.* FROM marine_live WHERE marine_live.fammarine_id = :id';
			if ($liveinterval) $query .= ' AND DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.$globalLiveInterval.' SECOND) <= date';
			$query .= ' ORDER BY date';
		} else {
			$query = 'SELECT marine_live.* FROM marine_live WHERE marine_live.fammarine_id = :id';
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
	public function getAllLiveMarineDataByIdent($ident)
	{
		date_default_timezone_set('UTC');
		$ident = filter_var($ident, FILTER_SANITIZE_STRING);
		$query  = self::$global_query.' WHERE marine_live.ident = :ident';
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
	public function deleteLiveMarineData()
	{
		global $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			//$query  = "DELETE FROM marine_live WHERE DATE_SUB(UTC_TIMESTAMP(),INTERVAL 30 MINUTE) >= marine_live.date";
			$query  = 'DELETE FROM marine_live WHERE DATE_SUB(UTC_TIMESTAMP(),INTERVAL 12 HOUR) >= marine_live.date';
            		//$query  = "DELETE FROM marine_live WHERE marine_live.id IN (SELECT marine_live.id FROM marine_live INNER JOIN (SELECT l.fammarine_id, max(l.date) as maxdate FROM marine_live l GROUP BY l.fammarine_id) s on marine_live.fammarine_id = s.fammarine_id AND marine_live.date = s.maxdate AND DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 HOUR) >= marine_live.date)";
		} else {
			$query  = "DELETE FROM marine_live WHERE NOW() AT TIME ZONE 'UTC' - INTERVAL '12 HOURS' >= marine_live.date";
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
	public function deleteLiveMarineDataNotUpdated()
	{
		global $globalDBdriver, $globalDebug;
		if ($globalDBdriver == 'mysql') {
			//$query = 'SELECT fammarine_id FROM marine_live WHERE DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 HOUR) >= marine_live.date AND marine_live.fammarine_id NOT IN (SELECT fammarine_id FROM marine_live WHERE DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 HOUR) < marine_live.date) LIMIT 800 OFFSET 0';
    			$query = "SELECT marine_live.fammarine_id FROM marine_live INNER JOIN (SELECT fammarine_id,MAX(date) as max_date FROM marine_live GROUP BY fammarine_id) s ON s.fammarine_id = marine_live.fammarine_id AND DATE_SUB(UTC_TIMESTAMP(), INTERVAL 2 HOUR) >= s.max_date LIMIT 1200 OFFSET 0";
    			try {
				
				$sth = $this->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error";
			}
			$query_delete = 'DELETE FROM marine_live WHERE fammarine_id IN (';
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
                                	$query_delete = 'DELETE FROM marine_live WHERE fammarine_id IN (';
                                	$j = 0;
				}
				$query_delete .= "'".$row['fammarine_id']."',";
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
			//$query = "SELECT fammarine_id FROM marine_live WHERE NOW() AT TIME ZONE 'UTC' - INTERVAL '9 HOURS' >= marine_live.date AND marine_live.fammarine_id NOT IN (SELECT fammarine_id FROM marine_live WHERE NOW() AT TIME ZONE 'UTC' - INTERVAL '9 HOURS' < marine_live.date) LIMIT 800 OFFSET 0";
    			//$query = "SELECT marine_live.fammarine_id FROM marine_live INNER JOIN (SELECT fammarine_id,MAX(date) as max_date FROM marine_live GROUP BY fammarine_id) s ON s.fammarine_id = marine_live.fammarine_id AND NOW() AT TIME ZONE 'UTC' - INTERVAL '2 HOURS' >= s.max_date LIMIT 800 OFFSET 0";
    			$query = "DELETE FROM marine_live WHERE fammarine_id IN (SELECT marine_live.fammarine_id FROM marine_live INNER JOIN (SELECT fammarine_id,MAX(date) as max_date FROM marine_live GROUP BY fammarine_id) s ON s.fammarine_id = marine_live.fammarine_id AND NOW() AT TIME ZONE 'UTC' - INTERVAL '2 HOURS' >= s.max_date LIMIT 800 OFFSET 0)";
    			try {
				
				$sth = $this->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error";
			}
/*			$query_delete = "DELETE FROM marine_live WHERE fammarine_id IN (";
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
                                	$query_delete = "DELETE FROM marine_live WHERE fammarine_id IN (";
                                	$j = 0;
				}
				$query_delete .= "'".$row['fammarine_id']."',";
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
		return 'error';
	}

    /**
     * Deletes all info in the table for an ident
     *
     * @param $ident
     * @return String success or false
     */
	public function deleteLiveMarineDataByIdent($ident)
	{
		$ident = filter_var($ident, FILTER_SANITIZE_STRING);
		$query  = 'DELETE FROM marine_live WHERE ident = :ident';
        
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
	public function deleteLiveMarineDataById($id)
	{
		$id = filter_var($id, FILTER_SANITIZE_STRING);
		$query  = 'DELETE FROM marine_live WHERE fammarine_id = :id';
        
    		try {
			
			$sth = $this->db->prepare($query);
			$sth->execute(array(':id' => $id));
		} catch(PDOException $e) {
			return "error";
		}

		return "success";
	}


	/**
	* Gets the marine races
	*
	* @return array all races
	*
	*/
	public function getAllRaces()
	{
		$query  = 'SELECT DISTINCT marine_live.race_id, marine_live.race_name FROM marine_live ORDER BY marine_live.race_name';
		$sth = $this->db->prepare($query);
		$sth->execute();
		return $sth->fetchAll(PDO::FETCH_ASSOC);
	}

    /**
     * Gets the aircraft ident within the last hour
     *
     * @param $ident
     * @return String the ident
     */
	public function getIdentFromLastHour($ident)
	{
		global $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			$query  = 'SELECT marine_live.ident FROM marine_live 
				WHERE marine_live.ident = :ident 
				AND marine_live.date >= DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 HOUR) 
				AND marine_live.date < UTC_TIMESTAMP()';
			$query_data = array(':ident' => $ident);
		} else {
			$query  = "SELECT marine_live.ident FROM marine_live 
				WHERE marine_live.ident = :ident 
				AND marine_live.date >= now() AT TIME ZONE 'UTC' - INTERVAL '1 HOURS'
				AND marine_live.date < now() AT TIME ZONE 'UTC'";
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
		global $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			$query  = 'SELECT marine_live.ident, marine_live.fammarine_id FROM marine_live 
				WHERE marine_live.ident = :ident 
				AND marine_live.date >= DATE_SUB(UTC_TIMESTAMP(),INTERVAL 30 MINUTE)'; 
//				AND marine_live.date < UTC_TIMESTAMP()";
			$query_data = array(':ident' => $ident);
		} else {
			$query  = "SELECT marine_live.ident, marine_live.fammarine_id FROM marine_live 
				WHERE marine_live.ident = :ident 
				AND marine_live.date >= now() AT TIME ZONE 'UTC' - INTERVAL '30 MINUTES'";
//				AND marine_live.date < now() AT TIME ZONE 'UTC'";
			$query_data = array(':ident' => $ident);
		}
		
		$sth = $this->db->prepare($query);
		$sth->execute($query_data);
		$ident_result='';
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$ident_result = $row['fammarine_id'];
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
		global $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			$query  = 'SELECT marine_live.ident, marine_live.fammarine_id FROM marine_live 
				WHERE marine_live.fammarine_id = :id 
				AND marine_live.date >= DATE_SUB(UTC_TIMESTAMP(),INTERVAL 10 HOUR)'; 
//				AND marine_live.date < UTC_TIMESTAMP()";
			$query_data = array(':id' => $id);
		} else {
			$query  = "SELECT marine_live.ident, marine_live.fammarine_id FROM marine_live 
				WHERE marine_live.fammarine_id = :id 
				AND marine_live.date >= now() AT TIME ZONE 'UTC' - INTERVAL '10 HOUR'";
//				AND marine_live.date < now() AT TIME ZONE 'UTC'";
			$query_data = array(':id' => $id);
		}
		
		$sth = $this->db->prepare($query);
		$sth->execute($query_data);
		$ident_result='';
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$ident_result = $row['fammarine_id'];
		}
		return $ident_result;
        }

    /**
     * Check recent aircraft by mmsi
     *
     * @param $mmsi
     * @return String the ident
     */
	public function checkMMSIRecent($mmsi)
	{
		global $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			$query  = 'SELECT marine_live.fammarine_id FROM marine_live 
				WHERE marine_live.mmsi = :mmsi 
				AND marine_live.date >= DATE_SUB(UTC_TIMESTAMP(),INTERVAL 10 HOUR)'; 
//				AND marine_live.date < UTC_TIMESTAMP()";
			$query_data = array(':mmsi' => $mmsi);
		} else {
			$query  = "SELECT marine_live.fammarine_id FROM marine_live 
				WHERE marine_live.mmsi = :mmsi 
				AND marine_live.date >= now() AT TIME ZONE 'UTC' - INTERVAL '10 HOUR'";
//				AND marine_live.date < now() AT TIME ZONE 'UTC'";
			$query_data = array(':mmsi' => $mmsi);
		}
		
		$sth = $this->db->prepare($query);
		$sth->execute($query_data);
		$ident_result='';
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$ident_result = $row['fammarine_id'];
		}
		return $ident_result;
        }

    /**
     * Adds a new spotter data
     *
     * @param String $fammarine_id the ID from flightaware
     * @param String $ident the flight ident
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
     * @return String success or false
     */
	public function addLiveMarineData($fammarine_id = '', $ident = '', $latitude = '', $longitude = '', $heading = '', $groundspeed = '', $date = '', $putinarchive = false, $mmsi = '',$type = '',$typeid = '',$imo = '', $callsign = '',$arrival_code = '',$arrival_date = '',$status = '',$statusid = '',$noarchive = false,$format_source = '', $source_name = '', $over_country = '',$captain_id = '',$captain_name = '',$race_id = '', $race_name = '', $distance = '', $race_rank = '', $race_time = '')
	{
		global $globalArchive, $globalDebug;
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

        
		$fammarine_id = filter_var($fammarine_id,FILTER_SANITIZE_STRING);
		$ident = filter_var($ident,FILTER_SANITIZE_STRING);
		$latitude = filter_var($latitude,FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
		$longitude = filter_var($longitude,FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
		$distance = filter_var($distance,FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
		$heading = filter_var($heading,FILTER_SANITIZE_NUMBER_INT);
		$groundspeed = filter_var($groundspeed,FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
		$format_source = filter_var($format_source,FILTER_SANITIZE_STRING);
		$source_name = filter_var($source_name,FILTER_SANITIZE_STRING);
		$over_country = filter_var($over_country,FILTER_SANITIZE_STRING);
		$type = filter_var($type,FILTER_SANITIZE_STRING);
		$typeid = filter_var($typeid,FILTER_SANITIZE_NUMBER_INT);
		$mmsi = filter_var($mmsi,FILTER_SANITIZE_NUMBER_INT);
		$status = filter_var($status,FILTER_SANITIZE_STRING);
		$statusid = filter_var($statusid,FILTER_SANITIZE_NUMBER_INT);
		$imo = filter_var($imo,FILTER_SANITIZE_STRING);
		$callsign = filter_var($callsign,FILTER_SANITIZE_STRING);
		$arrival_code = filter_var($arrival_code,FILTER_SANITIZE_STRING);
		$arrival_date = filter_var($arrival_date,FILTER_SANITIZE_STRING);
		$captain_id = filter_var($captain_id,FILTER_SANITIZE_STRING);
		$captain_name = filter_var($captain_name,FILTER_SANITIZE_STRING);
		$race_id = filter_var($race_id,FILTER_SANITIZE_STRING);
		$race_name = filter_var($race_name,FILTER_SANITIZE_STRING);
		$race_rank = filter_var($race_rank,FILTER_SANITIZE_NUMBER_INT);
		if ($race_rank == '') $race_rank = NULL;
		$race_time = filter_var($race_time,FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
		if ($race_time == '') $race_time = NULL;
		if ($typeid == '') $typeid = NULL;
		if ($statusid == '') $statusid = NULL;
		if ($distance == '') $distance = NULL;

            	//if ($groundspeed == '' || $Common->isInteger($groundspeed) === false ) $groundspeed = 0;
            	if ($heading == '' || $Common->isInteger($heading) === false ) $heading = 0;
            	if ($arrival_date == '') $arrival_date = NULL;
            	$query = '';
		if ($globalArchive) {
			if ($globalDebug) echo '-- Delete previous data -- ';
			$query .= 'DELETE FROM marine_live WHERE fammarine_id = :fammarine_id;';
		}
		$query .= 'INSERT INTO marine_live (fammarine_id, ident, latitude, longitude, heading, ground_speed, date, format_source, source_name, over_country, mmsi, type,type_id,status,status_id,imo,arrival_port_name,arrival_port_date,captain_id,captain_name,race_id,race_name,distance,race_rank,race_time) 
		    VALUES (:fammarine_id,:ident,:latitude,:longitude,:heading,:groundspeed,:date,:format_source, :source_name, :over_country,:mmsi,:type,:typeid,:status,:statusid,:imo,:arrival_port_name,:arrival_port_date,:captain_id,:captain_name,:race_id,:race_name,:distance,:race_rank,:race_time)';
		$query_values = array(':fammarine_id' => $fammarine_id,':ident' => $ident,':latitude' => $latitude,':longitude' => $longitude,':heading' => $heading,':groundspeed' => $groundspeed,':date' => $date, ':format_source' => $format_source, ':source_name' => $source_name, ':over_country' => $over_country,':mmsi' => $mmsi,':type' => $type,':typeid' => $typeid,':status' => $status,':statusid' => $statusid,':imo' => $imo,':arrival_port_name' => $arrival_code,':arrival_port_date' => $arrival_date,':captain_id' => $captain_id,':captain_name' => $captain_name,':race_id' => $race_id,':race_name' => $race_name,':distance' => $distance,':race_time' => $race_time,':race_rank' => $race_rank);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
			$sth->closeCursor();
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
		
		if (isset($globalArchive) && $globalArchive && $putinarchive && $noarchive !== true) {
			if ($globalDebug) echo '(Add to Marine archive : ';
			$MarineArchive = new MarineArchive($this->db);
			$result =  $MarineArchive->addMarineArchiveData($fammarine_id, $ident, $latitude, $longitude, $heading, $groundspeed, $date, $putinarchive, $mmsi,$type,$typeid,$imo, $callsign,$arrival_code,$arrival_date,$status,$statusid,$noarchive,$format_source, $source_name, $over_country,$captain_id,$captain_name,$race_id,$race_name,$distance,$race_rank,$race_time);
			if ($globalDebug) echo $result.')';
		}
		return "success";
	}

	public function getOrderBy()
	{
		$orderby = array("aircraft_asc" => array("key" => "aircraft_asc", "value" => "Aircraft Type - ASC", "sql" => "ORDER BY marine_live.aircraft_icao ASC"), "aircraft_desc" => array("key" => "aircraft_desc", "value" => "Aircraft Type - DESC", "sql" => "ORDER BY marine_live.aircraft_icao DESC"),"manufacturer_asc" => array("key" => "manufacturer_asc", "value" => "Aircraft Manufacturer - ASC", "sql" => "ORDER BY marine_live.aircraft_manufacturer ASC"), "manufacturer_desc" => array("key" => "manufacturer_desc", "value" => "Aircraft Manufacturer - DESC", "sql" => "ORDER BY marine_live.aircraft_manufacturer DESC"),"airline_name_asc" => array("key" => "airline_name_asc", "value" => "Airline Name - ASC", "sql" => "ORDER BY marine_live.airline_name ASC"), "airline_name_desc" => array("key" => "airline_name_desc", "value" => "Airline Name - DESC", "sql" => "ORDER BY marine_live.airline_name DESC"), "ident_asc" => array("key" => "ident_asc", "value" => "Ident - ASC", "sql" => "ORDER BY marine_live.ident ASC"), "ident_desc" => array("key" => "ident_desc", "value" => "Ident - DESC", "sql" => "ORDER BY marine_live.ident DESC"), "airport_departure_asc" => array("key" => "airport_departure_asc", "value" => "Departure Airport - ASC", "sql" => "ORDER BY marine_live.departure_airport_city ASC"), "airport_departure_desc" => array("key" => "airport_departure_desc", "value" => "Departure Airport - DESC", "sql" => "ORDER BY marine_live.departure_airport_city DESC"), "airport_arrival_asc" => array("key" => "airport_arrival_asc", "value" => "Arrival Airport - ASC", "sql" => "ORDER BY marine_live.arrival_airport_city ASC"), "airport_arrival_desc" => array("key" => "airport_arrival_desc", "value" => "Arrival Airport - DESC", "sql" => "ORDER BY marine_live.arrival_airport_city DESC"), "date_asc" => array("key" => "date_asc", "value" => "Date - ASC", "sql" => "ORDER BY marine_live.date ASC"), "date_desc" => array("key" => "date_desc", "value" => "Date - DESC", "sql" => "ORDER BY marine_live.date DESC"));
		return $orderby;
	}

}


?>
