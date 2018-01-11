<?php
/**
 * This class is part of FlightAirmap. It's used for satellite data
 *
 * Copyright (c) Ycarus (Yannick Chabanois) <support@flightairmap.com>
 * Licensed under AGPL license.
 * For more information see: https://www.flightairmap.com/
*/
require_once(dirname(__FILE__).'/class.Connection.php');
require_once(dirname(__FILE__).'/libs/Predict/Predict.php');
require_once(dirname(__FILE__).'/libs/Predict/Predict/Sat.php');
require_once(dirname(__FILE__).'/libs/Predict/Predict/QTH.php');
require_once(dirname(__FILE__).'/libs/Predict/Predict/Time.php');
require_once(dirname(__FILE__).'/libs/Predict/Predict/TLE.php');

class Satellite {
    /** @var $db PDOStatement  */
	public $db;

	public function __construct($dbc = null) {
		$Connection = new Connection($dbc);
		$this->db = $Connection->db();
		if ($this->db === null) die('Error: No DB connection.');
	}

	public function get_tle($name) {
		$query = 'SELECT tle_name, tle_tle1, tle_tle2, tle_type FROM tle WHERE tle_name = :name LIMIT 1';
		try {
			$sth = $this->db->prepare($query);
			$sth->execute(array(':name' => $name));
		} catch(PDOException $e) {
			echo $e->getMessage();
		}
		$result = $sth->fetchAll(PDO::FETCH_ASSOC);
		if (isset($result[0])) return $result[0];
		else return array();
	}
	public function get_tle_types() {
		$query = 'SELECT DISTINCT tle_type FROM tle ORDER BY tle_type';
		try {
			$sth = $this->db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			echo $e->getMessage();
		}
		$result = $sth->fetchAll(PDO::FETCH_ASSOC);
		if (isset($result[0])) return $result;
		else return array();
	}
	public function get_tle_names() {
		$query = 'SELECT DISTINCT tle_name, tle_type FROM tle';
		try {
			$sth = $this->db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			echo $e->getMessage();
		}
		$result = $sth->fetchAll(PDO::FETCH_ASSOC);
		if (isset($result[0])) return $result;
		else return array();
	}
	public function get_tle_names_type($type) {
		$query = 'SELECT tle_name, tle_type FROM tle WHERE tle_type = :type ORDER BY tle_name';
		try {
			$sth = $this->db->prepare($query);
			$sth->execute(array(':type' => $type));
		} catch(PDOException $e) {
			echo $e->getMessage();
		}
		$result = $sth->fetchAll(PDO::FETCH_ASSOC);
		if (isset($result[0])) return $result;
		else return array();
	}
	
	public function position_all($timestamp_begin = '',$timestamp_end = '',$second = 10) {
		$all_sat = $this->get_tle_names();
		$result = array();
		foreach ($all_sat as $sat) {
			$position = $this->position($sat['tle_name'],$timestamp_begin,$timestamp_end,$second);
			$result = array_merge($position,$result);
		}
		return $result;
	}

	public function position_all_type($type,$timestamp_begin = '',$timestamp_end = '',$second = 10) {
		$all_sat = $this->get_tle_names_type($type);
		$result = array();
		foreach ($all_sat as $sat) {
			$position = $this->position($sat['tle_name'],$timestamp_begin,$timestamp_end,$second);
			if (isset($position[0])) $result = array_merge($position,$result);
			else $result[] = $position;
		}
		return $result;
	}

	public function position($name,$timestamp_begin = '',$timestamp_end = '',$second = 10) {
		$qth = new Predict_QTH();
		$qth->lat = floatval(37.790252);
		$qth->lon = floatval(-122.419968);
	
		$tle_file = $this->get_tle($name);
		$type = $tle_file['tle_type'];
		$tle = new Predict_TLE($tle_file['tle_name'],$tle_file['tle_tle1'],$tle_file['tle_tle2']);
		$sat = new Predict_Sat($tle);
		$predict = new Predict();
		//if ($timestamp == '') $now = Predict_Time::get_current_daynum();
		if ($timestamp_begin == '') $timestamp_begin = time();
		if ($timestamp_end == '') {
			$now = Predict_Time::unix2daynum($timestamp_begin);
			$predict->predict_calc($sat,$qth,$now);
			return array('name' => $name, 'latitude' => $sat->ssplat,'longitude' => $sat->ssplon, 'altitude' => $sat->alt,'speed' => $sat->velo*60*60,'timestamp' => $timestamp_begin,'type' => $type);
		} else {
			$result = array();
			for ($timestamp = $timestamp_begin; $timestamp <= $timestamp_end; $timestamp=$timestamp+$second) {
				$now = Predict_Time::unix2daynum($timestamp);
				$predict->predict_calc($sat,$qth,$now);
				$result[] = array('name' => $name,'latitude' => $sat->ssplat,'longitude' => $sat->ssplon, 'altitude' => $sat->alt,'speed' => $sat->velo*60*60,'timestamp' => $timestamp,'type' => $type);
			}
			return $result;
		}
	}

	public function get_info($name) {
		$query = 'SELECT * FROM satellite WHERE LOWER(name) LIKE :name OR LOWER(name_alternate) LIKE :name LIMIT 1';
		try {
			$sth = $this->db->prepare($query);
			$sth->execute(array(':name' => $name.'%'));
		} catch(PDOException $e) {
			echo $e->getMessage();
		}
		$result = $sth->fetchAll(PDO::FETCH_ASSOC);
		if (isset($result[0])) return $result[0];
		else return array();
	}

    /**
     * Gets all launch site
     *
     * @param bool $limit
     * @param array $filters
     * @return array the launch site list
     */
	public function countAllLaunchSite($limit = true, $filters = array())
	{
		//$filter_query = $this->getFilter($filters,true,true);
		$filter_query = ' WHERE';
		$query  = "SELECT DISTINCT satellite.launch_site AS launch_site, COUNT(satellite.launch_site) AS launch_site_count
		    FROM satellite".$filter_query." satellite.launch_site <> '' AND satellite.launch_site IS NOT NULL";
		$query_values = array();
		$query .= " GROUP BY satellite.launch_site ORDER BY launch_site_count DESC";
		if ($limit) $query .= " LIMIT 10 OFFSET 0";
		$sth = $this->db->prepare($query);
		$sth->execute($query_values);
		$launch_site_array = array();
		$temp_array = array();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['launch_site'] = $row['launch_site'];
			$temp_array['launch_site_count'] = $row['launch_site_count'];
			$launch_site_array[] = $temp_array;
		}
		return $launch_site_array;
	}

	/**
	* Gets all owners
	*
	* @return array the owners list
	*
	*/
	public function countAllOwners($limit = true, $filters = array())
	{
		//$filter_query = $this->getFilter($filters,true,true);
		$filter_query = ' WHERE';
		$query  = "SELECT DISTINCT satellite.owner AS owner_name, COUNT(satellite.owner) AS owner_count
		    FROM satellite".$filter_query." satellite.owner <> '' AND satellite.owner IS NOT NULL";
		$query_values = array();
		$query .= " GROUP BY satellite.owner ORDER BY owner_count DESC";
		if ($limit) $query .= " LIMIT 10 OFFSET 0";
		$sth = $this->db->prepare($query);
		$sth->execute($query_values);
		$owner_array = array();
		$temp_array = array();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['owner_name'] = $row['owner_name'];
			$temp_array['owner_count'] = $row['owner_count'];
			$owner_array[] = $temp_array;
		}
		return $owner_array;
	}

    /**
     * Gets all countries owners
     *
     * @param bool $limit
     * @param array $filters
     * @return array the countries list
     */
	public function countAllCountriesOwners($limit = true, $filters = array())
	{
		global $globalDBdriver;
		//$filter_query = $this->getFilter($filters,true,true);
		$filter_query = ' WHERE';
		$query  = "SELECT DISTINCT satellite.country_owner AS country_name, COUNT(satellite.country_owner) AS country_count
		    FROM satellite".$filter_query." satellite.country_owner <> '' AND satellite.country_owner IS NOT NULL";
		$query_values = array();
		$query .= " GROUP BY satellite.country_owner ORDER BY country_count DESC";
		if ($limit) $query .= " LIMIT 10 OFFSET 0";
		$sth = $this->db->prepare($query);
		$sth->execute($query_values);
		$owner_array = array();
		$temp_array = array();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['country_name'] = $row['country_name'];
			$temp_array['country_count'] = $row['country_count'];
			$owner_array[] = $temp_array;
		}
		return $owner_array;
	}

    /**
     * Counts all launch dates during the last year
     *
     * @param array $filters
     * @param string $sincedate
     * @return array the launch date list
     */
	public function countAllMonthsLastYear($filters = array(), $sincedate = '')
	{
		global $globalTimezone, $globalDBdriver;
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime();
			$offset = $datetime->format('P');
		} else $offset = '+00:00';
		//$filter_query = $this->getFilter($filters,true,true);
		$filter_query = ' WHERE';
		if ($globalDBdriver == 'mysql') {
			$query  = "SELECT MONTH(CONVERT_TZ(satellite.launch_date,'+00:00', :offset)) AS month_name, YEAR(CONVERT_TZ(satellite.launch_date,'+00:00', :offset)) AS year_name, count(*) as date_count
				FROM satellite".$filter_query." satellite.launch_date >= DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 YEAR)";
			if ($sincedate != '') $query .= " AND satellite.launch_date > '".$sincedate."'";
			$query .= " GROUP BY year_name, month_name
				ORDER BY year_name, month_name ASC";
			$query_data = array(':offset' => $offset);
		} else {
			$query  = "SELECT EXTRACT(MONTH FROM satellite.launch_date AT TIME ZONE INTERVAL :offset) AS month_name, EXTRACT(YEAR FROM satellite.launch_date AT TIME ZONE INTERVAL :offset) AS year_name, count(*) as date_count
				FROM satellite".$filter_query." satellite.launch_date >= CURRENT_TIMESTAMP AT TIME ZONE INTERVAL :offset - INTERVAL '1 YEARS'";
			if ($sincedate != '') $query .= " AND satellite.launch_date > '".$sincedate."'";
			$query .= " GROUP BY year_name, month_name
				ORDER BY year_name, month_name ASC";
			$query_data = array(':offset' => $offset);
		}
		$sth = $this->db->prepare($query);
		$sth->execute($query_data);
		$date_array = array();
		$temp_array = array();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['year_name'] = $row['year_name'];
			$temp_array['month_name'] = $row['month_name'];
			$temp_array['date_count'] = $row['date_count'];
			$date_array[] = $temp_array;
		}
		return $date_array;
	}

    /**
     * Counts all dates during the last 10 years
     *
     * @param array $filters
     * @param string $sincedate
     * @return array the date list
     */
	public function countAllYears($filters = array(), $sincedate = '')
	{
		global $globalTimezone, $globalDBdriver;
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime();
			$offset = $datetime->format('P');
		} else $offset = '+00:00';
		//$filter_query = $this->getFilter($filters,true,true);
		$filter_query = ' WHERE';
		if ($globalDBdriver == 'mysql') {
			$query  = "SELECT YEAR(CONVERT_TZ(satellite.launch_date,'+00:00', :offset)) AS year_name, count(*) as date_count
				FROM satellite".$filter_query." satellite.launch_date >= DATE_SUB(UTC_TIMESTAMP(),INTERVAL 10 YEAR)";
			if ($sincedate != '') $query .= " AND satellite.launch_date > '".$sincedate."'";
			$query .= " GROUP BY year_name
				ORDER BY year_name ASC";
			$query_data = array(':offset' => $offset);
		} else {
			$query  = "SELECT EXTRACT(YEAR FROM satellite.launch_date AT TIME ZONE INTERVAL :offset) AS year_name, count(*) as date_count
				FROM satellite".$filter_query." satellite.launch_date >= CURRENT_TIMESTAMP AT TIME ZONE INTERVAL :offset - INTERVAL '10 YEARS'";
			if ($sincedate != '') $query .= " AND satellite.launch_date > '".$sincedate."'";
			$query .= " GROUP BY year_name
				ORDER BY year_name ASC";
			$query_data = array(':offset' => $offset);
		}
		$sth = $this->db->prepare($query);
		$sth->execute($query_data);
		$date_array = array();
		$temp_array = array();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['year_name'] = $row['year_name'];
			$temp_array['date_count'] = $row['date_count'];
			$date_array[] = $temp_array;
		}
		return $date_array;
	}
}
/*
$sat = new Satellite();
print_r($sat->position('ISS (ZARYA)',time(),time()+100));
print_r($sat->get_tle_types());
*/
?>