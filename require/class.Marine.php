<?php
/**
 * This class is part of FlightAirmap. It's used for marine data
 *
 * Copyright (c) Ycarus (Yannick Chabanois) <support@flightairmap.com>
 * Licensed under AGPL license.
 * For more information see: https://www.flightairmap.com/
*/
require_once(dirname(__FILE__).'/class.Image.php');
$global_marine_query = "SELECT marine_output.* FROM marine_output";

class Marine{
	public $db;
	
	public function __construct($dbc = null) {
		$Connection = new Connection($dbc);
		$this->db = $Connection->db();
		if ($this->db === null) die('Error: No DB connection. (Marine)');
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
					$filter_query_join .= " INNER JOIN (SELECT fammarine_id FROM marine_output WHERE marine_output.ident IN ('".implode("','",$flt['idents'])."') AND marine_output.format_source IN ('".implode("','",$flt['source'])."')) spfi ON spfi.fammarine_id = marine_output.fammarine_id";
				} else {
					$filter_query_join .= " INNER JOIN (SELECT fammarine_id FROM marine_output WHERE marine_output.ident IN ('".implode("','",$flt['idents'])."')) spfi ON spfi.fammarine_id = marine_output.fammarine_id";
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
		if (isset($filter['year']) && $filter['year'] != '') {
			if ($globalDBdriver == 'mysql') {
				$filter_query_where .= " AND YEAR(marine_output.date) = '".$filter['year']."'";
			} else {
				$filter_query_where .= " AND EXTRACT(YEAR FROM marine_output.date) = '".$filter['year']."'";
			}
		}
		if (isset($filter['month']) && $filter['month'] != '') {
			if ($globalDBdriver == 'mysql') {
				$filter_query_where .= " AND MONTH(marine_output.date) = '".$filter['month']."'";
			} else {
				$filter_query_where .= " AND EXTRACT(MONTH FROM marine_output.date) = '".$filter['month']."'";
			}
		}
		if (isset($filter['day']) && $filter['day'] != '') {
			if ($globalDBdriver == 'mysql') {
				$filter_query_where .= " AND DAY(marine_output.date) = '".$filter['day']."'";
			} else {
				$filter_query_where .= " AND EXTRACT(DAY FROM marine_output.date) = '".$filter['day']."'";
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
     * Executes the SQL statements to get the spotter information
     *
     * @param String $query the SQL query
     * @param array $params parameter of the query
     * @param String $limitQuery the limit query
     * @param bool $schedules
     * @return array the spotter information
     */
	public function getDataFromDB($query, $params = array(), $limitQuery = '',$schedules = false)
	{
		global $globalVM;
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
		$spotter_array = array();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$num_rows++;
			$temp_array = array();
			if (isset($row['marine_live_id'])) {
				$temp_array['marine_id'] = $this->getMarineIDBasedOnFamMarineID($row['fammarine_id']);
			/*
			} elseif (isset($row['spotter_archive_id'])) {
				$temp_array['spotter_id'] = $row['spotter_archive_id'];
			} elseif (isset($row['spotter_archive_output_id'])) {
				$temp_array['spotter_id'] = $row['spotter_archive_output_id'];
			*/} 
			elseif (isset($row['marineid'])) {
				$temp_array['marine_id'] = $row['marineid'];
			} else {
				$temp_array['marine_id'] = '';
			}
			if (isset($row['fammarine_id'])) $temp_array['fammarine_id'] = $row['fammarine_id'];
			if (isset($row['mmsi'])) $temp_array['mmsi'] = $row['mmsi'];
			if (isset($row['type'])) $temp_array['type'] = html_entity_decode($row['type'],ENT_QUOTES);
			if (isset($row['type_id'])) $temp_array['type_id'] = $row['type_id'];
			if (isset($row['status'])) $temp_array['status'] = $row['status'];
			if (isset($row['status_id'])) $temp_array['status_id'] = $row['status_id'];
			if (isset($row['captain_id'])) $temp_array['captain_id'] = $row['captain_id'];
			if (isset($row['captain_name'])) $temp_array['captain_name'] = $row['captain_name'];
			if (isset($row['race_id'])) $temp_array['race_id'] = $row['race_id'];
			if (isset($row['race_name'])) $temp_array['race_name'] = $row['race_name'];
			if (isset($row['race_time']) && isset($row['status']) && $row['status'] != 'Racing' && $row['race_time'] > 0) $temp_array['race_time'] = $row['race_time'];
			if (isset($row['race_rank'])) $temp_array['race_rank'] = $row['race_rank'];
			if (isset($row['ident'])) $temp_array['ident'] = $row['ident'];
			if (isset($row['arrival_port_name'])) $temp_array['arrival_port_name'] = $row['arrival_port_name'];
			if (isset($row['latitude'])) $temp_array['latitude'] = $row['latitude'];
			if (isset($row['longitude'])) $temp_array['longitude'] = $row['longitude'];
			if (isset($row['distance']) && $row['distance'] != '') $temp_array['distance'] = $row['distance'];
			if (isset($row['format_source'])) $temp_array['format_source'] = $row['format_source'];
			if (isset($row['heading'])) {
				$temp_array['heading'] = $row['heading'];
				$heading_direction = $this->parseDirection($row['heading']);
				if (isset($heading_direction[0]['direction_fullname'])) $temp_array['heading_name'] = $heading_direction[0]['direction_fullname'];
			}
			if (isset($row['ground_speed'])) $temp_array['ground_speed'] = $row['ground_speed'];

			if(isset($temp_array['mmsi']) && $temp_array['mmsi'] != "")
			{
				$Image = new Image($this->db);
				if (isset($temp_array['ident']) && $temp_array['ident'] != '') $image_array = $Image->getMarineImage($temp_array['mmsi'],'',$temp_array['ident']);
				else $image_array = $Image->getMarineImage($temp_array['mmsi']);
				unset($Image);
				if (count($image_array) > 0) {
					$temp_array['image'] = $image_array[0]['image'];
					$temp_array['image_thumbnail'] = $image_array[0]['image_thumbnail'];
					$temp_array['image_source'] = $image_array[0]['image_source'];
					$temp_array['image_source_website'] = $image_array[0]['image_source_website'];
					$temp_array['image_copyright'] = $image_array[0]['image_copyright'];
				}
			} elseif(isset($temp_array['type']) && $temp_array['type'] != "")
			{
				$Image = new Image($this->db);
				$image_array = $Image->getMarineImage('','','',$temp_array['type']);
				unset($Image);
				if (count($image_array) > 0) {
					$temp_array['image'] = $image_array[0]['image'];
					$temp_array['image_thumbnail'] = $image_array[0]['image_thumbnail'];
					$temp_array['image_source'] = $image_array[0]['image_source'];
					$temp_array['image_source_website'] = $image_array[0]['image_source_website'];
					$temp_array['image_copyright'] = $image_array[0]['image_copyright'];
				}
			}
			
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
			$spotter_array[] = $temp_array;
		}
		if ($num_rows == 0) return array();
		$spotter_array[0]['query_number_rows'] = $num_rows;
		return $spotter_array;
	}


    /**
     * Gets all the spotter information based on the latest data entry
     *
     * @param string $limit
     * @param string $sort
     * @param array $filter
     * @return array the spotter information
     */
	public function getLatestMarineData($limit = '', $sort = '', $filter = array())
	{
		global $global_marine_query;
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
			$orderby_query = " ORDER BY marine_output.date DESC";
		}
		$query  = $global_marine_query.$filter_query." ".$orderby_query;
		$spotter_array = $this->getDataFromDB($query, array(),$limit_query,true);
		return $spotter_array;
	}
    
	/*
	* Gets all the spotter information based on the spotter id
	*
     * @param string $id
     * @return array the spotter information
     */
    public function getMarineDataByID($id = '')
	{
		global $global_marine_query;
		
		date_default_timezone_set('UTC');
		if ($id == '') return array();
		$additional_query = "marine_output.fammarine_id = :id";
		$query_values = array(':id' => $id);
		$query  = $global_marine_query." WHERE ".$additional_query." ";
		$spotter_array = $this->getDataFromDB($query,$query_values);
		return $spotter_array;
	}

    /**
     * Gets all the spotter information based on the callsign
     *
     * @param string $ident
     * @param string $limit
     * @param string $sort
     * @param array $filter
     * @return array the spotter information
     */
	public function getMarineDataByIdent($ident = '', $limit = '', $sort = '', $filter = array())
	{
		global $global_marine_query;
		
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
				$additional_query = " marine_output.ident = :ident";
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
			$orderby_query = " ORDER BY marine_output.date DESC";
		}

		$query = $global_marine_query.$filter_query." ".$additional_query." ".$orderby_query;
		//echo $query."\n";
		$spotter_array = $this->getDataFromDB($query, $query_values, $limit_query);

		return $spotter_array;
	}

    /**
     * Gets all the marine information based on the type
     *
     * @param string $type
     * @param string $limit
     * @param string $sort
     * @param array $filter
     * @return array the marine information
     */
	public function getMarineDataByType($type = '', $limit = '', $sort = '', $filter = array())
	{
		global $global_marine_query;
		
		date_default_timezone_set('UTC');
		
		$limit_query = '';
		$filter_query = $this->getFilter($filter,true,true);
		if (!is_string($type))
		{
			return array();
		} else {
			$additional_query = " AND marine_output.type_id = :type";
			$query_values = array(':type' => $type);
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
			$orderby_query = " ORDER BY marine_output.date DESC";
		}

		$query = $global_marine_query.$filter_query." marine_output.type <> '' ".$additional_query." ".$orderby_query;
		//echo $query."\n";
		$spotter_array = $this->getDataFromDB($query, $query_values, $limit_query);

		return $spotter_array;
	}

    /**
     * @param string $date
     * @param string $limit
     * @param string $sort
     * @param array $filter
     * @return array
     */
    public function getMarineDataByDate($date = '', $limit = '', $sort = '', $filter = array())
	{
		global $global_marine_query, $globalTimezone, $globalDBdriver;
		
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
				$additional_query = " AND DATE(CONVERT_TZ(marine_output.date,'+00:00', :offset)) = :date ";
				$query_values = array(':date' => $datetime->format('Y-m-d'), ':offset' => $offset);
			} elseif ($globalDBdriver == 'pgsql') {
				$additional_query = " AND to_char(marine_output.date AT TIME ZONE :timezone,'YYYY-mm-dd') = :date ";
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
			$orderby_query = " ORDER BY marine_output.date DESC";
		}

		$query = $global_marine_query.$filter_query." marine_output.ident <> '' ".$additional_query.$orderby_query;
		$spotter_array = $this->getDataFromDB($query, $query_values, $limit_query);
		return $spotter_array;
	}

    /**
     * Gets all the marine information based on the captain
     *
     * @param string $captain
     * @param string $limit
     * @param string $sort
     * @param array $filter
     * @return array the marine information
     */
	public function getMarineDataByCaptain($captain = '', $limit = '', $sort = '', $filter = array())
	{
		global $global_marine_query;
		date_default_timezone_set('UTC');
		$query_values = array();
		$limit_query = '';
		$additional_query = '';
		$filter_query = $this->getFilter($filter,true,true);
		$captain = filter_var($captain,FILTER_SANITIZE_STRING);
		if ($captain != "")
		{
			$additional_query = " AND (marine_output.captain_name = :captain OR marine_output.captain_id = :captain)";
			$query_values = array(':captain' => $captain);
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
			$orderby_query = " ORDER BY marine_output.date DESC";
		}
		$query = $global_marine_query.$filter_query." marine_output.captain_name <> '' ".$additional_query." ".$orderby_query;
		$spotter_array = $this->getDataFromDB($query, $query_values, $limit_query);
		return $spotter_array;
	}

    /**
     * Gets all the marine information based on the race
     *
     * @param string $race
     * @param string $limit
     * @param string $sort
     * @param array $filter
     * @return array the marine information
     */
	public function getMarineDataByRace($race = '', $limit = '', $sort = '', $filter = array())
	{
		global $global_marine_query,$globalDBdriver;
		date_default_timezone_set('UTC');
		$query_values = array();
		$limit_query = '';
		$additional_query = '';
		$filter_query = $this->getFilter($filter,true,true);
		$race = filter_var($race,FILTER_SANITIZE_STRING);
		if ($race != "")
		{
			$additional_query = " AND (marine_output.race_name = :race OR marine_output.race_id = :race)";
			$query_values = array(':race' => $race);
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
			if ($globalDBdriver == 'mysql') {
				$orderby_query = " ORDER BY -marine_output.race_rank DESC, marine_output.distance ASC";
			} else {
				$orderby_query = " ORDER BY marine_output.race_rank ASC, marine_output.distance ASC";
			}
		}
		$query = $global_marine_query.$filter_query." marine_output.race_name <> '' ".$additional_query." ".$orderby_query;
		$spotter_array = $this->getDataFromDB($query, $query_values, $limit_query);
		return $spotter_array;
	}

    /**
     * Count races by captain
     *
     * @param $captain
     * @param array $filters
     * @return Integer number of race for a captain
     */
	public function countRacesByCaptain($captain,$filters = array())
	{
		$captain = filter_var($captain,FILTER_SANITIZE_STRING);
		$filter_query = $this->getFilter($filters,true,true);
		$query  = "SELECT COUNT(*) AS nb 
			FROM marine_output".$filter_query." (marine_output.captain_name = :captain OR marine_output.captain_id = :captain)";
		$query_values = array();
		$query_values = array_merge($query_values,array(':captain' => $captain));
		$sth = $this->db->prepare($query);
		$sth->execute($query_values);
		$result = $sth->fetchAll(PDO::FETCH_ASSOC);
		return $result[0]['nb'];
	}

    /**
     * Count captains by race
     *
     * @param $race
     * @param array $filters
     * @return String Duration of all races
     */
	public function countCaptainsByRace($race,$filters = array())
	{
		$race = filter_var($race,FILTER_SANITIZE_STRING);
		$filter_query = $this->getFilter($filters,true,true);
		$query  = "SELECT COUNT(*) AS nb 
			FROM marine_output".$filter_query." (marine_output.race_name = :race OR marine_output.race_id = :race)";
		$query_values = array();
		$query_values = array_merge($query_values,array(':race' => $race));
		$sth = $this->db->prepare($query);
		$sth->execute($query_values);
		$result = $sth->fetchAll(PDO::FETCH_ASSOC);
		return $result[0]['nb'];
	}

    /**
     * Gets all boat types that have been used by a captain
     *
     * @param $captain
     * @param array $filters
     * @param string $year
     * @param string $month
     * @param string $day
     * @return array the boat list
     */
	public function countAllBoatTypesByCaptain($captain,$filters = array(),$year = '',$month = '',$day = '')
	{
		global $globalDBdriver;
		$filter_query = $this->getFilter($filters,true,true);
		$captain = filter_var($captain,FILTER_SANITIZE_STRING);
		$query  = "SELECT DISTINCT marine_output.type, COUNT(marine_output.type) AS type_count
			FROM marine_output".$filter_query." (marine_output.captain_id = :captain OR marine_output.captain_name = :captain)";
		$query_values = array();
		if ($year != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND YEAR(marine_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			} else {
				$query .= " AND EXTRACT(YEAR FROM marine_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			}
		}
		if ($month != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND MONTH(marine_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			} else {
				$query .= " AND EXTRACT(MONTH FROM marine_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			}
		}
		if ($day != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND DAY(marine_output.date) = :day";
				$query_values = array_merge($query_values,array(':day' => $day));
			} else {
				$query .= " AND EXTRACT(DAY FROM marine_output.date) = :day";
				$query_values = array_merge($query_values,array(':day' => $day));
			}
		}
		$query .= " GROUP BY marine_output.type
			ORDER BY type_count DESC";
		$query_values = array_merge($query_values,array(':captain' => $captain));
		$sth = $this->db->prepare($query);
		$sth->execute($query_values);
		return $sth->fetchAll(PDO::FETCH_ASSOC);
	}

    /**
     * Gets all boat types that have been used on a race
     *
     * @param $race
     * @param array $filters
     * @param string $year
     * @param string $month
     * @param string $day
     * @return array the boat list
     */
	public function countAllBoatTypesByRace($race,$filters = array(),$year = '',$month = '',$day = '')
	{
		global $globalDBdriver;
		$filter_query = $this->getFilter($filters,true,true);
		$race = filter_var($race,FILTER_SANITIZE_STRING);
		$query  = "SELECT DISTINCT marine_output.type, COUNT(marine_output.type) AS type_count
			FROM marine_output".$filter_query." (marine_output.race_id = :race OR marine_output.race_name = :race)";
		$query_values = array();
		if ($year != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND YEAR(marine_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			} else {
				$query .= " AND EXTRACT(YEAR FROM marine_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			}
		}
		if ($month != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND MONTH(marine_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			} else {
				$query .= " AND EXTRACT(MONTH FROM marine_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			}
		}
		if ($day != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND DAY(marine_output.date) = :day";
				$query_values = array_merge($query_values,array(':day' => $day));
			} else {
				$query .= " AND EXTRACT(DAY FROM marine_output.date) = :day";
				$query_values = array_merge($query_values,array(':day' => $day));
			}
		}
		$query .= " GROUP BY marine_output.type
			ORDER BY type_count DESC";
		$query_values = array_merge($query_values,array(':race' => $race));
		$sth = $this->db->prepare($query);
		$sth->execute($query_values);
		return $sth->fetchAll(PDO::FETCH_ASSOC);
	}

    /**
     * Gets race duration by captain
     *
     * @param $captain
     * @param array $filters
     * @param string $year
     * @param string $month
     * @param string $day
     * @return String Duration of all race
     */
	public function getRaceDurationByCaptain($captain,$filters = array(),$year = '',$month = '',$day = '')
	{
		global $globalDBdriver;
		$captain = filter_var($captain,FILTER_SANITIZE_STRING);
		$filter_query = $this->getFilter($filters,true,true);
		$query  = "SELECT SUM(last_seen - date) AS duration 
		    FROM marine_output".$filter_query." (marine_output.captain_name = :captain OR marine_output.captain_id = :captain) 
		    AND last_seen > date";
		$query_values = array();
		if ($year != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND YEAR(marine_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			} else {
				$query .= " AND EXTRACT(YEAR FROM marine_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			}
		}
		if ($month != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND MONTH(marine_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			} else {
				$query .= " AND EXTRACT(MONTH FROM marine_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			}
		}
		if ($day != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND DAY(marine_output.date) = :day";
				$query_values = array_merge($query_values,array(':day' => $day));
			} else {
				$query .= " AND EXTRACT(DAY FROM marine_output.date) = :day";
				$query_values = array_merge($query_values,array(':day' => $day));
			}
		}
		$query_values = array_merge($query_values,array(':captain' => $captain));
		$sth = $this->db->prepare($query);
		$sth->execute($query_values);
		$result = $sth->fetchAll(PDO::FETCH_ASSOC);
		if (is_int($result[0]['duration'])) return gmdate('H:i:s',$result[0]['duration']);
		else return $result[0]['duration'];
	}

    /**
     * Gets race duration by captains
     *
     * @param bool $limit
     * @param array $filters
     * @param string $year
     * @param string $month
     * @param string $day
     * @return array Duration of all race
     */
	public function getRaceDurationByCaptains($limit = true,$filters = array(),$year = '',$month = '',$day = '')
	{
		global $globalDBdriver;
		$filter_query = $this->getFilter($filters,true,true);
		$query  = "SELECT SUM(last_seen - date) AS duration, captain_id, captain_name 
		    FROM marine_output".$filter_query." last_seen > date";
		$query_values = array();
		if ($year != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND YEAR(marine_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			} else {
				$query .= " AND EXTRACT(YEAR FROM marine_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			}
		}
		if ($month != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND MONTH(marine_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			} else {
				$query .= " AND EXTRACT(MONTH FROM marine_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			}
		}
		if ($day != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND DAY(marine_output.date) = :day";
				$query_values = array_merge($query_values,array(':day' => $day));
			} else {
				$query .= " AND EXTRACT(DAY FROM marine_output.date) = :day";
				$query_values = array_merge($query_values,array(':day' => $day));
			}
		}
		$query .= " GROUP BY marine_output.captain_id,marine_output.captain_name ORDER BY duration DESC";
		if ($limit) $query .= " LIMIT 10 OFFSET 0";
		$sth = $this->db->prepare($query);
		$sth->execute($query_values);
		//if (is_int($result[0]['duration'])) return gmdate('H:i:s',$result[0]['duration']);
		//else return $result[0]['duration'];
		$duration_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			if ($row['duration'] != '') {
				$temp_array['marine_duration_days'] = $row['duration'];
				//$temp_array['marine_duration'] = strtotime($row['duration']);
				$temp_array['marine_captain_id'] = $row['captain_id'];
				$temp_array['marine_captain_name'] = $row['captain_name'];
				$duration_array[] = $temp_array;
			}
		}
		return $duration_array;

	}

    /**
     * Gets a list of all captain names and captain ids
     *
     * @param array $filters
     * @return array list of captain names and captain ids
     */
	public function getAllCaptainNames($filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$query  = "SELECT DISTINCT marine_output.captain_name, marine_output.captain_id
			FROM marine_output".$filter_query." marine_output.captain_name <> '' 
			ORDER BY marine_output.captain_name ASC";
	
		$sth = $this->db->prepare($query);
		$sth->execute();
		return $sth->fetchAll(PDO::FETCH_ASSOC);
	}

    /**
     * Gets a list of all race names and race ids
     *
     * @param array $filters
     * @return array list of race names and race ids
     */
	public function getAllRaceNames($filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$query  = "SELECT DISTINCT marine_output.race_name, marine_output.race_id
			FROM marine_output".$filter_query." marine_output.race_name <> '' 
			ORDER BY marine_output.race_name ASC";
	
		$sth = $this->db->prepare($query);
		$sth->execute();
		return $sth->fetchAll(PDO::FETCH_ASSOC);
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
		$query  = "SELECT DISTINCT marine_output.source_name 
				FROM marine_output".$filter_query." marine_output.source_name <> ''";
		if ($type != '') {
			$query_values = array(':type' => $type);
			$query .= " AND format_source = :type";
		}
		$query .= " ORDER BY marine_output.source_name ASC";

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
		$query  = "SELECT DISTINCT marine_output.ident
								FROM marine_output".$filter_query." marine_output.ident <> '' 
								ORDER BY marine_output.date ASC LIMIT 700 OFFSET 0";

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

    /**
     * Gets all info from a mmsi
     *
     * @param $mmsi
     * @return array ident
     */
	public function getIdentity($mmsi)
	{
		$mmsi = filter_var($mmsi,FILTER_SANITIZE_NUMBER_INT);
		$query  = "SELECT * FROM marine_identity WHERE mmsi = :mmsi LIMIT 1";
		$sth = $this->db->prepare($query);
		$sth->execute(array(':mmsi' => $mmsi));
		$result = $sth->fetchAll(PDO::FETCH_ASSOC);
		if (isset($result[0])) return $result[0];
		else return array();
	}

    /**
     * Add identity
     * @param $mmsi
     * @param $imo
     * @param $ident
     * @param $callsign
     * @param $type
     */
	public function addIdentity($mmsi,$imo,$ident,$callsign,$type)
	{
		$mmsi = filter_var($mmsi,FILTER_SANITIZE_NUMBER_INT);
		if ($mmsi != '') {
			$imo = filter_var($imo,FILTER_SANITIZE_NUMBER_INT);
			$ident = filter_var($ident,FILTER_SANITIZE_STRING);
			$callsign = filter_var($callsign,FILTER_SANITIZE_STRING);
			$type = filter_var($type,FILTER_SANITIZE_STRING);
			$identinfo = $this->getIdentity($mmsi);
			if (empty($identinfo)) {
				$query  = "INSERT INTO marine_identity (mmsi,imo,call_sign,ship_name,type) VALUES (:mmsi,:imo,:call_sign,:ship_name,:type)";
				$sth = $this->db->prepare($query);
				$sth->execute(array(':mmsi' => $mmsi,':imo' => $imo,':call_sign' => $callsign,':ship_name' => $ident,':type' => $type));
			} elseif ($ident != '' && $identinfo['ship_name'] != $ident) {
				$query  = "UPDATE marine_identity SET ship_name = :ship_name,type = :type WHERE mmsi = :mmsi";
				$sth = $this->db->prepare($query);
				$sth->execute(array(':mmsi' => $mmsi,':ship_name' => $ident,':type' => $type));
			}
		}
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
			$query  = "SELECT DISTINCT DATE(CONVERT_TZ(marine_output.date,'+00:00', :offset)) as date
								FROM marine_output
								WHERE marine_output.date <> '' 
								ORDER BY marine_output.date ASC LIMIT 0,100";
		} else {
			$query  = "SELECT DISTINCT to_char(marine_output.date AT TIME ZONE INTERVAL :offset,'YYYY-mm-dd') as date
								FROM marine_output
								WHERE marine_output.date <> '' 
								ORDER BY marine_output.date ASC LIMIT 0,100";
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
	* @param String $fammarine_id the ID
	* @param String $ident the marine ident
	* @return String success or false
	*
	*/
	public function updateIdentMarineData($fammarine_id = '', $ident = '',$fromsource = NULL)
	{
		$query = 'UPDATE marine_output SET ident = :ident WHERE fammarine_id = :fammarine_id';
		$query_values = array(':fammarine_id' => $fammarine_id,':ident' => $ident);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch (PDOException $e) {
			return "error : ".$e->getMessage();
		}
		return "success";
	}

	/**
	* Update arrival marine data
	*
	* @param String $fammarine_id the ID
	* @param String $arrival_code the marine ident
	* @return String success or false
	*
	*/
	public function updateArrivalPortNameMarineData($fammarine_id = '', $arrival_code = '',$fromsource = NULL)
	{
		$query = 'UPDATE marine_output SET arrival_port_name = :arrival_code WHERE fammarine_id = :fammarine_id';
		$query_values = array(':fammarine_id' => $fammarine_id,':arrival_code' => $arrival_code);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch (PDOException $e) {
			return "error : ".$e->getMessage();
		}
		return "success";
	}

	/**
	* Update Status data
	*
	* @param String $fammarine_id the ID
	* @param String $status_id the marine status id
	* @param String $status the marine status
	* @return String success or false
	*
	*/
	public function updateStatusMarineData($fammarine_id = '', $status_id = '',$status = '')
	{

		$query = 'UPDATE marine_output SET status = :status, status_id = :status_id WHERE fammarine_id = :fammarine_id';
                $query_values = array(':fammarine_id' => $fammarine_id,':status' => $status,':status_id' => $status_id);

		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch (PDOException $e) {
			return "error : ".$e->getMessage();
		}
		
		return "success";

	}

    /**
     * Update latest marine data
     *
     * @param String $fammarine_id the ID
     * @param String $ident the marine ident
     * @param string $latitude
     * @param string $longitude
     * @param float $groundspeed
     * @param string $date
     * @param float $distance
     * @param integer $race_rank
     * @param integer $race_time
     * @param string $status
     * @param string $race_begin
     * @return String success or false
     */
	public function updateLatestMarineData($fammarine_id = '', $ident = '', $latitude = '', $longitude = '', $groundspeed = NULL, $date = '',$distance = NULL,$race_rank = NULL, $race_time = NULL, $status = '', $race_begin = '')
	{
		if ($latitude == '') $latitude = NULL;
		if ($longitude == '') $longitude = NULL;
		$groundspeed = round($groundspeed);
		if ($race_begin != '') {
			$query = 'UPDATE marine_output SET ident = :ident, last_latitude = :last_latitude, last_longitude = :last_longitude, last_seen = :last_seen, last_ground_speed = :last_ground_speed, distance = :distance, race_rank = :race_rank, race_time = :race_time, status = :status, date = :race_begin WHERE fammarine_id = :fammarine_id';
			$query_values = array(':fammarine_id' => $fammarine_id,':last_latitude' => $latitude,':last_longitude' => $longitude, ':last_ground_speed' => $groundspeed,':last_seen' => $date,':ident' => $ident,':distance' => $distance,':race_rank' => $race_rank,':race_time' => $race_time,':status' => $status,':race_begin' => $race_begin);
		} else {
			$query = 'UPDATE marine_output SET ident = :ident, last_latitude = :last_latitude, last_longitude = :last_longitude, last_seen = :last_seen, last_ground_speed = :last_ground_speed, distance = :distance, race_rank = :race_rank, race_time = :race_time, status = :status WHERE fammarine_id = :fammarine_id';
			$query_values = array(':fammarine_id' => $fammarine_id,':last_latitude' => $latitude,':last_longitude' => $longitude, ':last_ground_speed' => $groundspeed,':last_seen' => $date,':ident' => $ident,':distance' => $distance,':race_rank' => $race_rank,':race_time' => $race_time,':status' => $status);
		}
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch (PDOException $e) {
			echo "error : ".$e->getMessage();
			return "error : ".$e->getMessage();
		}
		
		return "success";

	}

    /**
     * Adds a new marine data
     *
     * @param String $fammarine_id the ID
     * @param String $ident the marine ident
     * @param String $latitude latitude of flight
     * @param String $longitude latitude of flight
     * @param String $heading heading of flight
     * @param String $groundspeed speed of flight
     * @param String $date date of flight
     * @param string $mmsi
     * @param string $type
     * @param string $typeid
     * @param string $imo
     * @param string $callsign
     * @param string $arrival_code
     * @param string $arrival_date
     * @param string $status
     * @param string $statusid
     * @param string $format_source
     * @param string $source_name
     * @param string $captain_id
     * @param string $captain_name
     * @param string $race_id
     * @param string $race_name
     * @param string $distance
     * @param string $race_rank
     * @param string $race_time
     * @return String success or false
     */
	public function addMarineData($fammarine_id = '', $ident = '', $latitude = '', $longitude = '', $heading = '', $groundspeed = '', $date = '', $mmsi = '',$type = '',$typeid = '',$imo = '',$callsign = '',$arrival_code = '',$arrival_date = '',$status = '',$statusid = '',$format_source = '', $source_name = '', $captain_id = '',$captain_name = '',$race_id = '', $race_name = '', $distance = '',$race_rank = '', $race_time = '')
	{
		global $globalMarineImageFetch;
		
		//$Image = new Image($this->db);
		$Common = new Common();
		
		date_default_timezone_set('UTC');
		
		//getting the registration
		if ($fammarine_id != "")
		{
			if (!is_string($fammarine_id))
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
		
		if ($heading != "")
		{
			if (!is_numeric($heading))
			{
				return false;
			}
		}
		if ($mmsi != "")
		{
			if (!is_numeric($mmsi))
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

    
		if ($date == "")
		{
			$date = date("Y-m-d H:i:s", time());
		}

		$fammarine_id = filter_var($fammarine_id,FILTER_SANITIZE_STRING);
		$ident = filter_var($ident,FILTER_SANITIZE_STRING);
		$latitude = filter_var($latitude,FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
		$longitude = filter_var($longitude,FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
		$heading = filter_var($heading,FILTER_SANITIZE_NUMBER_INT);
		$groundspeed = filter_var($groundspeed,FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
		$format_source = filter_var($format_source,FILTER_SANITIZE_STRING);
		$mmsi = filter_var($mmsi,FILTER_SANITIZE_STRING);
		$type = filter_var($type,FILTER_SANITIZE_STRING);
		$status = filter_var($status,FILTER_SANITIZE_STRING);
		$type_id = filter_var($typeid,FILTER_SANITIZE_NUMBER_INT);
		$status_id = filter_var($statusid,FILTER_SANITIZE_NUMBER_INT);
		$imo = filter_var($imo,FILTER_SANITIZE_STRING);
		$callsign = filter_var($callsign,FILTER_SANITIZE_STRING);
		$arrival_code = filter_var($arrival_code,FILTER_SANITIZE_STRING);
		$arrival_date = filter_var($arrival_date,FILTER_SANITIZE_STRING);
		$captain_id = filter_var($captain_id,FILTER_SANITIZE_STRING);
		$captain_name = filter_var($captain_name,FILTER_SANITIZE_STRING);
		$race_id = filter_var($race_id,FILTER_SANITIZE_STRING);
		$race_name = filter_var($race_name,FILTER_SANITIZE_STRING);
		$race_rank = filter_var($race_rank,FILTER_SANITIZE_NUMBER_INT);
		$race_time = filter_var($race_time,FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
		$distance = filter_var($distance,FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
		if (isset($globalMarineImageFetch) && $globalMarineImageFetch === TRUE) {
			$Image = new Image($this->db);
			$image_array = $Image->getMarineImage($mmsi,$imo,$ident);
			if (!isset($image_array[0]['mmsi'])) {
				$Image->addMarineImage($mmsi,$imo,$ident);
			}
			unset($Image);
		}
		if ($latitude == '' && $longitude == '') {
			$latitude = 0;
			$longitude = 0;
		}
		if ($type_id == '') $type_id = NULL;
		if ($status_id == '') $status_id = NULL;
		if ($distance == '') $distance = NULL;
		if ($race_rank == '') $race_rank = NULL;
		if ($race_time == '') $race_time = NULL;
		if ($heading == '' || $Common->isInteger($heading) === false) $heading = 0;
		//if ($groundspeed == '' || $Common->isInteger($groundspeed) === false) $groundspeed = 0;
		if ($arrival_date == '') $arrival_date = NULL;
		$query  = "INSERT INTO marine_output (fammarine_id, ident, latitude, longitude, heading, ground_speed, date, format_source, source_name, mmsi, type, type_id, status,status_id,imo,arrival_port_name,arrival_port_date,captain_id,captain_name,race_id,race_name, distance, race_rank,race_time) 
		    VALUES (:fammarine_id,:ident,:latitude,:longitude,:heading,:speed,:date,:format_source, :source_name,:mmsi,:type,:type_id,:status,:status_id,:imo,:arrival_port_name,:arrival_port_date,:captain_id,:captain_name,:race_id,:race_name, :distance, :race_rank,:race_time)";

		$query_values = array(':fammarine_id' => $fammarine_id,':ident' => $ident,':latitude' => $latitude,':longitude' => $longitude,':heading' => $heading,':speed' => $groundspeed,':date' => $date,':format_source' => $format_source, ':source_name' => $source_name,':mmsi' => $mmsi,':type' => $type,':type_id' => $type_id,':status' => $status,':status_id' => $status_id,':imo' => $imo,':arrival_port_name' => $arrival_code,':arrival_port_date' => $arrival_date,':captain_id' => $captain_id,':captain_name' => $captain_name,':race_id' => $race_id,':race_name' => $race_name,':distance' => $distance,':race_rank' => $race_rank,':race_time' => $race_time);
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
		global $globalDBdriver, $globalTimezone;
		if ($globalDBdriver == 'mysql') {
			$query  = "SELECT marine_output.ident FROM marine_output 
								WHERE marine_output.ident = :ident 
								AND marine_output.date >= DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 HOUR) 
								AND marine_output.date < UTC_TIMESTAMP()";
			$query_data = array(':ident' => $ident);
		} else {
			$query  = "SELECT marine_output.ident FROM marine_output 
								WHERE marine_output.ident = :ident 
								AND marine_output.date >= now() AT TIME ZONE 'UTC' - INTERVAL '1 HOURS'
								AND marine_output.date < now() AT TIME ZONE 'UTC'";
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
     * @return array the marine data
     */
	public function getRealTimeData($q = '')
	{
		global $globalDBdriver;
		$additional_query = '';
		if ($q != "")
		{
			if (!is_string($q))
			{
				return false;
			} else {
				$q_array = explode(" ", $q);
				foreach ($q_array as $q_item){
					$q_item = filter_var($q_item,FILTER_SANITIZE_STRING);
					$additional_query .= " AND (";
					$additional_query .= "(marine_output.ident like '%".$q_item."%')";
					$additional_query .= ")";
				}
			}
		}
		if ($globalDBdriver == 'mysql') {
			$query  = "SELECT marine_output.* FROM marine_output 
				WHERE marine_output.date >= DATE_SUB(UTC_TIMESTAMP(),INTERVAL 20 SECOND) ".$additional_query." 
				AND marine_output.date < UTC_TIMESTAMP()";
		} else {
			$query  = "SELECT marine_output.* FROM marine_output 
				WHERE marine_output.date::timestamp >= CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '20 SECONDS' ".$additional_query." 
				AND marine_output.date::timestamp < CURRENT_TIMESTAMP AT TIME ZONE 'UTC'";
		}
		$marine_array = $this->getDataFromDB($query, array());

		return $marine_array;
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

	public function countAllMarineOverCountries($limit = true,$olderthanmonths = 0,$sincedate = '',$filters = array())
	{
		global $globalDBdriver, $globalArchive;
		//$filter_query = $this->getFilter($filters,true,true);
		$Connection= new Connection($this->db);
		if (!$Connection->tableExists('countries')) return array();
		require_once('class.SpotterLive.php');
		if (!isset($globalArchive) || $globalArchive !== TRUE) {
			$MarineLive = new MarineLive($this->db);
			$filter_query = $MarineLive->getFilter($filters,true,true);
			$filter_query .= " over_country IS NOT NULL AND over_country <> ''";
			if ($olderthanmonths > 0) {
				if ($globalDBdriver == 'mysql') {
					$filter_query .= ' AND marine_live.date < DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.$olderthanmonths.' MONTH) ';
				} else {
					$filter_query .= " AND marine_live.date < CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$olderthanmonths." MONTHS'";
				}
			}
			if ($sincedate != '') {
				if ($globalDBdriver == 'mysql') {
					$filter_query .= " AND marine_live.date > '".$sincedate."' ";
				} else {
					$filter_query .= " AND marine_live.date > CAST('".$sincedate."' AS TIMESTAMP)";
				}
			}
			$query = "SELECT c.name, c.iso3, c.iso2, count(c.name) as nb FROM countries c INNER JOIN (SELECT DISTINCT fammarine_id,over_country FROM marine_live".$filter_query.") l ON c.iso2 = l.over_country ";
		} else {
			require_once(dirname(__FILE__)."/class.MarineArchive.php");
			$MarineArchive = new MarineArchive($this->db);
			$filter_query = $MarineArchive->getFilter($filters,true,true);
			$filter_query .= " over_country <> ''";
			if ($olderthanmonths > 0) {
				if ($globalDBdriver == 'mysql') {
					$filter_query .= ' AND marine_archive.date < DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.$olderthanmonths.' MONTH) ';
				} else {
					$filter_query .= " AND marine_archive.date < CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$olderthanmonths." MONTHS'";
				}
			}
			if ($sincedate != '') {
				if ($globalDBdriver == 'mysql') {
					$filter_query .= " AND marine_archive.date > '".$sincedate."' ";
				} else {
					$filter_query .= " AND marine_archive.date > CAST('".$sincedate."' AS TIMESTAMP)";
				}
			}
			$filter_query .= " LIMIT 200 OFFSET 0";
			$query = "SELECT c.name, c.iso3, c.iso2, count(c.name) as nb FROM countries c INNER JOIN (SELECT DISTINCT fammarine_id,over_country FROM marine_archive".$filter_query.") l ON c.iso2 = l.over_country ";
		}
		$query .= "GROUP BY c.name,c.iso3,c.iso2 ORDER BY nb DESC";
		if ($limit) $query .= " LIMIT 10 OFFSET 0";

		$sth = $this->db->prepare($query);
		$sth->execute();
 
		$flight_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['marine_count'] = $row['nb'];
			$temp_array['marine_country'] = $row['name'];
			$temp_array['marine_country_iso3'] = $row['iso3'];
			$temp_array['marine_country_iso2'] = $row['iso2'];
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
		$query  = "SELECT DISTINCT marine_output.ident, COUNT(marine_output.ident) AS callsign_icao_count 
                    FROM marine_output".$filter_query." marine_output.ident <> ''";
		 if ($olderthanmonths > 0) {
			if ($globalDBdriver == 'mysql') $query .= ' AND date < DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.$olderthanmonths.' MONTH)';
			else $query .= " AND marine_output.date < CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$olderthanmonths." MONTHS'";
		}
		if ($sincedate != '') {
			if ($globalDBdriver == 'mysql') $query .= " AND marine_output.date > '".$sincedate."'";
			else $query .= " AND marine_output.date > CAST('".$sincedate."' AS TIMESTAMP)";
		}
		$query_values = array();
		if ($year != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND YEAR(marine_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			} else {
				$query .= " AND EXTRACT(YEAR FROM marine_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			}
		}
		if ($month != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND MONTH(marine_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			} else {
				$query .= " AND EXTRACT(MONTH FROM marine_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			}
		}
		if ($day != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND DAY(marine_output.date) = :day";
				$query_values = array_merge($query_values,array(':day' => $day));
			} else {
				$query .= " AND EXTRACT(DAY FROM marine_output.date) = :day";
				$query_values = array_merge($query_values,array(':day' => $day));
			}
		}
		$query .= " GROUP BY marine_output.ident ORDER BY callsign_icao_count DESC";
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
			$query  = "SELECT DATE(CONVERT_TZ(marine_output.date,'+00:00', :offset)) AS date_name, count(*) as date_count
								FROM marine_output";
			$query .= $this->getFilter($filters);
			$query .= " GROUP BY date_name 
								ORDER BY date_count DESC
								LIMIT 10 OFFSET 0";
		} else {
			$query  = "SELECT to_char(marine_output.date AT TIME ZONE INTERVAL :offset,'YYYY-mm-dd') AS date_name, count(*) as date_count
								FROM marine_output";
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
			$query  = "SELECT DATE(CONVERT_TZ(marine_output.date,'+00:00', :offset)) AS date_name, count(*) as date_count
								FROM marine_output".$filter_query." marine_output.date >= DATE_SUB(UTC_TIMESTAMP(),INTERVAL 7 DAY)";
			$query .= " GROUP BY date_name 
								ORDER BY marine_output.date ASC";
			$query_data = array(':offset' => $offset);
		} else {
			$query  = "SELECT to_char(marine_output.date AT TIME ZONE INTERVAL :offset,'YYYY-mm-dd') AS date_name, count(*) as date_count
								FROM marine_output".$filter_query." marine_output.date >= CURRENT_TIMESTAMP AT TIME ZONE INTERVAL :offset - INTERVAL '7 DAYS'";
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
			$query  = "SELECT DATE(CONVERT_TZ(marine_output.date,'+00:00', :offset)) AS date_name, count(*) as date_count
								FROM marine_output".$filter_query." marine_output.date >= DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 MONTH)";
			$query .= " GROUP BY date_name 
								ORDER BY marine_output.date ASC";
			$query_data = array(':offset' => $offset);
		} else {
			$query  = "SELECT to_char(marine_output.date AT TIME ZONE INTERVAL :offset,'YYYY-mm-dd') AS date_name, count(*) as date_count
								FROM marine_output".$filter_query." marine_output.date >= CURRENT_TIMESTAMP AT TIME ZONE INTERVAL :offset - INTERVAL '1 MONTHS'";
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
			$query  = "SELECT YEAR(CONVERT_TZ(marine_output.date,'+00:00', :offset)) AS year_name,MONTH(CONVERT_TZ(marine_output.date,'+00:00', :offset)) AS month_name, count(*) as date_count
								FROM marine_output";
			$query .= $this->getFilter($filters);
			$query .= " GROUP BY year_name, month_name ORDER BY date_count DESC";
		} else {
			$query  = "SELECT EXTRACT(YEAR FROM marine_output.date AT TIME ZONE INTERVAL :offset) AS year_name,EXTRACT(MONTH FROM marine_output.date AT TIME ZONE INTERVAL :offset) AS month_name, count(*) as date_count
								FROM marine_output";
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
			$query  = "SELECT MONTH(CONVERT_TZ(marine_output.date,'+00:00', :offset)) AS month_name, YEAR(CONVERT_TZ(marine_output.date,'+00:00', :offset)) AS year_name, count(*) as date_count
								FROM marine_output".$filter_query." marine_output.date >= DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 YEAR)";
			$query .= " GROUP BY year_name, month_name
								ORDER BY year_name, month_name ASC";
			$query_data = array(':offset' => $offset);
		} else {
			$query  = "SELECT EXTRACT(MONTH FROM marine_output.date AT TIME ZONE INTERVAL :offset) AS month_name, EXTRACT(YEAR FROM marine_output.date AT TIME ZONE INTERVAL :offset) AS year_name, count(*) as date_count
								FROM marine_output".$filter_query." marine_output.date >= CURRENT_TIMESTAMP AT TIME ZONE INTERVAL :offset - INTERVAL '1 YEARS'";
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
			$query  = "SELECT HOUR(CONVERT_TZ(marine_output.date,'+00:00', :offset)) AS hour_name, count(*) as hour_count
								FROM marine_output";
			$query .= $this->getFilter($filters);
			$query .= " GROUP BY hour_name 
								".$orderby_sql;

/*		$query  = "SELECT HOUR(marine_output.date) AS hour_name, count(*) as hour_count
								FROM marine_output 
								GROUP BY hour_name 
								".$orderby_sql."
								LIMIT 10 OFFSET 00";
  */    
		$query_data = array(':offset' => $offset);
		} else {
			$query  = "SELECT EXTRACT(HOUR FROM marine_output.date AT TIME ZONE INTERVAL :offset) AS hour_name, count(*) as hour_count
								FROM marine_output";
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
			$query  = "SELECT HOUR(CONVERT_TZ(marine_output.date,'+00:00', :offset)) AS hour_name, count(*) as hour_count
								FROM marine_output".$filter_query." DATE(CONVERT_TZ(marine_output.date,'+00:00', :offset)) = :date
								GROUP BY hour_name 
								ORDER BY hour_name ASC";
		} else {
			$query  = "SELECT EXTRACT(HOUR FROM marine_output.date AT TIME ZONE INTERVAL :offset) AS hour_name, count(*) as hour_count
								FROM marine_output".$filter_query." to_char(marine_output.date AT TIME ZONE INTERVAL :offset, 'YYYY-mm-dd') = :date
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
			$query  = "SELECT HOUR(CONVERT_TZ(marine_output.date,'+00:00', :offset)) AS hour_name, count(*) as hour_count
								FROM marine_output".$filter_query." marine_output.ident = :ident 
								GROUP BY hour_name 
								ORDER BY hour_name ASC";
		} else {
			$query  = "SELECT EXTRACT(HOUR FROM marine_output.date AT TIME ZONE INTERVAL :offset) AS hour_name, count(*) as hour_count
								FROM marine_output".$filter_query." marine_output.ident = :ident 
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
     * Gets all aircraft registrations that have flown over
     *
     * @param bool $limit
     * @param int $olderthanmonths
     * @param string $sincedate
     * @param array $filters
     * @return array the aircraft list
     */
	public function countAllCaptainsByRaces($limit = true,$olderthanmonths = 0,$sincedate = '',$filters = array())
	{
		global $globalDBdriver;
		$filter_query = $this->getFilter($filters,true,true);
		$query  = "SELECT DISTINCT marine_output.race_id, marine_output.race_name, COUNT(marine_output.captain_id) AS captain_count 
			FROM marine_output".$filter_query." race_id IS NOT NULL";
		if ($olderthanmonths > 0) {
			if ($globalDBdriver == 'mysql') {
				$query .= ' AND marine_output.date < DATE_SUB(UTC_TIMESTAMP(), INTERVAL '.$olderthanmonths.' MONTH)';
			} else {
				$query .= " AND marine_output.date < CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$olderthanmonths." MONTHS'";
			}
		}
		if ($sincedate != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND marine_output.date > '".$sincedate."'";
			} else {
				$query .= " AND marine_output.date > CAST('".$sincedate."' AS TIMESTAMP)";
			}
		}
		$query .= " GROUP BY marine_output.race_id,marine_output.race_name ORDER BY captain_count DESC";
		if ($limit) $query .= " LIMIT 10 OFFSET 0";
		$sth = $this->db->prepare($query);
		$sth->execute();
		$marine_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['marine_race_id'] = $row['race_id'];
			$temp_array['marine_race_name'] = $row['race_name'];
			$temp_array['marine_captain_count'] = $row['captain_count'];
			$marine_array[] = $temp_array;
		}
		return $marine_array;
	}

    /**
     * Counts all vessels
     *
     * @param array $filters
     * @param string $year
     * @param string $month
     * @return Integer the number of vessels
     */
	public function countOverallMarine($filters = array(),$year = '',$month = '')
	{
		global $globalDBdriver;
		//$queryi  = "SELECT COUNT(marine_output.marine_id) AS flight_count FROM marine_output";
		$queryi  = "SELECT COUNT(DISTINCT marine_output.mmsi) AS flight_count FROM marine_output";
		$query_values = array();
		$query = '';
		if ($year != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND YEAR(marine_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			} else {
				$query .= " AND EXTRACT(YEAR FROM marine_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			}
		}
		if ($month != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND MONTH(marine_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			} else {
				$query .= " AND EXTRACT(MONTH FROM marine_output.date) = :month";
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
     * Counts all vessel type
     *
     * @param array $filters
     * @param string $year
     * @param string $month
     * @return Integer the number of vessels
     */
	public function countOverallMarineTypes($filters = array(),$year = '',$month = '')
	{
		global $globalDBdriver;
		$queryi  = "SELECT COUNT(DISTINCT marine_output.type) AS marine_count FROM marine_output";
		$query_values = array();
		$query = '';
		if ($year != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND YEAR(marine_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			} else {
				$query .= " AND EXTRACT(YEAR FROM marine_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			}
		}
		if ($month != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND MONTH(marine_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			} else {
				$query .= " AND EXTRACT(MONTH FROM marine_output.date) = :month";
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
     * Gets a number of all race
     *
     * @param array $filters
     * @param string $year
     * @param string $month
     * @return Integer number of races
     */
	public function countOverallMarineRaces($filters = array(),$year = '',$month = '')
	{
		global $globalDBdriver;
		$queryi  = "SELECT COUNT(DISTINCT marine_output.race_id) AS marine_count FROM marine_output";
		$query_values = array();
		$query = '';
		if ($year != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND YEAR(marine_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			} else {
				$query .= " AND EXTRACT(YEAR FROM marine_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			}
		}
		if ($month != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND MONTH(marine_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			} else {
				$query .= " AND EXTRACT(MONTH FROM marine_output.date) = :month";
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
     * Gets a number of all captain
     *
     * @param array $filters
     * @param string $year
     * @param string $month
     * @return Integer number of captain
     */
	public function countOverallMarineCaptains($filters = array(),$year = '',$month = '')
	{
		global $globalDBdriver;
		$queryi  = "SELECT COUNT(DISTINCT marine_output.captain_id) AS marine_count FROM marine_output";
		$query_values = array();
		$query = '';
		if ($year != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND YEAR(marine_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			} else {
				$query .= " AND EXTRACT(YEAR FROM marine_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			}
		}
		if ($month != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND MONTH(marine_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			} else {
				$query .= " AND EXTRACT(MONTH FROM marine_output.date) = :month";
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
			$query  = "SELECT HOUR(CONVERT_TZ(marine_output.date,'+00:00', :offset)) AS hour_name, count(*) as hour_count
								FROM marine_output".$filter_query." DATE(CONVERT_TZ(marine_output.date,'+00:00', :offset)) = CURDATE()
								GROUP BY hour_name 
								ORDER BY hour_name ASC";
		} else {
			$query  = "SELECT EXTRACT(HOUR FROM marine_output.date AT TIME ZONE INTERVAL :offset) AS hour_name, count(*) as hour_count
								FROM marine_output".$filter_query." to_char(marine_output.date AT TIME ZONE INTERVAL :offset,'YYYY-mm-dd') = CAST(NOW() AS date)
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
     * @param $fammarine_id
     * @return Integer the Barrie Spotter ID
     */
	public function getMarineIDBasedOnFamMarineID($fammarine_id)
	{
		$fammarine_id = filter_var($fammarine_id,FILTER_SANITIZE_STRING);

		$query  = "SELECT marine_output.marine_id
				FROM marine_output 
				WHERE marine_output.fammarine_id = '".$fammarine_id."'";
        
		
		$sth = $this->db->prepare($query);
		$sth->execute();

		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			return $row['marine_id'];
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
	* @return String the countries
	*/
	public function getCountryFromLatitudeLongitude($latitude,$longitude)
	{
		global $globalDebug;
		$latitude = filter_var($latitude,FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
		$longitude = filter_var($longitude,FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
	
		$Connection = new Connection($this->db);
		if (!$Connection->tableExists('countries')) return '';
	
		try {
			/*
			if ($globalDBdriver == 'mysql') {
				//$query  = "SELECT name, iso2, iso3 FROM countries WHERE Within(GeomFromText('POINT(:latitude :longitude)'), ogc_geom) LIMIT 1";
				$query = "SELECT name, iso2, iso3 FROM countries WHERE Within(GeomFromText('POINT(".$longitude.' '.$latitude.")'), ogc_geom) LIMIT 1";
			}
			*/
			// This query seems to work both for MariaDB and PostgreSQL
			$query = "SELECT name,iso2,iso3 FROM countries WHERE ST_Within(ST_GeomFromText('POINT(".$longitude." ".$latitude.")',4326), ogc_geom) LIMIT 1";
		
			$sth = $this->db->prepare($query);
			//$sth->execute(array(':latitude' => $latitude,':longitude' => $longitude));
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
	* @return String the countries
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
	public function countAllMarineTypes($limit = true,$olderthanmonths = 0,$sincedate = '',$filters = array(),$year = '',$month = '',$day = '')
	{
		global $globalDBdriver;
		$filter_query = $this->getFilter($filters,true,true);
		$query  = "SELECT marine_output.type AS marine_type, COUNT(marine_output.type) AS marine_type_count, marine_output.type_id AS marine_type_id 
		    FROM marine_output ".$filter_query." marine_output.type <> '' AND marine_output.type_id IS NOT NULL";
		if ($olderthanmonths > 0) {
			if ($globalDBdriver == 'mysql') {
				$query .= ' AND marine_output.date < DATE_SUB(UTC_TIMESTAMP(), INTERVAL '.$olderthanmonths.' MONTH)';
			} else {
				$query .= " AND marine_output.date < CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$olderthanmonths." MONTHS'";
			}
		}
		if ($sincedate != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND marine_output.date > '".$sincedate."'";
			} else {
				$query .= " AND marine_output.date > CAST('".$sincedate."' AS TIMESTAMP)";
			}
		}
		$query_values = array();
		if ($year != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND YEAR(marine_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			} else {
				$query .= " AND EXTRACT(YEAR FROM marine_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			}
		}
		if ($month != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND MONTH(marine_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			} else {
				$query .= " AND EXTRACT(MONTH FROM marine_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			}
		}
		if ($day != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND DAY(marine_output.date) = :day";
				$query_values = array_merge($query_values,array(':day' => $day));
			} else {
				$query .= " AND EXTRACT(DAY FROM marine_output.date) = :day";
				$query_values = array_merge($query_values,array(':day' => $day));
			}
		}
		$query .= " GROUP BY marine_output.type, marine_output.type_id ORDER BY marine_type_count DESC";
		if ($limit) $query .= " LIMIT 10 OFFSET 0";
		$sth = $this->db->prepare($query);
		$sth->execute($query_values);
		$marine_array = array();
		$temp_array = array();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['marine_type'] = html_entity_decode($row['marine_type'],ENT_QUOTES);
			$temp_array['marine_type_id'] = $row['marine_type_id'];
			$temp_array['marine_type_count'] = $row['marine_type_count'];
			$marine_array[] = $temp_array;
		}
		return $marine_array;
	}

    /**
     * Gets all the tracker information
     *
     * @param string $q
     * @param string $callsign
     * @param string $mmsi
     * @param string $imo
     * @param string $date_posted
     * @param string $limit
     * @param string $sort
     * @param string $includegeodata
     * @param string $origLat
     * @param string $origLon
     * @param string $dist
     * @param string $captain_id
     * @param string $captain_name
     * @param string $race_id
     * @param string $race_name
     * @param array $filters
     * @return array the tracker information
     */
	public function searchMarineData($q = '', $callsign = '',$mmsi = '', $imo = '', $date_posted = '', $limit = '', $sort = '', $includegeodata = '',$origLat = '',$origLon = '',$dist = '',$captain_id = '',$captain_name = '',$race_id = '',$race_name = '',$filters = array())
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
					if (is_int($q_item)) $additional_query .= "(marine_output.marine_id = '".$q_item."') OR ";
					if (is_int($q_item)) $additional_query .= "(marine_output.mmsi = '".$q_item."') OR ";
					if (is_int($q_item)) $additional_query .= "(marine_output.imo = '".$q_item."') OR ";
					if (is_int($q_item)) $additional_query .= "(marine_output.captain_id = '".$q_item."') OR ";
					if (is_int($q_item)) $additional_query .= "(marine_output.race_id = '".$q_item."') OR ";
					if (!is_int($q_item)) $additional_query .= "(marine_output.captain_name = '".$q_item."') OR ";
					if (!is_int($q_item)) $additional_query .= "(marine_output.race_name = '".$q_item."') OR ";
					$additional_query .= "(marine_output.ident like '%".$q_item."%')";
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
				$additional_query .= " AND marine_output.ident = :callsign";
				$query_values = array_merge($query_values,array(':callsign' => $callsign));
			}
		}
		if ($mmsi != "")
		{
			$mmsi = filter_var($mmsi,FILTER_SANITIZE_STRING);
			if (!is_numeric($mmsi))
			{
				return array();
			} else {
				$additional_query .= " AND marine_output.mmsi = :mmsi";
				$query_values = array_merge($query_values,array(':mmsi' => $mmsi));
			}
		}
		if ($imo != "")
		{
			$imo = filter_var($imo,FILTER_SANITIZE_STRING);
			if (!is_numeric($imo))
			{
				return array();
			} else {
				$additional_query .= " AND marine_output.imo = :imo";
				$query_values = array_merge($query_values,array(':imo' => $imo));
			}
		}
		if ($captain_id != "")
		{
			$captain_id = filter_var($captain_id,FILTER_SANITIZE_STRING);
			if (!is_numeric($captain_id))
			{
				return array();
			} else {
				$additional_query .= " AND marine_output.captain_id = :captain_id";
				$query_values = array_merge($query_values,array(':captain_id' => $captain_id));
			}
		}
		if ($race_id != "")
		{
			$race_id = filter_var($race_id,FILTER_SANITIZE_STRING);
			if (!is_numeric($race_id))
			{
				return array();
			} else {
				$additional_query .= " AND marine_output.race_id = :race_id";
				$query_values = array_merge($query_values,array(':race_id' => $race_id));
			}
		}
		if ($captain_name != "")
		{
			$captain_name = filter_var($captain_name,FILTER_SANITIZE_STRING);
			if (!is_string($captain_name))
			{
				return array();
			} else {
				$additional_query .= " AND marine_output.captain_name = :captain_name";
				$query_values = array_merge($query_values,array(':captain_name' => $captain_name));
			}
		}
		if ($race_name != "")
		{
			$race_name = filter_var($race_name,FILTER_SANITIZE_STRING);
			if (!is_numeric($race_name))
			{
				return array();
			} else {
				$additional_query .= " AND marine_output.race_name = :race_name";
				$query_values = array_merge($query_values,array(':race_name' => $race_name));
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
					$additional_query .= " AND TIMESTAMP(CONVERT_TZ(marine_output.date,'+00:00', '".$offset."')) >= '".$date_array[0]."' AND TIMESTAMP(CONVERT_TZ(marine_output.date,'+00:00', '".$offset."')) <= '".$date_array[1]."' ";
				} else {
					$additional_query .= " AND CAST(marine_output.date AT TIME ZONE INTERVAL ".$offset." AS TIMESTAMP) >= '".$date_array[0]."' AND CAST(marine_output.date AT TIME ZONE INTERVAL ".$offset." AS TIMESTAMP) <= '".$date_array[1]."' ";
				}
			} else {
				$date_array[0] = date("Y-m-d H:i:s", strtotime($date_array[0]));
				if ($globalDBdriver == 'mysql') {
					$additional_query .= " AND TIMESTAMP(CONVERT_TZ(marine_output.date,'+00:00', '".$offset."')) >= '".$date_array[0]."' ";
				} else {
					$additional_query .= " AND CAST(marine_output.date AT TIME ZONE INTERVAL ".$offset." AS TIMESTAMP) >= '".$date_array[0]."' ";
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
				$orderby_query = " ORDER BY marine_output.race_rank,marine_output.date DESC";
			}
		}
		if ($origLat != "" && $origLon != "" && $dist != "") {
			$dist = number_format($dist*0.621371,2,'.',''); // convert km to mile
			if ($globalDBdriver == 'mysql') {
				$query="SELECT marine_output.*, 1.60935*3956 * 2 * ASIN(SQRT( POWER(SIN(($origLat - marine_archive.latitude)*pi()/180/2),2)+COS( $origLat *pi()/180)*COS(marine_archive.latitude*pi()/180)*POWER(SIN(($origLon-marine_archive.longitude)*pi()/180/2),2))) as distance 
				    FROM marine_archive,marine_output".$filter_query." marine_output.fammarine_id = marine_archive.fammarine_id AND marine_output.ident <> '' ".$additional_query."AND marine_archive.longitude between ($origLon-$dist/cos(radians($origLat))*69) and ($origLon+$dist/cos(radians($origLat)*69)) and marine_archive.latitude between ($origLat-($dist/69)) and ($origLat+($dist/69)) 
				    AND (3956 * 2 * ASIN(SQRT( POWER(SIN(($origLat - marine_archive.latitude)*pi()/180/2),2)+COS( $origLat *pi()/180)*COS(marine_archive.latitude*pi()/180)*POWER(SIN(($origLon-marine_archive.longitude)*pi()/180/2),2)))) < $dist".$orderby_query;
			} else {
				$query="SELECT marine_output.*, 1.60935 * 3956 * 2 * ASIN(SQRT( POWER(SIN(($origLat - CAST(marine_archive.latitude as double precision))*pi()/180/2),2)+COS( $origLat *pi()/180)*COS(CAST(marine_archive.latitude as double precision)*pi()/180)*POWER(SIN(($origLon-CAST(marine_archive.longitude as double precision))*pi()/180/2),2))) as distance 
				    FROM marine_archive,marine_output".$filter_query." marine_output.fammarine_id = marine_archive.fammarine_id AND marine_output.ident <> '' ".$additional_query."AND CAST(marine_archive.longitude as double precision) between ($origLon-$dist/cos(radians($origLat))*69) and ($origLon+$dist/cos(radians($origLat))*69) and CAST(marine_archive.latitude as double precision) between ($origLat-($dist/69)) and ($origLat+($dist/69)) 
				    AND (3956 * 2 * ASIN(SQRT( POWER(SIN(($origLat - CAST(marine_archive.latitude as double precision))*pi()/180/2),2)+COS( $origLat *pi()/180)*COS(CAST(marine_archive.latitude as double precision)*pi()/180)*POWER(SIN(($origLon-CAST(marine_archive.longitude as double precision))*pi()/180/2),2)))) < $dist".$filter_query.$orderby_query;
			}
		} else {
			$query  = "SELECT marine_output.* FROM marine_output".$filter_query." marine_output.ident <> '' 
			    ".$additional_query."
			    ".$orderby_query;
		}
		$marine_array = $this->getDataFromDB($query, $query_values,$limit_query);
		return $marine_array;
	}

    /**
     * Check marine by id
     *
     * @param $id
     * @return String the ident
     */
	public function checkId($id)
	{
		$query  = 'SELECT marine_output.ident, marine_output.fammarine_id FROM marine_output WHERE marine_output.fammarine_id = :id';
		$query_data = array(':id' => $id);
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
     * Gets all info from a race
     *
     * @param $race_name
     * @return array race
     */
	public function getRaceByName($race_name)
	{
		$race_name = filter_var($race_name,FILTER_SANITIZE_STRING);
		$query  = "SELECT * FROM marine_race WHERE race_name = :race_name LIMIT 1";
		$sth = $this->db->prepare($query);
		$sth->execute(array(':race_name' => $race_name));
		$result = $sth->fetchAll(PDO::FETCH_ASSOC);
		if (isset($result[0])) return $result[0];
		else return array();
	}

    /**
     * Gets all info from a race
     *
     * @param $race_id
     * @return array race
     */
	public function getRace($race_id)
	{
		$race_id = filter_var($race_id,FILTER_SANITIZE_NUMBER_INT);
		$query  = "SELECT * FROM marine_race WHERE race_id = :race_id LIMIT 1";
		$sth = $this->db->prepare($query);
		$sth->execute(array(':race_id' => $race_id));
		$result = $sth->fetchAll(PDO::FETCH_ASSOC);
		if (isset($result[0])) return $result[0];
		else return array();
	}

    /**
     * Add race
     * @param $race_id
     * @param $race_name
     * @param $race_creator
     * @param $race_desc
     * @param $race_startdate
     * @param $race_markers
     */
	public function addRace($race_id,$race_name,$race_creator,$race_desc,$race_startdate,$race_markers)
	{
		$race_id = filter_var($race_id,FILTER_SANITIZE_NUMBER_INT);
		if ($race_id != '') {
			$race_name = filter_var($race_name,FILTER_SANITIZE_STRING);
			$race_creator = filter_var($race_creator,FILTER_SANITIZE_STRING);
			$race_desc = filter_var($race_desc,FILTER_SANITIZE_STRING);
			$race_startdate = filter_var($race_startdate,FILTER_SANITIZE_STRING);
			//$race_markers = filter_var($race_markers,FILTER_SANITIZE_STRING);
			$allrace = $this->getRace($race_id);
			if (empty($allrace)) {
				$query  = "INSERT INTO marine_race (race_id,race_name,race_creator,race_desc,race_startdate,race_markers) VALUES (:race_id,:race_name,:race_creator,:race_desc,:race_startdate,:race_markers)";
				$sth = $this->db->prepare($query);
				$sth->execute(array(':race_id' => $race_id,':race_name' => $race_name,':race_creator' => $race_creator,':race_desc' => $race_desc,':race_startdate' => $race_startdate,':race_markers' => $race_markers));
			} elseif ($race_id != '') {
				$query  = "UPDATE marine_race SET race_name = :race_name,race_desc = :race_desc,race_startdate = :race_startdate,race_markers = :race_markers WHERE race_id = :race_id";
				$sth = $this->db->prepare($query);
				$sth->execute(array(':race_id' => $race_id,':race_name' => $race_name,':race_desc' => $race_desc,':race_startdate' => $race_startdate,':race_markers' => $race_markers));
			}
		}
	}



	public function getOrderBy()
	{
		$orderby = array("type_asc" => array("key" => "type_asc", "value" => "Type - ASC", "sql" => "ORDER BY marine_output.type ASC"), "type_desc" => array("key" => "type_desc", "value" => "Type - DESC", "sql" => "ORDER BY marine_output.type DESC"),"manufacturer_asc" => array("key" => "manufacturer_asc", "value" => "Aircraft Manufacturer - ASC", "sql" => "ORDER BY marine_output.aircraft_manufacturer ASC"), "manufacturer_desc" => array("key" => "manufacturer_desc", "value" => "Aircraft Manufacturer - DESC", "sql" => "ORDER BY marine_output.aircraft_manufacturer DESC"),"airline_name_asc" => array("key" => "airline_name_asc", "value" => "Airline Name - ASC", "sql" => "ORDER BY marine_output.airline_name ASC"), "airline_name_desc" => array("key" => "airline_name_desc", "value" => "Airline Name - DESC", "sql" => "ORDER BY marine_output.airline_name DESC"), "ident_asc" => array("key" => "ident_asc", "value" => "Ident - ASC", "sql" => "ORDER BY marine_output.ident ASC"), "ident_desc" => array("key" => "ident_desc", "value" => "Ident - DESC", "sql" => "ORDER BY marine_output.ident DESC"), "airport_departure_asc" => array("key" => "airport_departure_asc", "value" => "Departure port - ASC", "sql" => "ORDER BY marine_output.departure_port_city ASC"), "airport_departure_desc" => array("key" => "airport_departure_desc", "value" => "Departure Airport - DESC", "sql" => "ORDER BY marine_output.departure_airport_city DESC"), "airport_arrival_asc" => array("key" => "airport_arrival_asc", "value" => "Arrival Airport - ASC", "sql" => "ORDER BY marine_output.arrival_airport_city ASC"), "airport_arrival_desc" => array("key" => "airport_arrival_desc", "value" => "Arrival Airport - DESC", "sql" => "ORDER BY marine_output.arrival_airport_city DESC"), "date_asc" => array("key" => "date_asc", "value" => "Date - ASC", "sql" => "ORDER BY marine_output.date ASC"), "date_desc" => array("key" => "date_desc", "value" => "Date - DESC", "sql" => "ORDER BY marine_output.date DESC"),"distance_asc" => array("key" => "distance_asc","value" => "Distance - ASC","sql" => "ORDER BY distance ASC"),"distance_desc" => array("key" => "distance_desc","value" => "Distance - DESC","sql" => "ORDER BY distance DESC"));
		return $orderby;
	}
    
}
?>
