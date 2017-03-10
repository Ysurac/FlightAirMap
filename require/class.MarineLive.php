<?php
//$global_query = "SELECT marine_live.* FROM marine_live";

class MarineLive {
	public $db;
	static $global_query = "SELECT marine_live.* FROM marine_live";

	public function __construct($dbc = null) {
		$Connection = new Connection($dbc);
		$this->db = $Connection->db();
	}


	/**
	* Get SQL query part for filter used
	* @param Array $filter the filter
	* @return Array the SQL part
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
	* @return Array the spotter information
	*
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
	* @return Array the spotter information
	*
	*/
	public function getMinLiveMarineData($filter = array())
	{
		global $globalDBdriver, $globalLiveInterval;
		date_default_timezone_set('UTC');

		$filter_query = $this->getFilter($filter,true,true);

		if (!isset($globalLiveInterval)) $globalLiveInterval = '200';
		if ($globalDBdriver == 'mysql') {
			$query  = 'SELECT marine_live.mmsi, marine_live.ident, marine_live.type,marine_live.fammarine_id, marine_live.latitude, marine_live.longitude, marine_live.heading, marine_live.ground_speed, marine_live.date, marine_live.format_source 
			FROM marine_live INNER JOIN (SELECT l.fammarine_id, max(l.date) as maxdate FROM marine_live l WHERE DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.$globalLiveInterval.' SECOND) <= l.date GROUP BY l.fammarine_id) s on marine_live.fammarine_id = s.fammarine_id AND marine_live.date = s.maxdate'.$filter_query." marine_live.latitude <> 0 AND marine_live.longitude <> 0";
		} else {
			$query  = "SELECT marine_live.mmsi, marine_live.ident, marine_live.type,marine_live.fammarine_id, marine_live.latitude, marine_live.longitude, marine_live.heading, marine_live.ground_speed, marine_live.date, marine_live.format_source 
			FROM marine_live INNER JOIN (SELECT l.fammarine_id, max(l.date) as maxdate FROM marine_live l WHERE CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$globalLiveInterval." SECONDS' <= l.date GROUP BY l.fammarine_id) s on marine_live.fammarine_id = s.fammarine_id AND marine_live.date = s.maxdate".$filter_query." marine_live.latitude <> '0' AND marine_live.longitude <> '0'";
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
	* @return Array the spotter information
	*
	*/
	public function getMinLastLiveMarineData($filter = array())
	{
		global $globalDBdriver, $globalLiveInterval;
		date_default_timezone_set('UTC');

		$filter_query = $this->getFilter($filter,true,true);

		if (!isset($globalLiveInterval)) $globalLiveInterval = '200';
		if ($globalDBdriver == 'mysql') {
			$query  = 'SELECT marine_live.ident, marine_live.fammarine_id,marine_live.type, marine_live.latitude, marine_live.longitude, marine_live.heading, marine_live.ground_speed, marine_live.date, marine_live.format_source 
			FROM marine_live'.$filter_query.' DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.$globalLiveInterval." SECOND) <= marine_live.date AND marine_live.latitude <> '0' AND marine_live.longitude <> '0' 
			ORDER BY marine_live.fammarine_id, marine_live.date";
                } else {
			$query  = "SELECT marine_live.ident, marine_live.fammarine_id, marine_live.type,marine_live.latitude, marine_live.longitude, marine_live.heading, marine_live.ground_speed, marine_live.date, marine_live.format_source 
			FROM marine_live".$filter_query." CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$globalLiveInterval." SECONDS' <= marine_live.date AND marine_live.latitude <> '0' AND marine_live.longitude <> '0' 
			ORDER BY marine_live.fammarine_id, marine_live.date";
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
	* @return String number of entry
	*
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
	* @return Array the spotter information
	*
	*/
	public function getLiveMarineDatabyCoord($coord, $filter = array())
	{
		global $globalDBdriver, $globalLiveInterval;
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
			$query  = 'SELECT marine_live.* FROM marine_live INNER JOIN (SELECT l.fammarine_id, max(l.date) as maxdate FROM marine_live l WHERE DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.$globalLiveInterval.' SECOND) <= l.date GROUP BY l.fammarine_id) s on marine_live.fammarine_id = s.fammarine_id AND marine_live.date = s.maxdate AND marine_live.latitude BETWEEN '.$minlat.' AND '.$maxlat.' AND marine_live.longitude BETWEEN '.$minlong.' AND '.$maxlong.' GROUP BY marine_live.fammarine_id'.$filter_query;
		} else {
			$query  = "SELECT marine_live.* FROM marine_live INNER JOIN (SELECT l.fammarine_id, max(l.date) as maxdate FROM marine_live l WHERE NOW() at time zone 'UTC'  - INTERVAL '".$globalLiveInterval." SECONDS' <= l.date GROUP BY l.fammarine_id) s on marine_live.fammarine_id = s.fammarine_id AND marine_live.date = s.maxdate AND marine_live.latitude BETWEEN ".$minlat." AND ".$maxlat." AND marine_live.longitude BETWEEN ".$minlong." AND ".$maxlong." GROUP BY marine_live.fammarine_id".$filter_query;
		}
		$spotter_array = $Spotter->getDataFromDB($query);
		return $spotter_array;
	}

	/**
	* Gets all the spotter information based on a user's latitude and longitude
	*
	* @return Array the spotter information
	*
	*/
	public function getLatestMarineForLayar($lat, $lng, $radius, $interval)
	{
		$Marine = new Marine($this->db);
		date_default_timezone_set('UTC');
		if ($lat != '') {
			if (!is_numeric($lat)) {
				return false;
			}
		}
		if ($lng != '')
		{
			if (!is_numeric($lng))
                        {
                                return false;
                        }
                }

                if ($radius != '')
                {
                        if (!is_numeric($radius))
                        {
                                return false;
                        }
                }
		$additional_query = '';
		if ($interval != '')
                {
                        if (!is_string($interval))
                        {
                                //$additional_query = ' AND DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 MINUTE) <= marine_live.date ';
			        return false;
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
	* @return Array the spotter information
	*
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
	* @return Array the spotter information
	*
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
	* Gets last spotter information based on a particular callsign
	*
	* @return Array the spotter information
	*
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
	* @return Array the spotter information
	*
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
	* @return Array the spotter information
	*
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
	* @return Array the spotter information
	*
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
			$query  = 'DELETE FROM marine_live WHERE DATE_SUB(UTC_TIMESTAMP(),INTERVAL 9 HOUR) >= marine_live.date';
            		//$query  = "DELETE FROM marine_live WHERE marine_live.id IN (SELECT marine_live.id FROM marine_live INNER JOIN (SELECT l.fammarine_id, max(l.date) as maxdate FROM marine_live l GROUP BY l.fammarine_id) s on marine_live.fammarine_id = s.fammarine_id AND marine_live.date = s.maxdate AND DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 HOUR) >= marine_live.date)";
		} else {
			$query  = "DELETE FROM marine_live WHERE NOW() AT TIME ZONE 'UTC' - INTERVAL '9 HOURS' >= marine_live.date";
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
	}

	/**
	* Deletes all info in the table for an ident
	*
	* @return String success or false
	*
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
	* @return String success or false
	*
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
	* Gets the aircraft ident within the last hour
	*
	* @return String the ident
	*
	*/
	public function getIdentFromLastHour($ident)
	{
		global $globalDBdriver, $globalTimezone;
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
	* @return String the ident
	*
	*/
	public function checkIdentRecent($ident)
	{
		global $globalDBdriver, $globalTimezone;
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
	* @return String the ident
	*
	*/
	public function checkIdRecent($id)
	{
		global $globalDBdriver, $globalTimezone;
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
	* @return String the ident
	*
	*/
	public function checkMMSIRecent($mmsi)
	{
		global $globalDBdriver, $globalTimezone;
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
	* @param String $aircraft_icao the aircraft type
	* @param String $departure_airport_icao the departure airport
	* @param String $arrival_airport_icao the arrival airport
	* @return String success or false
	*
	*/
	public function addLiveMarineData($fammarine_id = '', $ident = '', $latitude = '', $longitude = '', $heading = '', $groundspeed = '', $date = '', $putinarchive = false, $mmsi = '',$type = '',$imo = '', $callsign = '',$status = '',$noarchive = false,$format_source = '', $source_name = '', $over_country = '')
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
		$heading = filter_var($heading,FILTER_SANITIZE_NUMBER_INT);
		$groundspeed = filter_var($groundspeed,FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
		$format_source = filter_var($format_source,FILTER_SANITIZE_STRING);
		$source_name = filter_var($source_name,FILTER_SANITIZE_STRING);
		$over_country = filter_var($over_country,FILTER_SANITIZE_STRING);
		$type = filter_var($type,FILTER_SANITIZE_STRING);
		$mmsi = filter_var($mmsi,FILTER_SANITIZE_NUMBER_INT);
		$status = filter_var($status,FILTER_SANITIZE_STRING);
		$imo = filter_var($imo,FILTER_SANITIZE_STRING);
		$callsign = filter_var($callsign,FILTER_SANITIZE_STRING);

            	if ($groundspeed == '' || $Common->isInteger($groundspeed) === false ) $groundspeed = 0;
            	if ($heading == '' || $Common->isInteger($heading) === false ) $heading = 0;
            	
		$query  = 'INSERT INTO marine_live (fammarine_id, ident, latitude, longitude, heading, ground_speed, date, format_source, source_name, over_country, mmsi, type,status,imo) 
		VALUES (:fammarine_id,:ident,:latitude,:longitude,:heading,:groundspeed,:date,:format_source, :source_name, :over_country,:mmsi,:type,:status,:imo)';

		$query_values = array(':fammarine_id' => $fammarine_id,':ident' => $ident,':latitude' => $latitude,':longitude' => $longitude,':heading' => $heading,':groundspeed' => $groundspeed,':date' => $date, ':format_source' => $format_source, ':source_name' => $source_name, ':over_country' => $over_country,':mmsi' => $mmsi,':type' => $type,':status' => $status,':imo' => $imo);
		try {
			
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
                } catch(PDOException $e) {
                	return "error : ".$e->getMessage();
                }
		/*
		if (isset($globalArchive) && $globalArchive && $putinarchive && $noarchive !== true) {
		    if ($globalDebug) echo '(Add to SBS archive : ';
		    $MarineArchive = new MarineArchive($this->db);
		    $result =  $MarineArchive->addMarineArchiveData($fammarine_id, $ident, $registration, $airline_name, $airline_icao, $airline_country, $airline_type, $aircraft_icao, $aircraft_shadow, $aircraft_name, $aircraft_manufacturer, $departure_airport_icao, $departure_airport_name, $departure_airport_city, $departure_airport_country, $departure_airport_time,$arrival_airport_icao, $arrival_airport_name, $arrival_airport_city, $arrival_airport_country, $arrival_airport_time, $route_stop, $date,$latitude, $longitude, $waypoints, $heading, $groundspeed, $squawk, $ModeS, $pilot_id, $pilot_name,$verticalrate,$format_source,$source_name, $over_country);
		    if ($globalDebug) echo $result.')';
		}
		*/
		return "success";

	}

	public function getOrderBy()
	{
		$orderby = array("aircraft_asc" => array("key" => "aircraft_asc", "value" => "Aircraft Type - ASC", "sql" => "ORDER BY marine_live.aircraft_icao ASC"), "aircraft_desc" => array("key" => "aircraft_desc", "value" => "Aircraft Type - DESC", "sql" => "ORDER BY marine_live.aircraft_icao DESC"),"manufacturer_asc" => array("key" => "manufacturer_asc", "value" => "Aircraft Manufacturer - ASC", "sql" => "ORDER BY marine_live.aircraft_manufacturer ASC"), "manufacturer_desc" => array("key" => "manufacturer_desc", "value" => "Aircraft Manufacturer - DESC", "sql" => "ORDER BY marine_live.aircraft_manufacturer DESC"),"airline_name_asc" => array("key" => "airline_name_asc", "value" => "Airline Name - ASC", "sql" => "ORDER BY marine_live.airline_name ASC"), "airline_name_desc" => array("key" => "airline_name_desc", "value" => "Airline Name - DESC", "sql" => "ORDER BY marine_live.airline_name DESC"), "ident_asc" => array("key" => "ident_asc", "value" => "Ident - ASC", "sql" => "ORDER BY marine_live.ident ASC"), "ident_desc" => array("key" => "ident_desc", "value" => "Ident - DESC", "sql" => "ORDER BY marine_live.ident DESC"), "airport_departure_asc" => array("key" => "airport_departure_asc", "value" => "Departure Airport - ASC", "sql" => "ORDER BY marine_live.departure_airport_city ASC"), "airport_departure_desc" => array("key" => "airport_departure_desc", "value" => "Departure Airport - DESC", "sql" => "ORDER BY marine_live.departure_airport_city DESC"), "airport_arrival_asc" => array("key" => "airport_arrival_asc", "value" => "Arrival Airport - ASC", "sql" => "ORDER BY marine_live.arrival_airport_city ASC"), "airport_arrival_desc" => array("key" => "airport_arrival_desc", "value" => "Arrival Airport - DESC", "sql" => "ORDER BY marine_live.arrival_airport_city DESC"), "date_asc" => array("key" => "date_asc", "value" => "Date - ASC", "sql" => "ORDER BY marine_live.date ASC"), "date_desc" => array("key" => "date_desc", "value" => "Date - DESC", "sql" => "ORDER BY marine_live.date DESC"));
		return $orderby;
	}

}


?>
