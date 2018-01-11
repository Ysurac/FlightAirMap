<?php
/**
 * This class is part of FlightAirmap. It's used to support ATC (used by virtual airlines).
 *
 * Copyright (c) Ycarus (Yannick Chabanois) at Zugaina <support@flightairmap.com>
 * Licensed under AGPL license.
 * For more information see: https://www.flightairmap.com/
*/
require_once(dirname(__FILE__).'/settings.php');
require_once(dirname(__FILE__).'/class.Connection.php');

class ATC {
	public $db;

	/*
	 * Initialize DB connection
	*/
	public function __construct($dbc = null) {
		$Connection = new Connection($dbc);
		$this->db = $Connection->db;
		if ($this->db === null) die('Error: No DB connection. (ATC)');
	}

    /**
     * Get SQL query part for filter used
     * @param array $filter the filter
     * @param bool $where
     * @param bool $and
     * @return String the SQL part
     */
	public function getFilter($filter = array(),$where = false,$and = false) {
		global $globalFilter, $globalStatsFilters, $globalFilterName;
		if (is_array($globalStatsFilters) && isset($globalStatsFilters[$globalFilterName])) {
			if (isset($globalStatsFilters[$globalFilterName][0]['source'])) {
				foreach($globalStatsFilters[$globalFilterName] as $source) {
					if (isset($source['source'])) $filter['source'][] = $source['source'];
				}
			} else {
				$filter = $globalStatsFilters[$globalFilterName];
			}
		}
		if (is_array($globalFilter)) $filter = array_merge($filter,$globalFilter);
		$filter_query_join = '';
		$filter_query_where = '';
		if (isset($filter['source']) && !empty($filter['source'])) {
			$filter_query_where = " WHERE format_source IN ('".implode("','",$filter['source'])."')";
		}
		if ($filter_query_where == '' && $where) $filter_query_where = ' WHERE';
		elseif ($filter_query_where != '' && $and) $filter_query_where .= ' AND';
		$filter_query = $filter_query_join.$filter_query_where;
		return $filter_query;
	}

	/*
	 * Get all ATC from atc table
	 * @return array Return all ATC
     */
    public function getAll() {
		$filter_query = $this->getFilter(array());
		$query = "SELECT * FROM atc".$filter_query;
		$query_values = array();
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
		$all = $sth->fetchAll(PDO::FETCH_ASSOC);
		return $all;
	}

	/*
	 * Get ATC from atc table by id
	 * @param Integer ATC id
	 * @return Array Return ATC
	*/
	public function getById($id) {
		$filter_query = $this->getFilter(array(),true,true);
		$query = "SELECT * FROM atc".$filter_query." atc_id = :id";
		$query_values = array(':id' => $id);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
		$all = $sth->fetchAll(PDO::FETCH_ASSOC);
		return $all;
	}

	/*
	 * Get ATC from atc table by ident
	 * @param String $ident Flight ident
	 * @param String $format_source Format source
	 * @return Array Return ATC
	*/
	public function getByIdent($ident,$format_source = '') {
		$filter_query = $this->getFilter(array(),true,true);
		if ($format_source == '') {
			$query = "SELECT * FROM atc".$filter_query." ident = :ident";
			$query_values = array(':ident' => $ident);
		} else {
			$query = "SELECT * FROM atc".$filter_query." ident = :ident AND format_source = :format_source";
			$query_values = array(':ident' => $ident,':format_source' => $format_source);
		}
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
		$all = $sth->fetchAll(PDO::FETCH_ASSOC);
		return $all;
	}

	/*
	 * Add ATC in atc table
	 * @param String $ident Flight ident
	 * @param String $frequency Frequency
	 * @param Float $latitude Latitude
	 * @param Float $longitude Longitude
	 * @param Float $range Range
	 * @param String $info ATC info
	 * @param String $date ATC date
	 * @param String $type ATC type
	 * @param Integer $ivao_id ATC IVAO id
	 * @param String $ivao_name ATC IVAO name
	 * @param String $format_source Format source
	 * @param String $source_name Source name
	*/
	public function add($ident,$frequency,$latitude,$longitude,$range,$info,$date,$type = '',$ivao_id = '',$ivao_name = '',$format_source = '',$source_name = '') {
		$info = preg_replace('/[^(\x20-\x7F)]*/','',$info);
		$info = str_replace('^','<br />',$info);
		$info = str_replace('&amp;sect;','',$info);
		$info = str_replace('"','',$info);
		if ($type == '') $type = NULL;
		$query = "INSERT INTO atc (ident,frequency,latitude,longitude,atc_range,info,atc_lastseen,type,ivao_id,ivao_name,format_source,source_name) VALUES (:ident,:frequency,:latitude,:longitude,:range,:info,:date,:type,:ivao_id,:ivao_name,:format_source,:source_name)";
		$query_values = array(':ident' => $ident,':frequency' => $frequency,':latitude' => $latitude,':longitude' => $longitude,':range' => $range,':info' => $info,':date' => $date,':ivao_id' => $ivao_id,':ivao_name' => $ivao_name, ':type' => $type,':format_source' => $format_source,':source_name' => $source_name);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
		return '';
	}

	/*
	 * Update in atc table
	 * @param String $ident Flight ident
	 * @param String $frequency Frequency
	 * @param Float $latitude Latitude
	 * @param Float $longitude Longitude
	 * @param Float $range Range
	 * @param String $info ATC info
	 * @param String $date ATC date
	 * @param String $type ATC type
	 * @param Integer $ivao_id ATC IVAO id
	 * @param String $ivao_name ATC IVAO name
	 * @param String $format_source Format source
	 * @param String $source_name Source name
	*/
	public function update($ident,$frequency,$latitude,$longitude,$range,$info,$date,$type = '',$ivao_id = '',$ivao_name = '',$format_source = '',$source_name = '') {
		$info = preg_replace('/[^(\x20-\x7F)]*/','',$info);
		$info = str_replace('^','<br />',$info);
		$info = str_replace('&amp;sect;','',$info);
		$info = str_replace('"','',$info);
		if ($type == '') $type = NULL;
		$query = "UPDATE atc SET frequency = :frequency,latitude = :latitude,longitude = :longitude,atc_range = :range,info = :info,atc_lastseen = :date,type = :type,ivao_id = :ivao_id,ivao_name = :ivao_name WHERE ident = :ident AND format_source = :format_source AND source_name = :source_name";
		$query_values = array(':ident' => $ident,':frequency' => $frequency,':latitude' => $latitude,':longitude' => $longitude,':range' => $range,':info' => $info,':date' => $date,':ivao_id' => $ivao_id,':ivao_name' => $ivao_name, ':type' => $type,':format_source' => $format_source,':source_name' => $source_name);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
		return '';
	}

	/*
	 * Delete ATC from atc table by id
	 * @param Integer ATC id
	*/
	public function deleteById($id) {
		$query = "DELETE FROM atc WHERE atc_id = :id";
		$query_values = array(':id' => $id);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
		return '';
	}

	/*
	 * Delete ATC from atc table by ident
	 * @param String $ident Flight ident
	 * @param String $format_source Format source
	*/
	public function deleteByIdent($ident,$format_source) {
		$query = "DELETE FROM atc WHERE ident = :ident AND format_source = :format_source";
		$query_values = array(':ident' => $ident,':format_source' => $format_source);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
		return '';
	}

	/*
	 * Delete all ATC from atc table
	*/
	public function deleteAll() {
		$query = "DELETE FROM atc";
		$query_values = array();
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
		return '';
	}

	/*
	 * Delete ATC from atc table older than 1 hour
	*/
	public function deleteOldATC() {
		global $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			$query  = "DELETE FROM atc WHERE DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 HOUR) >= atc.atc_lastseen";
		} else {
			$query  = "DELETE FROM atc WHERE NOW() AT TIME ZONE 'UTC' - INTERVAL '1 HOUR' >= atc.atc_lastseen";
		}
		try {
			$sth = $this->db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			return "error";
		}
		return "success";
	}
}
?>