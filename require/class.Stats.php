<?php
/**
 * This class is part of FlightAirMap. It's used to save stats
 *
 * Copyright (c) Ycarus (Yannick Chabanois) at Zugaina <support@flightairmap.com>
 * Licensed under AGPL license.
 * For more information see: https://www.flightairmap.com/
*/

require_once(dirname(__FILE__).'/class.Spotter.php');
require_once(dirname(__FILE__).'/class.Marine.php');
require_once(dirname(__FILE__).'/class.Tracker.php');
require_once(dirname(__FILE__).'/class.Accident.php');
require_once(dirname(__FILE__).'/class.SpotterArchive.php');
require_once(dirname(__FILE__).'/class.Common.php');
class Stats {
	public $db;
	public $filter_name = '';

	/*
	 * Initialize DB connection
	*/
	public function __construct($dbc = null) {
		global $globalFilterName;
		if (isset($globalFilterName)) $this->filter_name = $globalFilterName;
		$Connection = new Connection($dbc);
		$this->db = $Connection->db();
		if ($this->db === null) die('Error: No DB connection. (Stats)');
	}

	public function addLastStatsUpdate($type,$stats_date) {
		$query = "DELETE FROM config WHERE name = :type;
			    INSERT INTO config (name,value) VALUES (:type,:stats_date);";
		$query_values = array('type' => $type,':stats_date' => $stats_date);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
		return '';
	}

	public function getLastStatsUpdate($type = 'last_update_stats') {
		$query = "SELECT value FROM config WHERE name = :type";
		try {
			$sth = $this->db->prepare($query);
			$sth->execute(array(':type' => $type));
		} catch(PDOException $e) {
			echo "error : ".$e->getMessage();
			return array();
		}
		$all = $sth->fetchAll(PDO::FETCH_ASSOC);
		return $all;
	}

	public function deleteStats($filter_name = '') {
		/*
		$query = "DELETE FROM config WHERE name = 'last_update_stats'";
		try {
		        $sth = $this->db->prepare($query);
		        $sth->execute();
		} catch(PDOException $e) {
		        return "error : ".$e->getMessage();
		}
		*/
		$query = "DELETE FROM stats WHERE filter_name = :filter_name;DELETE FROM stats_aircraft WHERE filter_name = :filter_name;DELETE FROM stats_airline WHERE filter_name = :filter_name;DELETE FROM stats_airport WHERE filter_name = :filter_name;DELETE FROM stats_callsign WHERE filter_name = :filter_name;DELETE FROM stats_country WHERE filter_name = :filter_name;DELETE FROM stats_flight WHERE filter_name = :filter_name;DELETE FROM stats_owner WHERE filter_name = :filter_name;DELETE FROM stats_pilot WHERE filter_name = :filter_name;DELETE FROM stats_registration WHERE filter_name = :filter_name;";
		try {
			$sth = $this->db->prepare($query);
			$sth->execute(array(':filter_name' => $filter_name));
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
	}
	public function deleteOldStats($filter_name = '') {
		if ($filter_name == '') {
			$query = "DELETE FROM config WHERE name = 'last_update_stats'";
		} else {
			$query = "DELETE FROM config WHERE name = 'last_update_stats_".$filter_name."'";
		}
		try {
			$sth = $this->db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
		$query = "DELETE FROM stats_aircraft WHERE filter_name = :filter_name;DELETE FROM stats_airline WHERE filter_name = :filter_name;DELETE FROM stats_callsign WHERE filter_name = :filter_name;DELETE FROM stats_country WHERE filter_name = :filter_name;DELETE FROM stats_owner WHERE filter_name = :filter_name;DELETE FROM stats_pilot WHERE filter_name = :filter_name;DELETE FROM stats_registration WHERE filter_name = :filter_name;";
		try {
			$sth = $this->db->prepare($query);
			$sth->execute(array(':filter_name' => $filter_name));
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
	}

	public function getAllAirlineNames($filter_name = '') {
		global $globalStatsFilters;
		if ($filter_name == '') $filter_name = $this->filter_name;
		$query = "SELECT * FROM stats_airline WHERE filter_name = :filter_name ORDER BY airline_name ASC";
		 try {
			$sth = $this->db->prepare($query);
			$sth->execute(array(':filter_name' => $filter_name));
		} catch(PDOException $e) {
			echo "error : ".$e->getMessage();
		}
		$all = $sth->fetchAll(PDO::FETCH_ASSOC);
		if (empty($all)) {
			$filters = array();
			if ($filter_name != '') {
				$filters = array_merge($filters,$globalStatsFilters[$filter_name]);
			}
			$Spotter = new Spotter($this->db);
			$all = $Spotter->getAllAirlineNames('',NULL,$filters);
		}
		return $all;
	}
	public function getAllAircraftTypes($stats_airline = '',$filter_name = '') {
		if ($filter_name == '') $filter_name = $this->filter_name;
		$query = "SELECT * FROM stats_aircraft WHERE stats_airline = :stats_airline AND filter_name = :filter_name ORDER BY aircraft_manufacturer ASC";
		try {
			$sth = $this->db->prepare($query);
			$sth->execute(array(':stats_airline' => $stats_airline,':filter_name' => $filter_name));
		} catch(PDOException $e) {
			echo "error : ".$e->getMessage();
		}
		$all = $sth->fetchAll(PDO::FETCH_ASSOC);
		return $all;
	}
	public function getAllManufacturers($stats_airline = '',$filter_name = '') {
		if ($filter_name == '') $filter_name = $this->filter_name;
		$query = "SELECT DISTINCT(aircraft_manufacturer) FROM stats_aircraft WHERE stats_airline = :stats_airline AND filter_name = :filter_name AND aircraft_manufacturer <> '' ORDER BY aircraft_manufacturer ASC";
		try {
			$sth = $this->db->prepare($query);
			$sth->execute(array(':stats_airline' => $stats_airline,':filter_name' => $filter_name));
		} catch(PDOException $e) {
			echo "error : ".$e->getMessage();
		}
		$all = $sth->fetchAll(PDO::FETCH_ASSOC);
		return $all;
	}
	public function getAllAirportNames($stats_airline = '',$filter_name = '') {
		if ($filter_name == '') $filter_name = $this->filter_name;
		$query = "SELECT airport_icao, airport_name,airport_city,airport_country FROM stats_airport WHERE stats_airline = :stats_airline AND filter_name = :filter_name AND stats_type = 'daily' GROUP BY airport_icao,airport_name,airport_city,airport_country ORDER BY airport_city ASC";
		try {
			$sth = $this->db->prepare($query);
			$sth->execute(array(':stats_airline' => $stats_airline,':filter_name' => $filter_name));
		} catch(PDOException $e) {
			echo "error : ".$e->getMessage();
		}
		$all = $sth->fetchAll(PDO::FETCH_ASSOC);
		return $all;
	}

	public function getAllOwnerNames($stats_airline = '',$filter_name = '') {
		if ($filter_name == '') $filter_name = $this->filter_name;
		$query = "SELECT owner_name FROM stats_owner WHERE stats_airline = :stats_airline AND filter_name = :filter_name ORDER BY owner_name ASC";
		try {
			$sth = $this->db->prepare($query);
			$sth->execute(array(':stats_airline' => $stats_airline,':filter_name' => $filter_name));
		} catch(PDOException $e) {
			echo "error : ".$e->getMessage();
		}
		$all = $sth->fetchAll(PDO::FETCH_ASSOC);
		return $all;
	}

	public function getAllPilotNames($stats_airline = '',$filter_name = '') {
		if ($filter_name == '') $filter_name = $this->filter_name;
		$query = "SELECT pilot_id,pilot_name FROM stats_pilot WHERE stats_airline = :stats_airline AND filter_name = :filter_name ORDER BY pilot_name ASC";
		try {
			$sth = $this->db->prepare($query);
			$sth->execute(array(':stats_airline' => $stats_airline,':filter_name' => $filter_name));
		} catch(PDOException $e) {
			echo "error : ".$e->getMessage();
		}
		$all = $sth->fetchAll(PDO::FETCH_ASSOC);
		return $all;
	}


	public function countAllAircraftTypes($limit = true, $stats_airline = '', $filter_name = '',$year = '', $month = '') {
		global $globalStatsFilters;
		if ($filter_name == '') $filter_name = $this->filter_name;
		if (strpos($stats_airline,'alliance_') !== FALSE) {
			$Spotter = new Spotter($this->db);
			$airlines = $Spotter->getAllAirlineNamesByAlliance(str_replace('_',' ',str_replace('alliance_','',$stats_airline)));
			$alliance_airlines = array();
			foreach ($airlines as $airline) {
				$alliance_airlines = array_merge($alliance_airlines,array($airline['airline_icao']));
			}
			if ($year == '' && $month == '') {
				if ($limit) $query = "SELECT aircraft_icao, cnt AS aircraft_icao_count, aircraft_name, aircraft_manufacturer FROM stats_aircraft WHERE aircraft_name <> '' AND aircraft_icao <> '' AND aircraft_icao <> 'NA' AND stats_airline IN ('".implode("','",$alliance_airlines)."') AND filter_name = :filter_name ORDER BY aircraft_icao_count DESC LIMIT 10 OFFSET 0";
				else $query = "SELECT aircraft_icao, cnt AS aircraft_icao_count, aircraft_name, aircraft_manufacturer FROM stats_aircraft WHERE aircraft_name <> '' AND aircraft_icao <> ''  AND aircraft_icao <> 'NA' AND stats_airline IN ('".implode("','",$alliance_airlines)."') AND filter_name = :filter_name ORDER BY aircraft_icao_count DESC";
				try {
					$sth = $this->db->prepare($query);
					$sth->execute(array(':filter_name' => $filter_name));
				} catch(PDOException $e) {
					echo "error : ".$e->getMessage();
				}
				$all = $sth->fetchAll(PDO::FETCH_ASSOC);
			} else $all = array();
		} else {
			if ($year == '' && $month == '') {
				if ($limit) $query = "SELECT aircraft_icao, cnt AS aircraft_icao_count, aircraft_name, aircraft_manufacturer FROM stats_aircraft WHERE aircraft_name <> '' AND aircraft_icao <> '' AND aircraft_icao <> 'NA' AND stats_airline = :stats_airline AND filter_name = :filter_name ORDER BY aircraft_icao_count DESC LIMIT 10 OFFSET 0";
				else $query = "SELECT aircraft_icao, cnt AS aircraft_icao_count, aircraft_name, aircraft_manufacturer FROM stats_aircraft WHERE aircraft_name <> '' AND aircraft_icao <> '' AND aircraft_icao <> 'NA' AND stats_airline = :stats_airline AND filter_name = :filter_name ORDER BY aircraft_icao_count DESC";
				try {
					$sth = $this->db->prepare($query);
					$sth->execute(array(':stats_airline' => $stats_airline,':filter_name' => $filter_name));
				} catch(PDOException $e) {
					echo "error : ".$e->getMessage();
				}
				$all = $sth->fetchAll(PDO::FETCH_ASSOC);
			} else $all = array();
		}
		if (empty($all)) {
			if (strpos($stats_airline,'alliance_') !== FALSE) {
				$filters = array('alliance' => str_replace('_',' ',str_replace('alliance_','',$stats_airline)),'year' => $year,'month' => $month);
			} else {
				$filters = array('airlines' => array($stats_airline),'year' => $year,'month' => $month);
			}
			if ($filter_name != '') {
				$filters = array_merge($filters,$globalStatsFilters[$filter_name]);
			}
			$Spotter = new Spotter($this->db);
			//$all = $Spotter->countAllAircraftTypes($limit,0,'',$filters,$year,$month);
			$all = $Spotter->countAllAircraftTypes($limit,0,'',$filters);
		}
		return $all;
	}
	public function countAllMarineTypes($limit = true, $filter_name = '',$year = '', $month = '') {
		global $globalStatsFilters, $globalVM;
		if ($filter_name == '') $filter_name = $this->filter_name;
		if ($year == '' && $month == '' && (!isset($globalVM) || $globalVM === FALSE)) {
			if ($limit) $query = "SELECT type AS marine_type, cnt AS marine_type_count, type_id AS marine_type_id FROM stats_marine_type WHERE filter_name = :filter_name ORDER BY marine_type_count DESC LIMIT 10 OFFSET 0";
			else $query = "SELECT type AS marine_type, cnt AS marine_type_count, type_id AS marine_type_id FROM stats_marine_type WHERE filter_name = :filter_name ORDER BY aircraft_icao_count DESC";
			try {
				$sth = $this->db->prepare($query);
				$sth->execute(array(':filter_name' => $filter_name));
			} catch(PDOException $e) {
				echo "error : ".$e->getMessage();
			}
			$all = $sth->fetchAll(PDO::FETCH_ASSOC);
		} else $all = array();
		if (empty($all)) {
			$filters = array('year' => $year,'month' => $month);
			if ($filter_name != '') {
				$filters = array_merge($filters,$globalStatsFilters[$filter_name]);
			}
			$Marine = new Marine($this->db);
			//$all = $Spotter->countAllAircraftTypes($limit,0,'',$filters,$year,$month);
			$all = $Marine->countAllMarineTypes($limit,0,'',$filters);
		}
		return $all;
	}
	public function countAllAirlineCountries($limit = true,$filter_name = '',$year = '',$month = '') {
		global $globalStatsFilters;
		if ($filter_name == '') $filter_name = $this->filter_name;
		if ($year == '' && $month == '') {
			if ($limit) $query = "SELECT airlines.country AS airline_country, SUM(stats_airline.cnt) as airline_country_count, countries.iso3 AS airline_country_iso3 FROM stats_airline,airlines,countries WHERE countries.name = airlines.country AND stats_airline.airline_icao=airlines.icao AND filter_name = :filter_name GROUP BY airline_country, countries.iso3 ORDER BY airline_country_count DESC LIMIT 10 OFFSET 0";
			else $query = "SELECT airlines.country AS airline_country, SUM(stats_airline.cnt) as airline_country_count, countries.iso3 AS airline_country_iso3 FROM stats_airline,airlines,countries WHERE countries.name = airlines.country AND stats_airline.airline_icao=airlines.icao AND filter_name = :filter_name GROUP BY airline_country, countries.iso3 ORDER BY airline_country_count DESC";
			try {
				$sth = $this->db->prepare($query);
				$sth->execute(array(':filter_name' => $filter_name));
			} catch(PDOException $e) {
				echo "error : ".$e->getMessage();
			}
			$all = $sth->fetchAll(PDO::FETCH_ASSOC);
		} else $all = array();
		if (empty($all)) {
			$Spotter = new Spotter($this->db);
			$filters = array();
			$filters = array('year' => $year,'month' => $month);
			if ($filter_name != '') {
				$filters = array_merge($filters,$globalStatsFilters[$filter_name]);
			}
			//$all = $Spotter->countAllAirlineCountries($limit,$filters,$year,$month);
			$all = $Spotter->countAllAirlineCountries($limit,$filters);
		}
		return $all;
	}
	public function countAllAircraftManufacturers($limit = true,$stats_airline = '', $filter_name = '',$year = '', $month = '') {
		global $globalStatsFilters;
		if ($filter_name == '') $filter_name = $this->filter_name;
		if (strpos($stats_airline,'alliance_') !== FALSE) {
			$Spotter = new Spotter($this->db);
			$airlines = $Spotter->getAllAirlineNamesByAlliance(str_replace('_',' ',str_replace('alliance_','',$stats_airline)));
			$alliance_airlines = array();
			foreach ($airlines as $airline) {
				$alliance_airlines = array_merge($alliance_airlines,array($airline['airline_icao']));
			}
			if ($year == '' && $month == '') {
				if ($limit) $query = "SELECT aircraft_manufacturer, SUM(stats_aircraft.cnt) as aircraft_manufacturer_count FROM stats_aircraft WHERE stats_airline IN ('".implode("','",$alliance_airlines)."') AND filter_name = :filter_name GROUP BY aircraft_manufacturer ORDER BY aircraft_manufacturer_count DESC LIMIT 10 OFFSET 0";
				else $query = "SELECT aircraft_manufacturer, SUM(stats_aircraft.cnt) as aircraft_manufacturer_count FROM stats_aircraft WHERE stats_airline IN ('".implode("','",$alliance_airlines)."') AND filter_name = :filter_name GROUP BY aircraft_manufacturer ORDER BY aircraft_manufacturer_count DESC";
				try {
					$sth = $this->db->prepare($query);
					$sth->execute(array(':filter_name' => $filter_name));
				} catch(PDOException $e) {
					echo "error : ".$e->getMessage();
				}
				$all = $sth->fetchAll(PDO::FETCH_ASSOC);
			} else $all = array();
		} else {
			if ($year == '' && $month == '') {
				if ($limit) $query = "SELECT aircraft_manufacturer, SUM(stats_aircraft.cnt) as aircraft_manufacturer_count FROM stats_aircraft WHERE stats_airline = :stats_airline AND filter_name = :filter_name GROUP BY aircraft_manufacturer ORDER BY aircraft_manufacturer_count DESC LIMIT 10 OFFSET 0";
				else $query = "SELECT aircraft_manufacturer, SUM(stats_aircraft.cnt) as aircraft_manufacturer_count FROM stats_aircraft WHERE stats_airline = :stats_airline AND filter_name = :filter_name GROUP BY aircraft_manufacturer ORDER BY aircraft_manufacturer_count DESC";
				try {
					$sth = $this->db->prepare($query);
					$sth->execute(array(':stats_airline' => $stats_airline,':filter_name' => $filter_name));
				} catch(PDOException $e) {
					echo "error : ".$e->getMessage();
				}
				$all = $sth->fetchAll(PDO::FETCH_ASSOC);
			} else $all = array();
		}
		if (empty($all)) {
			if (strpos($stats_airline,'alliance_') !== FALSE) {
				$filters = array('alliance' => str_replace('_',' ',str_replace('alliance_','',$stats_airline)),'year' => $year,'month' => $month);
			} else {
				$filters = array('airlines' => array($stats_airline),'year' => $year,'month' => $month);
			}
			if ($filter_name != '') {
				$filters = array_merge($filters,$globalStatsFilters[$filter_name]);
			}
			$Spotter = new Spotter($this->db);
			//$all = $Spotter->countAllAircraftManufacturers($filters,$year,$month);
			$all = $Spotter->countAllAircraftManufacturers($filters);
		}
		return $all;
	}

	public function countAllArrivalCountries($limit = true, $stats_airline = '', $filter_name = '',$year = '', $month = '') {
		global $globalStatsFilters;
		if ($filter_name == '') $filter_name = $this->filter_name;
		if (strpos($stats_airline,'alliance_') !== FALSE) {
			$Spotter = new Spotter($this->db);
			$airlines = $Spotter->getAllAirlineNamesByAlliance(str_replace('_',' ',str_replace('alliance_','',$stats_airline)));
			$alliance_airlines = array();
			foreach ($airlines as $airline) {
				$alliance_airlines = array_merge($alliance_airlines,array($airline['airline_icao']));
			}
			if ($year == '' && $month == '') {
				if ($limit) $query = "SELECT airport_country AS airport_arrival_country, SUM(arrival) as airport_arrival_country_count, countries.iso3 AS airport_arrival_country_iso3 FROM stats_airport, countries WHERE countries.name = stats_airport.airport_country AND stats_type = 'yearly' AND stats_airline IN ('".implode("','",$alliance_airlines)."') AND filter_name = :filter_name GROUP BY airport_arrival_country, countries.iso3 ORDER BY airport_arrival_country_count DESC LIMIT 10 OFFSET 0";
				else $query = "SELECT airport_country AS airport_arrival_country, SUM(arrival) as airport_arrival_country_count, countries.iso3 AS airport_arrival_country_iso3 FROM stats_airport, countries WHERE countries.name = stats_aiport.airport_country AND stats_type = 'yearly' AND stats_airline IN ('".implode("','",$alliance_airlines)."') AND filter_name = :filter_name GROUP BY airport_arrival_country, countries.iso3 ORDER BY airport_arrival_country_count DESC";
				try {
					$sth = $this->db->prepare($query);
					$sth->execute(array(':filter_name' => $filter_name));
				} catch(PDOException $e) {
					echo "error : ".$e->getMessage();
				}
				$all = $sth->fetchAll(PDO::FETCH_ASSOC);
			} else $all = array();
		} else {
			if ($year == '' && $month == '') {
				if ($limit) $query = "SELECT airport_country AS airport_arrival_country, SUM(arrival) as airport_arrival_country_count, countries.iso3 AS airport_arrival_country_iso3 FROM stats_airport, countries WHERE countries.name = stats_airport.airport_country AND stats_type = 'yearly' AND stats_airline = :stats_airline AND filter_name = :filter_name GROUP BY airport_arrival_country, countries.iso3 ORDER BY airport_arrival_country_count DESC LIMIT 10 OFFSET 0";
				else $query = "SELECT airport_country AS airport_arrival_country, SUM(arrival) as airport_arrival_country_count, countries.iso3 AS airport_arrival_country_iso3 FROM stats_airport, countries WHERE countries.name = stats_aiport.airport_country AND stats_type = 'yearly' AND stats_airline = :stats_airline AND filter_name = :filter_name GROUP BY airport_arrival_country, countries.iso3 ORDER BY airport_arrival_country_count DESC";
				try {
					$sth = $this->db->prepare($query);
					$sth->execute(array(':stats_airline' => $stats_airline,':filter_name' => $filter_name));
				} catch(PDOException $e) {
					echo "error : ".$e->getMessage();
				}
				$all = $sth->fetchAll(PDO::FETCH_ASSOC);
			} else $all = array();
		}
		if (empty($all)) {
			if (strpos($stats_airline,'alliance_') !== FALSE) {
				$filters = array('alliance' => str_replace('_',' ',str_replace('alliance_','',$stats_airline)),'year' => $year,'month' => $month);
			} else {
				$filters = array('airlines' => array($stats_airline),'year' => $year,'month' => $month);
			}
			if ($filter_name != '') {
				$filters = array_merge($filters,$globalStatsFilters[$filter_name]);
			}
			$Spotter = new Spotter($this->db);
			//$all = $Spotter->countAllArrivalCountries($limit,$filters,$year,$month);
			$all = $Spotter->countAllArrivalCountries($limit,$filters);
		}
		return $all;
	}
	public function countAllDepartureCountries($limit = true, $stats_airline = '', $filter_name = '', $year = '', $month = '') {
		global $globalStatsFilters;
		if ($filter_name == '') $filter_name = $this->filter_name;
		if (strpos($stats_airline,'alliance_') !== FALSE) {
			$Spotter = new Spotter($this->db);
			$airlines = $Spotter->getAllAirlineNamesByAlliance(str_replace('_',' ',str_replace('alliance_','',$stats_airline)));
			$alliance_airlines = array();
			foreach ($airlines as $airline) {
				$alliance_airlines = array_merge($alliance_airlines,array($airline['airline_icao']));
			}
			if ($limit) $query = "SELECT airport_country AS airport_departure_country, SUM(departure) as airport_departure_country_count, countries.iso3 as airport_departure_country_iso3 FROM stats_airport, countries WHERE countries.name = stats_airport.airport_country AND stats_type = 'yearly' AND stats_airline IN ('".implode("','",$alliance_airlines)."') AND filter_name = :filter_name GROUP BY airport_departure_country, countries.iso3 ORDER BY airport_departure_country_count DESC LIMIT 10 OFFSET 0";
			else $query = "SELECT airport_country AS airport_departure_country, SUM(departure) as airport_departure_country_count, countries.iso3 as airport_departure_country_iso3 FROM stats_airport, countries WHERE countries.iso3 = stats_airport.airport_country AND stats_type = 'yearly' AND stats_airline IN ('".implode("','",$alliance_airlines)."') AND filter_name = :filter_name GROUP BY airport_departure_country, countries.iso3 ORDER BY airport_departure_country_count DESC";
			$query_values = array(':filter_name' => $filter_name);
		} else {
			if ($limit) $query = "SELECT airport_country AS airport_departure_country, SUM(departure) as airport_departure_country_count, countries.iso3 as airport_departure_country_iso3 FROM stats_airport, countries WHERE countries.name = stats_airport.airport_country AND stats_type = 'yearly' AND stats_airline = :stats_airline AND filter_name = :filter_name GROUP BY airport_departure_country, countries.iso3 ORDER BY airport_departure_country_count DESC LIMIT 10 OFFSET 0";
			else $query = "SELECT airport_country AS airport_departure_country, SUM(departure) as airport_departure_country_count, countries.iso3 as airport_departure_country_iso3 FROM stats_airport, countries WHERE countries.iso3 = stats_airport.airport_country AND stats_type = 'yearly' AND stats_airline = :stats_airline AND filter_name = :filter_name GROUP BY airport_departure_country, countries.iso3 ORDER BY airport_departure_country_count DESC";
			$query_values = array(':stats_airline' => $stats_airline,':filter_name' => $filter_name);
		}
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			echo "error : ".$e->getMessage();
		}
		$all = $sth->fetchAll(PDO::FETCH_ASSOC);
		if (empty($all)) {
			if (strpos($stats_airline,'alliance_') !== FALSE) {
				$filters = array('alliance' => str_replace('_',' ',str_replace('alliance_','',$stats_airline)),'year' => $year,'month' => $month);
			} else {
				$filters = array('airlines' => array($stats_airline),'year' => $year,'month' => $month);
			}
			if ($filter_name != '') {
				$filters = array_merge($filters,$globalStatsFilters[$filter_name]);
			}
			$Spotter = new Spotter($this->db);
			//$all = $Spotter->countAllDepartureCountries($filters,$year,$month);
			$all = $Spotter->countAllDepartureCountries($filters);
		}
		return $all;
	}

	public function countAllAirlines($limit = true,$filter_name = '',$year = '',$month = '') {
		global $globalStatsFilters, $globalVATSIM, $globalIVAO;
		if ($filter_name == '') $filter_name = $this->filter_name;
		if ($year == '' && $month == '') {
			if ($globalVATSIM) $forsource = 'vatsim';
			if ($globalIVAO) $forsource = 'ivao';
			if (isset($forsource)) {
				if ($limit) $query = "SELECT DISTINCT stats_airline.airline_icao, stats_airline.cnt AS airline_count, stats_airline.airline_name, airlines.country as airline_country FROM stats_airline, airlines WHERE stats_airline.airline_name <> '' AND stats_airline.airline_icao <> '' AND airlines.icao = stats_airline.airline_icao AND filter_name = :filter_name AND airlines.forsource = :forsource ORDER BY airline_count DESC LIMIT 10 OFFSET 0";
				else $query = "SELECT DISTINCT stats_airline.airline_icao, stats_airline.cnt AS airline_count, stats_airline.airline_name, airlines.country as airline_country FROM stats_airline, airlines WHERE stats_airline.airline_name <> '' AND stats_airline.airline_icao <> '' AND airlines.icao = stats_airline.airline_icao AND filter_name = :filter_name AND airlines.forsource = :forsource ORDER BY airline_count DESC";
				$query_values = array(':filter_name' => $filter_name,':forsource' => $forsource);
			} else {
				if ($limit) $query = "SELECT DISTINCT stats_airline.airline_icao, stats_airline.cnt AS airline_count, stats_airline.airline_name, airlines.country as airline_country FROM stats_airline, airlines WHERE stats_airline.airline_name <> '' AND stats_airline.airline_icao <> '' AND airlines.icao = stats_airline.airline_icao AND filter_name = :filter_name AND airlines.forsource IS NULL ORDER BY airline_count DESC LIMIT 10 OFFSET 0";
				else $query = "SELECT DISTINCT stats_airline.airline_icao, stats_airline.cnt AS airline_count, stats_airline.airline_name, airlines.country as airline_country FROM stats_airline, airlines WHERE stats_airline.airline_name <> '' AND stats_airline.airline_icao <> '' AND airlines.icao = stats_airline.airline_icao AND filter_name = :filter_name AND airlines.forsource IS NULL ORDER BY airline_count DESC";
				$query_values = array(':filter_name' => $filter_name);
			}
			try {
				$sth = $this->db->prepare($query);
				$sth->execute($query_values);
			} catch(PDOException $e) {
				echo "error : ".$e->getMessage();
			}
			$all = $sth->fetchAll(PDO::FETCH_ASSOC);
		} else $all = array();
                if (empty($all)) {
	                $Spotter = new Spotter($this->db);
            		$filters = array();
			$filters = array('year' => $year,'month' => $month);
            		if ($filter_name != '') {
            			$filters = array_merge($filters,$globalStatsFilters[$filter_name]);
			}
			//$all = $Spotter->countAllAirlines($limit,0,'',$filters,$year,$month);
    		        $all = $Spotter->countAllAirlines($limit,0,'',$filters);
                }
                return $all;
	}
	public function countAllAircraftRegistrations($limit = true,$stats_airline = '',$filter_name = '',$year = '',$month = '') {
		global $globalStatsFilters;
		if ($filter_name == '') $filter_name = $this->filter_name;
		if (strpos($stats_airline,'alliance_') !== FALSE) {
			$Spotter = new Spotter($this->db);
			$airlines = $Spotter->getAllAirlineNamesByAlliance(str_replace('_',' ',str_replace('alliance_','',$stats_airline)));
			$alliance_airlines = array();
			foreach ($airlines as $airline) {
				$alliance_airlines = array_merge($alliance_airlines,array($airline['airline_icao']));
			}
			if ($year == '' && $month == '') {
				if ($limit) $query = "SELECT s.aircraft_icao, s.cnt AS aircraft_registration_count, a.type AS aircraft_name, s.registration FROM stats_registration s, aircraft a WHERE s.registration <> '' AND a.icao = s.aircraft_icao AND s.stats_airline IN ('".implode("','",$alliance_airlines)."') AND filter_name = :filter_name ORDER BY aircraft_registration_count DESC LIMIT 10 OFFSET 0";
				else $query = "SELECT s.aircraft_icao, s.cnt AS aircraft_registration_count, a.type AS aircraft_name FROM stats_registration s, aircraft a WHERE s.registration <> '' AND a.icao = s.aircraft_icao AND s.stats_airline IN ('".implode("','",$alliance_airlines)."') AND filter_name = :filter_name ORDER BY aircraft_registration_count DESC";
				try {
					$sth = $this->db->prepare($query);
					$sth->execute(array(':filter_name' => $filter_name));
				} catch(PDOException $e) {
					echo "error : ".$e->getMessage();
				}
				$all = $sth->fetchAll(PDO::FETCH_ASSOC);
			} else $all = array();
		} else {
			if ($year == '' && $month == '') {
				if ($limit) $query = "SELECT s.aircraft_icao, s.cnt AS aircraft_registration_count, a.type AS aircraft_name, s.registration FROM stats_registration s, aircraft a WHERE s.registration <> '' AND a.icao = s.aircraft_icao AND s.stats_airline = :stats_airline AND filter_name = :filter_name ORDER BY aircraft_registration_count DESC LIMIT 10 OFFSET 0";
				else $query = "SELECT s.aircraft_icao, s.cnt AS aircraft_registration_count, a.type AS aircraft_name FROM stats_registration s, aircraft a WHERE s.registration <> '' AND a.icao = s.aircraft_icao AND s.stats_airline = :stats_airline AND filter_name = :filter_name ORDER BY aircraft_registration_count DESC";
				try {
					$sth = $this->db->prepare($query);
					$sth->execute(array(':stats_airline' => $stats_airline,':filter_name' => $filter_name));
				} catch(PDOException $e) {
					echo "error : ".$e->getMessage();
				}
				$all = $sth->fetchAll(PDO::FETCH_ASSOC);
			} else $all = array();
		}
		if (empty($all)) {
			if (strpos($stats_airline,'alliance_') !== FALSE) {
				$filters = array('alliance' => str_replace('_',' ',str_replace('alliance_','',$stats_airline)),'year' => $year,'month' => $month);
			} else {
				$filters = array('airlines' => array($stats_airline),'year' => $year,'month' => $month);
			}
			if ($filter_name != '') {
				$filters = array_merge($filters,$globalStatsFilters[$filter_name]);
			}
			$Spotter = new Spotter($this->db);
			//$all = $Spotter->countAllAircraftRegistrations($limit,0,'',$filters,$year,$month);
			$all = $Spotter->countAllAircraftRegistrations($limit,0,'',$filters);
		}
		return $all;
	}
	public function countAllCallsigns($limit = true,$stats_airline = '',$filter_name = '',$year = '',$month = '') {
		global $globalStatsFilters;
		if ($filter_name == '') $filter_name = $this->filter_name;
		if (strpos($stats_airline,'alliance_') !== FALSE) {
			$Spotter = new Spotter($this->db);
			$airlines = $Spotter->getAllAirlineNamesByAlliance(str_replace('_',' ',str_replace('alliance_','',$stats_airline)));
			$alliance_airlines = array();
			foreach ($airlines as $airline) {
				$alliance_airlines = array_merge($alliance_airlines,array($airline['airline_icao']));
			}
			if ($year == '' && $month == '') {
				if ($limit) $query = "SELECT s.callsign_icao, s.cnt AS callsign_icao_count, a.name AS airline_name, a.icao as airline_icao FROM stats_callsign s, airlines a WHERE s.callsign_icao <> '' AND a.icao = s.airline_icao AND s.airline_icao IN ('".implode("','",$alliance_airlines)."') AND filter_name = :filter_name ORDER BY callsign_icao_count DESC LIMIT 10 OFFSET 0";
				else $query = "SELECT s.callsign_icao, s.cnt AS callsign_icao_count, a.name AS airline_name, a.icao as airline_icao FROM stats_callsign s, airlines a WHERE s.callsign_icao <> '' AND a.icao = s.airline_icao AND s.airline_icao IN ('".implode("','",$alliance_airlines)."') AND filter_name = :filter_name ORDER BY callsign_icao_count DESC";
				 try {
					$sth = $this->db->prepare($query);
					$sth->execute(array(':filter_name' => $filter_name));
				} catch(PDOException $e) {
					echo "error : ".$e->getMessage();
				}
				$all = $sth->fetchAll(PDO::FETCH_ASSOC);
			} else $all = array();
		} else {
			if ($year == '' && $month == '') {
				if ($limit) $query = "SELECT s.callsign_icao, s.cnt AS callsign_icao_count, a.name AS airline_name, a.icao as airline_icao FROM stats_callsign s, airlines a WHERE s.callsign_icao <> '' AND a.icao = s.airline_icao AND s.airline_icao = :stats_airline AND filter_name = :filter_name ORDER BY callsign_icao_count DESC LIMIT 10 OFFSET 0";
				else $query = "SELECT s.callsign_icao, s.cnt AS callsign_icao_count, a.name AS airline_name, a.icao as airline_icao FROM stats_callsign s, airlines a WHERE s.callsign_icao <> '' AND a.icao = s.airline_icao AND s.airline_icao = :stats_airline AND filter_name = :filter_name ORDER BY callsign_icao_count DESC";
				 try {
					$sth = $this->db->prepare($query);
					$sth->execute(array(':stats_airline' => $stats_airline,':filter_name' => $filter_name));
				} catch(PDOException $e) {
					echo "error : ".$e->getMessage();
				}
				$all = $sth->fetchAll(PDO::FETCH_ASSOC);
			} else $all = array();
		}
		if (empty($all)) {
			if (strpos($stats_airline,'alliance_') !== FALSE) {
				$filters = array('alliance' => str_replace('_',' ',str_replace('alliance_','',$stats_airline)),'year' => $year,'month' => $month);
			} else {
				$filters = array('airlines' => array($stats_airline),'year' => $year,'month' => $month);
			}
			if ($filter_name != '') {
				$filters = array_merge($filters,$globalStatsFilters[$filter_name]);
			}
			$Spotter = new Spotter($this->db);
			//$all = $Spotter->countAllCallsigns($limit,0,'',$filters,$year,$month);
			$all = $Spotter->countAllCallsigns($limit,0,'',$filters);
		}
		return $all;
	}
	public function countAllFlightOverCountries($limit = true, $stats_airline = '',$filter_name = '',$year = '',$month = '') {
		$Connection = new Connection($this->db);
		if ($filter_name == '') $filter_name = $this->filter_name;
		if ($Connection->tableExists('countries')) {
			if (strpos($stats_airline,'alliance_') !== FALSE) {
				$Spotter = new Spotter($this->db);
				$airlines = $Spotter->getAllAirlineNamesByAlliance(str_replace('_',' ',str_replace('alliance_','',$stats_airline)));
				if ($year == '' && $month == '') {
					$alliance_airlines = array();
					foreach ($airlines as $airline) {
						$alliance_airlines = array_merge($alliance_airlines,array($airline['airline_icao']));
					}
					if ($limit) $query = "SELECT countries.iso3 as flight_country_iso3, countries.iso2 as flight_country_iso2, countries.name as flight_country, cnt as flight_count, lat as flight_country_latitude, lon as flight_country_longitude FROM stats_country, countries WHERE stats_country.iso2 = countries.iso2 AND stats_country.stats_airline IN ('".implode("','",$alliance_airlines)."') AND filter_name = :filter_name ORDER BY flight_count DESC LIMIT 20 OFFSET 0";
					else $query = "SELECT countries.iso3 as flight_country_iso3, countries.iso2 as flight_country_iso2, countries.name as flight_country, cnt as flight_count, lat as flight_country_latitude, lon as flight_country_longitude FROM stats_country, countries WHERE stats_country.iso2 = countries.iso2 AND stats_country.stats_airline IN ('".implode("','",$alliance_airlines)."') AND filter_name = :filter_name ORDER BY flight_count DESC";
					 try {
						$sth = $this->db->prepare($query);
						$sth->execute(array(':filter_name' => $filter_name));
					} catch(PDOException $e) {
						echo "error : ".$e->getMessage();
					}
					$all = $sth->fetchAll(PDO::FETCH_ASSOC);
					return $all;
				} else return array();
			} else {
				if ($year == '' && $month == '') {
					if ($limit) $query = "SELECT countries.iso3 as flight_country_iso3, countries.iso2 as flight_country_iso2, countries.name as flight_country, cnt as flight_count, lat as flight_country_latitude, lon as flight_country_longitude FROM stats_country, countries WHERE stats_country.iso2 = countries.iso2 AND stats_country.stats_airline = :stats_airline AND filter_name = :filter_name ORDER BY flight_count DESC LIMIT 20 OFFSET 0";
					else $query = "SELECT countries.iso3 as flight_country_iso3, countries.iso2 as flight_country_iso2, countries.name as flight_country, cnt as flight_count, lat as flight_country_latitude, lon as flight_country_longitude FROM stats_country, countries WHERE stats_country.iso2 = countries.iso2 AND stats_country.stats_airline = :stats_airline AND filter_name = :filter_name ORDER BY flight_count DESC";
					 try {
						$sth = $this->db->prepare($query);
						$sth->execute(array(':stats_airline' => $stats_airline,':filter_name' => $filter_name));
					} catch(PDOException $e) {
						echo "error : ".$e->getMessage();
					}
					$all = $sth->fetchAll(PDO::FETCH_ASSOC);
					return $all;
				} else return array();
			}
			$Spotter = new Spotter($this->db);
			return $Spotter->countAllFlightOverCountries($limit);
		} else return array();
	}
	public function countAllMarineOverCountries($limit = true, $filter_name = '',$year = '',$month = '') {
		$Connection = new Connection($this->db);
		if ($filter_name == '') $filter_name = $this->filter_name;
		if ($Connection->tableExists('countries')) {
			$all = array();
			if ($year == '' && $month == '') {
				if ($limit) $query = "SELECT countries.iso3 as marine_country_iso3, countries.iso2 as marine_country_iso2, countries.name as marine_country, cnt as marine_count, lat as marine_country_latitude, lon as marine_country_longitude FROM stats_marine_country, countries WHERE stats_marine_country.iso2 = countries.iso2 AND filter_name = :filter_name ORDER BY marine_count DESC LIMIT 20 OFFSET 0";
				else $query = "SELECT countries.iso3 as marine_country_iso3, countries.iso2 as marine_country_iso2, countries.name as marine_country, cnt as marine_count, lat as marine_country_latitude, lon as marine_country_longitude FROM stats_marine_country, countries WHERE stats_marine_country.iso2 = countries.iso2 AND filter_name = :filter_name ORDER BY marine_count DESC";
				 try {
					$sth = $this->db->prepare($query);
					$sth->execute(array(':filter_name' => $filter_name));
				} catch(PDOException $e) {
					echo "error : ".$e->getMessage();
				}
				$all = $sth->fetchAll(PDO::FETCH_ASSOC);
			}
			if (empty($all)) {
				$filters = array();
				if ($filter_name != '') {
					$filters = array_merge($filters,$globalStatsFilters[$filter_name]);
				}
				$Marine = new Marine($this->db);
				$all = $Marine->countAllMarineOverCountries($limit,0,'',$filters);
			}
			return $all;
		} else return array();
	}
	public function countAllTrackerOverCountries($limit = true, $filter_name = '',$year = '',$month = '') {
		global $globalStatsFilters;
		$Connection = new Connection($this->db);
		if ($filter_name == '') $filter_name = $this->filter_name;
		if ($Connection->tableExists('countries')) {
			$all = array();
			if ($year == '' && $month == '') {
				if ($limit) $query = "SELECT countries.iso3 as tracker_country_iso3, countries.iso2 as tracker_country_iso2, countries.name as tracker_country, cnt as tracker_count, lat as tracker_country_latitude, lon as tracker_country_longitude FROM stats_tracker_country, countries WHERE stats_tracker_country.iso2 = countries.iso2 AND filter_name = :filter_name ORDER BY tracker_count DESC LIMIT 20 OFFSET 0";
				else $query = "SELECT countries.iso3 as tracker_country_iso3, countries.iso2 as tracker_country_iso2, countries.name as tracker_country, cnt as tracker_count, lat as tracker_country_latitude, lon as tracker_country_longitude FROM stats_tracker_country, countries WHERE stats_tracker_country.iso2 = countries.iso2 AND filter_name = :filter_name ORDER BY tracker_count DESC";
				 try {
					$sth = $this->db->prepare($query);
					$sth->execute(array(':filter_name' => $filter_name));
				} catch(PDOException $e) {
					echo "error : ".$e->getMessage();
				}
				$all = $sth->fetchAll(PDO::FETCH_ASSOC);
				return $all;
			}
			if (empty($all)) {
				$filters = array();
				if ($filter_name != '') {
					$filters = array_merge($filters,$globalStatsFilters[$filter_name]);
				}
				$Tracker = new Tracker($this->db);
				$all = $Tracker->countAllTrackerOverCountries($limit,0,'',$filters);
			}
			return $all;
		} else return array();
	}
	public function countAllPilots($limit = true,$stats_airline = '',$filter_name = '', $year = '',$month = '') {
		global $globalStatsFilters;
		if ($filter_name == '') $filter_name = $this->filter_name;
		if ($year == '' && $month == '') {
			if ($limit) $query = "SELECT pilot_id, cnt AS pilot_count, pilot_name, format_source FROM stats_pilot WHERE stats_airline = :stats_airline AND filter_name = :filter_name ORDER BY pilot_count DESC LIMIT 10 OFFSET 0";
			else $query = "SELECT pilot_id, cnt AS pilot_count, pilot_name, format_source FROM stats_pilot WHERE stats_airline = :stats_airline AND filter_name = :filter_name ORDER BY pilot_count DESC";
			try {
				$sth = $this->db->prepare($query);
				$sth->execute(array(':stats_airline' => $stats_airline,':filter_name' => $filter_name));
			} catch(PDOException $e) {
				echo "error : ".$e->getMessage();
			}
			$all = $sth->fetchAll(PDO::FETCH_ASSOC);
		} else $all = array();
		if (empty($all)) {
			$filters = array('airlines' => array($stats_airline),'year' => $year,'month' => $month);
			if ($filter_name != '') {
				$filters = array_merge($filters,$globalStatsFilters[$filter_name]);
			}
			$Spotter = new Spotter($this->db);
			//$all = $Spotter->countAllPilots($limit,0,'',$filters,$year,$month);
			$all = $Spotter->countAllPilots($limit,0,'',$filters);
		}
		return $all;
	}

	public function countAllOwners($limit = true,$stats_airline = '', $filter_name = '',$year = '',$month = '') {
		global $globalStatsFilters;
		if ($filter_name == '') $filter_name = $this->filter_name;
		if (strpos($stats_airline,'alliance_') !== FALSE) {
			$Spotter = new Spotter($this->db);
			$airlines = $Spotter->getAllAirlineNamesByAlliance(str_replace('_',' ',str_replace('alliance_','',$stats_airline)));
			if ($year == '' && $month == '') {
				$alliance_airlines = array();
				foreach ($airlines as $airline) {
					$alliance_airlines = array_merge($alliance_airlines,array($airline['airline_icao']));
				}
				if ($limit) $query = "SELECT owner_name, cnt AS owner_count FROM stats_owner WHERE stats_airline IN ('".implode("','",$alliance_airlines)."') AND filter_name = :filter_name ORDER BY owner_count DESC LIMIT 10 OFFSET 0";
				else $query = "SELECT owner_name, cnt AS owner_count FROM stats_owner WHERE stats_airline IN ('".implode("','",$alliance_airlines)."') AND filter_name = :filter_name ORDER BY owner_count DESC";
				try {
					$sth = $this->db->prepare($query);
					$sth->execute(array(':filter_name' => $filter_name));
				} catch(PDOException $e) {
					echo "error : ".$e->getMessage();
				}
				$all = $sth->fetchAll(PDO::FETCH_ASSOC);
			} else $all = array();
		} else {
			if ($year == '' && $month == '') {
				if ($limit) $query = "SELECT owner_name, cnt AS owner_count FROM stats_owner WHERE stats_airline = :stats_airline AND filter_name = :filter_name ORDER BY owner_count DESC LIMIT 10 OFFSET 0";
				else $query = "SELECT owner_name, cnt AS owner_count FROM stats_owner WHERE stats_airline = :stats_airline AND filter_name = :filter_name ORDER BY owner_count DESC";
				try {
					$sth = $this->db->prepare($query);
					$sth->execute(array(':stats_airline' => $stats_airline,':filter_name' => $filter_name));
				} catch(PDOException $e) {
					echo "error : ".$e->getMessage();
				}
				$all = $sth->fetchAll(PDO::FETCH_ASSOC);
			} else $all = array();
		}
		if (empty($all)) {
			if (strpos($stats_airline,'alliance_') !== FALSE) {
				$filters = array('alliance' => str_replace('_',' ',str_replace('alliance_','',$stats_airline)),'year' => $year,'month' => $month);
			} else {
				$filters = array('airlines' => array($stats_airline),'year' => $year,'month' => $month);
			}
			if ($filter_name != '') {
				$filters = array_merge($filters,$globalStatsFilters[$filter_name]);
			}
			$Spotter = new Spotter($this->db);
			//$all = $Spotter->countAllOwners($limit,0,'',$filters,$year,$month);
			$all = $Spotter->countAllOwners($limit,0,'',$filters);
		}
		return $all;
	}
	public function countAllDepartureAirports($limit = true,$stats_airline = '',$filter_name = '',$year = '',$month = '') {
		global $globalStatsFilters;
		if ($filter_name == '') $filter_name = $this->filter_name;
		if (strpos($stats_airline,'alliance_') !== FALSE) {
			$Spotter = new Spotter($this->db);
			$airlines = $Spotter->getAllAirlineNamesByAlliance(str_replace('_',' ',str_replace('alliance_','',$stats_airline)));
			if ($year == '' && $month == '') {
				$alliance_airlines = array();
				foreach ($airlines as $airline) {
					$alliance_airlines = array_merge($alliance_airlines,array($airline['airline_icao']));
				}
				if ($limit) $query = "SELECT DISTINCT airport_icao AS airport_departure_icao,airport.name AS airport_departure_name,airport_city AS airport_departure_city,airport_country AS airport_departure_country,departure AS airport_departure_icao_count, airport.latitude AS airport_departure_latitude, airport.longitude AS airport_departure_longitude FROM stats_airport,airport WHERE airport.icao = stats_airport.airport_icao AND departure > 0 AND stats_type = 'yearly' AND stats_airline IN ('".implode("','",$alliance_airlines)."') AND filter_name = :filter_name ORDER BY airport_departure_icao_count DESC LIMIT 10 OFFSET 0";
				else $query = "SELECT DISTINCT airport_icao AS airport_departure_icao,airport.name AS airport_departure_name,airport_city AS airport_departure_city,airport_country AS airport_departure_country,departure AS airport_departure_icao_count, airport.latitude AS airport_departure_latitude, airport.longitude AS airport_departure_longitude FROM stats_airport,airport WHERE airport.icao = stats_airport.airport_icao AND departure > 0 AND stats_type = 'yearly' AND stats_airline IN ('".implode("','",$alliance_airlines)."') AND filter_name = :filter_name ORDER BY airport_departure_icao_count DESC";
				try {
					$sth = $this->db->prepare($query);
					$sth->execute(array(':filter_name' => $filter_name));
				} catch(PDOException $e) {
					echo "error : ".$e->getMessage();
				}
				$all = $sth->fetchAll(PDO::FETCH_ASSOC);
			} else $all = array();
		} else {
			if ($year == '' && $month == '') {
				if ($limit) $query = "SELECT DISTINCT airport_icao AS airport_departure_icao,airport.name AS airport_departure_name,airport_city AS airport_departure_city,airport_country AS airport_departure_country,departure AS airport_departure_icao_count, airport.latitude AS airport_departure_latitude, airport.longitude AS airport_departure_longitude FROM stats_airport,airport WHERE airport.icao = stats_airport.airport_icao AND departure > 0 AND stats_type = 'yearly' AND stats_airline = :stats_airline AND filter_name = :filter_name ORDER BY airport_departure_icao_count DESC LIMIT 10 OFFSET 0";
				else $query = "SELECT DISTINCT airport_icao AS airport_departure_icao,airport.name AS airport_departure_name,airport_city AS airport_departure_city,airport_country AS airport_departure_country,departure AS airport_departure_icao_count, airport.latitude AS airport_departure_latitude, airport.longitude AS airport_departure_longitude FROM stats_airport,airport WHERE airport.icao = stats_airport.airport_icao AND departure > 0 AND stats_type = 'yearly' AND stats_airline = :stats_airline AND filter_name = :filter_name ORDER BY airport_departure_icao_count DESC";
				try {
					$sth = $this->db->prepare($query);
					$sth->execute(array(':stats_airline' => $stats_airline,':filter_name' => $filter_name));
				} catch(PDOException $e) {
					echo "error : ".$e->getMessage();
				}
				$all = $sth->fetchAll(PDO::FETCH_ASSOC);
			} else $all = array();
		}
		if (empty($all)) {
			if (strpos($stats_airline,'alliance_') !== FALSE) {
				$filters = array('alliance' => str_replace('_',' ',str_replace('alliance_','',$stats_airline)),'year' => $year,'month' => $month);
			} else {
				$filters = array('airlines' => array($stats_airline),'year' => $year,'month' => $month);
			}
			if ($filter_name != '') {
				$filters = array_merge($filters,$globalStatsFilters[$filter_name]);
			}
			$Spotter = new Spotter($this->db);
//            		$pall = $Spotter->countAllDepartureAirports($limit,0,'',$filters,$year,$month);
  //      		$dall = $Spotter->countAllDetectedDepartureAirports($limit,0,'',$filters,$year,$month);
			$pall = $Spotter->countAllDepartureAirports($limit,0,'',$filters);
			$dall = $Spotter->countAllDetectedDepartureAirports($limit,0,'',$filters);
			$all = array();
			foreach ($pall as $value) {
				$icao = $value['airport_departure_icao'];
				$all[$icao] = $value;
			}
			foreach ($dall as $value) {
				$icao = $value['airport_departure_icao'];
				if (isset($all[$icao])) {
					$all[$icao]['airport_departure_icao_count'] = $all[$icao]['airport_departure_icao_count'] + $value['airport_departure_icao_count'];
				} else $all[$icao] = $value;
			}
			$count = array();
			foreach ($all as $key => $row) {
				$count[$key] = $row['airport_departure_icao_count'];
			}
			array_multisort($count,SORT_DESC,$all);
		}
		return $all;
	}
	public function countAllArrivalAirports($limit = true,$stats_airline = '',$filter_name = '',$year = '',$month = '') {
		global $globalStatsFilters;
		if ($filter_name == '') $filter_name = $this->filter_name;
		if (strpos($stats_airline,'alliance_') !== FALSE) {
			$Spotter = new Spotter($this->db);
			$airlines = $Spotter->getAllAirlineNamesByAlliance(str_replace('_',' ',str_replace('alliance_','',$stats_airline)));
			if ($year == '' && $month == '') {
				$alliance_airlines = array();
				foreach ($airlines as $airline) {
					$alliance_airlines = array_merge($alliance_airlines,array($airline['airline_icao']));
				}
				if ($limit) $query = "SELECT DISTINCT airport_icao AS airport_arrival_icao,airport.name AS airport_arrival_name, airport_city AS airport_arrival_city,airport_country AS airport_arrival_country,arrival AS airport_arrival_icao_count, airport.latitude AS airport_arrival_latitude, airport.longitude AS airport_arrival_longitude FROM stats_airport, airport WHERE airport.icao = stats_airport.airport_icao AND arrival > 0 AND stats_type = 'yearly' AND stats_airline IN ('".implode("','",$alliance_airlines)."') AND filter_name = :filter_name ORDER BY airport_arrival_icao_count DESC LIMIT 10 OFFSET 0";
				else $query = "SELECT DISTINCT airport_icao AS airport_arrival_icao,airport.name AS airport_arrival_name, airport_city AS airport_arrival_city,airport_country AS airport_arrival_country,arrival AS airport_arrival_icao_count, airport.latitude AS airport_arrival_latitude, airport.longitude AS airport_arrival_longitude FROM stats_airport, airport WHERE airport.icao = stats_airport.airport_icao AND arrival > 0 AND stats_type = 'yearly' AND stats_airline IN ('".implode("','",$alliance_airlines)."') AND filter_name = :filter_name ORDER BY airport_arrival_icao_count DESC";
				try {
					$sth = $this->db->prepare($query);
					$sth->execute(array(':filter_name' => $filter_name));
				} catch(PDOException $e) {
					echo "error : ".$e->getMessage();
				}
				$all = $sth->fetchAll(PDO::FETCH_ASSOC);
			} else $all = array();
		} else {
			if ($year == '' && $month == '') {
				if ($limit) $query = "SELECT DISTINCT airport_icao AS airport_arrival_icao,airport.name AS airport_arrival_name, airport_city AS airport_arrival_city,airport_country AS airport_arrival_country,arrival AS airport_arrival_icao_count, airport.latitude AS airport_arrival_latitude, airport.longitude AS airport_arrival_longitude FROM stats_airport, airport WHERE airport.icao = stats_airport.airport_icao AND arrival > 0 AND stats_type = 'yearly' AND stats_airline = :stats_airline AND filter_name = :filter_name ORDER BY airport_arrival_icao_count DESC LIMIT 10 OFFSET 0";
				else $query = "SELECT DISTINCT airport_icao AS airport_arrival_icao,airport.name AS airport_arrival_name, airport_city AS airport_arrival_city,airport_country AS airport_arrival_country,arrival AS airport_arrival_icao_count, airport.latitude AS airport_arrival_latitude, airport.longitude AS airport_arrival_longitude FROM stats_airport, airport WHERE airport.icao = stats_airport.airport_icao AND arrival > 0 AND stats_type = 'yearly' AND stats_airline = :stats_airline AND filter_name = :filter_name ORDER BY airport_arrival_icao_count DESC";
				try {
					$sth = $this->db->prepare($query);
					$sth->execute(array(':stats_airline' => $stats_airline,':filter_name' => $filter_name));
				} catch(PDOException $e) {
					echo "error : ".$e->getMessage();
				}
				$all = $sth->fetchAll(PDO::FETCH_ASSOC);
			} else $all = array();
		}
		if (empty($all)) {
			if (strpos($stats_airline,'alliance_') !== FALSE) {
				$filters = array('alliance' => str_replace('_',' ',str_replace('alliance_','',$stats_airline)),'year' => $year,'month' => $month);
			} else {
				$filters = array('airlines' => array($stats_airline),'year' => $year,'month' => $month);
			}
			if ($filter_name != '') {
				$filters = array_merge($filters,$globalStatsFilters[$filter_name]);
			}
			$Spotter = new Spotter($this->db);
//			$pall = $Spotter->countAllArrivalAirports($limit,0,'',false,$filters,$year,$month);
//			$dall = $Spotter->countAllDetectedArrivalAirports($limit,0,'',false,$filters,$year,$month);
			$pall = $Spotter->countAllArrivalAirports($limit,0,'',false,$filters);
			$dall = $Spotter->countAllDetectedArrivalAirports($limit,0,'',false,$filters);
			$all = array();
			foreach ($pall as $value) {
				$icao = $value['airport_arrival_icao'];
				$all[$icao] = $value;
			}
			foreach ($dall as $value) {
				$icao = $value['airport_arrival_icao'];
				if (isset($all[$icao])) {
					$all[$icao]['airport_arrival_icao_count'] = $all[$icao]['airport_arrival_icao_count'] + $value['airport_arrival_icao_count'];
				} else $all[$icao] = $value;
			}
			$count = array();
			foreach ($all as $key => $row) {
				$count[$key] = $row['airport_arrival_icao_count'];
			}
			array_multisort($count,SORT_DESC,$all);
		}
		return $all;
	}
	public function countAllMonthsLastYear($limit = true,$stats_airline = '',$filter_name = '') {
		global $globalDBdriver, $globalStatsFilters;
		if ($filter_name == '') $filter_name = $this->filter_name;
		if (strpos($stats_airline,'alliance_') !== FALSE) {
			$Spotter = new Spotter($this->db);
			$airlines = $Spotter->getAllAirlineNamesByAlliance(str_replace('_',' ',str_replace('alliance_','',$stats_airline)));
			$alliance_airlines = array();
			foreach ($airlines as $airline) {
				$alliance_airlines = array_merge($alliance_airlines,array($airline['airline_icao']));
			}
			if ($globalDBdriver == 'mysql') {
				if ($limit) $query = "SELECT MONTH(stats_date) as month_name, YEAR(stats_date) as year_name, SUM(cnt) as date_count FROM stats WHERE stats_type = 'flights_bymonth' AND stats_date >= DATE_SUB(UTC_TIMESTAMP(),INTERVAL 12 MONTH) AND stats_airline IN ('".implode("','",$alliance_airlines)."') AND filter_name = :filter_name GROUP BY stats_date";
				else $query = "SELECT MONTH(stats_date) as month_name, YEAR(stats_date) as year_name, SUM(cnt) as date_count FROM stats WHERE stats_type = 'flights_bymonth' AND stats_airline IN ('".implode("','",$alliance_airlines)."') AND filter_name = :filter_name GROUP BY stats_date";
			} else {
				if ($limit) $query = "SELECT EXTRACT(MONTH FROM stats_date) as month_name, EXTRACT(YEAR FROM stats_date) as year_name, SUM(cnt) as date_count FROM stats WHERE stats_type = 'flights_bymonth' AND stats_date >= CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '12 MONTHS' AND stats_airline IN ('".implode("','",$alliance_airlines)."') AND filter_name = :filter_name GROUP BY stats_date";
				else $query = "SELECT EXTRACT(MONTH FROM stats_date) as month_name, EXTRACT(YEAR FROM stats_date) as year_name, SUM(cnt) as date_count FROM stats WHERE stats_type = 'flights_bymonth' AND stats_airline IN ('".implode("','",$alliance_airlines)."') AND filter_name = :filter_name GROUP BY stats_date";
			}
			$query_data = array(':filter_name' => $filter_name);
		} else {
			if ($globalDBdriver == 'mysql') {
				if ($limit) $query = "SELECT MONTH(stats_date) as month_name, YEAR(stats_date) as year_name, cnt as date_count FROM stats WHERE stats_type = 'flights_bymonth' AND stats_date >= DATE_SUB(UTC_TIMESTAMP(),INTERVAL 12 MONTH) AND stats_airline = :stats_airline AND filter_name = :filter_name";
				else $query = "SELECT MONTH(stats_date) as month_name, YEAR(stats_date) as year_name, cnt as date_count FROM stats WHERE stats_type = 'flights_bymonth' AND stats_airline = :stats_airline AND filter_name = :filter_name";
			} else {
				if ($limit) $query = "SELECT EXTRACT(MONTH FROM stats_date) as month_name, EXTRACT(YEAR FROM stats_date) as year_name, cnt as date_count FROM stats WHERE stats_type = 'flights_bymonth' AND stats_date >= CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '12 MONTHS' AND stats_airline = :stats_airline AND filter_name = :filter_name";
				else $query = "SELECT EXTRACT(MONTH FROM stats_date) as month_name, EXTRACT(YEAR FROM stats_date) as year_name, cnt as date_count FROM stats WHERE stats_type = 'flights_bymonth' AND stats_airline = :stats_airline AND filter_name = :filter_name";
			}
			$query_data = array(':stats_airline' => $stats_airline,':filter_name' => $filter_name);
		}
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_data);
		} catch(PDOException $e) {
			echo "error : ".$e->getMessage();
		}
		$all = $sth->fetchAll(PDO::FETCH_ASSOC);
		if (empty($all)) {
			if (strpos($stats_airline,'alliance_') !== FALSE) {
				$filters = array('alliance' => str_replace('_',' ',str_replace('alliance_','',$stats_airline)));
			} else {
				$filters = array('airlines' => array($stats_airline));
			}
			if ($filter_name != '') {
				$filters = array_merge($filters,$globalStatsFilters[$filter_name]);
			}
			$Spotter = new Spotter($this->db);
			$all = $Spotter->countAllMonthsLastYear($filters);
		}
		return $all;
	}

	public function countAllMarineMonthsLastYear($limit = true,$filter_name = '') {
		global $globalDBdriver, $globalStatsFilters;
		if ($filter_name == '') $filter_name = $this->filter_name;
		if ($globalDBdriver == 'mysql') {
			if ($limit) $query = "SELECT MONTH(stats_date) as month_name, YEAR(stats_date) as year_name, cnt as date_count FROM stats WHERE stats_type = 'marine_bymonth' AND stats_date >= DATE_SUB(UTC_TIMESTAMP(),INTERVAL 12 MONTH) AND filter_name = :filter_name";
			else $query = "SELECT MONTH(stats_date) as month_name, YEAR(stats_date) as year_name, cnt as date_count FROM stats WHERE stats_type = 'marine_bymonth' AND filter_name = :filter_name";
		} else {
			if ($limit) $query = "SELECT EXTRACT(MONTH FROM stats_date) as month_name, EXTRACT(YEAR FROM stats_date) as year_name, cnt as date_count FROM stats WHERE stats_type = 'marine_bymonth' AND stats_date >= CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '12 MONTHS' AND filter_name = :filter_name";
			else $query = "SELECT EXTRACT(MONTH FROM stats_date) as month_name, EXTRACT(YEAR FROM stats_date) as year_name, cnt as date_count FROM stats WHERE stats_type = 'marine_bymonth' AND filter_name = :filter_name";
		}
		$query_data = array(':filter_name' => $filter_name);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_data);
		} catch(PDOException $e) {
			echo "error : ".$e->getMessage();
		}
		$all = $sth->fetchAll(PDO::FETCH_ASSOC);
		if (empty($all)) {
			$filters = array();
			if ($filter_name != '') {
				$filters = array_merge($filters,$globalStatsFilters[$filter_name]);
			}
			$Marine = new Marine($this->db);
			$all = $Marine->countAllMonthsLastYear($filters);
		}
		return $all;
	}

	public function countAllTrackerMonthsLastYear($limit = true,$filter_name = '') {
		global $globalDBdriver, $globalStatsFilters;
		if ($filter_name == '') $filter_name = $this->filter_name;
		if ($globalDBdriver == 'mysql') {
			if ($limit) $query = "SELECT MONTH(stats_date) as month_name, YEAR(stats_date) as year_name, cnt as date_count FROM stats WHERE stats_type = 'tracker_bymonth' AND stats_date >= DATE_SUB(UTC_TIMESTAMP(),INTERVAL 12 MONTH) AND filter_name = :filter_name";
			else $query = "SELECT MONTH(stats_date) as month_name, YEAR(stats_date) as year_name, cnt as date_count FROM stats WHERE stats_type = 'tracker_bymonth' AND filter_name = :filter_name";
		} else {
			if ($limit) $query = "SELECT EXTRACT(MONTH FROM stats_date) as month_name, EXTRACT(YEAR FROM stats_date) as year_name, cnt as date_count FROM stats WHERE stats_type = 'tracker_bymonth' AND stats_date >= CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '12 MONTHS' AND filter_name = :filter_name";
			else $query = "SELECT EXTRACT(MONTH FROM stats_date) as month_name, EXTRACT(YEAR FROM stats_date) as year_name, cnt as date_count FROM stats WHERE stats_type = 'tracker_bymonth' AND filter_name = :filter_name";
		}
		$query_data = array(':filter_name' => $filter_name);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_data);
		} catch(PDOException $e) {
			echo "error : ".$e->getMessage();
		}
		$all = $sth->fetchAll(PDO::FETCH_ASSOC);
		if (empty($all)) {
			$filters = array();
			if ($filter_name != '') {
				$filters = array_merge($filters,$globalStatsFilters[$filter_name]);
			}
			$Tracker = new Tracker($this->db);
			$all = $Tracker->countAllMonthsLastYear($filters);
		}
		return $all;
	}
	
	public function countAllDatesLastMonth($stats_airline = '',$filter_name = '') {
		global $globalStatsFilters;
		if ($filter_name == '') $filter_name = $this->filter_name;
		if (strpos($stats_airline,'alliance_') !== FALSE) {
			$Spotter = new Spotter($this->db);
			$airlines = $Spotter->getAllAirlineNamesByAlliance(str_replace('_',' ',str_replace('alliance_','',$stats_airline)));
			$alliance_airlines = array();
			foreach ($airlines as $airline) {
				$alliance_airlines = array_merge($alliance_airlines,array($airline['airline_icao']));
			}
			$query = "SELECT flight_date as date_name, SUM(cnt) as date_count FROM stats_flight WHERE stats_type = 'month' AND stats_airline  IN ('".implode("','",$alliance_airlines)."') AND filter_name = :filter_name GROUP BY flight_date";
			$query_data = array(':filter_name' => $filter_name);
		} else {
			$query = "SELECT flight_date as date_name, cnt as date_count FROM stats_flight WHERE stats_type = 'month' AND stats_airline = :stats_airline AND filter_name = :filter_name";
			$query_data = array(':stats_airline' => $stats_airline,':filter_name' => $filter_name);
		}
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_data);
		} catch(PDOException $e) {
			echo "error : ".$e->getMessage();
		}
		$all = $sth->fetchAll(PDO::FETCH_ASSOC);
		if (empty($all)) {
			if (strpos($stats_airline,'alliance_') !== FALSE) {
				$filters = array('alliance' => str_replace('_',' ',str_replace('alliance_','',$stats_airline)));
			} else {
				$filters = array('airlines' => array($stats_airline));
			}
			if ($filter_name != '') {
				$filters = array_merge($filters,$globalStatsFilters[$filter_name]);
			}
			$Spotter = new Spotter($this->db);
			$all = $Spotter->countAllDatesLastMonth($filters);
		}
		return $all;
	}
	public function countAllMarineDatesLastMonth($filter_name = '') {
		global $globalStatsFilters;
		if ($filter_name == '') $filter_name = $this->filter_name;
		$query = "SELECT marine_date as date_name, cnt as date_count FROM stats_marine WHERE stats_type = 'month' AND filter_name = :filter_name";
		$query_data = array(':filter_name' => $filter_name);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_data);
		} catch(PDOException $e) {
			echo "error : ".$e->getMessage();
		}
		$all = $sth->fetchAll(PDO::FETCH_ASSOC);
		if (empty($all)) {
			$filters = array();
			if ($filter_name != '') {
				$filters = array_merge($filters,$globalStatsFilters[$filter_name]);
			}
			$Marine = new Marine($this->db);
			$all = $Marine->countAllDatesLastMonth($filters);
		}
		return $all;
	}
	public function countAllTrackerDatesLastMonth($filter_name = '') {
		global $globalStatsFilters;
		if ($filter_name == '') $filter_name = $this->filter_name;
		$query = "SELECT tracker_date as date_name, cnt as date_count FROM stats_tracker WHERE stats_type = 'month' AND filter_name = :filter_name";
		$query_data = array(':filter_name' => $filter_name);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_data);
		} catch(PDOException $e) {
			echo "error : ".$e->getMessage();
		}
		$all = $sth->fetchAll(PDO::FETCH_ASSOC);
		if (empty($all)) {
			$filters = array();
			if ($filter_name != '') {
				$filters = array_merge($filters,$globalStatsFilters[$filter_name]);
			}
			$Tracker = new Tracker($this->db);
			$all = $Tracker->countAllDatesLastMonth($filters);
		}
		return $all;
	}
	public function countAllDatesLast7Days($stats_airline = '',$filter_name = '') {
		global $globalDBdriver, $globalStatsFilters;
		if ($filter_name == '') $filter_name = $this->filter_name;
		if (strpos($stats_airline,'alliance_') !== FALSE) {
			$Spotter = new Spotter($this->db);
			$airlines = $Spotter->getAllAirlineNamesByAlliance(str_replace('_',' ',str_replace('alliance_','',$stats_airline)));
			$alliance_airlines = array();
			foreach ($airlines as $airline) {
				$alliance_airlines = array_merge($alliance_airlines,array($airline['airline_icao']));
			}
			if ($globalDBdriver == 'mysql') {
				$query = "SELECT flight_date as date_name, SUM(cnt) as date_count FROM stats_flight WHERE stats_type = 'month' AND flight_date >= DATE_SUB(UTC_TIMESTAMP(),INTERVAL 7 DAY) AND stats_airline IN ('".implode("','",$alliance_airlines)."') AND filter_name = :filter_name GROUP BY flight_date";
			} else {
				$query = "SELECT flight_date as date_name, SUM(cnt) as date_count FROM stats_flight WHERE stats_type = 'month' AND flight_date::timestamp >= CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '7 DAYS' AND stats_airline IN ('".implode("','",$alliance_airlines)."') AND filter_name = :filter_name GROUP BY flight_date";
			}
			$query_data = array(':filter_name' => $filter_name);
		} else {
			if ($globalDBdriver == 'mysql') {
				$query = "SELECT flight_date as date_name, cnt as date_count FROM stats_flight WHERE stats_type = 'month' AND flight_date >= DATE_SUB(UTC_TIMESTAMP(),INTERVAL 7 DAY) AND stats_airline = :stats_airline AND filter_name = :filter_name";
			} else {
				$query = "SELECT flight_date as date_name, cnt as date_count FROM stats_flight WHERE stats_type = 'month' AND flight_date::timestamp >= CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '7 DAYS' AND stats_airline = :stats_airline AND filter_name = :filter_name";
			}
			$query_data = array(':stats_airline' => $stats_airline,':filter_name' => $filter_name);
		}
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_data);
		} catch(PDOException $e) {
			echo "error : ".$e->getMessage();
		}
		$all = $sth->fetchAll(PDO::FETCH_ASSOC);
		if (empty($all)) {
			if (strpos($stats_airline,'alliance_') !== FALSE) {
				$filters = array('alliance' => str_replace('_',' ',str_replace('alliance_','',$stats_airline)));
			} else {
				$filters = array('airlines' => array($stats_airline));
			}
			if ($filter_name != '') {
				$filters = array_merge($filters,$globalStatsFilters[$filter_name]);
			}
			$Spotter = new Spotter($this->db);
			$all = $Spotter->countAllDatesLast7Days($filters);
		}
		return $all;
	}
	public function countAllMarineDatesLast7Days($filter_name = '') {
		global $globalDBdriver, $globalStatsFilters;
		if ($filter_name == '') $filter_name = $this->filter_name;
		if ($globalDBdriver == 'mysql') {
			$query = "SELECT marine_date as date_name, cnt as date_count FROM stats_marine WHERE stats_type = 'month' AND marine_date >= DATE_SUB(UTC_TIMESTAMP(),INTERVAL 7 DAY) AND filter_name = :filter_name";
		} else {
			$query = "SELECT marine_date as date_name, cnt as date_count FROM stats_marine WHERE stats_type = 'month' AND marine_date::timestamp >= CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '7 DAYS' AND filter_name = :filter_name";
		}
		$query_data = array(':filter_name' => $filter_name);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_data);
		} catch(PDOException $e) {
			echo "error : ".$e->getMessage();
		}
		$all = $sth->fetchAll(PDO::FETCH_ASSOC);
		if (empty($all)) {
			$filters = array();
			if ($filter_name != '') {
				$filters = array_merge($filters,$globalStatsFilters[$filter_name]);
			}
			$Marine = new Marine($this->db);
			$all = $Marine->countAllDatesLast7Days($filters);
		}
		return $all;
	}
	public function countAllTrackerDatesLast7Days($filter_name = '') {
		global $globalDBdriver, $globalStatsFilters;
		if ($filter_name == '') $filter_name = $this->filter_name;
		if ($globalDBdriver == 'mysql') {
			$query = "SELECT tracker_date as date_name, cnt as date_count FROM stats_tracker WHERE stats_type = 'month' AND tracker_date >= DATE_SUB(UTC_TIMESTAMP(),INTERVAL 7 DAY) AND filter_name = :filter_name";
		} else {
			$query = "SELECT tracker_date as date_name, cnt as date_count FROM stats_tracker WHERE stats_type = 'month' AND tracker_date::timestamp >= CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '7 DAYS' AND filter_name = :filter_name";
		}
		$query_data = array(':filter_name' => $filter_name);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_data);
		} catch(PDOException $e) {
			echo "error : ".$e->getMessage();
		}
		$all = $sth->fetchAll(PDO::FETCH_ASSOC);
		if (empty($all)) {
			$filters = array();
			if ($filter_name != '') {
				$filters = array_merge($filters,$globalStatsFilters[$filter_name]);
			}
			$Tracker = new Tracker($this->db);
			$all = $Tracker->countAllDatesLast7Days($filters);
		}
		return $all;
	}
	public function countAllDates($stats_airline = '',$filter_name = '') {
		global $globalStatsFilters;
		if ($filter_name == '') $filter_name = $this->filter_name;
		if (strpos($stats_airline,'alliance_') !== FALSE) {
			$Spotter = new Spotter($this->db);
			$airlines = $Spotter->getAllAirlineNamesByAlliance(str_replace('_',' ',str_replace('alliance_','',$stats_airline)));
			$alliance_airlines = array();
			foreach ($airlines as $airline) {
				$alliance_airlines = array_merge($alliance_airlines,array($airline['airline_icao']));
			}
			$query = "SELECT flight_date as date_name, SUM(cnt) as date_count FROM stats_flight WHERE stats_type = 'date' AND stats_airline IN ('".implode("','",$alliance_airlines)."') AND filter_name = :filter_name GROUP BY flight_date ORDER BY date_count DESC";
			$query_data = array(':filter_name' => $filter_name);
		} else {
			$query = "SELECT flight_date as date_name, cnt as date_count FROM stats_flight WHERE stats_type = 'date' AND stats_airline = :stats_airline AND filter_name = :filter_name ORDER BY date_count DESC";
			$query_data = array(':stats_airline' => $stats_airline,':filter_name' => $filter_name);
		}
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_data);
		} catch(PDOException $e) {
			echo "error : ".$e->getMessage();
		}
		$all = $sth->fetchAll(PDO::FETCH_ASSOC);
		if (empty($all)) {
			if (strpos($stats_airline,'alliance_') !== FALSE) {
				$filters = array('alliance' => str_replace('_',' ',str_replace('alliance_','',$stats_airline)));
			} else {
				$filters = array('airlines' => array($stats_airline));
			}
			if ($filter_name != '') {
				$filters = array_merge($filters,$globalStatsFilters[$filter_name]);
			}
			$Spotter = new Spotter($this->db);
			$all = $Spotter->countAllDates($filters);
		}
		return $all;
	}
	public function countAllDatesMarine($filter_name = '') {
		global $globalStatsFilters;
		if ($filter_name == '') $filter_name = $this->filter_name;
		$query = "SELECT marine_date as date_name, cnt as date_count FROM stats_marine WHERE stats_type = 'date' AND filter_name = :filter_name ORDER BY date_count DESC";
		$query_data = array(':filter_name' => $filter_name);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_data);
		} catch(PDOException $e) {
			echo "error : ".$e->getMessage();
		}
		$all = $sth->fetchAll(PDO::FETCH_ASSOC);
		if (empty($all)) {
			$filters = array();
			if ($filter_name != '') {
				$filters = $globalStatsFilters[$filter_name];
			}
			$Marine = new Marine($this->db);
			$all = $Marine->countAllDates($filters);
		}
		return $all;
	}
	public function countAllDatesTracker($filter_name = '') {
		global $globalStatsFilters;
		if ($filter_name == '') $filter_name = $this->filter_name;
		$query = "SELECT tracker_date as date_name, cnt as date_count FROM stats_tracker WHERE stats_type = 'date' AND filter_name = :filter_name ORDER BY date_count DESC";
		$query_data = array(':filter_name' => $filter_name);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_data);
		} catch(PDOException $e) {
			echo "error : ".$e->getMessage();
		}
		$all = $sth->fetchAll(PDO::FETCH_ASSOC);
		if (empty($all)) {
			$filters = array();
			if ($filter_name != '') {
				$filters = $globalStatsFilters[$filter_name];
			}
			$Tracker = new Tracker($this->db);
			$all = $Tracker->countAllDates($filters);
		}
		return $all;
	}
	public function countAllDatesByAirlines($filter_name = '') {
		global $globalStatsFilters;
		if ($filter_name == '') $filter_name = $this->filter_name;
		$query = "SELECT stats_airline as airline_icao, flight_date as date_name, cnt as date_count FROM stats_flight WHERE stats_type = 'date' AND filter_name = :filter_name";
		$query_data = array('filter_name' => $filter_name);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_data);
		} catch(PDOException $e) {
			echo "error : ".$e->getMessage();
		}
		$all = $sth->fetchAll(PDO::FETCH_ASSOC);
		if (empty($all)) {
			$filters = array();
			if ($filter_name != '') {
				$filters = array_merge($filters,$globalStatsFilters[$filter_name]);
			}
			$Spotter = new Spotter($this->db);
			$all = $Spotter->countAllDatesByAirlines($filters);
		}
		return $all;
	}
	public function countAllMonths($stats_airline = '',$filter_name = '') {
		global $globalStatsFilters, $globalDBdriver;
		if ($filter_name == '') $filter_name = $this->filter_name;
		if (strpos($stats_airline,'alliance_') !== FALSE) {
			$Spotter = new Spotter($this->db);
			$airlines = $Spotter->getAllAirlineNamesByAlliance(str_replace('_',' ',str_replace('alliance_','',$stats_airline)));
			$alliance_airlines = array();
			foreach ($airlines as $airline) {
				$alliance_airlines = array_merge($alliance_airlines,array($airline['airline_icao']));
			}
			if ($globalDBdriver == 'mysql') {
				$query = "SELECT YEAR(stats_date) AS year_name,MONTH(stats_date) AS month_name, SUM(cnt) as date_count FROM stats WHERE stats_type = 'flights_bymonth' AND stats_airline IN ('".implode("','",$alliance_airlines)."') AND filter_name = :filter_name GROUP BY stats_date ORDER BY date_count DESC";
			} else {
				$query = "SELECT EXTRACT(YEAR FROM stats_date) AS year_name,EXTRACT(MONTH FROM stats_date) AS month_name, SUM(cnt) as date_count FROM stats WHERE stats_type = 'flights_bymonth' AND stats_airline IN ('".implode("','",$alliance_airlines)."') AND filter_name = :filter_name GROUP BY stats_date ORDER BY date_count DESC";
			}
			$query_data = array(':filter_name' => $filter_name);
		} else {
			if ($globalDBdriver == 'mysql') {
				$query = "SELECT YEAR(stats_date) AS year_name,MONTH(stats_date) AS month_name, cnt as date_count FROM stats WHERE stats_type = 'flights_bymonth' AND stats_airline = :stats_airline AND filter_name = :filter_name ORDER BY date_count DESC";
			} else {
				$query = "SELECT EXTRACT(YEAR FROM stats_date) AS year_name,EXTRACT(MONTH FROM stats_date) AS month_name, cnt as date_count FROM stats WHERE stats_type = 'flights_bymonth' AND stats_airline = :stats_airline AND filter_name = :filter_name ORDER BY date_count DESC";
			}
			$query_data = array(':stats_airline' => $stats_airline, ':filter_name' => $filter_name);
		}
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_data);
		} catch(PDOException $e) {
			echo "error : ".$e->getMessage();
		}
		$all = $sth->fetchAll(PDO::FETCH_ASSOC);
		if (empty($all)) {
			if (strpos($stats_airline,'alliance_') !== FALSE) {
				$filters = array('alliance' => str_replace('_',' ',str_replace('alliance_','',$stats_airline)));
			} else {
				$filters = array('airlines' => array($stats_airline));
			}
			if ($filter_name != '') {
				$filters = array_merge($filters,$globalStatsFilters[$filter_name]);
			}
			$Spotter = new Spotter($this->db);
			$all = $Spotter->countAllMonths($filters);
		}
		return $all;
	}
	public function countFatalitiesLast12Months() {
		global $globalStatsFilters, $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			$query = "SELECT YEAR(stats_date) AS year, MONTH(stats_date) as month,cnt as count FROM stats WHERE stats_type = 'fatalities_bymonth' ORDER BY stats_date";
		} else {
			$query = "SELECT EXTRACT(YEAR FROM stats_date) AS year, EXTRACT(MONTH FROM stats_date) as month,cnt as count FROM stats WHERE stats_type = 'fatalities_bymonth' ORDER BY stats_date";
		}
		try {
			$sth = $this->db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			echo "error : ".$e->getMessage();
		}
		$all = $sth->fetchAll(PDO::FETCH_ASSOC);
		if (empty($all)) {
			$Accident = new Accident($this->db);
			$all = $Accident->countFatalitiesLast12Months();
		}
		return $all;
	}
	public function countFatalitiesByYear() {
		global $globalStatsFilters, $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			$query = "SELECT YEAR(stats_date) AS year, cnt as count FROM stats WHERE stats_type = 'fatalities_byyear' ORDER BY stats_date";
		} else {
			$query = "SELECT EXTRACT(YEAR FROM stats_date) AS year, cnt as count FROM stats WHERE stats_type = 'fatalities_byyear' ORDER BY stats_date";
		}
		try {
			$sth = $this->db->prepare($query);
			$sth->execute();
		} catch(PDOException $e) {
			echo "error : ".$e->getMessage();
		}
		$all = $sth->fetchAll(PDO::FETCH_ASSOC);
		if (empty($all)) {
			$Accident = new Accident($this->db);
			$all = $Accident->countFatalitiesByYear();
		}
		return $all;
	}
	public function countAllMilitaryMonths($filter_name = '') {
		global $globalStatsFilters;
		if ($filter_name == '') $filter_name = $this->filter_name;
		$query = "SELECT YEAR(stats_date) AS year_name,MONTH(stats_date) AS month_name, cnt as date_count FROM stats WHERE stats_type = 'military_flights_bymonth' AND filter_name = :filter_name";
		try {
			$sth = $this->db->prepare($query);
			$sth->execute(array(':filter_name' => $filter_name));
		} catch(PDOException $e) {
			echo "error : ".$e->getMessage();
		}
		$all = $sth->fetchAll(PDO::FETCH_ASSOC);
		if (empty($all)) {
			$filters = array();
			if ($filter_name != '') {
					$filters = array_merge($filters,$globalStatsFilters[$filter_name]);
			}
			$Spotter = new Spotter($this->db);
			$all = $Spotter->countAllMilitaryMonths($filters);
		}
		return $all;
	}
	public function countAllHours($orderby = 'hour',$limit = true,$stats_airline = '',$filter_name = '') {
		global $globalTimezone, $globalDBdriver, $globalStatsFilters;
		if ($filter_name == '') $filter_name = $this->filter_name;
		if (strpos($stats_airline,'alliance_') !== FALSE) {
			$Spotter = new Spotter($this->db);
			$airlines = $Spotter->getAllAirlineNamesByAlliance(str_replace('_',' ',str_replace('alliance_','',$stats_airline)));
			$alliance_airlines = array();
			foreach ($airlines as $airline) {
				$alliance_airlines = array_merge($alliance_airlines,array($airline['airline_icao']));
			}
			if ($limit) $query = "SELECT flight_date as hour_name, SUM(cnt) as hour_count FROM stats_flight WHERE stats_type = 'hour' AND stats_airline IN ('".implode("','",$alliance_airlines)."') AND filter_name = :filter_name GROUP BY flight_date";
			else $query = "SELECT flight_date as hour_name, SUM(cnt) as hour_count FROM stats_flight WHERE stats_type = 'hour' AND stats_airline IN ('".implode("','",$alliance_airlines)."') AND filter_name = :filter_name GROUP BY flight_date";
			$query_data = array(':filter_name' => $filter_name);
		} else {
			if ($limit) $query = "SELECT flight_date as hour_name, cnt as hour_count FROM stats_flight WHERE stats_type = 'hour' AND stats_airline = :stats_airline AND filter_name = :filter_name";
			else $query = "SELECT flight_date as hour_name, cnt as hour_count FROM stats_flight WHERE stats_type = 'hour' AND stats_airline = :stats_airline AND filter_name = :filter_name";
			$query_data = array(':stats_airline' => $stats_airline, ':filter_name' => $filter_name);
		}
		if ($orderby == 'hour') {
			if ($globalDBdriver == 'mysql') {
				$query .= " ORDER BY CAST(flight_date AS UNSIGNED) ASC";
			} else {
				$query .= " ORDER BY CAST(flight_date AS integer) ASC";
			}
		}
		if ($orderby == 'count') $query .= " ORDER BY hour_count DESC";
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_data);
		} catch(PDOException $e) {
			echo "error : ".$e->getMessage();
		}
		$all = $sth->fetchAll(PDO::FETCH_ASSOC);
		if (empty($all)) {
			if (strpos($stats_airline,'alliance_') !== FALSE) {
				$filters = array('alliance' => str_replace('_',' ',str_replace('alliance_','',$stats_airline)));
			} else {
				$filters = array('airlines' => array($stats_airline));
			}
			if ($filter_name != '') {
				$filters = array_merge($filters,$globalStatsFilters[$filter_name]);
			}
			$Spotter = new Spotter($this->db);
			$all = $Spotter->countAllHours($orderby,$filters);
		}
		return $all;
	}
	public function countAllMarineHours($orderby = 'hour',$limit = true,$filter_name = '') {
		global $globalTimezone, $globalDBdriver, $globalStatsFilters;
		if ($filter_name == '') $filter_name = $this->filter_name;
		if ($limit) $query = "SELECT marine_date as hour_name, cnt as hour_count FROM stats_marine WHERE stats_type = 'hour' AND filter_name = :filter_name";
		else $query = "SELECT marine_date as hour_name, cnt as hour_count FROM stats_marine WHERE stats_type = 'hour' AND filter_name = :filter_name";
		$query_data = array(':filter_name' => $filter_name);
		if ($orderby == 'hour') {
			if ($globalDBdriver == 'mysql') {
				$query .= " ORDER BY CAST(marine_date AS UNSIGNED) ASC";
			} else {
				$query .= " ORDER BY CAST(marine_date AS integer) ASC";
			}
		}
		if ($orderby == 'count') $query .= " ORDER BY hour_count DESC";
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_data);
		} catch(PDOException $e) {
			echo "error : ".$e->getMessage();
		}
		$all = $sth->fetchAll(PDO::FETCH_ASSOC);
		if (empty($all)) {
			$filters = array();
			if ($filter_name != '') {
				$filters = array_merge($filters,$globalStatsFilters[$filter_name]);
			}
			$Marine = new Marine($this->db);
			$all = $Marine->countAllHours($orderby,$filters);
		}
		return $all;
	}
	public function countAllTrackerHours($orderby = 'hour',$limit = true,$filter_name = '') {
		global $globalTimezone, $globalDBdriver, $globalStatsFilters;
		if ($filter_name == '') $filter_name = $this->filter_name;
		if ($limit) $query = "SELECT tracker_date as hour_name, cnt as hour_count FROM stats_tracker WHERE stats_type = 'hour' AND filter_name = :filter_name";
		else $query = "SELECT tracker_date as hour_name, cnt as hour_count FROM stats_tracker WHERE stats_type = 'hour' AND filter_name = :filter_name";
		$query_data = array(':filter_name' => $filter_name);
		if ($orderby == 'hour') {
			if ($globalDBdriver == 'mysql') {
				$query .= " ORDER BY CAST(tracker_date AS UNSIGNED) ASC";
			} else {
				$query .= " ORDER BY CAST(tracker_date AS integer) ASC";
			}
		}
		if ($orderby == 'count') $query .= " ORDER BY hour_count DESC";
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_data);
		} catch(PDOException $e) {
			echo "error : ".$e->getMessage();
		}
		$all = $sth->fetchAll(PDO::FETCH_ASSOC);
		if (empty($all)) {
			$filters = array();
			if ($filter_name != '') {
				$filters = array_merge($filters,$globalStatsFilters[$filter_name]);
			}
			$Tracker = new Tracker($this->db);
			$all = $Tracker->countAllHours($orderby,$filters);
		}
		return $all;
	}
	public function countOverallFlights($stats_airline = '', $filter_name = '',$year = '',$month = '') {
		global $globalStatsFilters;
		if ($filter_name == '') $filter_name = $this->filter_name;
		if ($year == '') $year = date('Y');
		$all = $this->getSumStats('flights_bymonth',$year,$stats_airline,$filter_name,$month);
		if (empty($all)) {
			if (strpos($stats_airline,'alliance_') !== FALSE) {
				$filters = array('alliance' => str_replace('_',' ',str_replace('alliance_','',$stats_airline)),'year' => $year,'month' => $month);
			} else {
				$filters = array('airlines' => array($stats_airline),'year' => $year,'month' => $month);
			}
			if ($filter_name != '') {
				$filters = array_merge($filters,$globalStatsFilters[$filter_name]);
			}
			$Spotter = new Spotter($this->db);
			//$all = $Spotter->countOverallFlights($filters,$year,$month);
			$all = $Spotter->countOverallFlights($filters);
		}
		return $all;
	}
	public function countOverallMarine($filter_name = '',$year = '',$month = '') {
		global $globalStatsFilters;
		if ($filter_name == '') $filter_name = $this->filter_name;
		if ($year == '') $year = date('Y');
		$all = $this->getSumStats('marine_bymonth',$year,'',$filter_name,$month);
		if (empty($all)) {
			$filters = array('year' => $year,'month' => $month);
			if ($filter_name != '') {
				$filters = array_merge($filters,$globalStatsFilters[$filter_name]);
			}
			$Marine = new Marine($this->db);
			//$all = $Spotter->countOverallFlights($filters,$year,$month);
			$all = $Marine->countOverallMarine($filters);
		}
		return $all;
	}
	public function countOverallTracker($filter_name = '',$year = '',$month = '') {
		global $globalStatsFilters;
		if ($filter_name == '') $filter_name = $this->filter_name;
		if ($year == '') $year = date('Y');
		$all = $this->getSumStats('tracker_bymonth',$year,'',$filter_name,$month);
		if (empty($all)) {
			$filters = array('year' => $year,'month' => $month);
			if ($filter_name != '') {
				$filters = array_merge($filters,$globalStatsFilters[$filter_name]);
			}
			$Tracker = new Tracker($this->db);
			//$all = $Spotter->countOverallFlights($filters,$year,$month);
			$all = $Tracker->countOverallTracker($filters);
		}
		return $all;
	}
	public function countOverallMilitaryFlights($filter_name = '',$year = '', $month = '') {
		global $globalStatsFilters;
		if ($filter_name == '') $filter_name = $this->filter_name;
		if ($year == '') $year = date('Y');
		$all = $this->getSumStats('military_flights_bymonth',$year,'',$filter_name,$month);
		if (empty($all)) {
			$filters = array();
			$filters = array('year' => $year,'month' => $month);
			if ($filter_name != '') {
				$filters = array_merge($filters,$globalStatsFilters[$filter_name]);
			}
			$Spotter = new Spotter($this->db);
			//$all = $Spotter->countOverallMilitaryFlights($filters,$year,$month);
			$all = $Spotter->countOverallMilitaryFlights($filters);
		}
		return $all;
	}
	public function countOverallArrival($stats_airline = '',$filter_name = '', $year = '', $month = '') {
		global $globalStatsFilters;
		if ($filter_name == '') $filter_name = $this->filter_name;
		if ($year == '') $year = date('Y');
		$all = $this->getSumStats('realarrivals_bymonth',$year,$stats_airline,$filter_name,$month);
		if (empty($all)) {
			if (strpos($stats_airline,'alliance_') !== FALSE) {
				$filters = array('alliance' => str_replace('_',' ',str_replace('alliance_','',$stats_airline)),'year' => $year,'month' => $month);
			} else {
				$filters = array('airlines' => array($stats_airline),'year' => $year,'month' => $month);
			}
			if ($filter_name != '') {
				$filters = array_merge($filters,$globalStatsFilters[$filter_name]);
			}
			$Spotter = new Spotter($this->db);
			//$all = $Spotter->countOverallArrival($filters,$year,$month);
			$all = $Spotter->countOverallArrival($filters);
		}
		return $all;
	}
	public function countOverallAircrafts($stats_airline = '',$filter_name = '',$year = '', $month = '') {
		global $globalStatsFilters;
		if ($filter_name == '') $filter_name = $this->filter_name;
		if (strpos($stats_airline,'alliance_') !== FALSE) {
			$Spotter = new Spotter($this->db);
			$airlines = $Spotter->getAllAirlineNamesByAlliance(str_replace('_',' ',str_replace('alliance_','',$stats_airline)));
			if ($year == '' && $month == '') {
				$alliance_airlines = array();
				foreach ($airlines as $airline) {
					$alliance_airlines = array_merge($alliance_airlines,array($airline['airline_icao']));
				}
				$query = "SELECT COUNT(*) AS nb FROM stats_aircraft WHERE stats_airline IN ('".implode("','",$alliance_airlines)."') AND filter_name = :filter_name";
				try {
					$sth = $this->db->prepare($query);
					$sth->execute(array(':filter_name' => $filter_name));
				} catch(PDOException $e) {
					echo "error : ".$e->getMessage();
				}
				$result = $sth->fetchAll(PDO::FETCH_ASSOC);
				$all = $result[0]['nb'];
			} else $all = $this->getSumStats('aircrafts_bymonth',$year,$stats_airline,$filter_name,$month);
		} else {
			if ($year == '' && $month == '') {
				$query = "SELECT COUNT(*) AS nb FROM stats_aircraft WHERE stats_airline = :stats_airline AND filter_name = :filter_name";
				try {
					$sth = $this->db->prepare($query);
					$sth->execute(array(':filter_name' => $filter_name,':stats_airline' => $stats_airline));
				} catch(PDOException $e) {
					echo "error : ".$e->getMessage();
				}
				$result = $sth->fetchAll(PDO::FETCH_ASSOC);
				$all = $result[0]['nb'];
			} else $all = $this->getSumStats('aircrafts_bymonth',$year,$stats_airline,$filter_name,$month);
		}
		if (empty($all)) {
			if (strpos($stats_airline,'alliance_') !== FALSE) {
				$filters = array('alliance' => str_replace('_',' ',str_replace('alliance_','',$stats_airline)),'year' => $year,'month' => $month);
			} else {
				$filters = array('airlines' => array($stats_airline),'year' => $year,'month' => $month);
			}
			if ($filter_name != '') {
				$filters = array_merge($filters,$globalStatsFilters[$filter_name]);
			}
			$Spotter = new Spotter($this->db);
			//$all = $Spotter->countOverallAircrafts($filters,$year,$month);
			$all = $Spotter->countOverallAircrafts($filters);
		}
		return $all;
	}
	public function countOverallAirlines($filter_name = '',$year = '',$month = '') {
		global $globalStatsFilters;
		if ($filter_name == '') $filter_name = $this->filter_name;
		if ($year == '' && $month == '') {
			$query = "SELECT COUNT(*) AS nb_airline FROM stats_airline WHERE filter_name = :filter_name";
			try {
				$sth = $this->db->prepare($query);
				$sth->execute(array(':filter_name' => $filter_name));
			} catch(PDOException $e) {
				echo "error : ".$e->getMessage();
			}
			$result = $sth->fetchAll(PDO::FETCH_ASSOC);
			$all = $result[0]['nb_airline'];
		} else $all = $this->getSumStats('airlines_bymonth',$year,'',$filter_name,$month);
		if (empty($all)) {
			$filters = array();
			$filters = array('year' => $year,'month' => $month);
			if ($filter_name != '') {
				$filters = array_merge($filters,$globalStatsFilters[$filter_name]);
			}
			$Spotter = new Spotter($this->db);
			//$all = $Spotter->countOverallAirlines($filters,$year,$month);
			$all = $Spotter->countOverallAirlines($filters);
		}
		return $all;
	}
	public function countOverallMarineTypes($filter_name = '',$year = '',$month = '') {
		global $globalStatsFilters;
		if ($filter_name == '') $filter_name = $this->filter_name;
		$all = array();
		if ($year == '' && $month == '') {
			$query = "SELECT COUNT(*) AS nb_type FROM stats_marine_type WHERE filter_name = :filter_name";
			try {
				$sth = $this->db->prepare($query);
				$sth->execute(array(':filter_name' => $filter_name));
			} catch(PDOException $e) {
				echo "error : ".$e->getMessage();
			}
			$result = $sth->fetchAll(PDO::FETCH_ASSOC);
			$all = $result[0]['nb_type'];
		}
		if (empty($all)) {
			$filters = array();
			$filters = array('year' => $year,'month' => $month);
			if ($filter_name != '') {
				$filters = array_merge($filters,$globalStatsFilters[$filter_name]);
			}
			$Marine = new Marine($this->db);
			//$all = $Spotter->countOverallAirlines($filters,$year,$month);
			$all = $Marine->countOverallMarineTypes($filters);
		}
		return $all;
	}
	public function countOverallOwners($stats_airline = '',$filter_name = '',$year = '', $month = '') {
		global $globalStatsFilters;
		if ($filter_name == '') $filter_name = $this->filter_name;
		if (strpos($stats_airline,'alliance_') !== FALSE) {
			$Spotter = new Spotter($this->db);
			$airlines = $Spotter->getAllAirlineNamesByAlliance(str_replace('_',' ',str_replace('alliance_','',$stats_airline)));
			if ($year == '' && $month == '') {
				$alliance_airlines = array();
				foreach ($airlines as $airline) {
					$alliance_airlines = array_merge($alliance_airlines,array($airline['airline_icao']));
				}
				$query = "SELECT count(*) as nb FROM stats_owner WHERE stats_airline IN ('".implode("','",$alliance_airlines)."') AND filter_name = :filter_name";
				$query_values = array(':filter_name' => $filter_name);
				try {
					$sth = $this->db->prepare($query);
					$sth->execute($query_values);
				} catch(PDOException $e) {
					echo "error : ".$e->getMessage();
				}
				$result = $sth->fetchAll(PDO::FETCH_ASSOC);
				$all = $result[0]['nb'];
			} else {
				$all = $this->getSumStats('owners_bymonth',$year,$stats_airline,$filter_name,$month);
			}
		} else {
			if ($year == '' && $month == '') {
				$query = "SELECT count(*) as nb FROM stats_owner WHERE stats_airline = :stats_airline AND filter_name = :filter_name";
				$query_values = array(':stats_airline' => $stats_airline, ':filter_name' => $filter_name);
				try {
					$sth = $this->db->prepare($query);
					$sth->execute($query_values);
				} catch(PDOException $e) {
					echo "error : ".$e->getMessage();
				}
				$result = $sth->fetchAll(PDO::FETCH_ASSOC);
				$all = $result[0]['nb'];
			} else {
				$all = $this->getSumStats('owners_bymonth',$year,$stats_airline,$filter_name,$month);
			}
		}
		if (empty($all)) {
			if (strpos($stats_airline,'alliance_') !== FALSE) {
				$filters = array('alliance' => str_replace('_',' ',str_replace('alliance_','',$stats_airline)),'year' => $year,'month' => $month);
			} else {
				$filters = array('airlines' => array($stats_airline),'year' => $year,'month' => $month);
			}
			if ($filter_name != '') {
				$filters = array_merge($filters,$globalStatsFilters[$filter_name]);
			}
			$Spotter = new Spotter($this->db);
			//$all = $Spotter->countOverallOwners($filters,$year,$month);
			$all = $Spotter->countOverallOwners($filters);
		}
		return $all;
	}
	public function countOverallPilots($stats_airline = '',$filter_name = '',$year = '',$month = '') {
		global $globalStatsFilters;
		if ($filter_name == '') $filter_name = $this->filter_name;
		//if ($year == '') $year = date('Y');
		if ($year == '' && $month == '') {
			$query = "SELECT count(*) as nb FROM stats_pilot WHERE stats_airline = :stats_airline AND filter_name = :filter_name";
			$query_values = array(':stats_airline' => $stats_airline, ':filter_name' => $filter_name);
			try {
				$sth = $this->db->prepare($query);
				$sth->execute($query_values);
			} catch(PDOException $e) {
				echo "error : ".$e->getMessage();
			}
			$result = $sth->fetchAll(PDO::FETCH_ASSOC);
			$all = $result[0]['nb'];
		} else {
			$all = $this->getSumStats('pilots_bymonth',$year,$stats_airline,$filter_name,$month);
		}
		if (empty($all)) {
			$filters = array('airlines' => array($stats_airline),'year' => $year,'month' => $month);
			if ($filter_name != '') {
				$filters = array_merge($filters,$globalStatsFilters[$filter_name]);
			}
			$Spotter = new Spotter($this->db);
			//$all = $Spotter->countOverallPilots($filters,$year,$month);
			$all = $Spotter->countOverallPilots($filters);
		}
		return $all;
	}

	public function getLast7DaysAirports($airport_icao = '', $stats_airline = '',$filter_name = '') {
		if ($filter_name == '') $filter_name = $this->filter_name;
		if (strpos($stats_airline,'alliance_') !== FALSE) {
			$Spotter = new Spotter($this->db);
			$airlines = $Spotter->getAllAirlineNamesByAlliance(str_replace('_',' ',str_replace('alliance_','',$stats_airline)));
			$alliance_airlines = array();
			foreach ($airlines as $airline) {
				$alliance_airlines = array_merge($alliance_airlines,array($airline['airline_icao']));
			}
			$query = "SELECT * FROM stats_airport WHERE stats_type = 'daily' AND airport_icao = :airport_icao AND stats_airline IN ('".implode("','",$alliance_airlines)."') AND filter_name = :filter_name ORDER BY date";
			$query_values = array(':airport_icao' => $airport_icao,':filter_name' => $filter_name);
		} else {
			$query = "SELECT * FROM stats_airport WHERE stats_type = 'daily' AND airport_icao = :airport_icao AND stats_airline = :stats_airline AND filter_name = :filter_name ORDER BY date";
			$query_values = array(':airport_icao' => $airport_icao,':stats_airline' => $stats_airline, ':filter_name' => $filter_name);
		}
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			echo "error : ".$e->getMessage();
		}
		$all = $sth->fetchAll(PDO::FETCH_ASSOC);
		return $all;
	}
	public function getStats($type,$stats_airline = '', $filter_name = '') {
		if ($filter_name == '') $filter_name = $this->filter_name;
		$query = "SELECT * FROM stats WHERE stats_type = :type AND stats_airline = :stats_airline AND filter_name = :filter_name ORDER BY stats_date";
		$query_values = array(':type' => $type,':stats_airline' => $stats_airline,':filter_name' => $filter_name);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			echo "error : ".$e->getMessage();
		}
		$all = $sth->fetchAll(PDO::FETCH_ASSOC);
		return $all;
	}
	public function deleteStatsByType($type,$stats_airline = '', $filter_name = '') {
		if ($filter_name == '') $filter_name = $this->filter_name;
		$query = "DELETE FROM stats WHERE stats_type = :type AND stats_airline = :stats_airline AND filter_name = :filter_name";
		$query_values = array(':type' => $type,':stats_airline' => $stats_airline,':filter_name' => $filter_name);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			echo "error : ".$e->getMessage();
		}
	}
	public function getSumStats($type,$year,$stats_airline = '',$filter_name = '',$month = '') {
		if ($filter_name == '') $filter_name = $this->filter_name;
		global $globalArchiveMonths, $globalDBdriver;
		if (strpos($stats_airline,'alliance_') !== FALSE) {
			$Spotter = new Spotter($this->db);
			$airlines = $Spotter->getAllAirlineNamesByAlliance(str_replace('_',' ',str_replace('alliance_','',$stats_airline)));
			$alliance_airlines = array();
			foreach ($airlines as $airline) {
				$alliance_airlines = array_merge($alliance_airlines,array($airline['airline_icao']));
			}
			if ($globalDBdriver == 'mysql') {
				if ($month == '') {
					$query = "SELECT SUM(cnt) as total FROM stats WHERE stats_type = :type AND YEAR(stats_date) = :year AND stats_airline IN ('".implode("','",$alliance_airlines)."') AND filter_name = :filter_name";
					$query_values = array(':type' => $type, ':year' => $year, ':filter_name' => $filter_name);
				} else {
					$query = "SELECT SUM(cnt) as total FROM stats WHERE stats_type = :type AND YEAR(stats_date) = :year AND MONTH(stats_date) = :month AND stats_airline IN ('".implode("','",$alliance_airlines)."') AND filter_name = :filter_name";
					$query_values = array(':type' => $type, ':year' => $year, ':filter_name' => $filter_name,':month' => $month);
				}
			} else {
				if ($month == '') {
					$query = "SELECT SUM(cnt) as total FROM stats WHERE stats_type = :type AND EXTRACT(YEAR FROM stats_date) = :year AND stats_airline IN ('".implode("','",$alliance_airlines)."') AND filter_name = :filter_name";
					$query_values = array(':type' => $type, ':year' => $year, ':filter_name' => $filter_name);
				} else {
					$query = "SELECT SUM(cnt) as total FROM stats WHERE stats_type = :type AND EXTRACT(YEAR FROM stats_date) = :year AND EXTRACT(MONTH FROM stats_date) = :month AND stats_airline IN ('".implode("','",$alliance_airlines)."') AND filter_name = :filter_name";
					$query_values = array(':type' => $type, ':year' => $year, ':filter_name' => $filter_name,':month' => $month);
				}
			}
		} else {
			if ($globalDBdriver == 'mysql') {
				if ($month == '') {
					$query = "SELECT SUM(cnt) as total FROM stats WHERE stats_type = :type AND YEAR(stats_date) = :year AND stats_airline = :stats_airline AND filter_name = :filter_name";
					$query_values = array(':type' => $type, ':year' => $year, ':stats_airline' => $stats_airline,':filter_name' => $filter_name);
				} else {
					$query = "SELECT SUM(cnt) as total FROM stats WHERE stats_type = :type AND YEAR(stats_date) = :year AND MONTH(stats_date) = :month AND stats_airline = :stats_airline AND filter_name = :filter_name";
					$query_values = array(':type' => $type, ':year' => $year, ':stats_airline' => $stats_airline,':filter_name' => $filter_name,':month' => $month);
				}
			} else {
				if ($month == '') {
					$query = "SELECT SUM(cnt) as total FROM stats WHERE stats_type = :type AND EXTRACT(YEAR FROM stats_date) = :year AND stats_airline = :stats_airline AND filter_name = :filter_name";
					$query_values = array(':type' => $type, ':year' => $year, ':stats_airline' => $stats_airline,':filter_name' => $filter_name);
				} else {
					$query = "SELECT SUM(cnt) as total FROM stats WHERE stats_type = :type AND EXTRACT(YEAR FROM stats_date) = :year AND EXTRACT(MONTH FROM stats_date) = :month AND stats_airline = :stats_airline AND filter_name = :filter_name";
					$query_values = array(':type' => $type, ':year' => $year, ':stats_airline' => $stats_airline,':filter_name' => $filter_name,':month' => $month);
				}
			}
		}
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			echo "error : ".$e->getMessage();
		}
		$all = $sth->fetchAll(PDO::FETCH_ASSOC);
		return $all[0]['total'];
	}
	public function getStatsTotal($type, $stats_airline = '', $filter_name = '') {
		global $globalArchiveMonths, $globalDBdriver;
		if ($filter_name == '') $filter_name = $this->filter_name;
		if (strpos($stats_airline,'alliance_') !== FALSE) {
			$Spotter = new Spotter($this->db);
			$airlines = $Spotter->getAllAirlineNamesByAlliance(str_replace('_',' ',str_replace('alliance_','',$stats_airline)));
			$alliance_airlines = array();
			foreach ($airlines as $airline) {
				$alliance_airlines = array_merge($alliance_airlines,array($airline['airline_icao']));
			}
			if ($globalDBdriver == 'mysql') {
				$query = "SELECT SUM(cnt) as total FROM stats WHERE stats_type = :type AND stats_date < DATE_SUB(UTC_TIMESTAMP(), INTERVAL ".$globalArchiveMonths." MONTH) AND stats_airline IN ('".implode("','",$alliance_airlines)."') AND filter_name = :filter_name";
			} else {
				$query = "SELECT SUM(cnt) as total FROM stats WHERE stats_type = :type AND stats_date < CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$globalArchiveMonths." MONTHS' AND stats_airline IN ('".implode("','",$alliance_airlines)."') AND filter_name = :filter_name";
			}
			$query_values = array(':type' => $type, ':filter_name' => $filter_name);
		} else {
			if ($globalDBdriver == 'mysql') {
				$query = "SELECT SUM(cnt) as total FROM stats WHERE stats_type = :type AND stats_date < DATE_SUB(UTC_TIMESTAMP(), INTERVAL ".$globalArchiveMonths." MONTH) AND stats_airline = :stats_airline AND filter_name = :filter_name";
			} else {
				$query = "SELECT SUM(cnt) as total FROM stats WHERE stats_type = :type AND stats_date < CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$globalArchiveMonths." MONTHS' AND stats_airline = :stats_airline AND filter_name = :filter_name";
			}
			$query_values = array(':type' => $type, ':stats_airline' => $stats_airline, ':filter_name' => $filter_name);
		}
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			echo "error : ".$e->getMessage();
		}
		$all = $sth->fetchAll(PDO::FETCH_ASSOC);
		return $all[0]['total'];
	}
	public function getStatsAircraftTotal($stats_airline = '', $filter_name = '') {
		global $globalArchiveMonths, $globalDBdriver;
		if ($filter_name == '') $filter_name = $this->filter_name;
		if (strpos($stats_airline,'alliance_') !== FALSE) {
			$Spotter = new Spotter($this->db);
			$airlines = $Spotter->getAllAirlineNamesByAlliance(str_replace('_',' ',str_replace('alliance_','',$stats_airline)));
			$alliance_airlines = array();
			foreach ($airlines as $airline) {
				$alliance_airlines = array_merge($alliance_airlines,array($airline['airline_icao']));
			}
			if ($globalDBdriver == 'mysql') {
				$query = "SELECT SUM(cnt) as total FROM stats_aircraft WHERE stats_airline IN ('".implode("','",$alliance_airlines)."') AND filter_name = :filter_name";
			} else {
				$query = "SELECT SUM(cnt) as total FROM stats_aircraft WHERE stats_airline IN ('".implode("','",$alliance_airlines)."') AND filter_name = :filter_name";
			}
		} else {
			if ($globalDBdriver == 'mysql') {
				$query = "SELECT SUM(cnt) as total FROM stats_aircraft WHERE stats_airline = :stats_airline AND filter_name = :filter_name";
			} else {
				$query = "SELECT SUM(cnt) as total FROM stats_aircraft WHERE stats_airline = :stats_airline AND filter_name = :filter_name";
			}
		}
		try {
			$sth = $this->db->prepare($query);
			$sth->execute(array(':stats_airline' => $stats_airline, ':filter_name' => $filter_name));
		} catch(PDOException $e) {
			echo "error : ".$e->getMessage();
		}
		$all = $sth->fetchAll(PDO::FETCH_ASSOC);
		return $all[0]['total'];
	}
	public function getStatsAirlineTotal($filter_name = '') {
		global $globalArchiveMonths, $globalDBdriver;
		if ($filter_name == '') $filter_name = $this->filter_name;
		if ($globalDBdriver == 'mysql') {
			$query = "SELECT SUM(cnt) as total FROM stats_airline WHERE filter_name = :filter_name";
		} else {
			$query = "SELECT SUM(cnt) as total FROM stats_airline WHERE filter_name = :filter_name";
		}
		try {
			$sth = $this->db->prepare($query);
			$sth->execute(array(':filter_name' => $filter_name));
		} catch(PDOException $e) {
			echo "error : ".$e->getMessage();
		}
		$all = $sth->fetchAll(PDO::FETCH_ASSOC);
		return $all[0]['total'];
	}
	public function getStatsOwnerTotal($filter_name = '') {
		global $globalArchiveMonths, $globalDBdriver;
		if ($filter_name == '') $filter_name = $this->filter_name;
		if ($globalDBdriver == 'mysql') {
			$query = "SELECT SUM(cnt) as total FROM stats_owner WHERE filter_name = :filter_name";
		} else {
			$query = "SELECT SUM(cnt) as total FROM stats_owner WHERE filter_name = :filter_name";
		}
		try {
			$sth = $this->db->prepare($query);
			$sth->execute(array(':filter_name' => $filter_name));
		} catch(PDOException $e) {
			echo "error : ".$e->getMessage();
		}
		$all = $sth->fetchAll(PDO::FETCH_ASSOC);
		return $all[0]['total'];
	}
	public function getStatsOwner($owner_name,$filter_name = '') {
		global $globalArchiveMonths, $globalDBdriver;
		if ($filter_name == '') $filter_name = $this->filter_name;
		$query = "SELECT cnt FROM stats_owner WHERE filter_name = :filter_name AND owner_name = :owner_name";
		try {
			$sth = $this->db->prepare($query);
			$sth->execute(array(':filter_name' => $filter_name,':owner_name' => $owner_name));
		} catch(PDOException $e) {
			echo "error : ".$e->getMessage();
		}
		$all = $sth->fetchAll(PDO::FETCH_ASSOC);
		if (isset($all[0]['cnt'])) return $all[0]['cnt'];
		else return 0;
	}
	public function getStatsPilotTotal($filter_name = '') {
		global $globalArchiveMonths, $globalDBdriver;
		if ($filter_name == '') $filter_name = $this->filter_name;
		if ($globalDBdriver == 'mysql') {
			$query = "SELECT SUM(cnt) as total FROM stats_pilot WHERE filter_name = :filter_name";
		} else {
			$query = "SELECT SUM(cnt) as total FROM stats_pilot WHERE filter_name = :filter_name";
		}
		try {
			$sth = $this->db->prepare($query);
			$sth->execute(array(':filter_name' => $filter_name));
		} catch(PDOException $e) {
			echo "error : ".$e->getMessage();
		}
		$all = $sth->fetchAll(PDO::FETCH_ASSOC);
		return $all[0]['total'];
	}
	public function getStatsPilot($pilot,$filter_name = '') {
		global $globalArchiveMonths, $globalDBdriver;
		if ($filter_name == '') $filter_name = $this->filter_name;
		$query = "SELECT cnt FROM stats_pilot WHERE filter_name = :filter_name AND (pilot_name = :pilot OR pilot_id = :pilot)";
		try {
			$sth = $this->db->prepare($query);
			$sth->execute(array(':filter_name' => $filter_name,':pilot' => $pilot));
		} catch(PDOException $e) {
			echo "error : ".$e->getMessage();
		}
		$all = $sth->fetchAll(PDO::FETCH_ASSOC);
		if (isset($all[0]['cnt'])) return $all[0]['cnt'];
		else return 0;
	}

	public function addStat($type,$cnt,$stats_date,$stats_airline = '',$filter_name = '') {
		global $globalDBdriver;
		if ($filter_name == '') $filter_name = $this->filter_name;
		if ($globalDBdriver == 'mysql') {
			$query = "INSERT INTO stats (stats_type,cnt,stats_date,stats_airline,filter_name) VALUES (:type,:cnt,:stats_date,:stats_airline,:filter_name) ON DUPLICATE KEY UPDATE cnt = :cnt";
		} else {
			$query = "UPDATE stats SET cnt = :cnt WHERE stats_type = :type AND stats_date = :stats_date AND stats_airline = :stats_airline AND filter_name = :filter_name; INSERT INTO stats (stats_type,cnt,stats_date,stats_airline,filter_name) SELECT :type,:cnt,:stats_date,:stats_airline,:filter_name WHERE NOT EXISTS (SELECT 1 FROM stats WHERE  stats_type = :type AND stats_date = :stats_date AND stats_airline = :stats_airline AND filter_name = :filter_name);"; 
		}
		$query_values = array(':type' => $type,':cnt' => $cnt,':stats_date' => $stats_date, ':stats_airline' => $stats_airline,':filter_name' => $filter_name);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
	}
	public function updateStat($type,$cnt,$stats_date,$stats_airline = '',$filter_name = '') {
		global $globalDBdriver;
		if ($filter_name == '') $filter_name = $this->filter_name;
		if ($globalDBdriver == 'mysql') {
			$query = "INSERT INTO stats (stats_type,cnt,stats_date,stats_airline,filter_name) VALUES (:type,:cnt,:stats_date,:stats_airline,:filter_name) ON DUPLICATE KEY UPDATE cnt = cnt+:cnt, stats_date = :date";
		} else {
			//$query = "INSERT INTO stats (stats_type,cnt,stats_date) VALUES (:type,:cnt,:stats_date) ON DUPLICATE KEY UPDATE cnt = cnt+:cnt, stats_date = :date";
			$query = "UPDATE stats SET cnt = cnt+:cnt WHERE stats_type = :type AND stats_date = :stats_date AND stats_airline = :stats_airline AND filter_name = :filter_name; INSERT INTO stats (stats_type,cnt,stats_date,stats_airline,filter_name) SELECT :type,:cnt,:stats_date,:stats_airline,:filter_name WHERE NOT EXISTS (SELECT 1 FROM stats WHERE  stats_type = :type AND stats_date = :stats_date AND stats_airline = :stats_airline AND filter_name = :filter_name);"; 
		}
		$query_values = array(':type' => $type,':cnt' => $cnt,':stats_date' => $stats_date,':stats_airline' => $stats_airline,':filter_name' => $filter_name);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
	}
        /*
	public function getStatsSource($date,$stats_type = '') {
		if ($stats_type == '') {
			$query = "SELECT * FROM stats_source WHERE stats_date = :date ORDER BY source_name";
			$query_values = array(':date' => $date);
		} else {
			$query = "SELECT * FROM stats_source WHERE stats_date = :date AND stats_type = :stats_type ORDER BY source_name";
			$query_values = array(':date' => $date,':stats_type' => $stats_type);
		}
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute($query_values);
                } catch(PDOException $e) {
                        echo "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                return $all;
        }
        */

	public function getStatsSource($stats_type,$year = '',$month = '',$day = '') {
		global $globalDBdriver;
		$query = "SELECT * FROM stats_source WHERE stats_type = :stats_type";
		$query_values = array();
		if ($globalDBdriver == 'mysql') {
			if ($year != '') {
				$query .= ' AND YEAR(stats_date) = :year';
				$query_values = array_merge($query_values,array(':year' => $year));
			}
			if ($month != '') {
				$query .= ' AND MONTH(stats_date) = :month';
				$query_values = array_merge($query_values,array(':month' => $month));
			}
			if ($day != '') {
				$query .= ' AND DAY(stats_date) = :day';
				$query_values = array_merge($query_values,array(':day' => $day));
			}
		} else {
			if ($year != '') {
				$query .= ' AND EXTRACT(YEAR FROM stats_date) = :year';
				$query_values = array_merge($query_values,array(':year' => $year));
			}
			if ($month != '') {
				$query .= ' AND EXTRACT(MONTH FROM stats_date) = :month';
				$query_values = array_merge($query_values,array(':month' => $month));
			}
			if ($day != '') {
				$query .= ' AND EXTRACT(DAY FROM stats_date) = :day';
				$query_values = array_merge($query_values,array(':day' => $day));
			}
		}
		$query .= " ORDER BY source_name";
		$query_values = array_merge($query_values,array(':stats_type' => $stats_type));
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			echo "error : ".$e->getMessage();
		}
		$all = $sth->fetchAll(PDO::FETCH_ASSOC);
		return $all;
	}

	public function addStatSource($data,$source_name,$stats_type,$date) {
		global $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			$query = "INSERT INTO stats_source (source_data,source_name,stats_type,stats_date) VALUES (:data,:source_name,:stats_type,:stats_date) ON DUPLICATE KEY UPDATE source_data = :data";
		} else {
			$query = "UPDATE stats_source SET source_data = :data WHERE stats_date = :stats_date AND source_name = :source_name AND stats_type = :stats_type; INSERT INTO stats_source (source_data,source_name,stats_type,stats_date) SELECT :data,:source_name,:stats_type,:stats_date WHERE NOT EXISTS (SELECT 1 FROM stats_source WHERE stats_date = :stats_date AND source_name = :source_name AND stats_type = :stats_type);"; 
		}
		$query_values = array(':data' => $data,':stats_date' => $date,':source_name' => $source_name,':stats_type' => $stats_type);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
	}
	public function addStatFlight($type,$date_name,$cnt,$stats_airline = '',$filter_name = '') {
		$query = "INSERT INTO stats_flight (stats_type,flight_date,cnt,stats_airline,filter_name) VALUES (:type,:flight_date,:cnt,:stats_airline,:filter_name)";
		$query_values = array(':type' => $type,':flight_date' => $date_name,':cnt' => $cnt, ':stats_airline' => $stats_airline,':filter_name' => $filter_name);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
	}
	public function addStatMarine($type,$date_name,$cnt,$filter_name = '') {
		$query = "INSERT INTO stats_marine (stats_type,marine_date,cnt,filter_name) VALUES (:type,:flight_date,:cnt,:filter_name)";
		$query_values = array(':type' => $type,':flight_date' => $date_name,':cnt' => $cnt,':filter_name' => $filter_name);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
	}
	public function addStatTracker($type,$date_name,$cnt,$filter_name = '') {
		$query = "INSERT INTO stats_tracker (stats_type,tracker_date,cnt,filter_name) VALUES (:type,:flight_date,:cnt,:filter_name)";
		$query_values = array(':type' => $type,':flight_date' => $date_name,':cnt' => $cnt,':filter_name' => $filter_name);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
	}
	public function addStatAircraftRegistration($registration,$cnt,$aircraft_icao = '',$airline_icao = '',$filter_name = '',$reset = false) {
		global $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			if ($reset) {
				$query = "INSERT INTO stats_registration (aircraft_icao,registration,cnt,stats_airline,filter_name) VALUES (:aircraft_icao,:registration,:cnt,:stats_airline,:filter_name) ON DUPLICATE KEY UPDATE cnt = :cnt";
			} else {
				$query = "INSERT INTO stats_registration (aircraft_icao,registration,cnt,stats_airline,filter_name) VALUES (:aircraft_icao,:registration,:cnt,:stats_airline,:filter_name) ON DUPLICATE KEY UPDATE cnt = cnt+:cnt";
			}
		} else {
			if ($reset) {
				$query = "UPDATE stats_registration SET cnt = :cnt WHERE registration = :registration AND stats_airline = :stats_airline AND filter_name = :filter_name; INSERT INTO stats_registration (aircraft_icao,registration,cnt,stats_airline,filter_name) SELECT :aircraft_icao,:registration,:cnt,:stats_airline,:filter_name WHERE NOT EXISTS (SELECT 1 FROM stats_registration WHERE registration = :registration AND stats_airline = :stats_airline AND filter_name = :filter_name);"; 
			} else {
				$query = "UPDATE stats_registration SET cnt = cnt+:cnt WHERE registration = :registration AND stats_airline = :stats_airline AND filter_name = :filter_name; INSERT INTO stats_registration (aircraft_icao,registration,cnt,stats_airline,filter_name) SELECT :aircraft_icao,:registration,:cnt,:stats_airline,:filter_name WHERE NOT EXISTS (SELECT 1 FROM stats_registration WHERE registration = :registration AND stats_airline = :stats_airline AND filter_name = :filter_name);"; 
			}
		}
		$query_values = array(':aircraft_icao' => $aircraft_icao,':registration' => $registration,':cnt' => $cnt,':stats_airline' => $airline_icao, ':filter_name' => $filter_name);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
	}
	public function addStatCallsign($callsign_icao,$cnt,$airline_icao = '', $filter_name = '', $reset = false) {
		global $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			if ($reset) {
				$query = "INSERT INTO stats_callsign (callsign_icao,airline_icao,cnt,filter_name) VALUES (:callsign_icao,:airline_icao,:cnt,:filter_name) ON DUPLICATE KEY UPDATE cnt = :cnt";
			} else {
				$query = "INSERT INTO stats_callsign (callsign_icao,airline_icao,cnt,filter_name) VALUES (:callsign_icao,:airline_icao,:cnt,:filter_name) ON DUPLICATE KEY UPDATE cnt = cnt+:cnt";
			}
		} else {
			if ($reset) {
				$query = "UPDATE stats_callsign SET cnt = :cnt WHERE callsign_icao = :callsign_icao AND filter_name = :filter_name; INSERT INTO stats_callsign (callsign_icao,airline_icao,cnt,filter_name) SELECT :callsign_icao,:airline_icao,:cnt,:filter_name WHERE NOT EXISTS (SELECT 1 FROM stats_callsign WHERE callsign_icao = :callsign_icao AND filter_name = :filter_name);"; 
			} else {
				$query = "UPDATE stats_callsign SET cnt = cnt+:cnt WHERE callsign_icao = :callsign_icao AND filter_name = :filter_name; INSERT INTO stats_callsign (callsign_icao,airline_icao,cnt,filter_name) SELECT :callsign_icao,:airline_icao,:cnt,:filter_name WHERE NOT EXISTS (SELECT 1 FROM stats_callsign WHERE callsign_icao = :callsign_icao AND filter_name = :filter_name);"; 
			}
		}
		$query_values = array(':callsign_icao' => $callsign_icao,':airline_icao' => $airline_icao,':cnt' => $cnt, ':filter_name' => $filter_name);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
	}
	public function addStatCountry($iso2,$iso3,$name,$cnt,$airline_icao = '',$filter_name = '',$reset = false) {
		global $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			if ($reset) {
				$query = "INSERT INTO stats_country (iso2,iso3,name,cnt,stats_airline,filter_name) VALUES (:iso2,:iso3,:name,:cnt,:airline,:filter_name) ON DUPLICATE KEY UPDATE cnt = :cnt";
			} else {
				$query = "INSERT INTO stats_country (iso2,iso3,name,cnt,stats_airline,filter_name) VALUES (:iso2,:iso3,:name,:cnt,:airline,:filter_name) ON DUPLICATE KEY UPDATE cnt = cnt+:cnt";
			}
		} else {
			if ($reset) {
				$query = "UPDATE stats_country SET cnt = :cnt WHERE iso2 = :iso2 AND filter_name = :filter_name AND stats_airline = :airline; INSERT INTO stats_country (iso2,iso3,name,cnt,stats_airline,filter_name) SELECT :iso2,:iso3,:name,:cnt,:airline,:filter_name WHERE NOT EXISTS (SELECT 1 FROM stats_country WHERE iso2 = :iso2 AND filter_name = :filter_name AND stats_airline = :airline);"; 
			} else {
				$query = "UPDATE stats_country SET cnt = cnt+:cnt WHERE iso2 = :iso2 AND filter_name = :filter_name AND stats_airline = :airline; INSERT INTO stats_country (iso2,iso3,name,cnt,stats_airline,filter_name) SELECT :iso2,:iso3,:name,:cnt,:airline,:filter_name WHERE NOT EXISTS (SELECT 1 FROM stats_country WHERE iso2 = :iso2 AND filter_name = :filter_name AND stats_airline = :airline);"; 
			}
		}
		$query_values = array(':iso2' => $iso2,':iso3' => $iso3,':name' => $name,':cnt' => $cnt,':filter_name' => $filter_name,':airline' => $airline_icao);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
	}
	public function addStatCountryMarine($iso2,$iso3,$name,$cnt,$filter_name = '',$reset = false) {
		global $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			if ($reset) {
				$query = "INSERT INTO stats_marine_country (iso2,iso3,name,cnt,filter_name) VALUES (:iso2,:iso3,:name,:cnt,:filter_name) ON DUPLICATE KEY UPDATE cnt = :cnt";
			} else {
				$query = "INSERT INTO stats_marine_country (iso2,iso3,name,cnt,filter_name) VALUES (:iso2,:iso3,:name,:cnt,:filter_name) ON DUPLICATE KEY UPDATE cnt = cnt+:cnt";
			}
		} else {
			if ($reset) {
				$query = "UPDATE stats_marine_country SET cnt = :cnt WHERE iso2 = :iso2 AND filter_name = :filter_name; INSERT INTO stats_marine_country (iso2,iso3,name,cnt,filter_name) SELECT :iso2,:iso3,:name,:cnt,:filter_name WHERE NOT EXISTS (SELECT 1 FROM stats_marine_country WHERE iso2 = :iso2 AND filter_name = :filter_name);"; 
			} else {
				$query = "UPDATE stats_marine_country SET cnt = cnt+:cnt WHERE iso2 = :iso2 AND filter_name = :filter_name; INSERT INTO stats_marine_country (iso2,iso3,name,cnt,filter_name) SELECT :iso2,:iso3,:name,:cnt,:filter_name WHERE NOT EXISTS (SELECT 1 FROM stats_marine_country WHERE iso2 = :iso2 AND filter_name = :filter_name);"; 
			}
		}
		$query_values = array(':iso2' => $iso2,':iso3' => $iso3,':name' => $name,':cnt' => $cnt,':filter_name' => $filter_name);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
	}
	public function addStatCountryTracker($iso2,$iso3,$name,$cnt,$filter_name = '',$reset = false) {
		global $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			if ($reset) {
				$query = "INSERT INTO stats_tracker_country (iso2,iso3,name,cnt,filter_name) VALUES (:iso2,:iso3,:name,:cnt,:filter_name) ON DUPLICATE KEY UPDATE cnt = :cnt";
			} else {
				$query = "INSERT INTO stats_tracker_country (iso2,iso3,name,cnt,filter_name) VALUES (:iso2,:iso3,:name,:cnt,:filter_name) ON DUPLICATE KEY UPDATE cnt = cnt+:cnt";
			}
		} else {
			if ($reset) {
				$query = "UPDATE stats_tracker_country SET cnt = :cnt WHERE iso2 = :iso2 AND filter_name = :filter_name; INSERT INTO stats_tracker_country (iso2,iso3,name,cnt,filter_name) SELECT :iso2,:iso3,:name,:cnt,:filter_name WHERE NOT EXISTS (SELECT 1 FROM stats_tracker_country WHERE iso2 = :iso2 AND filter_name = :filter_name);"; 
			} else {
				$query = "UPDATE stats_tracker_country SET cnt = cnt+:cnt WHERE iso2 = :iso2 AND filter_name = :filter_name; INSERT INTO stats_tracker_country (iso2,iso3,name,cnt,filter_name) SELECT :iso2,:iso3,:name,:cnt,:filter_name WHERE NOT EXISTS (SELECT 1 FROM stats_tracker_country WHERE iso2 = :iso2 AND filter_name = :filter_name);"; 
			}
		}
		$query_values = array(':iso2' => $iso2,':iso3' => $iso3,':name' => $name,':cnt' => $cnt,':filter_name' => $filter_name);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
	}
	public function addStatAircraft($aircraft_icao,$cnt,$aircraft_name = '',$aircraft_manufacturer = '', $airline_icao = '', $filter_name = '', $reset = false) {
		global $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			if ($reset) {
				$query = "INSERT INTO stats_aircraft (aircraft_icao,aircraft_name,aircraft_manufacturer,cnt,stats_airline, filter_name) VALUES (:aircraft_icao,:aircraft_name,:aircraft_manufacturer,:cnt,:stats_airline,:filter_name) ON DUPLICATE KEY UPDATE cnt = :cnt, aircraft_name = :aircraft_name, aircraft_manufacturer = :aircraft_manufacturer, stats_airline = :stats_airline";
			} else {
				$query = "INSERT INTO stats_aircraft (aircraft_icao,aircraft_name,aircraft_manufacturer,cnt,stats_airline, filter_name) VALUES (:aircraft_icao,:aircraft_name,:aircraft_manufacturer,:cnt,:stats_airline,:filter_name) ON DUPLICATE KEY UPDATE cnt = cnt+:cnt, aircraft_name = :aircraft_name, aircraft_manufacturer = :aircraft_manufacturer, stats_airline = :stats_airline";
			}
		} else {
			if ($reset) {
				$query = "UPDATE stats_aircraft SET cnt = :cnt, aircraft_name = :aircraft_name, aircraft_manufacturer = :aircraft_manufacturer, filter_name = :filter_name WHERE aircraft_icao = :aircraft_icao AND stats_airline = :stats_airline AND filter_name = :filter_name; INSERT INTO stats_aircraft (aircraft_icao,aircraft_name,aircraft_manufacturer,cnt,stats_airline,filter_name) SELECT :aircraft_icao,:aircraft_name,:aircraft_manufacturer,:cnt,:stats_airline,:filter_name WHERE NOT EXISTS (SELECT 1 FROM stats_aircraft WHERE aircraft_icao = :aircraft_icao AND stats_airline = :stats_airline AND filter_name = :filter_name);"; 
			} else {
				$query = "UPDATE stats_aircraft SET cnt = cnt+:cnt, aircraft_name = :aircraft_name, aircraft_manufacturer = :aircraft_manufacturer, filter_name = :filter_name WHERE aircraft_icao = :aircraft_icao AND stats_airline = :stats_airline AND filter_name = :filter_name; INSERT INTO stats_aircraft (aircraft_icao,aircraft_name,aircraft_manufacturer,cnt,stats_airline,filter_name) SELECT :aircraft_icao,:aircraft_name,:aircraft_manufacturer,:cnt,:stats_airline,:filter_name WHERE NOT EXISTS (SELECT 1 FROM stats_aircraft WHERE aircraft_icao = :aircraft_icao AND stats_airline = :stats_airline AND filter_name = :filter_name);"; 
			}
		}
		$query_values = array(':aircraft_icao' => $aircraft_icao,':aircraft_name' => $aircraft_name,':cnt' => $cnt, ':aircraft_manufacturer' => $aircraft_manufacturer,':stats_airline' => $airline_icao, ':filter_name' => $filter_name);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
	}
	public function addStatMarineType($type,$type_id,$cnt, $filter_name = '', $reset = false) {
		global $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			if ($reset) {
				$query = "INSERT INTO stats_marine_type (type,type_id,cnt, filter_name) VALUES (:type,:type_id,:cnt,:filter_name) ON DUPLICATE KEY UPDATE cnt = :cnt";
			} else {
				$query = "INSERT INTO stats_marine_type (type,type_id,cnt, filter_name) VALUES (:type,:type_id,:cnt,:filter_name) ON DUPLICATE KEY UPDATE cnt = cnt+:cnt";
			}
		} else {
			if ($reset) {
				$query = "UPDATE stats_marine_type SET cnt = :cnt, type = :type, filter_name = :filter_name WHERE type_id = :type_id AND filter_name = :filter_name; INSERT INTO stats_marine_type (type, type_id,cnt,filter_name) SELECT :type,:type_id,:cnt,:filter_name WHERE NOT EXISTS (SELECT 1 FROM stats_marine_type WHERE type = :type AND filter_name = :filter_name);"; 
			} else {
				$query = "UPDATE stats_marine_type SET cnt = cnt+:cnt, type = :type, filter_name = :filter_name WHERE type_id = :type_id AND filter_name = :filter_name; INSERT INTO stats_marine_type (type,type_id,cnt,filter_name) SELECT :type,:type_id,:cnt,:filter_name WHERE NOT EXISTS (SELECT 1 FROM stats_marine_type WHERE type = :type AND filter_name = :filter_name);"; 
			}
		}
		$query_values = array(':type' => $type,':type_id' => $type_id,':cnt' => $cnt, ':filter_name' => $filter_name);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
	}
	public function addStatTrackerType($type,$cnt, $filter_name = '', $reset = false) {
		global $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			if ($reset) {
				$query = "INSERT INTO stats_tracker_type (type,cnt, filter_name) VALUES (:type,:type_id,:cnt,:filter_name) ON DUPLICATE KEY UPDATE cnt = :cnt";
			} else {
				$query = "INSERT INTO stats_tracker_type (type,cnt, filter_name) VALUES (:type,:type_id,:cnt,:filter_name) ON DUPLICATE KEY UPDATE cnt = cnt+:cnt";
			}
		} else {
			if ($reset) {
				$query = "UPDATE stats_tracker_type SET cnt = :cnt WHERE type = :type AND filter_name = :filter_name; INSERT INTO stats_tracker_type (type, cnt,filter_name) SELECT :type,:cnt,:filter_name WHERE NOT EXISTS (SELECT 1 FROM stats_tracker_type WHERE type = :type AND filter_name = :filter_name);"; 
			} else {
				$query = "UPDATE stats_tracker_type SET cnt = cnt+:cnt WHERE type = :type AND filter_name = :filter_name; INSERT INTO stats_tracker_type (type,cnt,filter_name) SELECT :type,:cnt,:filter_name WHERE NOT EXISTS (SELECT 1 FROM stats_tracker_type WHERE type = :type AND filter_name = :filter_name);"; 
			}
		}
		$query_values = array(':type' => $type,':cnt' => $cnt, ':filter_name' => $filter_name);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
	}
	public function addStatAirline($airline_icao,$cnt,$airline_name = '',$filter_name = '', $reset = false) {
		global $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			if ($reset) {
				$query = "INSERT INTO stats_airline (airline_icao,airline_name,cnt,filter_name) VALUES (:airline_icao,:airline_name,:cnt,:filter_name) ON DUPLICATE KEY UPDATE cnt = :cnt,airline_name = :airline_name";
			} else {
				$query = "INSERT INTO stats_airline (airline_icao,airline_name,cnt,filter_name) VALUES (:airline_icao,:airline_name,:cnt,:filter_name) ON DUPLICATE KEY UPDATE cnt = cnt+:cnt,airline_name = :airline_name";
			}
		} else {
			if ($reset) {
				$query = "UPDATE stats_airline SET cnt = :cnt WHERE airline_icao = :airline_icao AND filter_name = :filter_name; INSERT INTO stats_airline (airline_icao,airline_name,cnt,filter_name) SELECT :airline_icao,:airline_name,:cnt,:filter_name WHERE NOT EXISTS (SELECT 1 FROM stats_airline WHERE airline_icao = :airline_icao AND filter_name = :filter_name);"; 
			} else {
				$query = "UPDATE stats_airline SET cnt = cnt+:cnt WHERE airline_icao = :airline_icao AND filter_name = :filter_name; INSERT INTO stats_airline (airline_icao,airline_name,cnt,filter_name) SELECT :airline_icao,:airline_name,:cnt,:filter_name WHERE NOT EXISTS (SELECT 1 FROM stats_airline WHERE airline_icao = :airline_icao AND filter_name = :filter_name);"; 
			}
		}
		$query_values = array(':airline_icao' => $airline_icao,':airline_name' => $airline_name,':cnt' => $cnt,':filter_name' => $filter_name);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
	}
	public function addStatOwner($owner_name,$cnt,$stats_airline = '', $filter_name = '', $reset = false) {
		global $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			if ($reset) {
				$query = "INSERT INTO stats_owner (owner_name,cnt,stats_airline,filter_name) VALUES (:owner_name,:cnt,:stats_airline,:filter_name) ON DUPLICATE KEY UPDATE cnt = :cnt";
			} else {
				$query = "INSERT INTO stats_owner (owner_name,cnt,stats_airline,filter_name) VALUES (:owner_name,:cnt,:stats_airline,:filter_name) ON DUPLICATE KEY UPDATE cnt = cnt+:cnt";
			}
		} else {
			if ($reset) {
				$query = "UPDATE stats_owner SET cnt = :cnt WHERE owner_name = :owner_name AND stats_airline = :stats_airline AND filter_name = :filter_name; INSERT INTO stats_owner (owner_name,cnt,stats_airline,filter_name) SELECT :owner_name,:cnt,:stats_airline,:filter_name WHERE NOT EXISTS (SELECT 1 FROM stats_owner WHERE owner_name = :owner_name AND stats_airline = :stats_airline AND filter_name = :filter_name);"; 
			} else {
				$query = "UPDATE stats_owner SET cnt = cnt+:cnt WHERE owner_name = :owner_name AND stats_airline = :stats_airline AND filter_name = :filter_name; INSERT INTO stats_owner (owner_name,cnt,stats_airline,filter_name) SELECT :owner_name,:cnt,:stats_airline,:filter_name WHERE NOT EXISTS (SELECT 1 FROM stats_owner WHERE owner_name = :owner_name AND stats_airline = :stats_airline AND filter_name = :filter_name);"; 
			}
		}
		$query_values = array(':owner_name' => $owner_name,':cnt' => $cnt,':stats_airline' => $stats_airline,':filter_name' => $filter_name);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
	}
	public function addStatPilot($pilot_id,$cnt,$pilot_name,$stats_airline = '',$filter_name = '',$format_source = '',$reset = false) {
		global $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			if ($reset) {
				$query = "INSERT INTO stats_pilot (pilot_id,cnt,pilot_name,stats_airline,filter_name,format_source) VALUES (:pilot_id,:cnt,:pilot_name,:stats_airline,:filter_name,:format_source) ON DUPLICATE KEY UPDATE cnt = :cnt, pilot_name = :pilot_name";
			} else {
				$query = "INSERT INTO stats_pilot (pilot_id,cnt,pilot_name,stats_airline,filter_name,format_source) VALUES (:pilot_id,:cnt,:pilot_name,:stats_airline,:filter_name,:format_source) ON DUPLICATE KEY UPDATE cnt = cnt+:cnt, pilot_name = :pilot_name";
			}
		} else {
			if ($reset) {
				$query = "UPDATE stats_pilot SET cnt = :cnt, pilot_name = :pilot_name WHERE pilot_id = :pilot_id AND stats_airline = :stats_airline AND filter_name = :filter_name AND format_source = :format_source; INSERT INTO stats_pilot (pilot_id,cnt,pilot_name,stats_airline,filter_name,format_source) SELECT :pilot_id,:cnt,:pilot_name,:stats_airline,:filter_name,:format_source WHERE NOT EXISTS (SELECT 1 FROM stats_pilot WHERE pilot_id = :pilot_id AND stats_airline = :stats_airline AND filter_name = :filter_name AND format_source = :format_source);"; 
			} else {
				$query = "UPDATE stats_pilot SET cnt = cnt+:cnt, pilot_name = :pilot_name WHERE pilot_id = :pilot_id AND stats_airline = :stats_airline AND filter_name = :filter_name AND format_source = :format_source; INSERT INTO stats_pilot (pilot_id,cnt,pilot_name,stats_airline,filter_name,format_source) SELECT :pilot_id,:cnt,:pilot_name,:stats_airline,:filter_name,:format_source WHERE NOT EXISTS (SELECT 1 FROM stats_pilot WHERE pilot_id = :pilot_id AND stats_airline = :stats_airline AND filter_name = :filter_name AND format_source = :format_source);"; 
			}
		}
		$query_values = array(':pilot_id' => $pilot_id,':cnt' => $cnt,':pilot_name' => $pilot_name,':stats_airline' => $stats_airline,':filter_name' => $filter_name,':format_source' => $format_source);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
	}
	public function addStatDepartureAirports($airport_icao,$airport_name,$airport_city,$airport_country,$departure,$airline_icao = '',$filter_name = '',$reset = false) {
		global $globalDBdriver;
		if ($airport_icao != '') {
			if ($globalDBdriver == 'mysql') {
				if ($reset) {
					$query = "INSERT INTO stats_airport (airport_icao,airport_name,airport_city,airport_country,departure,stats_type,date,stats_airline,filter_name) VALUES (:airport_icao,:airport_name,:airport_city,:airport_country,:departure,'yearly',:date,:stats_airline,:filter_name) ON DUPLICATE KEY UPDATE departure = :departure";
				} else {
					$query = "INSERT INTO stats_airport (airport_icao,airport_name,airport_city,airport_country,departure,stats_type,date,stats_airline,filter_name) VALUES (:airport_icao,:airport_name,:airport_city,:airport_country,:departure,'yearly',:date,:stats_airline,:filter_name) ON DUPLICATE KEY UPDATE departure = departure+:departure";
				}
			} else {
				if ($reset) {
					$query = "UPDATE stats_airport SET departure = :departure WHERE airport_icao = :airport_icao AND stats_type = 'yearly' AND stats_airline = :stats_airline AND date = :date AND filter_name = :filter_name; INSERT INTO stats_airport (airport_icao,airport_name,airport_city,airport_country,departure,stats_type,date,stats_airline,filter_name) SELECT :airport_icao,:airport_name,:airport_city,:airport_country,:departure,'yearly',:date,:stats_airline,:filter_name WHERE NOT EXISTS (SELECT 1 FROM stats_airport WHERE airport_icao = :airport_icao AND stats_type = 'yearly' AND stats_airline = :stats_airline AND date = :date AND filter_name = :filter_name);"; 
				} else {
					$query = "UPDATE stats_airport SET departure = departure+:departure WHERE airport_icao = :airport_icao AND stats_type = 'yearly' AND stats_airline = :stats_airline AND date = :date AND filter_name = :filter_name; INSERT INTO stats_airport (airport_icao,airport_name,airport_city,airport_country,departure,stats_type,date,stats_airline,filter_name) SELECT :airport_icao,:airport_name,:airport_city,:airport_country,:departure,'yearly',:date,:stats_airline,:filter_name WHERE NOT EXISTS (SELECT 1 FROM stats_airport WHERE airport_icao = :airport_icao AND stats_type = 'yearly' AND stats_airline = :stats_airline AND date = :date AND filter_name = :filter_name);"; 
				}
			}
			$query_values = array(':airport_icao' => $airport_icao,':airport_name' => $airport_name,':airport_city' => $airport_city,':airport_country' => $airport_country,':departure' => $departure,':date' => date('Y').'-01-01 00:00:00', ':stats_airline' => $airline_icao,':filter_name' => $filter_name);
			try {
				$sth = $this->db->prepare($query);
				$sth->execute($query_values);
			} catch(PDOException $e) {
				return "error : ".$e->getMessage();
			}
		}
	}
	public function addStatDepartureAirportsDaily($date,$airport_icao,$airport_name,$airport_city,$airport_country,$departure,$airline_icao = '',$filter_name = '') {
		global $globalDBdriver;
		if ($airport_icao != '') {
			if ($globalDBdriver == 'mysql') {
				$query = "INSERT INTO stats_airport (airport_icao,airport_name,airport_city,airport_country,departure,stats_type,date,stats_airline,filter_name) VALUES (:airport_icao,:airport_name,:airport_city,:airport_country,:departure,'daily',:date,:stats_airline,:filter_name) ON DUPLICATE KEY UPDATE departure = :departure";
			} else {
				$query = "UPDATE stats_airport SET departure = :departure WHERE airport_icao = :airport_icao AND stats_type = 'daily' AND date = :date AND stats_airline = :stats_airline AND filter_name = :filter_name; INSERT INTO stats_airport (airport_icao,airport_name,airport_city,airport_country,departure,stats_type,date,stats_airline,filter_name) SELECT :airport_icao,:airport_name,:airport_city,:airport_country,:departure,'daily',:date,:stats_airline,:filter_name WHERE NOT EXISTS (SELECT 1 FROM stats_airport WHERE airport_icao = :airport_icao AND stats_type = 'daily' AND date = :date AND stats_airline = :stats_airline AND filter_name = :filter_name);"; 
			}
			$query_values = array(':airport_icao' => $airport_icao,':airport_name' => $airport_name,':airport_city' => $airport_city,':airport_country' => $airport_country,':departure' => $departure,':date' => $date,':stats_airline' => $airline_icao,':filter_name' => $filter_name);
			 try {
				$sth = $this->db->prepare($query);
				$sth->execute($query_values);
			} catch(PDOException $e) {
				return "error : ".$e->getMessage();
			}
		}
	}
	public function addStatArrivalAirports($airport_icao,$airport_name,$airport_city,$airport_country,$arrival,$airline_icao = '',$filter_name = '',$reset = false) {
		global $globalDBdriver;
		if ($airport_icao != '') {
			if ($globalDBdriver == 'mysql') {
				if ($reset) {
					$query = "INSERT INTO stats_airport (airport_icao,airport_name,airport_city,airport_country,arrival,stats_type,date,stats_airline,filter_name) VALUES (:airport_icao,:airport_name,:airport_city,:airport_country,:arrival,'yearly',:date,:stats_airline,:filter_name) ON DUPLICATE KEY UPDATE arrival = :arrival";
				} else {
					$query = "INSERT INTO stats_airport (airport_icao,airport_name,airport_city,airport_country,arrival,stats_type,date,stats_airline,filter_name) VALUES (:airport_icao,:airport_name,:airport_city,:airport_country,:arrival,'yearly',:date,:stats_airline,:filter_name) ON DUPLICATE KEY UPDATE arrival = arrival+:arrival";
				}
			} else {
				if ($reset) {
					$query = "UPDATE stats_airport SET arrival = :arrival WHERE airport_icao = :airport_icao AND stats_type = 'yearly' AND stats_airline = :stats_airline AND date = :date AND filter_name = :filter_name; INSERT INTO stats_airport (airport_icao,airport_name,airport_city,airport_country,arrival,stats_type,date,stats_airline,filter_name) SELECT :airport_icao,:airport_name,:airport_city,:airport_country,:arrival,'yearly',:date,:stats_airline,:filter_name WHERE NOT EXISTS (SELECT 1 FROM stats_airport WHERE airport_icao = :airport_icao AND stats_type = 'yearly' AND stats_airline = :stats_airline AND date = :date AND filter_name = :filter_name);"; 
				} else {
					$query = "UPDATE stats_airport SET arrival = arrival+:arrival WHERE airport_icao = :airport_icao AND stats_type = 'yearly' AND stats_airline = :stats_airline AND date = :date AND filter_name = :filter_name; INSERT INTO stats_airport (airport_icao,airport_name,airport_city,airport_country,arrival,stats_type,date,stats_airline,filter_name) SELECT :airport_icao,:airport_name,:airport_city,:airport_country,:arrival,'yearly',:date,:stats_airline,:filter_name WHERE NOT EXISTS (SELECT 1 FROM stats_airport WHERE airport_icao = :airport_icao AND stats_type = 'yearly' AND stats_airline = :stats_airline AND date = :date AND filter_name = :filter_name);"; 
				}
			}
			$query_values = array(':airport_icao' => $airport_icao,':airport_name' => $airport_name,':airport_city' => $airport_city,':airport_country' => $airport_country,':arrival' => $arrival,':date' => date('Y').'-01-01 00:00:00',':stats_airline' => $airline_icao,':filter_name' => $filter_name);
			try {
				$sth = $this->db->prepare($query);
				$sth->execute($query_values);
			} catch(PDOException $e) {
				return "error : ".$e->getMessage();
			}
		}
	}
	public function addStatArrivalAirportsDaily($date,$airport_icao,$airport_name,$airport_city,$airport_country,$arrival,$airline_icao = '',$filter_name = '') {
		global $globalDBdriver;
		if ($airport_icao != '') {
			if ($globalDBdriver == 'mysql') {
				$query = "INSERT INTO stats_airport (airport_icao,airport_name,airport_city,airport_country,arrival,stats_type,date,stats_airline,filter_name) VALUES (:airport_icao,:airport_name,:airport_city,:airport_country,:arrival,'daily',:date,:stats_airline,:filter_name) ON DUPLICATE KEY UPDATE arrival = :arrival";
			} else {
				$query = "UPDATE stats_airport SET arrival = :arrival WHERE airport_icao = :airport_icao AND stats_type = 'daily' AND date = :date AND stats_airline = :stats_airline AND filter_name = :filter_name; INSERT INTO stats_airport (airport_icao,airport_name,airport_city,airport_country,arrival,stats_type,date,stats_airline,filter_name) SELECT :airport_icao,:airport_name,:airport_city,:airport_country,:arrival,'daily',:date,:stats_airline,:filter_name WHERE NOT EXISTS (SELECT 1 FROM stats_airport WHERE airport_icao = :airport_icao AND stats_type = 'daily' AND date = :date AND stats_airline = :stats_airline AND filter_name = :filter_name);"; 
			}
			$query_values = array(':airport_icao' => $airport_icao,':airport_name' => $airport_name,':airport_city' => $airport_city,':airport_country' => $airport_country,':arrival' => $arrival, ':date' => $date,':stats_airline' => $airline_icao,':filter_name' => $filter_name);
			try {
				$sth = $this->db->prepare($query);
				$sth->execute($query_values);
			} catch(PDOException $e) {
				return "error : ".$e->getMessage();
			}
		}
	}

	public function deleteStat($id) {
		$query = "DELETE FROM stats WHERE stats_id = :id";
		$query_values = array(':id' => $id);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
	}
	public function deleteStatFlight($type) {
		$query = "DELETE FROM stats_flight WHERE stats_type = :type";
		$query_values = array(':type' => $type);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
	}
	public function deleteStatMarine($type) {
		$query = "DELETE FROM stats_marine WHERE stats_type = :type";
		$query_values = array(':type' => $type);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
	}
	public function deleteStatTracker($type) {
		$query = "DELETE FROM stats_tracker WHERE stats_type = :type";
		$query_values = array(':type' => $type);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
	}
	public function deleteStatAirport($type) {
		$query = "DELETE FROM stats_airport WHERE stats_type = :type";
		$query_values = array(':type' => $type);
		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
	}

	public function addOldStats() {
		global $globalMasterServer, $globalAircraft, $globalMarine, $globalTracker, $globalDebug, $globalArchiveMonths, $globalArchive, $globalArchiveYear, $globalDBdriver, $globalStatsFilters,$globalDeleteLastYearStats,$globalStatsReset,$globalStatsResetYear, $globalAccidents;
		$Common = new Common();
		$Connection = new Connection($this->db);
		date_default_timezone_set('UTC');
		if ((isset($globalMarine) && $globalMarine) || (isset($globalMasterServer) && $globalMasterServer)) {
			$last_update = $this->getLastStatsUpdate('last_update_stats_marine');
			if ($globalDebug) echo '!!! Update Marine stats !!!'."\n";
			if (isset($last_update[0]['value'])) {
				$last_update_day = $last_update[0]['value'];
			} else $last_update_day = '2012-12-12 12:12:12';
			$reset = false;
			$Marine = new Marine($this->db);
			$filtername = 'marine';
			if ($Connection->tableExists('countries')) {
				if ($globalDebug) echo 'Count all vessels by countries...'."\n";
				$alldata = $Marine->countAllMarineOverCountries(false,0,$last_update_day);
				foreach ($alldata as $number) {
					echo $this->addStatCountryMarine($number['marine_country_iso2'],$number['marine_country_iso3'],$number['marine_country'],$number['marine_count'],'','',$reset);
				}
			}
			if ($globalDebug) echo 'Count all vessels by months...'."\n";
			$last_month = date('Y-m-01 00:00:00', strtotime('-1 month', strtotime($last_update_day)));
			$filter_last_month = array('since_date' => $last_month);
			$alldata = $Marine->countAllMonths($filter_last_month);
			$lastyear = false;
			foreach ($alldata as $number) {
				if ($number['year_name'] != date('Y')) $lastyear = true;
				$this->addStat('marine_bymonth',$number['date_count'],date('Y-m-d H:i:s',mktime(0,0,0,$number['month_name'],1,$number['year_name'])));
			}
			echo 'Marine data...'."\n";
			$this->deleteStatMarine('month');
			echo '-> countAllDatesLastMonth...'."\n";
			$alldata = $Marine->countAllDatesLastMonth($filter_last_month);
			foreach ($alldata as $number) {
				$this->addStatMarine('month',$number['date_name'],$number['date_count']);
			}
			echo '-> countAllDates...'."\n";
			$previousdata = $this->countAllDatesMarine();
			$this->deleteStatMarine('date');
			$alldata = $Common->array_merge_noappend($previousdata,$Marine->countAllDates($filter_last_month));
			$values = array();
			foreach ($alldata as $cnt) {
				$values[] = $cnt['date_count'];
			}
			array_multisort($values,SORT_DESC,$alldata);
			array_splice($alldata,11);
			foreach ($alldata as $number) {
				$this->addStatMarine('date',$number['date_name'],$number['date_count']);
			}
			
			$this->deleteStatMarine('hour');
			echo '-> countAllHours...'."\n";
			$alldata = $Marine->countAllHours('hour',$filter_last_month);
			foreach ($alldata as $number) {
				$this->addStatMarine('hour',$number['hour_name'],$number['hour_count']);
			}
			if ($globalDebug) echo 'Count all types...'."\n";
			$alldata = $Marine->countAllMarineTypes(false,0,$last_update_day);
			foreach ($alldata as $number) {
				$this->addStatMarineType($number['marine_type'],$number['marine_type_id'],$number['marine_type_count'],'',$reset);
			}

			echo 'Insert last stats update date...'."\n";
			date_default_timezone_set('UTC');
			$this->addLastStatsUpdate('last_update_stats_marine',date('Y-m-d G:i:s'));
		}
		if ((isset($globalTracker) && $globalTracker) || (isset($globalMasterServer) && $globalMasterServer)) {
			$last_update = $this->getLastStatsUpdate('last_update_stats_tracker');
			if ($globalDebug) echo '!!! Update tracker stats !!!'."\n";
			if (isset($last_update[0]['value'])) {
				$last_update_day = $last_update[0]['value'];
			} else $last_update_day = '2012-12-12 12:12:12';
			$reset = false;
			$Tracker = new Tracker($this->db);
			if ($Connection->tableExists('countries')) {
				if ($globalDebug) echo 'Count all trackers by countries...'."\n";
				$alldata = $Tracker->countAllTrackerOverCountries(false,0,$last_update_day);
				foreach ($alldata as $number) {
					$this->addStatCountryTracker($number['tracker_country_iso2'],$number['tracker_country_iso3'],$number['tracker_country'],$number['tracker_count'],'','',$reset);
				}
			}
			if ($globalDebug) echo 'Count all vessels by months...'."\n";
			$last_month = date('Y-m-01 00:00:00', strtotime('-1 month', strtotime($last_update_day)));
			$filter_last_month = array('since_date' => $last_month);
			$alldata = $Tracker->countAllMonths($filter_last_month);
			$lastyear = false;
			foreach ($alldata as $number) {
				if ($number['year_name'] != date('Y')) $lastyear = true;
				$this->addStat('tracker_bymonth',$number['date_count'],date('Y-m-d H:i:s',mktime(0,0,0,$number['month_name'],1,$number['year_name'])));
			}
			echo 'Tracker data...'."\n";
			$this->deleteStatTracker('month');
			echo '-> countAllDatesLastMonth...'."\n";
			$alldata = $Tracker->countAllDatesLastMonth($filter_last_month);
			foreach ($alldata as $number) {
				$this->addStatTracker('month',$number['date_name'],$number['date_count']);
			}
			echo '-> countAllDates...'."\n";
			$previousdata = $this->countAllDatesTracker();
			$this->deleteStatTracker('date');
			$alldata = $Common->array_merge_noappend($previousdata,$Tracker->countAllDates($filter_last_month));
			$values = array();
			foreach ($alldata as $cnt) {
				$values[] = $cnt['date_count'];
			}
			array_multisort($values,SORT_DESC,$alldata);
			array_splice($alldata,11);
			foreach ($alldata as $number) {
				$this->addStatTracker('date',$number['date_name'],$number['date_count']);
			}
			
			$this->deleteStatTracker('hour');
			echo '-> countAllHours...'."\n";
			$alldata = $Tracker->countAllHours('hour',$filter_last_month);
			foreach ($alldata as $number) {
				$this->addStatTracker('hour',$number['hour_name'],$number['hour_count']);
			}
			if ($globalDebug) echo 'Count all types...'."\n";
			$alldata = $Tracker->countAllTrackerTypes(false,0,$last_update_day);
			foreach ($alldata as $number) {
				$this->addStatTrackerType($number['tracker_type'],$number['tracker_type_count'],'',$reset);
			}
			echo 'Insert last stats update date...'."\n";
			date_default_timezone_set('UTC');
			$this->addLastStatsUpdate('last_update_stats_tracker',date('Y-m-d G:i:s'));
		}

		if (!isset($globalAircraft) || (isset($globalAircraft) && $globalAircraft) || (isset($globalMasterServer) && $globalMasterServer)) {
			$last_update = $this->getLastStatsUpdate('last_update_stats');
			if ($globalDebug) echo '!!! Update aicraft stats !!!'."\n";
			if (isset($last_update[0]['value'])) {
				$last_update_day = $last_update[0]['value'];
			} else $last_update_day = '2012-12-12 12:12:12';
			$reset = false;
			//if ($globalStatsResetYear && date('Y',strtotime($last_update_day)) != date('Y')) {
			if ($globalStatsResetYear) {
				$reset = true;
				$last_update_day = date('Y').'-01-01 00:00:00';
			}
			$Spotter = new Spotter($this->db);

			if ($globalDebug) echo 'Count all aircraft types...'."\n";
			$alldata = $Spotter->countAllAircraftTypes(false,0,$last_update_day);
			foreach ($alldata as $number) {
				$this->addStatAircraft($number['aircraft_icao'],$number['aircraft_icao_count'],$number['aircraft_name'],$number['aircraft_manufacturer'],'','',$reset);
			}
			if ($globalDebug) echo 'Count all airlines...'."\n";
			$alldata = $Spotter->countAllAirlines(false,0,$last_update_day);
			foreach ($alldata as $number) {
				$this->addStatAirline($number['airline_icao'],$number['airline_count'],$number['airline_name'],'',$reset);
			}
			if ($globalDebug) echo 'Count all registrations...'."\n";
			$alldata = $Spotter->countAllAircraftRegistrations(false,0,$last_update_day);
			foreach ($alldata as $number) {
				$this->addStatAircraftRegistration($number['registration'],$number['aircraft_registration_count'],$number['aircraft_icao'],'','',$reset);
			}
			if ($globalDebug) echo 'Count all callsigns...'."\n";
			$alldata = $Spotter->countAllCallsigns(false,0,$last_update_day);
			foreach ($alldata as $number) {
				$this->addStatCallsign($number['callsign_icao'],$number['callsign_icao_count'],$number['airline_icao'],'',$reset);
			}
			if ($globalDebug) echo 'Count all owners...'."\n";
			$alldata = $Spotter->countAllOwners(false,0,$last_update_day);
			foreach ($alldata as $number) {
				$this->addStatOwner($number['owner_name'],$number['owner_count'],'','',$reset);
			}
			if ($globalDebug) echo 'Count all pilots...'."\n";
			$alldata = $Spotter->countAllPilots(false,0,$last_update_day);
			foreach ($alldata as $number) {
				if ($number['pilot_id'] == 0 || $number['pilot_id'] == '') $number['pilot_id'] = $number['pilot_name'];
				$this->addStatPilot($number['pilot_id'],$number['pilot_count'],$number['pilot_name'],'','',$number['format_source'],$reset);
			}
			
			if ($globalDebug) echo 'Count all departure airports...'."\n";
			$pall = $Spotter->countAllDepartureAirports(false,0,$last_update_day);
			if ($globalDebug) echo 'Count all detected departure airports...'."\n";
			$dall = $Spotter->countAllDetectedDepartureAirports(false,0,$last_update_day);
			if ($globalDebug) echo 'Order departure airports...'."\n";
			$alldata = array();
			foreach ($pall as $value) {
				$icao = $value['airport_departure_icao'];
				$alldata[$icao] = $value;
			}
			foreach ($dall as $value) {
				$icao = $value['airport_departure_icao'];
				if (isset($alldata[$icao])) {
					$alldata[$icao]['airport_departure_icao_count'] = $alldata[$icao]['airport_departure_icao_count'] + $value['airport_departure_icao_count'];
				} else $alldata[$icao] = $value;
			}
			$count = array();
			foreach ($alldata as $key => $row) {
				$count[$key] = $row['airport_departure_icao_count'];
			}
			array_multisort($count,SORT_DESC,$alldata);
			foreach ($alldata as $number) {
				echo $this->addStatDepartureAirports($number['airport_departure_icao'],$number['airport_departure_name'],$number['airport_departure_city'],$number['airport_departure_country'],$number['airport_departure_icao_count'],'','',$reset);
			}
			if ($globalDebug) echo 'Count all arrival airports...'."\n";
			$pall = $Spotter->countAllArrivalAirports(false,0,$last_update_day);
			if ($globalDebug) echo 'Count all detected arrival airports...'."\n";
			$dall = $Spotter->countAllDetectedArrivalAirports(false,0,$last_update_day);
			if ($globalDebug) echo 'Order arrival airports...'."\n";
			$alldata = array();
			foreach ($pall as $value) {
				$icao = $value['airport_arrival_icao'];
				$alldata[$icao] = $value;
			}
			foreach ($dall as $value) {
				$icao = $value['airport_arrival_icao'];
				if (isset($alldata[$icao])) {
					$alldata[$icao]['airport_arrival_icao_count'] = $alldata[$icao]['airport_arrival_icao_count'] + $value['airport_arrival_icao_count'];
				} else $alldata[$icao] = $value;
			}
			$count = array();
			foreach ($alldata as $key => $row) {
				$count[$key] = $row['airport_arrival_icao_count'];
			}
			array_multisort($count,SORT_DESC,$alldata);
			foreach ($alldata as $number) {
				echo $this->addStatArrivalAirports($number['airport_arrival_icao'],$number['airport_arrival_name'],$number['airport_arrival_city'],$number['airport_arrival_country'],$number['airport_arrival_icao_count'],'','',$reset);
			}
			if ($Connection->tableExists('countries')) {
				if ($globalDebug) echo 'Count all flights by countries...'."\n";
				//$SpotterArchive = new SpotterArchive();
				//$alldata = $SpotterArchive->countAllFlightOverCountries(false,0,$last_update_day);
				$Spotter = new Spotter($this->db);
				$alldata = $Spotter->countAllFlightOverCountries(false,0,$last_update_day);
				foreach ($alldata as $number) {
					$this->addStatCountry($number['flight_country_iso2'],$number['flight_country_iso3'],$number['flight_country'],$number['flight_count'],'','',$reset);
				}
			}
			
			if (isset($globalAccidents) && $globalAccidents) {
				if ($globalDebug) echo 'Count fatalities stats...'."\n";
				$Accident = new Accident($this->db);
				$this->deleteStatsByType('fatalities_byyear');
				$alldata = $Accident->countFatalitiesByYear();
				foreach ($alldata as $number) {
					$this->addStat('fatalities_byyear',$number['count'],date('Y-m-d H:i:s',mktime(0,0,0,1,1,$number['year'])));
				}
				$this->deleteStatsByType('fatalities_bymonth');
				$alldata = $Accident->countFatalitiesLast12Months();
				foreach ($alldata as $number) {
					$this->addStat('fatalities_bymonth',$number['count'],date('Y-m-d H:i:s',mktime(0,0,0,$number['month'],1,$number['year'])));
				}
			}

			// Add by month using getstat if month finish...
			//if (date('m',strtotime($last_update_day)) != date('m')) {
			if ($globalDebug) echo 'Count all flights by months...'."\n";
			$last_month = date('Y-m-01 00:00:00', strtotime('-1 month', strtotime($last_update_day)));
			$filter_last_month = array('since_date' => $last_month);
			$Spotter = new Spotter($this->db);
			$alldata = $Spotter->countAllMonths($filter_last_month);
			$lastyear = false;
			foreach ($alldata as $number) {
				if ($number['year_name'] != date('Y')) $lastyear = true;
				$this->addStat('flights_bymonth',$number['date_count'],date('Y-m-d H:i:s',mktime(0,0,0,$number['month_name'],1,$number['year_name'])));
			}
			if ($globalDebug) echo 'Count all military flights by months...'."\n";
			$alldata = $Spotter->countAllMilitaryMonths($filter_last_month);
			foreach ($alldata as $number) {
				$this->addStat('military_flights_bymonth',$number['date_count'],date('Y-m-d H:i:s',mktime(0,0,0,$number['month_name'],1,$number['year_name'])));
			}
			if ($globalDebug) echo 'Count all owners by months...'."\n";
			$alldata = $Spotter->countAllMonthsOwners($filter_last_month);
			foreach ($alldata as $number) {
				$this->addStat('owners_bymonth',$number['date_count'],date('Y-m-d H:i:s',mktime(0,0,0,$number['month_name'],1,$number['year_name'])));
			}
			if ($globalDebug) echo 'Count all pilots by months...'."\n";
			$alldata = $Spotter->countAllMonthsPilots($filter_last_month);
			foreach ($alldata as $number) {
				$this->addStat('pilots_bymonth',$number['date_count'],date('Y-m-d H:i:s',mktime(0,0,0,$number['month_name'],1,$number['year_name'])));
			}
			if ($globalDebug) echo 'Count all airlines by months...'."\n";
			$alldata = $Spotter->countAllMonthsAirlines($filter_last_month);
			foreach ($alldata as $number) {
				$this->addStat('airlines_bymonth',$number['date_count'],date('Y-m-d H:i:s',mktime(0,0,0,$number['month_name'],1,$number['year_name'])));
			}
			if ($globalDebug) echo 'Count all aircrafts by months...'."\n";
			$alldata = $Spotter->countAllMonthsAircrafts($filter_last_month);
			foreach ($alldata as $number) {
				$this->addStat('aircrafts_bymonth',$number['date_count'],date('Y-m-d H:i:s',mktime(0,0,0,$number['month_name'],1,$number['year_name'])));
			}
			if ($globalDebug) echo 'Count all real arrivals by months...'."\n";
			$alldata = $Spotter->countAllMonthsRealArrivals($filter_last_month);
			foreach ($alldata as $number) {
				$this->addStat('realarrivals_bymonth',$number['date_count'],date('Y-m-d H:i:s',mktime(0,0,0,$number['month_name'],1,$number['year_name'])));
			}
			if ($globalDebug) echo 'Airports data...'."\n";
			if ($globalDebug) echo '...Departure'."\n";
			$this->deleteStatAirport('daily');
//			$pall = $Spotter->getLast7DaysAirportsDeparture();
  //      		$dall = $Spotter->getLast7DaysDetectedAirportsDeparture();
			$pall = $Spotter->getLast7DaysAirportsDeparture();
			$dall = $Spotter->getLast7DaysDetectedAirportsDeparture();
			/*
			$alldata = array();
			foreach ($pall as $value) {
				$icao = $value['departure_airport_icao'];
				$alldata[$icao] = $value;
			}
			foreach ($dall as $value) {
				$icao = $value['departure_airport_icao'];
				$ddate = $value['date'];
				if (isset($alldata[$icao])) {
					$alldata[$icao]['departure_airport_count'] = $alldata[$icao]['departure_airport_count'] + $value['departure_airport_count'];
				} else $alldata[$icao] = $value;
			}
			$count = array();
			foreach ($alldata as $key => $row) {
				$count[$key] = $row['departure_airport_count'];
			}
			array_multisort($count,SORT_DESC,$alldata);
			*/
			foreach ($dall as $value) {
				$icao = $value['departure_airport_icao'];
				$ddate = $value['date'];
				$find = false;
				foreach ($pall as $pvalue) {
					if ($pvalue['departure_airport_icao'] == $icao && $pvalue['date'] == $ddate) {
						$pvalue['departure_airport_count'] = $pvalue['departure_airport_count'] + $value['departure_airport_count'];
						$find = true;
						break;
					}
				}
				if ($find === false) {
					$pall[] = $value;
				}
			}
			$alldata = $pall;
			foreach ($alldata as $number) {
				$this->addStatDepartureAirportsDaily($number['date'],$number['departure_airport_icao'],$number['departure_airport_name'],$number['departure_airport_city'],$number['departure_airport_country'],$number['departure_airport_count']);
			}
			echo '...Arrival'."\n";
			$pall = $Spotter->getLast7DaysAirportsArrival();
			$dall = $Spotter->getLast7DaysDetectedAirportsArrival();
			/*
			$alldata = array();
			foreach ($pall as $value) {
				$icao = $value['arrival_airport_icao'];
				$alldata[$icao] = $value;
			}
			foreach ($dall as $value) {
				$icao = $value['arrival_airport_icao'];
				if (isset($alldata[$icao])) {
					$alldata[$icao]['arrival_airport_icao_count'] = $alldata[$icao]['arrival_airport_count'] + $value['arrival_airport_count'];
				} else $alldata[$icao] = $value;
			}
			$count = array();
			foreach ($alldata as $key => $row) {
				$count[$key] = $row['arrival_airport_count'];
			}
			array_multisort($count,SORT_DESC,$alldata);
			*/

			foreach ($dall as $value) {
				$icao = $value['arrival_airport_icao'];
				$ddate = $value['date'];
				$find = false;
				foreach ($pall as $pvalue) {
					if ($pvalue['arrival_airport_icao'] == $icao && $pvalue['date'] == $ddate) {
						$pvalue['arrival_airport_count'] = $pvalue['arrival_airport_count'] + $value['arrival_airport_count'];
						$find = true;
						break;
					}
				}
				if ($find === false) {
						$pall[] = $value;
				}
			}
			$alldata = $pall;
			foreach ($alldata as $number) {
				$this->addStatArrivalAirportsDaily($number['date'],$number['arrival_airport_icao'],$number['arrival_airport_name'],$number['arrival_airport_city'],$number['arrival_airport_country'],$number['arrival_airport_count']);
			}

			echo 'Flights data...'."\n";
			$this->deleteStatFlight('month');
			echo '-> countAllDatesLastMonth...'."\n";
			$alldata = $Spotter->countAllDatesLastMonth($filter_last_month);
			foreach ($alldata as $number) {
				$this->addStatFlight('month',$number['date_name'],$number['date_count']);
			}
			echo '-> countAllDates...'."\n";
			$previousdata = $this->countAllDates();
			$previousdatabyairlines = $this->countAllDatesByAirlines();
			$this->deleteStatFlight('date');
			$alldata = $Common->array_merge_noappend($previousdata,$Spotter->countAllDates($filter_last_month));
			$values = array();
			foreach ($alldata as $cnt) {
				$values[] = $cnt['date_count'];
			}
			array_multisort($values,SORT_DESC,$alldata);
			array_splice($alldata,11);
			foreach ($alldata as $number) {
				$this->addStatFlight('date',$number['date_name'],$number['date_count']);
			}
			
			$this->deleteStatFlight('hour');
			echo '-> countAllHours...'."\n";
			$alldata = $Spotter->countAllHours('hour',$filter_last_month);
			foreach ($alldata as $number) {
				$this->addStatFlight('hour',$number['hour_name'],$number['hour_count']);
			}

			// Count by airlines
			echo '--- Stats by airlines ---'."\n";
			if ($Connection->tableExists('countries')) {
				if ($globalDebug) echo 'Count all flights by countries by airlines...'."\n";
				$SpotterArchive = new SpotterArchive($this->db);
				//$Spotter = new Spotter($this->db);
				$alldata = $SpotterArchive->countAllFlightOverCountriesByAirlines(false,0,$last_update_day);
				//$alldata = $Spotter->countAllFlightOverCountriesByAirlines(false,0,$last_update_day);
				foreach ($alldata as $number) {
					$this->addStatCountry($number['flight_country_iso2'],$number['flight_country_iso3'],$number['flight_country'],$number['flight_count'],$number['airline_icao'],'',$reset);
				}
			}
			if ($globalDebug) echo 'Count all aircraft types by airlines...'."\n";
			$Spotter = new Spotter($this->db);
			$alldata = $Spotter->countAllAircraftTypesByAirlines(false,0,$last_update_day);
			foreach ($alldata as $number) {
				$this->addStatAircraft($number['aircraft_icao'],$number['aircraft_icao_count'],$number['aircraft_name'],$number['aircraft_manufacturer'],$number['airline_icao'],'',$reset);
			}
			if ($globalDebug) echo 'Count all aircraft registrations by airlines...'."\n";
			$alldata = $Spotter->countAllAircraftRegistrationsByAirlines(false,0,$last_update_day);
			foreach ($alldata as $number) {
				$this->addStatAircraftRegistration($number['registration'],$number['aircraft_registration_count'],$number['aircraft_icao'],$number['airline_icao'],'',$reset);
			}
			if ($globalDebug) echo 'Count all callsigns by airlines...'."\n";
			$alldata = $Spotter->countAllCallsignsByAirlines(false,0,$last_update_day);
			foreach ($alldata as $number) {
				$this->addStatCallsign($number['callsign_icao'],$number['callsign_icao_count'],$number['airline_icao'],'',$reset);
			}
			if ($globalDebug) echo 'Count all owners by airlines...'."\n";
			$alldata = $Spotter->countAllOwnersByAirlines(false,0,$last_update_day);
			foreach ($alldata as $number) {
				$this->addStatOwner($number['owner_name'],$number['owner_count'],$number['airline_icao'],'',$reset);
			}
			if ($globalDebug) echo 'Count all pilots by airlines...'."\n";
			$alldata = $Spotter->countAllPilotsByAirlines(false,0,$last_update_day);
			foreach ($alldata as $number) {
				$this->addStatPilot($number['pilot_id'],$number['pilot_count'],$number['pilot_name'],$number['airline_icao'],'',$number['format_source'],$reset);
			}
			if ($globalDebug) echo 'Count all departure airports by airlines...'."\n";
			$pall = $Spotter->countAllDepartureAirportsByAirlines(false,0,$last_update_day);
			if ($globalDebug) echo 'Count all detected departure airports by airlines...'."\n";
			$dall = $Spotter->countAllDetectedDepartureAirportsByAirlines(false,0,$last_update_day);
			if ($globalDebug) echo 'Order detected departure airports by airlines...'."\n";
			//$alldata = array();
			foreach ($dall as $value) {
				$icao = $value['airport_departure_icao'];
				$dicao = $value['airline_icao'];
				$find = false;
				foreach ($pall as $pvalue) {
					if ($pvalue['airport_departure_icao'] == $icao && $pvalue['airline_icao'] = $dicao) {
						$pvalue['airport_departure_icao_count'] = $pvalue['airport_departure_icao_count'] + $value['airport_departure_icao_count'];
						$find = true;
						break;
					}
				}
				if ($find === false) {
					$pall[] = $value;
				}
			}
			$alldata = $pall;
			foreach ($alldata as $number) {
				echo $this->addStatDepartureAirports($number['airport_departure_icao'],$number['airport_departure_name'],$number['airport_departure_city'],$number['airport_departure_country'],$number['airport_departure_icao_count'],$number['airline_icao'],'',$reset);
			}
			if ($globalDebug) echo 'Count all arrival airports by airlines...'."\n";
			$pall = $Spotter->countAllArrivalAirportsByAirlines(false,0,$last_update_day);
			if ($globalDebug) echo 'Count all detected arrival airports by airlines...'."\n";
			$dall = $Spotter->countAllDetectedArrivalAirportsByAirlines(false,0,$last_update_day);
			if ($globalDebug) echo 'Order arrival airports by airlines...'."\n";
			//$alldata = array();
			foreach ($dall as $value) {
				$icao = $value['airport_arrival_icao'];
				$dicao = $value['airline_icao'];
				$find = false;
				foreach ($pall as $pvalue) {
					if ($pvalue['airport_arrival_icao'] == $icao && $pvalue['airline_icao'] = $dicao) {
						$pvalue['airport_arrival_icao_count'] = $pvalue['airport_arrival_icao_count'] + $value['airport_arrival_icao_count'];
						$find = true;
						break;
					}
				}
				if ($find === false) {
					$pall[] = $value;
				}
			}
			$alldata = $pall;
			foreach ($alldata as $number) {
				if ($number['airline_icao'] != '') echo $this->addStatArrivalAirports($number['airport_arrival_icao'],$number['airport_arrival_name'],$number['airport_arrival_city'],$number['airport_arrival_country'],$number['airport_arrival_icao_count'],$number['airline_icao'],'',$reset);
			}
			if ($globalDebug) echo 'Count all flights by months by airlines...'."\n";
			$Spotter = new Spotter($this->db);
			$alldata = $Spotter->countAllMonthsByAirlines($filter_last_month);
			$lastyear = false;
			foreach ($alldata as $number) {
				if ($number['year_name'] != date('Y')) $lastyear = true;
				$this->addStat('flights_bymonth',$number['date_count'],date('Y-m-d H:i:s',mktime(0,0,0,$number['month_name'],1,$number['year_name'])),$number['airline_icao']);
			}
			if ($globalDebug) echo 'Count all owners by months by airlines...'."\n";
			$alldata = $Spotter->countAllMonthsOwnersByAirlines($filter_last_month);
			foreach ($alldata as $number) {
				$this->addStat('owners_bymonth',$number['date_count'],date('Y-m-d H:i:s',mktime(0,0,0,$number['month_name'],1,$number['year_name'])),$number['airline_icao']);
			}
			if ($globalDebug) echo 'Count all pilots by months by airlines...'."\n";
			$alldata = $Spotter->countAllMonthsPilotsByAirlines($filter_last_month);
			foreach ($alldata as $number) {
				$this->addStat('pilots_bymonth',$number['date_count'],date('Y-m-d H:i:s',mktime(0,0,0,$number['month_name'],1,$number['year_name'])),$number['airline_icao']);
			}
			if ($globalDebug) echo 'Count all aircrafts by months by airlines...'."\n";
			$alldata = $Spotter->countAllMonthsAircraftsByAirlines($filter_last_month);
			foreach ($alldata as $number) {
				$this->addStat('aircrafts_bymonth',$number['date_count'],date('Y-m-d H:i:s',mktime(0,0,0,$number['month_name'],1,$number['year_name'])),$number['airline_icao']);
			}
			if ($globalDebug) echo 'Count all real arrivals by months by airlines...'."\n";
			$alldata = $Spotter->countAllMonthsRealArrivalsByAirlines($filter_last_month);
			foreach ($alldata as $number) {
				$this->addStat('realarrivals_bymonth',$number['date_count'],date('Y-m-d H:i:s',mktime(0,0,0,$number['month_name'],1,$number['year_name'])),$number['airline_icao']);
			}
			if ($globalDebug) echo '...Departure'."\n";
			$pall = $Spotter->getLast7DaysAirportsDepartureByAirlines();
			$dall = $Spotter->getLast7DaysDetectedAirportsDepartureByAirlines();
			foreach ($dall as $value) {
				$icao = $value['departure_airport_icao'];
				$airline = $value['airline_icao'];
				$ddate = $value['date'];
				$find = false;
				foreach ($pall as $pvalue) {
					if ($pvalue['departure_airport_icao'] == $icao && $pvalue['date'] == $ddate && $pvalue['airline_icao'] = $airline) {
						$pvalue['departure_airport_count'] = $pvalue['departure_airport_count'] + $value['departure_airport_count'];
						$find = true;
						break;
					}
				}
				if ($find === false) {
					$pall[] = $value;
				}
			}
			$alldata = $pall;
			foreach ($alldata as $number) {
				$this->addStatDepartureAirportsDaily($number['date'],$number['departure_airport_icao'],$number['departure_airport_name'],$number['departure_airport_city'],$number['departure_airport_country'],$number['departure_airport_count'],$number['airline_icao']);
			}
			if ($globalDebug) echo '...Arrival'."\n";
			$pall = $Spotter->getLast7DaysAirportsArrivalByAirlines();
			$dall = $Spotter->getLast7DaysDetectedAirportsArrivalByAirlines();
			foreach ($dall as $value) {
				$icao = $value['arrival_airport_icao'];
				$airline = $value['airline_icao'];
				$ddate = $value['date'];
				$find = false;
				foreach ($pall as $pvalue) {
					if ($pvalue['arrival_airport_icao'] == $icao && $pvalue['date'] == $ddate && $pvalue['airline_icao'] == $airline) {
						$pvalue['arrival_airport_count'] = $pvalue['arrival_airport_count'] + $value['arrival_airport_count'];
						$find = true;
						break;
					}
				}
				if ($find === false) {
					$pall[] = $value;
				}
			}
			$alldata = $pall;
			foreach ($alldata as $number) {
				$this->addStatArrivalAirportsDaily($number['date'],$number['arrival_airport_icao'],$number['arrival_airport_name'],$number['arrival_airport_city'],$number['arrival_airport_country'],$number['arrival_airport_count'],$number['airline_icao']);
			}

			if ($globalDebug) echo 'Flights data...'."\n";
			if ($globalDebug) echo '-> countAllDatesLastMonth...'."\n";
			$alldata = $Spotter->countAllDatesLastMonthByAirlines($filter_last_month);
			foreach ($alldata as $number) {
				$this->addStatFlight('month',$number['date_name'],$number['date_count'], $number['airline_icao']);
			}
			if ($globalDebug) echo '-> countAllDates...'."\n";
			//$previousdata = $this->countAllDatesByAirlines();
			$alldata = $Common->array_merge_noappend($previousdatabyairlines,$Spotter->countAllDatesByAirlines($filter_last_month));
			$values = array();
			foreach ($alldata as $cnt) {
				$values[] = $cnt['date_count'];
			}
			array_multisort($values,SORT_DESC,$alldata);
			array_splice($alldata,11);
			foreach ($alldata as $number) {
				$this->addStatFlight('date',$number['date_name'],$number['date_count'],$number['airline_icao']);
			}
			
			if ($globalDebug) echo '-> countAllHours...'."\n";
			$alldata = $Spotter->countAllHoursByAirlines('hour',$filter_last_month);
			foreach ($alldata as $number) {
				$this->addStatFlight('hour',$number['hour_name'],$number['hour_count'],$number['airline_icao']);
			}

			// Stats by filters
			if (!isset($globalStatsFilters) || $globalStatsFilters == '') $globalStatsFilters = array();
			foreach ($globalStatsFilters as $name => $filter) {
				if (!empty($filter)) {
					//$filter_name = $filter['name'];
					$filter_name = $name;
					$reset = false;
					$last_update = $this->getLastStatsUpdate('last_update_stats_'.$filter_name);
					if (isset($filter['resetall']) && isset($last_update[0]['value']) && strtotime($filter['resetall']) > strtotime($last_update[0]['value'])) {
						if ($globalDebug) echo '!!! Delete stats for filter '.$filter_name.' !!!'."\n";
						$this->deleteOldStats($filter_name);
						unset($last_update);
					}
					if (isset($last_update[0]['value'])) {
						$last_update_day = $last_update[0]['value'];
					} else {
						$last_update_day = '2012-12-12 12:12:12';
						if (isset($filter['DeleteLastYearStats'])) {
							$last_update_day = date('Y').'-01-01 00:00:00';
						}
					}
					if (isset($filter['DeleteLastYearStats']) && date('Y',strtotime($last_update_day)) != date('Y')) {
						$last_update_day = date('Y').'-01-01 00:00:00';
						$reset = true;
					}
					// Count by filter
					if ($globalDebug) echo '--- Stats for filter '.$filter_name.' ---'."\n";
					$Spotter = new Spotter($this->db);
					if ($globalDebug) echo 'Count all aircraft types...'."\n";
					$alldata = $Spotter->countAllAircraftTypes(false,0,$last_update_day,$filter);
					foreach ($alldata as $number) {
						$this->addStatAircraft($number['aircraft_icao'],$number['aircraft_icao_count'],$number['aircraft_name'],$number['aircraft_manufacturer'],'',$filter_name,$reset);
					}
					if ($globalDebug) echo 'Count all airlines...'."\n";
					$alldata = $Spotter->countAllAirlines(false,0,$last_update_day,$filter);
					foreach ($alldata as $number) {
						$this->addStatAirline($number['airline_icao'],$number['airline_count'],$number['airline_name'],$filter_name,$reset);
					}
					if ($globalDebug) echo 'Count all aircraft registrations...'."\n";
					$alldata = $Spotter->countAllAircraftRegistrations(false,0,$last_update_day,$filter);
					foreach ($alldata as $number) {
						$this->addStatAircraftRegistration($number['registration'],$number['aircraft_registration_count'],$number['aircraft_icao'],'',$filter_name,$reset);
					}
					if ($globalDebug) echo 'Count all callsigns...'."\n";
					$alldata = $Spotter->countAllCallsigns(false,0,$last_update_day,$filter);
					foreach ($alldata as $number) {
						$this->addStatCallsign($number['callsign_icao'],$number['callsign_icao_count'],'',$filter_name,$reset);
					}
					if ($globalDebug) echo 'Count all owners...'."\n";
					$alldata = $Spotter->countAllOwners(false,0,$last_update_day,$filter);
					foreach ($alldata as $number) {
						$this->addStatOwner($number['owner_name'],$number['owner_count'],'',$filter_name,$reset);
					}
					if ($globalDebug) echo 'Count all pilots...'."\n";
					$alldata = $Spotter->countAllPilots(false,0,$last_update_day,$filter);
					foreach ($alldata as $number) {
						$this->addStatPilot($number['pilot_id'],$number['pilot_count'],$number['pilot_name'],'',$filter_name,$number['format_source'],$reset);
					}
					if ($globalDebug) echo 'Count departure airports...'."\n";
					$pall = $Spotter->countAllDepartureAirports(false,0,$last_update_day,$filter);
					$dall = $Spotter->countAllDetectedDepartureAirports(false,0,$last_update_day,$filter);
					$alldata = array();
					foreach ($pall as $value) {
						$icao = $value['airport_departure_icao'];
						$alldata[$icao] = $value;
					}
					foreach ($dall as $value) {
						$icao = $value['airport_departure_icao'];
						if (isset($alldata[$icao])) {
							$alldata[$icao]['airport_departure_icao_count'] = $alldata[$icao]['airport_departure_icao_count'] + $value['airport_departure_icao_count'];
						} else $alldata[$icao] = $value;
					}
					$count = array();
					foreach ($alldata as $key => $row) {
						$count[$key] = $row['airport_departure_icao_count'];
					}
					array_multisort($count,SORT_DESC,$alldata);
					foreach ($alldata as $number) {
						echo $this->addStatDepartureAirports($number['airport_departure_icao'],$number['airport_departure_name'],$number['airport_departure_city'],$number['airport_departure_country'],$number['airport_departure_icao_count'],'',$filter_name,$reset);
					}
					if ($globalDebug) echo 'Count all arrival airports...'."\n";
					$pall = $Spotter->countAllArrivalAirports(false,0,$last_update_day,false,$filter);
					$dall = $Spotter->countAllDetectedArrivalAirports(false,0,$last_update_day,false,$filter);
					$alldata = array();
					foreach ($pall as $value) {
						$icao = $value['airport_arrival_icao'];
						$alldata[$icao] = $value;
					}
					foreach ($dall as $value) {
						$icao = $value['airport_arrival_icao'];
						if (isset($alldata[$icao])) {
							$alldata[$icao]['airport_arrival_icao_count'] = $alldata[$icao]['airport_arrival_icao_count'] + $value['airport_arrival_icao_count'];
						} else $alldata[$icao] = $value;
					}
					$count = array();
					foreach ($alldata as $key => $row) {
						$count[$key] = $row['airport_arrival_icao_count'];
					}
					array_multisort($count,SORT_DESC,$alldata);
					foreach ($alldata as $number) {
						echo $this->addStatArrivalAirports($number['airport_arrival_icao'],$number['airport_arrival_name'],$number['airport_arrival_city'],$number['airport_arrival_country'],$number['airport_arrival_icao_count'],'',$filter_name,$reset);
					}
					if ($globalDebug) echo 'Count all months...'."\n";
					$Spotter = new Spotter($this->db);
					$alldata = $Spotter->countAllMonths($filter);
					$lastyear = false;
					foreach ($alldata as $number) {
						if ($number['year_name'] != date('Y')) $lastyear = true;
						$this->addStat('flights_bymonth',$number['date_count'],date('Y-m-d H:i:s',mktime(0,0,0,$number['month_name'],1,$number['year_name'])),'',$filter_name);
					}
					if ($globalDebug) echo 'Count all owners by months...'."\n";
					$alldata = $Spotter->countAllMonthsOwners($filter);
					foreach ($alldata as $number) {
						$this->addStat('owners_bymonth',$number['date_count'],date('Y-m-d H:i:s',mktime(0,0,0,$number['month_name'],1,$number['year_name'])),'',$filter_name);
					}
					if ($globalDebug) echo 'Count all pilots by months...'."\n";
					$alldata = $Spotter->countAllMonthsPilots($filter);
					foreach ($alldata as $number) {
						$this->addStat('pilots_bymonth',$number['date_count'],date('Y-m-d H:i:s',mktime(0,0,0,$number['month_name'],1,$number['year_name'])),'',$filter_name);
					}
					if ($globalDebug) echo 'Count all military by months...'."\n";
					$alldata = $Spotter->countAllMilitaryMonths($filter);
					foreach ($alldata as $number) {
						$this->addStat('military_flights_bymonth',$number['date_count'],date('Y-m-d H:i:s',mktime(0,0,0,$number['month_name'],1,$number['year_name'])),'',$filter_name);
					}
					if ($globalDebug) echo 'Count all aircrafts by months...'."\n";
					$alldata = $Spotter->countAllMonthsAircrafts($filter);
				    	foreach ($alldata as $number) {
			    			$this->addStat('aircrafts_bymonth',$number['date_count'],date('Y-m-d H:i:s',mktime(0,0,0,$number['month_name'],1,$number['year_name'])),'',$filter_name);
					}
					if ($globalDebug) echo 'Count all real arrivals by months...'."\n";
					$alldata = $Spotter->countAllMonthsRealArrivals($filter);
					foreach ($alldata as $number) {
						$this->addStat('realarrivals_bymonth',$number['date_count'],date('Y-m-d H:i:s',mktime(0,0,0,$number['month_name'],1,$number['year_name'])),'',$filter_name);
					}
					echo '...Departure'."\n";
					$pall = $Spotter->getLast7DaysAirportsDeparture('',$filter);
					$dall = $Spotter->getLast7DaysDetectedAirportsDeparture('',$filter);
					foreach ($dall as $value) {
						$icao = $value['departure_airport_icao'];
						$ddate = $value['date'];
						$find = false;
						foreach ($pall as $pvalue) {
							if ($pvalue['departure_airport_icao'] == $icao && $pvalue['date'] == $ddate) {
								$pvalue['departure_airport_count'] = $pvalue['departure_airport_count'] + $value['departure_airport_count'];
								$find = true;
								break;
							}
						}
						if ($find === false) {
							$pall[] = $value;
						}
					}
					$alldata = $pall;
					foreach ($alldata as $number) {
						$this->addStatDepartureAirportsDaily($number['date'],$number['departure_airport_icao'],$number['departure_airport_name'],$number['departure_airport_city'],$number['departure_airport_country'],$number['departure_airport_count'],'',$filter_name);
					}
					echo '...Arrival'."\n";
					$pall = $Spotter->getLast7DaysAirportsArrival('',$filter);
					$dall = $Spotter->getLast7DaysDetectedAirportsArrival('',$filter);
					foreach ($dall as $value) {
						$icao = $value['arrival_airport_icao'];
						$ddate = $value['date'];
						$find = false;
						foreach ($pall as $pvalue) {
							if ($pvalue['arrival_airport_icao'] == $icao && $pvalue['date'] == $ddate) {
								$pvalue['arrival_airport_count'] = $pvalue['arrival_airport_count'] + $value['arrival_airport_count'];
								$find = true;
								break;
							}
						}
						if ($find === false) {
							$pall[] = $value;
						}
					}
					$alldata = $pall;
					foreach ($alldata as $number) {
						$this->addStatArrivalAirportsDaily($number['date'],$number['arrival_airport_icao'],$number['arrival_airport_name'],$number['arrival_airport_city'],$number['arrival_airport_country'],$number['arrival_airport_count'],'',$filter_name);
					}
					echo 'Flights data...'."\n";
					echo '-> countAllDatesLastMonth...'."\n";
					$alldata = $Spotter->countAllDatesLastMonth($filter);
					foreach ($alldata as $number) {
						$this->addStatFlight('month',$number['date_name'],$number['date_count'], '',$filter_name);
					}
					echo '-> countAllDates...'."\n";
					$previousdata = $this->countAllDates('',$filter_name);
					$alldata = $Common->array_merge_noappend($previousdata,$Spotter->countAllDates($filter));
					$values = array();
					foreach ($alldata as $cnt) {
						$values[] = $cnt['date_count'];
					}
					array_multisort($values,SORT_DESC,$alldata);
					array_splice($alldata,11);
					foreach ($alldata as $number) {
						$this->addStatFlight('date',$number['date_name'],$number['date_count'],'',$filter_name);
					}
				
					echo '-> countAllHours...'."\n";
					$alldata = $Spotter->countAllHours('hour',$filter);
					foreach ($alldata as $number) {
						$this->addStatFlight('hour',$number['hour_name'],$number['hour_count'],'',$filter_name);
					}
					echo 'Insert last stats update date...'."\n";
					date_default_timezone_set('UTC');
					$this->addLastStatsUpdate('last_update_stats_'.$filter_name,date('Y-m-d G:i:s'));
					if (isset($filter['DeleteLastYearStats']) && $filter['DeleteLastYearStats'] == true) {
						if (date('Y',strtotime($last_update_day)) != date('Y')) {
							$this->deleteOldStats($filter_name);
							$this->addLastStatsUpdate('last_update_stats_'.$filter_name,date('Y').'-01-01 00:00:00');
						}
					}
				}
			}
		}

			// Last year stats
			if (isset($lastyear) && $lastyear) {
				echo 'Data from last year...'."\n";
				// SUM all previous month to put as year
				$previous_year = date('Y');
				$previous_year--;
				$this->addStat('aircrafts_byyear',$this->getSumStats('aircrafts_bymonth',$previous_year),$previous_year.'-01-01 00:00:00');
				$this->addStat('airlines_byyear',$this->getSumStats('airlines_bymonth',$previous_year),$previous_year.'-01-01 00:00:00');
				$this->addStat('owner_byyear',$this->getSumStats('owner_bymonth',$previous_year),$previous_year.'-01-01 00:00:00');
				$this->addStat('pilot_byyear',$this->getSumStats('pilot_bymonth',$previous_year),$previous_year.'-01-01 00:00:00');
				$allairlines = $this->getAllAirlineNames();
				foreach ($allairlines as $data) {
					$this->addStat('aircrafts_byyear',$this->getSumStats('aircrafts_bymonth',$previous_year,$data['airline_icao']),$previous_year.'-01-01 00:00:00',$data['airline_icao']);
					$this->addStat('airlines_byyear',$this->getSumStats('airlines_bymonth',$previous_year,$data['airline_icao']),$previous_year.'-01-01 00:00:00',$data['airline_icao']);
					$this->addStat('owner_byyear',$this->getSumStats('owner_bymonth',$previous_year,$data['airline_icao']),$previous_year.'-01-01 00:00:00',$data['airline_icao']);
					$this->addStat('pilot_byyear',$this->getSumStats('pilot_bymonth',$previous_year,$data['airline_icao']),$previous_year.'-01-01 00:00:00',$data['airline_icao']);
				}
				
				if (isset($globalArchiveYear) && $globalArchiveYear) {
					if ($globalArchive) {
						$query = "INSERT INTO spotter_archive_output SELECT * FROM spotter_output WHERE spotter_output.date < '".date('Y')."-01-01 00:00:00'";
						try {
							$sth = $this->db->prepare($query);
							$sth->execute();
						} catch(PDOException $e) {
							return "error : ".$e->getMessage().' - query : '.$query."\n";
						}
						$query = "INSERT INTO tracker_archive_output SELECT * FROM tracker_output WHERE tracker_output.date < '".date('Y')."-01-01 00:00:00'";
						try {
							$sth = $this->db->prepare($query);
							$sth->execute();
						} catch(PDOException $e) {
							return "error : ".$e->getMessage().' - query : '.$query."\n";
						}
						$query = "INSERT INTO marine_archive_output SELECT * FROM marine_output WHERE marine_output.date < '".date('Y')."-01-01 00:00:00'";
						try {
							$sth = $this->db->prepare($query);
							$sth->execute();
						} catch(PDOException $e) {
							return "error : ".$e->getMessage().' - query : '.$query."\n";
						}
					}
					echo 'Delete old data'."\n";
					if ($globalDBdriver == 'mysql') {
						$query = "DELETE FROM spotter_output WHERE spotter_output.date < '".date('Y')."-01-01 00:00:00' LIMIT 10000";
					} else {
						$query = "DELETE FROM spotter_output WHERE spotter_id IN (SELECT spotter_id FROM spotter_output WHERE spotter_output.date < '".date('Y')."-01-01 00:00:00' LIMIT 10000)";
					}
					try {
						$sth = $this->db->prepare($query);
						$sth->execute();
					} catch(PDOException $e) {
						return "error : ".$e->getMessage().' - query : '.$query."\n";
					}
					if ($globalDBdriver == 'mysql') {
						$query = "DELETE FROM tracker_output WHERE tracker_output.date < '".date('Y')."-01-01 00:00:00' LIMIT 10000";
					} else {
						$query = "DELETE FROM tracker_output WHERE tracker_id IN (SELECT tracker_id FROM tracker_output WHERE tracker_output.date < '".date('Y')."-01-01 00:00:00' LIMIT 10000)";
					}
					try {
						$sth = $this->db->prepare($query);
						$sth->execute();
					} catch(PDOException $e) {
						return "error : ".$e->getMessage().' - query : '.$query."\n";
					}
					if ($globalDBdriver == 'mysql') {
						$query = "DELETE FROM marine_output WHERE marine_output.date < '".date('Y')."-01-01 00:00:00' LIMIT 10000";
					} else {
						$query = "DELETE FROM marine_output WHERE marine_id IN (SELECT marine_id FROM marine_output WHERE marine_output.date < '".date('Y')."-01-01 00:00:00' LIMIT 10000)";
					}
					try {
						$sth = $this->db->prepare($query);
						$sth->execute();
					} catch(PDOException $e) {
						return "error : ".$e->getMessage().' - query : '.$query."\n";
					}
				}
				if (isset($globalDeleteLastYearStats) && $globalDeleteLastYearStats) {
					$last_update = $this->getLastStatsUpdate('last_update_stats');
					if (date('Y',strtotime($last_update[0]['value'])) != date('Y')) {
						$this->deleteOldStats();
						$this->addLastStatsUpdate('last_update_stats',date('Y').'-01-01 00:00:00');
						$lastyearupdate = true;
					}
				}
			}
			if ($globalArchiveMonths > 0) {
				if ($globalArchive) {
					echo 'Archive old data...'."\n";
					if ($globalDBdriver == 'mysql') {
						//$query = "INSERT INTO spotter_archive_output SELECT * FROM spotter_output WHERE spotter_output.date < DATE_FORMAT(UTC_TIMESTAMP() - INTERVAL ".$globalArchiveMonths." MONTH, '%Y/%m/01')";
						$query = "INSERT INTO spotter_archive_output (spotter_id,flightaware_id,ident,registration,airline_name,airline_icao,airline_country,airline_type,aircraft_icao,aircraft_name,aircraft_manufacturer,departure_airport_icao,departure_airport_name,departure_airport_city,departure_airport_country,departure_airport_time,arrival_airport_icao,arrival_airport_name,arrival_airport_city,arrival_airport_country,arrival_airport_time,route_stop,date,latitude,longitude,waypoints,altitude,heading,ground_speed,highlight,squawk,ModeS,pilot_id,pilot_name,owner_name,verticalrate,format_source,source_name,ground,last_ground,last_seen,last_latitude,last_longitude,last_altitude,last_ground_speed,real_arrival_airport_icao,real_arrival_airport_time,real_departure_airport_icao,real_departure_airport_time)
							    SELECT spotter_id,flightaware_id,ident,registration,airline_name,airline_icao,airline_country,airline_type,aircraft_icao,aircraft_name,aircraft_manufacturer,departure_airport_icao,departure_airport_name,departure_airport_city,departure_airport_country,departure_airport_time,arrival_airport_icao,arrival_airport_name,arrival_airport_city,arrival_airport_country,arrival_airport_time,route_stop,date,latitude,longitude,waypoints,altitude,heading,ground_speed,highlight,squawk,ModeS,pilot_id,pilot_name,owner_name,verticalrate,format_source,source_name,ground,last_ground,last_seen,last_latitude,last_longitude,last_altitude,last_ground_speed,real_arrival_airport_icao,real_arrival_airport_time,real_departure_airport_icao,real_departure_airport_time
							     FROM spotter_output WHERE spotter_output.date < DATE_FORMAT(UTC_TIMESTAMP() - INTERVAL ".$globalArchiveMonths." MONTH, '%Y/%m/01')";
					} else {
						$query = "INSERT INTO spotter_archive_output (spotter_id,flightaware_id,ident,registration,airline_name,airline_icao,airline_country,airline_type,aircraft_icao,aircraft_name,aircraft_manufacturer,departure_airport_icao,departure_airport_name,departure_airport_city,departure_airport_country,departure_airport_time,arrival_airport_icao,arrival_airport_name,arrival_airport_city,arrival_airport_country,arrival_airport_time,route_stop,date,latitude,longitude,waypoints,altitude,heading,ground_speed,highlight,squawk,ModeS,pilot_id,pilot_name,owner_name,verticalrate,format_source,source_name,ground,last_ground,last_seen,last_latitude,last_longitude,last_altitude,last_ground_speed,real_arrival_airport_icao,real_arrival_airport_time,real_departure_airport_icao,real_departure_airport_time)
							     SELECT 
								spotter_id,flightaware_id,ident,registration,airline_name,airline_icao,airline_country,airline_type,aircraft_icao,aircraft_name,aircraft_manufacturer,departure_airport_icao,departure_airport_name,departure_airport_city,departure_airport_country,departure_airport_time,arrival_airport_icao,arrival_airport_name,arrival_airport_city,arrival_airport_country,arrival_airport_time,route_stop,date,latitude,longitude,waypoints,altitude,heading,ground_speed,highlight,squawk,ModeS,pilot_id,pilot_name,owner_name,verticalrate,format_source,source_name,ground,last_ground,last_seen,last_latitude,last_longitude,last_altitude,last_ground_speed,real_arrival_airport_icao,real_arrival_airport_time,real_departure_airport_icao,real_departure_airport_time
							    FROM spotter_output WHERE spotter_output.date < CAST(to_char(CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$globalArchiveMonths." MONTHS', 'YYYY/mm/01') AS TIMESTAMP)";
					}
					try {
						$sth = $this->db->prepare($query);
						$sth->execute();
					} catch(PDOException $e) {
						return "error : ".$e->getMessage();
					}
					echo 'Archive old tracker data...'."\n";
					if ($globalDBdriver == 'mysql') {
						$query = "INSERT INTO tracker_archive_output (tracker_archive_output_id,famtrackid, ident, latitude, longitude, altitude, heading, ground_speed, date, format_source, source_name, comment, type) 
							    SELECT tracker_id,famtrackid, ident, latitude, longitude, altitude, heading, ground_speed, date, format_source, source_name, comment, type
							     FROM tracker_output WHERE tracker_output.date < DATE_FORMAT(UTC_TIMESTAMP() - INTERVAL ".$globalArchiveMonths." MONTH, '%Y/%m/01')";
					} else {
						$query = "INSERT INTO tracker_archive_output (tracker_archive_output_id,famtrackid, ident, latitude, longitude, altitude, heading, ground_speed, date, format_source, source_name, comment, type) 
							     SELECT tracker_id,famtrackid, ident, latitude, longitude, altitude, heading, ground_speed, date, format_source, source_name, comment, type
							    FROM tracker_output WHERE tracker_output.date < CAST(to_char(CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$globalArchiveMonths." MONTHS', 'YYYY/mm/01') AS TIMESTAMP)";
					}
					try {
						$sth = $this->db->prepare($query);
						$sth->execute();
					} catch(PDOException $e) {
						return "error : ".$e->getMessage();
					}
					echo 'Archive old marine data...'."\n";
					if ($globalDBdriver == 'mysql') {
						$query = "INSERT INTO marine_archive_output (marine_archive_output_id,fammarine_id, ident, latitude, longitude, heading, ground_speed, date, format_source, source_name, mmsi, type, status,imo,arrival_port_name,arrival_port_date) 
							    SELECT marine_id,fammarine_id, ident, latitude, longitude, heading, ground_speed, date, format_source, source_name, mmsi, type, status,imo,arrival_port_name,arrival_port_date 
							     FROM marine_output WHERE marine_output.date < DATE_FORMAT(UTC_TIMESTAMP() - INTERVAL ".$globalArchiveMonths." MONTH, '%Y/%m/01')";
					} else {
						$query = "INSERT INTO marine_archive_output (marine_archive_output_id,fammarine_id, ident, latitude, longitude, heading, ground_speed, date, format_source, source_name, mmsi, type, status,imo,arrival_port_name,arrival_port_date) 
							     SELECT marine_id,fammarine_id, ident, latitude, longitude, heading, ground_speed, date, format_source, source_name, mmsi, type, status,imo,arrival_port_name,arrival_port_date 
							    FROM marine_output WHERE marine_output.date < CAST(to_char(CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$globalArchiveMonths." MONTHS', 'YYYY/mm/01') AS TIMESTAMP)";
					}
					try {
						$sth = $this->db->prepare($query);
						$sth->execute();
					} catch(PDOException $e) {
						return "error : ".$e->getMessage();
					}
				}
				echo 'Deleting old data...'."\n";
				if ($globalDBdriver == 'mysql') {
					$query = "DELETE FROM spotter_output WHERE spotter_output.date < DATE_FORMAT(UTC_TIMESTAMP() - INTERVAL ".$globalArchiveMonths." MONTH, '%Y/%m/01')";
				} else {
					$query = "DELETE FROM spotter_output WHERE spotter_id IN ( SELECT spotter_id FROM spotter_output WHERE spotter_output.date < CAST(to_char(CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$globalArchiveMonths." MONTHS', 'YYYY/mm/01') AS TIMESTAMP))";
				}
				try {
					$sth = $this->db->prepare($query);
					$sth->execute();
				} catch(PDOException $e) {
					return "error : ".$e->getMessage();
				}
				echo 'Deleting old tracker data...'."\n";
				if ($globalDBdriver == 'mysql') {
					$query = "DELETE FROM tracker_output WHERE tracker_output.date < DATE_FORMAT(UTC_TIMESTAMP() - INTERVAL ".$globalArchiveMonths." MONTH, '%Y/%m/01')";
				} else {
					$query = "DELETE FROM tracker_output WHERE tracker_id IN ( SELECT tracker_id FROM tracker_output WHERE tracker_output.date < CAST(to_char(CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$globalArchiveMonths." MONTHS', 'YYYY/mm/01') AS TIMESTAMP))";
				}
				try {
					$sth = $this->db->prepare($query);
					$sth->execute();
				} catch(PDOException $e) {
					return "error : ".$e->getMessage();
				}
				echo 'Deleting old marine data...'."\n";
				if ($globalDBdriver == 'mysql') {
					$query = "DELETE FROM marine_output WHERE marine_output.date < DATE_FORMAT(UTC_TIMESTAMP() - INTERVAL ".$globalArchiveMonths." MONTH, '%Y/%m/01')";
				} else {
					$query = "DELETE FROM marine_output WHERE marine_id IN ( SELECT marine_id FROM marine_output WHERE marine_output.date < CAST(to_char(CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$globalArchiveMonths." MONTHS', 'YYYY/mm/01') AS TIMESTAMP))";
				}
				try {
					$sth = $this->db->prepare($query);
					$sth->execute();
				} catch(PDOException $e) {
					return "error : ".$e->getMessage();
				}
			}
			if (!isset($lastyearupdate)) {
				echo 'Insert last stats update date...'."\n";
				date_default_timezone_set('UTC');
				$this->addLastStatsUpdate('last_update_stats',date('Y-m-d G:i:s'));
			}
			if ($globalStatsResetYear) {
				require_once(dirname(__FILE__).'/../install/class.settings.php');
				settings::modify_settings(array('globalStatsResetYear' => 'FALSE'));
			}
	}
}

?>