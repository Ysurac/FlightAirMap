<?php
/*
* This class save stats older than a year and $globalArchiveMonths
*/

require_once(dirname(__FILE__).'/class.Spotter.php');
require_once(dirname(__FILE__).'/class.Common.php');
class Stats {
	public $db;
        function __construct($dbc = null) {
		$Connection = new Connection($dbc);
		$this->db = $Connection->db;
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
                        return "error : ".$e->getMessage();
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
                        return "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                if (empty($all)) {
            	    $Spotter = new Spotter($this->db);
            	    $all = $Spotter->countAllAircraftTypes($limit);
                }
                return $all;
	}
	public function countAllAirlineCountries($limit = true) {
		if ($limit) $query = "SELECT airlines.country AS airline_country, stats_airline.cnt as airline_country_count FROM stats_airline,airlines WHERE stats_airline.airline_icao=airlines.icao LIMIT 0,10";
		else $query = "SELECT airlines.country AS airline_country, stats_airline.cnt as airline_country_count FROM stats_airline,airlines WHERE stats_airline.airline_icao=airlines.icao";
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                if (empty($all)) {
            		$Spotter = new Spotter($this->db);
            		$all = $Spotter->countAllAirlineCountries($limit);
                
                }
                return $all;
	}
	public function countAllAircraftManufacturers($limit = true) {
		if ($limit) $query = "SELECT aircraft.manufacturer AS aircraft_manufacturer, stats_aircraft.cnt as aircraft_manufacturer_count FROM stats_aircraft,aircraft WHERE stats_aircraft.aircraft_icao=aircraft.icao GROUP BY aircraft.manufacturer LIMIT 0,10";
		else $query = "SELECT aircraft.manufacturer AS aircraft_manufacturer, stats_aircraft.cnt as aircraft_manufacturer_count FROM stats_aircraft,aircraft WHERE stats_aircraft.aircraft_icao=aircraft.icao GROUP BY aircraft.manufacturer";
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                if (empty($all)) {
            		$Spotter = new Spotter($this->db);
			$all = $Spotter->countAllAircraftManufacturers($limit);
                }
                return $all;
	}

	public function countAllArrivalCountries($limit = true) {
		if ($limit) $query = "SELECT airport_country AS arrival_airport_country, arrival as airport_arrival_country_count FROM stats_airport WHERE type = 'yearly' LIMIT 0,10";
		else $query = "SELECT airport_country AS arrival_airport_country, arrival as airport_arrival_country_count FROM stats_airport WHERE type = 'yearly'";
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                if (empty($all)) {
	                $Spotter = new Spotter($this->db);
            		$all = $Spotter->countAllArrivalCountries($limit);
                }
                return $all;
	}
	public function countAllDepartureCountries($limit = true) {
		if ($limit) $query = "SELECT airport_country AS departure_airport_country, departure as airport_departure_country_count FROM stats_airport WHERE type = 'yearly' LIMIT 0,10";
		else $query = "SELECT airport_country AS departure_airport_country, departure as airport_departure_country_count FROM stats_airport WHERE type = 'yearly'";
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                if (empty($all)) {
		        $Spotter = new Spotter($this->db);
    	    	        $all = $Spotter->countAllDepartureCountries($limit);
                }
                return $all;
	}

	public function countAllAirlines($limit = true) {
		if ($limit) $query = "SELECT airline_icao, cnt AS airline_count, airline_name FROM stats_airline WHERE airline_name <> '' AND airline_icao <> '' ORDER BY airline_count DESC LIMIT 10 OFFSET 0";
		else $query = "SELECT airline_icao, cnt AS airline_count, airline_name FROM stats_airline WHERE airline_name <> '' AND airline_icao <> '' ORDER BY airline_count DESC";
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                if (empty($all)) {
	                $Spotter = new Spotter($this->db);
    		        $all = $Spotter->countAllAirlines($limit);
                }
                return $all;
	}
	public function countAllAircraftRegistrations($limit = true) {
		if ($limit) $query = "SELECT s.aircraft_icao, s.cnt AS aircraft_registration_count, a.type AS aircraft_name FROM stats_registration s, aircraft a WHERE s.registration <> '' AND a.icao = s.aircraft_icao ORDER BY aircraft_registration_count DESC LIMIT 10 OFFSET 0";
		else $query = "SELECT s.aircraft_icao, s.cnt AS aircraft_registration_count, a.type AS aircraft_name FROM stats_registration s, aircraft a WHERE s.registration <> '' AND a.icao = s.aircraft_icao ORDER BY aircraft_registration_count DESC";
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                if (empty($all)) {
	                $Spotter = new Spotter($this->db);
    		        $all = $Spotter->countAllAircraftRegistrations($limit);
                }
                return $all;
	}
	public function countAllCallsigns($limit = true) {
		if ($limit) $query = "SELECT s.callsign_icao, s.cnt AS callsign_icao_count, a.name AS airline_name FROM stats_callsign s, airlines a WHERE s.callsign_icao <> '' AND a.icao = s.airline_icao ORDER BY callsign_icao_count DESC LIMIT 10 OFFSET 0";
		else $query = "SELECT s.callsign_icao, s.cnt AS callsign_icao_count, a.name AS airline_name FROM stats_callsign s, airlines a WHERE s.callsign_icao <> '' AND a.icao = s.airline_icao ORDER BY callsign_icao_count DESC";
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                if (empty($all)) {
	                $Spotter = new Spotter($this->db);
    		        $all = $Spotter->countAllCallsigns($limit);
                }
                return $all;
	}
	public function countAllFlightOverCountries($limit = true) {
		if ($limit) $query = "SELECT iso3 as flight_country_iso3, iso2 as flight_country_iso2, name as flight_country, cnt as flight_count FROM stats_country ORDER BY flight_count DESC LIMIT 20 OFFSET 0";
		else $query = "SELECT iso3 as flight_country_iso3, iso2 as flight_country_iso2, name as flight_country, cnt as flight_count FROM stats_country ORDER BY flight_count DESC";
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                /*
                if (empty($all)) {
	                $Spotter = new Spotter($this->db);
    		        $all = $Spotter->countAllFlightOverCountries($limit);
                }
                */
                return $all;
	}
	public function countAllPilots($limit = true) {
		if ($limit) $query = "SELECT pilot_id, cnt AS pilot_count, pilot_name FROM stats_pilot ORDER BY pilot_count DESC LIMIT 0,10";
		else $query = "SELECT pilot_id, cnt AS pilot_count, pilot_name FROM stats_pilot ORDER BY pilot_count DESC";
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                if (empty($all)) {
            		$Spotter = new Spotter($this->db);
            		$all = $Spotter->countAllPilots($limit);
                }
                return $all;
	}
	public function countAllOwners($limit = true) {
		if ($limit) $query = "SELECT owner_name, cnt AS owner_count FROM stats_owner ORDER BY owner_count DESC LIMIT 0,10";
		else $query = "SELECT owner_name, cnt AS owner_count FROM stats_owner ORDER BY owner_count DESC";
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                if (empty($all)) {
            		$Spotter = new Spotter($this->db);
            		$all = $Spotter->countAllOwners($limit);
                }
                return $all;
	}
	public function countAllDepartureAirports($limit = true) {
		if ($limit) $query = "SELECT airport_icao AS airport_departure_icao,airport_city AS airport_departure_city,airport_country AS airport_departure_country,departure AS airport_departure_icao_count FROM stats_airport WHERE type = 'yearly' LIMIT 0,10";
		else $query = "SELECT airport_icao AS airport_departure_icao,airport_city AS airport_departure_city,airport_country AS airport_departure_country,departure AS airport_departure_icao_count FROM stats_airport WHERE type = 'yearly'";
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                if (empty($all)) {
            		$Spotter = new Spotter($this->db);
            		$all = $Spotter->countAllDepartureAirports($limit);
                }
                return $all;
	}
	public function countAllArrivalAirports($limit = true) {
		if ($limit) $query = "SELECT airport_icao AS airport_arrival_icao,airport_city AS airport_arrival_city,airport_country AS airport_arrival_country,arrival AS airport_arrival_icao_count FROM stats_airport WHERE type = 'yearly' LIMIT 0,10";
		else $query = "SELECT airport_icao AS airport_arrival_icao,airport_city AS airport_arrival_city,airport_country AS airport_arrival_country,arrival AS airport_arrival_icao_count FROM stats_airport WHERE type = 'yearly'";
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                if (empty($all)) {
            		$Spotter = new Spotter($this->db);
        		$all = $Spotter->countAllArrivalAirports($limit);
                }
                return $all;
	}
	public function countAllMonthsLastYear($limit = true) {
		global $globalTimezone;
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime();
			$offset = $datetime->format('P');
		} else $offset = '+00:00';
		if ($limit) $query = "SELECT MONTH(CONVERT_TZ(stats_date,'+00:00',:offset)) as month_name, YEAR(CONVERT_TZ(stats_date,'+00:00',:offset)) as year_name, cnt as date_count FROM stats WHERE type = 'flights_bymonth' AND stats_date >= DATE_SUB(UTC_TIMESTAMP(),INTERVAL 12 MONTH)";
		else $query = "SELECT MONTH(CONVERT_TZ(stats_date,'+00:00',:offset)) as month_name, YEAR(CONVERT_TZ(stats_date,'+00:00',:offset)) as year_name, cnt as date_count FROM stats WHERE type = 'flights_bymonth'";
		$query_data = array(':offset' => $offset);
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute($query_data);
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                if (empty($all)) {
            		$Spotter = new Spotter($this->db);
            		$all = $Spotter->countAllMonthsLastYear($limit);
                }
                return $all;
	}
	
	public function countAllDatesLastMonth() {
		$query = "SELECT flight_date as date_name, cnt as date_count FROM stats_flight WHERE type = 'month'";
		$query_data = array();
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute($query_data);
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                if (empty($all)) {
            		$Spotter = new Spotter($this->db);
            		$all = $Spotter->countAllDatesLastMonth();
                }
                return $all;
	}
	public function countAllDatesLast7Days() {
		$query = "SELECT flight_date as date_name, cnt as date_count FROM stats_flight WHERE type = 'month' AND flight_date >= DATE_SUB(UTC_TIMESTAMP(),INTERVAL 7 DAY)";
		$query_data = array();
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute($query_data);
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                if (empty($all)) {
            		$Spotter = new Spotter($this->db);
            		$all = $Spotter->countAllDatesLast7Days();
                }
                return $all;
	}
	public function countAllDates() {
		$query = "SELECT flight_date as date_name, cnt as date_count FROM stats_flight WHERE type = 'date'";
		$query_data = array();
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute($query_data);
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                if (empty($all)) {
            		$Spotter = new Spotter($this->db);
            		$all = $Spotter->countAllDates();
                }
                return $all;
	}
	public function countAllMonths() {
		global $globalTimezone;
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime();
			$offset = $datetime->format('P');
		} else $offset = '+00:00';
	    	$query = "SELECT YEAR(CONVERT_TZ(stats_date,'+00:00', :offset)) AS year_name,MONTH(CONVERT_TZ(stats_date,'+00:00', :offset)) AS month_name, cnt as date_count FROM stats WHERE type = 'flights_bymonth'";
		$query_data = array(':offset' => $offset);
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute($query_data);
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                if (empty($all)) {
            		$Spotter = new Spotter($this->db);
            		$all = $Spotter->countAllMonths();
                }
                return $all;
	}
	public function countAllMilitaryMonths() {
		global $globalTimezone;
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime();
			$offset = $datetime->format('P');
		} else $offset = '+00:00';
	    	$query = "SELECT YEAR(CONVERT_TZ(stats_date,'+00:00', :offset)) AS year_name,MONTH(CONVERT_TZ(stats_date,'+00:00', :offset)) AS month_name, cnt as date_count FROM stats WHERE type = 'military_flights_bymonth'";
		$query_data = array(':offset' => $offset);
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute($query_data);
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                if (empty($all)) {
            		$Spotter = new Spotter($this->db);
            		$all = $Spotter->countAllMilitaryMonths();
                }
                return $all;
	}
	public function countAllHours($orderby = 'hour',$limit = true) {
		global $globalTimezone;
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime();
			$offset = $datetime->format('P');
		} else $offset = '+00:00';
		if ($limit) $query = "SELECT flight_date as hour_name, cnt as hour_count FROM stats_flight WHERE type = 'hour'";
		else $query = "SELECT flight_date as hour_name, cnt as hour_count FROM stats_flight WHERE type = 'hour'";
		if ($orderby == 'hour') $query .= " ORDER BY CAST(hour_name AS integer) ASC";
		if ($orderby == 'count') $query .= " ORDER BY hour_count DESC";
		$query_data = array(':offset' => $offset);
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute($query_data);
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                if (empty($all)) {
            		$Spotter = new Spotter($this->db);
            		$all = $Spotter->countAllHours('hour',$limit);
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
		$all = $this->getSumStats('airlines_bymonth',date('Y'));
		if (empty($all)) {
			$Spotter = new Spotter($this->db);
			$all = $Spotter->countOverallAirlines();
		}
		return $all;
	}
	public function countOverallOwners() {
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
		$query = "SELECT * FROM stats_airport WHERE type = 'daily' AND airport_icao = :airport_icao ORDER BY date";
		$query_values = array(':airport_icao' => $airport_icao);
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute($query_values);
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                return $all;
	}
	public function getStats($type) {
                $query = "SELECT * FROM stats WHERE type = :type ORDER BY stat_date";
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
	public function getSumStats($type,$year) {
    		global $globalArchiveMonths;
                $query = "SELECT SUM(cnt) as total FROM stats WHERE type = :type AND YEAR(stats_date) = :year";
                $query_values = array(':type' => $type, ':year' => $year);
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute($query_values);
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                return $all[0]['total'];
        }
	public function getStatsTotal($type) {
    		global $globalArchiveMonths;
                $query = "SELECT SUM(cnt) as total FROM stats WHERE type = :type AND stats_date < DATE_SUB(UTC_TIMESTAMP(), INTERVAL ".$globalArchiveMonths." MONTH)";
                $query_values = array(':type' => $type);
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute($query_values);
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                return $all[0]['total'];
        }
	public function getStatsAircraftTotal() {
    		global $globalArchiveMonths;
                $query = "SELECT SUM(cnt) as total FROM stats_aircraft AND stats_date < DATE_SUB(UTC_TIMESTAMP(), INTERVAL ".$globalArchiveMonths." MONTH)";
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                return $all[0]['total'];
        }
	public function getStatsAirlineTotal() {
    		global $globalArchiveMonths;
                $query = "SELECT SUM(cnt) as total FROM stats_airline AND stats_date < DATE_SUB(UTC_TIMESTAMP(), INTERVAL ".$globalArchiveMonths." MONTH)";
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                return $all[0]['total'];
        }
	public function getStatsOwnerTotal() {
    		global $globalArchiveMonths;
                $query = "SELECT SUM(cnt) as total FROM stats_owner AND stats_date < DATE_SUB(UTC_TIMESTAMP(), INTERVAL ".$globalArchiveMonths." MONTH)";
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                return $all[0]['total'];
        }
	public function getStatsPilotTotal() {
    		global $globalArchiveMonths;
                $query = "SELECT SUM(cnt) as total FROM stats_pilot AND stats_date < DATE_SUB(UTC_TIMESTAMP(), INTERVAL ".$globalArchiveMonths." MONTH)";
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                return $all[0]['total'];
        }

	public function addStat($type,$cnt,$stats_date) {
                $query = "INSERT INTO stats (type,cnt,stats_date) VALUES (:type,:cnt,:stats_date) ON DUPLICATE KEY UPDATE cnt = :cnt";
                $query_values = array(':type' => $type,':cnt' => $cnt,':stats_date' => $stats_date);
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute($query_values);
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
        }
	public function updateStat($type,$cnt,$stats_date) {
                $query = "INSERT INTO stats (type,cnt,stats_date) VALUES (:type,:cnt,:stats_date) ON DUPLICATE KEY UPDATE cnt = cnt+:cnt, stats_date = :date";
                $query_values = array(':type' => $type,':cnt' => $cnt,':stats_date' => $stats_date);
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute($query_values);
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
        }
	public function addStatFlight($type,$date_name,$cnt) {
                $query = "INSERT INTO stats_flight (type,flight_date,cnt) VALUES (:type,:flight_date,:cnt)";
                $query_values = array(':type' => $type,':flight_date' => $date_name,':cnt' => $cnt);
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute($query_values);
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
        }
	public function addStatAircraftRegistration($registration,$cnt,$aircraft_icao = '') {
                $query = "INSERT INTO stats_registration (aircraft_icao,registration,cnt) VALUES (:aircraft_icao,:registration,:cnt) ON DUPLICATE KEY UPDATE cnt = cnt+:cnt";
                $query_values = array(':aircraft_icao' => $aircraft_icao,':registration' => $registration,':cnt' => $cnt);
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute($query_values);
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
        }
	public function addStatCallsign($callsign_icao,$cnt,$airline_icao = '') {
                $query = "INSERT INTO stats_callsign (callsign_icao,airline_icao,cnt) VALUES (:callsign_icao,:airline_icao,:cnt) ON DUPLICATE KEY UPDATE cnt = cnt+:cnt";
                $query_values = array(':callsign_icao' => $callsign_icao,':airline_icao' => $airline_icao,':cnt' => $cnt);
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute($query_values);
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
        }
	public function addStatCountry($iso2,$iso3,$name,$cnt) {
                $query = "INSERT INTO stats_country (iso2,iso3,name,cnt) VALUES (:iso2,:iso3,:name,:cnt) ON DUPLICATE KEY UPDATE cnt = cnt+:cnt";
                $query_values = array(':iso2' => $iso2,':iso3' => $iso3,':name' => $name,':cnt' => $cnt);
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute($query_values);
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
        }
	public function addStatAircraft($aircraft_icao,$cnt,$aircraft_name = '') {
                $query = "INSERT INTO stats_aircraft (aircraft_icao,aircraft_name,cnt) VALUES (:aircraft_icao,:aircraft_name,:cnt) ON DUPLICATE KEY UPDATE cnt = cnt+:cnt";
                $query_values = array(':aircraft_icao' => $aircraft_icao,':aircraft_name' => $aircraft_name,':cnt' => $cnt);
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute($query_values);
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
        }
	public function addStatAirline($airline_icao,$cnt,$airline_name = '') {
                $query = "INSERT INTO stats_airline (airline_icao,airline_name,cnt) VALUES (:airline_icao,:airline_name,:cnt) ON DUPLICATE KEY UPDATE cnt = cnt+:cnt,airline_name = :airline_name";
                $query_values = array(':airline_icao' => $airline_icao,':airline_name' => $airline_name,':cnt' => $cnt);
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute($query_values);
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
        }
	public function addStatOwner($owner_name,$cnt) {
                $query = "INSERT INTO stats_owner (owner_name,cnt) VALUES (:owner_name,:cnt) ON DUPLICATE KEY UPDATE cnt = cnt+:cnt";
                $query_values = array(':owner_name' => $owner_name,':cnt' => $cnt);
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute($query_values);
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
        }
	public function addStatPilot($pilot_id,$cnt) {
                $query = "INSERT INTO stats_pilot (pilot_id,cnt) VALUES (:owner_name,:cnt) ON DUPLICATE KEY UPDATE cnt = cnt+:cnt";
                $query_values = array(':pilot_id' => $pilot_id,':cnt' => $cnt);
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute($query_values);
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
        }
	public function addStatDepartureAirports($airport_icao,$airport_name,$airport_city,$airport_country,$departure) {
                $query = "INSERT INTO stats_airport (airport_icao,airport_name,airport_city,airport_country,departure,type) VALUES (:airport_icao,:airport_name,:airport_city,:airport_country,:departure,'yearly') ON DUPLICATE KEY UPDATE departure = departure+:departure";
                $query_values = array(':airport_icao' => $airport_icao,':airport_name' => $airport_name,':airport_city' => $airport_city,':airport_country' => $airport_country,':departure' => $departure);
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute($query_values);
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
        }
	public function addStatDepartureAirportsDaily($date,$airport_icao,$airport_name,$airport_city,$airport_country,$departure) {
                $query = "INSERT INTO stats_airport (airport_icao,airport_name,airport_city,airport_country,departure,type,date) VALUES (:airport_icao,:airport_name,:airport_city,:airport_country,:departure,'daily',:date) ON DUPLICATE KEY UPDATE departure = :departure";
                $query_values = array(':airport_icao' => $airport_icao,':airport_name' => $airport_name,':airport_city' => $airport_city,':airport_country' => $airport_country,':departure' => $departure,':date' => $date);
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute($query_values);
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
        }
	public function addStatArrivalAirports($airport_icao,$airport_name,$airport_city,$airport_country,$arrival) {
                $query = "INSERT INTO stats_airport (airport_icao,airport_name,airport_city,airport_country,arrival,type) VALUES (:airport_icao,:airport_name,:airport_city,:airport_country,:arrival,'yearly') ON DUPLICATE KEY UPDATE arrival = arrival+:arrival";
                $query_values = array(':airport_icao' => $airport_icao,':airport_name' => $airport_name,':airport_city' => $airport_city,':airport_country' => $airport_country,':arrival' => $arrival);
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute($query_values);
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
        }
	public function addStatArrivalAirportsDaily($date,$airport_icao,$airport_name,$airport_city,$airport_country,$arrival) {
                $query = "INSERT INTO stats_airport (airport_icao,airport_name,airport_city,airport_country,arrival,type,date) VALUES (:airport_icao,:airport_name,:airport_city,:airport_country,:arrival,'daily',:date) ON DUPLICATE KEY UPDATE arrival = :arrival";
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
                $query = "DELETE FROM stats_flight WHERE type = :type";
                $query_values = array(':type' => $type);
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute($query_values);
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
        }
	public function deleteStatAirport($type) {
                $query = "DELETE FROM stats_airport WHERE type = :type";
                $query_values = array(':type' => $type);
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute($query_values);
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
        }
        
        public function addOldStats() {
    		global $globalArchiveMonths, $globalArchive, $globalArchiveYear;
    		$Common = new Common();
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
			$lastyear = false;
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
				$alldata = $Spotter->countAllFlightOverCountries(false,$monthsSinceLastYear);
				foreach ($alldata as $number) {
					$this->addStatCountry($number['flight_country_iso2'],$number['flight_country_iso3'],$number['flight_country'],$number['flight_count']);
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
							return "error : ".$e->getMessage();
						}
					}
					$query = 'DELETE FROM spotter_output WHERE spotter_output.date < '.date('Y').'-01-01 00:00:00';
					try {
						$sth = $this->db->prepare($query);
						$sth->execute();
					} catch(PDOException $e) {
						return "error : ".$e->getMessage();
					}
				}
			}
			if (!isset($globalArchiveMonths) || $globalArchiveMonths == '') $globalArchiveMonths = 2;
			if ($globalArchiveMonths > 0) {
				$alldata = $Spotter->countAllAircraftTypes(false,$globalArchiveMonths);
				foreach ($alldata as $number) {
					$this->addStatAircraft($number['aircraft_icao'],$number['aircraft_icao_count']);
				}
				$alldata = $Spotter->countAllAirlines(false,$globalArchiveMonths);
				foreach ($alldata as $number) {
					$this->addStatAirline($number['airline_icao'],$number['airline_count'],$number['airline_name']);
				}
				$alldata = $Spotter->countAllAircraftRegistration(false,$globalArchiveMonths);
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
				$alldata = $Spotter->countAllFlightOverCountries(false,$globalArchiveMonths);
				foreach ($alldata as $number) {
					$this->addStatCountry($number['flight_country_iso2'],$number['flight_country_iso3'],$number['flight_country'],$number['flight_count']);
				}
				$alldata = $Spotter->countAllPilots(false,$globalArchiveMonths);
				foreach ($alldata as $number) {
					$this->addStatPilot($number['pilot_id'],$number['pilot_count']);
				}
				$alldata = $Spotter->countAllDepartureAirports(false,$globalArchiveMonths);
				//print_r($alldate);
				foreach ($alldata as $number) {
					$this->addStatDepartureAirports($number['airport_departure_icao'],$number['airport_departure_name'],$number['airport_departure_city'],$number['airport_departure_country'],$number['airport_departure_icao_count']);
				}
				$alldata = $Spotter->countAllArrivalAirports(false,$globalArchiveMonths);
				foreach ($alldata as $number) {
					$this->addStatArrivalAirports($number['airport_arrival_icao'],$number['airport_arrival_name'],$number['airport_arrival_city'],$number['airport_arrival_country'],$number['airport_arrival_icao_count']);
				}
				$this->addStat('aircrafts_byyear',$this->getStatsAircraftTotal(),date('Y').'-01-01 00:00:00');
				$this->addStat('airlines_byyear',$this->getStatsAirlineTotal(),date('Y').'-01-01 00:00:00');
				$this->addStat('owner_byyear',$this->getStatsOwnerTotal(),date('Y').'-01-01 00:00:00');
				$this->addStat('pilot_byyear',$this->getStatsPilotTotal(),date('Y').'-01-01 00:00:00');
			
				if ($globalArchive) {
					$query = "INSERT INTO spotter_archive_output SELECT * FROM spotter_output WHERE spotter_output.date < DATE_FORMAT(UTC_TIMESTAMP() - INTERVAL ".$globalArchiveMonths." MONTH, '%Y/%m/01')";
					try {
						$sth = $this->db->prepare($query);
						$sth->execute();
					} catch(PDOException $e) {
						return "error : ".$e->getMessage();
					}
				}
	
				//$query = 'DELETE FROM spotter_output WHERE spotter_output.date < DATE_SUB(UTC_TIMESTAMP(), INTERVAL '.$globalArchiveMonths.' MONTH)';
				$query = "DELETE FROM spotter_output WHERE spotter_output.date < DATE_FORMAT(UTC_TIMESTAMP() - INTERVAL ".$globalArchiveMonths." MONTH, '%Y/%m/01')";
				try {
					$sth = $this->db->prepare($query);
					$sth->execute();
				} catch(PDOException $e) {
					return "error : ".$e->getMessage();
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
				$this->addStatAircraft($number['aircraft_icao'],$number['aircraft_icao_count']);
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
			$alldata = $Spotter->countAllDepartureAirports(false,0,$last_update_day);
			foreach ($alldata as $number) {
				$this->addStatDepartureAirports($number['airport_departure_icao'],$number['airport_departure_name'],$number['airport_departure_city'],$number['airport_departure_country'],$number['airport_departure_icao_count']);
			}
			$alldata = $Spotter->countAllArrivalAirports(false,0,$last_update_day);
			foreach ($alldata as $number) {
				$this->addStatArrivalAirports($number['airport_arrival_icao'],$number['airport_arrival_name'],$number['airport_arrival_city'],$number['airport_arrival_country'],$number['airport_arrival_icao_count']);
			}
			$alldata = $Spotter->countAllFlightOverCountries(false,0,$last_update_day);
			foreach ($alldata as $number) {
				$this->addStatCountry($number['flight_country_iso2'],$number['flight_country_iso3'],$number['flight_country'],$number['flight_count']);
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
			$this->deleteStatAirport('daily');
			$alldata = $Spotter->getLast7DaysAirportsDeparture();
			foreach ($alldata as $number) {
				$this->addStatDepartureAirportsDaily($number['date'],$number['departure_airport_icao'],$number['departure_airport_name'],$number['departure_airport_city'],$number['departure_airport_country'],$number['departure_airport_count']);
			}
			$alldata = $Spotter->getLast7DaysAirportsArrival();
			foreach ($alldata as $number) {
				$this->addStatArrivalAirportsDaily($number['date'],$number['arrival_airport_icao'],$number['arrival_airport_name'],$number['arrival_airport_city'],$number['arrival_airport_country'],$number['arrival_airport_count']);
			}

			echo 'Flights data...'."\n";
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
							return "error : ".$e->getMessage();
						}
					}
					echo 'Delete old data'."\n";
					$query = "DELETE FROM spotter_output WHERE spotter_output.date < '".date('Y')."-01-01 00:00:00'";
					try {
						$sth = $this->db->prepare($query);
						$sth->execute();
					} catch(PDOException $e) {
						return "error : ".$e->getMessage();
					}
				}
			}
			if ($globalArchiveMonths > 0) {
				if ($globalArchive) {
					$query = "INSERT INTO spotter_archive_output SELECT * FROM spotter_output WHERE spotter_output.date < DATE_FORMAT(UTC_TIMESTAMP() - INTERVAL ".$globalArchiveMonths." MONTH, '%Y/%m/01')";
					try {
						$sth = $this->db->prepare($query);
						$sth->execute();
					} catch(PDOException $e) {
						return "error : ".$e->getMessage();
					}
				}
				echo 'Deleting old data...'."\n";
				//$query = 'DELETE FROM spotter_output WHERE spotter_output.date < DATE_SUB(UTC_TIMESTAMP(), INTERVAL '.$globalArchiveMonths.' MONTH)';
				$query = "DELETE FROM spotter_output WHERE spotter_output.date < DATE_FORMAT(UTC_TIMESTAMP() - INTERVAL ".$globalArchiveMonths." MONTH, '%Y/%m/01')";
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