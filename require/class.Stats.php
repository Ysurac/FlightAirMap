<?php
/*
* This class save stats older than a year and $globalArchiveMonths
*/

require_once('class.Spotter.php');
class Stats {
	public $db;
        function __construct($dbc = null) {
    		if ($dbc === null) {
			$Connection = new Connection();
			$this->db = $Connection->db;
		} else $this->db = $dbc;
        }

	public function addLastStatsUpdate($stats_date) {
                $query = "INSERT INTO config (name,value) VALUES ('last_update_stats',:stats_date) ON DUPLICATE KEY UPDATE value = :stats_date";
                $query_values = array(':stats_date' => $stats_date);
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute($query_values);
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
        }

	public function getLastStatsUpdate() {
                $query = "SELECT value FROM config WHERE name = 'last_update_stats'";
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                return $all;
        }


	public function countAllAircraftTypes($limit = true) {
		$query = "SELECT aircraft_icao, cnt AS aircraft_icao_count, aircraft_name FROM stats_aircraft WHERE aircraft_name <> '' AND aircraft_icao <> ''";
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                $Spotter = new Spotter();
                $spotterall = $Spotter->countAllAircraftTypes(false);
                $all_result = array_merge($all,$spotterall);
                $values = array();
                foreach ($all_result as $cnt) {
                    $values[] = $cnt['aircraft_icao_count'];
                }
                array_multisort($values,SORT_DESC,$all_result);
                if ($limit) array_splice($all_result,11);
                return $all_result;
	}
	public function countAllAirlineCountries($limit = true) {
		$query = "SELECT airlines.country AS airline_country, stats_airline.cnt as airline_country_count FROM stats_airline,airlines WHERE stats_airline.airline_icao=airlines.icao";
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                $Spotter = new Spotter();
                $all_result = array_merge($all,$Spotter->countAllAirlineCountries(false));
                $values = array();
                foreach ($all_result as $cnt) {
                    $values[] = $cnt['airline_country_count'];
                }
                array_multisort($values,SORT_DESC,$all_result);
                if ($limit) array_splice($all_result,11);
                return $all_result;
	}
	public function countAllAircraftManufacturers($limit = true) {
		$query = "SELECT aircraft.manufacturer AS aircraft_manufacturer, stats_aircraft.cnt as aircraft_manufacturer_count FROM stats_aircraft,aircraft WHERE stats_aircraft.aircraft_icao=aircraft.icao GROUP BY aircraft.manufacturer";
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                $Spotter = new Spotter();
                $all_result = array_merge($all,$Spotter->countAllAircraftManufacturers(false));
                $values = array();
                foreach ($all_result as $cnt) {
                    $values[] = $cnt['aircraft_manufacturer_count'];
                }
                array_multisort($values,SORT_DESC,$all_result);
                if ($limit) array_splice($all_result,11);
                return $all_result;
	}

	public function countAllArrivalCountries($limit = true) {
		$query = "SELECT airport_country AS arrival_airport_country, arrival as airport_arrival_country_count FROM stats_airport";
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                $Spotter = new Spotter();
                $all_result = array_merge($all,$Spotter->countAllArrivalCountries(false));
                $values = array();
                foreach ($all_result as $cnt) {
                    $values[] = $cnt['airport_arrival_country_count'];
                }
                array_multisort($values,SORT_DESC,$all_result);
                if ($limit) array_splice($all_result,11);
                return $all_result;
	}
	public function countAllDepartureCountries($limit = true) {
		$query = "SELECT airport_country AS departure_airport_country, departure as airport_departure_country_count FROM stats_airport";
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                $Spotter = new Spotter();
                $all_result = array_merge($all,$Spotter->countAllDepartureCountries(false));
                $values = array();
                foreach ($all_result as $cnt) {
                    $values[] = $cnt['airport_departure_country_count'];
                }
                array_multisort($values,SORT_DESC,$all_result);
                if ($limit) array_splice($all_result,11);
                return $all_result;
	}

	public function countAllAirlines($limit = true) {
		$query = "SELECT airline_icao, cnt AS airline_count, airline_name FROM stats_airline WHERE airline_name <> '' AND airline_icao <> ''";
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                $Spotter = new Spotter();
                $all_result = array_merge($all,$Spotter->countAllAirlines(false));
                $values = array();
                foreach ($all_result as $cnt) {
                    $values[] = $cnt['airline_count'];
                }
                array_multisort($values,SORT_DESC,$all_result);
                if ($limit) array_splice($all_result,11);
                return $all_result;
	}
	public function countAllPilots($limit = true) {
		$query = "SELECT pilot_id, cnt AS pilot_count, pilot_name FROM stats_pilot";
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                $Spotter = new Spotter();
                $all_result = array_merge($all,$Spotter->countAllPilots(false));
                $values = array();
                foreach ($all_result as $cnt) {
                    $values[] = $cnt['pilot_count'];
                }
                array_multisort($values,SORT_DESC,$all_result);
                if ($limit) array_splice($all_result,11);
                return $all_result;
	}
	public function countAllOwners($limit = true) {
		$query = "SELECT owner_name, cnt AS owner_count FROM stats_owner";
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                $Spotter = new Spotter();
                $all_result = array_merge($all,$Spotter->countAllOwners(false));
                $values = array();
                foreach ($all_result as $cnt) {
                    $values[] = $cnt['owner_count'];
                }
                array_multisort($values,SORT_DESC,$all_result);
                if ($limit) array_splice($all_result,11);
                return $all_result;
	}
	public function countAllDepartureAirports($limit = true) {
		$query = "SELECT airport_icao AS airport_departure_icao,airport_city AS airport_departure_city,airport_country AS airport_departure_country,departure AS airport_departure_icao_count FROM stats_airport";
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                $Spotter = new Spotter();
                $all_result = array_merge($all,$Spotter->countAllDepartureAirports(false));
                $values = array();
                foreach ($all_result as $cnt) {
                    $values[] = $cnt['airport_departure_icao_count'];
                }
                array_multisort($values,SORT_DESC,$all_result);
                if ($limit) array_splice($all_result,11);
                return $all_result;
	}
	public function countAllArrivalAirports($limit = true) {
		$query = "SELECT airport_icao AS airport_arrival_icao,airport_city AS airport_arrival_city,airport_country AS airport_arrival_country,arrival AS airport_arrival_icao_count FROM stats_airport";
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute();
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                $Spotter = new Spotter();
                $all_result = array_merge($all,$Spotter->countAllArrivalAirports(false));
                $values = array();
                foreach ($all_result as $cnt) {
                    $values[] = $cnt['airport_arrival_icao_count'];
                }
                array_multisort($values,SORT_DESC,$all_result);
                if ($limit) array_splice($all_result,11);
                return $all_result;
	}
	public function countAllMonthsLastYear($limit = true) {
		global $globalTimezone;
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime();
			$offset = $datetime->format('P');
		} else $offset = '+00:00';
		$query = "SELECT MONTH(CONVERT_TZ(stats_date,'+00:00',:offset)) as month_name, YEAR(CONVERT_TZ(stats_date,'+00:00',:offset)) as year_name, cnt as date_count FROM stats WHERE type = 'flights_by_month'";
		$query_data = array(':offset' => $offset);
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute($query_data);
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
                $all = $sth->fetchAll(PDO::FETCH_ASSOC);
                $Spotter = new Spotter();
                $all_result = array_merge($all,$Spotter->countAllMonthsLastYear(false));
                $values = array();
                foreach ($all_result as $cnt) {
                    $values[] = $cnt['date_count'];
                }
                array_multisort($values,SORT_DESC,$all_result);
                if ($limit) array_splice($all_result,11);
                return $all_result;
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
                $query = "INSERT INTO stats_airline (airline_icao,airline_name,cnt) VALUES (:airline_icao,:airline_name,:cnt) ON DUPLICATE KEY UPDATE cnt = cnt+:cnt";
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
	public function addStatDepartureAirports($airport_icao,$airport_city,$airport_country,$departure) {
                $query = "INSERT INTO stats_airport (airport_icao,airport_city,airport_country,departure) VALUES (:airport_icao,:airport_city,:airport_country,:departure) ON DUPLICATE KEY UPDATE departure = departure+:departure";
                $query_values = array(':airport_icao' => $airport_icao,':airport_city' => $airport_city,':airport_country' => $airport_country,':departure' => $departure);
                 try {
                        $sth = $this->db->prepare($query);
                        $sth->execute($query_values);
                } catch(PDOException $e) {
                        return "error : ".$e->getMessage();
                }
        }
	public function addStatArrivalAirports($airport_icao,$airport_city,$airport_country,$arrival) {
                $query = "INSERT INTO stats_airport (airport_icao,airport_city,airport_country,arrival) VALUES (:airport_icao,:airport_city,:airport_country,:arrival) ON DUPLICATE KEY UPDATE arrival = arrival+:arrival";
                $query_values = array(':airport_icao' => $airport_icao,':airport_city' => $airport_city,':airport_country' => $airport_country,':arrival' => $arrival);
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
        
        public function addOldStats() {
    		global $globalArchiveMonths, $globalArchive;
    		$last_update = $this->getLastStatsUpdate();
    		if (empty($last_update)) {
		$Spotter = new Spotter();
		$alldata = $Spotter->countAllMonths();
		$lastyear = false;
		foreach ($alldata as $number) {
			if ($number['year_name'] != date('Y')) $lastyear = true;
			$this->addStat('flights_bymonth',$number['date_count'],date('Y-m-d H:i:s',mktime(0,0,0,$number['month_name'],1,$number['year_name'])));
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
		if ($lastyear) {
			$monthsSinceLastYear = date('n');
			$alldata = $Spotter->countAllAircraftTypes(false,$monthsSinceLastYear);
			foreach ($alldata as $number) {
				$this->addStatAircraft($number['aircraft_icao'],$number['aircraft_icao_count'],$number['aircraft_name']);
			}
			$alldata = $Spotter->countAllAirlines(false,$monthsSinceLastYear);
			foreach ($alldata as $number) {
				$this->addStatAirline($number['airline_icao'],$number['airline_count']);
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
			
			if ($globalArchive) {
				$query = "INSERT INTO spotter_archive_output SELECT * FROM spotter_output WHERE spotter_output.date < '".date('Y')."-01-01 00:00:00'";
				echo $query;
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
		if (!isset($globalArchiveMonths) || $globalArchiveMonths == '') $globalArchiveMonths = 2;
		if ($globalArchiveMonths > 0) {
			$alldata = $Spotter->countAllAircraftTypes(false,$globalArchiveMonths);
			foreach ($alldata as $number) {
				$this->addStatAircraft($number['aircraft_icao'],$number['aircraft_icao_count']);
			}
			$alldata = $Spotter->countAllAirlines(false,$globalArchiveMonths);
			foreach ($alldata as $number) {
				$this->addStatAirline($number['airline_icao'],$number['airline_count']);
			}
			$alldata = $Spotter->countAllOwners(false,$globalArchiveMonths);
			foreach ($alldata as $number) {
				$this->addStatOwner($number['owner_name'],$number['owner_count']);
			}
			$alldata = $Spotter->countAllPilots(false,$globalArchiveMonths);
			foreach ($alldata as $number) {
				$this->addStatPilot($number['pilot_id'],$number['pilot_count']);
			}
			$alldata = $Spotter->countAllDepartureAirports(false,$globalArchiveMonths);
			//print_r($alldate);
			foreach ($alldata as $number) {
				$this->addStatDepartureAirports($number['airport_departure_icao'],$number['airport_departure_city'],$number['airport_departure_country'],$number['airport_departure_icao_count']);
			}
			$alldata = $Spotter->countAllArrivalAirports(false,$globalArchiveMonths);
			foreach ($alldata as $number) {
				$this->addStatArrivalAirports($number['airport_arrival_icao'],$number['airport_arrival_city'],$number['airport_arrival_country'],$number['airport_arrival_icao_count']);
			}
			$this->addStat('aircrafts_byyear',$this->getStatsAircraftTotal(),date('Y').'-01-01 00:00:00');
			$this->addStat('airlines_byyear',$this->getStatsAirlineTotal(),date('Y').'-01-01 00:00:00');
			$this->addStat('owner_byyear',$this->getStatsOwnerTotal(),date('Y').'-01-01 00:00:00');
			$this->addStat('pilot_byyear',$this->getStatsPilotTotal(),date('Y').'-01-01 00:00:00');
		
			if ($globalArchive) {
				$query = 'INSERT INTO spotter_archive_output SELECT * FROM spotter_output WHERE spotter_output.date < DATE_SUB(UTC_TIMESTAMP(), INTERVAL '.$globalArchiveMonths.' MONTH)';
				try {
					$sth = $this->db->prepare($query);
					$sth->execute();
				} catch(PDOException $e) {
					return "error : ".$e->getMessage();
				}
			}

			$query = 'DELETE FROM spotter_output WHERE spotter_output.date < DATE_SUB(UTC_TIMESTAMP(), INTERVAL '.$globalArchiveMonths.' MONTH)';
			try {
				$sth = $this->db->prepare($query);
				$sth->execute();
			} catch(PDOException $e) {
				return "error : ".$e->getMessage();
			}
		}
		} else {
			/*
			$last_update_day = $last_update[];

			$alldata = $Spotter->countAllAircraftTypes(false,0,$last_update_day);
			foreach ($alldata as $number) {
				$this->addStatAircraft($number['aircraft_icao'],$number['aircraft_icao_count']);
			}
			$alldata = $Spotter->countAllAirlines(false,0,$last_update_day);
			foreach ($alldata as $number) {
				$this->addStatAirline($number['airline_icao'],$number['airline_count']);
			}
			$alldata = $Spotter->countAllOwners(false,0,$last_update_day);
			foreach ($alldata as $number) {
				$this->addStatOwner($number['owner_name'],$number['owner_count']);
			}
			$alldata = $Spotter->countAllPilots(false,0,$last_update_day);
			foreach ($alldata as $number) {
				$this->addStatPilot($number['pilot_id'],$number['pilot_count']);
			}


			$alldata = $Spotter->countAllAircraftTypes(false,0,$last_update_day);
			foreach ($alldata as $number) {
				$this->addStatAircraft($number['aircraft_icao'],$number['aircraft_icao_count']);
			}
			$alldata = $Spotter->countAllAirlines(false,0,$last_update_day);
			foreach ($alldata as $number) {
				$this->addStatAirline($number['airline_icao'],$number['airline_count']);
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
				$this->addStatDepartureAirports($number['airport_departure_icao'],$number['airport_departure_city'],$number['airport_departure_country'],$number['airport_departure_icao_count']);
			}
			$alldata = $Spotter->countAllArrivalAirports(false,0,$last_update_day);
			foreach ($alldata as $number) {
				$this->addStatArrivalAirports($number['airport_arrival_icao'],$number['airport_arrival_city'],$number['airport_arrival_country'],$number['airport_arrival_icao_count']);
			}
			
			// Add by month using getstat if month finish...
			*/
		}
	}
}

?>