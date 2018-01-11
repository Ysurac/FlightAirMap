<?php
/**
 * This class is part of FlightAirmap. It's used to set and get sources (and weather stations) info
 *
 * Copyright (c) Ycarus (Yannick Chabanois) at Zugaina <support@flightairmap.com>
 * Licensed under AGPL license.
 * For more information see: https://www.flightairmap.com/
*/
require_once(dirname(__FILE__).'/settings.php');
require_once(dirname(__FILE__).'/class.Connection.php');

class Source {
	public $db;

	/*
	 * Initialize DB connection
	*/
	public function __construct($dbc = null) {
		$Connection = new Connection($dbc);
		$this->db = $Connection->db;
		if ($this->db === null) die('Error: No DB connection. (Source)');
	}

	public function getAllLocationInfo() {
		$query = "SELECT * FROM source_location";
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

	public function getLocationInfobyName($name) {
		$query = "SELECT * FROM source_location WHERE name = :name";
		$query_values = array(':name' => $name);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
		$all = $sth->fetchAll(PDO::FETCH_ASSOC);
		return $all;
	}

	public function getLocationInfobyNameType($name,$type) {
		$query = "SELECT * FROM source_location WHERE name = :name AND type = :type";
		$query_values = array(':name' => $name,':type' => $type);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
		$all = $sth->fetchAll(PDO::FETCH_ASSOC);
		return $all;
	}

    /**
     * @param $name
     * @return array
     */
    public function getLocationInfobySourceName($name) {
		$query = "SELECT * FROM source_location WHERE source = :name";
		$query_values = array(':name' => $name);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			echo "error : ".$e->getMessage();
			return array();
		}
		$all = $sth->fetchAll(PDO::FETCH_ASSOC);
		return $all;
	}

	public function getLocationInfoByType($type, $coord = array(), $limit = false) {
		$query = "SELECT * FROM source_location WHERE type = :type";
		if (is_array($coord) && !empty($coord) && count($coord) == 4) {
			$minlong = filter_var($coord[0],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
			$minlat = filter_var($coord[1],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
			$maxlong = filter_var($coord[2],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
			$maxlat = filter_var($coord[3],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
			$query .= " AND source_location.latitude BETWEEN ".$minlat." AND ".$maxlat." AND source_location.longitude BETWEEN ".$minlong." AND ".$maxlong." AND source_location.latitude <> 0 AND source_location.longitude <> 0";
		}
		$query .= " ORDER BY last_seen DESC";
		if ($limit) $query .= " LIMIT 1000";
		$query_values = array(':type' => $type);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
		$all = $sth->fetchAll(PDO::FETCH_ASSOC);
		return $all;
	}

	public function getLocationInfoByLocationID($location_id) {
		$query = "SELECT * FROM source_location WHERE location_id = :location_id";
		$query_values = array(':location_id' => $location_id);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
		$all = $sth->fetchAll(PDO::FETCH_ASSOC);
		return $all;
	}

	public function getLocationInfoByID($id) {
		$query = "SELECT * FROM source_location WHERE id = :id";
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

	public function addLocation($name,$latitude,$longitude,$altitude,$city,$country,$source,$logo = 'antenna.png',$type = '',$source_id = 0,$location_id = 0,$last_seen = '', $description = '') {
		if ($last_seen == '') $last_seen = date('Y-m-d H:i:s');
		$query = "INSERT INTO source_location (name,latitude,longitude,altitude,country,city,logo,source,type,source_id,last_seen,location_id,description) VALUES (:name,:latitude,:longitude,:altitude,:country,:city,:logo,:source,:type,:source_id,:last_seen,:location_id,:description)";
		$query_values = array(':name' => $name,':latitude' => $latitude, ':longitude' => $longitude,':altitude' => $altitude,':city' => $city,':country' => $country,':logo' => $logo,':source' => $source,':type' => $type,':source_id' => $source_id,':last_seen' => $last_seen,':location_id' => $location_id,':description' => $description);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			echo "error : ".$e->getMessage();
		}
	}

	public function updateLocation($name,$latitude,$longitude,$altitude,$city,$country,$source,$logo = 'antenna.png',$type = '',$source_id = 0,$location_id = 0,$last_seen = '',$description = '') {
		if ($last_seen == '') $last_seen = date('Y-m-d H:i:s');
		$query = "UPDATE source_location SET latitude = :latitude,longitude = :longitude,altitude = :altitude,country = :country,city = :city,logo = :logo,type = :type, source_id = :source_id, last_seen = :last_seen,location_id = :location_id, description = :description WHERE name = :name AND source = :source";
		$query_values = array(':name' => $name,':latitude' => $latitude, ':longitude' => $longitude,':altitude' => $altitude,':city' => $city,':country' => $country,':logo' => $logo,':source' => $source,':type' => $type,':source_id' => $source_id,':last_seen' => $last_seen,':location_id' => $location_id,':description' => $description);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
		return '';
	}

	public function updateLocationDescByName($name,$source,$source_id = 0,$description = '') {
		$query = "UPDATE source_location SET description = :description WHERE source_id = :source_id AND name = :name AND source = :source";
		$query_values = array(':name' => $name,':source' => $source,':source_id' => $source_id,':description' => $description);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
		return '';
	}

	public function updateLocationByLocationID($name,$latitude,$longitude,$altitude,$city,$country,$source,$logo = 'antenna.png',$type = '',$source_id = 0, $location_id,$last_seen = '',$description = '') {
		if ($last_seen == '') $last_seen = date('Y-m-d H:i:s');
		$query = "UPDATE source_location SET latitude = :latitude,longitude = :longitude,altitude = :altitude,country = :country,city = :city,logo = :logo,type = :type, last_seen = :last_seen, description = :description WHERE location_id = :location_id AND source = :source AND source_id = :source_id";
		$query_values = array(':source_id' => $source_id,':latitude' => $latitude, ':longitude' => $longitude,':altitude' => $altitude,':city' => $city,':country' => $country,':logo' => $logo,':source' => $source,':type' => $type,':last_seen' => $last_seen,':location_id' => $location_id,':description' => $description);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			echo "error : ".$e->getMessage();
		}
	}

	public function deleteLocation($id) {
		$query = "DELETE FROM source_location WHERE id = :id";
		$query_values = array(':id' => $id);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
		return '';
	}

	public function deleteLocationByType($type) {
		$query = "DELETE FROM source_location WHERE type = :type";
		$query_values = array(':type' => $type);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
		return '';
	}

	public function deleteLocationBySource($source) {
		$query = "DELETE FROM source_location WHERE source = :source";
		$query_values = array(':source' => $source);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
		return '';
	}

	public function deleteAllLocation() {
		$query = "DELETE FROM source_location";
		try {
			$sth = $this->db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
		return '';
	}

	public function deleteOldLocationByType($type) {
		global $globalDBdriver;
		if ($type == 'wx') {
			if ($globalDBdriver == 'mysql') {
				$query  = "DELETE FROM source_location WHERE DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 DAY) >= source_location.last_seen AND type = :type";
			} else {
				$query  = "DELETE FROM source_location WHERE NOW() AT TIME ZONE 'UTC' - INTERVAL '1 DAY' >= source_location.last_seen AND type = :type";
			}
		} elseif ($type == 'lightning') {
			if ($globalDBdriver == 'mysql') {
				$query  = "DELETE FROM source_location WHERE DATE_SUB(UTC_TIMESTAMP(),INTERVAL 20 MINUTE) >= source_location.last_seen AND type = :type";
			} else {
				$query  = "DELETE FROM source_location WHERE NOW() AT TIME ZONE 'UTC' - INTERVAL '20 MINUTE' >= source_location.last_seen AND type = :type";
			}
		} else {
			if ($globalDBdriver == 'mysql') {
				$query  = "DELETE FROM source_location WHERE DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 WEEK) >= source_location.last_seen AND type = :type";
			} else {
				$query  = "DELETE FROM source_location WHERE NOW() AT TIME ZONE 'UTC' - INTERVAL '1 WEEK' >= source_location.last_seen AND type = :type";
			}
		}
		try {
			$sth = $this->db->prepare($query);
			$sth->execute(array(':type' => $type));
		} catch(PDOException $e) {
			return "error";
		}
		return "success";
	}
}
?>