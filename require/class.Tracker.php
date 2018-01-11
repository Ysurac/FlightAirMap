<?php
/**
 * This class is part of FlightAirmap. It's used to for trackers data
 *
 * Copyright (c) Ycarus (Yannick Chabanois) <support@flightairmap.com>
 * Licensed under AGPL license.
 * For more information see: https://www.flightairmap.com/
*/
require_once(dirname(__FILE__).'/class.Scheduler.php');
require_once(dirname(__FILE__).'/class.ACARS.php');
require_once(dirname(__FILE__).'/class.Image.php');
$global_tracker_query = "SELECT tracker_output.* FROM tracker_output";

class Tracker{
	public $db;
	
	public function __construct($dbc = null) {
		$Connection = new Connection($dbc);
		$this->db = $Connection->db();
		if ($this->db === null) die('Error: No DB connection. (Tracker)');
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
					$filter_query_join .= " INNER JOIN (SELECT famtrackid FROM tracker_output WHERE tracker_output.ident IN ('".implode("','",$flt['idents'])."') AND tracker_output.format_source IN ('".implode("','",$flt['source'])."')) spfi ON spfi.famtrackid = tracker_output.famtrackid";
				} else {
					$filter_query_join .= " INNER JOIN (SELECT famtrackid FROM tracker_output WHERE tracker_output.ident IN ('".implode("','",$flt['idents'])."')) spfi ON spfi.famtrackid = tracker_output.famtrackid";
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
		if (isset($filter['year']) && $filter['year'] != '') {
			if ($globalDBdriver == 'mysql') {
				$filter_query_where .= " AND YEAR(tracker_output.date) = '".$filter['year']."'";
			} else {
				$filter_query_where .= " AND EXTRACT(YEAR FROM tracker_output.date) = '".$filter['year']."'";
			}
		}
		if (isset($filter['month']) && $filter['month'] != '') {
			if ($globalDBdriver == 'mysql') {
				$filter_query_where .= " AND MONTH(tracker_output.date) = '".$filter['month']."'";
			} else {
				$filter_query_where .= " AND EXTRACT(MONTH FROM tracker_output.date) = '".$filter['month']."'";
			}
		}
		if (isset($filter['day']) && $filter['day'] != '') {
			if ($globalDBdriver == 'mysql') {
				$filter_query_where .= " AND DAY(tracker_output.date) = '".$filter['day']."'";
			} else {
				$filter_query_where .= " AND EXTRACT(DAY FROM tracker_output.date) = '".$filter['day']."'";
			}
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
     * Executes the SQL statements to get the tracker information
     *
     * @param String $query the SQL query
     * @param array $params parameter of the query
     * @param String $limitQuery the limit query
     * @param bool $schedules
     * @return array the tracker information
     */
	public function getDataFromDB($query, $params = array(), $limitQuery = '',$schedules = false)
	{
		date_default_timezone_set('UTC');
		if (!is_string($query))
		{
			return array();
		}
		
		if ($limitQuery != "")
		{
			if (!is_string($limitQuery))
			{
				return array();
			}
		}

		try {
			$sth = $this->db->prepare($query.$limitQuery);
			$sth->execute($params);
		} catch (PDOException $e) {
			printf("Invalid query : %s\nWhole query: %s\n",$e->getMessage(), $query.$limitQuery);
			exit();
		}
		
		$num_rows = 0;
		$tracker_array = array();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$num_rows++;
			$temp_array = array();
			if (isset($row['tracker_live_id'])) {
				$temp_array['tracker_id'] = $this->getTrackerIDBasedOnFamTrackID($row['famtrackid']);
			/*
			} elseif (isset($row['tracker_archive_id'])) {
				$temp_array['tracker_id'] = $row['tracker_archive_id'];
			} elseif (isset($row['tracker_archive_output_id'])) {
				$temp_array['tracker_id'] = $row['tracker_archive_output_id'];
			*/} 
			elseif (isset($row['trackerid'])) {
				$temp_array['trackerid'] = $row['trackerid'];
			} else {
				$temp_array['trackerid'] = '';
			}
			if (isset($row['famtrackid'])) $temp_array['famtrackid'] = $row['famtrackid'];
			if (isset($row['type'])) $temp_array['type'] = $row['type'];
			if (isset($row['comment'])) $temp_array['comment'] = $row['comment'];
			$temp_array['ident'] = $row['ident'];
			if (isset($row['latitude'])) $temp_array['latitude'] = $row['latitude'];
			if (isset($row['longitude'])) $temp_array['longitude'] = $row['longitude'];
			if (isset($row['format_source'])) $temp_array['format_source'] = $row['format_source'];
			if (isset($row['altitude'])) $temp_array['altitude'] = $row['altitude'];
			if (isset($row['heading'])) {
				$temp_array['heading'] = $row['heading'];
				$heading_direction = $this->parseDirection($row['heading']);
				if (isset($heading_direction[0]['direction_fullname'])) $temp_array['heading_name'] = $heading_direction[0]['direction_fullname'];
			}
			if (isset($row['ground_speed'])) $temp_array['ground_speed'] = $row['ground_speed'];
			
			if (isset($row['date'])) {
				$dateArray = $this->parseDateString($row['date']);
				if ($dateArray['seconds'] < 10)
				{
					$temp_array['date'] = "a few seconds ago";
				} elseif ($dateArray['seconds'] >= 5 && $dateArray['seconds'] < 30)
				{
					$temp_array['date'] = "half a minute ago";
				} elseif ($dateArray['seconds'] >= 30 && $dateArray['seconds'] < 60)
				{
					$temp_array['date'] = "about a minute ago";
				} elseif ($dateArray['minutes'] < 5)
				{
					$temp_array['date'] = "a few minutes ago";
				} elseif ($dateArray['minutes'] >= 5 && $dateArray['minutes'] < 60)
				{
					$temp_array['date'] = "about ".$dateArray['minutes']." minutes ago";
				} elseif ($dateArray['hours'] < 2)
				{
					$temp_array['date'] = "about an hour ago";
				} elseif ($dateArray['hours'] >= 2 && $dateArray['hours'] < 24)
				{
					$temp_array['date'] = "about ".$dateArray['hours']." hours ago";
				} else {
					$temp_array['date'] = date("M j Y, g:i a",strtotime($row['date']." UTC"));
				}
				$temp_array['date_minutes_past'] = $dateArray['minutes'];
				$temp_array['date_iso_8601'] = date("c",strtotime($row['date']." UTC"));
				$temp_array['date_rfc_2822'] = date("r",strtotime($row['date']." UTC"));
				$temp_array['date_unix'] = strtotime($row['date']." UTC");
				if (isset($row['last_seen']) && $row['last_seen'] != '') {
					if (strtotime($row['last_seen']) > strtotime($row['date'])) {
						$temp_array['duration'] = strtotime($row['last_seen']) - strtotime($row['date']);
						$temp_array['last_seen_date_iso_8601'] = date("c",strtotime($row['last_seen']." UTC"));
						$temp_array['last_seen_date_rfc_2822'] = date("r",strtotime($row['last_seen']." UTC"));
						$temp_array['last_seen_date_unix'] = strtotime($row['last_seen']." UTC");
					}
				}
			}
			
			$fromsource = NULL;
			if (isset($row['source_name']) && $row['source_name'] != '') $temp_array['source_name'] = $row['source_name'];
			if (isset($row['over_country']) && $row['over_country'] != '') $temp_array['over_country'] = $row['over_country'];
			if (isset($row['distance']) && $row['distance'] != '') $temp_array['distance'] = $row['distance'];
			$temp_array['query_number_rows'] = $num_rows;
			$tracker_array[] = $temp_array;
		}
		if ($num_rows == 0) return array();
		$tracker_array[0]['query_number_rows'] = $num_rows;
		return $tracker_array;
	}


    /**
     * Gets all the tracker information based on the latest data entry
     *
     * @param string $limit
     * @param string $sort
     * @param array $filter
     * @return array the tracker information
     */
	public function getLatestTrackerData($limit = '', $sort = '', $filter = array())
	{
		global $global_tracker_query;
		
		date_default_timezone_set('UTC');

		$filter_query = $this->getFilter($filter);
		
		if ($limit != "")
		{
			$limit_array = explode(",", $limit);
			
			$limit_array[0] = filter_var($limit_array[0],FILTER_SANITIZE_NUMBER_INT);
			$limit_array[1] = filter_var($limit_array[1],FILTER_SANITIZE_NUMBER_INT);
			
			if ($limit_array[0] >= 0 && $limit_array[1] >= 0)
			{
				//$limit_query = " LIMIT ".$limit_array[0].",".$limit_array[1];
				$limit_query = " LIMIT ".$limit_array[1]." OFFSET ".$limit_array[0];
			} else $limit_query = "";
		} else $limit_query = "";
		
		if ($sort != "")
		{
			$search_orderby_array = $this->getOrderBy();
			$orderby_query = $search_orderby_array[$sort]['sql'];
		} else {
			$orderby_query = " ORDER BY tracker_output.date DESC";
		}

		$query  = $global_tracker_query.$filter_query." ".$orderby_query;

		$tracker_array = $this->getDataFromDB($query, array(),$limit_query,true);

		return $tracker_array;
	}
    
	/*
	* Gets all the tracker information based on the tracker id
	*
	* @return Array the tracker information
	*
	*/
	public function getTrackerDataByID($id = '')
	{
		global $global_tracker_query;
		
		date_default_timezone_set('UTC');
		if ($id == '') return array();
		$additional_query = "tracker_output.famtrackid = :id";
		$query_values = array(':id' => $id);
		$query  = $global_tracker_query." WHERE ".$additional_query." ";
		$tracker_array = $this->getDataFromDB($query,$query_values);
		return $tracker_array;
	}

    /**
     * Gets all the tracker information based on the callsign
     *
     * @param string $ident
     * @param string $limit
     * @param string $sort
     * @param array $filter
     * @return array the tracker information
     */
	public function getTrackerDataByIdent($ident = '', $limit = '', $sort = '', $filter = array())
	{
		global $global_tracker_query;
		
		date_default_timezone_set('UTC');
		
		$query_values = array();
		$limit_query = '';
		$additional_query = '';
		$filter_query = $this->getFilter($filter,true,true);
		if ($ident != "")
		{
			if (!is_string($ident))
			{
				return array();
			} else {
				$additional_query = " AND (tracker_output.ident = :ident)";
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
			$orderby_query = " ORDER BY tracker_output.date DESC";
		}

		$query = $global_tracker_query.$filter_query." tracker_output.ident <> '' ".$additional_query." ".$orderby_query;

		$tracker_array = $this->getDataFromDB($query, $query_values, $limit_query);

		return $tracker_array;
	}
	
	public function getTrackerDataByDate($date = '', $limit = '', $sort = '',$filter = array())
	{
		global $global_tracker_query, $globalTimezone, $globalDBdriver;
		
		$query_values = array();
		$limit_query = '';
		$additional_query = '';

		$filter_query = $this->getFilter($filter,true,true);
		
		if ($date != "")
		{
			if ($globalTimezone != '') {
				date_default_timezone_set($globalTimezone);
				$datetime = new DateTime($date);
				$offset = $datetime->format('P');
			} else {
				date_default_timezone_set('UTC');
				$datetime = new DateTime($date);
				$offset = '+00:00';
			}
			if ($globalDBdriver == 'mysql') {
				$additional_query = " AND DATE(CONVERT_TZ(tracker_output.date,'+00:00', :offset)) = :date ";
				$query_values = array(':date' => $datetime->format('Y-m-d'), ':offset' => $offset);
			} elseif ($globalDBdriver == 'pgsql') {
				$additional_query = " AND to_char(tracker_output.date AT TIME ZONE :timezone,'YYYY-mm-dd') = :date ";
				$query_values = array(':date' => $datetime->format('Y-m-d'), ':timezone' => $globalTimezone);
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
			$orderby_query = " ORDER BY tracker_output.date DESC";
		}

		$query = $global_tracker_query.$filter_query." tracker_output.ident <> '' ".$additional_query.$orderby_query;
		$tracker_array = $this->getDataFromDB($query, $query_values, $limit_query);
		return $tracker_array;
	}


    /**
     * Gets all source name
     *
     * @param String type format of source
     * @param array $filters
     * @return array list of source name
     */
	public function getAllSourceName($type = '',$filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$query_values = array();
		$query  = "SELECT DISTINCT tracker_output.source_name 
				FROM tracker_output".$filter_query." tracker_output.source_name <> ''";
		if ($type != '') {
			$query_values = array(':type' => $type);
			$query .= " AND format_source = :type";
		}
		$query .= " ORDER BY tracker_output.source_name ASC";

		$sth = $this->db->prepare($query);
		if (!empty($query_values)) $sth->execute($query_values);
		else $sth->execute();

		$source_array = array();
		$temp_array = array();
		
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['source_name'] = $row['source_name'];
			$source_array[] = $temp_array;
		}
		return $source_array;
	}


    /**
     * Gets a list of all idents/callsigns
     *
     * @param array $filters
     * @return array list of ident/callsign names
     */
	public function getAllIdents($filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$query  = "SELECT DISTINCT tracker_output.ident
								FROM tracker_output".$filter_query." tracker_output.ident <> '' 
								ORDER BY tracker_output.date ASC LIMIT 700 OFFSET 0";

		$sth = $this->db->prepare($query);
		$sth->execute();
    
		$ident_array = array();
		$temp_array = array();
		
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['ident'] = $row['ident'];
			$ident_array[] = $temp_array;
		}

		return $ident_array;
	}

	/*
	* Gets a list of all dates
	*
	* @return Array list of date names
	*
	*/
	public function getAllDates()
	{
		global $globalTimezone, $globalDBdriver;
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime();
			$offset = $datetime->format('P');
		} else $offset = '+00:00';

		if ($globalDBdriver == 'mysql') {
			$query  = "SELECT DISTINCT DATE(CONVERT_TZ(tracker_output.date,'+00:00', :offset)) as date
								FROM tracker_output
								WHERE tracker_output.date <> '' 
								ORDER BY tracker_output.date ASC LIMIT 0,100";
		} else {
			$query  = "SELECT DISTINCT to_char(tracker_output.date AT TIME ZONE INTERVAL :offset,'YYYY-mm-dd') as date
								FROM tracker_output
								WHERE tracker_output.date <> '' 
								ORDER BY tracker_output.date ASC LIMIT 0,100";
		}
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':offset' => $offset));
    
		$date_array = array();
		$temp_array = array();
		
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['date'] = $row['date'];

			$date_array[] = $temp_array;
		}

		return $date_array;
	}


    /**
     * Update ident tracker data
     *
     * @param string $famtrackid
     * @param String $ident the flight ident
     * @param null $fromsource
     * @return String success or false
     */
	public function updateIdentTrackerData($famtrackid = '', $ident = '',$fromsource = NULL)
	{

		$query = 'UPDATE tracker_output SET ident = :ident WHERE famtrackid = :famtrackid';
                $query_values = array(':famtrackid' => $famtrackid,':ident' => $ident);

		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch (PDOException $e) {
			return "error : ".$e->getMessage();
		}
		
		return "success";

	}

    /**
     * Update latest tracker data
     *
     * @param string $famtrackid
     * @param String $ident the flight ident
     * @param string $latitude
     * @param string $longitude
     * @param string $altitude
     * @param null $groundspeed
     * @param string $date
     * @return String success or false
     */
	public function updateLatestTrackerData($famtrackid = '', $ident = '', $latitude = '', $longitude = '', $altitude = '', $groundspeed = NULL, $date = '')
	{
		$query = 'UPDATE tracker_output SET ident = :ident, last_latitude = :last_latitude, last_longitude = :last_longitude, last_altitude = :last_altitude, last_seen = :last_seen, last_ground_speed = :last_ground_speed WHERE famtrackid = :famtrackid';
                $query_values = array(':famtrackid' => $famtrackid,':last_latitude' => $latitude,':last_longitude' => $longitude, ':last_altitude' => $altitude,':last_ground_speed' => $groundspeed,':last_seen' => $date,':ident' => $ident);

		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch (PDOException $e) {
			return "error : ".$e->getMessage();
		}
		
		return "success";

	}

    /**
     * Adds a new tracker data
     *
     * @param string $famtrackid
     * @param String $ident the flight ident
     * @param String $latitude latitude of flight
     * @param String $longitude latitude of flight
     * @param String $altitude altitude of flight
     * @param String $heading heading of flight
     * @param String $groundspeed speed of flight
     * @param String $date date of flight
     * @param string $comment
     * @param string $type
     * @param string $format_source
     * @param string $source_name
     * @return String success or false
     */
	public function addTrackerData($famtrackid = '', $ident = '', $latitude = '', $longitude = '', $altitude = '', $heading = '', $groundspeed = '', $date = '', $comment = '', $type = '',$format_source = '', $source_name = '')
	{
		//$Image = new Image($this->db);
		$Common = new Common();
		
		date_default_timezone_set('UTC');
		
		//getting the registration
		if ($famtrackid != "")
		{
			if (!is_string($famtrackid))
			{
				return false;
			}
		}
		$fromsource = NULL;
		//getting the airline information
		if ($ident != "")
		{
			if (!is_string($ident))
			{
				return false;
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
		
		if ($altitude != "")
		{
			if (!is_numeric($altitude))
			{
				return false;
			}
		} else $altitude = 0;
		
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

    
		if ($date == "" || strtotime($date) < time()-20*60)
		{
			$date = date("Y-m-d H:i:s", time());
		}

		$famtrackid = filter_var($famtrackid,FILTER_SANITIZE_STRING);
		$ident = filter_var($ident,FILTER_SANITIZE_STRING);
		$latitude = filter_var($latitude,FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
		$longitude = filter_var($longitude,FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
		$altitude = filter_var($altitude,FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
		$heading = filter_var($heading,FILTER_SANITIZE_NUMBER_INT);
		$groundspeed = filter_var($groundspeed,FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
		$format_source = filter_var($format_source,FILTER_SANITIZE_STRING);
		$comment = filter_var($comment,FILTER_SANITIZE_STRING);
		$type = filter_var($type,FILTER_SANITIZE_STRING);
	
                if ($latitude == '' && $longitude == '') {
            		$latitude = 0;
            		$longitude = 0;
            	}
                if ($heading == '' || $Common->isInteger($heading) === false) $heading = 0;
                if ($groundspeed == '' || $Common->isInteger($groundspeed) === false) $groundspeed = 0;
                $query  = "INSERT INTO tracker_output (famtrackid, ident, latitude, longitude, altitude, heading, ground_speed, date, format_source, source_name, comment, type) 
                VALUES (:famtrackid,:ident,:latitude,:longitude,:altitude,:heading,:speed,:date,:format_source, :source_name,:comment,:type)";

                $query_values = array(':famtrackid' => $famtrackid,':ident' => $ident,':latitude' => $latitude,':longitude' => $longitude,':altitude' => $altitude,':heading' => $heading,':speed' => $groundspeed,':date' => $date,':format_source' => $format_source, ':source_name' => $source_name,':comment' => $comment,':type' => $type);

		try {
		        
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
			$this->db = null;
		} catch (PDOException $e) {
		    return "error : ".$e->getMessage();
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
		global $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			$query  = "SELECT tracker_output.ident FROM tracker_output 
								WHERE tracker_output.ident = :ident 
								AND tracker_output.date >= DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 HOUR) 
								AND tracker_output.date < UTC_TIMESTAMP()";
			$query_data = array(':ident' => $ident);
		} else {
			$query  = "SELECT tracker_output.ident FROM tracker_output 
								WHERE tracker_output.ident = :ident 
								AND tracker_output.date >= now() AT TIME ZONE 'UTC' - INTERVAL '1 HOURS'
								AND tracker_output.date < now() AT TIME ZONE 'UTC'";
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
     * Gets the aircraft data from the last 20 seconds
     *
     * @param string $q
     * @return array the tracker data
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
					$additional_query .= "(tracker_output.ident like '%".$q_item."%')";
					$additional_query .= ")";
				}
			}
		}
		if ($globalDBdriver == 'mysql') {
			$query  = "SELECT tracker_output.* FROM tracker_output 
				WHERE tracker_output.date >= DATE_SUB(UTC_TIMESTAMP(),INTERVAL 20 SECOND) ".$additional_query." 
				AND tracker_output.date < UTC_TIMESTAMP()";
		} else {
			$query  = "SELECT tracker_output.* FROM tracker_output 
				WHERE tracker_output.date::timestamp >= CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '20 SECONDS' ".$additional_query." 
				AND tracker_output.date::timestamp < CURRENT_TIMESTAMP AT TIME ZONE 'UTC'";
		}
		$tracker_array = $this->getDataFromDB($query, array());
		return $tracker_array;
	}

    /**
     * Gets all number of flight over countries
     *
     * @param bool $limit
     * @param int $olderthanmonths
     * @param string $sincedate
     * @param array $filters
     * @return array the airline country list
     */
	public function countAllTrackerOverCountries($limit = true,$olderthanmonths = 0,$sincedate = '',$filters = array())
	{
		global $globalDBdriver, $globalArchive;
		//$filter_query = $this->getFilter($filters,true,true);
		$Connection= new Connection($this->db);
		if (!$Connection->tableExists('countries')) return array();
		if (!isset($globalArchive) || $globalArchive !== TRUE) {
			require_once('class.TrackerLive.php');
			$TrackerLive = new TrackerLive($this->db);
			$filter_query = $TrackerLive->getFilter($filters,true,true);
			$filter_query .= " over_country IS NOT NULL AND over_country <> ''";
			if ($olderthanmonths > 0) {
				if ($globalDBdriver == 'mysql') {
					$filter_query .= ' AND tracker_live.date < DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.$olderthanmonths.' MONTH) ';
				} else {
					$filter_query .= " AND tracker_live.date < CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$olderthanmonths." MONTHS'";
				}
			}
			if ($sincedate != '') {
				if ($globalDBdriver == 'mysql') {
					$filter_query .= " AND tracker_live.date > '".$sincedate."' ";
				} else {
					$filter_query .= " AND tracker_live.date > CAST('".$sincedate."' AS TIMESTAMP)";
				}
			}
			$query = "SELECT c.name, c.iso3, c.iso2, count(c.name) as nb FROM countries c INNER JOIN (SELECT DISTINCT famtrackid,over_country FROM tracker_live".$filter_query.") l ON c.iso2 = l.over_country ";
		} else {
			require_once('class.TrackerArchive.php');
			$TrackerArchive = new TrackerArchive($this->db);
			$filter_query = $TrackerArchive->getFilter($filters,true,true);
			$filter_query .= " over_country IS NOT NULL AND over_country <> ''";
			if ($olderthanmonths > 0) {
				if ($globalDBdriver == 'mysql') {
					$filter_query .= ' AND tracker_archive.date < DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.$olderthanmonths.' MONTH) ';
				} else {
					$filter_query .= " AND tracker_archive.date < CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$olderthanmonths." MONTHS'";
				}
			}
			if ($sincedate != '') {
				if ($globalDBdriver == 'mysql') {
					$filter_query .= " AND tracker_archive.date > '".$sincedate."' ";
				} else {
					$filter_query .= " AND tracker_archive.date > CAST('".$sincedate."' AS TIMESTAMP)";
				}
			}
			$query = "SELECT c.name, c.iso3, c.iso2, count(c.name) as nb FROM countries c INNER JOIN (SELECT DISTINCT famtrackid,over_country FROM tracker_archive".$filter_query.") l ON c.iso2 = l.over_country ";
		}
		$query .= "GROUP BY c.name,c.iso3,c.iso2 ORDER BY nb DESC";
		if ($limit) $query .= " LIMIT 10 OFFSET 0";
      
		
		$sth = $this->db->prepare($query);
		$sth->execute();
 
		$flight_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['tracker_count'] = $row['nb'];
			$temp_array['tracker_country'] = $row['name'];
			$temp_array['tracker_country_iso3'] = $row['iso3'];
			$temp_array['tracker_country_iso2'] = $row['iso2'];
			$flight_array[] = $temp_array;
		}
		return $flight_array;
	}

    /**
     * Gets all callsigns that have flown over
     *
     * @param bool $limit
     * @param int $olderthanmonths
     * @param string $sincedate
     * @param array $filters
     * @param string $year
     * @param string $month
     * @param string $day
     * @return array the callsign list
     */
	public function countAllCallsigns($limit = true, $olderthanmonths = 0, $sincedate = '',$filters = array(),$year = '', $month = '', $day = '')
	{
		global $globalDBdriver;
		$filter_query = $this->getFilter($filters,true,true);
		$query  = "SELECT DISTINCT tracker_output.ident, COUNT(tracker_output.ident) AS callsign_icao_count 
                    FROM tracker_output".$filter_query." tracker_output.ident <> ''";
		 if ($olderthanmonths > 0) {
			if ($globalDBdriver == 'mysql') $query .= ' AND date < DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.$olderthanmonths.' MONTH)';
			else $query .= " AND tracker_output.date < CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$olderthanmonths." MONTHS'";
		}
		if ($sincedate != '') {
			if ($globalDBdriver == 'mysql') $query .= " AND tracker_output.date > '".$sincedate."'";
			else $query .= " AND tracker_output.date > CAST('".$sincedate."' AS TIMESTAMP)";
		}
		$query_values = array();
		if ($year != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND YEAR(tracker_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			} else {
				$query .= " AND EXTRACT(YEAR FROM tracker_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			}
		}
		if ($month != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND MONTH(tracker_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			} else {
				$query .= " AND EXTRACT(MONTH FROM tracker_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			}
		}
		if ($day != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND DAY(tracker_output.date) = :day";
				$query_values = array_merge($query_values,array(':day' => $day));
			} else {
				$query .= " AND EXTRACT(DAY FROM tracker_output.date) = :day";
				$query_values = array_merge($query_values,array(':day' => $day));
			}
		}
		$query .= " GROUP BY tracker_output.ident ORDER BY callsign_icao_count DESC";
		if ($limit) $query .= " LIMIT 10 OFFSET 0";
      		
		$sth = $this->db->prepare($query);
		$sth->execute($query_values);
      
		$callsign_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['callsign_icao'] = $row['ident'];
			$temp_array['airline_name'] = $row['airline_name'];
			$temp_array['airline_icao'] = $row['airline_icao'];
			$temp_array['callsign_icao_count'] = $row['callsign_icao_count'];
          
			$callsign_array[] = $temp_array;
		}

		return $callsign_array;
	}


    /**
     * Counts all dates
     *
     * @param array $filters
     * @return array the date list
     */
	public function countAllDates($filters = array())
	{
		global $globalTimezone, $globalDBdriver;
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime();
			$offset = $datetime->format('P');
		} else $offset = '+00:00';

		if ($globalDBdriver == 'mysql') {
			$query  = "SELECT DATE(CONVERT_TZ(tracker_output.date,'+00:00', :offset)) AS date_name, count(*) as date_count
								FROM tracker_output";
			$query .= $this->getFilter($filters);
			$query .= " GROUP BY date_name 
								ORDER BY date_count DESC
								LIMIT 10 OFFSET 0";
		} else {
			$query  = "SELECT to_char(tracker_output.date AT TIME ZONE INTERVAL :offset,'YYYY-mm-dd') AS date_name, count(*) as date_count
								FROM tracker_output";
			$query .= $this->getFilter($filters);
			$query .= " GROUP BY date_name 
								ORDER BY date_count DESC
								LIMIT 10 OFFSET 0";
		}
      
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':offset' => $offset));
      
		$date_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['date_name'] = $row['date_name'];
			$temp_array['date_count'] = $row['date_count'];

			$date_array[] = $temp_array;
		}

		return $date_array;
	}


    /**
     * Counts all dates during the last 7 days
     *
     * @param array $filters
     * @return array the date list
     */
	public function countAllDatesLast7Days($filters = array())
	{
		global $globalTimezone, $globalDBdriver;
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime();
			$offset = $datetime->format('P');
		} else $offset = '+00:00';
		$filter_query = $this->getFilter($filters,true,true);
		if ($globalDBdriver == 'mysql') {
			$query  = "SELECT DATE(CONVERT_TZ(tracker_output.date,'+00:00', :offset)) AS date_name, count(*) as date_count
								FROM tracker_output".$filter_query." tracker_output.date >= DATE_SUB(UTC_TIMESTAMP(),INTERVAL 7 DAY)";
			$query .= " GROUP BY date_name 
								ORDER BY tracker_output.date ASC";
			$query_data = array(':offset' => $offset);
		} else {
			$query  = "SELECT to_char(tracker_output.date AT TIME ZONE INTERVAL :offset,'YYYY-mm-dd') AS date_name, count(*) as date_count
								FROM tracker_output".$filter_query." tracker_output.date >= CURRENT_TIMESTAMP AT TIME ZONE INTERVAL :offset - INTERVAL '7 DAYS'";
			$query .= " GROUP BY date_name 
								ORDER BY date_name ASC";
			$query_data = array(':offset' => $offset);
    		}
		
		$sth = $this->db->prepare($query);
		$sth->execute($query_data);
      
		$date_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['date_name'] = $row['date_name'];
			$temp_array['date_count'] = $row['date_count'];
          
			$date_array[] = $temp_array;
		}

		return $date_array;
	}

    /**
     * Counts all dates during the last month
     *
     * @param array $filters
     * @return array the date list
     */
	public function countAllDatesLastMonth($filters = array())
	{
		global $globalTimezone, $globalDBdriver;
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime();
			$offset = $datetime->format('P');
		} else $offset = '+00:00';
		$filter_query = $this->getFilter($filters,true,true);
		if ($globalDBdriver == 'mysql') {
			$query  = "SELECT DATE(CONVERT_TZ(tracker_output.date,'+00:00', :offset)) AS date_name, count(*) as date_count
								FROM tracker_output".$filter_query." tracker_output.date >= DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 MONTH)";
			$query .= " GROUP BY date_name 
								ORDER BY tracker_output.date ASC";
			$query_data = array(':offset' => $offset);
		} else {
			$query  = "SELECT to_char(tracker_output.date AT TIME ZONE INTERVAL :offset,'YYYY-mm-dd') AS date_name, count(*) as date_count
								FROM tracker_output".$filter_query." tracker_output.date >= CURRENT_TIMESTAMP AT TIME ZONE INTERVAL :offset - INTERVAL '1 MONTHS'";
			$query .= " GROUP BY date_name 
								ORDER BY date_name ASC";
			$query_data = array(':offset' => $offset);
    		}
		
		$sth = $this->db->prepare($query);
		$sth->execute($query_data);
      
		$date_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['date_name'] = $row['date_name'];
			$temp_array['date_count'] = $row['date_count'];
          
			$date_array[] = $temp_array;
		}

		return $date_array;
	}


    /**
     * Counts all month
     *
     * @param array $filters
     * @return array the month list
     */
	public function countAllMonths($filters = array())
	{
		global $globalTimezone, $globalDBdriver;
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime();
			$offset = $datetime->format('P');
		} else $offset = '+00:00';

		if ($globalDBdriver == 'mysql') {
			$query  = "SELECT YEAR(CONVERT_TZ(tracker_output.date,'+00:00', :offset)) AS year_name,MONTH(CONVERT_TZ(tracker_output.date,'+00:00', :offset)) AS month_name, count(*) as date_count
								FROM tracker_output";
			$query .= $this->getFilter($filters);
			$query .= " GROUP BY year_name, month_name ORDER BY date_count DESC";
		} else {
			$query  = "SELECT EXTRACT(YEAR FROM tracker_output.date AT TIME ZONE INTERVAL :offset) AS year_name,EXTRACT(MONTH FROM tracker_output.date AT TIME ZONE INTERVAL :offset) AS month_name, count(*) as date_count
								FROM tracker_output";
			$query .= $this->getFilter($filters);
			$query .= " GROUP BY year_name, month_name ORDER BY date_count DESC";
		}
      
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':offset' => $offset));
      
		$date_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['month_name'] = $row['month_name'];
			$temp_array['year_name'] = $row['year_name'];
			$temp_array['date_count'] = $row['date_count'];

			$date_array[] = $temp_array;
		}

		return $date_array;
	}


    /**
     * Counts all dates during the last year
     *
     * @param $filters
     * @return array the date list
     */
	public function countAllMonthsLastYear($filters)
	{
		global $globalTimezone, $globalDBdriver;
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime();
			$offset = $datetime->format('P');
		} else $offset = '+00:00';
		$filter_query = $this->getFilter($filters,true,true);
		if ($globalDBdriver == 'mysql') {
			$query  = "SELECT MONTH(CONVERT_TZ(tracker_output.date,'+00:00', :offset)) AS month_name, YEAR(CONVERT_TZ(tracker_output.date,'+00:00', :offset)) AS year_name, count(*) as date_count
								FROM tracker_output".$filter_query." tracker_output.date >= DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 YEAR)";
			$query .= " GROUP BY year_name, month_name
								ORDER BY year_name, month_name ASC";
			$query_data = array(':offset' => $offset);
		} else {
			$query  = "SELECT EXTRACT(MONTH FROM tracker_output.date AT TIME ZONE INTERVAL :offset) AS month_name, EXTRACT(YEAR FROM tracker_output.date AT TIME ZONE INTERVAL :offset) AS year_name, count(*) as date_count
								FROM tracker_output".$filter_query." tracker_output.date >= CURRENT_TIMESTAMP AT TIME ZONE INTERVAL :offset - INTERVAL '1 YEARS'";
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
     * Counts all hours
     *
     * @param $orderby
     * @param array $filters
     * @return array the hour list
     */
	public function countAllHours($orderby,$filters = array())
	{
		global $globalTimezone, $globalDBdriver;
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime();
			$offset = $datetime->format('P');
		} else $offset = '+00:00';

		$orderby_sql = '';
		if ($orderby == "hour")
		{
			$orderby_sql = "ORDER BY hour_name ASC";
		}
		if ($orderby == "count")
		{
			$orderby_sql = "ORDER BY hour_count DESC";
		}
		
		if ($globalDBdriver == 'mysql') {
			$query  = "SELECT HOUR(CONVERT_TZ(tracker_output.date,'+00:00', :offset)) AS hour_name, count(*) as hour_count
								FROM tracker_output";
			$query .= $this->getFilter($filters);
			$query .= " GROUP BY hour_name 
								".$orderby_sql;

/*		$query  = "SELECT HOUR(tracker_output.date) AS hour_name, count(*) as hour_count
								FROM tracker_output 
								GROUP BY hour_name 
								".$orderby_sql."
								LIMIT 10 OFFSET 00";
  */    
		$query_data = array(':offset' => $offset);
		} else {
			$query  = "SELECT EXTRACT(HOUR FROM tracker_output.date AT TIME ZONE INTERVAL :offset) AS hour_name, count(*) as hour_count
								FROM tracker_output";
			$query .= $this->getFilter($filters);
			$query .= " GROUP BY hour_name 
								".$orderby_sql;
			$query_data = array(':offset' => $offset);
		}
		
		$sth = $this->db->prepare($query);
		$sth->execute($query_data);
      
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
     * Counts all hours by date
     *
     * @param $date
     * @param array $filters
     * @return array the hour list
     */
	public function countAllHoursByDate($date, $filters = array())
	{
		global $globalTimezone, $globalDBdriver;
		$filter_query = $this->getFilter($filters,true,true);
		$date = filter_var($date,FILTER_SANITIZE_STRING);
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime($date);
			$offset = $datetime->format('P');
		} else $offset = '+00:00';

		if ($globalDBdriver == 'mysql') {
			$query  = "SELECT HOUR(CONVERT_TZ(tracker_output.date,'+00:00', :offset)) AS hour_name, count(*) as hour_count
								FROM tracker_output".$filter_query." DATE(CONVERT_TZ(tracker_output.date,'+00:00', :offset)) = :date
								GROUP BY hour_name 
								ORDER BY hour_name ASC";
		} else {
			$query  = "SELECT EXTRACT(HOUR FROM tracker_output.date AT TIME ZONE INTERVAL :offset) AS hour_name, count(*) as hour_count
								FROM tracker_output".$filter_query." to_char(tracker_output.date AT TIME ZONE INTERVAL :offset, 'YYYY-mm-dd') = :date
								GROUP BY hour_name 
								ORDER BY hour_name ASC";
		}
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':date' => $date, ':offset' => $offset));
      
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
     * Counts all hours by a ident/callsign
     *
     * @param $ident
     * @param array $filters
     * @return array the hour list
     */
	public function countAllHoursByIdent($ident, $filters = array())
	{
		global $globalTimezone, $globalDBdriver;
		$filter_query = $this->getFilter($filters,true,true);
		$ident = filter_var($ident,FILTER_SANITIZE_STRING);
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime();
			$offset = $datetime->format('P');
		} else $offset = '+00:00';

		if ($globalDBdriver == 'mysql') {
			$query  = "SELECT HOUR(CONVERT_TZ(tracker_output.date,'+00:00', :offset)) AS hour_name, count(*) as hour_count
								FROM tracker_output".$filter_query." tracker_output.ident = :ident 
								GROUP BY hour_name 
								ORDER BY hour_name ASC";
		} else {
			$query  = "SELECT EXTRACT(HOUR FROM tracker_output.date AT TIME ZONE INTERVAL :offset) AS hour_name, count(*) as hour_count
								FROM tracker_output".$filter_query." tracker_output.ident = :ident 
								GROUP BY hour_name 
								ORDER BY hour_name ASC";
		}
      
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':ident' => $ident,':offset' => $offset));
      
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
     * Counts all trackers that have flown over
     *
     * @param array $filters
     * @param string $year
     * @param string $month
     * @return Integer the number of trackers
     */
	public function countOverallTracker($filters = array(),$year = '',$month = '')
	{
		global $globalDBdriver;
		//$queryi  = "SELECT COUNT(tracker_output.tracker_id) AS flight_count FROM tracker_output";
		$queryi  = "SELECT COUNT(DISTINCT tracker_output.ident) AS tracker_count FROM tracker_output";
		$query_values = array();
		$query = '';
		if ($year != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND YEAR(tracker_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			} else {
				$query .= " AND EXTRACT(YEAR FROM tracker_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			}
		}
		if ($month != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND MONTH(tracker_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			} else {
				$query .= " AND EXTRACT(MONTH FROM tracker_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			}
		}
		if (empty($query_values)) $queryi .= $this->getFilter($filters);
		else $queryi .= $this->getFilter($filters,true,true).substr($query,4);
		
		$sth = $this->db->prepare($queryi);
		$sth->execute($query_values);
		return $sth->fetchColumn();
	}

    /**
     * Counts all trackers type that have flown over
     *
     * @param array $filters
     * @param string $year
     * @param string $month
     * @return Integer the number of flights
     */
	public function countOverallTrackerTypes($filters = array(),$year = '',$month = '')
	{
		global $globalDBdriver;
		$queryi  = "SELECT COUNT(DISTINCT tracker_output.type) AS tracker_count FROM tracker_output";
		$query_values = array();
		$query = '';
		if ($year != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND YEAR(tracker_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			} else {
				$query .= " AND EXTRACT(YEAR FROM tracker_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			}
		}
		if ($month != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND MONTH(tracker_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			} else {
				$query .= " AND EXTRACT(MONTH FROM tracker_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			}
		}
		if (empty($query_values)) $queryi .= $this->getFilter($filters);
		else $queryi .= $this->getFilter($filters,true,true).substr($query,4);
		
		$sth = $this->db->prepare($queryi);
		$sth->execute($query_values);
		return $sth->fetchColumn();
	}


    /**
     * Counts all hours of today
     *
     * @param array $filters
     * @return array the hour list
     */
	public function countAllHoursFromToday($filters = array())
	{
		global $globalTimezone, $globalDBdriver;
		$filter_query = $this->getFilter($filters,true,true);
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime();
			$offset = $datetime->format('P');
		} else $offset = '+00:00';

		if ($globalDBdriver == 'mysql') {
			$query  = "SELECT HOUR(CONVERT_TZ(tracker_output.date,'+00:00', :offset)) AS hour_name, count(*) as hour_count
								FROM tracker_output".$filter_query." DATE(CONVERT_TZ(tracker_output.date,'+00:00', :offset)) = CURDATE()
								GROUP BY hour_name 
								ORDER BY hour_name ASC";
		} else {
			$query  = "SELECT EXTRACT(HOUR FROM tracker_output.date AT TIME ZONE INTERVAL :offset) AS hour_name, count(*) as hour_count
								FROM tracker_output".$filter_query." to_char(tracker_output.date AT TIME ZONE INTERVAL :offset,'YYYY-mm-dd') = CAST(NOW() AS date)
								GROUP BY hour_name 
								ORDER BY hour_name ASC";
		}
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':offset' => $offset));
      
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
     * Gets the Barrie Spotter ID based on the FlightAware ID
     *
     * @param $famtrackid
     * @return Integer the Barrie Spotter ID
     */
	public function getTrackerIDBasedOnFamTrackID($famtrackid)
	{
		$famtrackid = filter_var($famtrackid,FILTER_SANITIZE_STRING);

		$query  = "SELECT tracker_output.tracker_id
				FROM tracker_output 
				WHERE tracker_output.famtrackid = '".$famtrackid."'";
        
		
		$sth = $this->db->prepare($query);
		$sth->execute();

		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			return $row['tracker_id'];
		}
	}
  
 
	/**
	* Parses a date string
	*
	* @param String $dateString the date string
	* @param String $timezone the timezone of a user
	* @return array the time information
	*
	*/
	public function parseDateString($dateString, $timezone = '')
	{
		$time_array = array();
	
		if ($timezone != "")
		{
			date_default_timezone_set($timezone);
		}
		
		$current_date = date("Y-m-d H:i:s");
		$date = date("Y-m-d H:i:s",strtotime($dateString." UTC"));
		
		$diff = abs(strtotime($current_date) - strtotime($date));

		$time_array['years'] = floor($diff / (365*60*60*24)); 
		$years = $time_array['years'];
		
		$time_array['months'] = floor(($diff - $years * 365*60*60*24) / (30*60*60*24));
		$months = $time_array['months'];
		
		$time_array['days'] = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24)/ (60*60*24));
		$days = $time_array['days'];
		$time_array['hours'] = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24 - $days*60*60*24)/ (60*60));
		$hours = $time_array['hours'];
		$time_array['minutes'] = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24 - $days*60*60*24 - $hours*60*60)/ 60);
		$minutes = $time_array['minutes'];
		$time_array['seconds'] = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24 - $days*60*60*24 - $hours*60*60 - $minutes*60));  
		
		return $time_array;
	}
	
	/**
	* Parses the direction degrees to working
	*
	* @param Float $direction the direction in degrees
	* @return array the direction information
	*
	*/
	public function parseDirection($direction = 0)
	{
		if ($direction == '') $direction = 0;
		$direction_array = array();
		$temp_array = array();

		if ($direction == 360 || ($direction >= 0 && $direction < 22.5))
		{
			$temp_array['direction_degree'] = $direction;
			$temp_array['direction_shortname'] = "N";
			$temp_array['direction_fullname'] = "North";
		} elseif ($direction >= 22.5 && $direction < 45){
			$temp_array['direction_degree'] = $direction;
			$temp_array['direction_shortname'] = "NNE";
			$temp_array['direction_fullname'] = "North-Northeast";
		} elseif ($direction >= 45 && $direction < 67.5){
			$temp_array['direction_degree'] = $direction;
			$temp_array['direction_shortname'] = "NE";
			$temp_array['direction_fullname'] = "Northeast";
		} elseif ($direction >= 67.5 && $direction < 90){
			$temp_array['direction_degree'] = $direction;
			$temp_array['direction_shortname'] = "ENE";
			$temp_array['direction_fullname'] = "East-Northeast";
		} elseif ($direction >= 90 && $direction < 112.5){
			$temp_array['direction_degree'] = $direction;
			$temp_array['direction_shortname'] = "E";
			$temp_array['direction_fullname'] = "East";
		} elseif ($direction >= 112.5 && $direction < 135){
			$temp_array['direction_degree'] = $direction;
			$temp_array['direction_shortname'] = "ESE";
			$temp_array['direction_fullname'] = "East-Southeast";
		} elseif ($direction >= 135 && $direction < 157.5){
			$temp_array['direction_degree'] = $direction;
			$temp_array['direction_shortname'] = "SE";
			$temp_array['direction_fullname'] = "Southeast";
		} elseif ($direction >= 157.5 && $direction < 180){
			$temp_array['direction_degree'] = $direction;
			$temp_array['direction_shortname'] = "SSE";
			$temp_array['direction_fullname'] = "South-Southeast";
		} elseif ($direction >= 180 && $direction < 202.5){
			$temp_array['direction_degree'] = $direction;
			$temp_array['direction_shortname'] = "S";
			$temp_array['direction_fullname'] = "South";
		} elseif ($direction >= 202.5 && $direction < 225){
			$temp_array['direction_degree'] = $direction;
			$temp_array['direction_shortname'] = "SSW";
			$temp_array['direction_fullname'] = "South-Southwest";
		} elseif ($direction >= 225 && $direction < 247.5){
			$temp_array['direction_degree'] = $direction;
			$temp_array['direction_shortname'] = "SW";
			$temp_array['direction_fullname'] = "Southwest";
		} elseif ($direction >= 247.5 && $direction < 270){
			$temp_array['direction_degree'] = $direction;
			$temp_array['direction_shortname'] = "WSW";
			$temp_array['direction_fullname'] = "West-Southwest";
		} elseif ($direction >= 270 && $direction < 292.5){
			$temp_array['direction_degree'] = $direction;
			$temp_array['direction_shortname'] = "W";
			$temp_array['direction_fullname'] = "West";
		} elseif ($direction >= 292.5 && $direction < 315){
			$temp_array['direction_degree'] = $direction;
			$temp_array['direction_shortname'] = "WNW";
			$temp_array['direction_fullname'] = "West-Northwest";
		} elseif ($direction >= 315 && $direction < 337.5){
			$temp_array['direction_degree'] = $direction;
			$temp_array['direction_shortname'] = "NW";
			$temp_array['direction_fullname'] = "Northwest";
		} elseif ($direction >= 337.5 && $direction < 360){
			$temp_array['direction_degree'] = $direction;
			$temp_array['direction_shortname'] = "NNW";
			$temp_array['direction_fullname'] = "North-Northwest";
		}
		$direction_array[] = $temp_array;
		return $direction_array;
	}
	
	
	/**
	* Gets Country from latitude/longitude
	*
	* @param Float $latitude latitute of the flight
	* @param Float $longitude longitute of the flight
	* @return String the countrie
	*/
	public function getCountryFromLatitudeLongitude($latitude,$longitude)
	{
		global $globalDebug;
		$latitude = filter_var($latitude,FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
		$longitude = filter_var($longitude,FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
	
		$Connection = new Connection($this->db);
		if (!$Connection->tableExists('countries')) return '';
	
		try {
			$query = "SELECT name,iso2,iso3 FROM countries WHERE ST_Within(ST_GeomFromText('POINT(".$longitude." ".$latitude.")',4326), ogc_geom) LIMIT 1";
			$sth = $this->db->prepare($query);
			$sth->execute();
    
			$row = $sth->fetch(PDO::FETCH_ASSOC);
			$sth->closeCursor();
			if (count($row) > 0) {
				return $row;
			} else return '';
		} catch (PDOException $e) {
			if (isset($globalDebug) && $globalDebug) echo 'Error : '.$e->getMessage()."\n";
			return '';
		}
	
	}

	/**
	* Gets Country from iso2
	*
	* @param String $iso2 ISO2 country code
	* @return String the countrie
	*/
	public function getCountryFromISO2($iso2)
	{
		global $globalDebug;
		$iso2 = filter_var($iso2,FILTER_SANITIZE_STRING);
	
		$Connection = new Connection($this->db);
		if (!$Connection->tableExists('countries')) return '';
	
		try {
			$query = "SELECT name,iso2,iso3 FROM countries WHERE iso2 = :iso2 LIMIT 1";
		
			$sth = $this->db->prepare($query);
			$sth->execute(array(':iso2' => $iso2));
    
			$row = $sth->fetch(PDO::FETCH_ASSOC);
			$sth->closeCursor();
			if (count($row) > 0) {
				return $row;
			} else return '';
		} catch (PDOException $e) {
			if (isset($globalDebug) && $globalDebug) echo 'Error : '.$e->getMessage()."\n";
			return '';
		}
	
	}

    /**
     * Gets all vessels types that have flown over
     *
     * @param bool $limit
     * @param int $olderthanmonths
     * @param string $sincedate
     * @param array $filters
     * @param string $year
     * @param string $month
     * @param string $day
     * @return array the vessel type list
     */
	public function countAllTrackerTypes($limit = true,$olderthanmonths = 0,$sincedate = '',$filters = array(),$year = '',$month = '',$day = '')
	{
		global $globalDBdriver;
		$filter_query = $this->getFilter($filters,true,true);
		$query  = "SELECT tracker_output.type AS tracker_type, COUNT(tracker_output.type) AS tracker_type_count 
		    FROM tracker_output ".$filter_query." tracker_output.type  <> ''";
		if ($olderthanmonths > 0) {
			if ($globalDBdriver == 'mysql') {
				$query .= ' AND tracker_output.date < DATE_SUB(UTC_TIMESTAMP(), INTERVAL '.$olderthanmonths.' MONTH)';
			} else {
				$query .= " AND tracker_output.date < CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$olderthanmonths." MONTHS'";
			}
		}
		if ($sincedate != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND tracker_output.date > '".$sincedate."'";
			} else {
				$query .= " AND tracker_output.date > CAST('".$sincedate."' AS TIMESTAMP)";
			}
		}
		$query_values = array();
		if ($year != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND YEAR(tracker_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			} else {
				$query .= " AND EXTRACT(YEAR FROM tracker_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			}
		}
		if ($month != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND MONTH(tracker_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			} else {
				$query .= " AND EXTRACT(MONTH FROM tracker_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			}
		}
		if ($day != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND DAY(tracker_output.date) = :day";
				$query_values = array_merge($query_values,array(':day' => $day));
			} else {
				$query .= " AND EXTRACT(DAY FROM tracker_output.date) = :day";
				$query_values = array_merge($query_values,array(':day' => $day));
			}
		}
		$query .= " GROUP BY tracker_output.type ORDER BY tracker_type_count DESC";
		if ($limit) $query .= " LIMIT 10 OFFSET 0";
		$sth = $this->db->prepare($query);
		$sth->execute($query_values);
		$tracker_array = array();
		$temp_array = array();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['tracker_type'] = $row['tracker_type'];
			$temp_array['tracker_type_count'] = $row['tracker_type_count'];
			$tracker_array[] = $temp_array;
		}
		return $tracker_array;
	}

    /**
     * Gets all the tracker information
     *
     * @param string $q
     * @param string $callsign
     * @param string $date_posted
     * @param string $limit
     * @param string $sort
     * @param string $includegeodata
     * @param string $origLat
     * @param string $origLon
     * @param string $dist
     * @param array $filters
     * @return array the tracker information
     */
	public function searchTrackerData($q = '', $callsign = '', $date_posted = '', $limit = '', $sort = '', $includegeodata = '',$origLat = '',$origLon = '',$dist = '',$filters = array())
	{
		global $globalTimezone, $globalDBdriver;
		date_default_timezone_set('UTC');
		$query_values = array();
		$additional_query = '';
		$filter_query = $this->getFilter($filters,true,true);
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
					if (is_int($q_item)) $additional_query .= "(tracker_output.tracker_id = '".$q_item."') OR ";
					$additional_query .= "(tracker_output.ident like '%".$q_item."%') OR ";
					$additional_query .= ")";
				}
			}
		}
		if ($callsign != "")
		{
			$callsign = filter_var($callsign,FILTER_SANITIZE_STRING);
			if (!is_string($callsign))
			{
				return array();
			} else {
				$additional_query .= " AND tracker_output.ident = :callsign";
				$query_values = array_merge($query_values,array(':callsign' => $callsign));
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
					$additional_query .= " AND TIMESTAMP(CONVERT_TZ(tracker_output.date,'+00:00', '".$offset."')) >= '".$date_array[0]."' AND TIMESTAMP(CONVERT_TZ(tracker_output.date,'+00:00', '".$offset."')) <= '".$date_array[1]."' ";
				} else {
					$additional_query .= " AND CAST(tracker_output.date AT TIME ZONE INTERVAL ".$offset." AS TIMESTAMP) >= '".$date_array[0]."' AND CAST(tracker_output.date AT TIME ZONE INTERVAL ".$offset." AS TIMESTAMP) <= '".$date_array[1]."' ";
				}
			} else {
				$date_array[0] = date("Y-m-d H:i:s", strtotime($date_array[0]));
				if ($globalDBdriver == 'mysql') {
					$additional_query .= " AND TIMESTAMP(CONVERT_TZ(tracker_output.date,'+00:00', '".$offset."')) >= '".$date_array[0]."' ";
				} else {
					$additional_query .= " AND CAST(tracker_output.date AT TIME ZONE INTERVAL ".$offset." AS TIMESTAMP) >= '".$date_array[0]."' ";
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
				$limit_query = " LIMIT ".$limit_array[1]." OFFSET ".$limit_array[0];
			} else $limit_query = "";
		} else $limit_query = "";
		if ($sort != "")
		{
			$search_orderby_array = $this->getOrderBy();
			$orderby_query = $search_orderby_array[$sort]['sql'];
		} else {
			if ($origLat != "" && $origLon != "" && $dist != "") {
				$orderby_query = " ORDER BY distance ASC";
			} else {
				$orderby_query = " ORDER BY tracker_output.date DESC";
			}
		}
		if ($origLat != "" && $origLon != "" && $dist != "") {
			$dist = number_format($dist*0.621371,2,'.',''); // convert km to mile
			if ($globalDBdriver == 'mysql') {
				$query="SELECT tracker_output.*, 1.60935*3956 * 2 * ASIN(SQRT( POWER(SIN(($origLat - tracker_archive.latitude)*pi()/180/2),2)+COS( $origLat *pi()/180)*COS(tracker_archive.latitude*pi()/180)*POWER(SIN(($origLon-tracker_archive.longitude)*pi()/180/2),2))) as distance 
				    FROM tracker_archive,tracker_output".$filter_query." tracker_output.famtrackid = tracker_archive.famtrackid AND tracker_output.ident <> '' ".$additional_query."AND tracker_archive.longitude between ($origLon-$dist/cos(radians($origLat))*69) and ($origLon+$dist/cos(radians($origLat)*69)) and tracker_archive.latitude between ($origLat-($dist/69)) and ($origLat+($dist/69)) 
				    AND (3956 * 2 * ASIN(SQRT( POWER(SIN(($origLat - tracker_archive.latitude)*pi()/180/2),2)+COS( $origLat *pi()/180)*COS(tracker_archive.latitude*pi()/180)*POWER(SIN(($origLon-tracker_archive.longitude)*pi()/180/2),2)))) < $dist".$orderby_query;
			} else {
				$query="SELECT tracker_output.*, 1.60935 * 3956 * 2 * ASIN(SQRT( POWER(SIN(($origLat - CAST(tracker_archive.latitude as double precision))*pi()/180/2),2)+COS( $origLat *pi()/180)*COS(CAST(tracker_archive.latitude as double precision)*pi()/180)*POWER(SIN(($origLon-CAST(tracker_archive.longitude as double precision))*pi()/180/2),2))) as distance 
				    FROM tracker_archive,tracker_output".$filter_query." tracker_output.famtrackid = tracker_archive.famtrackid AND tracker_output.ident <> '' ".$additional_query."AND CAST(tracker_archive.longitude as double precision) between ($origLon-$dist/cos(radians($origLat))*69) and ($origLon+$dist/cos(radians($origLat))*69) and CAST(tracker_archive.latitude as double precision) between ($origLat-($dist/69)) and ($origLat+($dist/69)) 
				    AND (3956 * 2 * ASIN(SQRT( POWER(SIN(($origLat - CAST(tracker_archive.latitude as double precision))*pi()/180/2),2)+COS( $origLat *pi()/180)*COS(CAST(tracker_archive.latitude as double precision)*pi()/180)*POWER(SIN(($origLon-CAST(tracker_archive.longitude as double precision))*pi()/180/2),2)))) < $dist".$filter_query.$orderby_query;
			}
		} else {
			$query  = "SELECT tracker_output.* FROM tracker_output".$filter_query." tracker_output.ident <> '' 
			    ".$additional_query."
			    ".$orderby_query;
		}
		$tracker_array = $this->getDataFromDB($query, $query_values,$limit_query);
		return $tracker_array;
	}

	/**
	* Gets the short url from bit.ly
	*
	* @param String $url the full url
	* @return String the bit.ly url
	*
	*/
	public function getBitlyURL($url)
	{
		global $globalBitlyAccessToken;
		
		if ($globalBitlyAccessToken == '') return $url;
		$google_url = 'https://api-ssl.bitly.com/v3/shorten?access_token='.$globalBitlyAccessToken.'&longUrl='.$url;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $google_url);
		$bitly_data = curl_exec($ch);
		curl_close($ch);
		$bitly_data = json_decode($bitly_data);
		$bitly_url = '';
		if ($bitly_data->status_txt = "OK"){
			$bitly_url = $bitly_data->data->url;
		}
		return $bitly_url;
	}

	public function getOrderBy()
	{
		$orderby = array("type_asc" => array("key" => "type_asc", "value" => "Type - ASC", "sql" => "ORDER BY tracker_output.type ASC"), "type_desc" => array("key" => "type_desc", "value" => "Type - DESC", "sql" => "ORDER BY tracker_output.type DESC"),"manufacturer_asc" => array("key" => "manufacturer_asc", "value" => "Aircraft Manufacturer - ASC", "sql" => "ORDER BY tracker_output.aircraft_manufacturer ASC"), "manufacturer_desc" => array("key" => "manufacturer_desc", "value" => "Aircraft Manufacturer - DESC", "sql" => "ORDER BY tracker_output.aircraft_manufacturer DESC"),"airline_name_asc" => array("key" => "airline_name_asc", "value" => "Airline Name - ASC", "sql" => "ORDER BY tracker_output.airline_name ASC"), "airline_name_desc" => array("key" => "airline_name_desc", "value" => "Airline Name - DESC", "sql" => "ORDER BY tracker_output.airline_name DESC"), "ident_asc" => array("key" => "ident_asc", "value" => "Ident - ASC", "sql" => "ORDER BY tracker_output.ident ASC"), "ident_desc" => array("key" => "ident_desc", "value" => "Ident - DESC", "sql" => "ORDER BY tracker_output.ident DESC"), "airport_departure_asc" => array("key" => "airport_departure_asc", "value" => "Departure Airport - ASC", "sql" => "ORDER BY tracker_output.departure_airport_city ASC"), "airport_departure_desc" => array("key" => "airport_departure_desc", "value" => "Departure Airport - DESC", "sql" => "ORDER BY tracker_output.departure_airport_city DESC"), "airport_arrival_asc" => array("key" => "airport_arrival_asc", "value" => "Arrival Airport - ASC", "sql" => "ORDER BY tracker_output.arrival_airport_city ASC"), "airport_arrival_desc" => array("key" => "airport_arrival_desc", "value" => "Arrival Airport - DESC", "sql" => "ORDER BY tracker_output.arrival_airport_city DESC"), "date_asc" => array("key" => "date_asc", "value" => "Date - ASC", "sql" => "ORDER BY tracker_output.date ASC"), "date_desc" => array("key" => "date_desc", "value" => "Date - DESC", "sql" => "ORDER BY tracker_output.date DESC"),"distance_asc" => array("key" => "distance_asc","value" => "Distance - ASC","sql" => "ORDER BY distance ASC"),"distance_desc" => array("key" => "distance_desc","value" => "Distance - DESC","sql" => "ORDER BY distance DESC"));
		
		return $orderby;
		
	}
}
?>