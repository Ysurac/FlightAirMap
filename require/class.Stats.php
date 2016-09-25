<?php
/*
* This class save stats older than a year and $globalArchiveMonths
*/

require_once(dirname(__FILE__).'/class.Spotter.php');
require_once(dirname(__FILE__).'/class.SpotterArchive.php');
require_once(dirname(__FILE__).'/class.Common.php');
class Stats {
	public $db;
	public function __construct($dbc = null) {
		$Connection = new Connection($dbc);
		$this->db = $Connection->db();
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
        }

	public function getLastStatsUpdate($type = 'last_update_stats') {
                $query = "SELECT value FROM config WHERE name = :type";
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute(array(':type' => $type));
                } catch(PDOException $e) {
                        echo "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                return $all;
        }
	public function getAllAirlineNames() {
                $query = "SELECT * FROM stats_airline ORDER BY airline_name ASC";
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        echo "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                return $all;
        }
	public function getAllAircraftTypes() {
                $query = "SELECT * FROM stats_aircraft ORDER BY aircraft_name ASC";
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        echo "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                return $all;
        }
	public function getAllAirportNames() {
                $query = "SELECT airport_icao, airport_name,airport_city,airport_country FROM stats_airport GROUP BY airport_icao,airport_name,airport_city,airport_country ORDER BY airport_city ASC";
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        echo "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                return $all;
        }


	public function countAllAircraftTypes($limit = true) {
		if ($limit) $query = "SELECT aircraft_icao, cnt AS aircraft_icao_count, aircraft_name FROM stats_aircraft WHERE aircraft_name <> '' AND aircraft_icao <> '' ORDER BY aircraft_icao_count DESC LIMIT 10 OFFSET 0";
		else $query = "SELECT aircraft_icao, cnt AS aircraft_icao_count, aircraft_name FROM stats_aircraft WHERE aircraft_name <> '' AND aircraft_icao <> '' ORDER BY aircraft_icao_count DESC";
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        echo "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                if (empty($all)) {
            	    $Spotter = new Spotter($this->db);
            	    $all = $Spotter->countAllAircraftTypes($limit);
                }
                return $all;
	}
	public function countAllAirlineCountries($limit = true) {
		if ($limit) $query = "SELECT airlines.country AS airline_country, SUM(stats_airline.cnt) as airline_country_count FROM stats_airline,airlines WHERE stats_airline.airline_icao=airlines.icao GROUP BY airline_country ORDER BY airline_country_count DESC LIMIT 10 OFFSET 0";
		else $query = "SELECT airlines.country AS airline_country, SUM(stats_airline.cnt) as airline_country_count FROM stats_airline,airlines WHERE stats_airline.airline_icao=airlines.icao GROUP BY airline_country ORDER BY airline_country_count DESC";
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        echo "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                if (empty($all)) {
            		$Spotter = new Spotter($this->db);
            		$all = $Spotter->countAllAirlineCountries($limit);
                
                }
                return $all;
	}
	public function countAllAircraftManufacturers($limit = true) {
		if ($limit) $query = "SELECT aircraft.manufacturer AS aircraft_manufacturer, SUM(stats_aircraft.cnt) as aircraft_manufacturer_count FROM stats_aircraft,aircraft WHERE stats_aircraft.aircraft_icao=aircraft.icao GROUP BY aircraft.manufacturer ORDER BY aircraft_manufacturer_count DESC LIMIT 10 OFFSET 0";
		else $query = "SELECT aircraft.manufacturer AS aircraft_manufacturer, SUM(stats_aircraft.cnt) as aircraft_manufacturer_count FROM stats_aircraft,aircraft WHERE stats_aircraft.aircraft_icao=aircraft.icao GROUP BY aircraft.manufacturer ORDER BY aircraft_manufacturer_count DESC";
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        echo "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                if (empty($all)) {
            		$Spotter = new Spotter($this->db);
			$all = $Spotter->countAllAircraftManufacturers();
                }
                return $all;
	}

	public function countAllArrivalCountries($limit = true) {
		if ($limit) $query = "SELECT airport_country AS airport_arrival_country, arrival as airport_arrival_country_count FROM stats_airport WHERE stats_type = 'yearly' LIMIT 10 OFFSET 0";
		else $query = "SELECT airport_country AS airport_arrival_country, arrival as airport_arrival_country_count FROM stats_airport WHERE stats_type = 'yearly'";
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        echo "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                if (empty($all)) {
	                $Spotter = new Spotter($this->db);
            		$all = $Spotter->countAllArrivalCountries($limit);
                }
                return $all;
	}
	public function countAllDepartureCountries($limit = true) {
		if ($limit) $query = "SELECT airport_country AS airport_departure_country, departure as airport_departure_country_count FROM stats_airport WHERE stats_type = 'yearly' LIMIT 10 OFFSET 0";
		else $query = "SELECT airport_country AS airport_departure_country, departure as airport_departure_country_count FROM stats_airport WHERE stats_type = 'yearly'";
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        echo "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                if (empty($all)) {
		        $Spotter = new Spotter($this->db);
    	    	        $all = $Spotter->countAllDepartureCountries();
                }
                return $all;
	}

	public function countAllAirlines($limit = true) {
		if ($limit) $query = "SELECT stats_airline.airline_icao, stats_airline.cnt AS airline_count, stats_airline.airline_name, airlines.country as airline_country FROM stats_airline, airlines WHERE stats_airline.airline_name <> '' AND stats_airline.airline_icao <> '' AND airlines.icao = airline_icao ORDER BY airline_count DESC LIMIT 10 OFFSET 0";
		else $query = "SELECT stats_airline.airline_icao, stats_airline.cnt AS airline_count, stats_airline.airline_name, airlines.country as airline_country FROM stats_airline, airlines WHERE stats_airline.airline_name <> '' AND stats_airline.airline_icao <> '' AND airlines.icao = airline_icao ORDER BY airline_count DESC";
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        echo "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                if (empty($all)) {
	                $Spotter = new Spotter($this->db);
    		        $all = $Spotter->countAllAirlines($limit);
                }
                return $all;
	}
	public function countAllAircraftRegistrations($limit = true) {
		if ($limit) $query = "SELECT s.aircraft_icao, s.cnt AS aircraft_registration_count, a.type AS aircraft_name, s.registration FROM stats_registration s, aircraft a WHERE s.registration <> '' AND a.icao = s.aircraft_icao ORDER BY aircraft_registration_count DESC LIMIT 10 OFFSET 0";
		else $query = "SELECT s.aircraft_icao, s.cnt AS aircraft_registration_count, a.type AS aircraft_name FROM stats_registration s, aircraft a WHERE s.registration <> '' AND a.icao = s.aircraft_icao ORDER BY aircraft_registration_count DESC";
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        echo "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                if (empty($all)) {
	                $Spotter = new Spotter($this->db);
    		        $all = $Spotter->countAllAircraftRegistrations($limit);
                }
                return $all;
	}
	public function countAllCallsigns($limit = true) {
		if ($limit) $query = "SELECT s.callsign_icao, s.cnt AS callsign_icao_count, a.name AS airline_name, a.icao as airline_icao FROM stats_callsign s, airlines a WHERE s.callsign_icao <> '' AND a.icao = s.airline_icao ORDER BY callsign_icao_count DESC LIMIT 10 OFFSET 0";
		else $query = "SELECT s.callsign_icao, s.cnt AS callsign_icao_count, a.name AS airline_name, a.icao as airline_icao FROM stats_callsign s, airlines a WHERE s.callsign_icao <> '' AND a.icao = s.airline_icao ORDER BY callsign_icao_count DESC";
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        echo "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                if (empty($all)) {
	                $Spotter = new Spotter($this->db);
    		        $all = $Spotter->countAllCallsigns($limit);
                }
                return $all;
	}
	public function countAllFlightOverCountries($limit = true) {
		$Connection = new Connection();
		if ($Connection->tableExists('countries')) {
			if ($limit) $query = "SELECT countries.iso3 as flight_country_iso3, countries.iso2 as flight_country_iso2, countries.name as flight_country, cnt as flight_count, lat as flight_country_latitude, lon as flight_country_longitude FROM stats_country, countries WHERE stats_country.iso2 = countries.iso2 ORDER BY flight_count DESC LIMIT 20 OFFSET 0";
			else $query = "SELECT countries.iso3 as flight_country_iso3, countries.iso2 as flight_country_iso2, countries.name as flight_country, cnt as flight_count, lat as flight_country_latitude, lon as flight_country_longitude FROM stats_country, countries WHERE stats_country.iso2 = countries.iso2 ORDER BY flight_count DESC";
			 try {
				$sth = $this->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				echo "error : ".$e->getMessage();
			}
			$all = $sth->fetchAll(PDO::FETCH_ASSOC);
                /*
                if (empty($all)) {
	                $Spotter = new Spotter($this->db);
    		        $all = $Spotter->countAllFlightOverCountries($limit);
                }
                */
			return $all;
		} else {
			return array();
		}
	}
	public function countAllPilots($limit = true) {
		if ($limit) $query = "SELECT pilot_id, cnt AS pilot_count, pilot_name FROM stats_pilot ORDER BY pilot_count DESC LIMIT 10 OFFSET 0";
		else $query = "SELECT pilot_id, cnt AS pilot_count, pilot_name FROM stats_pilot ORDER BY pilot_count DESC";
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        echo "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                if (empty($all)) {
            		$Spotter = new Spotter($this->db);
            		$all = $Spotter->countAllPilots($limit);
                }
                return $all;
	}
	public function countAllOwners($limit = true) {
		if ($limit) $query = "SELECT owner_name, cnt AS owner_count FROM stats_owner ORDER BY owner_count DESC LIMIT 10 OFFSET 0";
		else $query = "SELECT owner_name, cnt AS owner_count FROM stats_owner ORDER BY owner_count DESC";
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        echo "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                if (empty($all)) {
            		$Spotter = new Spotter($this->db);
            		$all = $Spotter->countAllOwners($limit);
                }
                return $all;
	}
	public function countAllDepartureAirports($limit = true) {
		if ($limit) $query = "SELECT airport_icao AS airport_departure_icao,airport_city AS airport_departure_city,airport_country AS airport_departure_country,departure AS airport_departure_icao_count FROM stats_airport WHERE stats_type = 'yearly' LIMIT 10 OFFSET 0";
		else $query = "SELECT airport_icao AS airport_departure_icao,airport_city AS airport_departure_city,airport_country AS airport_departure_country,departure AS airport_departure_icao_count FROM stats_airport WHERE stats_type = 'yearly'";
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        echo "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                if (empty($all)) {
            		$Spotter = new Spotter($this->db);
            		$pall = $Spotter->countAllDepartureAirports($limit);
        		$dall = $Spotter->countAllDetectedDepartureAirports($limit);
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
	public function countAllArrivalAirports($limit = true) {
		if ($limit) $query = "SELECT airport_icao AS airport_arrival_icao,airport_city AS airport_arrival_city,airport_country AS airport_arrival_country,arrival AS airport_arrival_icao_count FROM stats_airport WHERE stats_type = 'yearly' LIMIT 10 OFFSET 0";
		else $query = "SELECT airport_icao AS airport_arrival_icao,airport_city AS airport_arrival_city,airport_country AS airport_arrival_country,arrival AS airport_arrival_icao_count FROM stats_airport WHERE stats_type = 'yearly'";
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        echo "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                if (empty($all)) {
            		$Spotter = new Spotter($this->db);
        		$pall = $Spotter->countAllArrivalAirports($limit);
        		$dall = $Spotter->countAllDetectedArrivalAirports($limit);
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
	public function countAllMonthsLastYear($limit = true) {
		global $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			if ($limit) $query = "SELECT MONTH(stats_date) as month_name, YEAR(stats_date) as year_name, cnt as date_count FROM stats WHERE stats_type = 'flights_bymonth' AND stats_date >= DATE_SUB(UTC_TIMESTAMP(),INTERVAL 12 MONTH)";
			else $query = "SELECT MONTH(stats_date) as month_name, YEAR(stats_date) as year_name, cnt as date_count FROM stats WHERE stats_type = 'flights_bymonth'";
		} else {
			if ($limit) $query = "SELECT EXTRACT(MONTH FROM stats_date) as month_name, EXTRACT(YEAR FROM stats_date) as year_name, cnt as date_count FROM stats WHERE stats_type = 'flights_bymonth' AND stats_date >= CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '12 MONTHS'";
			else $query = "SELECT EXTRACT(MONTH FROM stats_date) as month_name, EXTRACT(YEAR FROM stats_date) as year_name, cnt as date_count FROM stats WHERE stats_type = 'flights_bymonth'";
		}
		$query_data = array();
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute($query_data);
                } catch(PDOException $e) {
                        echo "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                if (empty($all)) {
            		$Spotter = new Spotter($this->db);
            		$all = $Spotter->countAllMonthsLastYear();
                }
                return $all;
	}
	
	public function countAllDatesLastMonth() {
		$query = "SELECT flight_date as date_name, cnt as date_count FROM stats_flight WHERE stats_type = 'month'";
		$query_data = array();
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute($query_data);
                } catch(PDOException $e) {
                        echo "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                if (empty($all)) {
            		$Spotter = new Spotter($this->db);
            		$all = $Spotter->countAllDatesLastMonth();
                }
                return $all;
	}
	public function countAllDatesLast7Days() {
		global $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			$query = "SELECT flight_date as date_name, cnt as date_count FROM stats_flight WHERE stats_type = 'month' AND flight_date >= DATE_SUB(UTC_TIMESTAMP(),INTERVAL 7 DAY)";
		} else {
			$query = "SELECT flight_date as date_name, cnt as date_count FROM stats_flight WHERE stats_type = 'month' AND flight_date::timestamp >= CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '7 DAYS'";
		}
		$query_data = array();
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute($query_data);
                } catch(PDOException $e) {
                        echo "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                if (empty($all)) {
            		$Spotter = new Spotter($this->db);
            		$all = $Spotter->countAllDatesLast7Days();
                }
                return $all;
	}
	public function countAllDates() {
		$query = "SELECT flight_date as date_name, cnt as date_count FROM stats_flight WHERE stats_type = 'date'";
		$query_data = array();
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute($query_data);
                } catch(PDOException $e) {
                        echo "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                if (empty($all)) {
            		$Spotter = new Spotter($this->db);
            		$all = $Spotter->countAllDates();
                }
                return $all;
	}
	public function countAllMonths() {
	    	$query = "SELECT YEAR(stats_date) AS year_name,MONTH(stats_date) AS month_name, cnt as date_count FROM stats WHERE stats_type = 'flights_bymonth'";
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        echo "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                if (empty($all)) {
            		$Spotter = new Spotter($this->db);
            		$all = $Spotter->countAllMonths();
                }
                return $all;
	}
	public function countAllMilitaryMonths() {
	    	$query = "SELECT YEAR(stats_date) AS year_name,MONTH(stats_date) AS month_name, cnt as date_count FROM stats WHERE stats_type = 'military_flights_bymonth'";
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        echo "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                if (empty($all)) {
            		$Spotter = new Spotter($this->db);
            		$all = $Spotter->countAllMilitaryMonths();
                }
                return $all;
	}
	public function countAllHours($orderby = 'hour',$limit = true) {
		global $globalTimezone, $globalDBdriver;

		if ($limit) $query = "SELECT flight_date as hour_name, cnt as hour_count FROM stats_flight WHERE stats_type = 'hour'";
		else $query = "SELECT flight_date as hour_name, cnt as hour_count FROM stats_flight WHERE stats_type = 'hour'";
		if ($orderby == 'hour') {
			if ($globalDBdriver == 'mysql') {
				$query .= " ORDER BY flight_date ASC";
			} else {
				$query .= " ORDER BY CAST(flight_date AS integer) ASC";
			}
		}
		if ($orderby == 'count') $query .= " ORDER BY hour_count DESC";
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        echo "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                if (empty($all)) {
            		$Spotter = new Spotter($this->db);
            		$all = $Spotter->countAllHours($orderby);
                }
                return $all;
	}
	
	public function countOverallFlights() {
		$all = $this->getSumStats('flights_bymonth',date('Y'));
		if (empty($all)) {
			$Spotter = new Spotter($this->db);
			$all = $Spotter->countOverallFlights();
		}
		return $all;
	}
	public function countOverallMilitaryFlights() {
		$all = $this->getSumStats('military_flights_bymonth',date('Y'));
		if (empty($all)) {
			$Spotter = new Spotter($this->db);
			$all = $Spotter->countOverallMilitaryFlights();
		}
		return $all;
	}
	public function countOverallArrival() {
		$all = $this->getSumStats('realarrivals_bymonth',date('Y'));
		if (empty($all)) {
			$Spotter = new Spotter($this->db);
			$all = $Spotter->countOverallArrival();
		}
		return $all;
	}
	public function countOverallAircrafts() {
		$all = $this->getSumStats('aircrafts_bymonth',date('Y'));
		if (empty($all)) {
			$Spotter = new Spotter($this->db);
			$all = $Spotter->countOverallAircrafts();
		}
		return $all;
	}
	public function countOverallAirlines() {
		$query = "SELECT COUNT(*) AS nb_airline FROM stats_airline";
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        echo "error : ".$e->getMessage();
                }
                $result = $sth->fetchAll(PDO::FETCH_ASSOC);
                $all = $result[0]['nb_airline'];
		//$all = $this->getSumStats('airlines_bymonth',date('Y'));
		if (empty($all)) {
			$Spotter = new Spotter($this->db);
			$all = $Spotter->countOverallAirlines();
		}
		return $all;
	}
	public function countOverallOwners() {
		/*
		$query = "SELECT COUNT(*) AS nb_owner FROM stats_owner";
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        echo "error : ".$e->getMessage();
                }
                $result = $sth->fetchAll(PDO::FETCH_ASSOC);
                $all = $result[0]['nb_owner'];
                */
		$all = $this->getSumStats('owners_bymonth',date('Y'));
		if (empty($all)) {
			$Spotter = new Spotter($this->db);
			$all = $Spotter->countOverallOwners();
		}
		return $all;
	}
	public function countOverallPilots() {
		$all = $this->getSumStats('pilots_bymonth',date('Y'));
		if (empty($all)) {
			$Spotter = new Spotter($this->db);
			$all = $Spotter->countOverallPilots();
		}
		return $all;
	}

	public function getLast7DaysAirports($airport_icao = '') {
		$query = "SELECT * FROM stats_airport WHERE stats_type = 'daily' AND airport_icao = :airport_icao ORDER BY date";
		$query_values = array(':airport_icao' => $airport_icao);
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute($query_values);
                } catch(PDOException $e) {
                        echo "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                return $all;
	}
	public function getStats($type) {
                $query = "SELECT * FROM stats WHERE stats_type = :type ORDER BY stats_date";
                $query_values = array(':type' => $type);
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute($query_values);
                } catch(PDOException $e) {
                        echo "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                return $all;
        }
	public function getSumStats($type,$year) {
    		global $globalArchiveMonths, $globalDBdriver;
    		if ($globalDBdriver == 'mysql') {
	                $query = "SELECT SUM(cnt) as total FROM stats WHERE stats_type = :type AND YEAR(stats_date) = :year";
	        } else {
            		$query = "SELECT SUM(cnt) as total FROM stats WHERE stats_type = :type AND EXTRACT(YEAR FROM stats_date) = :year";
                }
                $query_values = array(':type' => $type, ':year' => $year);
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute($query_values);
                } catch(PDOException $e) {
                        echo "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                return $all[0]['total'];
        }
	public function getStatsTotal($type) {
    		global $globalArchiveMonths, $globalDBdriver;
    		if ($globalDBdriver == 'mysql') {
			$query = "SELECT SUM(cnt) as total FROM stats WHERE stats_type = :type AND stats_date < DATE_SUB(UTC_TIMESTAMP(), INTERVAL ".$globalArchiveMonths." MONTH)";
		} else {
			$query = "SELECT SUM(cnt) as total FROM stats WHERE stats_type = :type AND stats_date < CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$globalArchiveMonths." MONTHS'";
                }
                $query_values = array(':type' => $type);
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute($query_values);
                } catch(PDOException $e) {
                        echo "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                return $all[0]['total'];
        }
	public function getStatsAircraftTotal() {
    		global $globalArchiveMonths, $globalDBdriver;
    		if ($globalDBdriver == 'mysql') {
			$query = "SELECT SUM(cnt) as total FROM stats_aircraft";
                } else {
			$query = "SELECT SUM(cnt) as total FROM stats_aircraft";
                }
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        echo "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                return $all[0]['total'];
        }
	public function getStatsAirlineTotal() {
    		global $globalArchiveMonths, $globalDBdriver;
    		if ($globalDBdriver == 'mysql') {
			$query = "SELECT SUM(cnt) as total FROM stats_airline";
                } else {
			$query = "SELECT SUM(cnt) as total FROM stats_airline";
                }
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        echo "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                return $all[0]['total'];
        }
	public function getStatsOwnerTotal() {
    		global $globalArchiveMonths, $globalDBdriver;
    		if ($globalDBdriver == 'mysql') {
			$query = "SELECT SUM(cnt) as total FROM stats_owner";
		} else {
			$query = "SELECT SUM(cnt) as total FROM stats_owner";
                }
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        echo "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                return $all[0]['total'];
        }
	public function getStatsPilotTotal() {
    		global $globalArchiveMonths, $globalDBdriver;
    		if ($globalDBdriver == 'mysql') {
            		$query = "SELECT SUM(cnt) as total FROM stats_pilot";
            	} else {
            		$query = "SELECT SUM(cnt) as total FROM stats_pilot";
            	}
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        echo "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                return $all[0]['total'];
        }

	public function addStat($type,$cnt,$stats_date) {
		global $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			$query = "INSERT INTO stats (stats_type,cnt,stats_date) VALUES (:type,:cnt,:stats_date) ON DUPLICATE KEY UPDATE cnt = :cnt";
                } else {
			$query = "UPDATE stats SET cnt = :cnt WHERE stats_type = :type AND stats_date = :stats_date; INSERT INTO stats (stats_type,cnt,stats_date) SELECT :type,:cnt,:stats_date WHERE NOT EXISTS (SELECT 1 FROM stats WHERE  stats_type = :type AND stats_date = :stats_date);"; 
		}
                $query_values = array(':type' => $type,':cnt' => $cnt,':stats_date' => $stats_date);
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute($query_values);
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
        }
	public function updateStat($type,$cnt,$stats_date) {
		global $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			$query = "INSERT INTO stats (stats_type,cnt,stats_date) VALUES (:type,:cnt,:stats_date) ON DUPLICATE KEY UPDATE cnt = cnt+:cnt, stats_date = :date";
		} else {
            		//$query = "INSERT INTO stats (stats_type,cnt,stats_date) VALUES (:type,:cnt,:stats_date) ON DUPLICATE KEY UPDATE cnt = cnt+:cnt, stats_date = :date";
			$query = "UPDATE stats SET cnt = cnt+:cnt WHERE stats_type = :type AND stats_date = :stats_date; INSERT INTO stats (stats_type,cnt,stats_date) SELECT :type,:cnt,:stats_date WHERE NOT EXISTS (SELECT 1 FROM stats WHERE  stats_type = :type AND stats_date = :stats_date);"; 
                }
                $query_values = array(':type' => $type,':cnt' => $cnt,':stats_date' => $stats_date);
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute($query_values);
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
        }
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
	public function addStatFlight($type,$date_name,$cnt) {
                $query = "INSERT INTO stats_flight (stats_type,flight_date,cnt) VALUES (:type,:flight_date,:cnt)";
                $query_values = array(':type' => $type,':flight_date' => $date_name,':cnt' => $cnt);
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute($query_values);
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
        }
	public function addStatAircraftRegistration($registration,$cnt,$aircraft_icao = '') {
		global $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			$query = "INSERT INTO stats_registration (aircraft_icao,registration,cnt) VALUES (:aircraft_icao,:registration,:cnt) ON DUPLICATE KEY UPDATE cnt = cnt+:cnt";
		} else {
			$query = "UPDATE stats_registration SET cnt = cnt+:cnt WHERE registration = :registration; INSERT INTO stats_registration (aircraft_icao,registration,cnt) SELECT :aircraft_icao,:registration,:cnt WHERE NOT EXISTS (SELECT 1 FROM stats_registration WHERE registration = :registration);"; 
		}
                $query_values = array(':aircraft_icao' => $aircraft_icao,':registration' => $registration,':cnt' => $cnt);
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute($query_values);
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
        }
	public function addStatCallsign($callsign_icao,$cnt,$airline_icao = '') {
		global $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			$query = "INSERT INTO stats_callsign (callsign_icao,airline_icao,cnt) VALUES (:callsign_icao,:airline_icao,:cnt) ON DUPLICATE KEY UPDATE cnt = cnt+:cnt";
		} else {
			$query = "UPDATE stats_callsign SET cnt = cnt+:cnt WHERE callsign_icao = :callsign_icao; INSERT INTO stats_callsign (callsign_icao,airline_icao,cnt) SELECT :callsign_icao,:airline_icao,:cnt WHERE NOT EXISTS (SELECT 1 FROM stats_callsign WHERE callsign_icao = :callsign_icao);"; 
		}
                $query_values = array(':callsign_icao' => $callsign_icao,':airline_icao' => $airline_icao,':cnt' => $cnt);
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute($query_values);
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
        }
	public function addStatCountry($iso2,$iso3,$name,$cnt) {
		global $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			$query = "INSERT INTO stats_country (iso2,iso3,name,cnt) VALUES (:iso2,:iso3,:name,:cnt) ON DUPLICATE KEY UPDATE cnt = cnt+:cnt";
		} else {
			$query = "UPDATE stats_country SET cnt = cnt+:cnt WHERE iso2 = :iso2; INSERT INTO stats_country (iso2,iso3,name,cnt) SELECT :iso2,:iso3,:name,:cnt WHERE NOT EXISTS (SELECT 1 FROM stats_country WHERE iso2 = :iso2);"; 
		}
                $query_values = array(':iso2' => $iso2,':iso3' => $iso3,':name' => $name,':cnt' => $cnt);
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute($query_values);
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
        }
	public function addStatAircraft($aircraft_icao,$cnt,$aircraft_name = '') {
		global $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			$query = "INSERT INTO stats_aircraft (aircraft_icao,aircraft_name,cnt) VALUES (:aircraft_icao,:aircraft_name,:cnt) ON DUPLICATE KEY UPDATE cnt = cnt+:cnt";
		} else {
			$query = "UPDATE stats_aircraft SET cnt = cnt+:cnt WHERE aircraft_icao = :aircraft_icao; INSERT INTO stats_aircraft (aircraft_icao,aircraft_name,cnt) SELECT :aircraft_icao,:aircraft_name,:cnt WHERE NOT EXISTS (SELECT 1 FROM stats_aircraft WHERE aircraft_icao = :aircraft_icao);"; 
		}
                $query_values = array(':aircraft_icao' => $aircraft_icao,':aircraft_name' => $aircraft_name,':cnt' => $cnt);
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute($query_values);
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
        }
	public function addStatAirline($airline_icao,$cnt,$airline_name = '') {
		global $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			$query = "INSERT INTO stats_airline (airline_icao,airline_name,cnt) VALUES (:airline_icao,:airline_name,:cnt) ON DUPLICATE KEY UPDATE cnt = cnt+:cnt,airline_name = :airline_name";
		} else {
			$query = "UPDATE stats_airline SET cnt = cnt+:cnt WHERE airline_icao = :airline_icao; INSERT INTO stats_airline (airline_icao,airline_name,cnt) SELECT :airline_icao,:airline_name,:cnt WHERE NOT EXISTS (SELECT 1 FROM stats_airline WHERE airline_icao = :airline_icao);"; 
		}
                $query_values = array(':airline_icao' => $airline_icao,':airline_name' => $airline_name,':cnt' => $cnt);
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute($query_values);
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
        }
	public function addStatOwner($owner_name,$cnt) {
		global $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			$query = "INSERT INTO stats_owner (owner_name,cnt) VALUES (:owner_name,:cnt) ON DUPLICATE KEY UPDATE cnt = cnt+:cnt";
		} else {
			$query = "UPDATE stats_owner SET cnt = cnt+:cnt WHERE owner_name = :owner_name; INSERT INTO stats_owner (owner_name,cnt) SELECT :owner_name,:cnt WHERE NOT EXISTS (SELECT 1 FROM stats_owner WHERE owner_name = :owner_name);"; 
		}
                $query_values = array(':owner_name' => $owner_name,':cnt' => $cnt);
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute($query_values);
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
        }
	public function addStatPilot($pilot_id,$cnt) {
		global $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			$query = "INSERT INTO stats_pilot (pilot_id,cnt) VALUES (:pilot_id,:cnt) ON DUPLICATE KEY UPDATE cnt = cnt+:cnt";
		} else {
			$query = "UPDATE stats_pilot SET cnt = cnt+:cnt WHERE pilot_id = :pilot_id; INSERT INTO stats_pilot (pilot_id,cnt) SELECT :pilot_id,:cnt WHERE NOT EXISTS (SELECT 1 FROM stats_pilot WHERE pilot_id = :pilot_id);"; 
		}
                $query_values = array(':pilot_id' => $pilot_id,':cnt' => $cnt);
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute($query_values);
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
        }
	public function addStatDepartureAirports($airport_icao,$airport_name,$airport_city,$airport_country,$departure) {
		global $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			$query = "INSERT INTO stats_airport (airport_icao,airport_name,airport_city,airport_country,departure,stats_type,date) VALUES (:airport_icao,:airport_name,:airport_city,:airport_country,:departure,'yearly',:date) ON DUPLICATE KEY UPDATE departure = departure+:departure";
		} else {
			$query = "UPDATE stats_airport SET departure = departure+:departure WHERE airport_icao = :airport_icao AND stats_type = 'yearly'; INSERT INTO stats_airport (airport_icao,airport_name,airport_city,airport_country,departure,stats_type,date) SELECT :airport_icao,:airport_name,:airport_city,:airport_country,:departure,'yearly',:date WHERE NOT EXISTS (SELECT 1 FROM stats_airline WHERE airport_icao = :airport_icao AND stats_type = 'yearly');"; 
		}
                $query_values = array(':airport_icao' => $airport_icao,':airport_name' => $airport_name,':airport_city' => $airport_city,':airport_country' => $airport_country,':departure' => $departure,':date' => date('Y').'-01-01 00:00:00');
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute($query_values);
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
        }
	public function addStatDepartureAirportsDaily($date,$airport_icao,$airport_name,$airport_city,$airport_country,$departure) {
		global $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			$query = "INSERT INTO stats_airport (airport_icao,airport_name,airport_city,airport_country,departure,stats_type,date) VALUES (:airport_icao,:airport_name,:airport_city,:airport_country,:departure,'daily',:date) ON DUPLICATE KEY UPDATE departure = :departure";
		} else {
			$query = "UPDATE stats_airport SET departure = departure+:departure WHERE airport_icao = :airport_icao AND stats_type = 'daily'; INSERT INTO stats_airport (airport_icao,airport_name,airport_city,airport_country,departure,stats_type,date) SELECT :airport_icao,:airport_name,:airport_city,:airport_country,:departure,'daily',:date WHERE NOT EXISTS (SELECT 1 FROM stats_airline WHERE airport_icao = :airport_icao AND stats_type = 'daily');"; 
		}
                $query_values = array(':airport_icao' => $airport_icao,':airport_name' => $airport_name,':airport_city' => $airport_city,':airport_country' => $airport_country,':departure' => $departure,':date' => $date);
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute($query_values);
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
        }
	public function addStatArrivalAirports($airport_icao,$airport_name,$airport_city,$airport_country,$arrival) {
		global $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			$query = "INSERT INTO stats_airport (airport_icao,airport_name,airport_city,airport_country,arrival,stats_type,date) VALUES (:airport_icao,:airport_name,:airport_city,:airport_country,:arrival,'yearly',:date) ON DUPLICATE KEY UPDATE arrival = arrival+:arrival";
		} else {
			$query = "UPDATE stats_airport SET arrival = arrival+:arrival WHERE airport_icao = :airport_icao AND stats_type = 'yearly'; INSERT INTO stats_airport (airport_icao,airport_name,airport_city,airport_country,arrival,stats_type,date) SELECT :airport_icao,:airport_name,:airport_city,:airport_country,:arrival,'yearly',:date WHERE NOT EXISTS (SELECT 1 FROM stats_airline WHERE airport_icao = :airport_icao AND stats_type = 'yearly');"; 
		}
                $query_values = array(':airport_icao' => $airport_icao,':airport_name' => $airport_name,':airport_city' => $airport_city,':airport_country' => $airport_country,':arrival' => $arrival,':date' => date('Y').'-01-01 00:00:00');
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute($query_values);
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
        }
	public function addStatArrivalAirportsDaily($date,$airport_icao,$airport_name,$airport_city,$airport_country,$arrival) {
		global $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			$query = "INSERT INTO stats_airport (airport_icao,airport_name,airport_city,airport_country,arrival,stats_type,date) VALUES (:airport_icao,:airport_name,:airport_city,:airport_country,:arrival,'daily',:date) ON DUPLICATE KEY UPDATE arrival = :arrival";
		} else {
			$query = "UPDATE stats_airport SET arrival = arrival+:arrival WHERE airport_icao = :airport_icao AND stats_type = 'daily'; INSERT INTO stats_airport (airport_icao,airport_name,airport_city,airport_country,arrival,stats_type,date) SELECT :airport_icao,:airport_name,:airport_city,:airport_country,:arrival,'yearly',:date WHERE NOT EXISTS (SELECT 1 FROM stats_airline WHERE airport_icao = :airport_icao AND stats_type = 'daily');"; 
		}
                $query_values = array(':airport_icao' => $airport_icao,':airport_name' => $airport_name,':airport_city' => $airport_city,':airport_country' => $airport_country,':arrival' => $arrival, ':date' => $date);
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute($query_values);
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
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
    		global $globalArchiveMonths, $globalArchive, $globalArchiveYear, $globalDBdriver;
    		$Common = new Common();
    		$Connection = new Connection();
    		date_default_timezone_set('UTC');
    		$last_update = $this->getLastStatsUpdate('last_update_stats');
		//print_r($last_update);
		$flightsbymonth = $this->getStats('flights_by_month');
    		if (empty($last_update) && empty($flightsbymonth)) {
			// Initial update
			$Spotter = new Spotter($this->db);
			$alldata = $Spotter->countAllMonths();
			$lastyear = false;
			foreach ($alldata as $number) {
				if ($number['year_name'] != date('Y')) $lastyear = true;
				$this->addStat('flights_bymonth',$number['date_count'],date('Y-m-d H:i:s',mktime(0,0,0,$number['month_name'],1,$number['year_name'])));
			}
			$alldata = $Spotter->countAllMilitaryMonths();
			//$lastyear = false;
			foreach ($alldata as $number) {
				if ($number['year_name'] != date('Y')) $lastyear = true;
				$this->addStat('military_flights_bymonth',$number['date_count'],date('Y-m-d H:i:s',mktime(0,0,0,$number['month_name'],1,$number['year_name'])));
			}
			$alldata = $Spotter->countAllMonthsOwners();
			foreach ($alldata as $number) {
				$this->addStat('owners_bymonth',$number['date_count'],date('Y-m-d H:i:s',mktime(0,0,0,$number['month_name'],1,$number['year_name'])));
			}
			$alldata = $Spotter->countAllMonthsPilots();
			foreach ($alldata as $number) {
				$this->addStat('pilots_bymonth',$number['date_count'],date('Y-m-d H:i:s',mktime(0,0,0,$number['month_name'],1,$number['year_name'])));
			}
			$alldata = $Spotter->countAllMonthsAirlines();
			foreach ($alldata as $number) {
				$this->addStat('airlines_bymonth',$number['date_count'],date('Y-m-d H:i:s',mktime(0,0,0,$number['month_name'],1,$number['year_name'])));
			}
			$alldata = $Spotter->countAllMonthsAircrafts();
			foreach ($alldata as $number) {
				$this->addStat('aircrafts_bymonth',$number['date_count'],date('Y-m-d H:i:s',mktime(0,0,0,$number['month_name'],1,$number['year_name'])));
			}
			$alldata = $Spotter->countAllMonthsRealArrivals();
			foreach ($alldata as $number) {
				$this->addStat('realarrivals_bymonth',$number['date_count'],date('Y-m-d H:i:s',mktime(0,0,0,$number['month_name'],1,$number['year_name'])));
			}
			$this->deleteStatFlight('month');
			$alldata = $Spotter->countAllDatesLastMonth();
			foreach ($alldata as $number) {
				$this->addStatFlight('month',$number['date_name'],$number['date_count']);
			}
			$previousdata = $this->countAllDates();
			$this->deleteStatFlight('date');
			$alldata = $Common->array_merge_noappend($previousdata,$Spotter->countAllDates());
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
			$alldata = $Spotter->countAllHours('hour');
			foreach ($alldata as $number) {
				$this->addStatFlight('hour',$number['hour_name'],$number['hour_count']);
			}
			if ($lastyear) {
				$monthsSinceLastYear = date('n');
				$alldata = $Spotter->countAllAircraftTypes(false,$monthsSinceLastYear);
				foreach ($alldata as $number) {
					$this->addStatAircraft($number['aircraft_icao'],$number['aircraft_icao_count'],$number['aircraft_name']);
				}
				$alldata = $Spotter->countAllAirlines(false,$monthsSinceLastYear);
				foreach ($alldata as $number) {
					$this->addStatAirline($number['airline_icao'],$number['airline_count'],$number['airline_name']);
				}
				if ($Connection->tableExists('countries')) {
					$alldata = $Spotter->countAllFlightOverCountries(false,$monthsSinceLastYear);
					foreach ($alldata as $number) {
						$this->addStatCountry($number['flight_country_iso2'],$number['flight_country_iso3'],$number['flight_country'],$number['flight_count']);
					}
				}
				$alldata = $Spotter->countAllOwners(false,$monthsSinceLastYear);
				foreach ($alldata as $number) {
					$this->addStatOwner($number['owner_name'],$number['owner_count']);
				}
				$alldata = $Spotter->countAllPilots(false,$monthsSinceLastYear);
				foreach ($alldata as $number) {
					$this->addStatPilot($number['pilot_id'],$number['pilot_count']);
				}
				$previous_year = date('Y');
				$previous_year--;
				$this->addStat('aircrafts_byyear',$this->getStatsAircraftTotal(),$previous_year.'-01-01 00:00:00');
				$this->addStat('airlines_byyear',$this->getStatsAirlineTotal(),$previous_year.'-01-01 00:00:00');
				$this->addStat('owner_byyear',$this->getStatsOwnerTotal(),$previous_year.'-01-01 00:00:00');
				$this->addStat('pilot_byyear',$this->getStatsPilotTotal(),$previous_year.'-01-01 00:00:00');
				
				if (isset($globalArchiveYear) && $globalArchiveYear) {
					if ($globalArchive) {
						$query = "INSERT INTO spotter_archive_output SELECT * FROM spotter_output WHERE spotter_output.date < '".date('Y')."-01-01 00:00:00'";
						//echo $query;
						try {
							$sth = $this->db->prepare($query);
							$sth->execute();
						} catch(PDOException $e) {
							return "error : ".$e->getMessage().' - query : '.$query."\n";
						}
					}
					$query = "DELETE FROM spotter_output WHERE spotter_output.date < '".date('Y')."-01-01 00:00:00'";
					try {
						$sth = $this->db->prepare($query);
						$sth->execute();
					} catch(PDOException $e) {
						return "error : ".$e->getMessage().' - query : '.$query."\n";
					}
				}
			}
			if (!isset($globalArchiveMonths) || $globalArchiveMonths == '') $globalArchiveMonths = 2;
			if ($globalArchiveMonths > 0) {
				$alldata = $Spotter->countAllAircraftTypes(false,$globalArchiveMonths);
				foreach ($alldata as $number) {
					$this->addStatAircraft($number['aircraft_icao'],$number['aircraft_icao_count'],$number['aircraft_name']);
				}
				$alldata = $Spotter->countAllAirlines(false,$globalArchiveMonths);
				foreach ($alldata as $number) {
					$this->addStatAirline($number['airline_icao'],$number['airline_count'],$number['airline_name']);
				}
				$alldata = $Spotter->countAllAircraftRegistrations(false,$globalArchiveMonths);
				foreach ($alldata as $number) {
					$this->addStatAircraftRegistration($number['registration'],$number['aircraft_registration_count'],$number['aircraft_icao']);
				}
				$alldata = $Spotter->countAllCallsigns(false,$globalArchiveMonths);
				foreach ($alldata as $number) {
					$this->addStatCallsign($number['callsign_icao'],$number['callsign_icao_count'],$number['airline_icao']);
				}
				$alldata = $Spotter->countAllOwners(false,$globalArchiveMonths);
				foreach ($alldata as $number) {
					$this->addStatOwner($number['owner_name'],$number['owner_count']);
				}
				if ($Connection->tableExists('countries')) {
					$alldata = $Spotter->countAllFlightOverCountries(false,$globalArchiveMonths);
					foreach ($alldata as $number) {
						$this->addStatCountry($number['flight_country_iso2'],$number['flight_country_iso3'],$number['flight_country'],$number['flight_count']);
					}
				}
				$alldata = $Spotter->countAllPilots(false,$globalArchiveMonths);
				foreach ($alldata as $number) {
					$this->addStatPilot($number['pilot_id'],$number['pilot_count']);
				}
				$pall = $Spotter->countAllDepartureAirports(false,$globalArchiveMonths);
        			$dall = $Spotter->countAllDetectedDepartureAirports(false,$globalArchiveMonths);
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

				//print_r($alldate);
				foreach ($alldata as $number) {
					$this->addStatDepartureAirports($number['airport_departure_icao'],$number['airport_departure_name'],$number['airport_departure_city'],$number['airport_departure_country'],$number['airport_departure_icao_count']);
				}
				$pdata = $Spotter->countAllArrivalAirports(false,$globalArchiveMonths);
        			$dall = $Spotter->countAllDetectedArrivalAirports(false,$globalArchiveMonths);
	        		$alldata = array();
    				foreach ($pdata as $value) {
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
					$this->addStatArrivalAirports($number['airport_arrival_icao'],$number['airport_arrival_name'],$number['airport_arrival_city'],$number['airport_arrival_country'],$number['airport_arrival_icao_count']);
				}
				$this->addStat('aircrafts_byyear',$this->getStatsAircraftTotal(),date('Y').'-01-01 00:00:00');
				$this->addStat('airlines_byyear',$this->getStatsAirlineTotal(),date('Y').'-01-01 00:00:00');
				$this->addStat('owner_byyear',$this->getStatsOwnerTotal(),date('Y').'-01-01 00:00:00');
				$this->addStat('pilot_byyear',$this->getStatsPilotTotal(),date('Y').'-01-01 00:00:00');
			
				if ($globalArchive) {
					if ($globalDBdriver == 'mysql') {
						$query = "INSERT INTO spotter_archive_output SELECT * FROM spotter_output WHERE spotter_output.date < DATE_FORMAT(UTC_TIMESTAMP() - INTERVAL ".$globalArchiveMonths." MONTH, '%Y/%m/01')";
					} else {
						$query = "INSERT INTO spotter_archive_output SELECT * FROM spotter_output WHERE spotter_output.date < CAST(to_char(CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$globalArchiveMonths." MONTHS', 'YYYY/mm/01') AS TIMESTAMP)";
					}
					try {
						$sth = $this->db->prepare($query);
						$sth->execute();
					} catch(PDOException $e) {
						return "error : ".$e->getMessage().' - query : '.$query."\n";
					}
				}
	
				//$query = 'DELETE FROM spotter_output WHERE spotter_output.date < DATE_SUB(UTC_TIMESTAMP(), INTERVAL '.$globalArchiveMonths.' MONTH)';
				if ($globalDBdriver == 'mysql') {
					$query = "DELETE FROM spotter_output WHERE spotter_output.date < DATE_FORMAT(UTC_TIMESTAMP() - INTERVAL ".$globalArchiveMonths." MONTH, '%Y/%m/01')";
				} else {
					$query = "DELETE FROM spotter_output WHERE spotter_output.date < CAST(to_char(CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$globalArchiveMonths." MONTHS, 'YYYY/mm/01') AS TIMESTAMP)";
				}
				try {
					$sth = $this->db->prepare($query);
					$sth->execute();
				} catch(PDOException $e) {
					return "error : ".$e->getMessage().' - query : '.$query."\n";
				}
			}
			$this->addLastStatsUpdate('last_update_stats',date('Y-m-d G:i:s'));
		} else {
			echo 'Update stats !'."\n";
			if (isset($last_update[0]['value'])) {
				$last_update_day = $last_update[0]['value'];
			} else $last_update_day = '2012-12-12 12:12:12';
			$Spotter = new Spotter($this->db);
			$alldata = $Spotter->countAllAircraftTypes(false,0,$last_update_day);
			foreach ($alldata as $number) {
				$this->addStatAircraft($number['aircraft_icao'],$number['aircraft_icao_count'],$number['aircraft_name']);
			}
			$alldata = $Spotter->countAllAirlines(false,0,$last_update_day);
			foreach ($alldata as $number) {
				$this->addStatAirline($number['airline_icao'],$number['airline_count'],$number['airline_name']);
			}
			$alldata = $Spotter->countAllAircraftRegistrations(false,0,$last_update_day);
			foreach ($alldata as $number) {
				$this->addStatAircraftRegistration($number['registration'],$number['aircraft_registration_count'],$number['aircraft_icao']);
			}
			$alldata = $Spotter->countAllCallsigns(false,0,$last_update_day);
			foreach ($alldata as $number) {
				$this->addStatCallsign($number['callsign_icao'],$number['callsign_icao_count'],$number['airline_icao']);
			}
			$alldata = $Spotter->countAllOwners(false,0,$last_update_day);
			foreach ($alldata as $number) {
				$this->addStatOwner($number['owner_name'],$number['owner_count']);
			}
			$alldata = $Spotter->countAllPilots(false,0,$last_update_day);
			foreach ($alldata as $number) {
				$this->addStatPilot($number['pilot_id'],$number['pilot_count']);
			}
			$pall = $Spotter->countAllDepartureAirports(false,0,$last_update_day);
        		$dall = $Spotter->countAllDetectedDepartureAirports(false,0,$last_update_day);
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
				$this->addStatDepartureAirports($number['airport_departure_icao'],$number['airport_departure_name'],$number['airport_departure_city'],$number['airport_departure_country'],$number['airport_departure_icao_count']);
			}
			$pall = $Spotter->countAllArrivalAirports(false,0,$last_update_day);
        		$dall = $Spotter->countAllDetectedArrivalAirports(false,0,$last_update_day);
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
				$this->addStatArrivalAirports($number['airport_arrival_icao'],$number['airport_arrival_name'],$number['airport_arrival_city'],$number['airport_arrival_country'],$number['airport_arrival_icao_count']);
			}
			if ($Connection->tableExists('countries')) {
				$SpotterArchive = new SpotterArchive();
				$alldata = $SpotterArchive->countAllFlightOverCountries(false,0,$last_update_day);
				foreach ($alldata as $number) {
					$this->addStatCountry($number['flight_country_iso2'],$number['flight_country_iso3'],$number['flight_country'],$number['flight_count']);
				}
			}
			

			// Add by month using getstat if month finish...

			//if (date('m',strtotime($last_update_day)) != date('m')) {
			$Spotter = new Spotter($this->db);
			$alldata = $Spotter->countAllMonths();
			$lastyear = false;
			foreach ($alldata as $number) {
				if ($number['year_name'] != date('Y')) $lastyear = true;
				$this->addStat('flights_bymonth',$number['date_count'],date('Y-m-d H:i:s',mktime(0,0,0,$number['month_name'],1,$number['year_name'])));
			}
			$alldata = $Spotter->countAllMilitaryMonths();
			foreach ($alldata as $number) {
				$this->addStat('military_flights_bymonth',$number['date_count'],date('Y-m-d H:i:s',mktime(0,0,0,$number['month_name'],1,$number['year_name'])));
			}
			$alldata = $Spotter->countAllMonthsOwners();
			foreach ($alldata as $number) {
				$this->addStat('owners_bymonth',$number['date_count'],date('Y-m-d H:i:s',mktime(0,0,0,$number['month_name'],1,$number['year_name'])));
			}
			$alldata = $Spotter->countAllMonthsPilots();
			foreach ($alldata as $number) {
				$this->addStat('pilots_bymonth',$number['date_count'],date('Y-m-d H:i:s',mktime(0,0,0,$number['month_name'],1,$number['year_name'])));
			}
			$alldata = $Spotter->countAllMonthsAirlines();
			foreach ($alldata as $number) {
				$this->addStat('airlines_bymonth',$number['date_count'],date('Y-m-d H:i:s',mktime(0,0,0,$number['month_name'],1,$number['year_name'])));
			}
			$alldata = $Spotter->countAllMonthsAircrafts();
			foreach ($alldata as $number) {
				$this->addStat('aircrafts_bymonth',$number['date_count'],date('Y-m-d H:i:s',mktime(0,0,0,$number['month_name'],1,$number['year_name'])));
			}
			$alldata = $Spotter->countAllMonthsRealArrivals();
			foreach ($alldata as $number) {
				$this->addStat('realarrivals_bymonth',$number['date_count'],date('Y-m-d H:i:s',mktime(0,0,0,$number['month_name'],1,$number['year_name'])));
			}
			echo 'Airports data...'."\n";
			echo '...Departure'."\n";
			$this->deleteStatAirport('daily');
			$pall = $Spotter->getLast7DaysAirportsDeparture();
        		$dall = $Spotter->getLast7DaysDetectedAirportsDeparture();
	        	$alldata = array();
    			foreach ($pall as $value) {
	        		$icao = $value['departure_airport_icao'];
    				$alldata[$icao] = $value;
	        	}
	        	foreach ($dall as $value) {
    				$icao = $value['departure_airport_icao'];
        			if (isset($alldata[$icao])) {                                                           
        				$alldata[$icao]['departure_airport_count'] = $alldata[$icao]['departure_airport_count'] + $value['departure_airport_count'];
	        		} else $alldata[$icao] = $value;
    			}
        		$count = array();
        		foreach ($alldata as $key => $row) {
        			$count[$key] = $row['departure_airport_count'];
	        	}
    			array_multisort($count,SORT_DESC,$alldata);
			foreach ($alldata as $number) {
				$this->addStatDepartureAirportsDaily($number['date'],$number['departure_airport_icao'],$number['departure_airport_name'],$number['departure_airport_city'],$number['departure_airport_country'],$number['departure_airport_count']);
			}
			echo '...Arrival'."\n";
			$pall = $Spotter->getLast7DaysAirportsArrival();
        		$dall = $Spotter->getLast7DaysDetectedAirportsArrival();
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

			foreach ($alldata as $number) {
				$this->addStatArrivalAirportsDaily($number['date'],$number['arrival_airport_icao'],$number['arrival_airport_name'],$number['arrival_airport_city'],$number['arrival_airport_country'],$number['arrival_airport_count']);
			}

			echo 'Flights data...'."\n";
			$this->deleteStatFlight('month');
			echo '-> countAllDatesLastMonth...'."\n";
			$alldata = $Spotter->countAllDatesLastMonth();
			foreach ($alldata as $number) {
				$this->addStatFlight('month',$number['date_name'],$number['date_count']);
			}
			echo '-> countAllDates...'."\n";
			$previousdata = $this->countAllDates();
			$this->deleteStatFlight('date');
			$alldata = $Common->array_merge_noappend($previousdata,$Spotter->countAllDates());
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
			$alldata = $Spotter->countAllHours('hour');
			foreach ($alldata as $number) {
				$this->addStatFlight('hour',$number['hour_name'],$number['hour_count']);
			}
			if ($lastyear) {
				echo 'Data from last year...'."\n";
				// SUM all previous month to put as year
				$previous_year = date('Y');
				$previous_year--;
				$this->addStat('aircrafts_byyear',$this->getSumStats('aircrafts_bymonth',$previous_year),$previous_year.'-01-01 00:00:00');
				$this->addStat('airlines_byyear',$this->getSumStats('airlines_bymonth',$previous_year),$previous_year.'-01-01 00:00:00');
				$this->addStat('owner_byyear',$this->getSumStats('owner_bymonth',$previous_year),$previous_year.'-01-01 00:00:00');
				$this->addStat('pilot_byyear',$this->getSumStats('pilot_bymonth',$previous_year),$previous_year.'-01-01 00:00:00');
				
				if (isset($globalArchiveYear) && $globalArchiveYear) {
					if ($globalArchive) {
						$query = "INSERT INTO spotter_archive_output SELECT * FROM spotter_output WHERE spotter_output.date < '".date('Y')."-01-01 00:00:00'";
						try {
							$sth = $this->db->prepare($query);
							$sth->execute();
						} catch(PDOException $e) {
							return "error : ".$e->getMessage().' - query : '.$query."\n";
						}
					}
					echo 'Delete old data'."\n";
					$query = "DELETE FROM spotter_output WHERE spotter_output.date < '".date('Y')."-01-01 00:00:00'";
					try {
						$sth = $this->db->prepare($query);
						$sth->execute();
					} catch(PDOException $e) {
						return "error : ".$e->getMessage().' - query : '.$query."\n";
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
				}
				echo 'Deleting old data...'."\n";
				//$query = 'DELETE FROM spotter_output WHERE spotter_output.date < DATE_SUB(UTC_TIMESTAMP(), INTERVAL '.$globalArchiveMonths.' MONTH)';
				if ($globalDBdriver == 'mysql') {
					$query = "DELETE FROM spotter_output WHERE spotter_output.date < DATE_FORMAT(UTC_TIMESTAMP() - INTERVAL ".$globalArchiveMonths." MONTH, '%Y/%m/01')";
				} else {
					$query = "DELETE FROM spotter_output WHERE spotter_output.date < CAST(to_char(CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$globalArchiveMonths." MONTHS', 'YYYY/mm/01') AS TIMESTAMP)";
				}
				try {
					$sth = $this->db->prepare($query);
					$sth->execute();
				} catch(PDOException $e) {
					return "error : ".$e->getMessage();
				}
			}
			echo 'Insert last stats update date...'."\n";
			date_default_timezone_set('UTC');
			$this->addLastStatsUpdate('last_update_stats',date('Y-m-d G:i:s'));
		}
	}
}

?>