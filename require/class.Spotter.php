<?php
require_once(dirname(__FILE__).'/class.Scheduler.php');
require_once(dirname(__FILE__).'/class.ACARS.php');
require_once(dirname(__FILE__).'/class.Image.php');
$global_query = "SELECT spotter_output.spotter_id,spotter_output.flightaware_id,spotter_output.ident,spotter_output.registration,spotter_output.airline_name,spotter_output.airline_icao,spotter_output.airline_country,spotter_output.airline_type,spotter_output.aircraft_icao,spotter_output.aircraft_name,spotter_output.aircraft_manufacturer,spotter_output.departure_airport_icao,spotter_output.departure_airport_name,spotter_output.departure_airport_city,spotter_output.departure_airport_country,spotter_output.departure_airport_time,spotter_output.arrival_airport_icao,spotter_output.arrival_airport_name,spotter_output.arrival_airport_city,spotter_output.arrival_airport_country,spotter_output.arrival_airport_time,spotter_output.route_stop,spotter_output.date,spotter_output.latitude,spotter_output.longitude,spotter_output.waypoints,spotter_output.altitude,spotter_output.real_altitude,spotter_output.heading,spotter_output.ground_speed,spotter_output.highlight,spotter_output.squawk,spotter_output.ModeS,spotter_output.pilot_id,spotter_output.pilot_name,spotter_output.owner_name,spotter_output.verticalrate,spotter_output.format_source,spotter_output.source_name,spotter_output.ground,spotter_output.last_ground,spotter_output.last_seen,spotter_output.last_latitude,spotter_output.last_longitude,spotter_output.last_altitude,spotter_output.last_ground_speed,spotter_output.real_arrival_airport_icao,spotter_output.real_arrival_airport_time,spotter_output.real_departure_airport_icao,spotter_output.real_departure_airport_time FROM spotter_output";

class Spotter{
	public $aircraft_correct_icaotype = array('CL64' => 'CL60',
					'F9LX' => 'F900',
					'K35T' => 'K35R',
					'F5EX' => 'FA50',
					'G102' => 'GLID',
					'LJ36' => 'LJ35',
					'G500' => 'EGRT',
					'A300' => 'A30B',
					'ROT' => 'B77W',
					'BPN' => 'B772',
					'0011' => 'B77W',
					'F9DX' => 'F900',
					'B757' => 'B752',
					'4/05' => 'A332',
					'F/A3' => 'A320',
					'F2EX' => 'F2TH',
					'EA55' => 'EA50',
					'B73B' => 'B737',
					'G450' => 'GLF4',
					'H25X' => 'H25B',
					'E175' => 'E75S',
					'B777' => 'B77W',
					'B777F' => 'B77W',
					'BAE' => 'B461',
					'BEECHCRAFT' => 'B190',
					'C172R' => 'C172',
					'CESSNA' => 'C550',
					'CONCORDE' => 'CONC',
					'CRJ200' => 'CRJ2',
					'CRJ700' => 'CRJ7',
					'A300B4' => 'A30B',
					'MD' => 'MD11',
					'DHC' => 'DH8A',
					'EMB' => 'E550',
					'A225' => 'AN225',
					'A140' => 'AN124',
					'F406GC' => 'F406',
					'RW500' => 'AC50',
					'S340A' => 'SF34',
					'F2LX' => 'F2TH',
					'CL65' => 'CL60',
					'A380' => 'A388',
					'G550' => 'GLF5',
					'F9EX' => 'F900',
					'E195' => 'E190',
					'H750' => 'H25B',
					'777' => 'B772',
					'747' => 'B748',
					'B747' => 'B744',
					'B757' => 'B753',
					'B767' => 'B763',
					'PA39' => 'PA30',
					'H900' => 'H25B',
					'AN74' => 'AN72',
					'CL85' => 'CRJ2',
					'G400' => 'GLF4',
					'CL61' => 'CL60',
					'F2TS' => 'F2TH',
					'Z602' => 'CH60',
					'G100' => 'ASTR');

    /** @var $db PDOStatement */
	public $db;
	
	public function __construct($dbc = null) {
		$Connection = new Connection($dbc);
		$this->db = $Connection->db();
		if ($this->db === null) die('Error: No DB connection. (Spotter)');
	}

	/**
	* Get SQL query part for filter used
	* @param array $filter the filter
	* @return String the SQL part
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
			if (isset($flt['airlines']) && !empty($flt['airlines'])) {
				if ($flt['airlines'][0] != '' && $flt['airlines'][0] != 'all') {
					if (isset($flt['source'])) {
						$filter_query_join .= " INNER JOIN (SELECT flightaware_id FROM spotter_output WHERE spotter_output.airline_icao IN ('".implode("','",$flt['airlines'])."') AND spotter_output.format_source IN ('".implode("','",$flt['source'])."')) saf ON saf.flightaware_id = spotter_output.flightaware_id";
					} else {
						$filter_query_join .= " INNER JOIN (SELECT flightaware_id FROM spotter_output WHERE spotter_output.airline_icao IN ('".implode("','",$flt['airlines'])."')) saf ON saf.flightaware_id = spotter_output.flightaware_id";
					}
				}
			}
			if (isset($flt['pilots_id']) && !empty($flt['pilots_id'])) {
				if (isset($flt['source'])) {
					$filter_query_join .= " INNER JOIN (SELECT flightaware_id FROM spotter_output WHERE spotter_output.pilot_id IN ('".implode("','",$flt['pilots_id'])."') AND spotter_output.format_source IN ('".implode("','",$flt['source'])."')) spf ON spf.flightaware_id = spotter_output.flightaware_id";
				} else {
					$filter_query_join .= " INNER JOIN (SELECT flightaware_id FROM spotter_output WHERE spotter_output.pilot_id IN ('".implode("','",$flt['pilots_id'])."')) spf ON spf.flightaware_id = spotter_output.flightaware_id";
				}
			}
			if (isset($flt['idents']) && !empty($flt['idents'])) {
				if (isset($flt['source'])) {
					$filter_query_join .= " INNER JOIN (SELECT flightaware_id FROM spotter_output WHERE spotter_output.ident IN ('".implode("','",$flt['idents'])."') AND spotter_output.format_source IN ('".implode("','",$flt['source'])."')) spfi ON spfi.flightaware_id = spotter_output.flightaware_id";
				} else {
					$filter_query_join .= " INNER JOIN (SELECT flightaware_id FROM spotter_output WHERE spotter_output.ident IN ('".implode("','",$flt['idents'])."')) spfi ON spfi.flightaware_id = spotter_output.flightaware_id";
				}
			}
			if (isset($flt['registrations']) && !empty($flt['registrations'])) {
				if (isset($flt['source'])) {
					$filter_query_join .= " INNER JOIN (SELECT flightaware_id FROM spotter_output WHERE spotter_output.registration IN ('".implode("','",$flt['registrations'])."') AND spotter_output.format_source IN ('".implode("','",$flt['source'])."')) sre ON sre.flightaware_id = spotter_output.flightaware_id";
				} else {
					$filter_query_join .= " INNER JOIN (SELECT flightaware_id FROM spotter_output WHERE spotter_output.registration IN ('".implode("','",$flt['registrations'])."')) sre ON sre.flightaware_id = spotter_output.flightaware_id";
				}
			}
			if ((isset($flt['airlines']) && empty($flt['airlines']) && isset($flt['pilots_id']) && empty($flt['pilots_id']) && isset($flt['idents']) && empty($flt['idents']) && isset($flt['registrations']) && empty($flt['registrations'])) || (!isset($flt['airlines']) && !isset($flt['pilots_id']) && !isset($flt['idents']) && !isset($flt['registrations']))) {
				if (isset($flt['source'])) {
					$filter_query_join .= " INNER JOIN (SELECT flightaware_id FROM spotter_output WHERE spotter_output.format_source IN ('".implode("','",$flt['source'])."')) sf ON sf.flightaware_id = spotter_output.flightaware_id";
				}
			}
		}
		if (isset($filter['airlines']) && !empty($filter['airlines'])) {
			if ($filter['airlines'][0] != '' && $filter['airlines'][0] != 'all') {
					$filter_query_join .= " INNER JOIN (SELECT flightaware_id FROM spotter_output WHERE spotter_output.airline_icao IN ('".implode("','",$filter['airlines'])."')) sof ON sof.flightaware_id = spotter_output.flightaware_id";
			}
		}
		if (isset($filter['airlinestype']) && !empty($filter['airlinestype'])) {
			$filter_query_join .= " INNER JOIN (SELECT flightaware_id FROM spotter_output WHERE spotter_output.airline_type = '".$filter['airlinestype']."') sa ON sa.flightaware_id = spotter_output.flightaware_id ";
		}
		if (isset($filter['alliance']) && !empty($filter['alliance'])) {
			$filter_query_join .= " INNER JOIN (SELECT icao FROM airlines WHERE alliance = '".$filter['alliance']."') sal ON sal.icao = spotter_output.airline_icao ";
		}
		if (isset($filter['pilots_id']) && !empty($filter['pilots_id'])) {
			$filter_query_join .= " INNER JOIN (SELECT flightaware_id FROM spotter_output WHERE spotter_output.pilot_id IN ('".implode("','",$filter['pilots_id'])."')) spid ON spid.flightaware_id = spotter_output.flightaware_id";
		}
		if (isset($filter['blocked']) && $filter['blocked'] == true) {
			$filter_query_join .= " INNER JOIN (SELECT callsign FROM aircraft_block) cblk ON cblk.callsign = spotter_output.ident";
		}
		if (isset($filter['source']) && !empty($filter['source'])) {
			if (count($filter['source']) == 1) {
				$filter_query_where .= " AND format_source = '".$filter['source'][0]."'";
			} else {
				$filter_query_where .= " AND format_source IN ('".implode("','",$filter['source'])."')";
			}
		}
		if (isset($filter['ident']) && !empty($filter['ident'])) {
			$filter_query_where .= " AND ident = '".$filter['ident']."'";
		}
		if (isset($filter['id']) && !empty($filter['id'])) {
			$filter_query_where .= " AND flightaware_id = '".$filter['id']."'";
		}
		if (isset($filter['source_aprs']) && !empty($filter['source_aprs'])) {
			$filter_query_where .= " AND format_source = 'aprs' AND source_name IN ('".implode("','",$filter['source_aprs'])."')";
		}
		if (isset($filter['year']) && $filter['year'] != '') {
			if ($globalDBdriver == 'mysql') {
				$filter_query_where .= " AND YEAR(spotter_output.date) = '".$filter['year']."'";
			} else {
				$filter_query_where .= " AND EXTRACT(YEAR FROM spotter_output.date) = '".$filter['year']."'";
			}
		}
		if (isset($filter['month']) && $filter['month'] != '') {
			if ($globalDBdriver == 'mysql') {
				$filter_query_where .= " AND MONTH(spotter_output.date) = '".$filter['month']."'";
			} else {
				$filter_query_where .= " AND EXTRACT(MONTH FROM spotter_output.date) = '".$filter['month']."'";
			}
		}
		if (isset($filter['day']) && $filter['day'] != '') {
			if ($globalDBdriver == 'mysql') {
				$filter_query_where .= " AND DAY(spotter_output.date) = '".$filter['day']."'";
			} else {
				$filter_query_where .= " AND EXTRACT(DAY FROM spotter_output.date) = '".$filter['day']."'";
			}
		}
		if (isset($filter['since_date']) && $filter['since_date'] != '') {
			$filter_query_where .= " AND spotter_output.date >= '".$filter['since_date']."'";
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
	* @return array the spotter information
	*
	*/
	public function getDataFromDB($query, $params = array(), $limitQuery = '',$schedules = false)
	{
		global $globalSquawkCountry, $globalIVAO, $globalVATSIM, $globalphpVMS, $globalAirlinesSource, $globalVAM, $globalVA, $globalNoAirlines;
		$Image = new Image($this->db);
		$Schedule = new Schedule($this->db);
		$ACARS = new ACARS($this->db);
		if (!isset($globalIVAO)) $globalIVAO = FALSE;
		if (!isset($globalVATSIM)) $globalVATSIM = FALSE;
		if (!isset($globalphpVMS)) $globalphpVMS = FALSE;
		if (!isset($globalVAM)) $globalVAM = FALSE;
		if (!isset($globalVA)) $globalVA = FALSE;
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
		
	//	$num_rows = count($sth->fetchAll());
		$num_rows = 0;

		$spotter_array = array();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$num_rows++;
			$temp_array = array();
			if (isset($row['spotter_live_id'])) {
				//$temp_array['spotter_id'] = $row['spotter_live_id'];
				$temp_array['spotter_id'] = $this->getSpotterIDBasedOnFlightAwareID($row['flightaware_id']);
			} elseif (isset($row['spotter_archive_id'])) {
				$temp_array['spotter_id'] = $row['spotter_archive_id'];
			} elseif (isset($row['spotter_archive_output_id'])) {
				$temp_array['spotter_id'] = $row['spotter_archive_output_id'];
			} elseif (isset($row['spotter_id'])) {
				$temp_array['spotter_id'] = $row['spotter_id'];
			} else {
				$temp_array['spotter_id'] = '';
			}
			if (isset($row['flightaware_id'])) $temp_array['flightaware_id'] = $row['flightaware_id'];
			if (isset($row['modes'])) $temp_array['modes'] = $row['modes'];
			$temp_array['ident'] = $row['ident'];
			if ($temp_array['ident'] != '') {
				$temp_array['blocked'] = $this->checkIdentBlocked($temp_array['ident']);
			}
			if (isset($row['registration']) && $row['registration'] != '') {
				$temp_array['registration'] = $row['registration'];
			} elseif (isset($temp_array['modes'])) {
				$temp_array['registration'] = $this->getAircraftRegistrationBymodeS($temp_array['modes']);
			} else $temp_array['registration'] = '';
			if (isset($row['aircraft_icao'])) {
				/*
				$icao = $row['aircraft_icao'];
				if (isset($this->aircraft_correct_icaotype[$icao])) {
					$aircraft_array = $this->getAllAircraftInfo($this->aircraft_correct_icaotype[$icao]);
				} else {
				*/
					$temp_array['aircraft_type'] = $row['aircraft_icao'];
				//}
			}
			$temp_array['departure_airport'] = $row['departure_airport_icao'];
			$temp_array['arrival_airport'] = $row['arrival_airport_icao'];
			if (isset($row['real_arrival_airport_icao']) && $row['real_arrival_airport_icao'] != NULL) $temp_array['real_arrival_airport'] = $row['real_arrival_airport_icao'];
			if (isset($row['latitude'])) $temp_array['latitude'] = $row['latitude'];
			if (isset($row['longitude'])) $temp_array['longitude'] = $row['longitude'];
			if (isset($row['verticalrate'])) $temp_array['verticalrate'] = $row['verticalrate'];
			if (isset($row['last_latitude'])) $temp_array['last_latitude'] = $row['last_latitude'];
			if (isset($row['last_longitude'])) $temp_array['last_longitude'] = $row['last_longitude'];
			/*
			if (Connection->tableExists('countries')) {
				$country_info = $this->getCountryFromLatitudeLongitude($temp_array['latitude'],$temp_array['longitude']);
				if (is_array($country_info) && isset($country_info['name']) && isset($country_info['iso2'])) {
				    $temp_array['country'] = $country_info['name'];
				    $temp_array['country_iso2'] = $country_info['iso2'];
				}
			}
			*/
			if (isset($row['waypoints'])) $temp_array['waypoints'] = $row['waypoints'];
			if (isset($row['format_source'])) $temp_array['format_source'] = $row['format_source'];
			if (isset($row['route_stop']) && $row['route_stop'] != '') {
				$temp_array['route_stop'] = $row['route_stop'];
				$allroute = explode(' ',$row['route_stop']);
				foreach ($allroute as $route) {
					$route_airport_array = $this->getAllAirportInfo($route);
					if (isset($route_airport_array[0]['name'])) {
						$route_stop_details = array();
						$route_stop_details['airport_name'] = $route_airport_array[0]['name'];
						$route_stop_details['airport_city'] = $route_airport_array[0]['city'];
						$route_stop_details['airport_country'] = $route_airport_array[0]['country'];
						$route_stop_details['airport_icao'] = $route_airport_array[0]['icao'];
						$temp_array['route_stop_details'][] = $route_stop_details;
					}
				}
			}
			if (isset($row['altitude'])) $temp_array['altitude'] = $row['altitude'];
			if (isset($row['real_altitude'])) $temp_array['real_altitude'] = $row['real_altitude'];
			if (isset($row['heading'])) {
				$temp_array['heading'] = $row['heading'];
				$heading_direction = $this->parseDirection($row['heading']);
				if (isset($heading_direction[0]['direction_fullname'])) $temp_array['heading_name'] = $heading_direction[0]['direction_fullname'];
			}
			if (isset($row['ground_speed'])) $temp_array['ground_speed'] = $row['ground_speed'];
			$temp_array['image'] = "";
			$temp_array['image_thumbnail'] = "";
			$temp_array['image_source'] = "";
			$temp_array['image_copyright'] = "";
 
			if (isset($row['highlight'])) {
				$temp_array['highlight'] = $row['highlight'];
			} else $temp_array['highlight'] = '';
			
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
			
			if (isset($row['aircraft_name']) && $row['aircraft_name'] != '' && isset($row['aircraft_shadow']) && $row['aircraft_shadow'] != '') {
				$temp_array['aircraft_name'] = $row['aircraft_name'];
				$temp_array['aircraft_manufacturer'] = $row['aircraft_manufacturer'];
				if (isset($row['aircraft_shadow'])) {
					$temp_array['aircraft_shadow'] = $row['aircraft_shadow'];
				}
			} elseif (isset($row['aircraft_icao'])) {
				$icao = $row['aircraft_icao'];
				if (!isset($this->aircraft_correct_icaotype[$icao])) {
					$aircraft_array = $this->getAllAircraftInfo($row['aircraft_icao']);
				} else {
					$aircraft_array = $this->getAllAircraftInfo($this->aircraft_correct_icaotype[$icao]);
				}
				if (count($aircraft_array) > 0) {
					$temp_array['aircraft_name'] = $aircraft_array[0]['type'];
					$temp_array['aircraft_manufacturer'] = $aircraft_array[0]['manufacturer'];
				
					if ($aircraft_array[0]['aircraft_shadow'] != NULL) {
						$temp_array['aircraft_shadow'] = $aircraft_array[0]['aircraft_shadow'];
					} else $temp_array['aircraft_shadow'] = 'default.png';
                                } else {
                            		$temp_array['aircraft_shadow'] = 'default.png';
					$temp_array['aircraft_name'] = 'N/A';
					$temp_array['aircraft_manufacturer'] = 'N/A';
                            	}
			}
			$fromsource = NULL;
			if (isset($globalAirlinesSource) && $globalAirlinesSource != '') $fromsource = $globalAirlinesSource;
			elseif (isset($row['format_source']) && $row['format_source'] == 'vatsimtxt') $fromsource = 'vatsim';
			elseif (isset($row['format_source']) && $row['format_source'] == 'whazzup') $fromsource = 'ivao';
			elseif (isset($globalVATSIM) && $globalVATSIM) $fromsource = 'vatsim';
			elseif (isset($globalIVAO) && $globalIVAO) $fromsource = 'ivao';
			if (!isset($row['airline_name']) || $row['airline_name'] == '') {
				if ((!isset($globalNoAirlines) || $globalNoAirlines === FALSE) && !is_numeric(substr($row['ident'], 0, 3))) {
					if ((isset($temp_array['registration']) && $row['ident'] != str_replace('-','',$temp_array['registration'])) || !isset($temp_array['registration'])) {
						/*
						if (isset($row['format_type']) && $row['format_type'] == 'flarm') {
							$airline_array = $this->getAllAirlineInfo('NA');
						} else
						*/
						if (is_numeric(substr($row['ident'], 2, 1))) {
							$airline_array = $this->getAllAirlineInfo(substr($row['ident'], 0, 2),$fromsource);
						//} elseif (is_numeric(substr($row['ident'], 3, 1)) && substr($row['ident'], 0,3) != 'OGN') {
						} elseif (is_numeric(substr($row['ident'], 3, 1)) && !((substr($row['ident'], 0, 3) == 'OGN' || substr($row['ident'], 0, 3) == 'FLR' || substr($row['ident'], 0, 3) == 'ICA') && isset($row['format_source']) && $row['format_source'] == 'aprs')) {
							$airline_array = $this->getAllAirlineInfo(substr($row['ident'], 0, 3),$fromsource);
						} else {
							$airline_array = $this->getAllAirlineInfo('NA');
						}
					} else {
						$airline_array = $this->getAllAirlineInfo('NA');
					}
				} else {
					$airline_array = $this->getAllAirlineInfo('NA');
				}
				if (count($airline_array) > 0) {
					$temp_array['airline_icao'] = $airline_array[0]['icao'];
					$temp_array['airline_iata'] = $airline_array[0]['iata'];
					$temp_array['airline_name'] = $airline_array[0]['name'];
					$temp_array['airline_country'] = $airline_array[0]['country'];
					$temp_array['airline_callsign'] = $airline_array[0]['callsign'];
					$temp_array['airline_type'] = $airline_array[0]['type'];
				} else {
					$airline_array = $this->getAllAirlineInfo('NA');
					$temp_array['airline_icao'] = $airline_array[0]['icao'];
					$temp_array['airline_iata'] = $airline_array[0]['iata'];
					$temp_array['airline_name'] = $airline_array[0]['name'];
					$temp_array['airline_country'] = $airline_array[0]['country'];
					$temp_array['airline_callsign'] = $airline_array[0]['callsign'];
					$temp_array['airline_type'] = $airline_array[0]['type'];
				}
			} else {
				$temp_array['airline_icao'] = $row['airline_icao'];
				if (isset($row['airline_iata'])) $temp_array['airline_iata'] = $row['airline_iata'];
				else $temp_array['airline_iata'] = 'N/A';
				$temp_array['airline_name'] = $row['airline_name'];
				if (isset($row['airline_country'])) $temp_array['airline_country'] = $row['airline_country'];
				if (isset($row['airline_callsign'])) $temp_array['airline_callsign'] = $row['airline_callsign'];
				else $temp_array['airline_callsign'] = 'N/A';
				if (isset($row['airline_type'])) $temp_array['airline_type'] = $row['airline_type'];
				if ($temp_array['airline_icao'] != '' && $temp_array['airline_iata'] == 'N/A') {
					$airline_array = $this->getAllAirlineInfo($temp_array['airline_icao']);
					if (count($airline_array) > 0) {
						$temp_array['airline_icao'] = $airline_array[0]['icao'];
						$temp_array['airline_iata'] = $airline_array[0]['iata'];
						$temp_array['airline_name'] = $airline_array[0]['name'];
						$temp_array['airline_country'] = $airline_array[0]['country'];
						$temp_array['airline_callsign'] = $airline_array[0]['callsign'];
						$temp_array['airline_type'] = $airline_array[0]['type'];
					}
				}
			}
			if (isset($temp_array['airline_iata']) && $temp_array['airline_iata'] != '') {
				$acars_array = $ACARS->getLiveAcarsData($temp_array['airline_iata'].substr($temp_array['ident'],3));
				//$acars_array = ACARS->getLiveAcarsData('BA40YL');
				if (count($acars_array) > 0) {
					$temp_array['acars'] = $acars_array;
					//print_r($acars_array);
				}
			}
			if (isset($row['owner_name']) && $row['owner_name'] != '' && $row['owner_name'] != NULL) {
				$temp_array['aircraft_owner'] = $row['owner_name'];
			}
			if ($temp_array['registration'] != "" && !$globalVA && !$globalIVAO && !$globalVATSIM && !$globalphpVMS && !$globalVAM && !isset($temp_array['aircraft_owner'])) {
				$owner_info = $this->getAircraftOwnerByRegistration($temp_array['registration']);
				if ($owner_info['owner'] != '') $temp_array['aircraft_owner'] = ucwords(strtolower($owner_info['owner']));
				$temp_array['aircraft_base'] = $owner_info['base'];
				$temp_array['aircraft_date_first_reg'] = $owner_info['date_first_reg'];
			}

			if($temp_array['registration'] != "" || (($globalVA || $globalIVAO || $globalVATSIM || $globalphpVMS || $globalVAM) && isset($temp_array['aircraft_type']) && $temp_array['aircraft_type'] != ''))
			{
				if ($globalIVAO) {
					if (isset($temp_array['airline_icao']))	$image_array = $Image->getSpotterImage('',$temp_array['aircraft_type'],$temp_array['airline_icao']);
					else $image_array = $Image->getSpotterImage('',$temp_array['aircraft_type']);
				} elseif (isset($temp_array['aircraft_type']) && isset($temp_array['airline_icao'])) $image_array = $Image->getSpotterImage($temp_array['registration'],$temp_array['aircraft_type'],$temp_array['airline_icao']);
				elseif (isset($temp_array['aircraft_type'])) $image_array = $Image->getSpotterImage($temp_array['registration'],$temp_array['aircraft_type']);
				else $image_array = $Image->getSpotterImage($temp_array['registration']);
				if (count($image_array) > 0) {
					$temp_array['image'] = $image_array[0]['image'];
					$temp_array['image_thumbnail'] = $image_array[0]['image_thumbnail'];
					$temp_array['image_source'] = $image_array[0]['image_source'];
					$temp_array['image_source_website'] = $image_array[0]['image_source_website'];
					if ($temp_array['image_source_website'] == '' && $temp_array['image_source'] == 'planespotters') {
						$planespotter_url_array = explode("_", $temp_array['image']);
						$planespotter_id = str_replace(".jpg", "", $planespotter_url_array[1]);
						$temp_array['image_source_website'] = 'http://www.planespotters.net/Aviation_Photos/photo.show?id='.$planespotter_id;
					 }
					$temp_array['image_copyright'] = $image_array[0]['image_copyright'];
				}
			}


			if (isset($row['departure_airport_time']) && $row['departure_airport_time'] != '') {
				$temp_array['departure_airport_time'] = $row['departure_airport_time'];
			}
			if (isset($row['arrival_airport_time']) && $row['arrival_airport_time'] != '') {
				$temp_array['arrival_airport_time'] = $row['arrival_airport_time'];
			}
			
			if ((!isset($globalVA) || ! $globalVA) && (!isset($globalIVAO) || ! $globalIVAO) && (!isset($globalVATSIM) || !$globalVATSIM) && (!isset($globalphpVMS) || !$globalphpVMS) && (!isset($globalVAM) || !$globalVAM)) {
				if ($schedules === true) {
					$schedule_array = $Schedule->getSchedule($temp_array['ident']);
					//print_r($schedule_array);
					if (count($schedule_array) > 0) {
						if ($schedule_array['departure_airport_icao'] != '') {
							$row['departure_airport_icao'] = $schedule_array['departure_airport_icao'];
							if (strlen($row['departure_airport_icao']) == 3) $row['departure_airport_icao'] = $this->getAirportIcao($row['departure_airport_icao']);
							$temp_array['departure_airport'] = $row['departure_airport_icao'];
						}
						if ($schedule_array['arrival_airport_icao'] != '') {
							$row['arrival_airport_icao'] = $schedule_array['arrival_airport_icao'];
							if (strlen($row['arrival_airport_icao']) == 3) $row['arrival_airport_icao'] = $this->getAirportIcao($row['arrival_airport_icao']);
							$temp_array['arrival_airport'] = $row['arrival_airport_icao'];
						}
						$temp_array['departure_airport_time'] = $schedule_array['departure_airport_time'];
						$temp_array['arrival_airport_time'] = $schedule_array['arrival_airport_time'];
					}
				}
			} else {
				if (isset($row['real_departure_airport_time']) && $row['real_departure_airport_time'] != '') {
					$temp_array['departure_airport_time'] = $row['real_departure_airport_time'];
				}
				if (isset($row['real_arrival_airport_time']) && $row['real_arrival_airport_time'] != '') {
					$temp_array['real_arrival_airport_time'] = $row['real_arrival_airport_time'];
				}
			}
			
			//if ($row['departure_airport_icao'] != '' && $row['departure_airport_name'] == '') {
			if ($row['departure_airport_icao'] != '') {
				if (strlen($row['departure_airport_icao']) == 3) $row['departure_airport_icao'] = $this->getAirportIcao($row['departure_airport_icao']);
				$departure_airport_array = $this->getAllAirportInfo($row['departure_airport_icao']);
				if (!isset($departure_airport_array[0]['name'])) $departure_airport_array = $this->getAllAirportInfo('NA');
			/*
			} elseif ($row['departure_airport_name'] != '') {
				$temp_array['departure_airport_name'] = $row['departure_airport_name'];
				$temp_array['departure_airport_city'] = $row['departure_airport_city'];
				$temp_array['departure_airport_country'] = $row['departure_airport_country'];
				$temp_array['departure_airport_icao'] = $row['departure_airport_icao'];
			*/
			} else $departure_airport_array = $this->getAllAirportInfo('NA');
			if (isset($departure_airport_array[0]['name'])) {
				$temp_array['departure_airport_name'] = $departure_airport_array[0]['name'];
				$temp_array['departure_airport_city'] = $departure_airport_array[0]['city'];
				$temp_array['departure_airport_country'] = $departure_airport_array[0]['country'];
				$temp_array['departure_airport_iata'] = $departure_airport_array[0]['iata'];
				$temp_array['departure_airport_icao'] = $departure_airport_array[0]['icao'];
				$temp_array['departure_airport_latitude'] = $departure_airport_array[0]['latitude'];
				$temp_array['departure_airport_longitude'] = $departure_airport_array[0]['longitude'];
				$temp_array['departure_airport_altitude'] = $departure_airport_array[0]['altitude'];
			}

			/*
			if (isset($row['departure_airport_time'])) {
				$temp_array['departure_airport_time'] = $row['departure_airport_time'];
			}
			*/
			
			if ($row['arrival_airport_icao'] != '') {
				if (strlen($row['arrival_airport_icao']) == 3) $row['arrival_airport_icao'] = $this->getAirportIcao($row['arrival_airport_icao']);
				$arrival_airport_array = $this->getAllAirportInfo($row['arrival_airport_icao']);
				if (count($arrival_airport_array) == 0) $arrival_airport_array = $this->getAllAirportInfo('NA');
			} else $arrival_airport_array = $this->getAllAirportInfo('NA');
			if (isset($arrival_airport_array[0]['name'])) {
				$temp_array['arrival_airport_name'] = $arrival_airport_array[0]['name'];
				$temp_array['arrival_airport_city'] = $arrival_airport_array[0]['city'];
				$temp_array['arrival_airport_country'] = $arrival_airport_array[0]['country'];
				$temp_array['arrival_airport_iata'] = $arrival_airport_array[0]['iata'];
				$temp_array['arrival_airport_icao'] = $arrival_airport_array[0]['icao'];
				$temp_array['arrival_airport_latitude'] = $arrival_airport_array[0]['latitude'];
				$temp_array['arrival_airport_longitude'] = $arrival_airport_array[0]['longitude'];
				$temp_array['arrival_airport_altitude'] = $arrival_airport_array[0]['altitude'];
			}
			/*
			if (isset($row['arrival_airport_time'])) {
				$temp_array['arrival_airport_time'] = $row['arrival_airport_time'];
			}
			*/
			if (isset($row['pilot_id']) && $row['pilot_id'] != '') $temp_array['pilot_id'] = $row['pilot_id'];
			if (isset($row['pilot_name']) && $row['pilot_name'] != '') $temp_array['pilot_name'] = $row['pilot_name'];
			if (isset($row['source_name']) && $row['source_name'] != '') $temp_array['source_name'] = $row['source_name'];
			if (isset($row['over_country']) && $row['over_country'] != '') $temp_array['over_country'] = $row['over_country'];
			if (isset($row['distance']) && $row['distance'] != '') $temp_array['distance'] = $row['distance'];
			if (isset($row['squawk'])) {
				$temp_array['squawk'] = $row['squawk'];
				if ($row['squawk'] != '' && isset($temp_array['country_iso2'])) {
					$temp_array['squawk_usage'] = $this->getSquawkUsage($row['squawk'],$temp_array['country_iso2']);
					if ($temp_array['squawk_usage'] == '' && isset($globalSquawkCountry)) $temp_array['squawk_usage'] = $this->getSquawkUsage($row['squawk'],$globalSquawkCountry);
				} elseif ($row['squawk'] != '' && isset($temp_array['over_country'])) {
					$temp_array['squawk_usage'] = $this->getSquawkUsage($row['squawk'],$temp_array['over_country']);
					if ($temp_array['squawk_usage'] == '' && isset($globalSquawkCountry)) $temp_array['squawk_usage'] = $this->getSquawkUsage($row['squawk'],$globalSquawkCountry);
				} elseif ($row['squawk'] != '' && isset($globalSquawkCountry)) $temp_array['squawk_usage'] = $this->getSquawkUsage($row['squawk'],$globalSquawkCountry);
			}
    			
			$temp_array['query_number_rows'] = $num_rows;
			
			$spotter_array[] = $temp_array;
		}
		if ($num_rows == 0) return array();
		$spotter_array[0]['query_number_rows'] = $num_rows;
		return $spotter_array;
	}


    /**
     * Gets all the spotter information
     *
     * @param string $q
     * @param string $registration
     * @param string $aircraft_icao
     * @param string $aircraft_manufacturer
     * @param string $highlights
     * @param string $airline_icao
     * @param string $airline_country
     * @param string $airline_type
     * @param string $airport
     * @param string $airport_country
     * @param string $callsign
     * @param string $departure_airport_route
     * @param string $arrival_airport_route
     * @param string $owner
     * @param string $pilot_id
     * @param string $pilot_name
     * @param string $altitude
     * @param string $date_posted
     * @param string $limit
     * @param string $sort
     * @param string $includegeodata
     * @param string $origLat
     * @param string $origLon
     * @param string $dist
     * @param array $filters
     * @return array the spotter information
     */
	public function searchSpotterData($q = '', $registration = '', $aircraft_icao = '', $aircraft_manufacturer = '', $highlights = '', $airline_icao = '', $airline_country = '', $airline_type = '', $airport = '', $airport_country = '', $callsign = '', $departure_airport_route = '', $arrival_airport_route = '', $owner = '',$pilot_id = '',$pilot_name = '',$altitude = '', $date_posted = '', $limit = '', $sort = '', $includegeodata = '',$origLat = '',$origLon = '',$dist = '',$filters = array())
	{
		global $globalTimezone, $globalDBdriver, $globalVA;
		require_once(dirname(__FILE__).'/class.Translation.php');
		$Translation = new Translation($this->db);

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
					if (is_numeric($q_item)) $additional_query .= "(spotter_output.spotter_id =  '".$q_item."') OR ";
					if (!is_numeric($q_item) && strlen($q_item) < 5) $additional_query .= "(spotter_output.aircraft_icao =  '".$q_item."') OR ";
					if (!is_numeric($q_item)) $additional_query .= "(spotter_output.aircraft_name like '%".$q_item."%') OR ";
					if (!is_numeric($q_item)) $additional_query .= "(spotter_output.aircraft_manufacturer like '%".$q_item."%') OR ";
					if (!is_numeric($q_item) && strlen($q_item) < 5) $additional_query .= "(spotter_output.airline_icao =  '".$q_item."') OR ";
					if (!is_numeric($q_item)) $additional_query .= "(spotter_output.airline_name like '%".$q_item."%') OR ";
					if (!is_numeric($q_item)) $additional_query .= "(spotter_output.airline_country like '%".$q_item."%') OR ";
					if (!is_numeric($q_item) && strlen($q_item) < 5) $additional_query .= "(spotter_output.departure_airport_icao =  '".$q_item."') OR ";
					if (!is_numeric($q_item)) $additional_query .= "(spotter_output.departure_airport_name like '%".$q_item."%') OR ";
					if (!is_numeric($q_item)) $additional_query .= "(spotter_output.departure_airport_city like '%".$q_item."%') OR ";
					if (!is_numeric($q_item)) $additional_query .= "(spotter_output.departure_airport_country like '%".$q_item."%') OR ";
					if (!is_numeric($q_item) && strlen($q_item) < 5) $additional_query .= "(spotter_output.arrival_airport_icao = '".$q_item."') OR ";
					if (!is_numeric($q_item)) $additional_query .= "(spotter_output.arrival_airport_name like '%".$q_item."%') OR ";
					if (!is_numeric($q_item)) $additional_query .= "(spotter_output.arrival_airport_city like '%".$q_item."%') OR ";
					if (!is_numeric($q_item)) $additional_query .= "(spotter_output.arrival_airport_country like '%".$q_item."%') OR ";
					if (!is_numeric($q_item) && strlen($q_item) < 10) $additional_query .= "(spotter_output.registration like '%".$q_item."%') OR ";
					if (!is_numeric($q_item)) $additional_query .= "(spotter_output.owner_name like '%".$q_item."%') OR ";
					if (isset($globalVA) && $globalVA) {
						$additional_query .= "(spotter_output.pilot_id =  '".$q_item."') OR ";
						$additional_query .= "(spotter_output.pilot_name like '%".$q_item."%') OR ";
					}
					if (!is_numeric($q_item)) {
						$translate = $Translation->ident2icao($q_item);
						if ($translate != $q_item) $additional_query .= "(spotter_output.ident like '%".$translate."%') OR ";
					}
					if (!is_numeric($q_item)) $additional_query .= "(spotter_output.highlight like '%".$q_item."%') OR";
					$additional_query .= "(spotter_output.ident like '%".$q_item."%')";
					$additional_query .= ")";
				}
			}
		}

		if ($registration != "")
		{
			$registration = filter_var($registration,FILTER_SANITIZE_STRING);
			if (!is_string($registration) || strlen($registration) > 10)
			{
				return array();
			} else {
				$additional_query .= " AND spotter_output.registration = :registration";
				$query_values = array_merge($query_values,array(':registration' => $registration));
			}
		}

		if ($aircraft_icao != "")
		{
			$aircraft_icao = filter_var($aircraft_icao,FILTER_SANITIZE_STRING);
			if (!is_string($aircraft_icao) || strlen($aircraft_icao) > 5)
			{
				return array();
			} else {
				$additional_query .= " AND spotter_output.aircraft_icao = :aircraft_icao";
				$query_values = array_merge($query_values,array(':aircraft_icao' => $aircraft_icao));
			}
		}

		if ($aircraft_manufacturer != "")
		{
			$aircraft_manufacturer = filter_var($aircraft_manufacturer,FILTER_SANITIZE_STRING);
			if (!is_string($aircraft_manufacturer))
			{
				return array();
			} else {
				$additional_query .= " AND spotter_output.aircraft_manufacturer = :aircraft_manufacturer";
				$query_values = array_merge($query_values,array(':aircraft_manufacturer' => $aircraft_manufacturer));
			}
		}

		if ($highlights == "true")
		{
			if (!is_string($highlights))
			{
				return array();
			} else {
				$additional_query .= " AND (spotter_output.highlight <> '')";
			}
		}

		if ($airline_icao != "")
		{
			$airline_icao = filter_var($airline_icao,FILTER_SANITIZE_STRING);
			if (!is_string($airline_icao) || strlen($airline_icao) > 5)
			{
				return array();
			} else {
				$additional_query .= " AND spotter_output.airline_icao = :airline_icao";
				$query_values = array_merge($query_values,array(':airline_icao' => $airline_icao));
			}
		}

		if ($airline_country != "")
		{
			$airline_country = filter_var($airline_country,FILTER_SANITIZE_STRING);
			if (!is_string($airline_country))
			{
				return array();
			} else {
				$additional_query .= " AND spotter_output.airline_country = :airline_country";
				$query_values = array_merge($query_values,array(':airline_country' => $airline_country));
			}
		}

		if ($airline_type != "")
		{
			if (!is_string($airline_type))
			{
				return array();
			} else {
				if ($airline_type == "passenger")
				{
					$additional_query .= " AND (spotter_output.airline_type = 'passenger')";
				}
				if ($airline_type == "cargo")
				{
					$additional_query .= " AND (spotter_output.airline_type = 'cargo')";
				}
				if ($airline_type == "military")
				{
					$additional_query .= " AND (spotter_output.airline_type = 'military')";
				}
			}
		}

		if ($airport != "")
		{
			$airport = filter_var($airport,FILTER_SANITIZE_STRING);
			if (!is_string($airport) || strlen($airport) > 5)
			{
				return array();
			} else {
				$additional_query .= " AND (spotter_output.departure_airport_icao = :airport OR spotter_output.arrival_airport_icao = :airport)";
				$query_values = array_merge($query_values,array(':airport' => $airport));
			}
		}

		if ($airport_country != "")
		{
			$airport_country = filter_var($airport_country,FILTER_SANITIZE_STRING);
			if (!is_string($airport_country))
			{
				return array();
			} else {
				$additional_query .= " AND (spotter_output.departure_airport_country = :airport_country OR spotter_output.arrival_airport_country = :airport_country)";
				$query_values = array_merge($query_values,array(':airport_country' => $airport_country));
			}
		}
    
		if ($callsign != "")
		{
			$callsign = filter_var($callsign,FILTER_SANITIZE_STRING);
			if (!is_string($callsign))
			{
				return array();
			} else {
				$translate = $Translation->ident2icao($callsign);
				if ($translate != $callsign) {
					$additional_query .= " AND (spotter_output.ident = :callsign OR spotter_output.ident = :translate)";
					$query_values = array_merge($query_values,array(':callsign' => $callsign,':translate' => $translate));
				} else {
					$additional_query .= " AND spotter_output.ident = :callsign";
					$query_values = array_merge($query_values,array(':callsign' => $callsign));
				}
			}
		}

		if ($owner != "")
		{
			$owner = filter_var($owner,FILTER_SANITIZE_STRING);
			if (!is_string($owner))
			{
				return array();
			} else {
				$additional_query .= " AND spotter_output.owner_name = :owner";
				$query_values = array_merge($query_values,array(':owner' => $owner));
			}
		}

		if ($pilot_name != "")
		{
			$pilot_name = filter_var($pilot_name,FILTER_SANITIZE_STRING);
			if (!is_string($pilot_name))
			{
				return array();
			} else {
				$additional_query .= " AND spotter_output.pilot_name = :pilot_name";
				$query_values = array_merge($query_values,array(':pilot_name' => $pilot_name));
			}
		}

		if ($pilot_id != "")
		{
			$pilot_id = filter_var($pilot_id,FILTER_SANITIZE_STRING);
			if (!is_string($pilot_id))
			{
				return array();
			} else {
				$additional_query .= " AND spotter_output.pilot_id = :pilot_id";
				$query_values = array_merge($query_values,array(':pilot_id' => $pilot_id));
			}
		}

		if ($departure_airport_route != "")
		{
			$departure_airport_route = filter_var($departure_airport_route,FILTER_SANITIZE_STRING);
			if (!is_string($departure_airport_route))
			{
				return array();
			} else {
				$additional_query .= " AND spotter_output.departure_airport_icao = :departure_airport_route";
				$query_values = array_merge($query_values,array(':departure_airport_route' => $departure_airport_route));
			}
		}

		if ($arrival_airport_route != "")
		{
			$arrival_airport_route = filter_var($arrival_airport_route,FILTER_SANITIZE_STRING);
			if (!is_string($arrival_airport_route))
			{
				return array();
			} else {
				$additional_query .= " AND spotter_output.arrival_airport_icao = :arrival_airport_route";
				$query_values = array_merge($query_values,array(':arrival_airport_route' => $arrival_airport_route));
			}
		}

		if ($altitude != "")
		{
			$altitude_array = explode(",", $altitude);
			$altitude_array[0] = filter_var($altitude_array[0],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
			$altitude_array[1] = filter_var($altitude_array[1],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);

			if ($altitude_array[1] != "")
			{                
				$altitude_array[0] = substr($altitude_array[0], 0, -2);
				$altitude_array[1] = substr($altitude_array[1], 0, -2);
				$additional_query .= " AND altitude BETWEEN '".$altitude_array[0]."' AND '".$altitude_array[1]."' ";
			} else {
				$altitude_array[0] = substr($altitude_array[0], 0, -2);
				$additional_query .= " AND altitude <= '".$altitude_array[0]."' ";
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
					$additional_query .= " AND TIMESTAMP(CONVERT_TZ(spotter_output.date,'+00:00', '".$offset."')) >= '".$date_array[0]."' AND TIMESTAMP(CONVERT_TZ(spotter_output.date,'+00:00', '".$offset."')) <= '".$date_array[1]."' ";
				} else {
					$additional_query .= " AND CAST(spotter_output.date AT TIME ZONE INTERVAL ".$offset." AS TIMESTAMP) >= '".$date_array[0]."' AND CAST(spotter_output.date AT TIME ZONE INTERVAL ".$offset." AS TIMESTAMP) <= '".$date_array[1]."' ";
				}
			} else {
				$date_array[0] = date("Y-m-d H:i:s", strtotime($date_array[0]));
				if ($globalDBdriver == 'mysql') {
					$additional_query .= " AND TIMESTAMP(CONVERT_TZ(spotter_output.date,'+00:00', '".$offset."')) >= '".$date_array[0]."' ";
				} else {
					$additional_query .= " AND CAST(spotter_output.date AT TIME ZONE INTERVAL ".$offset." AS TIMESTAMP) >= '".$date_array[0]."' ";
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
				//$limit_query = " LIMIT ".$limit_array[0].",".$limit_array[1];
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
				$orderby_query = " ORDER BY spotter_output.date DESC";
			}
		}

		if ($includegeodata == "true")
		{
			$additional_query .= " AND spotter_output.waypoints <> ''";
		}


		if ($origLat != "" && $origLon != "" && $dist != "") {
			$dist = number_format($dist*0.621371,2,'.',''); // convert km to mile

			if ($globalDBdriver == 'mysql') {
				$query="SELECT spotter_output.*, 1.60935*3956 * 2 * ASIN(SQRT( POWER(SIN(($origLat - spotter_archive.latitude)*pi()/180/2),2)+COS( $origLat *pi()/180)*COS(spotter_archive.latitude*pi()/180)*POWER(SIN(($origLon-spotter_archive.longitude)*pi()/180/2),2))) as distance 
						FROM spotter_archive,spotter_output".$filter_query." spotter_output.flightaware_id = spotter_archive.flightaware_id AND spotter_output.ident <> '' ".$additional_query."AND spotter_archive.longitude between ($origLon-$dist/cos(radians($origLat))*69) and ($origLon+$dist/cos(radians($origLat)*69)) and spotter_archive.latitude between ($origLat-($dist/69)) and ($origLat+($dist/69)) 
						AND (3956 * 2 * ASIN(SQRT( POWER(SIN(($origLat - spotter_archive.latitude)*pi()/180/2),2)+COS( $origLat *pi()/180)*COS(spotter_archive.latitude*pi()/180)*POWER(SIN(($origLon-spotter_archive.longitude)*pi()/180/2),2)))) < $dist".$orderby_query;
			} else {
				$query="SELECT spotter_output.*, 1.60935 * 3956 * 2 * ASIN(SQRT( POWER(SIN(($origLat - CAST(spotter_archive.latitude as double precision))*pi()/180/2),2)+COS( $origLat *pi()/180)*COS(CAST(spotter_archive.latitude as double precision)*pi()/180)*POWER(SIN(($origLon-CAST(spotter_archive.longitude as double precision))*pi()/180/2),2))) as distance 
						FROM spotter_archive,spotter_output".$filter_query." spotter_output.flightaware_id = spotter_archive.flightaware_id AND spotter_output.ident <> '' ".$additional_query."AND CAST(spotter_archive.longitude as double precision) between ($origLon-$dist/cos(radians($origLat))*69) and ($origLon+$dist/cos(radians($origLat))*69) and CAST(spotter_archive.latitude as double precision) between ($origLat-($dist/69)) and ($origLat+($dist/69)) 
						AND (3956 * 2 * ASIN(SQRT( POWER(SIN(($origLat - CAST(spotter_archive.latitude as double precision))*pi()/180/2),2)+COS( $origLat *pi()/180)*COS(CAST(spotter_archive.latitude as double precision)*pi()/180)*POWER(SIN(($origLon-CAST(spotter_archive.longitude as double precision))*pi()/180/2),2)))) < $dist".$filter_query.$orderby_query;
			}
		} else {		
			$query = "SELECT spotter_output.* FROM spotter_output".$filter_query." 
					".substr($additional_query,4)."
					".$orderby_query;
		}
		$spotter_array = $this->getDataFromDB($query, $query_values,$limit_query);
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
	public function getLatestSpotterData($limit = '', $sort = '', $filter = array())
	{
		global $global_query;
		
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
			$orderby_query = " ORDER BY spotter_output.date DESC";
		}

		$query  = $global_query.$filter_query." ".$orderby_query;

		$spotter_array = $this->getDataFromDB($query, array(),$limit_query,true);

		return $spotter_array;
	}


    /**
     * Gets all the spotter information based on a user's latitude and longitude
     *
     * @param $lat
     * @param $lng
     * @param $radius
     * @param $interval
     * @return array the spotter information
     */
	public function getLatestSpotterForLayar($lat, $lng, $radius, $interval)
	{
		date_default_timezone_set('UTC');
		$limit_query = '';
		if ($lat != "")
		{
			if (!is_numeric($lat))
			{
				return array();
			}
		}
        
		if ($lng != "")
		{
			if (!is_numeric($lng))
			{
				return array();
			}
		}
		
		if ($radius != "")
		{
			if (!is_numeric($radius))
			{
				return array();
			}
		}
    		$additional_query = '';
		if ($interval != "")
		{
			if (!is_string($interval))
			{
				return array();
			} else {
				if ($interval == "30m"){
					$additional_query = ' AND DATE_SUB(UTC_TIMESTAMP(),INTERVAL 30 MINUTE) <= $this_output.date ';
				} else if ($interval == "1h"){
					$additional_query = ' AND DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 HOUR) <= $this_output.date ';
				} else if ($interval == "3h"){
					$additional_query = ' AND DATE_SUB(UTC_TIMESTAMP(),INTERVAL 3 HOUR) <= $this_output.date ';
				} else if ($interval == "6h"){
					$additional_query = ' AND DATE_SUB(UTC_TIMESTAMP(),INTERVAL 6 HOUR) <= $this_output.date ';
				} else if ($interval == "12h"){
					$additional_query = ' AND DATE_SUB(UTC_TIMESTAMP(),INTERVAL 12 HOUR) <= $this_output.date ';
				} else if ($interval == "24h"){
					$additional_query = ' AND DATE_SUB(UTC_TIMESTAMP(),INTERVAL 24 HOUR) <= $this_output.date ';
				} else if ($interval == "7d"){
					$additional_query = ' AND DATE_SUB(UTC_TIMESTAMP(),INTERVAL 7 DAY) <= $this_output.date ';
				} else if ($interval == "30d"){
					$additional_query = ' AND DATE_SUB(UTC_TIMESTAMP(),INTERVAL 30 DAY) <= $this_output.date ';
				} 
			}
		}

		$query  = "SELECT spotter_output.*, ( 6371 * acos( cos( radians($lat) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians($lng) ) + sin( radians($lat) ) * sin( radians( latitude ) ) ) ) AS distance FROM spotter_output 
                   WHERE spotter_output.latitude <> '' 
				   AND spotter_output.longitude <> '' 
                   ".$additional_query."
                   HAVING distance < :radius  
				   ORDER BY distance";

		$spotter_array = $this->getDataFromDB($query, array(':radius' => $radius),$limit_query);

		return $spotter_array;
	}


    /**
     * Gets all the spotter information sorted by the newest aircraft type
     *
     * @param string $limit
     * @param string $sort
     * @param array $filter
     * @return array the spotter information
     */
	public function getNewestSpotterDataSortedByAircraftType($limit = '', $sort = '',$filter = array())
	{
		global $global_query;
		
		date_default_timezone_set('UTC');

		$filter_query = $this->getFilter($filter,true,true);

		$limit_query = '';
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
			$orderby_query = " ORDER BY spotter_output.date DESC ";
		}

		$query  = $global_query." ".$filter_query." spotter_output.aircraft_name <> '' GROUP BY spotter_output.aircraft_icao,spotter_output.ident,spotter_output.spotter_id, spotter_output.flightaware_id, spotter_output.registration,spotter_output.airline_name,spotter_output.airline_icao,spotter_output.airline_country,spotter_output.airline_type,spotter_output.aircraft_icao,spotter_output.aircraft_name,spotter_output.aircraft_manufacturer,spotter_output.departure_airport_icao,spotter_output.departure_airport_name,spotter_output.departure_airport_city,spotter_output.departure_airport_country,spotter_output.departure_airport_time,spotter_output.arrival_airport_icao,spotter_output.arrival_airport_name,spotter_output.arrival_airport_city,spotter_output.arrival_airport_country,spotter_output.arrival_airport_time,spotter_output.route_stop,spotter_output.date,spotter_output.latitude,spotter_output.longitude,spotter_output.waypoints,spotter_output.altitude,spotter_output.heading,spotter_output.ground_speed,spotter_output.highlight,spotter_output.squawk,spotter_output.ModeS,spotter_output.pilot_id,spotter_output.pilot_name,spotter_output.verticalrate,spotter_output.owner_name,spotter_output.format_source,spotter_output.source_name,spotter_output.ground,spotter_output.last_ground,spotter_output.last_seen,spotter_output.last_latitude,spotter_output.last_longitude,spotter_output.last_altitude,spotter_output.last_ground_speed,spotter_output.real_arrival_airport_icao,spotter_output.real_arrival_airport_time ".$orderby_query;

		$spotter_array = $this->getDataFromDB($query, array(), $limit_query);

		return $spotter_array;
	}

    /**
     * Gets all the spotter information sorted by the newest aircraft registration
     *
     * @param string $limit
     * @param string $sort
     * @param array $filter
     * @return array the spotter information
     */
	public function getNewestSpotterDataSortedByAircraftRegistration($limit = '', $sort = '', $filter = array())
	{
		global $global_query;
		
		date_default_timezone_set('UTC');
		$filter_query = $this->getFilter($filter,true,true);

		$limit_query = '';
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
			$orderby_query = " ORDER BY spotter_output.date DESC ";
		}

		$query  = $global_query." ".$filter_query." spotter_output.registration <> '' GROUP BY spotter_output.registration,spotter_output.ident,spotter_output.spotter_id, spotter_output.flightaware_id, spotter_output.registration,spotter_output.airline_name,spotter_output.airline_icao,spotter_output.airline_country,spotter_output.airline_type,spotter_output.aircraft_icao,spotter_output.aircraft_name,spotter_output.aircraft_manufacturer,spotter_output.departure_airport_icao,spotter_output.departure_airport_name,spotter_output.departure_airport_city,spotter_output.departure_airport_country,spotter_output.departure_airport_time,spotter_output.arrival_airport_icao,spotter_output.arrival_airport_name,spotter_output.arrival_airport_city,spotter_output.arrival_airport_country,spotter_output.arrival_airport_time,spotter_output.route_stop,spotter_output.date,spotter_output.latitude,spotter_output.longitude,spotter_output.waypoints,spotter_output.altitude,spotter_output.heading,spotter_output.ground_speed,spotter_output.highlight,spotter_output.squawk,spotter_output.ModeS,spotter_output.pilot_id,spotter_output.pilot_name,spotter_output.verticalrate,spotter_output.owner_name,spotter_output.format_source,spotter_output.source_name,spotter_output.ground,spotter_output.last_ground,spotter_output.last_seen,spotter_output.last_latitude,spotter_output.last_longitude,spotter_output.last_altitude,spotter_output.last_ground_speed,spotter_output.real_arrival_airport_icao,spotter_output.real_arrival_airport_time ".$orderby_query;

		$spotter_array = $this->getDataFromDB($query, array(), $limit_query);

		return $spotter_array;
	}


    /**
     * Gets all the spotter information sorted by the newest airline
     *
     * @param string $limit
     * @param string $sort
     * @param array $filter
     * @return array the spotter information
     */
	public function getNewestSpotterDataSortedByAirline($limit = '', $sort = '',$filter = array())
	{
		global $global_query;
		
		date_default_timezone_set('UTC');
		$filter_query = $this->getFilter($filter,true,true);
		
		$limit_query = '';
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
			$orderby_query = " ORDER BY spotter_output.date DESC ";
		}

		$query  = $global_query." ".$filter_query." spotter_output.airline_name <> '' GROUP BY spotter_output.airline_icao,spotter_output.ident,spotter_output.spotter_id, spotter_output.flightaware_id, spotter_output.registration,spotter_output.airline_name,spotter_output.airline_icao,spotter_output.airline_country,spotter_output.airline_type,spotter_output.aircraft_icao,spotter_output.aircraft_name,spotter_output.aircraft_manufacturer,spotter_output.departure_airport_icao,spotter_output.departure_airport_name,spotter_output.departure_airport_city,spotter_output.departure_airport_country,spotter_output.departure_airport_time,spotter_output.arrival_airport_icao,spotter_output.arrival_airport_name,spotter_output.arrival_airport_city,spotter_output.arrival_airport_country,spotter_output.arrival_airport_time,spotter_output.route_stop,spotter_output.date,spotter_output.latitude,spotter_output.longitude,spotter_output.waypoints,spotter_output.altitude,spotter_output.heading,spotter_output.ground_speed,spotter_output.highlight,spotter_output.squawk,spotter_output.ModeS,spotter_output.pilot_id,spotter_output.pilot_name,spotter_output.verticalrate,spotter_output.owner_name,spotter_output.format_source,spotter_output.source_name,spotter_output.ground,spotter_output.last_ground,spotter_output.last_seen,spotter_output.last_latitude,spotter_output.last_longitude,spotter_output.last_altitude,spotter_output.last_ground_speed,spotter_output.real_arrival_airport_icao,spotter_output.real_arrival_airport_time ".$orderby_query;

		$spotter_array = $this->getDataFromDB($query, array(), $limit_query);

		return $spotter_array;
	}


    /**
     * Gets all the spotter information sorted by the newest departure airport
     *
     * @param string $limit
     * @param string $sort
     * @param array $filter
     * @return array the spotter information
     */
	public function getNewestSpotterDataSortedByDepartureAirport($limit = '', $sort = '', $filter = array())
	{
		global $global_query;
		
		date_default_timezone_set('UTC');
		
		$filter_query = $this->getFilter($filter,true,true);
		
		$limit_query = '';
		
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
			$orderby_query = " ORDER BY spotter_output.date DESC ";
		}

		$query  = $global_query." ".$filter_query." spotter_output.departure_airport_name <> '' AND spotter_output.departure_airport_icao <> 'NA' GROUP BY spotter_output.departure_airport_icao,spotter_output.ident,spotter_output.spotter_id, spotter_output.flightaware_id, spotter_output.registration,spotter_output.airline_name,spotter_output.airline_icao,spotter_output.airline_country,spotter_output.airline_type,spotter_output.aircraft_icao,spotter_output.aircraft_name,spotter_output.aircraft_manufacturer,spotter_output.departure_airport_icao,spotter_output.departure_airport_name,spotter_output.departure_airport_city,spotter_output.departure_airport_country,spotter_output.departure_airport_time,spotter_output.arrival_airport_icao,spotter_output.arrival_airport_name,spotter_output.arrival_airport_city,spotter_output.arrival_airport_country,spotter_output.arrival_airport_time,spotter_output.route_stop,spotter_output.date,spotter_output.latitude,spotter_output.longitude,spotter_output.waypoints,spotter_output.altitude,spotter_output.heading,spotter_output.ground_speed,spotter_output.highlight,spotter_output.squawk,spotter_output.ModeS,spotter_output.pilot_id,spotter_output.pilot_name,spotter_output.verticalrate,spotter_output.owner_name,spotter_output.format_source,spotter_output.source_name,spotter_output.ground,spotter_output.last_ground,spotter_output.last_seen,spotter_output.last_latitude,spotter_output.last_longitude,spotter_output.last_altitude,spotter_output.last_ground_speed,spotter_output.real_arrival_airport_icao,spotter_output.real_arrival_airport_time ".$orderby_query;

		$spotter_array = $this->getDataFromDB($query, array(), $limit_query);

		return $spotter_array;
	}


    /**
     * Gets all the spotter information sorted by the newest arrival airport
     *
     * @param string $limit
     * @param string $sort
     * @param array $filter
     * @return array the spotter information
     */
	public function getNewestSpotterDataSortedByArrivalAirport($limit = '', $sort = '', $filter = array())
	{
		global $global_query;
		
		date_default_timezone_set('UTC');
		$filter_query = $this->getFilter($filter,true,true);
		$limit_query = '';
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
			$orderby_query = " ORDER BY spotter_output.date DESC ";
		}

		$query  = $global_query.$filter_query." spotter_output.arrival_airport_name <> '' AND spotter_output.arrival_airport_icao <> 'NA' AND spotter_output.arrival_airport_icao <> '' GROUP BY spotter_output.arrival_airport_icao,spotter_output.ident,spotter_output.spotter_id, spotter_output.flightaware_id, spotter_output.registration,spotter_output.airline_name,spotter_output.airline_icao,spotter_output.airline_country,spotter_output.airline_type,spotter_output.aircraft_icao,spotter_output.aircraft_name,spotter_output.aircraft_manufacturer,spotter_output.departure_airport_icao,spotter_output.departure_airport_name,spotter_output.departure_airport_city,spotter_output.departure_airport_country,spotter_output.departure_airport_time,spotter_output.arrival_airport_icao,spotter_output.arrival_airport_name,spotter_output.arrival_airport_city,spotter_output.arrival_airport_country,spotter_output.arrival_airport_time,spotter_output.route_stop,spotter_output.date,spotter_output.latitude,spotter_output.longitude,spotter_output.waypoints,spotter_output.altitude,spotter_output.heading,spotter_output.ground_speed,spotter_output.highlight,spotter_output.squawk,spotter_output.ModeS,spotter_output.pilot_id,spotter_output.pilot_name,spotter_output.verticalrate,spotter_output.owner_name,spotter_output.format_source,spotter_output.source_name,spotter_output.ground,spotter_output.last_ground,spotter_output.last_seen,spotter_output.last_latitude,spotter_output.last_longitude,spotter_output.last_altitude,spotter_output.last_ground_speed,spotter_output.real_arrival_airport_icao,spotter_output.real_arrival_airport_time ".$orderby_query;

		$spotter_array = $this->getDataFromDB($query, array(), $limit_query);

		return $spotter_array;
	}

    /**
     * Gets all the spotter information based on the spotter id
     *
     * @param string $id
     * @return array the spotter information
     */
	public function getSpotterDataByID($id = '')
	{
		global $global_query;
		
		date_default_timezone_set('UTC');
		if ($id == '') return array();
		$additional_query = "spotter_output.spotter_id = :id";
		$query_values = array(':id' => $id);
		//$query  = $global_query." WHERE spotter_output.ident <> '' ".$additional_query." ";
		$query  = $global_query." WHERE ".$additional_query." ";
		$spotter_array = $this->getDataFromDB($query,$query_values);
		return $spotter_array;
	}

	/**
	* Check if a ident is in block list
	*
	* @param String $ident the aircraft callsign
	* @return Boolean return true is in block list
	*
	*/
	public function checkIdentBlocked($ident = '')
	{
		date_default_timezone_set('UTC');
		$query = "SELECT count(*) as nb FROM aircraft_block WHERE callsign  = :callsign";
		$query_values = array(':callsign' => $ident);
		$sth = $this->db->prepare($query);
		$sth->execute($query_values);
		$row = $sth->fetchAll(PDO::FETCH_ASSOC);
		$sth->closeCursor();
		if ($row[0]['nb'] > 0) {
			return true;
		} else return false;
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
	public function getSpotterDataByIdent($ident = '', $limit = '', $sort = '', $filter = array())
	{
		global $global_query;
		
		$query_values = array();
		$limit_query = '';
		$filter_query = $this->getFilter($filter,true,true);
		if ($ident != "")
		{
			if (!is_string($ident))
			{
				return array();
			} else {
				$additional_query = " spotter_output.ident = :ident";
				$query_values = array(':ident' => $ident);
			}
		} else {
			$additional_query = " spotter_output.ident <> ''";
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
			$orderby_query = " ORDER BY spotter_output.date DESC";
		}
		$query = $global_query.$filter_query.$additional_query." ".$orderby_query;
		$spotter_array = $this->getDataFromDB($query, $query_values, $limit_query);
		return $spotter_array;
	}

    /**
     * Gets all the spotter information based on the owner
     *
     * @param string $owner
     * @param string $limit
     * @param string $sort
     * @param array $filter
     * @return array the spotter information
     */
	public function getSpotterDataByOwner($owner = '', $limit = '', $sort = '', $filter = array())
	{
		global $global_query;
		
		date_default_timezone_set('UTC');
		
		$query_values = array();
		$limit_query = '';
		$additional_query = '';
		if ($owner != "")
		{
			if (!is_string($owner))
			{
				return array();
			} else {
				$additional_query = " spotter_output.owner_name = :owner";
				$query_values = array(':owner' => $owner);
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
			if (isset($search_orderby_array[$sort]['sql'])) $orderby_query = $search_orderby_array[$sort]['sql'];
			else $orderby_query = " ORDER BY spotter_output.date DESC";
		} else {
			$orderby_query = " ORDER BY spotter_output.date DESC";
		}
		if ($additional_query == '') {
			$filter_query = $this->getFilter($filter,false,false);
		} else {
			$filter_query = $this->getFilter($filter,true,true);
		}
		$query = $global_query.$filter_query." ".$additional_query." ".$orderby_query;

		$spotter_array = $this->getDataFromDB($query, $query_values, $limit_query);

		return $spotter_array;
	}

    /**
     * Gets all the spotter information based on the pilot
     *
     * @param string $pilot
     * @param string $limit
     * @param string $sort
     * @param array $filter
     * @return array the spotter information
     */
	public function getSpotterDataByPilot($pilot = '', $limit = '', $sort = '', $filter = array())
	{
		global $global_query;
		
		date_default_timezone_set('UTC');
		
		$query_values = array();
		$limit_query = '';
		$additional_query = '';
		$filter_query = $this->getFilter($filter,true,true);
		if ($pilot != "")
		{
			$additional_query = " AND (spotter_output.pilot_name = :pilot OR spotter_output.pilot_id = :pilot)";
			$query_values = array(':pilot' => $pilot);
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
			$orderby_query = " ORDER BY spotter_output.date DESC";
		}

		$query = $global_query.$filter_query." spotter_output.pilot_name <> '' ".$additional_query." ".$orderby_query;

		$spotter_array = $this->getDataFromDB($query, $query_values, $limit_query);

		return $spotter_array;
	}


    /**
     * Gets all the spotter information based on the aircraft type
     *
     * @param string $aircraft_type
     * @param string $limit
     * @param string $sort
     * @param array $filter
     * @return array the spotter information
     */
	public function getSpotterDataByAircraft($aircraft_type = '', $limit = '', $sort = '', $filter = array())
	{
		global $global_query;
		
		date_default_timezone_set('UTC');
		
		$query_values = array();
		$limit_query = '';
		$additional_query = '';
		$filter_query = $this->getFilter($filter,true,true);
		
		if ($aircraft_type != "")
		{
			if (!is_string($aircraft_type))
			{
				return array();
			} else {
				$additional_query = " AND spotter_output.aircraft_icao = :aircraft_type";
				$query_values = array(':aircraft_type' => $aircraft_type);
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
			$orderby_query = " ORDER BY spotter_output.date DESC";
		}

		$query = $global_query.$filter_query." spotter_output.ident <> '' ".$additional_query." ".$orderby_query;

		$spotter_array = $this->getDataFromDB($query, $query_values, $limit_query);

		return $spotter_array;
	}


    /**
     * Gets all the spotter information based on the aircraft registration
     *
     * @param string $registration
     * @param string $limit
     * @param string $sort
     * @param array $filter
     * @return array the spotter information
     */
	public function getSpotterDataByRegistration($registration = '', $limit = '', $sort = '', $filter = array())
	{
		global $global_query;
		
		date_default_timezone_set('UTC');
		
		$query_values = array();
		$limit_query = '';
		$additional_query = '';
		
		if ($registration != "")
		{
			if (!is_string($registration))
			{
				return array();
			} else {
				$additional_query = " spotter_output.registration = :registration";
				$query_values = array(':registration' => $registration);
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
			$orderby_query = " ORDER BY spotter_output.date DESC";
		}
		$filter_query = $this->getFilter($filter,true,true);

		//$query = $global_query.$filter_query." spotter_output.ident <> '' ".$additional_query." ".$orderby_query;
		$query = $global_query.$filter_query." ".$additional_query." ".$orderby_query;

		$spotter_array = $this->getDataFromDB($query, $query_values, $limit_query);

		return $spotter_array;
	}


    /**
     * Gets all the spotter information based on the airline
     *
     * @param string $airline
     * @param string $limit
     * @param string $sort
     * @param array $filters
     * @return array the spotter information
     */
	public function getSpotterDataByAirline($airline = '', $limit = '', $sort = '',$filters = array())
	{
		global $global_query;
		
		date_default_timezone_set('UTC');

		$query_values = array();
		$limit_query = '';
		$additional_query = '';
		
		if ($airline != "")
		{
			if (!is_string($airline))
			{
				return array();
			} else {
				$additional_query = " spotter_output.airline_icao = :airline";
				$query_values = array(':airline' => $airline);
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
			$orderby_query = " ORDER BY spotter_output.date DESC";
		}
		if ($additional_query == '') {
			$filter_query = $this->getFilter($filters,false,false);
			$query = $global_query.$filter_query." ".$additional_query." ".$orderby_query;
		} else {
			$filter_query = $this->getFilter($filters,true,true);
			$query = $global_query.$filter_query." ".$additional_query." ".$orderby_query;
		}
		$spotter_array = $this->getDataFromDB($query, $query_values, $limit_query);

		return $spotter_array;
	}


    /**
     * Gets all the spotter information based on the airport
     *
     * @param string $airport
     * @param string $limit
     * @param string $sort
     * @param array $filters
     * @return array the spotter information
     */
	public function getSpotterDataByAirport($airport = '', $limit = '', $sort = '',$filters = array())
	{
		date_default_timezone_set('UTC');
		$query_values = array();
		$limit_query = '';
		$additional_query = '';
		$filter_query = $this->getFilter($filters,true,true);
		
		if ($airport != "" && $airport != 'NA')
		{
			if (!is_string($airport))
			{
				return array();
			} else {
				$additional_query .= "(SELECT spotter_output.spotter_id,spotter_output.flightaware_id,spotter_output.ident,spotter_output.registration,spotter_output.airline_name,spotter_output.airline_icao,spotter_output.airline_country,spotter_output.airline_type,spotter_output.aircraft_icao,spotter_output.aircraft_name,spotter_output.aircraft_manufacturer,spotter_output.departure_airport_icao,spotter_output.departure_airport_name,spotter_output.departure_airport_city,spotter_output.departure_airport_country,spotter_output.departure_airport_time,spotter_output.arrival_airport_icao,spotter_output.arrival_airport_name,spotter_output.arrival_airport_city,spotter_output.arrival_airport_country,spotter_output.arrival_airport_time,spotter_output.route_stop,spotter_output.date,spotter_output.latitude,spotter_output.longitude,spotter_output.waypoints,spotter_output.altitude,spotter_output.real_altitude,spotter_output.heading,spotter_output.ground_speed,spotter_output.highlight,spotter_output.squawk,spotter_output.ModeS,spotter_output.pilot_id,spotter_output.pilot_name,spotter_output.owner_name,spotter_output.verticalrate,spotter_output.format_source,spotter_output.source_name,spotter_output.ground,spotter_output.last_ground,spotter_output.last_seen,spotter_output.last_latitude,spotter_output.last_longitude,spotter_output.last_altitude,spotter_output.last_ground_speed,spotter_output.real_arrival_airport_icao,spotter_output.real_arrival_airport_time,spotter_output.real_departure_airport_icao,spotter_output.real_departure_airport_time 
				    FROM spotter_output
				    ".$filter_query." spotter_output.departure_airport_icao = :airport 
				  UNION 
				    SELECT spotter_output.spotter_id,spotter_output.flightaware_id,spotter_output.ident,spotter_output.registration,spotter_output.airline_name,spotter_output.airline_icao,spotter_output.airline_country,spotter_output.airline_type,spotter_output.aircraft_icao,spotter_output.aircraft_name,spotter_output.aircraft_manufacturer,spotter_output.departure_airport_icao,spotter_output.departure_airport_name,spotter_output.departure_airport_city,spotter_output.departure_airport_country,spotter_output.departure_airport_time,spotter_output.arrival_airport_icao,spotter_output.arrival_airport_name,spotter_output.arrival_airport_city,spotter_output.arrival_airport_country,spotter_output.arrival_airport_time,spotter_output.route_stop,spotter_output.date,spotter_output.latitude,spotter_output.longitude,spotter_output.waypoints,spotter_output.altitude,spotter_output.real_altitude,spotter_output.heading,spotter_output.ground_speed,spotter_output.highlight,spotter_output.squawk,spotter_output.ModeS,spotter_output.pilot_id,spotter_output.pilot_name,spotter_output.owner_name,spotter_output.verticalrate,spotter_output.format_source,spotter_output.source_name,spotter_output.ground,spotter_output.last_ground,spotter_output.last_seen,spotter_output.last_latitude,spotter_output.last_longitude,spotter_output.last_altitude,spotter_output.last_ground_speed,spotter_output.real_arrival_airport_icao,spotter_output.real_arrival_airport_time,spotter_output.real_departure_airport_icao,spotter_output.real_departure_airport_time 
				    FROM spotter_output 
				    ".$filter_query." spotter_output.arrival_airport_icao = :airport) AS spotter_output";
				$query_values = array(':airport' => $airport);
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
			}
		}
		
		if ($sort != "")
		{
			$search_orderby_array = $this->getOrderBy();
			$orderby_query = $search_orderby_array[$sort]['sql'];
		} else {
			$orderby_query = " ORDER BY date DESC";
		}
        $query = "SELECT * FROM ".$additional_query." ".$orderby_query;
		$spotter_array = $this->getDataFromDB($query, $query_values, $limit_query);

		return $spotter_array;
	}


    /**
     * Gets all the spotter information based on the date
     *
     * @param string $date
     * @param string $limit
     * @param string $sort
     * @param array $filter
     * @return array the spotter information
     */
	public function getSpotterDataByDate($date = '', $limit = '', $sort = '',$filter = array())
	{
		global $global_query, $globalTimezone, $globalDBdriver;
		
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
				$globalTimezone = 'UTC';
				$datetime = new DateTime($date);
				$offset = '+00:00';
			}
			if ($globalDBdriver == 'mysql') {
				if ($offset == '+00:00') {
					$additional_query = " AND DATE(spotter_output.date) = :date ";
					$query_values = array(':date' => $datetime->format('Y-m-d'));
				} else {
					$additional_query = " AND DATE(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) = :date ";
					$query_values = array(':date' => $datetime->format('Y-m-d'), ':offset' => $offset);
				}
			} elseif ($globalDBdriver == 'pgsql') {
				//$globalTimezone = 'UTC';
				$additional_query = " AND to_char(spotter_output.date AT TIME ZONE :timezone,'YYYY-mm-dd') = :date ";
				$query_values = array(':date' => $datetime->format('Y-m-d'), ':timezone' => $globalTimezone);
				//$additional_query = " AND to_char(spotter_output.date,'YYYY-mm-dd') = :date ";
				//$query_values = array(':date' => $datetime->format('Y-m-d'));
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
			$orderby_query = " ORDER BY spotter_output.date DESC";
		}

		$query = $global_query.$filter_query." ".substr($additional_query,4).$orderby_query;
		$spotter_array = $this->getDataFromDB($query, $query_values, $limit_query);
		return $spotter_array;
	}


    /**
     * Gets all the spotter information based on the country name
     *
     * @param string $country
     * @param string $limit
     * @param string $sort
     * @param array $filters
     * @return array the spotter information
     */
	public function getSpotterDataByCountry($country = '', $limit = '', $sort = '',$filters = array())
	{
		global $global_query;
		
		date_default_timezone_set('UTC');
		
		$query_values = array();
		$limit_query = '';
		$additional_query = '';
		$filter_query = $this->getFilter($filters,true,true);
		if ($country != "")
		{
			if (!is_string($country))
			{
				return array();
			} else {
				$additional_query .= " AND (spotter_output.departure_airport_country = :country OR spotter_output.arrival_airport_country = :country";
				$additional_query .= " OR spotter_output.airline_country = :country)";
				$query_values = array(':country' => $country);
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
			$orderby_query = " ORDER BY spotter_output.date DESC";
		}

		$query = $global_query.$filter_query." ".substr($additional_query,4)." ".$orderby_query;

		$spotter_array = $this->getDataFromDB($query, $query_values, $limit_query);

		return $spotter_array;
	}


    /**
     * Gets all the spotter information based on the manufacturer name
     *
     * @param string $aircraft_manufacturer
     * @param string $limit
     * @param string $sort
     * @param array $filters
     * @return array the spotter information
     */
	public function getSpotterDataByManufacturer($aircraft_manufacturer = '', $limit = '', $sort = '', $filters = array())
	{
		global $global_query;
		
		date_default_timezone_set('UTC');
		
		$query_values = array();
		$additional_query = '';
		$limit_query = '';
		$filter_query = $this->getFilter($filters,true,true);
		
		if ($aircraft_manufacturer != "")
		{
			if (!is_string($aircraft_manufacturer))
			{
				return array();
			} else {
				$additional_query .= " AND spotter_output.aircraft_manufacturer = :aircraft_manufacturer";
				$query_values = array(':aircraft_manufacturer' => $aircraft_manufacturer);
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
			$orderby_query = " ORDER BY spotter_output.date DESC";
		}

		$query = $global_query.$filter_query." spotter_output.ident <> '' ".$additional_query." ".$orderby_query;
		$spotter_array = $this->getDataFromDB($query, $query_values, $limit_query);
		return $spotter_array;
	}


    /**
     * Gets a list of all aircraft that take a route
     *
     * @param String $departure_airport_icao ICAO code of departure airport
     * @param String $arrival_airport_icao ICAO code of arrival airport
     * @param string $limit
     * @param string $sort
     * @param array $filters
     * @return array the spotter information
     */
	public function getSpotterDataByRoute($departure_airport_icao = '', $arrival_airport_icao = '', $limit = '', $sort = '', $filters = array())
	{
		$query_values = array();
		$additional_query = '';
		$limit_query = '';
		if ($departure_airport_icao != "")
		{
			if (!is_string($departure_airport_icao))
			{
				return array();
			} else {
				$departure_airport_icao = filter_var($departure_airport_icao,FILTER_SANITIZE_STRING);
				$additional_query .= " AND spotter_output.departure_airport_icao = :departure_airport_icao";
				//$additional_query .= " AND (spotter_output.departure_airport_icao = :departure_airport_icao AND spotter_output.real_departure_airport_icao IS NULL) OR spotter_output.real_departure_airport_icao = :departure_airport_icao";
				$query_values = array(':departure_airport_icao' => $departure_airport_icao);
			}
		}
		
		if ($arrival_airport_icao != "")
		{
			if (!is_string($arrival_airport_icao))
			{
				return array();
			} else {
				$arrival_airport_icao = filter_var($arrival_airport_icao,FILTER_SANITIZE_STRING);
				$additional_query .= " AND spotter_output.arrival_airport_icao = :arrival_airport_icao";
				//$additional_query .= " AND ((spotter_output.arrival_airport_icao = :arrival_airport_icao AND spotter_output.real_arrival_airport_icao IS NULL) OR spotter_output.real_arrival_airport_icao = :arrival_airport_icao)";
				$query_values = array_merge($query_values,array(':arrival_airport_icao' => $arrival_airport_icao));
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
			$orderby_query = " ORDER BY spotter_output.date DESC";
		}

		$query = "SELECT spotter_output.spotter_id,spotter_output.flightaware_id,spotter_output.ident,spotter_output.registration,spotter_output.airline_name,spotter_output.airline_icao,spotter_output.aircraft_icao,spotter_output.aircraft_name,spotter_output.aircraft_manufacturer,spotter_output.departure_airport_icao,spotter_output.departure_airport_name,spotter_output.departure_airport_city,spotter_output.departure_airport_country,spotter_output.departure_airport_time,spotter_output.arrival_airport_icao,spotter_output.arrival_airport_name,spotter_output.arrival_airport_city,spotter_output.arrival_airport_country,spotter_output.arrival_airport_time,spotter_output.route_stop,spotter_output.date,spotter_output.highlight,spotter_output.squawk,spotter_output.ModeS,spotter_output.pilot_id,spotter_output.pilot_name,spotter_output.owner_name,spotter_output.format_source,spotter_output.source_name,spotter_output.real_arrival_airport_icao,spotter_output.real_arrival_airport_time,spotter_output.real_departure_airport_icao,spotter_output.real_departure_airport_time FROM spotter_output";
		if ($additional_query != '') {
			$filter_query = $this->getFilter($filters,true,true);
		} else {
			$filter_query = $this->getFilter($filters,false,false);
		}
		$query .= $filter_query." ".substr($additional_query,4)." ".$orderby_query;
		$spotter_array = $this->getDataFromDB($query, $query_values, $limit_query);
		return $spotter_array;
	}


    /**
     * Gets all the spotter information based on the special column in the table
     *
     * @param string $limit
     * @param string $sort
     * @param array $filter
     * @return array the spotter information
     */
	public function getSpotterDataByHighlight($limit = '', $sort = '', $filter = array())
	{
		global $global_query;
		
		date_default_timezone_set('UTC');
		$filter_query = $this->getFilter($filter,true,true);
		$limit_query = '';
		
		if ($limit != "")
		{
			$limit_array = explode(",", $limit);
			
			$limit_array[0] = filter_var($limit_array[0],FILTER_SANITIZE_NUMBER_INT);
			$limit_array[1] = filter_var($limit_array[1],FILTER_SANITIZE_NUMBER_INT);
			
			if ($limit_array[0] >= 0 && $limit_array[1] >= 0)
			{
					$limit_query = " LIMIT ".$limit_array[1]." OFFSET ".$limit_array[0];
			}
		}
		
		if ($sort != "")
		{
			$search_orderby_array = $this->getOrderBy();
			$orderby_query = $search_orderby_array[$sort]['sql'];
		} else {
			$orderby_query = " ORDER BY spotter_output.date DESC";
		}

		$query  = $global_query.$filter_query." spotter_output.highlight <> '' ".$orderby_query;

		$spotter_array = $this->getDataFromDB($query, array(), $limit_query);

		return $spotter_array;
	}

    /**
     * Gets all the highlight based on a aircraft registration
     *
     * @param $registration
     * @param array $filter
     * @return String the highlight text
     */
	public function getHighlightByRegistration($registration,$filter = array())
	{
		global $global_query;
		
		date_default_timezone_set('UTC');
		$filter_query = $this->getFilter($filter,true,true);
		$registration = filter_var($registration,FILTER_SANITIZE_STRING);
		
		$query  = $global_query.$filter_query." spotter_output.highlight <> '' AND spotter_output.registration = :registration";
		$sth = $this->db->prepare($query);
		$sth->execute(array(':registration' => $registration));

		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$highlight = $row['highlight'];
		}
		if (isset($highlight)) return $highlight;
		else return '';
	}

	
	/**
	* Gets the squawk usage from squawk code
	*
	* @param String $squawk squawk code
	* @param String $country country
	* @return String usage
	*
	*/
	public function getSquawkUsage($squawk = '',$country = 'FR')
	{
		
		$squawk = filter_var($squawk,FILTER_SANITIZE_STRING);
		$country = filter_var($country,FILTER_SANITIZE_STRING);

		$query  = "SELECT squawk.* FROM squawk WHERE squawk.code = :squawk AND squawk.country = :country LIMIT 1";
		$query_values = array(':squawk' => ltrim($squawk,'0'), ':country' => $country);
		
		$sth = $this->db->prepare($query);
		$sth->execute($query_values);
    
		$row = $sth->fetch(PDO::FETCH_ASSOC);
		$sth->closeCursor();
		if (isset($row['usage'])) {
			return $row['usage'];
		} else return '';
	}

	/**
	* Gets the airport icao from the iata
	*
	* @param String $airport_iata the iata code of the airport
	* @return String airport iata
	*
	*/
	public function getAirportIcao($airport_iata = '')
	{
		
		$airport_iata = filter_var($airport_iata,FILTER_SANITIZE_STRING);

		$query  = "SELECT airport.* FROM airport WHERE airport.iata = :airport LIMIT 1";
		$query_values = array(':airport' => $airport_iata);
		
		$sth = $this->db->prepare($query);
		$sth->execute($query_values);
		
		$row = $sth->fetch(PDO::FETCH_ASSOC);
		$sth->closeCursor();
		if (count($row) > 0) {
			return $row['icao'];
		} else return '';
	}

	/**
	* Gets the airport distance
	*
	* @param String $airport_icao the icao code of the airport
	* @param Float $latitude the latitude
	* @param Float $longitude the longitude
	* @return Float distance to the airport
	*
	*/
	public function getAirportDistance($airport_icao,$latitude,$longitude)
	{
		
		$airport_icao = filter_var($airport_icao,FILTER_SANITIZE_STRING);

		$query  = "SELECT airport.latitude, airport.longitude FROM airport WHERE airport.icao = :airport LIMIT 1";
		$query_values = array(':airport' => $airport_icao);
		$sth = $this->db->prepare($query);
		$sth->execute($query_values);
		$row = $sth->fetchAll(PDO::FETCH_ASSOC);
		if (count($row) > 0) {
			$airport_latitude = $row[0]['latitude'];
			$airport_longitude = $row[0]['longitude'];
			$Common = new Common();
			return $Common->distance($latitude,$longitude,$airport_latitude,$airport_longitude);
		} else return '';
	}
	
	/**
	* Gets the airport info based on the icao
	*
	* @param String $airport the icao code of the airport
	* @return array airport information
	*
	*/
	public function getAllAirportInfo($airport = '')
	{
		
		$airport = filter_var($airport,FILTER_SANITIZE_STRING);

		$query_values = array();
		if ($airport == 'NA') {
			return array(array('name' => 'Not available','city' => 'N/A', 'country' => 'N/A','iata' => 'NA','icao' => 'NA','altitude' => NULL,'latitude' => 0,'longitude' => 0,'type' => 'NA','home_link' => '','wikipedia_link' => '','image_thumb' => '', 'image' => ''));
		} elseif ($airport == '') {
			$query  = "SELECT airport.name, airport.city, airport.country, airport.iata, airport.icao, airport.latitude, airport.longitude, airport.altitude, airport.type, airport.home_link, airport.wikipedia_link, airport.image_thumb, airport.image, airport.diagram_pdf, airport.diagram_png FROM airport";
		} else {
			$query  = "SELECT airport.name, airport.city, airport.country, airport.iata, airport.icao, airport.latitude, airport.longitude, airport.altitude, airport.type, airport.home_link, airport.wikipedia_link, airport.image_thumb, airport.image, airport.diagram_pdf, airport.diagram_png FROM airport WHERE airport.icao = :airport LIMIT 1";
			$query_values = array(':airport' => $airport);
		}
		
		$sth = $this->db->prepare($query);
		$sth->execute($query_values);
		/*
		$airport_array = array();
		$temp_array = array();
		
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['name'] = $row['name'];
			$temp_array['city'] = $row['city'];
			$temp_array['country'] = $row['country'];
			$temp_array['iata'] = $row['iata'];
			$temp_array['icao'] = $row['icao'];
			$temp_array['latitude'] = $row['latitude'];
			$temp_array['longitude'] = $row['longitude'];
			$temp_array['altitude'] = $row['altitude'];
			$temp_array['home_link'] = $row['home_link'];
			$temp_array['wikipedia_link'] = $row['wikipedia_link'];
			$temp_array['image'] = $row['image'];
			$temp_array['image_thumb'] = $row['image_thumb'];

			$airport_array[] = $temp_array;
		}

		return $airport_array;
		*/
		return $sth->fetchAll(PDO::FETCH_ASSOC);
	}
	
	/**
	* Gets the airport info based on the country
	*
	* @param array $countries Airports countries
	* @return array airport information
	*
	*/
	public function getAllAirportInfobyCountry($countries)
	{
		$lst_countries = '';
		foreach ($countries as $country) {
			$country = filter_var($country,FILTER_SANITIZE_STRING);
			if ($lst_countries == '') {
				$lst_countries = "'".$country."'";
			} else {
				$lst_countries .= ",'".$country."'";
			}
		}
		$query  = "SELECT airport.* FROM airport WHERE airport.country IN (".$lst_countries.")";
		
		$sth = $this->db->prepare($query);
		$sth->execute();
    
		$airport_array = array();
		$temp_array = array();
		
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['name'] = $row['name'];
			$temp_array['city'] = $row['city'];
			$temp_array['country'] = $row['country'];
			$temp_array['iata'] = $row['iata'];
			$temp_array['icao'] = $row['icao'];
			$temp_array['latitude'] = $row['latitude'];
			$temp_array['longitude'] = $row['longitude'];
			$temp_array['altitude'] = $row['altitude'];

			$airport_array[] = $temp_array;
		}

		return $airport_array;
	}
	
	/**
	* Gets airports info based on the coord
	*
	* @param array $coord Airports longitude min,latitude min, longitude max, latitude max
	* @return array airport information
	*
	*/
	public function getAllAirportInfobyCoord($coord)
	{
		global $globalDBdriver;
		if (is_array($coord)) {
			$minlong = filter_var($coord[0],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
			$minlat = filter_var($coord[1],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
			$maxlong = filter_var($coord[2],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
			$maxlat = filter_var($coord[3],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
		} else return array();
		if ($globalDBdriver == 'mysql') {
			$query  = "SELECT airport.* FROM airport WHERE airport.latitude BETWEEN ".$minlat." AND ".$maxlat." AND airport.longitude BETWEEN ".$minlong." AND ".$maxlong." AND airport.type != 'closed'";
		} else {
			$query  = "SELECT airport.* FROM airport WHERE CAST(airport.latitude AS FLOAT) BETWEEN ".$minlat." AND ".$maxlat." AND CAST(airport.longitude AS FLOAT) BETWEEN ".$minlong." AND ".$maxlong." AND airport.type != 'closed'";
		}
		$sth = $this->db->prepare($query);
		$sth->execute();
    
		$airport_array = array();
		
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array = $row;

			$airport_array[] = $temp_array;
		}

		return $airport_array;
	}

	/**
	* Gets waypoints info based on the coord
	*
	* @param array $coord waypoints coord
	* @return array airport information
	*
	*/
	public function getAllWaypointsInfobyCoord($coord)
	{
		if (is_array($coord)) {
			$minlong = filter_var($coord[0],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
			$minlat = filter_var($coord[1],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
			$maxlong = filter_var($coord[2],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
			$maxlat = filter_var($coord[3],FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
		} else return array();
		//$query  = "SELECT waypoints.* FROM waypoints WHERE waypoints.latitude_begin BETWEEN ".$minlat." AND ".$maxlat." AND waypoints.longitude_begin BETWEEN ".$minlong." AND ".$maxlong;
		$query  = "SELECT waypoints.* FROM waypoints WHERE (waypoints.latitude_begin BETWEEN ".$minlat." AND ".$maxlat." AND waypoints.longitude_begin BETWEEN ".$minlong." AND ".$maxlong.") OR (waypoints.latitude_end BETWEEN ".$minlat." AND ".$maxlat." AND waypoints.longitude_end BETWEEN ".$minlong." AND ".$maxlong.")";
		//$query  = "SELECT waypoints.* FROM waypoints";
		//$query  = "SELECT waypoints.* FROM waypoints INNER JOIN (SELECT waypoints.* FROM waypoints WHERE waypoints.latitude_begin BETWEEN ".$minlat." AND ".$maxlat." AND waypoints.longitude_begin BETWEEN ".$minlong." AND ".$maxlong.") w ON w.name_end = waypoints.name_begin OR w.name_begin = waypoints.name_begin OR w.name_begin = waypoints.name_end OR w.name_end = waypoints.name_end";
		//$query = "SELECT * FROM waypoints LEFT JOIN waypoints w ON waypoints.name_end = w.name_begin WHERE waypoints.latitude_begin BETWEEN ".$minlat." AND ".$maxlat." AND waypoints.longitude_begin BETWEEN ".$minlong." AND ".$maxlong;
		//$query = "SELECT z.name_begin as name_begin, z.name_end as name_end, z.latitude_begin as latitude_begin, z.longitude_begin as longitude_begin, z.latitude_end as latitude_end, z.longitude_end as longitude_end, z.segment_name as segment_name, w.name_end as name_end_seg2, w.latitude_end as latitude_end_seg2, w.longitude_end as longitude_end_seg2, w.segment_name as segment_name_seg2 FROM waypoints z INNER JOIN waypoints w ON z.name_end = w.name_begin WHERE z.latitude_begin BETWEEN ".$minlat." AND ".$maxlat." AND z.longitude_begin BETWEEN ".$minlong." AND ".$maxlong;
		//echo $query;
		
		$sth = $this->db->prepare($query);
		$sth->execute();
    
		$waypoints_array = array();
		
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array = $row;

			$waypoints_array[] = $temp_array;
		}

		return $waypoints_array;
	}


    /**
     * Gets the airline info based on the icao code or iata code
     *
     * @param String $airline_icao the iata code of the airport
     * @param null $fromsource
     * @return array airport information
     */
	public function getAllAirlineInfo($airline_icao, $fromsource = NULL)
	{
		global $globalUseRealAirlines, $globalNoAirlines;
		if (isset($globalUseRealAirlines) && $globalUseRealAirlines) $fromsource = NULL;
		$airline_icao = strtoupper(filter_var($airline_icao,FILTER_SANITIZE_STRING));
		//if ($airline_icao == 'NA' || $airline_icao == 'OGN' || (isset($globalNoAirlines) && $globalNoAirlines)) {
		if ($airline_icao == 'NA' || (isset($globalNoAirlines) && $globalNoAirlines)) {
			$airline_array = array();
			$airline_array[] = array('name' => 'Not Available','iata' => 'NA', 'icao' => 'NA', 'callsign' => '', 'country' => 'NA', 'type' =>'');
			return $airline_array;
		} else {
			if (strlen($airline_icao) == 2) {
				if ($fromsource === NULL) {
					$query  = "SELECT airlines.name, airlines.iata, airlines.icao, airlines.callsign, airlines.country, airlines.type, airlines.home_link, airlines.wikipedia_link, airlines.ban_eu FROM airlines WHERE airlines.iata = :airline_icao AND airlines.active = 'Y' AND airlines.forsource IS NULL LIMIT 1";
				} else {
					$query  = "SELECT airlines.name, airlines.iata, airlines.icao, airlines.callsign, airlines.country, airlines.type, airlines.home_link, airlines.wikipedia_link, airlines.ban_eu FROM airlines WHERE airlines.iata = :airline_icao AND airlines.active = 'Y' AND airlines.forsource = :fromsource LIMIT 1";
				}
			} else {
				if ($fromsource === NULL) {
					$query  = "SELECT airlines.name, airlines.iata, airlines.icao, airlines.callsign, airlines.country, airlines.type, airlines.home_link, airlines.wikipedia_link, airlines.ban_eu FROM airlines WHERE airlines.icao = :airline_icao AND airlines.active = 'Y' AND airlines.forsource IS NULL LIMIT 1";
				} else {
					$query  = "SELECT airlines.name, airlines.iata, airlines.icao, airlines.callsign, airlines.country, airlines.type, airlines.home_link, airlines.wikipedia_link, airlines.ban_eu FROM airlines WHERE airlines.icao = :airline_icao AND airlines.active = 'Y' AND airlines.forsource = :fromsource LIMIT 1";
				}
			}
			
			$sth = $this->db->prepare($query);
			if ($fromsource === NULL) {
				$sth->execute(array(':airline_icao' => $airline_icao));
			} else {
				$sth->execute(array(':airline_icao' => $airline_icao,':fromsource' => $fromsource));
			}
                        /*
			$airline_array = array();
			$temp_array = array();
		
			while($row = $sth->fetch(PDO::FETCH_ASSOC))
			{
				$temp_array['name'] = $row['name'];
				$temp_array['iata'] = $row['iata'];
				$temp_array['icao'] = $row['icao'];
				$temp_array['callsign'] = $row['callsign'];
				$temp_array['country'] = $row['country'];
				$temp_array['type'] = $row['type'];
				$airline_array[] = $temp_array;
			}
			return $airline_array;
			*/
			$result = $sth->fetchAll(PDO::FETCH_ASSOC);
			if (empty($result) && $fromsource !== NULL) {
				/*
				$query = 'SELECT COUNT(*) AS nb FROM airlines WHERE forsource = :fromsource';
				$sth = $this->db->prepare($query);
				$sth->execute(array(':fromsource' => $fromsource));
				$row = $sth->fetch(PDO::FETCH_ASSOC);
				$sth->closeCursor();
				if ($row['nb'] == 0) $result = $this->getAllAirlineInfo($airline_icao);
				*/
				$result = $this->getAllAirlineInfo($airline_icao);
			}
			return $result;
		}
	}

    /**
     * Gets the airline info based on the airline name
     *
     * @param String $airline_name the name of the airline
     * @param null $fromsource
     * @return array airline information
     */
	public function getAllAirlineInfoByName($airline_name, $fromsource = NULL)
	{
		global $globalUseRealAirlines, $globalNoAirlines;
		if (isset($globalNoAirlines) && $globalNoAirlines) return array();
		if (isset($globalUseRealAirlines) && $globalUseRealAirlines) $fromsource = NULL;
		$airline_name = strtolower(filter_var($airline_name,FILTER_SANITIZE_STRING));
		$query  = "SELECT airlines.name, airlines.iata, airlines.icao, airlines.callsign, airlines.country, airlines.type FROM airlines WHERE lower(airlines.name) = :airline_name AND airlines.active = 'Y' AND airlines.forsource IS NULL LIMIT 1";
		$sth = $this->db->prepare($query);
		if ($fromsource === NULL) {
			$sth->execute(array(':airline_name' => $airline_name));
		} else {
			$sth->execute(array(':airline_name' => $airline_name,':fromsource' => $fromsource));
		}
		$result = $sth->fetchAll(PDO::FETCH_ASSOC);
		if (empty($result) && $fromsource !== NULL) {
			$query = 'SELECT COUNT(*) AS nb FROM airlines WHERE forsource = :fromsource';
			$sth = $this->db->prepare($query);
			$sth->execute(array(':fromsource' => $fromsource));
			$row = $sth->fetch(PDO::FETCH_ASSOC);
			$sth->closeCursor();
			if ($row['nb'] == 0) $result = $this->getAllAirlineInfoByName($airline_name);
		}
		return $result;
	}
	
	
	
	/**
	* Gets the aircraft info based on the aircraft type
	*
	* @param String $aircraft_type the aircraft type
	* @return array aircraft information
	*
	*/
	public function getAllAircraftInfo($aircraft_type)
	{
		$aircraft_type = filter_var($aircraft_type,FILTER_SANITIZE_STRING);

		if ($aircraft_type == 'NA') {
			return array(array('icao' => 'NA','type' => 'Not Available', 'manufacturer' => 'Not Available', 'aircraft_shadow' => NULL));
		}
		$query  = "SELECT aircraft.icao, aircraft.type,aircraft.manufacturer,aircraft.aircraft_shadow, aircraft.official_page, aircraft.aircraft_description, aircraft.engine_type, aircraft.engine_count, aircraft.wake_category FROM aircraft WHERE aircraft.icao = :aircraft_type LIMIT 1";
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':aircraft_type' => $aircraft_type));
		/*
		$aircraft_array = array();
		$temp_array = array();
		
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array = array();
			$temp_array['icao'] = $row['icao'];
			$temp_array['type'] = $row['type'];
			$temp_array['manufacturer'] = $row['manufacturer'];
			$temp_array['aircraft_shadow'] = $row['aircraft_shadow'];

			$aircraft_array[] = $temp_array;
		}
		return $aircraft_array;
		*/
		return $sth->fetchAll(PDO::FETCH_ASSOC);
	}

	/**
	* Gets the aircraft icao based on the aircraft name/type
	*
	* @param String $aircraft_type the aircraft type
	* @return String aircraft information
	*
	*/
	public function getAircraftIcao($aircraft_type)
	{
		$aircraft_type = filter_var($aircraft_type,FILTER_SANITIZE_STRING);
		$all_aircraft = array('737-300' => 'B733',
				'777-200' => 'B772',
				'777-200ER' => 'B772',
				'777-300ER' => 'B77W',
				'c172p' => 'C172',
				'aerostar' => 'AEST',
				'A320-211' => 'A320',
				'747-8i' => 'B748',
				'A380' => 'A388');
		if (isset($all_aircraft[$aircraft_type])) return $all_aircraft[$aircraft_type];

		$query  = "SELECT aircraft.icao FROM aircraft WHERE aircraft.type LIKE :saircraft_type OR aircraft.type = :aircraft_type OR aircraft.icao = :aircraft_type LIMIT 1";
		$aircraft_type = strtoupper($aircraft_type);
		$sth = $this->db->prepare($query);
		$sth->execute(array(':saircraft_type' => '%'.$aircraft_type.'%',':aircraft_type' => $aircraft_type,));
		$result = $sth->fetchAll(PDO::FETCH_ASSOC);
		if (isset($result[0]['icao'])) return $result[0]['icao'];
		else return '';
	}

    /**
     * Gets the aircraft info based on the aircraft modes
     *
     * @param String $aircraft_modes the aircraft ident (hex)
     * @param string $source_type
     * @return String aircraft type
     */
	public function getAllAircraftType($aircraft_modes,$source_type = '')
	{
		$aircraft_modes = filter_var($aircraft_modes,FILTER_SANITIZE_STRING);
		$source_type = filter_var($source_type,FILTER_SANITIZE_STRING);

		if ($source_type == '' || $source_type == 'modes') {
			$query  = "SELECT aircraft_modes.ICAOTypeCode FROM aircraft_modes WHERE aircraft_modes.ModeS = :aircraft_modes AND aircraft_modes.source_type = 'modes' ORDER BY FirstCreated DESC LIMIT 1";
		} else {
			$query  = "SELECT aircraft_modes.ICAOTypeCode FROM aircraft_modes WHERE aircraft_modes.ModeS = :aircraft_modes AND aircraft_modes.source_type = 'flarm' ORDER BY FirstCreated DESC LIMIT 1";
		}
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':aircraft_modes' => $aircraft_modes));

		$row = $sth->fetch(PDO::FETCH_ASSOC);
		$sth->closeCursor();
		if (isset($row['icaotypecode'])) {
			$icao = $row['icaotypecode'];
			if (isset($this->aircraft_correct_icaotype[$icao])) $icao = $this->aircraft_correct_icaotype[$icao];
			return $icao;
		} elseif ($source_type == 'flarm') {
			return $this->getAllAircraftType($aircraft_modes);
		} else  return '';
	}

	/**
	* Gets the aircraft info based on the aircraft registration
	*
	* @param String $registration the aircraft registration
	* @return String aircraft type
	*
	*/
	public function getAllAircraftTypeByRegistration($registration)
	{
		$registration = filter_var($registration,FILTER_SANITIZE_STRING);

		$query  = "SELECT aircraft_modes.ICAOTypeCode FROM aircraft_modes WHERE aircraft_modes.registration = :registration ORDER BY FirstCreated DESC LIMIT 1";
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':registration' => $registration));

		$row = $sth->fetch(PDO::FETCH_ASSOC);
		$sth->closeCursor();
		if (isset($row['icaotypecode'])) {
			return $row['icaotypecode'];
		} else return '';
	}

    /**
     * Gets the spotter_id and flightaware_id based on the aircraft registration
     *
     * @param String $registration the aircraft registration
     * @param bool $limit
     * @return array spotter_id and flightaware_id
     */
	public function getAllIDByRegistration($registration, $limit = false)
	{
		$registration = filter_var($registration,FILTER_SANITIZE_STRING);

		$query  = "SELECT spotter_id,flightaware_id, date FROM spotter_output WHERE spotter_output.registration = :registration ORDER BY spotter_id DESC";
		if ($limit) $query .= " LIMIT 1";
		$sth = $this->db->prepare($query);
		$sth->execute(array(':registration' => $registration));

		$idarray = array();
		while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
			$date = $row['date'];
			$idarray[$date] = array('flightaware_id' => $row['flightaware_id'],'spotter_id' => $row['spotter_id']);
		}
		return $idarray;
	}

	/**
	* Gets correct aircraft operator code
	*
	* @param String $operator the aircraft operator code (callsign)
	* @return String aircraft operator code
	*
	*/
	public function getOperator($operator)
	{
		$operator = filter_var($operator,FILTER_SANITIZE_STRING);
		$query  = "SELECT translation.operator_correct FROM translation WHERE translation.operator = :operator LIMIT 1";
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':operator' => $operator));

		$row = $sth->fetch(PDO::FETCH_ASSOC);
		$sth->closeCursor();
		if (isset($row['operator_correct'])) {
			return $row['operator_correct'];
		} else return $operator;
	}

	/**
	* Gets the aircraft route based on the aircraft callsign
	*
	* @param String $callsign the aircraft callsign
	* @return array aircraft type
	*
	*/
	public function getRouteInfo($callsign)
	{
		$callsign = filter_var($callsign,FILTER_SANITIZE_STRING);
                if ($callsign == '') return array();
		$query  = "SELECT routes.Operator_ICAO, routes.FromAirport_ICAO, routes.ToAirport_ICAO, routes.RouteStop, routes.FromAirport_Time, routes.ToAirport_Time FROM routes WHERE CallSign = :callsign LIMIT 1";
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':callsign' => $callsign));

		$row = $sth->fetch(PDO::FETCH_ASSOC);
		$sth->closeCursor();
		if (is_array($row)) {
			return $row;
		} else return array();
	}

    /**
     * Gets the aircraft info based on the aircraft registration
     *
     * @param String $registration the aircraft registration
     * @param bool $limit
     * @return array aircraft information
     */
	public function getAircraftInfoByRegistration($registration, $limit = true)
	{
		$registration = filter_var($registration,FILTER_SANITIZE_STRING);

		$query  = "SELECT spotter_output.aircraft_icao, spotter_output.aircraft_name, spotter_output.aircraft_manufacturer, spotter_output.airline_icao FROM spotter_output WHERE spotter_output.registration = :registration";
		if ($limit) $query .= " LIMIT 200";
		$sth = $this->db->prepare($query);
		$sth->execute(array(':registration' => $registration));

		$aircraft_array = array();
		$temp_array = array();
		
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airline_icao'] = $row['airline_icao'];
			$temp_array['aircraft_icao'] = $row['aircraft_icao'];
			$temp_array['aircraft_name'] = $row['aircraft_name'];
			$temp_array['aircraft_manufacturer'] = $row['aircraft_manufacturer'];

			$aircraft_array[] = $temp_array;
		}

		return $aircraft_array;
	}
	
	/**
	* Gets the aircraft owner & base based on the aircraft registration
	*
	* @param String $registration the aircraft registration
	* @return array aircraft information
	*
	*/
	public function getAircraftOwnerByRegistration($registration)
	{
		$registration = filter_var($registration,FILTER_SANITIZE_STRING);
		$Connection = new Connection($this->db);
		if ($Connection->tableExists('aircraft_owner')) {
			$query  = "SELECT aircraft_owner.base, aircraft_owner.owner, aircraft_owner.date_first_reg FROM aircraft_owner WHERE registration = :registration LIMIT 1";
			$sth = $this->db->prepare($query);
			$sth->execute(array(':registration' => $registration));
			$result = $sth->fetch(PDO::FETCH_ASSOC);
			$sth->closeCursor();
			return $result;
		} else return array();
	}
	
	/**
	* Gets all flights (but with only little info)
	*
	* @return array basic flight information
	*
	*/
	public function getAllFlightsforSitemap()
	{
		//$query  = "SELECT spotter_output.spotter_id, spotter_output.ident, spotter_output.airline_name, spotter_output.aircraft_name, spotter_output.aircraft_icao FROM spotter_output ORDER BY LIMIT ";
		$query  = "SELECT spotter_output.spotter_id FROM spotter_output ORDER BY spotter_id DESC LIMIT 200 OFFSET 0";
		
		$sth = $this->db->prepare($query);
		$sth->execute();
                  /*
		$flight_array = array();
		$temp_array = array();
		
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['spotter_id'] = $row['spotter_id'];
//			$temp_array['ident'] = $row['ident'];
//			$temp_array['airline_name'] = $row['airline_name'];
//			$temp_array['aircraft_type'] = $row['aircraft_icao'];
//			$temp_array['aircraft_name'] = $row['aircraft_name'];
			//$temp_array['image'] = $row['image'];

			$flight_array[] = $temp_array;
		}

		return $flight_array;
		*/
		return $sth->fetchAll(PDO::FETCH_ASSOC);
	}
  
	/**
	* Gets a list of all aircraft manufacturers
	*
	* @return array list of aircraft types
	*
	*/
	public function getAllManufacturers()
	{
		/*
		$query  = "SELECT DISTINCT spotter_output.aircraft_manufacturer AS aircraft_manufacturer
								FROM spotter_output
								WHERE spotter_output.aircraft_manufacturer <> '' 
								ORDER BY spotter_output.aircraft_manufacturer ASC";
		  */
		
		$query = "SELECT DISTINCT manufacturer AS aircraft_manufacturer FROM aircraft WHERE manufacturer <> '' ORDER BY manufacturer ASC";
		$sth = $this->db->prepare($query);
		$sth->execute();

		$manufacturer_array = array();
		$temp_array = array();
		
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['aircraft_manufacturer'] = $row['aircraft_manufacturer'];

			$manufacturer_array[] = $temp_array;
		}

		return $manufacturer_array;
	}
  
    /**
	* Gets a list of all aircraft types
	*
	* @return array list of aircraft types
	*
	*/
	public function getAllAircraftTypes($filters = array())
	{
		/*
		$query  = "SELECT DISTINCT spotter_output.aircraft_icao AS aircraft_icao, spotter_output.aircraft_name AS aircraft_name
								FROM spotter_output  
								WHERE spotter_output.aircraft_icao <> '' 
								ORDER BY spotter_output.aircraft_name ASC";
								
		*/
		//$filter_query = $this->getFilter($filters,true,true);
		//$query = "SELECT DISTINCT icao AS aircraft_icao, type AS aircraft_name, manufacturer AS aircraft_manufacturer FROM aircraft".$filter_query." icao <> '' ORDER BY aircraft_manufacturer ASC";

		$query = "SELECT DISTINCT icao AS aircraft_icao, type AS aircraft_name, manufacturer AS aircraft_manufacturer FROM aircraft WHERE icao <> '' ORDER BY aircraft_manufacturer ASC";
		
		$sth = $this->db->prepare($query);
		$sth->execute();

		$aircraft_array = array();
		$temp_array = array();
		
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['aircraft_icao'] = $row['aircraft_icao'];
			$temp_array['aircraft_manufacturer'] = $row['aircraft_manufacturer'];
			$temp_array['aircraft_name'] = $row['aircraft_name'];

			$aircraft_array[] = $temp_array;
		}

		return $aircraft_array;
	}


    /**
     * Gets a list of all aircraft registrations
     *
     * @param array $filters
     * @return array list of aircraft registrations
     */
	public function getAllAircraftRegistrations($filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$query  = "SELECT DISTINCT spotter_output.registration 
				FROM spotter_output".$filter_query." spotter_output.registration <> '' 
				ORDER BY spotter_output.date DESC LIMIT 45000";
		$sth = $this->db->prepare($query);
		$sth->execute();
		$aircraft_array = array();
		$temp_array = array();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['registration'] = $row['registration'];
			$aircraft_array[] = $temp_array;
		}
		return $aircraft_array;
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
		$query  = "SELECT DISTINCT spotter_output.source_name 
				FROM spotter_output".$filter_query." spotter_output.source_name <> ''";
		if ($type != '') {
			$query_values = array(':type' => $type);
			$query .= " AND format_source = :type";
		}
		$query .= " ORDER BY spotter_output.source_name ASC";

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
     * Gets a list of all airline names
     *
     * @param string $airline_type
     * @param null $forsource
     * @param array $filters
     * @return array list of airline names
     */
	public function getAllAirlineNames($airline_type = '',$forsource = NULL,$filters = array())
	{
		global $globalAirlinesSource,$globalVATSIM, $globalIVAO, $globalNoAirlines;
		if (isset($globalNoAirlines) && $globalNoAirlines) return array();
		$filter_query = $this->getFilter($filters,true,true);
		$airline_type = filter_var($airline_type,FILTER_SANITIZE_STRING);
		if ($airline_type == '' || $airline_type == 'all') {
			if (isset($globalAirlinesSource) && $globalAirlinesSource != '') $forsource = $globalAirlinesSource;
			elseif (isset($globalVATSIM) && $globalVATSIM) $forsource = 'vatsim';
			elseif (isset($globalIVAO) && $globalIVAO) $forsource = 'ivao';
			if ($forsource === NULL) {
				$query = "SELECT DISTINCT icao AS airline_icao, name AS airline_name, type AS airline_type FROM airlines WHERE forsource IS NULL ORDER BY airline_name ASC";
				$query_data = array();
			} else {
				$query = "SELECT DISTINCT icao AS airline_icao, name AS airline_name, type AS airline_type FROM airlines WHERE forsource = :forsource ORDER BY airline_name ASC";
				$query_data = array(':forsource' => $forsource);
			}
		} else {
			$query  = "SELECT DISTINCT spotter_output.airline_icao AS airline_icao, spotter_output.airline_name AS airline_name, spotter_output.airline_type AS airline_type
					FROM spotter_output".$filter_query." spotter_output.airline_icao <> '' 
					AND spotter_output.airline_type = :airline_type 
					ORDER BY spotter_output.airline_name ASC";
			$query_data = array(':airline_type' => $airline_type);
		}
		
		$sth = $this->db->prepare($query);
		$sth->execute($query_data);
    
		$airline_array = array();
		$temp_array = array();
		
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airline_icao'] = $row['airline_icao'];
			$temp_array['airline_name'] = $row['airline_name'];
			$temp_array['airline_type'] = $row['airline_type'];

			$airline_array[] = $temp_array;
		}
		return $airline_array;
	}

    /**
     * Gets a list of all alliance names
     *
     * @param null $forsource
     * @param array $filters
     * @return array list of alliance names
     */
	public function getAllAllianceNames($forsource = NULL,$filters = array())
	{
		global $globalAirlinesSource,$globalVATSIM, $globalIVAO, $globalNoAirlines;
		if (isset($globalNoAirlines) && $globalNoAirlines) return array();
		$filter_query = $this->getFilter($filters,true,true);
		if (isset($globalAirlinesSource) && $globalAirlinesSource != '') $forsource = $globalAirlinesSource;
		elseif (isset($globalVATSIM) && $globalVATSIM) $forsource = 'vatsim';
		elseif (isset($globalIVAO) && $globalIVAO) $forsource = 'ivao';
		if ($forsource === NULL) {
			$query = "SELECT DISTINCT alliance FROM airlines WHERE alliance IS NOT NULL AND alliance <> 'NULL' AND forsource IS NULL ORDER BY alliance ASC";
			$query_data = array();
		} else {
			$query = "SELECT DISTINCT alliance FROM airlines WHERE alliance IS NOT NULL AND alliance <> 'NULL' AND forsource = :forsource ORDER BY alliance ASC";
			$query_data = array(':forsource' => $forsource);
		}
		
		$sth = $this->db->prepare($query);
		$sth->execute($query_data);
    
		$alliance_array = array();
		$alliance_array = $sth->fetchAll(PDO::FETCH_ASSOC);
		return $alliance_array;
	}

    /**
     * Gets a list of all airlines in an alliance
     *
     * @param $alliance
     * @param null $forsource
     * @param array $filters
     * @return array list of airline names
     */
	public function getAllAirlineNamesByAlliance($alliance,$forsource = NULL,$filters = array())
	{
		global $globalAirlinesSource,$globalVATSIM, $globalIVAO, $globalNoAirlines;
		if (isset($globalNoAirlines) && $globalNoAirlines) return array();
		$alliance = filter_var($alliance,FILTER_SANITIZE_STRING);
		//$filter_query = $this->getFilter($filters,true,true);
		if (isset($globalAirlinesSource) && $globalAirlinesSource != '') $forsource = $globalAirlinesSource;
		elseif (isset($globalVATSIM) && $globalVATSIM) $forsource = 'vatsim';
		elseif (isset($globalIVAO) && $globalIVAO) $forsource = 'ivao';
		if ($forsource === NULL) {
			//$query = "SELECT DISTINCT alliance FROM airlines WHERE alliance IS NOT NULL AND forsource IS NULL ORDER BY alliance ASC";
			$query = "SELECT DISTINCT icao AS airline_icao, name AS airline_name, type AS airline_type FROM airlines WHERE alliance = :alliance AND forsource IS NULL ORDER BY name ASC";
			$query_data = array(':alliance' => $alliance);
		} else {
			//$query = "SELECT DISTINCT alliance FROM airlines WHERE alliance IS NOT NULL AND forsource = :forsource ORDER BY alliance ASC";
			$query = "SELECT DISTINCT icao AS airline_icao, name AS airline_name, type AS airline_type FROM airlines WHERE alliance = :alliance AND forsource = :forsource ORDER BY name ASC";
			$query_data = array(':forsource' => $forsource, ':alliance' => $alliance);
		}
		
		$sth = $this->db->prepare($query);
		$sth->execute($query_data);
    
		$alliance_array = array();
		$alliance_array = $sth->fetchAll(PDO::FETCH_ASSOC);
		return $alliance_array;
	}

    /**
     * Gets a list of all airline countries
     *
     * @param array $filters
     * @return array list of airline countries
     */
	public function getAllAirlineCountries($filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$query  = "SELECT DISTINCT spotter_output.airline_country AS airline_country
				FROM spotter_output".$filter_query." spotter_output.airline_country <> '' 
				ORDER BY spotter_output.airline_country ASC";
		
		//$query = "SELECT DISTINCT country AS airline_country FROM airlines WHERE country <> '' AND active = 'Y' ORDER BY country ASC";
		$sth = $this->db->prepare($query);
		$sth->execute();

		$airline_array = array();
		$temp_array = array();
		
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airline_country'] = $row['airline_country'];

			$airline_array[] = $temp_array;
		}

		return $airline_array;
	}


    /**
     * Gets a list of all departure & arrival names
     *
     * @param array $filters
     * @return array list of airport names
     */
	public function getAllAirportNames($filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$airport_array = array();
		$query  = "SELECT DISTINCT spotter_output.departure_airport_icao AS airport_icao, spotter_output.departure_airport_name AS airport_name, spotter_output.departure_airport_city AS airport_city, spotter_output.departure_airport_country AS airport_country
				FROM spotter_output".$filter_query." spotter_output.departure_airport_icao <> '' AND spotter_output.departure_airport_icao <> 'NA' 
				ORDER BY spotter_output.departure_airport_city ASC";
		
		//$query = "SELECT DISTINCT icao AS airport_icao, name AS airport_name, city AS airport_city, country AS airport_country FROM airport ORDER BY city ASC";
		$sth = $this->db->prepare($query);
		$sth->execute();

		$temp_array = array();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airport_icao'] = $row['airport_icao'];
			$temp_array['airport_name'] = $row['airport_name'];
			$temp_array['airport_city'] = $row['airport_city'];
			$temp_array['airport_country'] = $row['airport_country'];

			$airport_array[$row['airport_city'].",".$row['airport_name']] = $temp_array;
		}

		$query  = "SELECT DISTINCT spotter_output.arrival_airport_icao AS airport_icao, spotter_output.arrival_airport_name AS airport_name, spotter_output.arrival_airport_city AS airport_city, spotter_output.arrival_airport_country AS airport_country
								FROM spotter_output".$filter_query." spotter_output.arrival_airport_icao <> '' AND spotter_output.arrival_airport_icao <> 'NA' 
								ORDER BY spotter_output.arrival_airport_city ASC";
					
		$sth = $this->db->prepare($query);
		$sth->execute();

		while($row = $sth->fetch(PDO::FETCH_ASSOC))
			{
		//	if ($airport_array[$row['airport_city'].",".$row['airport_name']]['airport_icao'] != $row['airport_icao'])
		//	{
				$temp_array['airport_icao'] = $row['airport_icao'];
				$temp_array['airport_name'] = $row['airport_name'];
				$temp_array['airport_city'] = $row['airport_city'];
				$temp_array['airport_country'] = $row['airport_country'];
				
				$airport_array[$row['airport_city'].",".$row['airport_name']] = $temp_array;
		//	}
		}

		return $airport_array;
	}

    /**
     * Gets a list of all owner names
     *
     * @param array $filters
     * @return array list of owner names
     */
	public function getAllOwnerNames($filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$query  = "SELECT DISTINCT spotter_output.owner_name
				FROM spotter_output".$filter_query." spotter_output.owner_name <> '' 
				ORDER BY spotter_output.owner_name ASC";
		
		$sth = $this->db->prepare($query);
		$sth->execute();
		return $sth->fetchAll(PDO::FETCH_ASSOC);
	}

    /**
     * Gets a list of all pilot names and pilot ids
     *
     * @param array $filters
     * @return array list of pilot names and pilot ids
     */
	public function getAllPilotNames($filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$query  = "SELECT DISTINCT spotter_output.pilot_name, spotter_output.pilot_id
				FROM spotter_output".$filter_query." spotter_output.pilot_name <> '' 
				ORDER BY spotter_output.pilot_name ASC";
		
		$sth = $this->db->prepare($query);
		$sth->execute();
		return $sth->fetchAll(PDO::FETCH_ASSOC);
	}


    /**
     * Gets a list of all departure & arrival airport countries
     *
     * @param array $filters
     * @return array list of airport countries
     */
	public function getAllAirportCountries($filters = array())
	{
		$airport_array = array();
					
		  /*
		$query  = "SELECT DISTINCT spotter_output.departure_airport_country AS airport_country
								FROM spotter_output
								WHERE spotter_output.departure_airport_country <> '' 
								ORDER BY spotter_output.departure_airport_country ASC";
		*/
		$query = "SELECT DISTINCT country AS airport_country FROM airport ORDER BY country ASC";
		
		$sth = $this->db->prepare($query);
		$sth->execute();
   
		$temp_array = array();
		
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airport_country'] = $row['airport_country'];

			$airport_array[$row['airport_country']] = $temp_array;
		}
		$filter_query = $this->getFilter($filters,true,true);
		$query  = "SELECT DISTINCT spotter_output.arrival_airport_country AS airport_country
								FROM spotter_output".$filter_query." spotter_output.arrival_airport_country <> '' 
								ORDER BY spotter_output.arrival_airport_country ASC";
					
		$sth = $this->db->prepare($query);
		$sth->execute();
		
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			if (isset($airport_array[$row['airport_country']]['airport_country']) && $airport_array[$row['airport_country']]['airport_country'] != $row['airport_country'])
			{
				$temp_array['airport_country'] = $row['airport_country'];
				$airport_array[$row['airport_country']] = $temp_array;
			}
		}

		return $airport_array;
	}


    /**
     * Gets a list of all countries (airline, departure airport & arrival airport)
     *
     * @param array $filters
     * @return array list of countries
     */
	public function getAllCountries($filters = array())
	{
		$Connection= new Connection($this->db);
		if ($Connection->tableExists('countries')) {
			$query  = "SELECT countries.name AS airport_country
				FROM countries
				ORDER BY countries.name ASC";
			$sth = $this->db->prepare($query);
			$sth->execute();
   
			$temp_array = array();
			$country_array = array();
		
			while($row = $sth->fetch(PDO::FETCH_ASSOC))
			{
				$temp_array['country'] = $row['airport_country'];
				$country_array[$row['airport_country']] = $temp_array;
			}
		} else {
			$filter_query = $this->getFilter($filters,true,true);
			$query  = "SELECT DISTINCT spotter_output.departure_airport_country AS airport_country
								FROM spotter_output".$filter_query." spotter_output.departure_airport_country <> '' 
								ORDER BY spotter_output.departure_airport_country ASC";

			$sth = $this->db->prepare($query);
			$sth->execute();
   
			$temp_array = array();
			$country_array = array();
			while($row = $sth->fetch(PDO::FETCH_ASSOC))
			{
				$temp_array['country'] = $row['airport_country'];
				$country_array[$row['airport_country']] = $temp_array;
			}

		$query  = "SELECT DISTINCT spotter_output.arrival_airport_country AS airport_country
								FROM spotter_output".$filter_query." spotter_output.arrival_airport_country <> '' 
								ORDER BY spotter_output.arrival_airport_country ASC";
					
		$sth = $this->db->prepare($query);
		$sth->execute();
		
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			if ($country_array[$row['airport_country']]['country'] != $row['airport_country'])
			{
				$temp_array['country'] = $row['airport_country'];
				
				$country_array[$row['country']] = $temp_array;
			}
		}
		
		$query  = "SELECT DISTINCT spotter_output.airline_country AS airline_country
								FROM spotter_output".$filter_query." spotter_output.airline_country <> '' 
								ORDER BY spotter_output.airline_country ASC";
					
		$sth = $this->db->prepare($query);
		$sth->execute();
		
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			if (isset($country_array[$row['airline_country']]['country']) && $country_array[$row['airline_country']]['country'] != $row['airline_country'])
			{
				$temp_array['country'] = $row['airline_country'];
				
				$country_array[$row['country']] = $temp_array;
			}
		}
		}  
		return $country_array;
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
		$query  = "SELECT DISTINCT spotter_output.ident
			FROM spotter_output".$filter_query." spotter_output.ident <> '' 
			ORDER BY spotter_output.date ASC LIMIT 700 OFFSET 0";

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
     * Get a list of flights from airport since 7 days
     * @param string $airport_icao
     * @param array $filters
     * @return array number, icao, name and city of airports
     */
	public function getLast7DaysAirportsDeparture($airport_icao = '',$filters = array()) {
		global $globalTimezone, $globalDBdriver;
		$filter_query = $this->getFilter($filters,true,true);
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime();
			$offset = $datetime->format('P');
		} else $offset = '+00:00';
		if ($airport_icao == '') {
			if ($globalDBdriver == 'mysql') {
				$query = "SELECT COUNT(departure_airport_icao) AS departure_airport_count, departure_airport_icao, departure_airport_name, departure_airport_city, departure_airport_country, DATE_FORMAT(DATE(CONVERT_TZ(spotter_output.date,'+00:00', :offset)),'%Y-%m-%d') as date FROM `spotter_output`".$filter_query." spotter_output.date >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 7 DAY) AND departure_airport_icao <> 'NA' AND departure_airport_icao <> '' GROUP BY departure_airport_icao, DATE_FORMAT(DATE(CONVERT_TZ(spotter_output.date,'+00:00', :offset)),'%Y-%m-%d'), departure_airport_name, departure_airport_city, departure_airport_country ORDER BY departure_airport_count DESC";
			} else {
				$query = "SELECT COUNT(departure_airport_icao) AS departure_airport_count, departure_airport_icao, departure_airport_name, departure_airport_city, departure_airport_country, to_char(spotter_output.date AT TIME ZONE INTERVAL :offset,'YYYY-mm-dd') as date FROM spotter_output".$filter_query." spotter_output.date >= CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '7 DAYS' AND departure_airport_icao <> 'NA' AND departure_airport_icao <> '' GROUP BY departure_airport_icao, to_char(spotter_output.date AT TIME ZONE INTERVAL :offset,'YYYY-mm-dd'), departure_airport_name, departure_airport_city, departure_airport_country ORDER BY departure_airport_count DESC";
			}
			$sth = $this->db->prepare($query);
			$sth->execute(array(':offset' => $offset));
		} else {
			if ($globalDBdriver == 'mysql') {
				$query = "SELECT COUNT(departure_airport_icao) AS departure_airport_count, departure_airport_icao, departure_airport_name, departure_airport_city, departure_airport_country, DATE_FORMAT(DATE(CONVERT_TZ(spotter_output.date,'+00:00', :offset)),'%Y-%m-%d') as date FROM `spotter_output`".$filter_query." spotter_output.date >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 7 DAY) AND departure_airport_icao = :airport_icao GROUP BY departure_airport_icao, DATE_FORMAT(DATE(CONVERT_TZ(spotter_output.date,'+00:00', :offset)),'%Y-%m-%d'), departure_airport_name, departure_airport_city, departure_airport_country ORDER BY departure_airport_count DESC";
			} else {
				$query = "SELECT COUNT(departure_airport_icao) AS departure_airport_count, departure_airport_icao, departure_airport_name, departure_airport_city, departure_airport_country, to_char(spotter_output.date AT TIME ZONE INTERVAL :offset,'YYYY-mm-dd') as date FROM spotter_output".$filter_query." spotter_output.date >= CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '7 DAYS' AND departure_airport_icao = :airport_icao GROUP BY departure_airport_icao, to_char(spotter_output.date AT TIME ZONE INTERVAL :offset,'YYYY-mm-dd'), departure_airport_name, departure_airport_city, departure_airport_country ORDER BY departure_airport_count DESC";
			}
			$sth = $this->db->prepare($query);
			$sth->execute(array(':offset' => $offset, ':airport_icao' => $airport_icao));
		}
		return $sth->fetchAll(PDO::FETCH_ASSOC);
	}

    /**
     * Get a list of flights from airport since 7 days
     * @param string $airport_icao
     * @return array number, icao, name and city of airports
     */
	public function getLast7DaysAirportsDepartureByAirlines($airport_icao = '') {
		global $globalTimezone, $globalDBdriver;
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime();
			$offset = $datetime->format('P');
		} else $offset = '+00:00';
		if ($airport_icao == '') {
			if ($globalDBdriver == 'mysql') {
				$query = "SELECT spotter_output.airline_icao, COUNT(departure_airport_icao) AS departure_airport_count, departure_airport_icao, departure_airport_name, departure_airport_city, departure_airport_country, DATE_FORMAT(DATE(CONVERT_TZ(spotter_output.date,'+00:00', :offset)),'%Y-%m-%d') as date FROM `spotter_output` WHERE spotter_output.date >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 7 DAY) AND departure_airport_icao <> 'NA' AND departure_airport_icao <> '' AND spotter_output.airline_icao <> '' GROUP BY spotter_output.airline_icao, departure_airport_icao, DATE_FORMAT(DATE(CONVERT_TZ(spotter_output.date,'+00:00', :offset)),'%Y-%m-%d'), departure_airport_name, departure_airport_city, departure_airport_country ORDER BY departure_airport_count DESC";
			} else {
				$query = "SELECT spotter_output.airline_icao, COUNT(departure_airport_icao) AS departure_airport_count, departure_airport_icao, departure_airport_name, departure_airport_city, departure_airport_country, to_char(spotter_output.date AT TIME ZONE INTERVAL :offset,'YYYY-mm-dd') as date FROM spotter_output WHERE spotter_output.date >= CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '7 DAYS' AND departure_airport_icao <> 'NA' AND departure_airport_icao <> '' AND spotter_output.airline_icao <> '' GROUP BY spotter_output.airline_icao, departure_airport_icao, to_char(spotter_output.date AT TIME ZONE INTERVAL :offset,'YYYY-mm-dd'), departure_airport_name, departure_airport_city, departure_airport_country ORDER BY departure_airport_count DESC";
			}
			$sth = $this->db->prepare($query);
			$sth->execute(array(':offset' => $offset));
		} else {
			if ($globalDBdriver == 'mysql') {
				$query = "SELECT spotter_output.airline_icao, COUNT(departure_airport_icao) AS departure_airport_count, departure_airport_icao, departure_airport_name, departure_airport_city, departure_airport_country, DATE_FORMAT(DATE(CONVERT_TZ(spotter_output.date,'+00:00', :offset)),'%Y-%m-%d') as date FROM `spotter_output` WHERE spotter_output.date >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 7 DAY) AND departure_airport_icao = :airport_icao AND spotter_output.airline_icao <> '' GROUP BY spotter_output.airline_icao, departure_airport_icao, DATE_FORMAT(DATE(CONVERT_TZ(spotter_output.date,'+00:00', :offset)),'%Y-%m-%d'), departure_airport_name, departure_airport_city, departure_airport_country ORDER BY departure_airport_count DESC";
			} else {
				$query = "SELECT spotter_output.airline_icao, COUNT(departure_airport_icao) AS departure_airport_count, departure_airport_icao, departure_airport_name, departure_airport_city, departure_airport_country, to_char(spotter_output.date AT TIME ZONE INTERVAL :offset,'YYYY-mm-dd') as date FROM spotter_output WHERE spotter_output.date >= CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '7 DAYS' AND departure_airport_icao = :airport_icao AND spotter_output.airline_icao <> '' GROUP BY spotter_output.airline_icao, departure_airport_icao, to_char(spotter_output.date AT TIME ZONE INTERVAL :offset,'YYYY-mm-dd'), departure_airport_name, departure_airport_city, departure_airport_country ORDER BY departure_airport_count DESC";
			}
			$sth = $this->db->prepare($query);
			$sth->execute(array(':offset' => $offset, ':airport_icao' => $airport_icao));
		}
		return $sth->fetchAll(PDO::FETCH_ASSOC);
	}

    /**
     * Get a list of flights from detected airport since 7 days
     * @param string $airport_icao
     * @param array $filters
     * @return array number, icao, name and city of airports
     */
	public function getLast7DaysDetectedAirportsDeparture($airport_icao = '', $filters = array()) {
		global $globalTimezone, $globalDBdriver;
		$filter_query = $this->getFilter($filters,true,true);
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime();
			$offset = $datetime->format('P');
		} else $offset = '+00:00';
		if ($airport_icao == '') {
			if ($globalDBdriver == 'mysql') {
				$query = "SELECT COUNT(real_departure_airport_icao) AS departure_airport_count, real_departure_airport_icao AS departure_airport_icao, airport.name AS departure_airport_name, airport.city AS departure_airport_city, airport.country AS departure_airport_country, DATE_FORMAT(DATE(CONVERT_TZ(spotter_output.date,'+00:00', :offset)),'%Y-%m-%d') as date 
				FROM airport, spotter_output".$filter_query." airport.icao = spotter_output.real_departure_airport_icao AND spotter_output.date >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 7 DAY) AND real_departure_airport_icao <> 'NA' AND real_departure_airport_icao <> '' 
				GROUP BY real_departure_airport_icao, DATE_FORMAT(DATE(CONVERT_TZ(spotter_output.date,'+00:00', :offset)),'%Y-%m-%d'), airport.name, airport.city, airport.country ORDER BY departure_airport_count DESC";
			} else {
				$query = "SELECT COUNT(real_departure_airport_icao) AS departure_airport_count, real_departure_airport_icao AS departure_airport_icao, airport.name AS departure_airport_name, airport.city AS departure_airport_city, airport.country AS departure_airport_country, to_char(spotter_output.date AT TIME ZONE INTERVAL :offset,'YYYY-mm-dd') as date 
				FROM airport, spotter_output".$filter_query." airport.icao = spotter_output.real_departure_airport_icao AND spotter_output.date >= CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '7 DAYS' AND real_departure_airport_icao <> 'NA' AND real_departure_airport_icao <> '' 
				GROUP BY real_departure_airport_icao, to_char(spotter_output.date AT TIME ZONE INTERVAL :offset,'YYYY-mm-dd'), airport.name, airport.city, airport.country ORDER BY departure_airport_count DESC";
			}
			$sth = $this->db->prepare($query);
			$sth->execute(array(':offset' => $offset));
		} else {
			if ($globalDBdriver == 'mysql') {
				$query = "SELECT COUNT(real_departure_airport_icao) AS departure_airport_count, real_departure_airport_icao AS departure_airport_icao, airport.name AS departure_airport_name, airport.city AS departure_airport_city, airport.country AS departure_airport_country, DATE_FORMAT(DATE(CONVERT_TZ(spotter_output.date,'+00:00', :offset)),'%Y-%m-%d') as date 
				FROM airport,spotter_output".$filter_query." airport.icao = spotter_output.real_departure_airport_icao AND spotter_output.date >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 7 DAY) AND real_departure_airport_icao = :airport_icao 
				GROUP BY departure_airport_icao, DATE_FORMAT(DATE(CONVERT_TZ(spotter_output.date,'+00:00', :offset)),'%Y-%m-%d'), airport.name, airport.city, airport.country ORDER BY departure_airport_count DESC";
			} else {
				$query = "SELECT COUNT(real_departure_airport_icao) AS departure_airport_count, real_departure_airport_icao AS departure_airport_icao, airport.name AS departure_airport_name, airport.city AS departure_airport_city, airport.country AS departure_airport_country, to_char(spotter_output.date AT TIME ZONE INTERVAL :offset,'YYYY-mm-dd') as date 
				FROM airport,spotter_output".$filter_query." airport.icao = spotter_output.real_departure_airport_icao AND spotter_output.date >= CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '7 DAYS' AND real_departure_airport_icao = :airport_icao GROUP BY departure_airport_icao, to_char(spotter_output.date AT TIME ZONE INTERVAL :offset,'YYYY-mm-dd'), airport.name, airport.city, airport.country ORDER BY departure_airport_count DESC";
			}
			$sth = $this->db->prepare($query);
			$sth->execute(array(':offset' => $offset, ':airport_icao' => $airport_icao));
		}
		return $sth->fetchAll(PDO::FETCH_ASSOC);
	}

    /**
     * Get a list of flights from detected airport since 7 days
     * @param string $airport_icao
     * @return array number, icao, name and city of airports
     */
	public function getLast7DaysDetectedAirportsDepartureByAirlines($airport_icao = '') {
		global $globalTimezone, $globalDBdriver;
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime();
			$offset = $datetime->format('P');
		} else $offset = '+00:00';
		if ($airport_icao == '') {
			if ($globalDBdriver == 'mysql') {
				$query = "SELECT spotter_output.airline_icao, COUNT(real_departure_airport_icao) AS departure_airport_count, real_departure_airport_icao AS departure_airport_icao, airport.name AS departure_airport_name, airport.city AS departure_airport_city, airport.country AS departure_airport_country, DATE_FORMAT(DATE(CONVERT_TZ(spotter_output.date,'+00:00', :offset)),'%Y-%m-%d') as date 
				FROM `spotter_output`, airport 
				WHERE spotter_output.airline_icao <> '' AND airport.icao = spotter_output.real_departure_airport_icao AND spotter_output.date >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 7 DAY) AND real_departure_airport_icao <> 'NA' AND real_departure_airport_icao <> '' 
				GROUP BY spotter_output.airline_icao, real_departure_airport_icao, DATE_FORMAT(DATE(CONVERT_TZ(spotter_output.date,'+00:00', :offset)),'%Y-%m-%d'), airport.name, airport.city, airport.country ORDER BY departure_airport_count DESC";
			} else {
				$query = "SELECT spotter_output.airline_icao, COUNT(real_departure_airport_icao) AS departure_airport_count, real_departure_airport_icao AS departure_airport_icao, airport.name AS departure_airport_name, airport.city AS departure_airport_city, airport.country AS departure_airport_country, to_char(spotter_output.date AT TIME ZONE INTERVAL :offset,'YYYY-mm-dd') as date 
				FROM spotter_output, airport 
				WHERE spotter_output.airline_icao <> '' AND airport.icao = spotter_output.real_departure_airport_icao AND spotter_output.date >= CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '7 DAYS' AND real_departure_airport_icao <> 'NA' AND real_departure_airport_icao <> '' 
				GROUP BY spotter_output.airline_icao, real_departure_airport_icao, to_char(spotter_output.date AT TIME ZONE INTERVAL :offset,'YYYY-mm-dd'), airport.name, airport.city, airport.country ORDER BY departure_airport_count DESC";
			}
			$sth = $this->db->prepare($query);
			$sth->execute(array(':offset' => $offset));
		} else {
			if ($globalDBdriver == 'mysql') {
				$query = "SELECT spotter_output.airline_icao, COUNT(real_departure_airport_icao) AS departure_airport_count, real_departure_airport_icao AS departure_airport_icao, airport.name AS departure_airport_name, airport.city AS departure_airport_city, airport.country AS departure_airport_country, DATE_FORMAT(DATE(CONVERT_TZ(spotter_output.date,'+00:00', :offset)),'%Y-%m-%d') as date 
				FROM `spotter_output`, airport 
				WHERE spotter_output.airline_icao <> '' AND airport.icao = spotter_output.real_departure_airport_icao AND spotter_output.date >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 7 DAY) AND real_departure_airport_icao = :airport_icao 
				GROUP BY spotter_output.airline_icao, departure_airport_icao, DATE_FORMAT(DATE(CONVERT_TZ(spotter_output.date,'+00:00', :offset)),'%Y-%m-%d'), airport.name, airport.city, airport.country ORDER BY departure_airport_count DESC";
			} else {
				$query = "SELECT spotter_output.airline_icao, COUNT(real_departure_airport_icao) AS departure_airport_count, real_departure_airport_icao AS departure_airport_icao, airport.name AS departure_airport_name, airport.city AS departure_airport_city, airport.country AS departure_airport_country, to_char(spotter_output.date AT TIME ZONE INTERVAL :offset,'YYYY-mm-dd') as date 
				FROM spotter_output, airport 
				WHERE spotter_output.airline_icao <> '' AND airport.icao = spotter_output.real_departure_airport_icao AND spotter_output.date >= CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '7 DAYS' AND real_departure_airport_icao = :airport_icao GROUP BY spotter_output.airline_icao, departure_airport_icao, to_char(spotter_output.date AT TIME ZONE INTERVAL :offset,'YYYY-mm-dd'), airport.name, airport.city, airport.country ORDER BY departure_airport_count DESC";
			}
			$sth = $this->db->prepare($query);
			$sth->execute(array(':offset' => $offset, ':airport_icao' => $airport_icao));
		}
		return $sth->fetchAll(PDO::FETCH_ASSOC);
	}


    /**
     * Get a list of flights to airport since 7 days
     * @param string $airport_icao
     * @param array $filters
     * @return array number, icao, name and city of airports
     */
	public function getLast7DaysAirportsArrival($airport_icao = '', $filters = array()) {
		global $globalTimezone, $globalDBdriver;
		$filter_query = $this->getFilter($filters,true,true);
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime();
			$offset = $datetime->format('P');
		} else $offset = '+00:00';
		if ($airport_icao == '') {
			if ($globalDBdriver == 'mysql') {
				$query = "SELECT COUNT(arrival_airport_icao) AS arrival_airport_count, arrival_airport_icao, arrival_airport_name, arrival_airport_city, arrival_airport_country, DATE_FORMAT(DATE(CONVERT_TZ(spotter_output.date,'+00:00', :offset)),'%Y-%m-%d') as date FROM `spotter_output`".$filter_query." spotter_output.date >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 7 DAY) AND arrival_airport_icao <> 'NA' AND arrival_airport_icao <> '' GROUP BY arrival_airport_icao, DATE_FORMAT(DATE(CONVERT_TZ(spotter_output.date,'+00:00', :offset)),'%Y-%m-%d'), arrival_airport_name, arrival_airport_city, arrival_airport_country ORDER BY arrival_airport_count DESC";
			} else {
				$query = "SELECT COUNT(arrival_airport_icao) AS arrival_airport_count, arrival_airport_icao, arrival_airport_name, arrival_airport_city, arrival_airport_country, to_char(spotter_output.date AT TIME ZONE INTERVAL :offset,'YYYY-mm-dd') as date FROM spotter_output".$filter_query." spotter_output.date >= CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '7 DAYS' AND arrival_airport_icao <> 'NA' AND arrival_airport_icao <> '' GROUP BY arrival_airport_icao, to_char(spotter_output.date AT TIME ZONE INTERVAL :offset,'YYYY-mm-dd'), arrival_airport_name, arrival_airport_city, arrival_airport_country ORDER BY arrival_airport_count DESC";
			}
			$sth = $this->db->prepare($query);
			$sth->execute(array(':offset' => $offset));
		} else {
			if ($globalDBdriver == 'mysql') {
				$query = "SELECT COUNT(arrival_airport_icao) AS arrival_airport_count, arrival_airport_icao, arrival_airport_name, arrival_airport_city, arrival_airport_country, DATE_FORMAT(DATE(CONVERT_TZ(spotter_output.date,'+00:00', :offset)),'%Y-%m-%d') as date FROM `spotter_output`".$filter_query." spotter_output.date >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 7 DAY) AND arrival_airport_icao = :airport_icao GROUP BY arrival_airport_icao, DATE_FORMAT(DATE(CONVERT_TZ(spotter_output.date,'+00:00', :offset)),'%Y-%m-%d'),arrival_airport_name, arrival_airport_city, arrival_airport_country ORDER BY arrival_airport_count DESC";
			} else {
				$query = "SELECT COUNT(arrival_airport_icao) AS arrival_airport_count, arrival_airport_icao, arrival_airport_name, arrival_airport_city, arrival_airport_country, to_char(spotter_output.date AT TIME ZONE INTERVAL :offset,'YYYY-mm-dd') as date FROM spotter_output".$filter_query." spotter_output.date >= CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '7 DAYS' AND arrival_airport_icao = :airport_icao GROUP BY arrival_airport_icao, to_char(spotter_output.date AT TIME ZONE INTERVAL :offset,'YYYY-mm-dd'), arrival_airport_name, arrival_airport_city, arrival_airport_country ORDER BY arrival_airport_count DESC";
			}
			$sth = $this->db->prepare($query);
			$sth->execute(array(':offset' => $offset, ':airport_icao' => $airport_icao));
		}
		
		return $sth->fetchAll(PDO::FETCH_ASSOC);
	}


    /**
     * Get a list of flights detected to airport since 7 days
     * @param string $airport_icao
     * @param array $filters
     * @return array number, icao, name and city of airports
     */
	public function getLast7DaysDetectedAirportsArrival($airport_icao = '',$filters = array()) {
		global $globalTimezone, $globalDBdriver;
		$filter_query = $this->getFilter($filters,true,true);
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime();
			$offset = $datetime->format('P');
		} else $offset = '+00:00';
		if ($airport_icao == '') {
			if ($globalDBdriver == 'mysql') {
				$query = "SELECT COUNT(real_arrival_airport_icao) AS arrival_airport_count, real_arrival_airport_icao AS arrival_airport_icao, airport.name AS arrival_airport_name, airport.city AS arrival_airport_city, airport.country AS arrival_airport_country, DATE_FORMAT(DATE(CONVERT_TZ(spotter_output.date,'+00:00', :offset)),'%Y-%m-%d') as date 
				FROM airport,spotter_output".$filter_query." airport.icao = spotter_output.real_arrival_airport_icao AND spotter_output.date >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 7 DAY) AND arrival_airport_icao <> 'NA' AND arrival_airport_icao <> '' 
				GROUP BY real_arrival_airport_icao, DATE_FORMAT(DATE(CONVERT_TZ(spotter_output.date,'+00:00', :offset)),'%Y-%m-%d'), airport.name, airport.city, airport.country ORDER BY arrival_airport_count DESC";
			} else {
				$query = "SELECT COUNT(real_arrival_airport_icao) AS arrival_airport_count, real_arrival_airport_icao AS arrival_airport_icao, airport.name AS arrival_airport_name, airport.city AS arrival_airport_city, airport.country AS arrival_airport_country, to_char(spotter_output.date AT TIME ZONE INTERVAL :offset,'YYYY-mm-dd') as date 
				FROM airport,spotter_output".$filter_query." airport.icao = spotter_output.real_arrival_airport_icao AND spotter_output.date >= CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '7 DAYS' AND arrival_airport_icao <> 'NA' AND arrival_airport_icao <> '' 
				GROUP BY real_arrival_airport_icao, to_char(spotter_output.date AT TIME ZONE INTERVAL :offset,'YYYY-mm-dd'), airport.name, airport.city, airport.country ORDER BY arrival_airport_count DESC";
			}
			$sth = $this->db->prepare($query);
			$sth->execute(array(':offset' => $offset));
		} else {
			if ($globalDBdriver == 'mysql') {
				$query = "SELECT COUNT(real_arrival_airport_icao) AS arrival_airport_count, real_arrival_airport_icao AS arrival_airport_icao, airport.name AS arrival_airport_name, airport.city AS arrival_airport_city, airport.country AS arrival_airport_country, DATE_FORMAT(DATE(CONVERT_TZ(spotter_output.date,'+00:00', :offset)),'%Y-%m-%d') as date 
				FROM airport,spotter_output".$filter_query." airport.icao = spotter_output.real_arrival_airport_icao AND spotter_output.date >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 7 DAY) AND arrival_airport_icao = :airport_icao 
				GROUP BY real_arrival_airport_icao, DATE_FORMAT(DATE(CONVERT_TZ(spotter_output.date,'+00:00', :offset)),'%Y-%m-%d'),airport.name, airport.city, airport.country ORDER BY arrival_airport_count DESC";
			} else {
				$query = "SELECT COUNT(real_arrival_airport_icao) AS arrival_airport_count, real_arrival_airport_icao AS arrival_airport_icao, airport.name AS arrival_airport_name, airport.city AS arrival_airport_city, airport.country AS arrival_airport_country, to_char(spotter_output.date AT TIME ZONE INTERVAL :offset,'YYYY-mm-dd') as date 
				FROM airport, spotter_output".$filter_query." airport.icao = spotter_output.real_arrival_airport_icao AND spotter_output.date >= CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '7 DAYS' AND arrival_airport_icao = :airport_icao 
				GROUP BY real_arrival_airport_icao, to_char(spotter_output.date AT TIME ZONE INTERVAL :offset,'YYYY-mm-dd'), airport.name, airport.city, airport.country ORDER BY arrival_airport_count DESC";
			}
			$sth = $this->db->prepare($query);
			$sth->execute(array(':offset' => $offset, ':airport_icao' => $airport_icao));
		}
		
		return $sth->fetchAll(PDO::FETCH_ASSOC);
	}


    /**
     * Get a list of flights to airport since 7 days
     * @param string $airport_icao
     * @return array number, icao, name and city of airports
     */
	public function getLast7DaysAirportsArrivalByAirlines($airport_icao = '') {
		global $globalTimezone, $globalDBdriver;
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime();
			$offset = $datetime->format('P');
		} else $offset = '+00:00';
		if ($airport_icao == '') {
			if ($globalDBdriver == 'mysql') {
				$query = "SELECT spotter_output.airline_icao, COUNT(arrival_airport_icao) AS arrival_airport_count, arrival_airport_icao, arrival_airport_name, arrival_airport_city, arrival_airport_country, DATE_FORMAT(DATE(CONVERT_TZ(spotter_output.date,'+00:00', :offset)),'%Y-%m-%d') as date FROM `spotter_output` WHERE spotter_output.date >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 7 DAY) AND arrival_airport_icao <> 'NA' AND arrival_airport_icao <> '' AND spotter_output.airline_icao <> '' GROUP BY spotter_output.airline_icao, arrival_airport_icao, DATE_FORMAT(DATE(CONVERT_TZ(spotter_output.date,'+00:00', :offset)),'%Y-%m-%d'), arrival_airport_name, arrival_airport_city, arrival_airport_country ORDER BY arrival_airport_count DESC";
			} else {
				$query = "SELECT spotter_output.airline_icao, COUNT(arrival_airport_icao) AS arrival_airport_count, arrival_airport_icao, arrival_airport_name, arrival_airport_city, arrival_airport_country, to_char(spotter_output.date AT TIME ZONE INTERVAL :offset,'YYYY-mm-dd') as date FROM spotter_output WHERE spotter_output.date >= CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '7 DAYS' AND arrival_airport_icao <> 'NA' AND arrival_airport_icao <> '' AND spotter_output.airline_icao <> '' GROUP BY spotter_output.airline_icao, arrival_airport_icao, to_char(spotter_output.date AT TIME ZONE INTERVAL :offset,'YYYY-mm-dd'), arrival_airport_name, arrival_airport_city, arrival_airport_country ORDER BY arrival_airport_count DESC";
			}
			$sth = $this->db->prepare($query);
			$sth->execute(array(':offset' => $offset));
		} else {
			if ($globalDBdriver == 'mysql') {
				$query = "SELECT spotter_output.airline_icao, COUNT(arrival_airport_icao) AS arrival_airport_count, arrival_airport_icao, arrival_airport_name, arrival_airport_city, arrival_airport_country, DATE_FORMAT(DATE(CONVERT_TZ(spotter_output.date,'+00:00', :offset)),'%Y-%m-%d') as date FROM `spotter_output` WHERE spotter_output.date >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 7 DAY) AND arrival_airport_icao = :airport_icao AND spotter_output.airline_icao <> '' GROUP BY spotter_output.airline_icao, arrival_airport_icao, DATE_FORMAT(DATE(CONVERT_TZ(spotter_output.date,'+00:00', :offset)),'%Y-%m-%d'),arrival_airport_name, arrival_airport_city, arrival_airport_country ORDER BY arrival_airport_count DESC";
			} else {
				$query = "SELECT spotter_output.airline_icao, COUNT(arrival_airport_icao) AS arrival_airport_count, arrival_airport_icao, arrival_airport_name, arrival_airport_city, arrival_airport_country, to_char(spotter_output.date AT TIME ZONE INTERVAL :offset,'YYYY-mm-dd') as date FROM spotter_output WHERE spotter_output.date >= CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '7 DAYS' AND arrival_airport_icao = :airport_icao AND spotter_output.airline_icao <> '' GROUP BY spotter_output.airline_icao, arrival_airport_icao, to_char(spotter_output.date AT TIME ZONE INTERVAL :offset,'YYYY-mm-dd'), arrival_airport_name, arrival_airport_city, arrival_airport_country ORDER BY arrival_airport_count DESC";
			}
			$sth = $this->db->prepare($query);
			$sth->execute(array(':offset' => $offset, ':airport_icao' => $airport_icao));
		}
		
		return $sth->fetchAll(PDO::FETCH_ASSOC);
	}


    /**
     * Get a list of flights detected to airport since 7 days
     * @param string $airport_icao
     * @return array number, icao, name and city of airports
     */
	public function getLast7DaysDetectedAirportsArrivalByAirlines($airport_icao = '') {
		global $globalTimezone, $globalDBdriver;
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime();
			$offset = $datetime->format('P');
		} else $offset = '+00:00';
		if ($airport_icao == '') {
			if ($globalDBdriver == 'mysql') {
				$query = "SELECT spotter_output.airline_icao, COUNT(real_arrival_airport_icao) AS arrival_airport_count, real_arrival_airport_icao AS arrival_airport_icao, airport.name AS arrival_airport_name, airport.city AS arrival_airport_city, airport.country AS arrival_airport_country, DATE_FORMAT(DATE(CONVERT_TZ(spotter_output.date,'+00:00', :offset)),'%Y-%m-%d') as date 
				FROM `spotter_output`, airport 
				WHERE spotter_output.airline_icao <> '' AND airport.icao = spotter_output.real_arrival_airport_icao AND spotter_output.date >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 7 DAY) AND arrival_airport_icao <> 'NA' AND arrival_airport_icao <> '' 
				GROUP BY spotter_output.airline_icao, real_arrival_airport_icao, DATE_FORMAT(DATE(CONVERT_TZ(spotter_output.date,'+00:00', :offset)),'%Y-%m-%d'), airport.name, airport.city, airport.country ORDER BY arrival_airport_count DESC";
			} else {
				$query = "SELECT spotter_output.airline_icao, COUNT(real_arrival_airport_icao) AS arrival_airport_count, real_arrival_airport_icao AS arrival_airport_icao, airport.name AS arrival_airport_name, airport.city AS arrival_airport_city, airport.country AS arrival_airport_country, to_char(spotter_output.date AT TIME ZONE INTERVAL :offset,'YYYY-mm-dd') as date 
				FROM spotter_output, airport 
				WHERE spotter_output.airline_icao <> '' AND airport.icao = spotter_output.real_arrival_airport_icao AND spotter_output.date >= CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '7 DAYS' AND arrival_airport_icao <> 'NA' AND arrival_airport_icao <> '' 
				GROUP BY spotter_output.airline_icao, real_arrival_airport_icao, to_char(spotter_output.date AT TIME ZONE INTERVAL :offset,'YYYY-mm-dd'), airport.name, airport.city, airport.country ORDER BY arrival_airport_count DESC";
			}
			$sth = $this->db->prepare($query);
			$sth->execute(array(':offset' => $offset));
		} else {
			if ($globalDBdriver == 'mysql') {
				$query = "SELECT spotter_output.airline_icao, COUNT(real_arrival_airport_icao) AS arrival_airport_count, real_arrival_airport_icao AS arrival_airport_icao, airport.name AS arrival_airport_name, airport.city AS arrival_airport_city, airport.country AS arrival_airport_country, DATE_FORMAT(DATE(CONVERT_TZ(spotter_output.date,'+00:00', :offset)),'%Y-%m-%d') as date 
				FROM `spotter_output`, airport 
				WHERE spotter_output.airline_icao <> '' AND airport.icao = spotter_output.real_arrival_airport_icao AND spotter_output.date >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 7 DAY) AND arrival_airport_icao = :airport_icao 
				GROUP BY spotter_output.airline_icao, real_arrival_airport_icao, DATE_FORMAT(DATE(CONVERT_TZ(spotter_output.date,'+00:00', :offset)),'%Y-%m-%d'),airport.name, airport.city, airport.country ORDER BY arrival_airport_count DESC";
			} else {
				$query = "SELECT spotter_output.airline_icao, COUNT(real_arrival_airport_icao) AS arrival_airport_count, real_arrival_airport_icao AS arrival_airport_icao, airport.name AS arrival_airport_name, airport.city AS arrival_airport_city, airport.country AS arrival_airport_country, to_char(spotter_output.date AT TIME ZONE INTERVAL :offset,'YYYY-mm-dd') as date 
				FROM spotter_output, airport 
				WHERE spotter_output.airline_icao <> '' AND  airport.icao = spotter_output.real_arrival_airport_icao AND spotter_output.date >= CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '7 DAYS' AND arrival_airport_icao = :airport_icao 
				GROUP BY spotter_output.airline_icao,real_arrival_airport_icao, to_char(spotter_output.date AT TIME ZONE INTERVAL :offset,'YYYY-mm-dd'), airport.name, airport.city, airport.country ORDER BY arrival_airport_count DESC";
			}
			$sth = $this->db->prepare($query);
			$sth->execute(array(':offset' => $offset, ':airport_icao' => $airport_icao));
		}
		return $sth->fetchAll(PDO::FETCH_ASSOC);
	}

	/**
	* Gets a list of all dates
	*
	* @return array list of date names
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
			$query  = "SELECT DISTINCT DATE(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) as date
								FROM spotter_output
								WHERE spotter_output.date <> '' 
								ORDER BY spotter_output.date ASC LIMIT 100 OFFSET 0";
		} else {
			$query  = "SELECT DISTINCT to_char(spotter_output.date AT TIME ZONE INTERVAL :offset,'YYYY-mm-dd') as date
								FROM spotter_output
								WHERE spotter_output.date <> '' 
								ORDER BY spotter_output.date ASC LIMIT 100 OFFSET 0";
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
	* Gets all route combinations
	*
	* @return array the route list
	*
	*/
	public function getAllRoutes()
	{
		$query  = "SELECT DISTINCT concat(spotter_output.departure_airport_icao, ' - ',  spotter_output.arrival_airport_icao) AS route,  spotter_output.departure_airport_icao, spotter_output.arrival_airport_icao 
				FROM spotter_output
				WHERE spotter_output.ident <> '' 
				GROUP BY route
				ORDER BY route ASC";

		$sth = $this->db->prepare($query);
		$sth->execute();

		$routes_array = array();
		$temp_array = array();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['route'] = $row['route'];
			$temp_array['airport_departure_icao'] = $row['departure_airport_icao'];
			$temp_array['airport_arrival_icao'] = $row['arrival_airport_icao'];

			$routes_array[] = $temp_array;
		}
		return $routes_array;
	}

    /**
     * Update ident spotter data
     *
     * @param String $flightaware_id the ID from flightaware
     * @param String $ident the flight ident
     * @param null $fromsource
     * @param null $sourcetype
     * @return String success or false
     */
	public function updateIdentSpotterData($flightaware_id = '', $ident = '',$fromsource = NULL,$sourcetype = NULL)
	{
		if (!is_numeric(substr($ident, 0, 3)) && !((substr($ident, 0, 3) == 'OGN' || substr($ident, 0, 3) == 'FLR' || substr($ident, 0, 3) == 'ICA') && $fromsource == 'aprs'))
		//if (!is_numeric(substr($ident, 0, 3)) && $sourcetype != 'flarm')
		{
			if (is_numeric(substr(substr($ident, 0, 3), -1, 1))) {
				$airline_array = $this->getAllAirlineInfo(substr($ident, 0, 2),$fromsource);
			} elseif (is_numeric(substr(substr($ident, 0, 4), -1, 1))) {
				$airline_array = $this->getAllAirlineInfo(substr($ident, 0, 3),$fromsource);
			} else {
				$airline_array = $this->getAllAirlineInfo("NA");
			}
			if (count($airline_array) == 0) {
				$airline_array = $this->getAllAirlineInfo("NA");
			}
			if (!isset($airline_array[0]['icao']) || $airline_array[0]['icao'] == ""){
				$airline_array = $this->getAllAirlineInfo("NA");
			}
		} else {
			$airline_array = $this->getAllAirlineInfo("NA");
		}
                $airline_name = $airline_array[0]['name'];
                $airline_icao = $airline_array[0]['icao'];
                $airline_country = $airline_array[0]['country'];
                $airline_type = $airline_array[0]['type'];


		$query = 'UPDATE spotter_output SET ident = :ident, airline_name = :airline_name, airline_icao = :airline_icao, airline_country = :airline_country, airline_type = :airline_type WHERE flightaware_id = :flightaware_id';
                $query_values = array(':flightaware_id' => $flightaware_id,':ident' => $ident,':airline_name' => $airline_name,':airline_icao' => $airline_icao,':airline_country' => $airline_country,':airline_type' => $airline_type);

		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch (PDOException $e) {
			return "error : ".$e->getMessage();
		}
		
		return "success";

	}

    /**
     * Update latest spotter data
     *
     * @param String $flightaware_id the ID from flightaware
     * @param String $ident the flight ident
     * @param string $latitude
     * @param string $longitude
     * @param string $altitude
     * @param string $altitude_real
     * @param bool $ground
     * @param null $groundspeed
     * @param string $date
     * @param String $arrival_airport_icao the arrival airport
     * @param string $arrival_airport_time
     * @return String success or false
     */
	public function updateLatestSpotterData($flightaware_id = '', $ident = '', $latitude = '', $longitude = '', $altitude = '',$altitude_real='', $ground = false, $groundspeed = NULL, $date = '', $arrival_airport_icao = '',$arrival_airport_time = '')
	{
		if ($groundspeed == '') $groundspeed = NULL;
		$query = 'UPDATE spotter_output SET ident = :ident, last_latitude = :last_latitude, last_longitude = :last_longitude, last_altitude = :last_altitude, last_ground = :last_ground, last_seen = :last_seen, real_arrival_airport_icao = :real_arrival_airport_icao, real_arrival_airport_time = :real_arrival_airport_time, last_ground_speed = :last_ground_speed WHERE flightaware_id = :flightaware_id';
                $query_values = array(':flightaware_id' => $flightaware_id,':real_arrival_airport_icao' => $arrival_airport_icao,':last_latitude' => $latitude,':last_longitude' => $longitude, ':last_altitude' => $altitude,':last_ground_speed' => $groundspeed,':last_seen' => $date,':real_arrival_airport_time' => $arrival_airport_time, ':last_ground' => $ground, ':ident' => $ident);

		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch (PDOException $e) {
			return "error : ".$e->getMessage();
		}
		
		return "success";
	}

    /**
     * Update initial spotter data
     *
     * @param String $flightaware_id the ID from flightaware
     * @param String $ident the flight ident
     * @param string $latitude
     * @param string $longitude
     * @param string $altitude
     * @param $altitude_real
     * @param bool $ground
     * @param null $groundspeed
     * @param string $date
     * @return String success or false
     */
	public function updateInitialSpotterData($flightaware_id = '', $ident = '', $latitude = '', $longitude = '', $altitude = '', $altitude_real,$ground = false, $groundspeed = NULL, $date = '')
	{
		if ($groundspeed == '') $groundspeed = NULL;
		$query = 'UPDATE spotter_output SET ident = :ident, latitude = :latitude, longitude = :longitude, altitude = :altitude, altitude_real = :altitude_real,ground = :ground, date = :date,ground_speed = :ground_speed WHERE flightaware_id = :flightaware_id';
                $query_values = array(':flightaware_id' => $flightaware_id,':latitude' => $latitude,':longitude' => $longitude, ':altitude' => $altitude,':ground_speed' => $groundspeed,':date' => $date, ':ground' => $ground, ':ident' => $ident,':altitude_real' => $altitude_real);

		try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch (PDOException $e) {
			return "error : ".$e->getMessage();
		}
		
		return "success";
	}

	/**
	* Update latest schedule spotter data
	*
	* @param String $flightaware_id the ID from flightaware
	* @param String $departure_airport_icao the departure airport ICAO
	* @param String $departure_airport_time the deaprture airport time
	* @param String $arrival_airport_icao the arrival airport ICAO
	* @param String $arrival_airport_time the arrival airport time
	* @return String success or false
	*
	*/	
	public function updateLatestScheduleSpotterData($flightaware_id = '', $departure_airport_icao = '', $departure_airport_time = '', $arrival_airport_icao = '',$arrival_airport_time = '')
	{
		$departure_airport_array = $this->getAllAirportInfo($departure_airport_icao);
        $arrival_airport_array = $this->getAllAirportInfo($arrival_airport_icao);
		if (isset($departure_airport_array[0]['name']) && isset($arrival_airport_array[0]['name'])) {
            $departure_airport_name = $departure_airport_array[0]['name'];
            $departure_airport_city = $departure_airport_array[0]['city'];
            $departure_airport_country = $departure_airport_array[0]['country'];

            $arrival_airport_name = $arrival_airport_array[0]['name'];
            $arrival_airport_city = $arrival_airport_array[0]['city'];
            $arrival_airport_country = $arrival_airport_array[0]['country'];

            $query = 'UPDATE spotter_output SET departure_airport_icao = :departure_airport_icao, departure_airport_name = :departure_airport_name, departure_airport_city = :departure_airport_city, departure_airport_country = :departure_airport_country, departure_airport_time = :departure_airport_time, arrival_airport_icao = :arrival_airport_icao, arrival_airport_city = :arrival_airport_city, arrival_airport_name = :arrival_airport_name, arrival_airport_country = :arrival_airport_country, arrival_airport_time = :arrival_airport_time WHERE flightaware_id = :flightaware_id';
            $query_values = array(':flightaware_id' => $flightaware_id, ':departure_airport_icao' => $departure_airport_icao, ':departure_airport_time' => $departure_airport_time, ':arrival_airport_icao' => $arrival_airport_icao, ':arrival_airport_time' => $arrival_airport_time, ':departure_airport_name' => $departure_airport_name, ':departure_airport_city' => $departure_airport_city, ':departure_airport_country' => $departure_airport_country, ':arrival_airport_name' => $arrival_airport_name, ':arrival_airport_city' => $arrival_airport_city, ':arrival_airport_country' => $arrival_airport_country);
            try {
                $sth = $this->db->prepare($query);
                $sth->execute($query_values);
            } catch (PDOException $e) {
                return "error : " . $e->getMessage();
            }
        }
		return "success";
	}

	/**
	* Adds a new spotter data
	*
	* @param String $flightaware_id the ID from flightaware
	* @param String $ident the flight ident
	* @param String $aircraft_icao the aircraft type
	* @param String $departure_airport_icao the departure airport
	* @param String $arrival_airport_icao the arrival airport
	* @param String $latitude latitude of flight
	* @param String $longitude latitude of flight
	* @param String $waypoints waypoints of flight
	* @param String $altitude altitude of flight
	* @param String $heading heading of flight
	* @param String $groundspeed speed of flight
	* @param String $date date of flight
	* @param String $departure_airport_time departure time of flight
	* @param String $arrival_airport_time arrival time of flight
	* @param String $squawk squawk code of flight
	* @param String $route_stop route stop of flight
	* @param String $highlight highlight or not
	* @param String $ModeS ModesS code of flight
	* @param String $registration registration code of flight
	* @param String $pilot_id pilot id of flight (for virtual airlines)
	* @param String $pilot_name pilot name of flight (for virtual airlines)
	* @param String $verticalrate vertival rate of flight
	* @return array success or false
	*/
	public function addSpotterData($flightaware_id = '', $ident = '', $aircraft_icao = '', $departure_airport_icao = '', $arrival_airport_icao = '', $latitude = '', $longitude = '', $waypoints = '', $altitude = '', $altitude_real = '',$heading = '', $groundspeed = '', $date = '', $departure_airport_time = '', $arrival_airport_time = '',$squawk = '', $route_stop = '', $highlight = '', $ModeS = '', $registration = '',$pilot_id = '', $pilot_name = '', $verticalrate = '', $ground = false,$format_source = '', $source_name = '',$source_type = '')
	{
		global $globalURL, $globalIVAO, $globalVATSIM, $globalphpVMS, $globalDebugTimeElapsed, $globalAirlinesSource, $globalVAM, $globalAircraftImageFetch, $globalVA;
		
		//if (isset($globalDebugTimeElapsed) || $globalDebugTimeElapsed == '') $globalDebugTimeElapsed = FALSE;
		$Image = new Image($this->db);
		$Common = new Common();
		
		if (!isset($globalIVAO)) $globalIVAO = FALSE;
		if (!isset($globalVATSIM)) $globalVATSIM = FALSE;
		if (!isset($globalphpVMS)) $globalphpVMS = FALSE;
		if (!isset($globalVAM)) $globalVAM = FALSE;
		if (!isset($globalVA)) $globalVA = FALSE;
		date_default_timezone_set('UTC');
		
		//getting the registration
		if ($flightaware_id != "" && $registration == '')
		{
			if (!is_string($flightaware_id))
			{
				return array();
			} else {
				if ($ModeS != '') {
					$timeelapsed = microtime(true);
					$registration = $this->getAircraftRegistrationBymodeS($ModeS,$source_type);
					if ($globalDebugTimeElapsed) echo 'ADD SPOTTER DATA : Time elapsed for getAircraftRegistrationBymodes : '.round(microtime(true)-$timeelapsed,2).'s'."\n";
				} else {
					$myhex = explode('-',$flightaware_id);
					if (count($myhex) > 0) {
						$timeelapsed = microtime(true);
						$registration = $this->getAircraftRegistrationBymodeS($myhex[0],$source_type);
						if ($globalDebugTimeElapsed) echo 'ADD SPOTTER DATA : Time elapsed for getAircraftRegistrationBymodes : '.round(microtime(true)-$timeelapsed,2).'s'."\n";
					}
				}
			}
		}
		$fromsource = NULL;
		if (isset($globalAirlinesSource) && $globalAirlinesSource != '') $fromsource = $globalAirlinesSource;
		elseif ($format_source == 'vatsimtxt') $fromsource = 'vatsim';
		elseif ($format_source == 'whazzup') $fromsource = 'ivao';
		elseif (isset($globalVATSIM) && $globalVATSIM) $fromsource = 'vatsim';
		elseif (isset($globalIVAO) && $globalIVAO) $fromsource = 'ivao';
		//getting the airline information
		if ($ident != "")
		{
			if (!is_string($ident))
			{
				return array();
			} else {
				if (!is_numeric(substr($ident, 0, 3)) && !((substr($ident, 0, 3) == 'OGN' || substr($ident, 0, 3) == 'FLR' || substr($ident, 0, 3) == 'ICA') && $format_source == 'aprs'))
				//if (!is_numeric(substr($ident, 0, 3)) && $source_type != 'flarm')
				{
					$timeelapsed = microtime(true);
					if (is_numeric(substr(substr($ident, 0, 3), -1, 1))) {
						$airline_array = $this->getAllAirlineInfo(substr($ident, 0, 2),$fromsource);
					} elseif (is_numeric(substr(substr($ident, 0, 4), -1, 1))) {
						$airline_array = $this->getAllAirlineInfo(substr($ident, 0, 3),$fromsource);
					} else {
						$airline_array = $this->getAllAirlineInfo("NA");
					}
					if (count($airline_array) == 0) {
						$airline_array = $this->getAllAirlineInfo("NA");
					}
					if (!isset($airline_array[0]['icao']) || $airline_array[0]['icao'] == ""){
						$airline_array = $this->getAllAirlineInfo("NA");
					}
					if ($globalDebugTimeElapsed) echo 'ADD SPOTTER DATA : Time elapsed for getAirlineInfo : '.round(microtime(true)-$timeelapsed,2).'s'."\n";

				} else {
					$timeelapsed = microtime(true);
					$airline_array = $this->getAllAirlineInfo("NA");
					if ($globalDebugTimeElapsed) echo 'ADD SPOTTER DATA : Time elapsed for getAirlineInfo(NA) : '.round(microtime(true)-$timeelapsed,2).'s'."\n";
				}
			}
		} else $airline_array = array();
		
		//getting the aircraft information
		$aircraft_array = array();
		if ($aircraft_icao != '')
		{
			if (!is_string($aircraft_icao))
			{
				return array();
			} else {
				if ($aircraft_icao == "" || $aircraft_icao == "XXXX")
				{
					$timeelapsed = microtime(true);
					$aircraft_array = $this->getAllAircraftInfo("NA");
					if ($globalDebugTimeElapsed) echo 'ADD SPOTTER DATA : Time elapsed for getAircraftInfo(NA) : '.round(microtime(true)-$timeelapsed,2).'s'."\n";
				} else {
					$timeelapsed = microtime(true);
					$aircraft_array = $this->getAllAircraftInfo($aircraft_icao);
					if ($globalDebugTimeElapsed) echo 'ADD SPOTTER DATA : Time elapsed for getAircraftInfo : '.round(microtime(true)-$timeelapsed,2).'s'."\n";
				}
			}
		} else {
			if ($ModeS != '') {
				$timeelapsed = microtime(true);
				$aircraft_icao = $this->getAllAircraftType($ModeS,$source_type);
				if ($globalDebugTimeElapsed) echo 'ADD SPOTTER DATA : Time elapsed for getAllAircraftType : '.round(microtime(true)-$timeelapsed,2).'s'."\n";
				if ($aircraft_icao == "" || $aircraft_icao == "XXXX")
				{
					$timeelapsed = microtime(true);
					$aircraft_array = $this->getAllAircraftInfo("NA");
					if ($globalDebugTimeElapsed) echo 'ADD SPOTTER DATA : Time elapsed for getAircraftInfo(NA) : '.round(microtime(true)-$timeelapsed,2).'s'."\n";
				} else {
					$timeelapsed = microtime(true);
					$aircraft_array = $this->getAllAircraftInfo($aircraft_icao);
					if ($globalDebugTimeElapsed) echo 'ADD SPOTTER DATA : Time elapsed for getAircraftInfo : '.round(microtime(true)-$timeelapsed,2).'s'."\n";
				}
			}
		}
		
		//getting the departure airport information
		$departure_airport_array = array();
		$departure_airport_icao = trim($departure_airport_icao);
		if ($departure_airport_icao != '')
		{
			if (!is_string($departure_airport_icao))
			{
				return array();
			} else {
				$timeelapsed = microtime(true);
				$departure_airport_array = $this->getAllAirportInfo($departure_airport_icao);
				if ($globalDebugTimeElapsed) echo 'ADD SPOTTER DATA : Time elapsed for getAllAirportInfo : '.round(microtime(true)-$timeelapsed,2).'s'."\n";
			}
		}
		
		//getting the arrival airport information
		$arrival_airport_array = array();
		$arrival_airport_icao = trim($arrival_airport_icao);
		if ($arrival_airport_icao != '')
		{
			if (!is_string($arrival_airport_icao))
			{
				return array();
			} else {
				$timeelapsed = microtime(true);
				$arrival_airport_array = $this->getAllAirportInfo($arrival_airport_icao);
				if ($globalDebugTimeElapsed) echo 'ADD SPOTTER DATA : Time elapsed for getAllAirportInfo : '.round(microtime(true)-$timeelapsed,2).'s'."\n";
			}
		}

		if ($latitude != "")
		{
			if (!is_numeric($latitude))
			{
				return array();
			}
		}
		
		if ($longitude != "")
		{
			if (!is_numeric($longitude))
			{
				return array();
			}
		}
		
		if ($waypoints != "")
		{
			if (!is_string($waypoints))
			{
				return array();
			}
		}
		
		if ($altitude != "")
		{
			if (!is_numeric($altitude))
			{
				return array();
			}
		} else $altitude = 0;
		if ($altitude_real != "")
		{
			if (!is_numeric($altitude_real))
			{
				return array();
			}
		} else $altitude_real = 0;
		
		if ($heading != "")
		{
			if (!is_numeric($heading))
			{
				return array();
			}
		}
		
		if ($groundspeed != "")
		{
			if (!is_numeric($groundspeed))
			{
				return array();
			}
		}

    
		if ($date == "" || strtotime($date) < time()-20*60)
		{
			$date = date("Y-m-d H:i:s", time());
		}

		//getting the aircraft image
		if (($registration != "" || $registration != 'NA') && !$globalVA && !$globalIVAO && !$globalVATSIM && !$globalphpVMS && !$globalVAM)
		{
			if (isset($globalAircraftImageFetch) && $globalAircraftImageFetch === TRUE) {
				$timeelapsed = microtime(true);
				$image_array = $Image->getSpotterImage($registration);
				if ($globalDebugTimeElapsed) echo 'ADD SPOTTER DATA : Time elapsed for getSpotterImage : '.round(microtime(true)-$timeelapsed,2).'s'."\n";
				if (!isset($image_array[0]['registration']))
				{
					//echo "Add image !!!! \n";
					$Image->addSpotterImage($registration);
				}
			}
			$timeelapsed = microtime(true);
			$owner_info = $this->getAircraftOwnerByRegistration($registration);
			if ($globalDebugTimeElapsed) echo 'ADD SPOTTER DATA : Time elapsed for getAircraftOwnerByRegistration : '.round(microtime(true)-$timeelapsed,2).'s'."\n";
			if ($owner_info['owner'] != '') $aircraft_owner = ucwords(strtolower($owner_info['owner']));
		}
    
		if (($globalVA || $globalIVAO || $globalVATSIM || $globalphpVMS || $globalVAM) && $aircraft_icao != '' && isset($globalAircraftImageFetch) && $globalAircraftImageFetch === TRUE)
		{
            		if (isset($airline_array[0]['icao'])) $airline_icao = $airline_array[0]['icao'];
            		else $airline_icao = '';
			$image_array = $Image->getSpotterImage('',$aircraft_icao,$airline_icao);
			if (!isset($image_array[0]['registration']))
			{
				//echo "Add image !!!! \n";
				$Image->addSpotterImage('',$aircraft_icao,$airline_icao);
			}
		}
    
		$flightaware_id = filter_var($flightaware_id,FILTER_SANITIZE_STRING);
		$ident = filter_var($ident,FILTER_SANITIZE_STRING);
		$registration = filter_var($registration,FILTER_SANITIZE_STRING);
		$aircraft_icao = filter_var($aircraft_icao,FILTER_SANITIZE_STRING);
		$departure_airport_icao = filter_var($departure_airport_icao,FILTER_SANITIZE_STRING);
		$arrival_airport_icao = filter_var($arrival_airport_icao,FILTER_SANITIZE_STRING);
		$latitude = filter_var($latitude,FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
		$longitude = filter_var($longitude,FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
		$waypoints = filter_var($waypoints,FILTER_SANITIZE_STRING);
		$altitude = filter_var($altitude,FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
		$altitude_real = filter_var($altitude_real,FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
		$heading = filter_var($heading,FILTER_SANITIZE_NUMBER_INT);
		$groundspeed = filter_var($groundspeed,FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
		$squawk = filter_var($squawk,FILTER_SANITIZE_NUMBER_INT);
		$route_stop = filter_var($route_stop,FILTER_SANITIZE_STRING);
		$ModeS = filter_var($ModeS,FILTER_SANITIZE_STRING);
		$pilot_id = filter_var($pilot_id,FILTER_SANITIZE_STRING);
		$pilot_name = filter_var($pilot_name,FILTER_SANITIZE_STRING);
		$format_source = filter_var($format_source,FILTER_SANITIZE_STRING);
		$verticalrate = filter_var($verticalrate,FILTER_SANITIZE_NUMBER_INT);
	
		if (count($airline_array) == 0) 
		{
                        $airline_array = $this->getAllAirlineInfo('NA');
                }
                if (count($aircraft_array) == 0) 
                {
                        $aircraft_array = $this->getAllAircraftInfo('NA');
                }
                if (count($departure_airport_array) == 0 || $departure_airport_array[0]['icao'] == '' || $departure_airport_icao == '') 
                {
                        $departure_airport_array = $this->getAllAirportInfo('NA');
                }
                if (count($arrival_airport_array) == 0 || $arrival_airport_array[0]['icao'] == '' || $arrival_airport_icao == '') 
                {
                        $arrival_airport_array = $this->getAllAirportInfo('NA');
                }
                if ($registration == '') $registration = 'NA';
                if ($latitude == '' && $longitude == '') {
            		$latitude = 0;
            		$longitude = 0;
            	}
                if ($squawk == '' || $Common->isInteger($squawk) === false) $squawk = NULL;
                if ($verticalrate == '' || $Common->isInteger($verticalrate) === false) $verticalrate = NULL;
                if ($heading == '' || $Common->isInteger($heading) === false) $heading = 0;
                if ($groundspeed == '' || $Common->isInteger($groundspeed) === false) $groundspeed = 0;
                if (!isset($aircraft_owner)) $aircraft_owner = NULL;
                $query  = "INSERT INTO spotter_output (flightaware_id, ident, registration, airline_name, airline_icao, airline_country, airline_type, aircraft_icao, aircraft_name, aircraft_manufacturer, departure_airport_icao, departure_airport_name, departure_airport_city, departure_airport_country, arrival_airport_icao, arrival_airport_name, arrival_airport_city, arrival_airport_country, latitude, longitude, waypoints, altitude, heading, ground_speed, date, departure_airport_time, arrival_airport_time, squawk, route_stop,highlight,ModeS, pilot_id, pilot_name, verticalrate, owner_name, ground, format_source, source_name,real_altitude) 
                VALUES (:flightaware_id,:ident,:registration,:airline_name,:airline_icao,:airline_country,:airline_type,:aircraft_icao,:aircraft_type,:aircraft_manufacturer,:departure_airport_icao,:departure_airport_name,:departure_airport_city,:departure_airport_country, :arrival_airport_icao, :arrival_airport_name, :arrival_airport_city, :arrival_airport_country, :latitude,:longitude,:waypoints,:altitude,:heading,:groundspeed,:date, :departure_airport_time, :arrival_airport_time, :squawk, :route_stop, :highlight, :ModeS, :pilot_id, :pilot_name, :verticalrate, :owner_name,:ground, :format_source, :source_name,:altitude_real)";

                $airline_name = $airline_array[0]['name'];
                $airline_icao = $airline_array[0]['icao'];
                $airline_country = $airline_array[0]['country'];
                $airline_type = $airline_array[0]['type'];
		if ($airline_type == '') {
			$timeelapsed = microtime(true);
			$airline_type = $this->getAircraftTypeBymodeS($ModeS);
			if ($globalDebugTimeElapsed) echo 'ADD SPOTTER DATA : Time elapsed for getAircraftTypeBymodes : '.round(microtime(true)-$timeelapsed,2).'s'."\n";
		}
		if ($airline_type == null) $airline_type = '';
                $aircraft_type = $aircraft_array[0]['type'];
                $aircraft_manufacturer = $aircraft_array[0]['manufacturer'];
                $departure_airport_name = $departure_airport_array[0]['name'];
	        $departure_airport_city = $departure_airport_array[0]['city'];
            	$departure_airport_country = $departure_airport_array[0]['country'];
                
                $arrival_airport_name = $arrival_airport_array[0]['name'];
                $arrival_airport_city = $arrival_airport_array[0]['city'];
                $arrival_airport_country = $arrival_airport_array[0]['country'];
                $query_values = array(':flightaware_id' => $flightaware_id,':ident' => $ident, ':registration' => $registration,':airline_name' => $airline_name,':airline_icao' => $airline_icao,':airline_country' => $airline_country,':airline_type' => $airline_type,':aircraft_icao' => $aircraft_icao,':aircraft_type' => $aircraft_type,':aircraft_manufacturer' => $aircraft_manufacturer,':departure_airport_icao' => $departure_airport_icao,':departure_airport_name' => $departure_airport_name,':departure_airport_city' => $departure_airport_city,':departure_airport_country' => $departure_airport_country,':arrival_airport_icao' => $arrival_airport_icao,':arrival_airport_name' => $arrival_airport_name,':arrival_airport_city' => $arrival_airport_city,':arrival_airport_country' => $arrival_airport_country,':latitude' => $latitude,':longitude' => $longitude, ':waypoints' => $waypoints,':altitude' => $altitude,':heading' => $heading,':groundspeed' => $groundspeed,':date' => $date,':departure_airport_time' => $departure_airport_time,':arrival_airport_time' => $arrival_airport_time, ':squawk' => $squawk, ':route_stop' => $route_stop, ':highlight' => $highlight, ':ModeS' => $ModeS, ':pilot_id' => $pilot_id, ':pilot_name' => $pilot_name, ':verticalrate' => $verticalrate, ':owner_name' => $aircraft_owner, ':format_source' => $format_source, ':ground' => $ground, ':source_name' => $source_name,':altitude_real' => $altitude_real);

		try {
		        
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
			$sth->closeCursor();
			$this->db = null;
		} catch (PDOException $e) {
		    return array('error' => $e->getMessage());
		}
		return $query_values;
		//return "success";
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
			$query  = "SELECT spotter_output.ident FROM spotter_output 
								WHERE spotter_output.ident = :ident 
								AND spotter_output.date >= DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 HOUR) 
								AND spotter_output.date < UTC_TIMESTAMP()";
			$query_data = array(':ident' => $ident);
		} else {
			$query  = "SELECT spotter_output.ident FROM spotter_output 
								WHERE spotter_output.ident = :ident 
								AND spotter_output.date >= now() AT TIME ZONE 'UTC' - INTERVAL '1 HOURS'
								AND spotter_output.date < now() AT TIME ZONE 'UTC'";
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
     * @return array the spotter data
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
					$additional_query .= "(spotter_output.aircraft_icao like '%".$q_item."%') OR ";
					$additional_query .= "(spotter_output.aircraft_name like '%".$q_item."%') OR ";
					$additional_query .= "(spotter_output.aircraft_manufacturer like '%".$q_item."%') OR ";
					$additional_query .= "(spotter_output.airline_icao like '%".$q_item."%') OR ";
					$additional_query .= "(spotter_output.departure_airport_icao like '%".$q_item."%') OR ";
					$additional_query .= "(spotter_output.arrival_airport_icao like '%".$q_item."%') OR ";
					$additional_query .= "(spotter_output.registration like '%".$q_item."%') OR ";
					$additional_query .= "(spotter_output.ident like '%".$q_item."%')";
					$additional_query .= ")";
				}
			}
		}
		if ($globalDBdriver == 'mysql') {
			$query  = "SELECT spotter_output.* FROM spotter_output 
				WHERE spotter_output.date >= DATE_SUB(UTC_TIMESTAMP(),INTERVAL 20 SECOND) ".$additional_query." 
				AND spotter_output.date < UTC_TIMESTAMP()";
		} else {
			$query  = "SELECT spotter_output.* FROM spotter_output 
				WHERE spotter_output.date::timestamp >= CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '20 SECONDS' ".$additional_query." 
				AND spotter_output.date::timestamp < CURRENT_TIMESTAMP AT TIME ZONE 'UTC'";
		}
		$spotter_array = $this->getDataFromDB($query, array());

		return $spotter_array;
	}


    /**
     * Gets all airlines that have flown over
     *
     * @param bool $limit
     * @param int $olderthanmonths
     * @param string $sincedate
     * @param array $filters
     * @param string $year
     * @param string $month
     * @param string $day
     * @return array the airline list
     */
	public function countAllAirlines($limit = true, $olderthanmonths = 0, $sincedate = '',$filters = array(), $year = '', $month = '', $day = '')
	{
		global $globalDBdriver;
		$filter_query = $this->getFilter($filters,true,true);
		$query  = "SELECT DISTINCT spotter_output.airline_name, spotter_output.airline_icao, spotter_output.airline_country, COUNT(spotter_output.airline_name) AS airline_count
		 			FROM spotter_output".$filter_query." spotter_output.airline_name <> '' AND spotter_output.airline_icao <> 'NA'";
		if ($olderthanmonths > 0) {
			if ($globalDBdriver == 'mysql') {
				$query .= ' AND spotter_output.date < DATE_SUB(UTC_TIMESTAMP(), INTERVAL '.$olderthanmonths.' MONTH)';
			} else {
				$query .= " AND spotter_output.date < CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$olderthanmonths." MONTHS'";
			}
		}
                if ($sincedate != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND spotter_output.date > '".$sincedate."'";
			} else {
				$query .= " AND spotter_output.date > CAST('".$sincedate."' AS TIMESTAMP)";
			}
		}
		$query_values = array();
		if ($year != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND YEAR(spotter_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			} else {
				$query .= " AND EXTRACT(YEAR FROM spotter_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			}
		}
		if ($month != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND MONTH(spotter_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			} else {
				$query .= " AND EXTRACT(MONTH FROM spotter_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			}
		}
		if ($day != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND DAY(spotter_output.date) = :day";
				$query_values = array_merge($query_values,array(':day' => $day));
			} else {
				$query .= " AND EXTRACT(DAY FROM spotter_output.date) = :day";
				$query_values = array_merge($query_values,array(':day' => $day));
			}
		}
		$query .= " GROUP BY spotter_output.airline_name,spotter_output.airline_icao, spotter_output.airline_country ORDER BY airline_count DESC";
		if ($limit) $query .= " LIMIT 10 OFFSET 0";

		$sth = $this->db->prepare($query);
		$sth->execute($query_values);
		$airline_array = array();
		$temp_array = array();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airline_name'] = $row['airline_name'];
			$temp_array['airline_icao'] = $row['airline_icao'];
			$temp_array['airline_count'] = $row['airline_count'];
			$temp_array['airline_country'] = $row['airline_country'];
			$airline_array[] = $temp_array;
		}
		return $airline_array;
	}

    /**
     * Gets all pilots that have flown over
     *
     * @param bool $limit
     * @param int $olderthanmonths
     * @param string $sincedate
     * @param array $filters
     * @param string $year
     * @param string $month
     * @param string $day
     * @return array the pilots list
     */
	public function countAllPilots($limit = true, $olderthanmonths = 0, $sincedate = '',$filters = array(),$year = '', $month = '',$day = '')
	{
		global $globalDBdriver;
		$filter_query = $this->getFilter($filters,true,true);
		$query  = "SELECT DISTINCT spotter_output.pilot_id, s.pilot_name, COUNT(spotter_output.pilot_id) AS pilot_count, spotter_output.format_source
			FROM spotter_output LEFT JOIN (SELECT DISTINCT pilot_id, pilot_name, max(date) as date FROM spotter_output GROUP BY pilot_id, pilot_name) s ON s.pilot_id = spotter_output.pilot_id".$filter_query." spotter_output.pilot_id <> ''";
                if ($olderthanmonths > 0) {
            		if ($globalDBdriver == 'mysql') {
				$query .= ' AND spotter_output.date < DATE_SUB(UTC_TIMESTAMP(), INTERVAL '.$olderthanmonths.' MONTH)';
			} else {
				$query .= " AND spotter_output.date < CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$olderthanmonths." MONTHS'";
			}
		}
                if ($sincedate != '') {
            		if ($globalDBdriver == 'mysql') {
				$query .= " AND spotter_output.date > '".$sincedate."'";
			} else {
				$query .= " AND spotter_output.date > CAST('".$sincedate."' AS TIMESTAMP)";
			}
		}
		$query_values = array();
		if ($year != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND YEAR(spotter_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			} else {
				$query .= " AND EXTRACT(YEAR FROM spotter_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			}
		}
		if ($month != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND MONTH(spotter_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			} else {
				$query .= " AND EXTRACT(MONTH FROM spotter_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			}
		}
		if ($day != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND DAY(spotter_output.date) = :day";
				$query_values = array_merge($query_values,array(':day' => $day));
			} else {
				$query .= " AND EXTRACT(DAY FROM spotter_output.date) = :day";
				$query_values = array_merge($query_values,array(':day' => $day));
			}
		}
		
		$query .= " GROUP BY spotter_output.pilot_id,s.pilot_name,spotter_output.format_source ORDER BY pilot_count DESC";
		if ($limit) $query .= " LIMIT 10 OFFSET 0";
      
		
		$sth = $this->db->prepare($query);
		$sth->execute($query_values);
		$airline_array = array();
		$temp_array = array();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['pilot_name'] = $row['pilot_name'];
			$temp_array['pilot_id'] = $row['pilot_id'];
			$temp_array['pilot_count'] = $row['pilot_count'];
			$temp_array['format_source'] = $row['format_source'];
			$airline_array[] = $temp_array;
		}
		return $airline_array;
	}

    /**
     * Gets all pilots that have flown over
     *
     * @param bool $limit
     * @param int $olderthanmonths
     * @param string $sincedate
     * @return array the pilots list
     */
	public function countAllPilotsByAirlines($limit = true, $olderthanmonths = 0, $sincedate = '')
	{
		global $globalDBdriver;
		$query  = "SELECT DISTINCT spotter_output.airline_icao, spotter_output.pilot_id, spotter_output.pilot_name, COUNT(spotter_output.pilot_id) AS pilot_count, spotter_output.format_source
		 			FROM spotter_output WHERE spotter_output.pilot_id <> '' ";
                if ($olderthanmonths > 0) {
            		if ($globalDBdriver == 'mysql') {
				$query .= 'AND spotter_output.date < DATE_SUB(UTC_TIMESTAMP(), INTERVAL '.$olderthanmonths.' MONTH) ';
			} else {
				$query .= "AND spotter_output.date < CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$olderthanmonths." MONTHS' ";
			}
		}
                if ($sincedate != '') {
            		if ($globalDBdriver == 'mysql') {
				$query .= "AND spotter_output.date > '".$sincedate."' ";
			} else {
				$query .= "AND spotter_output.date > CAST('".$sincedate."' AS TIMESTAMP)";
			}
		}
		$query .= "GROUP BY spotter_output.airline_icao, spotter_output.pilot_id,spotter_output.pilot_name,spotter_output.format_source ORDER BY pilot_count DESC";
		if ($limit) $query .= " LIMIT 10 OFFSET 0";
      
		
		$sth = $this->db->prepare($query);
		$sth->execute();
      
		$airline_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['pilot_name'] = $row['pilot_name'];
			$temp_array['pilot_id'] = $row['pilot_id'];
			$temp_array['pilot_count'] = $row['pilot_count'];
			$temp_array['airline_icao'] = $row['airline_icao'];
			$temp_array['format_source'] = $row['format_source'];
			$airline_array[] = $temp_array;
		}
		return $airline_array;
	}

    /**
     * Gets all owner that have flown over
     *
     * @param bool $limit
     * @param int $olderthanmonths
     * @param string $sincedate
     * @param array $filters
     * @param string $year
     * @param string $month
     * @param string $day
     * @return array the pilots list
     */
	public function countAllOwners($limit = true, $olderthanmonths = 0, $sincedate = '',$filters = array(),$year = '',$month = '',$day = '')
	{
		global $globalDBdriver;
		$filter_query = $this->getFilter($filters,true,true);
		$query  = "SELECT DISTINCT spotter_output.owner_name, COUNT(spotter_output.owner_name) AS owner_count
					FROM spotter_output".$filter_query." spotter_output.owner_name <> '' AND spotter_output.owner_name IS NOT NULL";
                if ($olderthanmonths > 0) {
            		if ($globalDBdriver == 'mysql') {
				$query .= ' AND spotter_output.date < DATE_SUB(UTC_TIMESTAMP(), INTERVAL '.$olderthanmonths.' MONTH)';
			} else {
				$query .= " AND spotter_output.date < CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$olderthanmonths." MONTHS'";
			}
		}
                if ($sincedate != '') {
            		if ($globalDBdriver == 'mysql') {
				$query .= " AND spotter_output.date > '".$sincedate."' ";
			} else {
				$query .= " AND spotter_output.date > CAST('".$sincedate."' AS TIMESTAMP)";
			}
		}
		$query_values = array();
		if ($year != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND YEAR(spotter_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			} else {
				$query .= " AND EXTRACT(YEAR FROM spotter_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			}
		}
		if ($month != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND MONTH(spotter_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			} else {
				$query .= " AND EXTRACT(MONTH FROM spotter_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			}
		}
		if ($day != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND DAY(spotter_output.date) = :day";
				$query_values = array_merge($query_values,array(':day' => $day));
			} else {
				$query .= " AND EXTRACT(DAY FROM spotter_output.date) = :day";
				$query_values = array_merge($query_values,array(':day' => $day));
			}
		}
		$query .= " GROUP BY spotter_output.owner_name ORDER BY owner_count DESC";
		if ($limit) $query .= " LIMIT 10 OFFSET 0";
		
		$sth = $this->db->prepare($query);
		$sth->execute($query_values);
		$airline_array = array();
		$temp_array = array();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['owner_name'] = $row['owner_name'];
			$temp_array['owner_count'] = $row['owner_count'];
			$airline_array[] = $temp_array;
		}
		return $airline_array;
	}

    /**
     * Gets all owner that have flown over
     *
     * @param bool $limit
     * @param int $olderthanmonths
     * @param string $sincedate
     * @param array $filters
     * @return array the pilots list
     */
	public function countAllOwnersByAirlines($limit = true, $olderthanmonths = 0, $sincedate = '',$filters = array())
	{
		global $globalDBdriver;
		$filter_query = $this->getFilter($filters,true,true);
		$query  = "SELECT DISTINCT spotter_output.airline_icao, spotter_output.owner_name, COUNT(spotter_output.owner_name) AS owner_count
		 			FROM spotter_output".$filter_query." spotter_output.owner_name <> '' AND spotter_output.owner_name IS NOT NULL ";
                if ($olderthanmonths > 0) {
            		if ($globalDBdriver == 'mysql') {
				$query .= 'AND spotter_output.date < DATE_SUB(UTC_TIMESTAMP(), INTERVAL '.$olderthanmonths.' MONTH) ';
			} else {
				$query .= "AND spotter_output.date < CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$olderthanmonths." MONTHS' ";
			}
		}
                if ($sincedate != '') {
            		if ($globalDBdriver == 'mysql') {
				$query .= "AND spotter_output.date > '".$sincedate."' ";
			} else {
				$query .= "AND spotter_output.date > CAST('".$sincedate."' AS TIMESTAMP)";
			}
		}
		$query .= "GROUP BY spotter_output.airline_icao, spotter_output.owner_name ORDER BY owner_count DESC";
		if ($limit) $query .= " LIMIT 10 OFFSET 0";
      
		
		$sth = $this->db->prepare($query);
		$sth->execute();
      
		$airline_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['owner_name'] = $row['owner_name'];
			$temp_array['owner_count'] = $row['owner_count'];
			$temp_array['airline_icao'] = $row['airline_icao'];
			$airline_array[] = $temp_array;
		}
		return $airline_array;
	}

    /**
     * Gets all airlines that have flown over by aircraft
     *
     * @param $aircraft_icao
     * @param array $filters
     * @return array the airline list
     */
	public function countAllAirlinesByAircraft($aircraft_icao,$filters = array())
	{
		$aircraft_icao = filter_var($aircraft_icao,FILTER_SANITIZE_STRING);
		$filter_query = $this->getFilter($filters,true,true);
		$query  = "SELECT DISTINCT spotter_output.airline_name, spotter_output.airline_icao, spotter_output.airline_country, COUNT(spotter_output.airline_name) AS airline_count
		 	    FROM spotter_output".$filter_query." spotter_output.airline_name <> '' AND spotter_output.aircraft_icao = :aircraft_icao 
			    GROUP BY spotter_output.airline_name, spotter_output.airline_icao, spotter_output.airline_country 
			    ORDER BY airline_count DESC";
      
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':aircraft_icao' => $aircraft_icao));
      
		$airline_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airline_name'] = $row['airline_name'];
			$temp_array['airline_icao'] = $row['airline_icao'];
			$temp_array['airline_count'] = $row['airline_count'];
			$temp_array['airline_country'] = $row['airline_country'];

			$airline_array[] = $temp_array;
		}

		return $airline_array;
	}


    /**
     * Gets all airline countries that have flown over by aircraft
     *
     * @param $aircraft_icao
     * @param array $filters
     * @return array the airline country list
     */
	public function countAllAirlineCountriesByAircraft($aircraft_icao,$filters = array())
	{
		$aircraft_icao = filter_var($aircraft_icao,FILTER_SANITIZE_STRING);
		$filter_query = $this->getFilter($filters,true,true);
		$query  = "SELECT DISTINCT spotter_output.airline_country, COUNT(spotter_output.airline_country) AS airline_country_count, countries.iso3 AS airline_country_iso3 
			FROM spotter_output, countries ".$filter_query." countries.name = spotter_output.airline_country AND spotter_output.aircraft_icao = :aircraft_icao
			GROUP BY spotter_output.airline_country, countries.iso3
			ORDER BY airline_country_count DESC
			LIMIT 10 OFFSET 0";
      
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':aircraft_icao' => $aircraft_icao));
      
		$airline_country_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airline_country_count'] = $row['airline_country_count'];
			$temp_array['airline_country'] = $row['airline_country'];
			$temp_array['airline_country_iso3'] = $row['airline_country_iso3'];
 
			$airline_country_array[] = $temp_array;
		}
		return $airline_country_array;
	}


    /**
     * Gets all airlines that have flown over by airport
     *
     * @param $airport_icao
     * @param array $filters
     * @return array the airline list
     */
	public function countAllAirlinesByAirport($airport_icao,$filters = array())
	{
		$airport_icao = filter_var($airport_icao,FILTER_SANITIZE_STRING);
		$filter_query = $this->getFilter($filters,true,true);
		$query  = "SELECT DISTINCT spotter_output.airline_name, spotter_output.airline_icao, spotter_output.airline_country, COUNT(spotter_output.airline_name) AS airline_count
		    FROM spotter_output".$filter_query." spotter_output.airline_name <> '' AND (spotter_output.departure_airport_icao = :airport_icao OR spotter_output.arrival_airport_icao = :airport_icao ) 
                    GROUP BY spotter_output.airline_name, spotter_output.airline_icao, spotter_output.airline_country
		    ORDER BY airline_count DESC";
      
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':airport_icao' => $airport_icao));
      
		$airline_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airline_name'] = $row['airline_name'];
			$temp_array['airline_icao'] = $row['airline_icao'];
			$temp_array['airline_count'] = $row['airline_count'];
			$temp_array['airline_country'] = $row['airline_country'];

			$airline_array[] = $temp_array;
		}
		return $airline_array;
	}


    /**
     * Gets all airline countries that have flown over by airport icao
     *
     * @param $airport_icao
     * @param array $filters
     * @return array the airline country list
     */
	public function countAllAirlineCountriesByAirport($airport_icao,$filters = array())
	{
		$airport_icao = filter_var($airport_icao,FILTER_SANITIZE_STRING);
		$filter_query = $this->getFilter($filters,true,true);
		$query  = "SELECT DISTINCT spotter_output.airline_country, COUNT(spotter_output.airline_country) AS airline_country_count, countries.iso3 AS airline_country_iso3 
			FROM countries, spotter_output".$filter_query." countries.name = spotter_output.airline_country AND (spotter_output.departure_airport_icao = :airport_icao OR spotter_output.arrival_airport_icao = :airport_icao )
			GROUP BY spotter_output.airline_country, countries.iso3
			ORDER BY airline_country_count DESC
			LIMIT 10 OFFSET 0";

		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':airport_icao' => $airport_icao));

		$airline_country_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airline_country_count'] = $row['airline_country_count'];
			$temp_array['airline_country'] = $row['airline_country'];
			$temp_array['airline_country_iso3'] = $row['airline_country_iso3'];
 
			$airline_country_array[] = $temp_array;
		}
		return $airline_country_array;
	}


    /**
     * Gets all airlines that have flown over by aircraft manufacturer
     *
     * @param $aircraft_manufacturer
     * @param array $filters
     * @return array the airline list
     */
	public function countAllAirlinesByManufacturer($aircraft_manufacturer,$filters = array())
	{
		$aircraft_manufacturer = filter_var($aircraft_manufacturer,FILTER_SANITIZE_STRING);
		$filter_query = $this->getFilter($filters,true,true);
		$query  = "SELECT DISTINCT spotter_output.airline_name, spotter_output.airline_icao, spotter_output.airline_country, COUNT(spotter_output.airline_name) AS airline_count
		 			FROM spotter_output".$filter_query." spotter_output.aircraft_manufacturer = :aircraft_manufacturer 
					GROUP BY spotter_output.airline_name
					ORDER BY airline_count DESC";
 
		$sth = $this->db->prepare($query);
		$sth->execute(array(':aircraft_manufacturer' => $aircraft_manufacturer));
 
		$airline_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airline_name'] = $row['airline_name'];
			$temp_array['airline_icao'] = $row['airline_icao'];
			$temp_array['airline_count'] = $row['airline_count'];
			$temp_array['airline_country'] = $row['airline_country'];

			$airline_array[] = $temp_array;
		}
		return $airline_array;
	}


    /**
     * Gets all airline countries that have flown over by aircraft manufacturer
     *
     * @param $aircraft_manufacturer
     * @param array $filters
     * @return array the airline country list
     */
	public function countAllAirlineCountriesByManufacturer($aircraft_manufacturer,$filters = array())
	{
		$aircraft_manufacturer = filter_var($aircraft_manufacturer,FILTER_SANITIZE_STRING);
		$filter_query = $this->getFilter($filters,true,true);
		$query  = "SELECT DISTINCT spotter_output.airline_country, COUNT(spotter_output.airline_country) AS airline_country_count, countries.iso3 AS airline_country_iso3
		 			FROM spotter_output,countries".$filter_query." spotter_output.aircraft_manufacturer = :aircraft_manufacturer AND spotter_output.airline_country = countries.name 
					GROUP BY spotter_output.airline_country, countries.iso3
					ORDER BY airline_country_count DESC
					LIMIT 10 OFFSET 0";
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':aircraft_manufacturer' => $aircraft_manufacturer));
		$airline_country_array = array();
		$temp_array = array();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airline_country_count'] = $row['airline_country_count'];
			$temp_array['airline_country'] = $row['airline_country'];
			$temp_array['airline_country_iso3'] = $row['airline_country_iso3'];
			$airline_country_array[] = $temp_array;
		}
		return $airline_country_array;
	}


    /**
     * Gets all airlines that have flown over by date
     *
     * @param $date
     * @param array $filters
     * @return array the airline list
     */
	public function countAllAirlinesByDate($date,$filters = array())
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
			$query  = "SELECT DISTINCT spotter_output.airline_name, spotter_output.airline_icao, spotter_output.airline_country, COUNT(spotter_output.airline_name) AS airline_count
		 			FROM spotter_output".$filter_query." DATE(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) = :date 
					GROUP BY spotter_output.airline_name,spotter_output.airline_icao,spotter_output.airline_country 
					ORDER BY airline_count DESC";
		} else {
			$query  = "SELECT DISTINCT spotter_output.airline_name, spotter_output.airline_icao, spotter_output.airline_country, COUNT(spotter_output.airline_name) AS airline_count
		 			FROM spotter_output".$filter_query." to_char(spotter_output.date AT TIME ZONE INTERVAL :offset,'YYYY-mm-dd') = :date 
					GROUP BY spotter_output.airline_name,spotter_output.airline_icao,spotter_output.airline_country
					ORDER BY airline_count DESC";
		}
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':date' => $date, ':offset' => $offset));

		$airline_array = array();
		$temp_array = array();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airline_name'] = $row['airline_name'];
			$temp_array['airline_icao'] = $row['airline_icao'];
			$temp_array['airline_count'] = $row['airline_count'];
			$temp_array['airline_country'] = $row['airline_country'];
 
			$airline_array[] = $temp_array;
		}

		return $airline_array;
	}


    /**
     * Gets all airline countries that have flown over by date
     *
     * @param $date
     * @param array $filters
     * @return array the airline country list
     */
	public function countAllAirlineCountriesByDate($date,$filters = array())
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
			$query  = "SELECT DISTINCT spotter_output.airline_country, COUNT(spotter_output.airline_country) AS airline_country_count, countries.iso3 as airline_country_iso
				FROM spotter_output,countries".$filter_query." spotter_output.airline_country <> '' AND DATE(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) = :date AND countries.name = spotter_output.airline_country
				GROUP BY spotter_output.airline_country, countries.iso3
				ORDER BY airline_country_count DESC
				LIMIT 10 OFFSET 0";
		} else {
			$query  = "SELECT DISTINCT spotter_output.airline_country, COUNT(spotter_output.airline_country) AS airline_country_count, countries.iso3 as airline_country_iso
					FROM spotter_output,countries".$filter_query." spotter_output.airline_country <> '' AND to_char(spotter_output.date AT TIME ZONE INTERVAL :offset,'YYYY-mm-dd') = :date AND countries.name = spotter_output.airline_country 
					GROUP BY spotter_output.airline_country, countries.iso3
					ORDER BY airline_country_count DESC
					LIMIT 10 OFFSET 0";
		}
		$sth = $this->db->prepare($query);
		$sth->execute(array(':date' => $date, ':offset' => $offset));
		$airline_country_array = array();
		$temp_array = array();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airline_country_count'] = $row['airline_country_count'];
			$temp_array['airline_country'] = $row['airline_country'];
			$temp_array['airline_country_iso3'] = $row['airline_country_iso3'];
			$airline_country_array[] = $temp_array;
		}
		return $airline_country_array;
	}


    /**
     * Gets all airlines that have flown over by ident/callsign
     *
     * @param $ident
     * @param array $filters
     * @return array the airline list
     */
	public function countAllAirlinesByIdent($ident,$filters = array())
	{
		$ident = filter_var($ident,FILTER_SANITIZE_STRING);
		$filter_query = $this->getFilter($filters,true,true);
		$query  = "SELECT DISTINCT spotter_output.airline_name, spotter_output.airline_icao, spotter_output.airline_country, COUNT(spotter_output.airline_name) AS airline_count
		 		FROM spotter_output".$filter_query." spotter_output.ident = :ident  
				GROUP BY spotter_output.airline_icao, spotter_output.airline_name, spotter_output.airline_country
				ORDER BY airline_count DESC";
      
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':ident' => $ident));
		return $sth->fetchAll(PDO::FETCH_ASSOC);
	}

    /**
     * Gets all airlines by owner
     *
     * @param $owner
     * @param array $filters
     * @return array the airline list
     */
	public function countAllAirlinesByOwner($owner,$filters = array())
	{
		$owner = filter_var($owner,FILTER_SANITIZE_STRING);
		$filter_query = $this->getFilter($filters,true,true);
		$query  = "SELECT DISTINCT spotter_output.airline_name, spotter_output.airline_icao, spotter_output.airline_country, COUNT(spotter_output.airline_name) AS airline_count
		 		FROM spotter_output".$filter_query." spotter_output.owner_name = :owner  
				GROUP BY spotter_output.airline_icao, spotter_output.airline_name, spotter_output.airline_country
				ORDER BY airline_count DESC";
      
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':owner' => $owner));
		return $sth->fetchAll(PDO::FETCH_ASSOC);
	}

    /**
     * Gets flight duration by owner
     *
     * @param $owner
     * @param array $filters
     * @param string $year
     * @param string $month
     * @param string $day
     * @return String Duration of all flights
     */
	public function getFlightDurationByOwner($owner,$filters = array(),$year = '',$month = '',$day = '')
	{
		global $globalDBdriver;
		$owner = filter_var($owner,FILTER_SANITIZE_STRING);
		$filter_query = $this->getFilter($filters,true,true);
		$query  = "SELECT SUM(last_seen - date) AS duration 
				FROM spotter_output".$filter_query." spotter_output.owner_name = :owner 
				AND last_seen > date";
		$query_values = array();
		if ($year != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND YEAR(spotter_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			} else {
				$query .= " AND EXTRACT(YEAR FROM spotter_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			}
		}
		if ($month != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND MONTH(spotter_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			} else {
				$query .= " AND EXTRACT(MONTH FROM spotter_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			}
		}
		if ($day != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND DAY(spotter_output.date) = :day";
				$query_values = array_merge($query_values,array(':day' => $day));
			} else {
				$query .= " AND EXTRACT(DAY FROM spotter_output.date) = :day";
				$query_values = array_merge($query_values,array(':day' => $day));
			}
		}
		$query_values = array_merge($query_values,array(':owner' => $owner));
		$sth = $this->db->prepare($query);
		$sth->execute($query_values);
		$result = $sth->fetchAll(PDO::FETCH_ASSOC);
		if (is_numeric($result[0]['duration'])) return gmdate('H:i:s',$result[0]['duration']);
		elseif ($result[0]['duration'] == '') return 0;
		else return $result[0]['duration'];
	}

    /**
     * Count flights by owner
     *
     * @param $owner
     * @param array $filters
     * @return String Duration of all flights
     */
	public function countFlightsByOwner($owner,$filters = array())
	{
		$owner = filter_var($owner,FILTER_SANITIZE_STRING);
		$filter_query = $this->getFilter($filters,true,true);
		$query  = "SELECT COUNT(*) AS nb 
				FROM spotter_output".$filter_query." spotter_output.owner_name = :owner";
		$query_values = array();
		$query_values = array_merge($query_values,array(':owner' => $owner));
		$sth = $this->db->prepare($query);
		$sth->execute($query_values);
		$result = $sth->fetchAll(PDO::FETCH_ASSOC);
		return $result[0]['nb'];
	}

    /**
     * Count flights by pilot
     *
     * @param $pilot
     * @param array $filters
     * @return String Duration of all flights
     */
	public function countFlightsByPilot($pilot,$filters = array())
	{
		$pilot = filter_var($pilot,FILTER_SANITIZE_STRING);
		$filter_query = $this->getFilter($filters,true,true);
		$query  = "SELECT COUNT(*) AS nb 
				FROM spotter_output".$filter_query." (spotter_output.pilot_name = :pilot OR spotter_output.pilot_id = :pilot)";
		$query_values = array();
		$query_values = array_merge($query_values,array(':pilot' => $pilot));
		$sth = $this->db->prepare($query);
		$sth->execute($query_values);
		$result = $sth->fetchAll(PDO::FETCH_ASSOC);
		return $result[0]['nb'];
	}

    /**
     * Gets flight duration by pilot
     *
     * @param $pilot
     * @param array $filters
     * @param string $year
     * @param string $month
     * @param string $day
     * @return String Duration of all flights
     */
	public function getFlightDurationByPilot($pilot,$filters = array(),$year = '',$month = '',$day = '')
	{
		global $globalDBdriver;
		$pilot = filter_var($pilot,FILTER_SANITIZE_STRING);
		$filter_query = $this->getFilter($filters,true,true);
		$query  = "SELECT SUM(last_seen - date) AS duration 
		 		FROM spotter_output".$filter_query." (spotter_output.pilot_name = :pilot OR spotter_output.pilot_id = :pilot) 
		 		AND last_seen > date";
		$query_values = array();
		if ($year != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND YEAR(spotter_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			} else {
				$query .= " AND EXTRACT(YEAR FROM spotter_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			}
		}
		if ($month != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND MONTH(spotter_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			} else {
				$query .= " AND EXTRACT(MONTH FROM spotter_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			}
		}
		if ($day != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND DAY(spotter_output.date) = :day";
				$query_values = array_merge($query_values,array(':day' => $day));
			} else {
				$query .= " AND EXTRACT(DAY FROM spotter_output.date) = :day";
				$query_values = array_merge($query_values,array(':day' => $day));
			}
		}
		$query_values = array_merge($query_values,array(':pilot' => $pilot));
		$sth = $this->db->prepare($query);
		$sth->execute($query_values);
		$result = $sth->fetchAll(PDO::FETCH_ASSOC);
		if (is_int($result[0]['duration'])) return gmdate('H:i:s',$result[0]['duration']);
		else return $result[0]['duration'];
	}

    /**
     * Gets all airlines used by pilot
     *
     * @param $pilot
     * @param array $filters
     * @return array the airline list
     */
	public function countAllAirlinesByPilot($pilot,$filters = array())
	{
		$pilot = filter_var($pilot,FILTER_SANITIZE_STRING);
		$filter_query = $this->getFilter($filters,true,true);
		$query  = "SELECT DISTINCT spotter_output.airline_name, spotter_output.airline_icao, spotter_output.airline_country, COUNT(spotter_output.airline_name) AS airline_count
		 		FROM spotter_output".$filter_query." (spotter_output.pilot_name = :pilot OR spotter_output.pilot_id = :pilot) 
				GROUP BY spotter_output.airline_icao, spotter_output.airline_name, spotter_output.airline_country
				ORDER BY airline_count DESC";
      
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':pilot' => $pilot));
		return $sth->fetchAll(PDO::FETCH_ASSOC);
	}

    /**
     * Gets all airlines that have flown over by route
     *
     * @param $departure_airport_icao
     * @param $arrival_airport_icao
     * @param array $filters
     * @return array the airline list
     */
	public function countAllAirlinesByRoute($departure_airport_icao, $arrival_airport_icao,$filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$departure_airport_icao = filter_var($departure_airport_icao,FILTER_SANITIZE_STRING);
		$arrival_airport_icao = filter_var($arrival_airport_icao,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.airline_name, spotter_output.airline_icao, spotter_output.airline_country, COUNT(spotter_output.airline_name) AS airline_count
		 			FROM spotter_output".$filter_query." (spotter_output.departure_airport_icao = :departure_airport_icao) AND (spotter_output.arrival_airport_icao = :arrival_airport_icao) 
					GROUP BY spotter_output.airline_name
					ORDER BY airline_count DESC";
      
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':departure_airport_icao' => $departure_airport_icao,':arrival_airport_icao' => $arrival_airport_icao));
      
		$airline_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airline_name'] = $row['airline_name'];
			$temp_array['airline_icao'] = $row['airline_icao'];
			$temp_array['airline_count'] = $row['airline_count'];
			$temp_array['airline_country'] = $row['airline_country'];

			$airline_array[] = $temp_array;
		}
		return $airline_array;
	}

    /**
     * Gets all airline countries that have flown over by route
     *
     * @param $departure_airport_icao
     * @param $arrival_airport_icao
     * @param array $filters
     * @return array the airline country list
     */
	public function countAllAirlineCountriesByRoute($departure_airport_icao, $arrival_airport_icao,$filters= array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$departure_airport_icao = filter_var($departure_airport_icao,FILTER_SANITIZE_STRING);
		$arrival_airport_icao = filter_var($arrival_airport_icao,FILTER_SANITIZE_STRING);
		$query  = "SELECT DISTINCT spotter_output.airline_country, COUNT(spotter_output.airline_country) AS airline_country_count, countries.iso3 AS airline_country_iso3
		 		FROM spotter_output,countries".$filter_query." spotter_output.airline_country <> '' AND (spotter_output.departure_airport_icao = :departure_airport_icao) AND (spotter_output.arrival_airport_icao = :arrival_airport_icao) AND countries.name = spotter_output.airline_country 
				GROUP BY spotter_output.airline_country, countries.iso3
				ORDER BY airline_country_count DESC
				LIMIT 10 OFFSET 0";
		$sth = $this->db->prepare($query);
		$sth->execute(array(':departure_airport_icao' => $departure_airport_icao,':arrival_airport_icao' => $arrival_airport_icao));
		$airline_country_array = array();
		$temp_array = array();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airline_country_count'] = $row['airline_country_count'];
			$temp_array['airline_country'] = $row['airline_country'];
			$temp_array['airline_country_iso3'] = $row['airline_country_iso3'];
			$airline_country_array[] = $temp_array;
		}
		return $airline_country_array;
	}


    /**
     * Gets all airlines that have flown over by country
     *
     * @param $country
     * @param array $filters
     * @return array the airline list
     */
	public function countAllAirlinesByCountry($country,$filters = array())
	{
		$country = filter_var($country,FILTER_SANITIZE_STRING);
		$filter_query = $this->getFilter($filters,true,true);
		$query  = "SELECT DISTINCT spotter_output.airline_name, spotter_output.airline_icao, spotter_output.airline_country, COUNT(spotter_output.airline_name) AS airline_count
		 	    FROM spotter_output".$filter_query." ((spotter_output.departure_airport_country = :country) OR (spotter_output.arrival_airport_country = :country)) OR spotter_output.airline_country = :country  
			    GROUP BY spotter_output.airline_name, spotter_output.airline_icao, spotter_output.airline_country 
			    ORDER BY airline_count DESC";
      
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':country' => $country));

		$airline_array = array();
		$temp_array = array();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airline_name'] = $row['airline_name'];
			$temp_array['airline_icao'] = $row['airline_icao'];
			$temp_array['airline_count'] = $row['airline_count'];
			$temp_array['airline_country'] = $row['airline_country'];
 
			$airline_array[] = $temp_array;
		}
		return $airline_array;
	}


    /**
     * Gets all airline countries that have flown over by country
     *
     * @param $country
     * @param array $filters
     * @return array the airline country list
     */
	public function countAllAirlineCountriesByCountry($country,$filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$country = filter_var($country,FILTER_SANITIZE_STRING);
		$query  = "SELECT DISTINCT spotter_output.airline_country, COUNT(spotter_output.airline_country) AS airline_country_count, countries.iso3 AS airline_country_iso3
			FROM countries,spotter_output".$filter_query." countries.name = spotter_output.airline_country AND spotter_output.airline_country <> '' AND ((spotter_output.departure_airport_country = :country) OR (spotter_output.arrival_airport_country = :country) OR spotter_output.airline_country = :country) 
			GROUP BY spotter_output.airline_country, countries.iso3
			ORDER BY airline_country_count DESC
			LIMIT 10 OFFSET 0";
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':country' => $country));

		$airline_country_array = array();
		$temp_array = array();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airline_country_count'] = $row['airline_country_count'];
			$temp_array['airline_country'] = $row['airline_country'];
			$temp_array['airline_country_iso3'] = $row['airline_country_iso3'];
			$airline_country_array[] = $temp_array;
		}
		return $airline_country_array;
	}


    /**
     * Gets all airlines countries
     *
     * @param bool $limit
     * @param array $filters
     * @param string $year
     * @param string $month
     * @param string $day
     * @return array the airline country list
     */
	public function countAllAirlineCountries($limit = true, $filters = array(), $year = '', $month = '', $day = '')
	{
		global $globalDBdriver;
		$filter_query = $this->getFilter($filters,true,true);
		$query  = "SELECT DISTINCT spotter_output.airline_country, COUNT(spotter_output.airline_country) AS airline_country_count, countries.iso3 AS airline_country_iso3
		 			FROM countries, spotter_output".$filter_query." countries.name = spotter_output.airline_country AND spotter_output.airline_country <> 'NA'";
		$query_values = array();
		if ($year != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND YEAR(spotter_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			} else {
				$query .= " AND EXTRACT(YEAR FROM spotter_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			}
		}
		if ($month != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND MONTH(spotter_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			} else {
				$query .= " AND EXTRACT(MONTH FROM spotter_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			}
		}
		if ($day != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND DAY(spotter_output.date) = :day";
				$query_values = array_merge($query_values,array(':day' => $day));
			} else {
				$query .= " AND EXTRACT(DAY FROM spotter_output.date) = :day";
				$query_values = array_merge($query_values,array(':day' => $day));
			}
		}
		$query .= " GROUP BY spotter_output.airline_country, countries.iso3
					ORDER BY airline_country_count DESC";
		if ($limit) $query .= " LIMIT 10 OFFSET 0";
      
		$sth = $this->db->prepare($query);
		$sth->execute($query_values);

		$airline_array = array();
		$temp_array = array();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airline_country_count'] = $row['airline_country_count'];
			$temp_array['airline_country'] = $row['airline_country'];
			$temp_array['airline_country_iso3'] = $row['airline_country_iso3'];

			$airline_array[] = $temp_array;
		}
		return $airline_array;
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
	public function countAllFlightOverCountries($limit = true,$olderthanmonths = 0,$sincedate = '',$filters = array())
	{
		global $globalDBdriver, $globalArchive;
		//$filter_query = $this->getFilter($filters,true,true);
		$Connection= new Connection($this->db);
		if (!$Connection->tableExists('countries')) return array();
		/*
		$query = "SELECT c.name, c.iso3, c.iso2, count(c.name) as nb 
					FROM countries c, spotter_output s
					WHERE Within(GeomFromText(CONCAT('POINT(',s.longitude,' ',s.latitude,')')), ogc_geom) ";
		*/
/*
		$query = "SELECT c.name, c.iso3, c.iso2, count(c.name) as nb 
					FROM countries c, spotter_live s
					WHERE c.iso2 = s.over_country ";
		$query = "SELECT c.name, c.iso3, c.iso2, count(c.name) as nb FROM countries c INNER JOIN (SELECT DISTINCT flightaware_id,over_country FROM spottrer_live) l ON c.iso2 = l.over_country ";
*/
		if (!isset($globalArchive) || $globalArchive === FALSE) {
			require_once('class.SpotterLive.php');
			$SpotterLive = new SpotterLive($this->db);
			$filter_query = $SpotterLive->getFilter($filters,true,true);
			$filter_query .= " over_country IS NOT NULL AND over_country <> ''";
			if ($olderthanmonths > 0) {
				if ($globalDBdriver == 'mysql') {
					$filter_query .= ' AND spotter_live.date < DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.$olderthanmonths.' MONTH) ';
				} else {
					$filter_query .= " AND spotter_live.date < CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$olderthanmonths." MONTHS'";
				}
			}
			if ($sincedate != '') {
				if ($globalDBdriver == 'mysql') {
					$filter_query .= " AND spotter_live.date > '".$sincedate."' ";
				} else {
					$filter_query .= " AND spotter_live.date > CAST('".$sincedate."' AS TIMESTAMP)";
				}
			}
			$query = "SELECT c.name, c.iso3, c.iso2, count(c.name) as nb FROM countries c INNER JOIN (SELECT DISTINCT flightaware_id,over_country FROM spotter_live".$filter_query.") l ON c.iso2 = l.over_country ";
		} else {
			require_once('class.SpotterArchive.php');
			$SpotterArchive = new SpotterArchive($this->db);
			$filter_query = $SpotterArchive->getFilter($filters,true,true);
			$filter_query .= " over_country IS NOT NULL AND over_country <> ''";
			if ($olderthanmonths > 0) {
				if ($globalDBdriver == 'mysql') {
					$filter_query .= ' AND spotter_archive.date < DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.$olderthanmonths.' MONTH) ';
				} else {
					$filter_query .= " AND spotter_archive.date < CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$olderthanmonths." MONTHS'";
				}
			}
			if ($sincedate != '') {
				if ($globalDBdriver == 'mysql') {
					$filter_query .= " AND spotter_archive.date > '".$sincedate."' ";
				} else {
					$filter_query .= " AND spotter_archive.date > CAST('".$sincedate."' AS TIMESTAMP)";
				}
			}
			$filter_query .= " LIMIT 100 OFFSET 0"; 
			$query = "SELECT c.name, c.iso3, c.iso2, count(c.name) as nb FROM countries c INNER JOIN (SELECT DISTINCT flightaware_id,over_country FROM spotter_archive".$filter_query.") l ON c.iso2 = l.over_country ";
		}
		$query .= "GROUP BY c.name,c.iso3,c.iso2 ORDER BY nb DESC";
		if ($limit) $query .= " LIMIT 10 OFFSET 0";
      
		
		$sth = $this->db->prepare($query);
		$sth->execute();
 
		$flight_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['flight_count'] = $row['nb'];
			$temp_array['flight_country'] = $row['name'];
			$temp_array['flight_country_iso3'] = $row['iso3'];
			$temp_array['flight_country_iso2'] = $row['iso2'];
			$flight_array[] = $temp_array;
		}
		return $flight_array;
	}

    /**
     * Gets all aircraft types that have flown over
     *
     * @param bool $limit
     * @param int $olderthanmonths
     * @param string $sincedate
     * @param array $filters
     * @param string $year
     * @param string $month
     * @param string $day
     * @return array the aircraft list
     */
	public function countAllAircraftTypes($limit = true,$olderthanmonths = 0,$sincedate = '',$filters = array(),$year = '',$month = '',$day = '')
	{
		global $globalDBdriver;
		$filter_query = $this->getFilter($filters,true,true);
		$query  = "SELECT spotter_output.aircraft_icao, COUNT(spotter_output.aircraft_icao) AS aircraft_icao_count, spotter_output.aircraft_name, spotter_output.aircraft_manufacturer 
		    FROM spotter_output ".$filter_query." spotter_output.aircraft_name  <> '' AND spotter_output.aircraft_icao  <> '' AND spotter_output.aircraft_icao  <> 'NA'";
		if ($olderthanmonths > 0) {
			if ($globalDBdriver == 'mysql') {
				$query .= ' AND spotter_output.date < DATE_SUB(UTC_TIMESTAMP(), INTERVAL '.$olderthanmonths.' MONTH)';
			} else {
				$query .= " AND spotter_output.date < CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$olderthanmonths." MONTHS'";
			}
		}
		if ($sincedate != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND spotter_output.date > '".$sincedate."'";
			} else {
				$query .= " AND spotter_output.date > CAST('".$sincedate."' AS TIMESTAMP)";
			}
		}
		$query_values = array();
		if ($year != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND YEAR(spotter_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			} else {
				$query .= " AND EXTRACT(YEAR FROM spotter_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			}
		}
		if ($month != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND MONTH(spotter_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			} else {
				$query .= " AND EXTRACT(MONTH FROM spotter_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			}
		}
		if ($day != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND DAY(spotter_output.date) = :day";
				$query_values = array_merge($query_values,array(':day' => $day));
			} else {
				$query .= " AND EXTRACT(DAY FROM spotter_output.date) = :day";
				$query_values = array_merge($query_values,array(':day' => $day));
			}
		}

		$query .= " GROUP BY spotter_output.aircraft_icao, spotter_output.aircraft_name, spotter_output.aircraft_manufacturer ORDER BY aircraft_icao_count DESC";
		if ($limit) $query .= " LIMIT 10 OFFSET 0";
 
		$sth = $this->db->prepare($query);
		$sth->execute($query_values);

		$aircraft_array = array();
		$temp_array = array();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['aircraft_icao'] = $row['aircraft_icao'];
			$temp_array['aircraft_name'] = $row['aircraft_name'];
			$temp_array['aircraft_manufacturer'] = $row['aircraft_manufacturer'];
			$temp_array['aircraft_icao_count'] = $row['aircraft_icao_count'];
			$aircraft_array[] = $temp_array;
		}
		return $aircraft_array;
	}

    /**
     * Gets all aircraft types that have flown over by airline
     *
     * @param bool $limit
     * @param int $olderthanmonths
     * @param string $sincedate
     * @param array $filters
     * @param string $year
     * @param string $month
     * @param string $day
     * @return array the aircraft list
     */
	public function countAllAircraftTypesByAirlines($limit = true,$olderthanmonths = 0,$sincedate = '',$filters = array(),$year = '',$month = '', $day = '')
	{
		global $globalDBdriver;
		$filter_query = $this->getFilter($filters,true,true);
		$query  = "SELECT spotter_output.airline_icao, spotter_output.aircraft_icao, COUNT(spotter_output.aircraft_icao) AS aircraft_icao_count, spotter_output.aircraft_name, spotter_output.aircraft_manufacturer 
		    FROM spotter_output".$filter_query." spotter_output.aircraft_name  <> '' AND spotter_output.aircraft_icao  <> '' AND spotter_output.airline_icao <>'' AND spotter_output.airline_icao <> 'NA'";
		if ($olderthanmonths > 0) {
			if ($globalDBdriver == 'mysql') {
				$query .= ' AND spotter_output.date < DATE_SUB(UTC_TIMESTAMP(), INTERVAL '.$olderthanmonths.' MONTH)';
			} else {
				$query .= " AND spotter_output.date < CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$olderthanmonths." MONTHS'";
			}
		}
		if ($sincedate != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND spotter_output.date > '".$sincedate."'";
			} else {
				$query .= " AND spotter_output.date > CAST('".$sincedate."' AS TIMESTAMP)";
			}
		}
		$query_values = array();
		if ($year != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND YEAR(spotter_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			} else {
				$query .= " AND EXTRACT(YEAR FROM spotter_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			}
		}
		if ($month != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND MONTH(spotter_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			} else {
				$query .= " AND EXTRACT(MONTH FROM spotter_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			}
		}
		if ($day != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND DAY(spotter_output.date) = :day";
				$query_values = array_merge($query_values,array(':day' => $day));
			} else {
				$query .= " AND EXTRACT(DAY FROM spotter_output.date) = :day";
				$query_values = array_merge($query_values,array(':day' => $day));
			}
		}

		$query .= " GROUP BY spotter_output.airline_icao, spotter_output.aircraft_icao, spotter_output.aircraft_name, spotter_output.aircraft_manufacturer ORDER BY aircraft_icao_count DESC";
		if ($limit) $query .= " LIMIT 10 OFFSET 0";
 
		$sth = $this->db->prepare($query);
		$sth->execute($query_values);

		$aircraft_array = array();
		$temp_array = array();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airline_icao'] = $row['airline_icao'];
			$temp_array['aircraft_icao'] = $row['aircraft_icao'];
			$temp_array['aircraft_name'] = $row['aircraft_name'];
			$temp_array['aircraft_manufacturer'] = $row['aircraft_manufacturer'];
			$temp_array['aircraft_icao_count'] = $row['aircraft_icao_count'];
			$aircraft_array[] = $temp_array;
		}
		return $aircraft_array;
	}

    /**
     * Gets all aircraft types that have flown over by months
     *
     * @param bool $limit
     * @param int $olderthanmonths
     * @param string $sincedate
     * @param array $filters
     * @return array the aircraft list
     */
	public function countAllAircraftTypesByMonths($limit = true,$olderthanmonths = 0,$sincedate = '',$filters = array())
	{
		global $globalDBdriver;
		$filter_query = $this->getFilter($filters,true,true);
		$query  = "SELECT EXTRACT(month from spotter_output.date) as month, EXTRACT(year from spotter_output.date) as year,spotter_output.aircraft_icao, COUNT(spotter_output.aircraft_icao) AS aircraft_icao_count, spotter_output.aircraft_name, spotter_output.aircraft_manufacturer 
		    FROM spotter_output".$filter_query." spotter_output.aircraft_name  <> '' AND spotter_output.aircraft_icao  <> '' AND spotter_output.airline_icao <>'' AND spotter_output.airline_icao <> 'NA' ";
		if ($olderthanmonths > 0) {
			if ($globalDBdriver == 'mysql') {
				$query .= 'AND spotter_output.date < DATE_SUB(UTC_TIMESTAMP(), INTERVAL '.$olderthanmonths.' MONTH) ';
			} else {
				$query .= "AND spotter_output.date < CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$olderthanmonths." MONTHS' ";
			}
		}
		if ($sincedate != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= "AND spotter_output.date > '".$sincedate."' ";
			} else {
				$query .= "AND spotter_output.date > CAST('".$sincedate."' AS TIMESTAMP)";
			}
		}

		$query .= "GROUP BY EXTRACT(month from spotter_output.date), EXTRACT(year from spotter_output.date), spotter_output.aircraft_icao, spotter_output.aircraft_name, spotter_output.aircraft_manufacturer ORDER BY aircraft_icao_count DESC";
		if ($limit) $query .= " LIMIT 10 OFFSET 0";
 
		$sth = $this->db->prepare($query);
		$sth->execute();

		$aircraft_array = array();
		$temp_array = array();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			//$temp_array['airline_icao'] = $row['airline_icao'];
			$temp_array['aircraft_icao'] = $row['aircraft_icao'];
			$temp_array['aircraft_name'] = $row['aircraft_name'];
			$temp_array['aircraft_manufacturer'] = $row['aircraft_manufacturer'];
			$temp_array['aircraft_icao_count'] = $row['aircraft_icao_count'];
			$aircraft_array[] = $temp_array;
		}
		return $aircraft_array;
	}


    /**
     * Gets all aircraft registration that have flown over by aircaft icao
     *
     * @param $aircraft_icao
     * @param array $filters
     * @return array the aircraft list
     */
	public function countAllAircraftRegistrationByAircraft($aircraft_icao,$filters = array())
	{
		$Image = new Image($this->db);
		$filter_query = $this->getFilter($filters,true,true);
		$aircraft_icao = filter_var($aircraft_icao,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.aircraft_icao, COUNT(spotter_output.registration) AS registration_count, spotter_output.aircraft_name, spotter_output.registration, spotter_output.airline_name  
				FROM spotter_output".$filter_query." spotter_output.aircraft_icao = :aircraft_icao  
				GROUP BY spotter_output.registration, spotter_output.aircraft_icao, spotter_output.aircraft_name, spotter_output.registration, spotter_output.airline_name  
				ORDER BY registration_count DESC";

		$sth = $this->db->prepare($query);
		$sth->execute(array(':aircraft_icao' => $aircraft_icao));

		$aircraft_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['aircraft_icao'] = $row['aircraft_icao'];
			$temp_array['aircraft_name'] = $row['aircraft_name'];
			$temp_array['registration'] = $row['registration'];
			$temp_array['airline_name'] = $row['airline_name'];
			$temp_array['image_thumbnail'] = "";
			if($row['registration'] != "")
			{
				$image_array = $Image->getSpotterImage($row['registration']);
				if (isset($image_array[0]['image_thumbnail'])) $temp_array['image_thumbnail'] = $image_array[0]['image_thumbnail'];
			}
			$temp_array['registration_count'] = $row['registration_count'];
			$temp_array['aircraft_registration_count'] = $row['registration_count'];

			$aircraft_array[] = $temp_array;
		}
		return $aircraft_array;
	}


    /**
     * Gets all aircraft types that have flown over by airline icao
     *
     * @param $airline_icao
     * @param array $filters
     * @return array the aircraft list
     */
	public function countAllAircraftTypesByAirline($airline_icao,$filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$airline_icao = filter_var($airline_icao,FILTER_SANITIZE_STRING);
		$query  = "SELECT DISTINCT spotter_output.aircraft_icao, COUNT(spotter_output.aircraft_icao) AS aircraft_icao_count, spotter_output.aircraft_name  
			    FROM spotter_output".$filter_query." spotter_output.aircraft_icao <> '' ";
		if ($airline_icao != '') $query .= "AND spotter_output.airline_icao = :airline_icao ";
		$query .= "GROUP BY spotter_output.aircraft_name, spotter_output.aircraft_icao ORDER BY aircraft_icao_count DESC";

		$sth = $this->db->prepare($query);
		if ($airline_icao != '') $sth->execute(array(':airline_icao' => $airline_icao));
		else $sth->execute();

		$aircraft_array = array();
		$temp_array = array();

		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['aircraft_icao'] = $row['aircraft_icao'];
			$temp_array['aircraft_name'] = $row['aircraft_name'];
			$temp_array['aircraft_icao_count'] = $row['aircraft_icao_count'];
			$aircraft_array[] = $temp_array;
		}
		return $aircraft_array;
	}


    /**
     * Gets all aircraft registration that have flown over by airline icao
     *
     * @param $airline_icao
     * @param array $filters
     * @return array the aircraft list
     */
	public function countAllAircraftRegistrationByAirline($airline_icao,$filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$Image = new Image($this->db);
		$airline_icao = filter_var($airline_icao,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.aircraft_icao, COUNT(spotter_output.registration) AS registration_count, spotter_output.aircraft_name, spotter_output.registration, spotter_output.airline_name 
			    FROM spotter_output".$filter_query." spotter_output.registration <> '' AND spotter_output.registration <> 'NA' ";
		if ($airline_icao != '') $query .= "AND spotter_output.airline_icao = :airline_icao ";
		$query .= "GROUP BY spotter_output.registration, spotter_output.aircraft_icao, spotter_output.aircraft_name, spotter_output.airline_name
			    ORDER BY registration_count DESC";

		$sth = $this->db->prepare($query);
		if ($airline_icao != '') $sth->execute(array(':airline_icao' => $airline_icao));
		else $sth->execute();

		$aircraft_array = array();
		$temp_array = array();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['aircraft_icao'] = $row['aircraft_icao'];
			$temp_array['aircraft_name'] = $row['aircraft_name'];
			$temp_array['registration'] = $row['registration'];
			$temp_array['airline_name'] = $row['airline_name'];
			$temp_array['image_thumbnail'] = "";
			if($row['registration'] != "")
			{
				$image_array = $Image->getSpotterImage($row['registration']);
				if (isset($image_array[0]['image_thumbnail'])) $temp_array['image_thumbnail'] = $image_array[0]['image_thumbnail'];
			}
			$temp_array['registration_count'] = $row['registration_count'];
			$aircraft_array[] = $temp_array;
		}
		return $aircraft_array;
	}


    /**
     * Gets all aircraft manufacturer that have flown over by airline icao
     *
     * @param $airline_icao
     * @param array $filters
     * @return array the aircraft list
     */
	public function countAllAircraftManufacturerByAirline($airline_icao,$filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$airline_icao = filter_var($airline_icao,FILTER_SANITIZE_STRING);
		$query  = "SELECT DISTINCT spotter_output.aircraft_manufacturer, COUNT(spotter_output.aircraft_manufacturer) AS aircraft_manufacturer_count  
				FROM spotter_output".$filter_query." spotter_output.aircraft_manufacturer <> '' ";
		if ($airline_icao != '') $query .= "AND spotter_output.airline_icao = :airline_icao ";
		$query .= "GROUP BY spotter_output.aircraft_manufacturer 
				ORDER BY aircraft_manufacturer_count DESC";

		$sth = $this->db->prepare($query);
		if ($airline_icao != '') $sth->execute(array(':airline_icao' => $airline_icao));
		else $sth->execute();

		$aircraft_array = array();
		$temp_array = array();

		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['aircraft_manufacturer'] = $row['aircraft_manufacturer'];
			$temp_array['aircraft_manufacturer_count'] = $row['aircraft_manufacturer_count'];
			$aircraft_array[] = $temp_array;
		}
		return $aircraft_array;
	}


    /**
     * Gets all aircraft types that have flown over by airline icao
     *
     * @param $airport_icao
     * @param array $filters
     * @return array the aircraft list
     */
	public function countAllAircraftTypesByAirport($airport_icao,$filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$airport_icao = filter_var($airport_icao,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.aircraft_icao, COUNT(spotter_output.aircraft_icao) AS aircraft_icao_count, spotter_output.aircraft_name  
				FROM spotter_output".$filter_query." spotter_output.aircraft_icao <> '' AND (spotter_output.departure_airport_icao = :airport_icao OR spotter_output.arrival_airport_icao = :airport_icao) 
				GROUP BY spotter_output.aircraft_name, spotter_output.aircraft_icao 
				ORDER BY aircraft_icao_count DESC";
 
		$sth = $this->db->prepare($query);
		$sth->execute(array(':airport_icao' => $airport_icao));

		$aircraft_array = array();
		$temp_array = array();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['aircraft_icao'] = $row['aircraft_icao'];
			$temp_array['aircraft_name'] = $row['aircraft_name'];
			$temp_array['aircraft_icao_count'] = $row['aircraft_icao_count'];

			$aircraft_array[] = $temp_array;
		}
		return $aircraft_array;
	}


    /**
     * Gets all aircraft registration that have flown over by airport icao
     *
     * @param $airport_icao
     * @param array $filters
     * @return array the aircraft list
     */
	public function countAllAircraftRegistrationByAirport($airport_icao,$filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$Image = new Image($this->db);
		$airport_icao = filter_var($airport_icao,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.aircraft_icao, COUNT(spotter_output.registration) AS registration_count, spotter_output.aircraft_name, spotter_output.registration, spotter_output.airline_name  
                    FROM spotter_output".$filter_query." spotter_output.registration <> '' AND (spotter_output.departure_airport_icao = :airport_icao OR spotter_output.arrival_airport_icao = :airport_icao)   
                    GROUP BY spotter_output.registration, spotter_output.aircraft_icao, spotter_output.aircraft_name, spotter_output.airline_name 
		    ORDER BY registration_count DESC";

		$sth = $this->db->prepare($query);
		$sth->execute(array(':airport_icao' => $airport_icao));

		$aircraft_array = array();
		$temp_array = array();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['aircraft_icao'] = $row['aircraft_icao'];
			$temp_array['aircraft_name'] = $row['aircraft_name'];
			$temp_array['registration'] = $row['registration'];
			$temp_array['airline_name'] = $row['airline_name'];
			$temp_array['image_thumbnail'] = "";
			if($row['registration'] != "")
			{
				$image_array = $Image->getSpotterImage($row['registration']);
				if (isset($image_array[0]['image_thumbnail'])) $temp_array['image_thumbnail'] = $image_array[0]['image_thumbnail'];
			}
			$temp_array['registration_count'] = $row['registration_count'];
			$aircraft_array[] = $temp_array;
		}
		return $aircraft_array;
	}


    /**
     * Gets all aircraft manufacturer that have flown over by airport icao
     *
     * @param $airport_icao
     * @param array $filters
     * @return array the aircraft list
     */
	public function countAllAircraftManufacturerByAirport($airport_icao,$filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$airport_icao = filter_var($airport_icao,FILTER_SANITIZE_STRING);
		$query  = "SELECT DISTINCT spotter_output.aircraft_manufacturer, COUNT(spotter_output.aircraft_manufacturer) AS aircraft_manufacturer_count  
                    FROM spotter_output".$filter_query." spotter_output.aircraft_manufacturer <> '' AND (spotter_output.departure_airport_icao = :airport_icao OR spotter_output.arrival_airport_icao = :airport_icao)  
                    GROUP BY spotter_output.aircraft_manufacturer 
					ORDER BY aircraft_manufacturer_count DESC";

		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':airport_icao' => $airport_icao));

		$aircraft_array = array();
		$temp_array = array();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['aircraft_manufacturer'] = $row['aircraft_manufacturer'];
			$temp_array['aircraft_manufacturer_count'] = $row['aircraft_manufacturer_count'];
			$aircraft_array[] = $temp_array;
		}
		return $aircraft_array;
	}

    /**
     * Gets all aircraft types that have flown over by aircraft manufacturer
     *
     * @param $aircraft_manufacturer
     * @param array $filters
     * @return array the aircraft list
     */
	public function countAllAircraftTypesByManufacturer($aircraft_manufacturer,$filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$aircraft_manufacturer = filter_var($aircraft_manufacturer,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.aircraft_icao, COUNT(spotter_output.aircraft_icao) AS aircraft_icao_count, spotter_output.aircraft_name  
                    FROM spotter_output".$filter_query." spotter_output.aircraft_manufacturer = :aircraft_manufacturer
                    GROUP BY spotter_output.aircraft_name 
					ORDER BY aircraft_icao_count DESC";

		$sth = $this->db->prepare($query);
		$sth->execute(array(':aircraft_manufacturer' => $aircraft_manufacturer));
		$aircraft_array = array();
		$temp_array = array();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['aircraft_icao'] = $row['aircraft_icao'];
			$temp_array['aircraft_name'] = $row['aircraft_name'];
			$temp_array['aircraft_icao_count'] = $row['aircraft_icao_count'];
			$aircraft_array[] = $temp_array;
		}
		return $aircraft_array;
	}


    /**
     * Gets all aircraft registration that have flown over by aircaft manufacturer
     *
     * @param $aircraft_manufacturer
     * @param array $filters
     * @return array the aircraft list
     */
	public function countAllAircraftRegistrationByManufacturer($aircraft_manufacturer, $filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$Image = new Image($this->db);
		$aircraft_manufacturer = filter_var($aircraft_manufacturer,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.aircraft_icao, COUNT(spotter_output.registration) AS registration_count, spotter_output.aircraft_name, spotter_output.registration, spotter_output.airline_name   
                    FROM spotter_output".$filter_query." spotter_output.registration <> '' AND spotter_output.aircraft_manufacturer = :aircraft_manufacturer 
                    GROUP BY spotter_output.registration 
		    ORDER BY registration_count DESC";
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':aircraft_manufacturer' => $aircraft_manufacturer));
		$aircraft_array = array();
		$temp_array = array();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['aircraft_icao'] = $row['aircraft_icao'];
			$temp_array['aircraft_name'] = $row['aircraft_name'];
			$temp_array['registration'] = $row['registration'];
			$temp_array['airline_name'] = $row['airline_name'];
			$temp_array['image_thumbnail'] = "";
			if($row['registration'] != "")
			{
				$image_array = $Image->getSpotterImage($row['registration']);
				if (isset($image_array[0]['image_thumbnail'])) $temp_array['image_thumbnail'] = $image_array[0]['image_thumbnail'];
			}
			$temp_array['registration_count'] = $row['registration_count'];
			$aircraft_array[] = $temp_array;
		}
		return $aircraft_array;
	}

    /**
     * Gets all aircraft types that have flown over by date
     *
     * @param $date
     * @param array $filters
     * @return array the aircraft list
     */
	public function countAllAircraftTypesByDate($date,$filters = array())
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
			$query  = "SELECT DISTINCT spotter_output.aircraft_icao, COUNT(spotter_output.aircraft_icao) AS aircraft_icao_count, spotter_output.aircraft_name  
					FROM spotter_output".$filter_query." DATE(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) = :date
					GROUP BY spotter_output.aircraft_name, spotter_output.aircraft_icao 
					ORDER BY aircraft_icao_count DESC";
		} else {
			$query  = "SELECT DISTINCT spotter_output.aircraft_icao, COUNT(spotter_output.aircraft_icao) AS aircraft_icao_count, spotter_output.aircraft_name  
					FROM spotter_output".$filter_query." to_char(spotter_output.date AT TIME ZONE INTERVAL :offset,'YYYY-mm-dd') = :date
					GROUP BY spotter_output.aircraft_name, spotter_output.aircraft_icao 
					ORDER BY aircraft_icao_count DESC";
		}
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':date' => $date, ':offset' => $offset));

		$aircraft_array = array();
		$temp_array = array();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['aircraft_icao'] = $row['aircraft_icao'];
			$temp_array['aircraft_name'] = $row['aircraft_name'];
			$temp_array['aircraft_icao_count'] = $row['aircraft_icao_count'];

			$aircraft_array[] = $temp_array;
		}
		return $aircraft_array;
	}


    /**
     * Gets all aircraft registration that have flown over by date
     *
     * @param $date
     * @param array $filters
     * @return array the aircraft list
     */
	public function countAllAircraftRegistrationByDate($date,$filters = array())
	{
		global $globalTimezone, $globalDBdriver;
		$filter_query = $this->getFilter($filters,true,true);
		$Image = new Image($this->db);
		$date = filter_var($date,FILTER_SANITIZE_STRING);
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime($date);
			$offset = $datetime->format('P');
		} else $offset = '+00:00';

		if ($globalDBdriver == 'mysql') {
			$query  = "SELECT DISTINCT spotter_output.aircraft_icao, COUNT(spotter_output.registration) AS registration_count, spotter_output.aircraft_name, spotter_output.registration, spotter_output.airline_name 
					FROM spotter_output".$filter_query." spotter_output.registration <> '' AND DATE(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) = :date 
					GROUP BY spotter_output.registration, spotter_output.aircraft_icao, spotter_output.aircraft_name, spotter_output.airline_name 
					ORDER BY registration_count DESC";
		} else {
			$query  = "SELECT DISTINCT spotter_output.aircraft_icao, COUNT(spotter_output.registration) AS registration_count, spotter_output.aircraft_name, spotter_output.registration, spotter_output.airline_name    
					FROM spotter_output".$filter_query." spotter_output.registration <> '' AND to_char(spotter_output.date AT TIME ZONE INTERVAL :offset,'YYYY-mm-dd') = :date 
					GROUP BY spotter_output.registration, spotter_output.aircraft_icao, spotter_output.aircraft_name, spotter_output.airline_name 
					ORDER BY registration_count DESC";
		}
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':date' => $date, ':offset' => $offset));

		$aircraft_array = array();
		$temp_array = array();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['aircraft_icao'] = $row['aircraft_icao'];
			$temp_array['aircraft_name'] = $row['aircraft_name'];
			$temp_array['registration'] = $row['registration'];
			$temp_array['airline_name'] = $row['airline_name'];
			$temp_array['image_thumbnail'] = "";
			if($row['registration'] != "")
			{
				$image_array = $Image->getSpotterImage($row['registration']);
				if (isset($image_array[0]['image_thumbnail'])) $temp_array['image_thumbnail'] = $image_array[0]['image_thumbnail'];
			}
			$temp_array['registration_count'] = $row['registration_count'];
 
			$aircraft_array[] = $temp_array;
		}
		return $aircraft_array;
	}


    /**
     * Gets all aircraft manufacturer that have flown over by date
     *
     * @param $date
     * @param array $filters
     * @return array the aircraft manufacturer list
     */
	public function countAllAircraftManufacturerByDate($date,$filters = array())
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
			$query  = "SELECT DISTINCT spotter_output.aircraft_manufacturer, COUNT(spotter_output.aircraft_manufacturer) AS aircraft_manufacturer_count  
				FROM spotter_output".$filter_query." spotter_output.aircraft_manufacturer <> '' AND DATE(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) = :date 
				GROUP BY spotter_output.aircraft_manufacturer 
				ORDER BY aircraft_manufacturer_count DESC";
		} else {
			$query  = "SELECT DISTINCT spotter_output.aircraft_manufacturer, COUNT(spotter_output.aircraft_manufacturer) AS aircraft_manufacturer_count  
				FROM spotter_output".$filter_query." spotter_output.aircraft_manufacturer <> '' AND to_char(spotter_output.date AT TIME ZONE INTERVAL :offset,'YYYY-mm-dd') = :date 
				GROUP BY spotter_output.aircraft_manufacturer 
				ORDER BY aircraft_manufacturer_count DESC";
		}
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':date' => $date, ':offset' => $offset));

		$aircraft_array = array();
		$temp_array = array();

		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['aircraft_manufacturer'] = $row['aircraft_manufacturer'];
			$temp_array['aircraft_manufacturer_count'] = $row['aircraft_manufacturer_count'];

			$aircraft_array[] = $temp_array;
		}
		return $aircraft_array;
	}


    /**
     * Gets all aircraft types that have flown over by ident/callsign
     *
     * @param $ident
     * @param array $filters
     * @return array the aircraft list
     */
	public function countAllAircraftTypesByIdent($ident,$filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$ident = filter_var($ident,FILTER_SANITIZE_STRING);
		$query  = "SELECT DISTINCT spotter_output.aircraft_icao, COUNT(spotter_output.aircraft_icao) AS aircraft_icao_count, spotter_output.aircraft_name  
				FROM spotter_output".$filter_query." spotter_output.ident = :ident 
				GROUP BY spotter_output.aircraft_name, spotter_output.aircraft_icao
				ORDER BY aircraft_icao_count DESC";

		$sth = $this->db->prepare($query);
		$sth->execute(array(':ident' => $ident));
      
		$aircraft_array = array();
		$temp_array = array();

		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['aircraft_icao'] = $row['aircraft_icao'];
			$temp_array['aircraft_name'] = $row['aircraft_name'];
			$temp_array['aircraft_icao_count'] = $row['aircraft_icao_count'];

			$aircraft_array[] = $temp_array;
		}
		return $aircraft_array;
	}

    /**
     * Gets all aircraft types that have flown over by pilot
     *
     * @param $pilot
     * @param array $filters
     * @param string $year
     * @param string $month
     * @param string $day
     * @return array the aircraft list
     */
	public function countAllAircraftTypesByPilot($pilot,$filters = array(),$year = '',$month = '',$day = '')
	{
		global $globalDBdriver;
		$filter_query = $this->getFilter($filters,true,true);
		$pilot = filter_var($pilot,FILTER_SANITIZE_STRING);
		$query  = "SELECT DISTINCT spotter_output.aircraft_icao, COUNT(spotter_output.aircraft_icao) AS aircraft_icao_count, spotter_output.aircraft_name  
				FROM spotter_output".$filter_query." (spotter_output.pilot_id = :pilot OR spotter_output.pilot_name = :pilot)";
		$query_values = array();
		if ($year != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND YEAR(spotter_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			} else {
				$query .= " AND EXTRACT(YEAR FROM spotter_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			}
		}
		if ($month != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND MONTH(spotter_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			} else {
				$query .= " AND EXTRACT(MONTH FROM spotter_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			}
		}
		if ($day != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND DAY(spotter_output.date) = :day";
				$query_values = array_merge($query_values,array(':day' => $day));
			} else {
				$query .= " AND EXTRACT(DAY FROM spotter_output.date) = :day";
				$query_values = array_merge($query_values,array(':day' => $day));
			}
		}

		$query .= " GROUP BY spotter_output.aircraft_name, spotter_output.aircraft_icao
				ORDER BY aircraft_icao_count DESC";
		$query_values = array_merge($query_values,array(':pilot' => $pilot));
		$sth = $this->db->prepare($query);
		$sth->execute($query_values);
		return $sth->fetchAll(PDO::FETCH_ASSOC);
	}

    /**
     * Gets all aircraft types that have flown over by owner
     *
     * @param $owner
     * @param array $filters
     * @param string $year
     * @param string $month
     * @param string $day
     * @return array the aircraft list
     */
	public function countAllAircraftTypesByOwner($owner,$filters = array(),$year = '',$month = '',$day = '')
	{
		global $globalDBdriver;
		$filter_query = $this->getFilter($filters,true,true);
		$owner = filter_var($owner,FILTER_SANITIZE_STRING);
		$query  = "SELECT DISTINCT spotter_output.aircraft_icao, COUNT(spotter_output.aircraft_icao) AS aircraft_icao_count, spotter_output.aircraft_name, spotter_output.aircraft_manufacturer 
				FROM spotter_output".$filter_query." spotter_output.owner_name = :owner";
		$query_values = array();
		if ($year != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND YEAR(spotter_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			} else {
				$query .= " AND EXTRACT(YEAR FROM spotter_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			}
		}
		if ($month != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND MONTH(spotter_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			} else {
				$query .= " AND EXTRACT(MONTH FROM spotter_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			}
		}
		if ($day != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND DAY(spotter_output.date) = :day";
				$query_values = array_merge($query_values,array(':day' => $day));
			} else {
				$query .= " AND EXTRACT(DAY FROM spotter_output.date) = :day";
				$query_values = array_merge($query_values,array(':day' => $day));
			}
		}
		$query .= " GROUP BY spotter_output.aircraft_name, spotter_output.aircraft_manufacturer, spotter_output.aircraft_icao
				ORDER BY aircraft_icao_count DESC";
		$query_values = array_merge($query_values,array(':owner' => $owner));
		$sth = $this->db->prepare($query);
		$sth->execute($query_values);
		return $sth->fetchAll(PDO::FETCH_ASSOC);
	}

    /**
     * Gets all aircraft registration that have flown over by ident/callsign
     *
     * @param $ident
     * @param array $filters
     * @return array the aircraft list
     */
	public function countAllAircraftRegistrationByIdent($ident,$filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$Image = new Image($this->db);
		$ident = filter_var($ident,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.aircraft_icao, COUNT(spotter_output.registration) AS registration_count, spotter_output.aircraft_name, spotter_output.registration, spotter_output.airline_name  
                    FROM spotter_output".$filter_query." spotter_output.registration <> '' AND spotter_output.ident = :ident 
                    GROUP BY spotter_output.registration,spotter_output.aircraft_icao, spotter_output.aircraft_name, spotter_output.airline_name
		    ORDER BY registration_count DESC";

		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':ident' => $ident));
      
		$aircraft_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['aircraft_icao'] = $row['aircraft_icao'];
			$temp_array['aircraft_name'] = $row['aircraft_name'];
			$temp_array['registration'] = $row['registration'];
			$temp_array['airline_name'] = $row['airline_name'];
			$temp_array['image_thumbnail'] = "";
			if($row['registration'] != "")
			{
				$image_array = $Image->getSpotterImage($row['registration']);
				if (isset($image_array[0]['image_thumbnail'])) $temp_array['image_thumbnail'] = $image_array[0]['image_thumbnail'];
				else $temp_array['image_thumbnail'] = '';
			}
			$temp_array['registration_count'] = $row['registration_count'];
			$aircraft_array[] = $temp_array;
		}
		return $aircraft_array;
	}

    /**
     * Gets all aircraft registration that have flown over by owner
     *
     * @param $owner
     * @param array $filters
     * @param string $year
     * @param string $month
     * @param string $day
     * @return array the aircraft list
     */
	public function countAllAircraftRegistrationByOwner($owner,$filters = array(),$year = '',$month = '',$day = '')
	{
		global $globalDBdriver;
		$filter_query = $this->getFilter($filters,true,true);
		$Image = new Image($this->db);
		$owner = filter_var($owner,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.aircraft_icao, COUNT(spotter_output.registration) AS registration_count, spotter_output.aircraft_name, spotter_output.aircraft_manufacturer, spotter_output.registration, spotter_output.airline_name  
                    FROM spotter_output".$filter_query." spotter_output.registration <> '' AND spotter_output.owner_name = :owner";
		$query_values = array();
		if ($year != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND YEAR(spotter_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			} else {
				$query .= " AND EXTRACT(YEAR FROM spotter_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			}
		}
		if ($month != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND MONTH(spotter_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			} else {
				$query .= " AND EXTRACT(MONTH FROM spotter_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			}
		}
		if ($day != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND DAY(spotter_output.date) = :day";
				$query_values = array_merge($query_values,array(':day' => $day));
			} else {
				$query .= " AND EXTRACT(DAY FROM spotter_output.date) = :day";
				$query_values = array_merge($query_values,array(':day' => $day));
			}
		}
		$query_values = array_merge($query_values,array(':owner' => $owner));

		$query .= " GROUP BY spotter_output.registration,spotter_output.aircraft_icao, spotter_output.aircraft_name, spotter_output.aircraft_manufacturer, spotter_output.airline_name
		    ORDER BY registration_count DESC";

		
		$sth = $this->db->prepare($query);
		$sth->execute($query_values);
      
		$aircraft_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['aircraft_icao'] = $row['aircraft_icao'];
			$temp_array['aircraft_name'] = $row['aircraft_name'];
			$temp_array['aircraft_manufacturer'] = $row['aircraft_manufacturer'];
			$temp_array['registration'] = $row['registration'];
			$temp_array['airline_name'] = $row['airline_name'];
			$temp_array['image_thumbnail'] = "";
			if($row['registration'] != "")
			{
				$image_array = $Image->getSpotterImage($row['registration']);
				if (isset($image_array[0]['image_thumbnail'])) $temp_array['image_thumbnail'] = $image_array[0]['image_thumbnail'];
				else $temp_array['image_thumbnail'] = '';
			}
			$temp_array['registration_count'] = $row['registration_count'];
			$aircraft_array[] = $temp_array;
		}
		return $aircraft_array;
	}

    /**
     * Gets all aircraft registration that have flown over by pilot
     *
     * @param $pilot
     * @param array $filters
     * @param string $year
     * @param string $month
     * @param string $day
     * @return array the aircraft list
     */
	public function countAllAircraftRegistrationByPilot($pilot,$filters = array(),$year = '',$month = '',$day = '')
	{
		global $globalDBdriver;
		$filter_query = $this->getFilter($filters,true,true);
		$Image = new Image($this->db);
		$pilot = filter_var($pilot,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.aircraft_icao, COUNT(spotter_output.registration) AS registration_count, spotter_output.aircraft_name, spotter_output.aircraft_manufacturer, spotter_output.registration, spotter_output.airline_name  
                    FROM spotter_output".$filter_query." spotter_output.registration <> '' AND (spotter_output.pilot_name = :pilot OR spotter_output.pilot_id = :pilot)";
		$query_values = array();
		if ($year != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND YEAR(spotter_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			} else {
				$query .= " AND EXTRACT(YEAR FROM spotter_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			}
		}
		if ($month != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND MONTH(spotter_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			} else {
				$query .= " AND EXTRACT(MONTH FROM spotter_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			}
		}
		if ($day != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND DAY(spotter_output.date) = :day";
				$query_values = array_merge($query_values,array(':day' => $day));
			} else {
				$query .= " AND EXTRACT(DAY FROM spotter_output.date) = :day";
				$query_values = array_merge($query_values,array(':day' => $day));
			}
		}
		$query_values = array_merge($query_values,array(':pilot' => $pilot));

		$query .= " GROUP BY spotter_output.registration,spotter_output.aircraft_icao, spotter_output.aircraft_name, spotter_output.aircraft_manufacturer, spotter_output.airline_name
		    ORDER BY registration_count DESC";

		
		$sth = $this->db->prepare($query);
		$sth->execute($query_values);
      
		$aircraft_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['aircraft_icao'] = $row['aircraft_icao'];
			$temp_array['aircraft_name'] = $row['aircraft_name'];
			$temp_array['aircraft_manufacturer'] = $row['aircraft_manufacturer'];
			$temp_array['registration'] = $row['registration'];
			$temp_array['airline_name'] = $row['airline_name'];
			$temp_array['image_thumbnail'] = "";
			if($row['registration'] != "")
			{
				$image_array = $Image->getSpotterImage($row['registration']);
				if (isset($image_array[0]['image_thumbnail'])) $temp_array['image_thumbnail'] = $image_array[0]['image_thumbnail'];
				else $temp_array['image_thumbnail'] = '';
			}
			$temp_array['registration_count'] = $row['registration_count'];
			$aircraft_array[] = $temp_array;
		}
		return $aircraft_array;
	}


    /**
     * Gets all aircraft manufacturer that have flown over by ident/callsign
     *
     * @param $ident
     * @param array $filters
     * @return array the aircraft manufacturer list
     */
	public function countAllAircraftManufacturerByIdent($ident,$filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$ident = filter_var($ident,FILTER_SANITIZE_STRING);
		$query  = "SELECT DISTINCT spotter_output.aircraft_manufacturer, COUNT(spotter_output.aircraft_manufacturer) AS aircraft_manufacturer_count  
                    FROM spotter_output".$filter_query." spotter_output.aircraft_manufacturer <> '' AND spotter_output.ident = :ident  
                    GROUP BY spotter_output.aircraft_manufacturer 
					ORDER BY aircraft_manufacturer_count DESC";

		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':ident' => $ident));
		$aircraft_array = array();
		$temp_array = array();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['aircraft_manufacturer'] = $row['aircraft_manufacturer'];
			$temp_array['aircraft_manufacturer_count'] = $row['aircraft_manufacturer_count'];
			$aircraft_array[] = $temp_array;
		}
		return $aircraft_array;
	}

    /**
     * Gets all aircraft manufacturer that have flown over by owner
     *
     * @param $owner
     * @param array $filters
     * @param string $year
     * @param string $month
     * @param string $day
     * @return array the aircraft manufacturer list
     */
	public function countAllAircraftManufacturerByOwner($owner,$filters = array(),$year = '',$month = '',$day = '')
	{
		global $globalDBdriver;
		$filter_query = $this->getFilter($filters,true,true);
		$owner = filter_var($owner,FILTER_SANITIZE_STRING);
		$query  = "SELECT DISTINCT spotter_output.aircraft_manufacturer, COUNT(spotter_output.aircraft_manufacturer) AS aircraft_manufacturer_count  
		    FROM spotter_output".$filter_query." spotter_output.aircraft_manufacturer <> '' AND spotter_output.owner_name = :owner";
		$query_values = array();
		if ($year != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND YEAR(spotter_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			} else {
				$query .= " AND EXTRACT(YEAR FROM spotter_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			}
		}
		if ($month != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND MONTH(spotter_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			} else {
				$query .= " AND EXTRACT(MONTH FROM spotter_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			}
		}
		if ($day != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND DAY(spotter_output.date) = :day";
				$query_values = array_merge($query_values,array(':day' => $day));
			} else {
				$query .= " AND EXTRACT(DAY FROM spotter_output.date) = :day";
				$query_values = array_merge($query_values,array(':day' => $day));
			}
		}
		$query_values = array_merge($query_values,array(':owner' => $owner));

		$query .= " GROUP BY spotter_output.aircraft_manufacturer 
		    ORDER BY aircraft_manufacturer_count DESC";

		
		$sth = $this->db->prepare($query);
		$sth->execute($query_values);
		return $sth->fetchAll(PDO::FETCH_ASSOC);
	}

    /**
     * Gets all aircraft manufacturer that have flown over by pilot
     *
     * @param $pilot
     * @param array $filters
     * @param string $year
     * @param string $month
     * @param string $day
     * @return array the aircraft manufacturer list
     */
	public function countAllAircraftManufacturerByPilot($pilot,$filters = array(),$year = '',$month = '',$day = '')
	{
		global $globalDBdriver;
		$filter_query = $this->getFilter($filters,true,true);
		$pilot = filter_var($pilot,FILTER_SANITIZE_STRING);
		$query  = "SELECT DISTINCT spotter_output.aircraft_manufacturer, COUNT(spotter_output.aircraft_manufacturer) AS aircraft_manufacturer_count  
		    FROM spotter_output".$filter_query." spotter_output.aircraft_manufacturer <> '' AND (spotter_output.pilot_name = :pilot OR spotter_output.pilot_id = :pilot)";
		$query_values = array();
		if ($year != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND YEAR(spotter_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			} else {
				$query .= " AND EXTRACT(YEAR FROM spotter_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			}
		}
		if ($month != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND MONTH(spotter_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			} else {
				$query .= " AND EXTRACT(MONTH FROM spotter_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			}
		}
		if ($day != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND DAY(spotter_output.date) = :day";
				$query_values = array_merge($query_values,array(':day' => $day));
			} else {
				$query .= " AND EXTRACT(DAY FROM spotter_output.date) = :day";
				$query_values = array_merge($query_values,array(':day' => $day));
			}
		}
		$query_values = array_merge($query_values,array(':pilot' => $pilot));

		$query .= " GROUP BY spotter_output.aircraft_manufacturer 
		    ORDER BY aircraft_manufacturer_count DESC";

		
		$sth = $this->db->prepare($query);
		$sth->execute($query_values);
		return $sth->fetchAll(PDO::FETCH_ASSOC);
	}


    /**
     * Gets all aircraft types that have flown over by route
     *
     * @param $departure_airport_icao
     * @param $arrival_airport_icao
     * @param array $filters
     * @return array the aircraft list
     */
	public function countAllAircraftTypesByRoute($departure_airport_icao, $arrival_airport_icao,$filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$departure_airport_icao = filter_var($departure_airport_icao,FILTER_SANITIZE_STRING);
		$arrival_airport_icao = filter_var($arrival_airport_icao,FILTER_SANITIZE_STRING);
		

		$query  = "SELECT DISTINCT spotter_output.aircraft_icao, COUNT(spotter_output.aircraft_icao) AS aircraft_icao_count, spotter_output.aircraft_name  
                    FROM spotter_output".$filter_query." (spotter_output.departure_airport_icao = :departure_airport_icao) AND (spotter_output.arrival_airport_icao = :arrival_airport_icao)
                    GROUP BY spotter_output.aircraft_name 
					ORDER BY aircraft_icao_count DESC";
 
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':departure_airport_icao' => $departure_airport_icao,':arrival_airport_icao' => $arrival_airport_icao));
		$aircraft_array = array();
		$temp_array = array();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['aircraft_icao'] = $row['aircraft_icao'];
			$temp_array['aircraft_name'] = $row['aircraft_name'];
			$temp_array['aircraft_icao_count'] = $row['aircraft_icao_count'];
			$aircraft_array[] = $temp_array;
		}
		return $aircraft_array;
	}

    /**
     * Gets all aircraft registration that have flown over by route
     *
     * @param $departure_airport_icao
     * @param $arrival_airport_icao
     * @param array $filters
     * @return array the aircraft list
     */
	public function countAllAircraftRegistrationByRoute($departure_airport_icao, $arrival_airport_icao,$filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$Image = new Image($this->db);
		$departure_airport_icao = filter_var($departure_airport_icao,FILTER_SANITIZE_STRING);
		$arrival_airport_icao = filter_var($arrival_airport_icao,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.aircraft_icao, COUNT(spotter_output.registration) AS registration_count, spotter_output.aircraft_name, spotter_output.registration, spotter_output.airline_name 
		    FROM spotter_output".$filter_query." spotter_output.registration <> '' AND (spotter_output.departure_airport_icao = :departure_airport_icao) AND (spotter_output.arrival_airport_icao = :arrival_airport_icao) 
		    GROUP BY spotter_output.registration 
		    ORDER BY registration_count DESC";

		$sth = $this->db->prepare($query);
		$sth->execute(array(':departure_airport_icao' => $departure_airport_icao,':arrival_airport_icao' => $arrival_airport_icao));
      
		$aircraft_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['aircraft_icao'] = $row['aircraft_icao'];
			$temp_array['aircraft_name'] = $row['aircraft_name'];
			$temp_array['registration'] = $row['registration'];
			$temp_array['airline_name'] = $row['airline_name'];
			$temp_array['image_thumbnail'] = "";
			if($row['registration'] != "")
			{
				$image_array = $Image->getSpotterImage($row['registration']);
				if (isset($image_array[0]['image_thumbnail'])) $temp_array['image_thumbnail'] = $image_array[0]['image_thumbnail'];
			}
			$temp_array['registration_count'] = $row['registration_count'];
          
			$aircraft_array[] = $temp_array;
		}

		return $aircraft_array;
	}


    /**
     * Gets all aircraft manufacturer that have flown over by route
     *
     * @param $departure_airport_icao
     * @param $arrival_airport_icao
     * @param array $filters
     * @return array the aircraft manufacturer list
     */
	public function countAllAircraftManufacturerByRoute($departure_airport_icao, $arrival_airport_icao,$filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$departure_airport_icao = filter_var($departure_airport_icao,FILTER_SANITIZE_STRING);
		$arrival_airport_icao = filter_var($arrival_airport_icao,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.aircraft_manufacturer, COUNT(spotter_output.aircraft_manufacturer) AS aircraft_manufacturer_count  
                    FROM spotter_output".$filter_query." spotter_output.aircraft_manufacturer <> '' AND (spotter_output.departure_airport_icao = :departure_airport_icao) AND (spotter_output.arrival_airport_icao = :arrival_airport_icao) 
                    GROUP BY spotter_output.aircraft_manufacturer 
					ORDER BY aircraft_manufacturer_count DESC";

		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':departure_airport_icao' => $departure_airport_icao,':arrival_airport_icao' => $arrival_airport_icao));
      
		$aircraft_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['aircraft_manufacturer'] = $row['aircraft_manufacturer'];
			$temp_array['aircraft_manufacturer_count'] = $row['aircraft_manufacturer_count'];
          
			$aircraft_array[] = $temp_array;
		}

		return $aircraft_array;
	}


    /**
     * Gets all aircraft types that have flown over by country
     *
     * @param $country
     * @param array $filters
     * @return array the aircraft list
     */
	public function countAllAircraftTypesByCountry($country,$filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$country = filter_var($country,FILTER_SANITIZE_STRING);
		$query  = "SELECT DISTINCT spotter_output.aircraft_icao, COUNT(spotter_output.aircraft_icao) AS aircraft_icao_count, spotter_output.aircraft_name  
			    FROM spotter_output".$filter_query." ((spotter_output.departure_airport_country = :country) OR (spotter_output.arrival_airport_country = :country)) OR spotter_output.airline_country = :country 
			    GROUP BY spotter_output.aircraft_name, spotter_output.aircraft_icao 
			    ORDER BY aircraft_icao_count DESC";
 
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':country' => $country));
      
		$aircraft_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['aircraft_icao'] = $row['aircraft_icao'];
			$temp_array['aircraft_name'] = $row['aircraft_name'];
			$temp_array['aircraft_icao_count'] = $row['aircraft_icao_count'];
          
			$aircraft_array[] = $temp_array;
		}

		return $aircraft_array;
	}


    /**
     * Gets all aircraft registration that have flown over by country
     *
     * @param $country
     * @param array $filters
     * @return array the aircraft list
     */
	public function countAllAircraftRegistrationByCountry($country,$filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$Image = new Image($this->db);
		$country = filter_var($country,FILTER_SANITIZE_STRING);
		$query  = "SELECT DISTINCT spotter_output.aircraft_icao, COUNT(spotter_output.registration) AS registration_count, spotter_output.aircraft_name, spotter_output.registration, spotter_output.airline_name 
			    FROM spotter_output".$filter_query." spotter_output.registration <> '' AND (((spotter_output.departure_airport_country = :country) OR (spotter_output.arrival_airport_country = :country)) OR spotter_output.airline_country = :country)    
			    GROUP BY spotter_output.registration, spotter_output.aircraft_icao, spotter_output.aircraft_name, spotter_output.airline_name 
			    ORDER BY registration_count DESC";

		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':country' => $country));
      
		$aircraft_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['aircraft_icao'] = $row['aircraft_icao'];
			$temp_array['aircraft_name'] = $row['aircraft_name'];
			$temp_array['registration'] = $row['registration'];
			$temp_array['airline_name'] = $row['airline_name'];
			$temp_array['image_thumbnail'] = "";
			if($row['registration'] != "")
			{
				$image_array = $Image->getSpotterImage($row['registration']);
				if (isset($image_array[0]['image_thumbnail'])) $temp_array['image_thumbnail'] = $image_array[0]['image_thumbnail'];
			}
			$temp_array['registration_count'] = $row['registration_count'];
          
			$aircraft_array[] = $temp_array;
		}

		return $aircraft_array;
	}
	
	
	/**
	* Gets all aircraft manufacturer that have flown over by country
	*
	* @return Array the aircraft manufacturer list
	*
	*/
	public function countAllAircraftManufacturerByCountry($country,$filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$country = filter_var($country,FILTER_SANITIZE_STRING);
		$query  = "SELECT DISTINCT spotter_output.aircraft_manufacturer, COUNT(spotter_output.aircraft_manufacturer) AS aircraft_manufacturer_count  
                    FROM spotter_output".$filter_query." spotter_output.aircraft_manufacturer <> '' AND (((spotter_output.departure_airport_country = :country) OR (spotter_output.arrival_airport_country = :country)) OR spotter_output.airline_country = :country) 
                    GROUP BY spotter_output.aircraft_manufacturer 
					ORDER BY aircraft_manufacturer_count DESC";

		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':country' => $country));
      
		$aircraft_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['aircraft_manufacturer'] = $row['aircraft_manufacturer'];
			$temp_array['aircraft_manufacturer_count'] = $row['aircraft_manufacturer_count'];
          
			$aircraft_array[] = $temp_array;
		}

		return $aircraft_array;
	}


    /**
     * Gets all aircraft manufacturers that have flown over
     *
     * @param array $filters
     * @param string $year
     * @param string $month
     * @param string $day
     * @return array the aircraft list
     */
	public function countAllAircraftManufacturers($filters = array(),$year = '',$month = '',$day = '')
	{
		global $globalDBdriver;
		$filter_query = $this->getFilter($filters,true,true);
		$query  = "SELECT DISTINCT spotter_output.aircraft_manufacturer, COUNT(spotter_output.aircraft_manufacturer) AS aircraft_manufacturer_count  
                    FROM spotter_output ".$filter_query." spotter_output.aircraft_manufacturer <> '' AND spotter_output.aircraft_manufacturer <> 'Not Available'";
                $query_values = array();
		if ($year != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND YEAR(spotter_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			} else {
				$query .= " AND EXTRACT(YEAR FROM spotter_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			}
		}
		if ($month != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND MONTH(spotter_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			} else {
				$query .= " AND EXTRACT(MONTH FROM spotter_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			}
		}
		if ($day != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND DAY(spotter_output.date) = :day";
				$query_values = array_merge($query_values,array(':day' => $day));
			} else {
				$query .= " AND EXTRACT(DAY FROM spotter_output.date) = :day";
				$query_values = array_merge($query_values,array(':day' => $day));
			}
		}
		$query .= " GROUP BY spotter_output.aircraft_manufacturer
					ORDER BY aircraft_manufacturer_count DESC
					LIMIT 10";
      
		
		$sth = $this->db->prepare($query);
		$sth->execute($query_values);
      
		$manufacturer_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['aircraft_manufacturer'] = $row['aircraft_manufacturer'];
			$temp_array['aircraft_manufacturer_count'] = $row['aircraft_manufacturer_count'];
          
			$manufacturer_array[] = $temp_array;
		}

		return $manufacturer_array;
	}


    /**
     * Gets all aircraft registrations that have flown over
     *
     * @param bool $limit
     * @param int $olderthanmonths
     * @param string $sincedate
     * @param array $filters
     * @param string $year
     * @param string $month
     * @param string $day
     * @return array the aircraft list
     */
	public function countAllAircraftRegistrations($limit = true,$olderthanmonths = 0,$sincedate = '',$filters = array(),$year = '', $month = '', $day = '')
	{
		global $globalDBdriver;
		$Image = new Image($this->db);
		$filter_query = $this->getFilter($filters,true,true);
		$query  = "SELECT DISTINCT spotter_output.registration, COUNT(spotter_output.registration) AS aircraft_registration_count, spotter_output.aircraft_icao,  spotter_output.aircraft_name, spotter_output.airline_name    
                    FROM spotter_output ".$filter_query." spotter_output.registration <> '' AND spotter_output.registration <> 'NA'";
                if ($olderthanmonths > 0) {
            		if ($globalDBdriver == 'mysql') {
				$query .= ' AND spotter_output.date < DATE_SUB(UTC_TIMESTAMP(), INTERVAL '.$olderthanmonths.' MONTH)';
			} else {
				$query .= " AND spotter_output.date < CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$olderthanmonths." MONTHS'";
			}
		}
                if ($sincedate != '') {
            		if ($globalDBdriver == 'mysql') {
				$query .= " AND spotter_output.date > '".$sincedate."'";
			} else {
				$query .= " AND spotter_output.date > CAST('".$sincedate."' AS TIMESTAMP)";
			}
		}
		$query_values = array();
		if ($year != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND YEAR(spotter_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			} else {
				$query .= " AND EXTRACT(YEAR FROM spotter_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			}
		}
		if ($month != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND MONTH(spotter_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			} else {
				$query .= " AND EXTRACT(MONTH FROM spotter_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			}
		}
		if ($day != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND DAY(spotter_output.date) = :day";
				$query_values = array_merge($query_values,array(':day' => $day));
			} else {
				$query .= " AND EXTRACT(DAY FROM spotter_output.date) = :day";
				$query_values = array_merge($query_values,array(':day' => $day));
			}
		}
		$query .= " GROUP BY spotter_output.registration, spotter_output.aircraft_icao, spotter_output.aircraft_name, spotter_output.airline_name ORDER BY aircraft_registration_count DESC";
		if ($limit) $query .= " LIMIT 10 OFFSET 0";
		
		$sth = $this->db->prepare($query);
		$sth->execute($query_values);
      
		$aircraft_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['registration'] = $row['registration'];
			$temp_array['aircraft_registration_count'] = $row['aircraft_registration_count'];
			$temp_array['aircraft_icao'] = $row['aircraft_icao'];
			$temp_array['aircraft_name'] = $row['aircraft_name'];
			$temp_array['airline_name'] = $row['airline_name'];
			$temp_array['image_thumbnail'] = "";
			if($row['registration'] != "")
			{
				$image_array = $Image->getSpotterImage($row['registration']);
				if (isset($image_array[0]['image_thumbnail'])) $temp_array['image_thumbnail'] = $image_array[0]['image_thumbnail'];
			}
          
			$aircraft_array[] = $temp_array;
		}

		return $aircraft_array;
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
	public function countAllAircraftRegistrationsByAirlines($limit = true,$olderthanmonths = 0,$sincedate = '',$filters = array())
	{
		global $globalDBdriver;
		$filter_query = $this->getFilter($filters,true,true);
		$Image = new Image($this->db);
		$query  = "SELECT DISTINCT spotter_output.airline_icao, spotter_output.registration, COUNT(spotter_output.registration) AS aircraft_registration_count, spotter_output.aircraft_icao,  spotter_output.aircraft_name, spotter_output.airline_name 
                    FROM spotter_output".$filter_query." spotter_output.airline_icao <> '' AND spotter_output.registration <> '' AND spotter_output.registration <> 'NA' ";
                if ($olderthanmonths > 0) {
            		if ($globalDBdriver == 'mysql') {
				$query .= 'AND spotter_output.date < DATE_SUB(UTC_TIMESTAMP(), INTERVAL '.$olderthanmonths.' MONTH) ';
			} else {
				$query .= "AND spotter_output.date < CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$olderthanmonths." MONTHS' ";
			}
		}
                if ($sincedate != '') {
            		if ($globalDBdriver == 'mysql') {
				$query .= "AND spotter_output.date > '".$sincedate."' ";
			} else {
				$query .= "AND spotter_output.date > CAST('".$sincedate."' AS TIMESTAMP)";
			}
		}

		// if ($olderthanmonths > 0) $query .= 'AND date < DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.$olderthanmonths.' MONTH) ';
		//if ($sincedate != '') $query .= "AND date > '".$sincedate."' ";
                $query .= "GROUP BY spotter_output.airline_icao, spotter_output.registration, spotter_output.aircraft_icao, spotter_output.aircraft_name, spotter_output.airline_name ORDER BY aircraft_registration_count DESC";
		if ($limit) $query .= " LIMIT 10 OFFSET 0";
		
		$sth = $this->db->prepare($query);
		$sth->execute();
      
		$aircraft_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['registration'] = $row['registration'];
			$temp_array['aircraft_registration_count'] = $row['aircraft_registration_count'];
			$temp_array['airline_icao'] = $row['airline_icao'];
			$temp_array['aircraft_icao'] = $row['aircraft_icao'];
			$temp_array['aircraft_name'] = $row['aircraft_name'];
			$temp_array['airline_name'] = $row['airline_name'];
			$temp_array['image_thumbnail'] = "";
			if($row['registration'] != "")
			{
				$image_array = $Image->getSpotterImage($row['registration']);
				if (isset($image_array[0]['image_thumbnail'])) $temp_array['image_thumbnail'] = $image_array[0]['image_thumbnail'];
			}
          
			$aircraft_array[] = $temp_array;
		}
		return $aircraft_array;
	}


    /**
     * Gets all departure airports of the airplanes that have flown over
     *
     * @param bool $limit
     * @param int $olderthanmonths
     * @param string $sincedate
     * @param array $filters
     * @param string $year
     * @param string $month
     * @param string $day
     * @return array the airport list
     */
	public function countAllDepartureAirports($limit = true, $olderthanmonths = 0, $sincedate = '',$filters = array(),$year = '',$month = '',$day = '')
	{
		global $globalDBdriver;
		$filter_query = $this->getFilter($filters,true,true);
		$query  = "SELECT DISTINCT spotter_output.departure_airport_icao, COUNT(spotter_output.departure_airport_icao) AS airport_departure_icao_count, spotter_output.departure_airport_name, spotter_output.departure_airport_city, spotter_output.departure_airport_country, airport.latitude, airport.longitude
				FROM airport, spotter_output".$filter_query." airport.icao = spotter_output.departure_airport_icao AND spotter_output.departure_airport_name <> '' AND spotter_output.departure_airport_icao <> 'NA' AND spotter_output.departure_airport_icao <> ''";
                if ($olderthanmonths > 0) {
            		if ($globalDBdriver == 'mysql') {
				$query .= ' AND spotter_output.date < DATE_SUB(UTC_TIMESTAMP(), INTERVAL '.$olderthanmonths.' MONTH)';
			} else {
				$query .= " AND spotter_output.date < CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$olderthanmonths." MONTHS'";
			}
                }
                if ($sincedate != '') {
            		if ($globalDBdriver == 'mysql') {
				$query .= " AND spotter_output.date > '".$sincedate."'";
			} else {
				$query .= " AND spotter_output.date > CAST('".$sincedate."' AS TIMESTAMP)";
			}
		}
		$query_values = array();
		if ($year != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND YEAR(spotter_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			} else {
				$query .= " AND EXTRACT(YEAR FROM spotter_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			}
		}
		if ($month != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND MONTH(spotter_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			} else {
				$query .= " AND EXTRACT(MONTH FROM spotter_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			}
		}
		if ($day != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND DAY(spotter_output.date) = :day";
				$query_values = array_merge($query_values,array(':day' => $day));
			} else {
				$query .= " AND EXTRACT(DAY FROM spotter_output.date) = :day";
				$query_values = array_merge($query_values,array(':day' => $day));
			}
		}
                $query .= " GROUP BY spotter_output.departure_airport_icao, spotter_output.departure_airport_name, spotter_output.departure_airport_city, spotter_output.departure_airport_country, airport.latitude, airport.longitude
				ORDER BY airport_departure_icao_count DESC";
		if ($limit) $query .= " LIMIT 10 OFFSET 0";

		$sth = $this->db->prepare($query);
		$sth->execute($query_values);

		$airport_array = array();
		$temp_array = array();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airport_departure_icao'] = $row['departure_airport_icao'];
			$temp_array['airport_departure_icao_count'] = $row['airport_departure_icao_count'];
			$temp_array['airport_departure_name'] = $row['departure_airport_name'];
			$temp_array['airport_departure_city'] = $row['departure_airport_city'];
			$temp_array['airport_departure_country'] = $row['departure_airport_country'];
			$temp_array['airport_departure_latitude'] = $row['latitude'];
			$temp_array['airport_departure_longitude'] = $row['longitude'];
			$airport_array[] = $temp_array;
		}
		return $airport_array;
	}

    /**
     * Gets all departure airports of the airplanes that have flown over
     *
     * @param bool $limit
     * @param int $olderthanmonths
     * @param string $sincedate
     * @param array $filters
     * @return array the airport list
     */
	public function countAllDepartureAirportsByAirlines($limit = true, $olderthanmonths = 0, $sincedate = '',$filters = array())
	{
		global $globalDBdriver;
		$filter_query = $this->getFilter($filters,true,true);
		$query  = "SELECT DISTINCT spotter_output.airline_icao, spotter_output.departure_airport_icao, COUNT(spotter_output.departure_airport_icao) AS airport_departure_icao_count, spotter_output.departure_airport_name, spotter_output.departure_airport_city, spotter_output.departure_airport_country 
			FROM spotter_output".$filter_query." spotter_output.airline_icao <> '' AND spotter_output.departure_airport_name <> '' AND spotter_output.departure_airport_icao <> 'NA' AND spotter_output.departure_airport_icao <> '' ";
                if ($olderthanmonths > 0) {
            		if ($globalDBdriver == 'mysql') {
				$query .= 'AND spotter_output.date < DATE_SUB(UTC_TIMESTAMP(), INTERVAL '.$olderthanmonths.' MONTH) ';
			} else {
				$query .= "AND spotter_output.date < CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$olderthanmonths." MONTHS' ";
			}
                }
                if ($sincedate != '') {
            		if ($globalDBdriver == 'mysql') {
				$query .= "AND spotter_output.date > '".$sincedate."' ";
			} else {
				$query .= "AND spotter_output.date > CAST('".$sincedate."' AS TIMESTAMP)";
			}
		}

            	//if ($olderthanmonths > 0) $query .= 'AND date < DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.$olderthanmonths.' MONTH) ';
                //if ($sincedate != '') $query .= "AND date > '".$sincedate."' ";
                $query .= "GROUP BY spotter_output.airline_icao, spotter_output.departure_airport_icao, spotter_output.departure_airport_name, spotter_output.departure_airport_city, spotter_output.departure_airport_country
				ORDER BY airport_departure_icao_count DESC";
		if ($limit) $query .= " LIMIT 10 OFFSET 0";
      
		$sth = $this->db->prepare($query);
		$sth->execute();
      
		$airport_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airline_icao'] = $row['airline_icao'];
			$temp_array['airport_departure_icao'] = $row['departure_airport_icao'];
			$temp_array['airport_departure_icao_count'] = $row['airport_departure_icao_count'];
			$temp_array['airport_departure_name'] = $row['departure_airport_name'];
			$temp_array['airport_departure_city'] = $row['departure_airport_city'];
			$temp_array['airport_departure_country'] = $row['departure_airport_country'];
          
			$airport_array[] = $temp_array;
		}
		return $airport_array;
	}

    /**
     * Gets all detected departure airports of the airplanes that have flown over
     *
     * @param bool $limit
     * @param int $olderthanmonths
     * @param string $sincedate
     * @param array $filters
     * @param string $year
     * @param string $month
     * @param string $day
     * @return array the airport list
     */
	public function countAllDetectedDepartureAirports($limit = true, $olderthanmonths = 0, $sincedate = '',$filters = array(),$year = '',$month = '',$day = '')
	{
		global $globalDBdriver;
		$filter_query = $this->getFilter($filters,true,true);
		$query  = "SELECT DISTINCT spotter_output.real_departure_airport_icao AS departure_airport_icao, COUNT(spotter_output.real_departure_airport_icao) AS airport_departure_icao_count, airport.name as departure_airport_name, airport.city as departure_airport_city, airport.country as departure_airport_country, airport.latitude as departure_airport_latitude, airport.longitude as departure_airport_longitude
				FROM airport, spotter_output".$filter_query." spotter_output.real_departure_airport_icao <> '' AND spotter_output.real_departure_airport_icao <> 'NA' AND airport.icao = spotter_output.real_departure_airport_icao";
                if ($olderthanmonths > 0) {
            		if ($globalDBdriver == 'mysql') {
				$query .= ' AND spotter_output.date < DATE_SUB(UTC_TIMESTAMP(), INTERVAL '.$olderthanmonths.' MONTH)';
			} else {
				$query .= " AND spotter_output.date < CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$olderthanmonths." MONTHS'";
			}
                }
                if ($sincedate != '') {
            		if ($globalDBdriver == 'mysql') {
				$query .= " AND spotter_output.date > '".$sincedate."'";
			} else {
				$query .= " AND spotter_output.date > CAST('".$sincedate."' AS TIMESTAMP)";
			}
		}
		$query_values = array();
		if ($year != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND YEAR(spotter_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			} else {
				$query .= " AND EXTRACT(YEAR FROM spotter_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			}
		}
		if ($month != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND MONTH(spotter_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			} else {
				$query .= " AND EXTRACT(MONTH FROM spotter_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			}
		}
		if ($day != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND DAY(spotter_output.date) = :day";
				$query_values = array_merge($query_values,array(':day' => $day));
			} else {
				$query .= " AND EXTRACT(DAY FROM spotter_output.date) = :day";
				$query_values = array_merge($query_values,array(':day' => $day));
			}
		}
                $query .= " GROUP BY spotter_output.real_departure_airport_icao, airport.name, airport.city, airport.country, airport.latitude, airport.longitude
				ORDER BY airport_departure_icao_count DESC";
		if ($limit) $query .= " LIMIT 10 OFFSET 0";
    		//echo $query;
		$sth = $this->db->prepare($query);
		$sth->execute($query_values);
      
		$airport_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airport_departure_icao'] = $row['departure_airport_icao'];
			$temp_array['airport_departure_icao_count'] = $row['airport_departure_icao_count'];
			$temp_array['airport_departure_name'] = $row['departure_airport_name'];
			$temp_array['airport_departure_city'] = $row['departure_airport_city'];
			$temp_array['airport_departure_country'] = $row['departure_airport_country'];
			$temp_array['airport_departure_latitude'] = $row['departure_airport_latitude'];
			$temp_array['airport_departure_longitude'] = $row['departure_airport_longitude'];
          
			$airport_array[] = $temp_array;
		}
		return $airport_array;
	}

    /**
     * Gets all detected departure airports of the airplanes that have flown over
     *
     * @param bool $limit
     * @param int $olderthanmonths
     * @param string $sincedate
     * @param array $filters
     * @return array the airport list
     */
	public function countAllDetectedDepartureAirportsByAirlines($limit = true, $olderthanmonths = 0, $sincedate = '',$filters = array())
	{
		global $globalDBdriver;
		$filter_query = $this->getFilter($filters,true,true);
		$query  = "SELECT DISTINCT spotter_output.airline_icao, spotter_output.real_departure_airport_icao AS departure_airport_icao, COUNT(spotter_output.real_departure_airport_icao) AS airport_departure_icao_count, airport.name as departure_airport_name, airport.city as departure_airport_city, airport.country as departure_airport_country
				FROM airport, spotter_output".$filter_query." spotter_output.airline_icao <> '' AND spotter_output.real_departure_airport_icao <> '' AND spotter_output.real_departure_airport_icao <> 'NA' AND airport.icao = spotter_output.real_departure_airport_icao ";
                if ($olderthanmonths > 0) {
            		if ($globalDBdriver == 'mysql') {
				$query .= 'AND spotter_output.date < DATE_SUB(UTC_TIMESTAMP(), INTERVAL '.$olderthanmonths.' MONTH) ';
			} else {
				$query .= "AND spotter_output.date < CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$olderthanmonths." MONTHS' ";
			}
                }
                if ($sincedate != '') {
            		if ($globalDBdriver == 'mysql') {
				$query .= "AND spotter_output.date > '".$sincedate."' ";
			} else {
				$query .= "AND spotter_output.date > CAST('".$sincedate."' AS TIMESTAMP) ";
			}
		}

            	//if ($olderthanmonths > 0) $query .= 'AND date < DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.$olderthanmonths.' MONTH) ';
                //if ($sincedate != '') $query .= "AND date > '".$sincedate."' ";
                $query .= "GROUP BY spotter_output.airline_icao, spotter_output.real_departure_airport_icao, airport.name, airport.city, airport.country
				ORDER BY airport_departure_icao_count DESC";
		if ($limit) $query .= " LIMIT 10 OFFSET 0";
      
		$sth = $this->db->prepare($query);
		$sth->execute();
      
		$airport_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airline_icao'] = $row['airline_icao'];
			$temp_array['airport_departure_icao'] = $row['departure_airport_icao'];
			$temp_array['airport_departure_icao_count'] = $row['airport_departure_icao_count'];
			$temp_array['airport_departure_name'] = $row['departure_airport_name'];
			$temp_array['airport_departure_city'] = $row['departure_airport_city'];
			$temp_array['airport_departure_country'] = $row['departure_airport_country'];
          
			$airport_array[] = $temp_array;
		}
		return $airport_array;
	}

    /**
     * Gets all departure airports of the airplanes that have flown over based on an airline icao
     *
     * @param $airline_icao
     * @param array $filters
     * @return array the airport list
     */
	public function countAllDepartureAirportsByAirline($airline_icao,$filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$airline_icao = filter_var($airline_icao,FILTER_SANITIZE_STRING);
		$query  = "SELECT DISTINCT spotter_output.departure_airport_icao, COUNT(spotter_output.departure_airport_icao) AS airport_departure_icao_count, spotter_output.departure_airport_name, spotter_output.departure_airport_city, spotter_output.departure_airport_country, airport.latitude, airport.longitude 
			    FROM airport,spotter_output".$filter_query." spotter_output.departure_airport_name <> '' AND spotter_output.departure_airport_icao <> 'NA' ";
		if ($airline_icao != '') $query .= "AND spotter_output.airline_icao = :airline_icao ";
		$query .= "AND spotter_output.departure_airport_icao <> '' AND airport.icao = spotter_output.departure_airport_icao 
			    GROUP BY spotter_output.departure_airport_icao, spotter_output.departure_airport_name, spotter_output.departure_airport_city, spotter_output.departure_airport_country, airport.latitude, airport.longitude 
			    ORDER BY airport_departure_icao_count DESC";
		$sth = $this->db->prepare($query);
		if ($airline_icao != '') $sth->execute(array(':airline_icao' => $airline_icao));
		else $sth->execute();
		$airport_array = array();
		$temp_array = array();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airport_departure_icao'] = $row['departure_airport_icao'];
			$temp_array['airport_departure_icao_count'] = $row['airport_departure_icao_count'];
			$temp_array['airport_departure_name'] = $row['departure_airport_name'];
			$temp_array['airport_departure_city'] = $row['departure_airport_city'];
			$temp_array['airport_departure_country'] = $row['departure_airport_country'];
			$temp_array['airport_departure_latitude'] = $row['latitude'];
			$temp_array['airport_departure_longitude'] = $row['longitude'];
			$airport_array[] = $temp_array;
		}
		return $airport_array;
	}

	/**
	* Gets all departure airports by country of the airplanes that have flown over based on an airline icao
	*
	* @return Array the airport list
	*
	*/
	public function countAllDepartureAirportCountriesByAirline($airline_icao,$filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$airline_icao = filter_var($airline_icao,FILTER_SANITIZE_STRING);
		$query  = "SELECT DISTINCT spotter_output.departure_airport_country, COUNT(spotter_output.departure_airport_country) AS airport_departure_country_count, countries.iso3 AS departure_airport_country_iso3 
			FROM countries,spotter_output".$filter_query." countries.name = spotter_output.departure_airport_country AND spotter_output.departure_airport_country <> '' ";
		if ($airline_icao != '') $query .= "AND spotter_output.airline_icao = :airline_icao ";
		$query .= "GROUP BY spotter_output.departure_airport_country, countries.iso3
			ORDER BY airport_departure_country_count DESC";
		
		$sth = $this->db->prepare($query);
		if ($airline_icao != '') $sth->execute(array(':airline_icao' => $airline_icao));
		else $sth->execute();
      
		$airport_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['departure_airport_country'] = $row['departure_airport_country'];
			$temp_array['airport_departure_country_count'] = $row['airport_departure_country_count'];
			$temp_array['departure_airport_country_iso3'] = $row['departure_airport_country_iso3'];
			$airport_array[] = $temp_array;
		}
		return $airport_array;
	}


    /**
     * Gets all departure airports of the airplanes that have flown over based on an aircraft icao
     *
     * @param $aircraft_icao
     * @param array $filters
     * @return array the airport list
     */
	public function countAllDepartureAirportsByAircraft($aircraft_icao,$filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$aircraft_icao = filter_var($aircraft_icao,FILTER_SANITIZE_STRING);
		$query  = "SELECT DISTINCT spotter_output.departure_airport_icao, COUNT(spotter_output.departure_airport_icao) AS airport_departure_icao_count, spotter_output.departure_airport_name, spotter_output.departure_airport_city, spotter_output.departure_airport_country, airport.latitude, airport.longitude 
			    FROM spotter_output,airport".$filter_query." spotter_output.departure_airport_name <> '' AND spotter_output.departure_airport_icao <> 'NA' AND spotter_output.aircraft_icao = :aircraft_icao AND spotter_output.departure_airport_icao <> '' AND spotter_output.departure_airport_icao = airport.icao 
			    GROUP BY spotter_output.departure_airport_icao, spotter_output.departure_airport_name, spotter_output.departure_airport_city, spotter_output.departure_airport_country, airport.latitude, airport.longitude
			    ORDER BY airport_departure_icao_count DESC";
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':aircraft_icao' => $aircraft_icao));
		$airport_array = array();
		$temp_array = array();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airport_departure_icao'] = $row['departure_airport_icao'];
			$temp_array['airport_departure_icao_count'] = $row['airport_departure_icao_count'];
			$temp_array['airport_departure_name'] = $row['departure_airport_name'];
			$temp_array['airport_departure_city'] = $row['departure_airport_city'];
			$temp_array['airport_departure_country'] = $row['departure_airport_country'];
			$temp_array['airport_departure_latitude'] = $row['latitude'];
			$temp_array['airport_departure_longitude'] = $row['longitude'];
			$airport_array[] = $temp_array;
		}

		return $airport_array;
	}


    /**
     * Gets all departure airports by country of the airplanes that have flown over based on an aircraft icao
     *
     * @param $aircraft_icao
     * @param array $filters
     * @return array the airport list
     */
	public function countAllDepartureAirportCountriesByAircraft($aircraft_icao,$filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$aircraft_icao = filter_var($aircraft_icao,FILTER_SANITIZE_STRING);
		$query  = "SELECT DISTINCT spotter_output.departure_airport_country, COUNT(spotter_output.departure_airport_country) AS airport_departure_country_count, countries.iso3 AS departure_airport_country_iso3 
			FROM countries, spotter_output".$filter_query." countries.name = spotter_output.departure_airport_country AND spotter_output.departure_airport_country <> '' AND spotter_output.aircraft_icao = :aircraft_icao
			GROUP BY spotter_output.departure_airport_country, countries.iso3
			ORDER BY airport_departure_country_count DESC";
      
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':aircraft_icao' => $aircraft_icao));
      
		$airport_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['departure_airport_country'] = $row['departure_airport_country'];
			$temp_array['airport_departure_country_count'] = $row['airport_departure_country_count'];
			$temp_array['departure_airport_country_iso3'] = $row['departure_airport_country_iso3'];
			$airport_array[] = $temp_array;
		}

		return $airport_array;
	}


    /**
     * Gets all departure airports of the airplanes that have flown over based on an aircraft registration
     *
     * @param $registration
     * @param array $filters
     * @return array the airport list
     */
	public function countAllDepartureAirportsByRegistration($registration,$filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$registration = filter_var($registration,FILTER_SANITIZE_STRING);
		$query  = "SELECT DISTINCT spotter_output.departure_airport_icao, COUNT(spotter_output.departure_airport_icao) AS airport_departure_icao_count, spotter_output.departure_airport_name, spotter_output.departure_airport_city, spotter_output.departure_airport_country, airport.latitude, airport.longitude 
			FROM spotter_output,airport".$filter_query." spotter_output.departure_airport_name <> '' AND spotter_output.departure_airport_icao <> 'NA' AND spotter_output.registration = :registration AND spotter_output.departure_airport_icao <> '' AND airport.icao = spotter_output.departure_airport_icao 
			GROUP BY spotter_output.departure_airport_icao, spotter_output.departure_airport_name, spotter_output.departure_airport_city, spotter_output.departure_airport_country, airport.latitude, airport.longitude
			ORDER BY airport_departure_icao_count DESC";
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':registration' => $registration));
		$airport_array = array();
		$temp_array = array();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airport_departure_icao'] = $row['departure_airport_icao'];
			$temp_array['airport_departure_icao_count'] = $row['airport_departure_icao_count'];
			$temp_array['airport_departure_name'] = $row['departure_airport_name'];
			$temp_array['airport_departure_city'] = $row['departure_airport_city'];
			$temp_array['airport_departure_country'] = $row['departure_airport_country'];
			$temp_array['airport_departure_latitude'] = $row['latitude'];
			$temp_array['airport_departure_longitude'] = $row['longitude'];
			$airport_array[] = $temp_array;
		}
		return $airport_array;
	}


    /**
     * Gets all departure airports by country of the airplanes that have flown over based on an aircraft registration
     *
     * @param $registration
     * @param array $filters
     * @return array the airport list
     */
	public function countAllDepartureAirportCountriesByRegistration($registration,$filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$registration = filter_var($registration,FILTER_SANITIZE_STRING);
		$query  = "SELECT DISTINCT spotter_output.departure_airport_country, COUNT(spotter_output.departure_airport_country) AS airport_departure_country_count, countries.iso3 AS departure_airport_country_iso3 
			FROM countries,spotter_output".$filter_query." countries.name = spotter_output.departure_airport_country AND spotter_output.departure_airport_country <> '' AND spotter_output.registration = :registration 
			GROUP BY spotter_output.departure_airport_country, countries.iso3
			ORDER BY airport_departure_country_count DESC";
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':registration' => $registration));
		$airport_array = array();
		$temp_array = array();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['departure_airport_country'] = $row['departure_airport_country'];
			$temp_array['departure_airport_country_iso3'] = $row['departure_airport_country_iso3'];
			$temp_array['airport_departure_country_count'] = $row['airport_departure_country_count'];
			$airport_array[] = $temp_array;
		}
		return $airport_array;
	}
	
	
	/**
	* Gets all departure airports of the airplanes that have flown over based on an arrivl airport icao
	*
	* @return Array the airport list
	*
	*/
	public function countAllDepartureAirportsByAirport($airport_icao,$filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$airport_icao = filter_var($airport_icao,FILTER_SANITIZE_STRING);
		if ($airport_icao == 'NA') return array();
		$query  = "SELECT DISTINCT spotter_output.departure_airport_icao, COUNT(spotter_output.departure_airport_icao) AS airport_departure_icao_count, spotter_output.departure_airport_name, spotter_output.departure_airport_city, spotter_output.departure_airport_country, airport.latitude, airport.longitude 
			    FROM spotter_output,airport".$filter_query." spotter_output.departure_airport_name <> '' AND spotter_output.departure_airport_icao <> 'NA' AND spotter_output.arrival_airport_icao = :airport_icao AND spotter_output.departure_airport_icao <> '' AND airport.icao = spotter_output.departure_airport_icao 
			    GROUP BY spotter_output.departure_airport_icao, spotter_output.departure_airport_name, spotter_output.departure_airport_city, spotter_output.departure_airport_country, airport.latitude, airport.longitude
			    ORDER BY airport_departure_icao_count DESC";
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':airport_icao' => $airport_icao));
		$airport_array = array();
		$temp_array = array();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airport_departure_icao'] = $row['departure_airport_icao'];
			$temp_array['airport_departure_icao_count'] = $row['airport_departure_icao_count'];
			$temp_array['airport_departure_name'] = $row['departure_airport_name'];
			$temp_array['airport_departure_city'] = $row['departure_airport_city'];
			$temp_array['airport_departure_country'] = $row['departure_airport_country'];
			$temp_array['airport_departure_latitude'] = $row['latitude'];
			$temp_array['airport_departure_longitude'] = $row['longitude'];
			$airport_array[] = $temp_array;
		}
		return $airport_array;
	}

    /**
     * Gets all departure airports by country of the airplanes that have flown over based on an airport icao
     *
     * @param $airport_icao
     * @param array $filters
     * @return array the airport list
     */
	public function countAllDepartureAirportCountriesByAirport($airport_icao,$filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$airport_icao = filter_var($airport_icao,FILTER_SANITIZE_STRING);
		$query  = "SELECT DISTINCT spotter_output.departure_airport_country, COUNT(spotter_output.departure_airport_country) AS airport_departure_country_count, countries.iso3 AS departure_airport_country_iso3 
			FROM countries,spotter_output".$filter_query." countries.name = spotter_output.departure_airport_country AND spotter_output.departure_airport_country <> '' AND spotter_output.arrival_airport_icao = :airport_icao 
			GROUP BY spotter_output.departure_airport_country, countries.iso3
			ORDER BY airport_departure_country_count DESC";
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':airport_icao' => $airport_icao));
      
		$airport_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['departure_airport_country'] = $row['departure_airport_country'];
			$temp_array['airport_departure_country_count'] = $row['airport_departure_country_count'];
			$temp_array['departure_airport_country_iso3'] = $row['departure_airport_country_iso3'];
			$airport_array[] = $temp_array;
		}
		return $airport_array;
	}


    /**
     * Gets all departure airports of the airplanes that have flown over based on an aircraft manufacturer
     *
     * @param $aircraft_manufacturer
     * @param array $filters
     * @return array the airport list
     */
	public function countAllDepartureAirportsByManufacturer($aircraft_manufacturer,$filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$aircraft_manufacturer = filter_var($aircraft_manufacturer,FILTER_SANITIZE_STRING);
		$query  = "SELECT DISTINCT spotter_output.departure_airport_icao, COUNT(spotter_output.departure_airport_icao) AS airport_departure_icao_count, spotter_output.departure_airport_name, spotter_output.departure_airport_city, spotter_output.departure_airport_country,airport.latitude, airport.longitude 
			FROM spotter_output,airport".$filter_query." spotter_output.departure_airport_name <> '' AND spotter_output.departure_airport_icao <> 'NA' AND spotter_output.aircraft_manufacturer = :aircraft_manufacturer AND spotter_output.departure_airport_icao <> '' AND airport.icao = spotter_output.departure_airport_icao 
			GROUP BY spotter_output.departure_airport_icao, spotter_output.departure_airport_name, spotter_output.departure_airport_city, spotter_output.departure_airport_country, airport.latitude, airport.longitude 
			ORDER BY airport_departure_icao_count DESC";
		$sth = $this->db->prepare($query);
		$sth->execute(array(':aircraft_manufacturer' => $aircraft_manufacturer));
		$airport_array = array();
		$temp_array = array();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airport_departure_icao'] = $row['departure_airport_icao'];
			$temp_array['airport_departure_icao_count'] = $row['airport_departure_icao_count'];
			$temp_array['airport_departure_name'] = $row['departure_airport_name'];
			$temp_array['airport_departure_city'] = $row['departure_airport_city'];
			$temp_array['airport_departure_country'] = $row['departure_airport_country'];
			$temp_array['airport_departure_latitude'] = $row['latitude'];
			$temp_array['airport_departure_longitude'] = $row['longitude'];
			$airport_array[] = $temp_array;
		}
		return $airport_array;
	}
	
	
	/**
	* Gets all departure airports by country of the airplanes that have flown over based on an aircraft manufacturer
	*
	* @return Array the airport list
	*
	*/
	public function countAllDepartureAirportCountriesByManufacturer($aircraft_manufacturer,$filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$aircraft_manufacturer = filter_var($aircraft_manufacturer,FILTER_SANITIZE_STRING);
		$query  = "SELECT DISTINCT spotter_output.departure_airport_country, COUNT(spotter_output.departure_airport_country) AS airport_departure_country_count, countries.iso3 AS departure_airport_country_iso3 
			FROM countries, spotter_output".$filter_query." countries.name = departure_airport_country AND spotter_output.departure_airport_country <> '' AND spotter_output.aircraft_manufacturer = :aircraft_manufacturer 
			GROUP BY spotter_output.departure_airport_country, countries.iso3
			ORDER BY airport_departure_country_count DESC";
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':aircraft_manufacturer' => $aircraft_manufacturer));
		$airport_array = array();
		$temp_array = array();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['departure_airport_country'] = $row['departure_airport_country'];
			$temp_array['departure_airport_country_iso3'] = $row['departure_airport_country_iso3'];
			$temp_array['airport_departure_country_count'] = $row['airport_departure_country_count'];
			$airport_array[] = $temp_array;
		}
		return $airport_array;
	}


    /**
     * Gets all departure airports of the airplanes that have flown over based on a date
     *
     * @param $date
     * @param array $filters
     * @return array the airport list
     */
	public function countAllDepartureAirportsByDate($date,$filters = array())
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
			$query  = "SELECT DISTINCT spotter_output.departure_airport_icao, COUNT(spotter_output.departure_airport_icao) AS airport_departure_icao_count, spotter_output.departure_airport_name, spotter_output.departure_airport_city, spotter_output.departure_airport_country,airport.latitude,airport.longitude 
					FROM spotter_output,airport".$filter_query." spotter_output.departure_airport_name <> '' AND spotter_output.departure_airport_icao <> 'NA' AND spotter_output.departure_airport_icao <> '' AND DATE(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) = :date AND airport.icao = spotter_output.departure_airport_icao 
					GROUP BY spotter_output.departure_airport_icao, spotter_output.departure_airport_name, spotter_output.departure_airport_city, spotter_output.departure_airport_country, airport.latitude, airport.longitude 
					ORDER BY airport_departure_icao_count DESC";
		} else {
			$query  = "SELECT DISTINCT spotter_output.departure_airport_icao, COUNT(spotter_output.departure_airport_icao) AS airport_departure_icao_count, spotter_output.departure_airport_name, spotter_output.departure_airport_city, spotter_output.departure_airport_country, airport.latitude, airport.longitude 
					FROM spotter_output,airport".$filter_query." spotter_output.departure_airport_name <> '' AND spotter_output.departure_airport_icao <> 'NA' AND spotter_output.departure_airport_icao <> '' AND to_char(spotter_output.date AT TIME ZONE INTERVAL :offset,'YYYY-mm-dd') = :date AND airport.icao = spotter_output.departure_airport_icao
					GROUP BY spotter_output.departure_airport_icao, spotter_output.departure_airport_name, spotter_output.departure_airport_city, spotter_output.departure_airport_country, airport.latitude, airport.longitude 
					ORDER BY airport_departure_icao_count DESC";
		}
		$sth = $this->db->prepare($query);
		$sth->execute(array(':date' => $date, ':offset' => $offset));
		$airport_array = array();
		$temp_array = array();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airport_departure_icao'] = $row['departure_airport_icao'];
			$temp_array['airport_departure_icao_count'] = $row['airport_departure_icao_count'];
			$temp_array['airport_departure_name'] = $row['departure_airport_name'];
			$temp_array['airport_departure_city'] = $row['departure_airport_city'];
			$temp_array['airport_departure_country'] = $row['departure_airport_country'];
			$temp_array['airport_departure_latitude'] = $row['latitude'];
			$temp_array['airport_departure_longitude'] = $row['longitude'];
			$airport_array[] = $temp_array;
		}
		return $airport_array;
	}


    /**
     * Gets all departure airports by country of the airplanes that have flown over based on a date
     *
     * @param $date
     * @param array $filters
     * @return array the airport list
     */
	public function countAllDepartureAirportCountriesByDate($date,$filters = array())
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
			$query  = "SELECT DISTINCT spotter_output.departure_airport_country, COUNT(spotter_output.departure_airport_country) AS airport_departure_country_count,countries.iso3 AS departure_airport_country_iso3
					FROM countries,spotter_output".$filter_query." countries.name = spotter_output.departure_airport_country AND spotter_output.departure_airport_country <> '' AND DATE(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) = :date 
					GROUP BY spotter_output.departure_airport_country, countries.iso3
					ORDER BY airport_departure_country_count DESC";
		} else {
			$query  = "SELECT DISTINCT spotter_output.departure_airport_country, COUNT(spotter_output.departure_airport_country) AS airport_departure_country_count, countries.iso3 AS departure_airport_country_iso3 
					FROM countries, spotter_output".$filter_query." countries.name = spotter_output.departure_airport_country AND spotter_output.departure_airport_country <> '' AND to_char(spotter_output.date AT TIME ZONE INTERVAL :offset,'YYYY-mm-dd') = :date 
					GROUP BY spotter_output.departure_airport_country, countries.iso3
					ORDER BY airport_departure_country_count DESC";
		}
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':date' => $date, ':offset' => $offset));
      
		$airport_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['departure_airport_country'] = $row['departure_airport_country'];
			$temp_array['departure_airport_country_iso3'] = $row['departure_airport_country_iso3'];
			$temp_array['airport_departure_country_count'] = $row['airport_departure_country_count'];
          
			$airport_array[] = $temp_array;
		}
		return $airport_array;
	}


    /**
     * Gets all departure airports of the airplanes that have flown over based on a ident/callsign
     *
     * @param $ident
     * @param array $filters
     * @return array the airport list
     */
	public function countAllDepartureAirportsByIdent($ident,$filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$ident = filter_var($ident,FILTER_SANITIZE_STRING);
		$query  = "SELECT DISTINCT spotter_output.departure_airport_icao, COUNT(spotter_output.departure_airport_icao) AS airport_departure_icao_count, spotter_output.departure_airport_name, spotter_output.departure_airport_city, spotter_output.departure_airport_country,airport.latitude, airport.longitude 
		    FROM spotter_output,airport".$filter_query." spotter_output.departure_airport_name <> '' AND spotter_output.departure_airport_icao <> 'NA' AND spotter_output.departure_airport_icao <> '' AND spotter_output.ident = :ident AND airport.icao = spotter_output.departure_airport_icao 
		    GROUP BY spotter_output.departure_airport_icao, spotter_output.departure_airport_name, spotter_output.departure_airport_city, spotter_output.departure_airport_country, airport.latitude, airport.longitude
		    ORDER BY airport_departure_icao_count DESC";
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':ident' => $ident));
		$airport_array = array();
		$temp_array = array();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airport_departure_icao'] = $row['departure_airport_icao'];
			$temp_array['airport_departure_icao_count'] = $row['airport_departure_icao_count'];
			$temp_array['airport_departure_name'] = $row['departure_airport_name'];
			$temp_array['airport_departure_city'] = $row['departure_airport_city'];
			$temp_array['airport_departure_country'] = $row['departure_airport_country'];
			$temp_array['airport_departure_latitude'] = $row['latitude'];
			$temp_array['airport_departure_longitude'] = $row['longitude'];
			$airport_array[] = $temp_array;
		}
		return $airport_array;
	}

    /**
     * Gets all departure airports of the airplanes that have flown over based on a owner
     *
     * @param $owner
     * @param array $filters
     * @return array the airport list
     */
	public function countAllDepartureAirportsByOwner($owner,$filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$owner = filter_var($owner,FILTER_SANITIZE_STRING);
		$query  = "SELECT DISTINCT spotter_output.departure_airport_icao, COUNT(spotter_output.departure_airport_icao) AS airport_departure_icao_count, spotter_output.departure_airport_name, spotter_output.departure_airport_city, spotter_output.departure_airport_country, airport.latitude, airport.longitude 
		    FROM spotter_output,airport".$filter_query." spotter_output.departure_airport_name <> '' AND spotter_output.departure_airport_icao <> 'NA' AND spotter_output.departure_airport_icao <> '' AND spotter_output.owner_name = :owner AND airport.icao = spotter_output.departure_airport_icao 
		    GROUP BY spotter_output.departure_airport_icao, spotter_output.departure_airport_name, spotter_output.departure_airport_city, spotter_output.departure_airport_country, airport.latitude, airport.longitude
		    ORDER BY airport_departure_icao_count DESC";
		$sth = $this->db->prepare($query);
		$sth->execute(array(':owner' => $owner));
		$airport_array = array();
		$temp_array = array();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airport_departure_icao'] = $row['departure_airport_icao'];
			$temp_array['airport_departure_icao_count'] = $row['airport_departure_icao_count'];
			$temp_array['airport_departure_name'] = $row['departure_airport_name'];
			$temp_array['airport_departure_city'] = $row['departure_airport_city'];
			$temp_array['airport_departure_country'] = $row['departure_airport_country'];
			$temp_array['airport_departure_latitude'] = $row['latitude'];
			$temp_array['airport_departure_longitude'] = $row['longitude'];
			$airport_array[] = $temp_array;
		}
		return $airport_array;
	}

    /**
     * Gets all departure airports of the airplanes that have flown over based on a pilot
     *
     * @param $pilot
     * @param array $filters
     * @return array the airport list
     */
	public function countAllDepartureAirportsByPilot($pilot,$filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$pilot = filter_var($pilot,FILTER_SANITIZE_STRING);
		$query  = "SELECT DISTINCT spotter_output.departure_airport_icao, COUNT(spotter_output.departure_airport_icao) AS airport_departure_icao_count, spotter_output.departure_airport_name, spotter_output.departure_airport_city, spotter_output.departure_airport_country, airport.latitude, airport.longitude 
		    FROM spotter_output,airport".$filter_query." spotter_output.departure_airport_name <> '' AND spotter_output.departure_airport_icao <> 'NA' AND spotter_output.departure_airport_icao <> '' AND (spotter_output.pilot_name = :pilot OR spotter_output.pilot_id = :pilot) AND airport.icao = spotter_output.departure_airport_icao 
		    GROUP BY spotter_output.departure_airport_icao, spotter_output.departure_airport_name, spotter_output.departure_airport_city, spotter_output.departure_airport_country, airport.latitude, airport.longitude
		    ORDER BY airport_departure_icao_count DESC";
		$sth = $this->db->prepare($query);
		$sth->execute(array(':pilot' => $pilot));
		$airport_array = array();
		$temp_array = array();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airport_departure_icao'] = $row['departure_airport_icao'];
			$temp_array['airport_departure_icao_count'] = $row['airport_departure_icao_count'];
			$temp_array['airport_departure_name'] = $row['departure_airport_name'];
			$temp_array['airport_departure_city'] = $row['departure_airport_city'];
			$temp_array['airport_departure_country'] = $row['departure_airport_country'];
			$temp_array['airport_departure_latitude'] = $row['latitude'];
			$temp_array['airport_departure_longitude'] = $row['longitude'];
			$airport_array[] = $temp_array;
		}
		return $airport_array;
	}


    /**
     * Gets all departure airports by country of the airplanes that have flown over based on a callsign/ident
     *
     * @param $ident
     * @param array $filters
     * @return array the airport list
     */
	public function countAllDepartureAirportCountriesByIdent($ident,$filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$ident = filter_var($ident,FILTER_SANITIZE_STRING);
		$query  = "SELECT DISTINCT spotter_output.departure_airport_country, COUNT(spotter_output.departure_airport_country) AS airport_departure_country_count, countries.iso3 AS departure_airport_country_iso3 
			FROM countries,spotter_output".$filter_query." countries.name = spotter_output.departure_airport_country AND spotter_output.departure_airport_country <> '' AND spotter_output.ident = :ident 
			GROUP BY spotter_output.departure_airport_country, countries.iso3
			ORDER BY airport_departure_country_count DESC";
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':ident' => $ident));
		$airport_array = array();
		$temp_array = array();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['departure_airport_country'] = $row['departure_airport_country'];
			$temp_array['departure_airport_country_iso3'] = $row['departure_airport_country_iso3'];
			$temp_array['airport_departure_country_count'] = $row['airport_departure_country_count'];
			$airport_array[] = $temp_array;
		}
		return $airport_array;
	}

    /**
     * Gets all departure airports by country of the airplanes that have flown over based on owner
     *
     * @param $owner
     * @param array $filters
     * @return array the airport list
     */
	public function countAllDepartureAirportCountriesByOwner($owner,$filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$owner = filter_var($owner,FILTER_SANITIZE_STRING);
		$query  = "SELECT DISTINCT spotter_output.departure_airport_country, COUNT(spotter_output.departure_airport_country) AS airport_departure_country_count, countries.iso3 AS airport_departure_country_iso3
			FROM spotter_output,countries".$filter_query." spotter_output.departure_airport_country <> '' AND spotter_output.owner_name = :owner  AND countries.name = spotter_output.departure_airport_country 
			GROUP BY spotter_output.departure_airport_country, countries.iso3
			ORDER BY airport_departure_country_count DESC";
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':owner' => $owner));
		return $sth->fetchAll(PDO::FETCH_ASSOC);
	}

    /**
     * Gets all departure airports by country of the airplanes that have flown over based on pilot
     *
     * @param $pilot
     * @param array $filters
     * @return array the airport list
     */
	public function countAllDepartureAirportCountriesByPilot($pilot,$filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$pilot = filter_var($pilot,FILTER_SANITIZE_STRING);
		$query  = "SELECT DISTINCT spotter_output.departure_airport_country, COUNT(spotter_output.departure_airport_country) AS airport_departure_country_count, countries.iso3 AS departure_airport_country_iso3 
			FROM spotter_output,countries".$filter_query." spotter_output.departure_airport_country <> '' AND (spotter_output.pilot_name = :pilot OR spotter_output.pilot_id = :pilot) AND countries.name = spotter_output.departure_airport_country 
			GROUP BY spotter_output.departure_airport_country, countries.iso3
			ORDER BY airport_departure_country_count DESC";
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':pilot' => $pilot));
		return $sth->fetchAll(PDO::FETCH_ASSOC);
	}


    /**
     * Gets all departure airports of the airplanes that have flown over based on a country
     *
     * @param $country
     * @param array $filters
     * @return array the airport list
     */
	public function countAllDepartureAirportsByCountry($country,$filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$country = filter_var($country,FILTER_SANITIZE_STRING);
		$query  = "SELECT DISTINCT spotter_output.departure_airport_icao, COUNT(spotter_output.departure_airport_icao) AS airport_departure_icao_count, spotter_output.departure_airport_name, spotter_output.departure_airport_city, spotter_output.departure_airport_country, airport.latitude, airport.longitude 
			    FROM spotter_output,airport".$filter_query." (((spotter_output.departure_airport_country = :country) OR (spotter_output.arrival_airport_country = :country)) OR spotter_output.airline_country = :country) AND airport.icao = spotter_output.departure_airport_icao 
			    GROUP BY spotter_output.departure_airport_icao, spotter_output.departure_airport_name, spotter_output.departure_airport_city, spotter_output.departure_airport_country, airport.latitude, airport.longitude 
			    ORDER BY airport_departure_icao_count DESC";
		$sth = $this->db->prepare($query);
		$sth->execute(array(':country' => $country));
		$airport_array = array();
		$temp_array = array();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airport_departure_icao'] = $row['departure_airport_icao'];
			$temp_array['airport_departure_icao_count'] = $row['airport_departure_icao_count'];
			$temp_array['airport_departure_name'] = $row['departure_airport_name'];
			$temp_array['airport_departure_city'] = $row['departure_airport_city'];
			$temp_array['airport_departure_country'] = $row['departure_airport_country'];
			$temp_array['airport_departure_latitude'] = $row['latitude'];
			$temp_array['airport_departure_longitude'] = $row['longitude'];
			$airport_array[] = $temp_array;
		}
		return $airport_array;
	}


    /**
     * Gets all departure airports by country of the airplanes that have flown over based on an aircraft icao
     *
     * @param $country
     * @param array $filters
     * @return array the airport list
     */
	public function countAllDepartureAirportCountriesByCountry($country,$filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$country = filter_var($country,FILTER_SANITIZE_STRING);
		$query  = "SELECT DISTINCT spotter_output.departure_airport_country, COUNT(spotter_output.departure_airport_country) AS airport_departure_country_count, countries.iso3 AS departure_airport_country_iso3 
			FROM countries,spotter_output".$filter_query." countries.name = spotter_output.departure_airport_country AND spotter_output.departure_airport_country <> '' AND ((spotter_output.departure_airport_country = :country) OR (spotter_output.arrival_airport_country = :country) OR spotter_output.airline_country = :country) 
			GROUP BY spotter_output.departure_airport_country, countries.iso3
			ORDER BY airport_departure_country_count DESC";
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':country' => $country));
		$airport_array = array();
		$temp_array = array();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['departure_airport_country'] = $row['departure_airport_country'];
			$temp_array['departure_airport_country_iso3'] = $row['departure_airport_country_iso3'];
			$temp_array['airport_departure_country_count'] = $row['airport_departure_country_count'];
			$airport_array[] = $temp_array;
		}
		return $airport_array;
	}
	

	/**
	* Gets all arrival airports of the airplanes that have flown over
	*
	* @param Boolean $limit Limit result to 10 or not
	* @param Integer $olderthanmonths Only show result older than x months
	* @param String $sincedate Only show result since x date
	* @param Boolean $icaoaskey Show result by ICAO
	* @param array $filters Filter used here
	* @return array the airport list
	*
	*/
	public function countAllArrivalAirports($limit = true, $olderthanmonths = 0, $sincedate = '', $icaoaskey = false,$filters = array(),$year = '',$month = '',$day = '')
	{
		global $globalDBdriver;
		$filter_query = $this->getFilter($filters,true,true);
		$query  = "SELECT DISTINCT spotter_output.arrival_airport_icao, COUNT(spotter_output.arrival_airport_icao) AS airport_arrival_icao_count, spotter_output.arrival_airport_name, spotter_output.arrival_airport_city, spotter_output.arrival_airport_country, airport.latitude as arrival_airport_latitude, airport.longitude as arrival_airport_longitude 
				FROM airport, spotter_output".$filter_query." airport.icao = spotter_output.arrival_airport_icao AND spotter_output.arrival_airport_name <> '' AND spotter_output.arrival_airport_icao <> 'NA' AND spotter_output.arrival_airport_icao <> ''";
                if ($olderthanmonths > 0) {
            		if ($globalDBdriver == 'mysql') {
				$query .= ' AND spotter_output.date < DATE_SUB(UTC_TIMESTAMP(), INTERVAL '.$olderthanmonths.' MONTH)';
			} else {
				$query .= " AND spotter_output.date < CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$olderthanmonths." MONTHS'";
			}
		}
                if ($sincedate != '') {
            		if ($globalDBdriver == 'mysql') {
				$query .= " AND spotter_output.date > '".$sincedate."'";
			} else {
				$query .= " AND spotter_output.date > CAST('".$sincedate."' AS TIMESTAMP)";
			}
		}
		$query_values = array();
		if ($year != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND YEAR(spotter_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			} else {
				$query .= " AND EXTRACT(YEAR FROM spotter_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			}
		}
		if ($month != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND MONTH(spotter_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			} else {
				$query .= " AND EXTRACT(MONTH FROM spotter_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			}
		}
		if ($day != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND DAY(spotter_output.date) = :day";
				$query_values = array_merge($query_values,array(':day' => $day));
			} else {
				$query .= " AND EXTRACT(DAY FROM spotter_output.date) = :day";
				$query_values = array_merge($query_values,array(':day' => $day));
			}
		}
                $query .= " GROUP BY spotter_output.arrival_airport_icao, spotter_output.arrival_airport_name, spotter_output.arrival_airport_city, spotter_output.arrival_airport_country, airport.latitude, airport.longitude
					ORDER BY airport_arrival_icao_count DESC";
		if ($limit) $query .= " LIMIT 10";
      
		
		$sth = $this->db->prepare($query);
		$sth->execute($query_values);
      
		$airport_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airport_arrival_icao'] = $row['arrival_airport_icao'];
			$temp_array['airport_arrival_icao_count'] = $row['airport_arrival_icao_count'];
			$temp_array['airport_arrival_name'] = $row['arrival_airport_name'];
			$temp_array['airport_arrival_city'] = $row['arrival_airport_city'];
			$temp_array['airport_arrival_country'] = $row['arrival_airport_country'];
			$temp_array['airport_arrival_latitude'] = $row['arrival_airport_latitude'];
			$temp_array['airport_arrival_longitude'] = $row['arrival_airport_longitude'];
          
			if ($icaoaskey) {
				$icao = $row['arrival_airport_icao'];
				$airport_array[$icao] = $temp_array;
			} else $airport_array[] = $temp_array;
		}

		return $airport_array;
	}

    /**
     * Gets all arrival airports of the airplanes that have flown over
     *
     * @param bool $limit
     * @param int $olderthanmonths
     * @param string $sincedate
     * @param bool $icaoaskey
     * @param array $filters
     * @return array the airport list
     */
	public function countAllArrivalAirportsByAirlines($limit = true, $olderthanmonths = 0, $sincedate = '', $icaoaskey = false,$filters = array())
	{
		global $globalDBdriver;
		$filter_query = $this->getFilter($filters,true,true);
		$query  = "SELECT DISTINCT spotter_output.airline_icao, spotter_output.arrival_airport_icao, COUNT(spotter_output.arrival_airport_icao) AS airport_arrival_icao_count, spotter_output.arrival_airport_name, spotter_output.arrival_airport_city, spotter_output.arrival_airport_country, countries.iso3 AS arrival_airport_country_iso3, airport.latitude, airport.longitude 
			FROM countries,spotter_output,airport".$filter_query." countries.name = spotter_output.arrival_airport_country AND spotter_output.airline_icao <> '' AND spotter_output.arrival_airport_name <> '' AND spotter_output.arrival_airport_icao <> 'NA' AND spotter_output.arrival_airport_icao <> '' AND airport.icao = spotter_output.arrival_airport_icao ";
                if ($olderthanmonths > 0) {
            		if ($globalDBdriver == 'mysql') {
				$query .= 'AND spotter_output.date < DATE_SUB(UTC_TIMESTAMP(), INTERVAL '.$olderthanmonths.' MONTH) ';
			} else {
				$query .= "AND spotter_output.date < CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$olderthanmonths." MONTHS' ";
			}
		}
                if ($sincedate != '') {
            		if ($globalDBdriver == 'mysql') {
				$query .= "AND spotter_output.date > '".$sincedate."' ";
			} else {
				$query .= "AND spotter_output.date > CAST('".$sincedate."' AS TIMESTAMP)";
			}
		}

            	//if ($olderthanmonths > 0) $query .= 'AND date < DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.$olderthanmonths.' MONTH) ';
                //if ($sincedate != '') $query .= "AND date > '".$sincedate."' ";
                $query .= "GROUP BY spotter_output.airline_icao,spotter_output.arrival_airport_icao, spotter_output.arrival_airport_name, spotter_output.arrival_airport_city, spotter_output.arrival_airport_country, countries.iso3, airport.latitude, airport.longitude
					ORDER BY airport_arrival_icao_count DESC";
		if ($limit) $query .= " LIMIT 10";
      
		
		$sth = $this->db->prepare($query);
		$sth->execute();
      
		$airport_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airline_icao'] = $row['airline_icao'];
			$temp_array['airport_arrival_icao'] = $row['arrival_airport_icao'];
			$temp_array['airport_arrival_icao_count'] = $row['airport_arrival_icao_count'];
			$temp_array['airport_arrival_name'] = $row['arrival_airport_name'];
			$temp_array['airport_arrival_city'] = $row['arrival_airport_city'];
			$temp_array['airport_arrival_country'] = $row['arrival_airport_country'];
			$temp_array['airport_arrival_country_iso3'] = $row['arrival_airport_country_iso3'];
			$temp_array['airport_arrival_latitude'] = $row['latitude'];
			$temp_array['airport_arrival_longitude'] = $row['longitude'];
          
			if ($icaoaskey) {
				$icao = $row['arrival_airport_icao'];
				$airport_array[$icao] = $temp_array;
			} else $airport_array[] = $temp_array;
		}

		return $airport_array;
	}


    /**
     * Gets all detected arrival airports of the airplanes that have flown over
     *
     * @param bool $limit
     * @param int $olderthanmonths
     * @param string $sincedate
     * @param bool $icaoaskey
     * @param array $filters
     * @param string $year
     * @param string $month
     * @param string $day
     * @return array the airport list
     */
	public function countAllDetectedArrivalAirports($limit = true, $olderthanmonths = 0, $sincedate = '',$icaoaskey = false,$filters = array(),$year = '',$month = '',$day = '')
	{
		global $globalDBdriver;
		$filter_query = $this->getFilter($filters,true,true);
		$query  = "SELECT DISTINCT spotter_output.real_arrival_airport_icao as arrival_airport_icao, COUNT(spotter_output.real_arrival_airport_icao) AS airport_arrival_icao_count, airport.name AS arrival_airport_name, airport.city AS arrival_airport_city, airport.country AS arrival_airport_country, airport.latitude AS arrival_airport_latitude, airport.longitude AS arrival_airport_longitude 
			FROM airport,spotter_output".$filter_query." spotter_output.real_arrival_airport_icao <> '' AND spotter_output.real_arrival_airport_icao <> 'NA' AND airport.icao = spotter_output.real_arrival_airport_icao";
                if ($olderthanmonths > 0) {
            		if ($globalDBdriver == 'mysql') {
				$query .= ' AND spotter_output.date < DATE_SUB(UTC_TIMESTAMP(), INTERVAL '.$olderthanmonths.' MONTH)';
			} else {
				$query .= " AND spotter_output.date < CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$olderthanmonths." MONTHS'";
			}
		}
                if ($sincedate != '') {
            		if ($globalDBdriver == 'mysql') {
				$query .= " AND spotter_output.date > '".$sincedate."'";
			} else {
				$query .= " AND spotter_output.date > CAST('".$sincedate."' AS TIMESTAMP)";
			}
		}
		$query_values = array();
		if ($year != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND YEAR(spotter_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			} else {
				$query .= " AND EXTRACT(YEAR FROM spotter_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			}
		}
		if ($month != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND MONTH(spotter_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			} else {
				$query .= " AND EXTRACT(MONTH FROM spotter_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			}
		}
		if ($day != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND DAY(spotter_output.date) = :day";
				$query_values = array_merge($query_values,array(':day' => $day));
			} else {
				$query .= " AND EXTRACT(DAY FROM spotter_output.date) = :day";
				$query_values = array_merge($query_values,array(':day' => $day));
			}
		}
		$query .= " GROUP BY spotter_output.real_arrival_airport_icao, airport.name, airport.city, airport.country, airport.latitude, airport.longitude
					ORDER BY airport_arrival_icao_count DESC";
		if ($limit) $query .= " LIMIT 10";
      
		
		$sth = $this->db->prepare($query);
		$sth->execute($query_values);
      
		$airport_array = array();
		$temp_array = array();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airport_arrival_icao'] = $row['arrival_airport_icao'];
			$temp_array['airport_arrival_icao_count'] = $row['airport_arrival_icao_count'];
			$temp_array['airport_arrival_name'] = $row['arrival_airport_name'];
			$temp_array['airport_arrival_city'] = $row['arrival_airport_city'];
			$temp_array['airport_arrival_country'] = $row['arrival_airport_country'];
          
			if ($icaoaskey) {
				$icao = $row['arrival_airport_icao'];
				$airport_array[$icao] = $temp_array;
			} else $airport_array[] = $temp_array;
		}

		return $airport_array;
	}

    /**
     * Gets all detected arrival airports of the airplanes that have flown over
     *
     * @param bool $limit
     * @param int $olderthanmonths
     * @param string $sincedate
     * @param bool $icaoaskey
     * @param array $filters
     * @return array the airport list
     */
	public function countAllDetectedArrivalAirportsByAirlines($limit = true, $olderthanmonths = 0, $sincedate = '',$icaoaskey = false,$filters = array())
	{
		global $globalDBdriver;
		$filter_query = $this->getFilter($filters,true,true);
		$query  = "SELECT DISTINCT spotter_output.airline_icao, spotter_output.real_arrival_airport_icao as arrival_airport_icao, COUNT(spotter_output.real_arrival_airport_icao) AS airport_arrival_icao_count, airport.name AS arrival_airport_name, airport.city AS arrival_airport_city, airport.country AS arrival_airport_country 
			FROM airport,spotter_output".$filter_query." spotter_output.airline_icao <> '' AND spotter_output.real_arrival_airport_icao <> '' AND spotter_output.real_arrival_airport_icao <> 'NA' AND airport.icao = spotter_output.real_arrival_airport_icao ";
                if ($olderthanmonths > 0) {
            		if ($globalDBdriver == 'mysql') {
				$query .= 'AND spotter_output.date < DATE_SUB(UTC_TIMESTAMP(), INTERVAL '.$olderthanmonths.' MONTH) ';
			} else {
				$query .= "AND spotter_output.date < CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$olderthanmonths." MONTHS' ";
			}
		}
                if ($sincedate != '') {
            		if ($globalDBdriver == 'mysql') {
				$query .= "AND spotter_output.date > '".$sincedate."' ";
			} else {
				$query .= "AND spotter_output.date > CAST('".$sincedate."' AS TIMESTAMP)";
			}
		}

            	//if ($olderthanmonths > 0) $query .= 'AND date < DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.$olderthanmonths.' MONTH) ';
                //if ($sincedate != '') $query .= "AND date > '".$sincedate."' ";
                $query .= "GROUP BY spotter_output.airline_icao, spotter_output.real_arrival_airport_icao, airport.name, airport.city, airport.country
					ORDER BY airport_arrival_icao_count DESC";
		if ($limit) $query .= " LIMIT 10";
      
		
		$sth = $this->db->prepare($query);
		$sth->execute();
      
		$airport_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airport_arrival_icao'] = $row['arrival_airport_icao'];
			$temp_array['airport_arrival_icao_count'] = $row['airport_arrival_icao_count'];
			$temp_array['airport_arrival_name'] = $row['arrival_airport_name'];
			$temp_array['airport_arrival_city'] = $row['arrival_airport_city'];
			$temp_array['airport_arrival_country'] = $row['arrival_airport_country'];
			$temp_array['airline_icao'] = $row['airline_icao'];
          
			if ($icaoaskey) {
				$icao = $row['arrival_airport_icao'];
				$airport_array[$icao] = $temp_array;
			} else $airport_array[] = $temp_array;
		}

		return $airport_array;
	}

    /**
     * Gets all arrival airports of the airplanes that have flown over based on an airline icao
     *
     * @param $airline_icao
     * @param array $filters
     * @return array the airport list
     */
	public function countAllArrivalAirportsByAirline($airline_icao, $filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$airline_icao = filter_var($airline_icao,FILTER_SANITIZE_STRING);
		$query  = "SELECT DISTINCT spotter_output.arrival_airport_icao, COUNT(spotter_output.arrival_airport_icao) AS airport_arrival_icao_count, spotter_output.arrival_airport_name, spotter_output.arrival_airport_city, spotter_output.arrival_airport_country, airport.latitude, airport.longitude 
			    FROM airport,spotter_output".$filter_query." spotter_output.arrival_airport_name <> '' AND spotter_output.arrival_airport_icao <> 'NA' AND spotter_output.arrival_airport_icao <> '' ";
		if ($airline_icao != '') $query .= "AND spotter_output.airline_icao = :airline_icao ";
		$query .= "AND airport.icao = spotter_output.arrival_airport_icao 
			    GROUP BY spotter_output.arrival_airport_icao, spotter_output.arrival_airport_name, spotter_output.arrival_airport_city, spotter_output.arrival_airport_country, airport.latitude, airport.longitude 
			    ORDER BY airport_arrival_icao_count DESC";
		
		$sth = $this->db->prepare($query);
		if ($airline_icao != '') $sth->execute(array(':airline_icao' => $airline_icao));
		else $sth->execute();
                
		$airport_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airport_arrival_icao'] = $row['arrival_airport_icao'];
			$temp_array['airport_arrival_icao_count'] = $row['airport_arrival_icao_count'];
			$temp_array['airport_arrival_name'] = $row['arrival_airport_name'];
			$temp_array['airport_arrival_city'] = $row['arrival_airport_city'];
			$temp_array['airport_arrival_country'] = $row['arrival_airport_country'];
			$temp_array['airport_arrival_latitude'] = $row['latitude'];
			$temp_array['airport_arrival_longitude'] = $row['longitude'];
          
			$airport_array[] = $temp_array;
		}

		return $airport_array;
	}


    /**
     * Gets all arrival airports by country of the airplanes that have flown over based on an airline icao
     *
     * @param $airline_icao
     * @param array $filters
     * @return array the airport list
     */
	public function countAllArrivalAirportCountriesByAirline($airline_icao,$filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$airline_icao = filter_var($airline_icao,FILTER_SANITIZE_STRING);
		
		$query  = "SELECT DISTINCT spotter_output.arrival_airport_country, COUNT(spotter_output.arrival_airport_country) AS airport_arrival_country_count, countries.iso3 AS arrival_airport_country_iso3 
			FROM countries, spotter_output".$filter_query." countries.name = spotter_output.arrival_airport_country AND spotter_output.arrival_airport_country <> '' ";
		if ($airline_icao != '') $query .= "AND spotter_output.airline_icao = :airline_icao ";
		$query .= "GROUP BY spotter_output.arrival_airport_country, countries.iso3
			ORDER BY airport_arrival_country_count DESC";
		
		$sth = $this->db->prepare($query);
		if ($airline_icao != '') $sth->execute(array(':airline_icao' => $airline_icao));
		else $sth->execute();
                
		$airport_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['arrival_airport_country'] = $row['arrival_airport_country'];
			$temp_array['airport_arrival_country_count'] = $row['airport_arrival_country_count'];
			$temp_array['arrival_airport_country_iso3'] = $row['arrival_airport_country_iso3'];
			$airport_array[] = $temp_array;
		}
		return $airport_array;
	}


    /**
     * Gets all arrival airports of the airplanes that have flown over based on an aircraft icao
     *
     * @param $aircraft_icao
     * @param array $filters
     * @return array the airport list
     */
	public function countAllArrivalAirportsByAircraft($aircraft_icao,$filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$aircraft_icao = filter_var($aircraft_icao,FILTER_SANITIZE_STRING);
		$query  = "SELECT DISTINCT spotter_output.arrival_airport_icao, COUNT(spotter_output.arrival_airport_icao) AS airport_arrival_icao_count, spotter_output.arrival_airport_name, spotter_output.arrival_airport_city, spotter_output.arrival_airport_country, airport.latitude, airport.longitude 
			    FROM spotter_output, airport".$filter_query." spotter_output.arrival_airport_name <> '' AND spotter_output.arrival_airport_icao <> 'NA' AND spotter_output.arrival_airport_icao <> '' AND spotter_output.aircraft_icao = :aircraft_icao AND airport.icao=spotter_output.arrival_airport_icao 
			    GROUP BY spotter_output.arrival_airport_icao, spotter_output.arrival_airport_name, spotter_output.arrival_airport_city, spotter_output.arrival_airport_country, airport.latitude, airport.longitude
			    ORDER BY airport_arrival_icao_count DESC";
      
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':aircraft_icao' => $aircraft_icao));
      
		$airport_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airport_arrival_icao'] = $row['arrival_airport_icao'];
			$temp_array['airport_arrival_icao_count'] = $row['airport_arrival_icao_count'];
			$temp_array['airport_arrival_name'] = $row['arrival_airport_name'];
			$temp_array['airport_arrival_city'] = $row['arrival_airport_city'];
			$temp_array['airport_arrival_country'] = $row['arrival_airport_country'];
			$temp_array['airport_arrival_latitude'] = $row['latitude'];
			$temp_array['airport_arrival_longitude'] = $row['longitude'];
			$airport_array[] = $temp_array;
		}

		return $airport_array;
	}


    /**
     * Gets all arrival airports by country of the airplanes that have flown over based on an aircraft icao
     *
     * @param $aircraft_icao
     * @param array $filters
     * @return array the airport list
     */
	public function countAllArrivalAirportCountriesByAircraft($aircraft_icao,$filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$aircraft_icao = filter_var($aircraft_icao,FILTER_SANITIZE_STRING);
		$query  = "SELECT DISTINCT spotter_output.arrival_airport_country, COUNT(spotter_output.arrival_airport_country) AS airport_arrival_country_count, countries.iso3 AS airport_arrival_country_iso3 
			    FROM countries, spotter_output".$filter_query." countries.name = spotter_output.arrival_airport_country AND spotter_output.arrival_airport_country <> '' AND spotter_output.aircraft_icao = :aircraft_icao
			    GROUP BY spotter_output.arrival_airport_country, countries.iso3
			    ORDER BY airport_arrival_country_count DESC";
      
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':aircraft_icao' => $aircraft_icao));
      
		$airport_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['arrival_airport_country'] = $row['arrival_airport_country'];
			$temp_array['airport_arrival_country_count'] = $row['airport_arrival_country_count'];
			$temp_array['arrival_airport_country_iso3'] = $row['airport_arrival_country_iso3'];
			$airport_array[] = $temp_array;
		}

		return $airport_array;
	}


    /**
     * Gets all arrival airports of the airplanes that have flown over based on an aircraft registration
     *
     * @param $registration
     * @param array $filters
     * @return array the airport list
     */
	public function countAllArrivalAirportsByRegistration($registration,$filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$registration = filter_var($registration,FILTER_SANITIZE_STRING);

		$query  = "SELECT DISTINCT spotter_output.arrival_airport_icao, COUNT(spotter_output.arrival_airport_icao) AS airport_arrival_icao_count, spotter_output.arrival_airport_name, spotter_output.arrival_airport_city, spotter_output.arrival_airport_country, airport.latitude, airport.longitude 
			FROM spotter_output,airport".$filter_query." spotter_output.arrival_airport_name <> '' AND spotter_output.arrival_airport_icao <> 'NA' AND spotter_output.arrival_airport_icao <> '' AND spotter_output.registration = :registration AND airport.icao = spotter_output.arrival_airport_icao 
			GROUP BY spotter_output.arrival_airport_icao, spotter_output.arrival_airport_name, spotter_output.arrival_airport_city, spotter_output.arrival_airport_country, airport.latitude, airport.longitude
			ORDER BY airport_arrival_icao_count DESC";
		$sth = $this->db->prepare($query);
		$sth->execute(array(':registration' => $registration));
		$airport_array = array();
		$temp_array = array();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airport_arrival_icao'] = $row['arrival_airport_icao'];
			$temp_array['airport_arrival_icao_count'] = $row['airport_arrival_icao_count'];
			$temp_array['airport_arrival_name'] = $row['arrival_airport_name'];
			$temp_array['airport_arrival_city'] = $row['arrival_airport_city'];
			$temp_array['airport_arrival_country'] = $row['arrival_airport_country'];
			$temp_array['airport_arrival_latitude'] = $row['latitude'];
			$temp_array['airport_arrival_longitude'] = $row['longitude'];
			$airport_array[] = $temp_array;
		}
		return $airport_array;
	}


    /**
     * Gets all arrival airports by country of the airplanes that have flown over based on an aircraft registration
     *
     * @param $registration
     * @param array $filters
     * @return array the airport list
     */
	public function countAllArrivalAirportCountriesByRegistration($registration,$filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$registration = filter_var($registration,FILTER_SANITIZE_STRING);
		$query  = "SELECT DISTINCT spotter_output.arrival_airport_country, COUNT(spotter_output.arrival_airport_country) AS airport_arrival_country_count, countries.iso3 AS arrival_airport_country_iso3 
			FROM countries,spotter_output".$filter_query." countries.name = spotter_output.arrival_airport_country AND spotter_output.arrival_airport_country <> '' AND spotter_output.registration = :registration 
			GROUP BY spotter_output.arrival_airport_country, countries.iso3
			ORDER BY airport_arrival_country_count DESC";
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':registration' => $registration));
		$airport_array = array();
		$temp_array = array();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['arrival_airport_country'] = $row['arrival_airport_country'];
			$temp_array['arrival_airport_country_iso3'] = $row['arrival_airport_country_iso3'];
			$temp_array['airport_arrival_country_count'] = $row['airport_arrival_country_count'];
			$airport_array[] = $temp_array;
		}
		return $airport_array;
	}


    /**
     * Gets all arrival airports of the airplanes that have flown over based on an departure airport
     *
     * @param $airport_icao
     * @param array $filters
     * @return array the airport list
     */
	public function countAllArrivalAirportsByAirport($airport_icao,$filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$airport_icao = filter_var($airport_icao,FILTER_SANITIZE_STRING);
		$query  = "SELECT DISTINCT spotter_output.arrival_airport_icao, COUNT(spotter_output.arrival_airport_icao) AS airport_arrival_icao_count, spotter_output.arrival_airport_name, spotter_output.arrival_airport_city, spotter_output.arrival_airport_country, airport.latitude, airport.longitude 
			    FROM spotter_output,airport".$filter_query." spotter_output.arrival_airport_name <> '' AND spotter_output.arrival_airport_icao <> 'NA' AND spotter_output.arrival_airport_icao <> '' AND spotter_output.departure_airport_icao = :airport_icao AND airport.icao = spotter_output.arrival_airport_icao 
			    GROUP BY spotter_output.arrival_airport_icao, spotter_output.arrival_airport_name, spotter_output.arrival_airport_city, spotter_output.arrival_airport_country, airport.latitude, airport.longitude 
			    ORDER BY airport_arrival_icao_count DESC";
		$sth = $this->db->prepare($query);
		$sth->execute(array(':airport_icao' => $airport_icao));
		$airport_array = array();
		$temp_array = array();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airport_arrival_icao'] = $row['arrival_airport_icao'];
			$temp_array['airport_arrival_icao_count'] = $row['airport_arrival_icao_count'];
			$temp_array['airport_arrival_name'] = $row['arrival_airport_name'];
			$temp_array['airport_arrival_city'] = $row['arrival_airport_city'];
			$temp_array['airport_arrival_country'] = $row['arrival_airport_country'];
			$temp_array['airport_arrival_latitude'] = $row['latitude'];
			$temp_array['airport_arrival_longitude'] = $row['longitude'];
			$airport_array[] = $temp_array;
		}
		return $airport_array;
	}


    /**
     * Gets all arrival airports by country of the airplanes that have flown over based on an airport icao
     *
     * @param $airport_icao
     * @param array $filters
     * @return array the airport list
     */
	public function countAllArrivalAirportCountriesByAirport($airport_icao,$filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$airport_icao = filter_var($airport_icao,FILTER_SANITIZE_STRING);
		$query  = "SELECT DISTINCT spotter_output.arrival_airport_country, COUNT(spotter_output.arrival_airport_country) AS airport_arrival_country_count, countries.iso3 AS arrival_airport_country_iso3 
			FROM countries, spotter_output".$filter_query." countries.name = spotter_output.arrival_airport_country AND spotter_output.arrival_airport_country <> '' AND spotter_output.departure_airport_icao = :airport_icao 
			GROUP BY spotter_output.arrival_airport_country, countries.iso3
			ORDER BY airport_arrival_country_count DESC";
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':airport_icao' => $airport_icao));
      
		$airport_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['arrival_airport_country'] = $row['arrival_airport_country'];
			$temp_array['airport_arrival_country_count'] = $row['airport_arrival_country_count'];
			$temp_array['arrival_airport_country_iso3'] = $row['arrival_airport_country_iso3'];
			$airport_array[] = $temp_array;
		}
		return $airport_array;
	}


    /**
     * Gets all arrival airports of the airplanes that have flown over based on a aircraft manufacturer
     *
     * @param $aircraft_manufacturer
     * @param array $filters
     * @return array the airport list
     */
	public function countAllArrivalAirportsByManufacturer($aircraft_manufacturer,$filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$aircraft_manufacturer = filter_var($aircraft_manufacturer,FILTER_SANITIZE_STRING);
		$query  = "SELECT DISTINCT spotter_output.arrival_airport_icao, COUNT(spotter_output.arrival_airport_icao) AS airport_arrival_icao_count, spotter_output.arrival_airport_name, spotter_output.arrival_airport_city, spotter_output.arrival_airport_country, airport.latitude, airport.longitude 
			FROM spotter_output,airport".$filter_query." spotter_output.arrival_airport_name <> '' AND spotter_output.arrival_airport_icao <> 'NA' AND spotter_output.arrival_airport_icao <> '' AND spotter_output.aircraft_manufacturer = :aircraft_manufacturer AND airport.icao = spotter_output.arrival_airport_icao 
			GROUP BY spotter_output.arrival_airport_icao, spotter_output.arrival_airport_name, spotter_output.arrival_airport_city, spotter_output.arrival_airport_country, airport.latitude, airport.longitude
			ORDER BY airport_arrival_icao_count DESC";
		$sth = $this->db->prepare($query);
		$sth->execute(array(':aircraft_manufacturer' => $aircraft_manufacturer));
		$airport_array = array();
		$temp_array = array();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airport_arrival_icao'] = $row['arrival_airport_icao'];
			$temp_array['airport_arrival_icao_count'] = $row['airport_arrival_icao_count'];
			$temp_array['airport_arrival_name'] = $row['arrival_airport_name'];
			$temp_array['airport_arrival_city'] = $row['arrival_airport_city'];
			$temp_array['airport_arrival_country'] = $row['arrival_airport_country'];
			$temp_array['airport_arrival_latitude'] = $row['latitude'];
			$temp_array['airport_arrival_longitude'] = $row['longitude'];
			$airport_array[] = $temp_array;
		}
		return $airport_array;
	}


    /**
     * Gets all arrival airports by country of the airplanes that have flown over based on a aircraft manufacturer
     *
     * @param $aircraft_manufacturer
     * @param array $filters
     * @return array the airport list
     */
	public function countAllArrivalAirportCountriesByManufacturer($aircraft_manufacturer,$filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$aircraft_manufacturer = filter_var($aircraft_manufacturer,FILTER_SANITIZE_STRING);
		$query  = "SELECT DISTINCT spotter_output.arrival_airport_country, COUNT(spotter_output.arrival_airport_country) AS airport_arrival_country_count, countries.iso3 AS arrival_airport_country_iso3 
			FROM countries,spotter_output".$filter_query." countries.name = spotter_output.arrival_airport_country AND spotter_output.arrival_airport_country <> '' AND spotter_output.aircraft_manufacturer = :aircraft_manufacturer 
			GROUP BY spotter_output.arrival_airport_country, countries.iso3
			ORDER BY airport_arrival_country_count DESC";
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':aircraft_manufacturer' => $aircraft_manufacturer));
		$airport_array = array();
		$temp_array = array();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['arrival_airport_country'] = $row['arrival_airport_country'];
			$temp_array['arrival_airport_country_iso3'] = $row['arrival_airport_country_iso3'];
			$temp_array['airport_arrival_country_count'] = $row['airport_arrival_country_count'];
			$airport_array[] = $temp_array;
		}
		return $airport_array;
	}


    /**
     * Gets all arrival airports of the airplanes that have flown over based on a date
     *
     * @param $date
     * @param array $filters
     * @return array the airport list
     */
	public function countAllArrivalAirportsByDate($date,$filters = array())
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
			$query  = "SELECT DISTINCT spotter_output.arrival_airport_icao, COUNT(spotter_output.arrival_airport_icao) AS airport_arrival_icao_count, spotter_output.arrival_airport_name, spotter_output.arrival_airport_city, spotter_output.arrival_airport_country, airport.latitude, airport.longitude 
					FROM spotter_output,airport".$filter_query." spotter_output.arrival_airport_name <> '' AND spotter_output.arrival_airport_icao <> 'NA' AND spotter_output.arrival_airport_icao <> '' AND DATE(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) = :date AND airport.icao = spotter_output.arrival_airport_icao 
					GROUP BY spotter_output.arrival_airport_icao, spotter_output.arrival_airport_name, spotter_output.arrival_airport_city, spotter_output.arrival_airport_country, airport.latitude, airport.longitude 
					ORDER BY airport_arrival_icao_count DESC";
		} else {
			$query  = "SELECT DISTINCT spotter_output.arrival_airport_icao, COUNT(spotter_output.arrival_airport_icao) AS airport_arrival_icao_count, spotter_output.arrival_airport_name, spotter_output.arrival_airport_city, spotter_output.arrival_airport_country, airport.latitude, airport.longitude 
					FROM spotter_output,airport".$filter_query." spotter_output.arrival_airport_name <> '' AND spotter_output.arrival_airport_icao <> 'NA' AND spotter_output.arrival_airport_icao <> '' AND to_char(spotter_output.date AT TIME ZONE INTERVAL :offset,'YYYY-mm-dd') = :date AND airport.icao = spotter_output.arrival_airport_icao 
					GROUP BY spotter_output.arrival_airport_icao, spotter_output.arrival_airport_name, spotter_output.arrival_airport_city, spotter_output.arrival_airport_country, airport.latitude, airport.longitude 
					ORDER BY airport_arrival_icao_count DESC";
		}
		$sth = $this->db->prepare($query);
		$sth->execute(array(':date' => $date, ':offset' => $offset));
		$airport_array = array();
		$temp_array = array();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airport_arrival_icao'] = $row['arrival_airport_icao'];
			$temp_array['airport_arrival_icao_count'] = $row['airport_arrival_icao_count'];
			$temp_array['airport_arrival_name'] = $row['arrival_airport_name'];
			$temp_array['airport_arrival_city'] = $row['arrival_airport_city'];
			$temp_array['airport_arrival_country'] = $row['arrival_airport_country'];
			$temp_array['airport_arrival_latitude'] = $row['latitude'];
			$temp_array['airport_arrival_longitude'] = $row['longitude'];
			$airport_array[] = $temp_array;
		}
		return $airport_array;
	}


    /**
     * Gets all arrival airports by country of the airplanes that have flown over based on a date
     *
     * @param $date
     * @param array $filters
     * @return array the airport list
     */
	public function countAllArrivalAirportCountriesByDate($date, $filters = array())
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
			$query  = "SELECT DISTINCT spotter_output.arrival_airport_country, COUNT(spotter_output.arrival_airport_country) AS airport_arrival_country_count, countries.iso3 AS arrival_airport_country_iso3 
					FROM countries,spotter_output".$filter_query." countries.name = spotter_output.arrival_airport_country AND spotter_output.arrival_airport_country <> '' AND DATE(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) = :date 
					GROUP BY spotter_output.arrival_airport_country, countries.iso3
					ORDER BY airport_arrival_country_count DESC";
		} else {
			$query  = "SELECT DISTINCT spotter_output.arrival_airport_country, COUNT(spotter_output.arrival_airport_country) AS airport_arrival_country_count, countries.iso3 AS arrival_airport_country_iso3 
					FROM countries,spotter_output".$filter_query." countries.name = spotter_output.arrival_airport_country AND spotter_output.arrival_airport_country <> '' AND to_char(spotter_output.date AT TIME ZONE INTERVAL :offset,'YYYY-mm-dd') = :date 
					GROUP BY spotter_output.arrival_airport_country, countries.iso3
					ORDER BY airport_arrival_country_count DESC";
		}
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':date' => $date, ':offset' => $offset));
      
		$airport_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['arrival_airport_country'] = $row['arrival_airport_country'];
			$temp_array['arrival_airport_country_iso3'] = $row['arrival_airport_country_iso3'];
			$temp_array['airport_arrival_country_count'] = $row['airport_arrival_country_count'];
			$airport_array[] = $temp_array;
		}
		return $airport_array;
	}


    /**
     * Gets all arrival airports of the airplanes that have flown over based on a ident/callsign
     *
     * @param $ident
     * @param array $filters
     * @return array the airport list
     */
	public function countAllArrivalAirportsByIdent($ident,$filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$ident = filter_var($ident,FILTER_SANITIZE_STRING);
		$query  = "SELECT DISTINCT spotter_output.arrival_airport_icao, COUNT(spotter_output.arrival_airport_icao) AS airport_arrival_icao_count, spotter_output.arrival_airport_name, spotter_output.arrival_airport_city, spotter_output.arrival_airport_country, airport.latitude, airport.longitude 
		    FROM spotter_output,airport".$filter_query." spotter_output.arrival_airport_name <> '' AND spotter_output.arrival_airport_icao <> 'NA' AND spotter_output.arrival_airport_icao <> '' AND spotter_output.ident = :ident AND airport.icao = spotter_output.arrival_airport_icao 
		    GROUP BY spotter_output.arrival_airport_icao, spotter_output.arrival_airport_name, spotter_output.arrival_airport_city, spotter_output.arrival_airport_country, airport.latitude, airport.longitude
		    ORDER BY airport_arrival_icao_count DESC";
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':ident' => $ident));
		$airport_array = array();
		$temp_array = array();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airport_arrival_icao'] = $row['arrival_airport_icao'];
			$temp_array['airport_arrival_icao_count'] = $row['airport_arrival_icao_count'];
			$temp_array['airport_arrival_name'] = $row['arrival_airport_name'];
			$temp_array['airport_arrival_city'] = $row['arrival_airport_city'];
			$temp_array['airport_arrival_country'] = $row['arrival_airport_country'];
			$temp_array['airport_arrival_latitude'] = $row['latitude'];
			$temp_array['airport_arrival_longitude'] = $row['longitude'];
			$airport_array[] = $temp_array;
		}

		return $airport_array;
	}

    /**
     * Gets all arrival airports of the airplanes that have flown over based on a owner
     *
     * @param $owner
     * @param array $filters
     * @return array the airport list
     */
	public function countAllArrivalAirportsByOwner($owner,$filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$owner = filter_var($owner,FILTER_SANITIZE_STRING);
		$query  = "SELECT DISTINCT spotter_output.arrival_airport_icao, COUNT(spotter_output.arrival_airport_icao) AS airport_arrival_icao_count, spotter_output.arrival_airport_name, spotter_output.arrival_airport_city, spotter_output.arrival_airport_country 
		    FROM spotter_output".$filter_query." spotter_output.arrival_airport_name <> '' AND spotter_output.arrival_airport_icao <> 'NA' AND spotter_output.arrival_airport_icao <> '' AND spotter_output.owner_name = :owner 
                    GROUP BY spotter_output.arrival_airport_icao, spotter_output.arrival_airport_name, spotter_output.arrival_airport_city, spotter_output.arrival_airport_country
		    ORDER BY airport_arrival_icao_count DESC";
      
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':owner' => $owner));
		$airport_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airport_arrival_icao'] = $row['arrival_airport_icao'];
			$temp_array['airport_arrival_icao_count'] = $row['airport_arrival_icao_count'];
			$temp_array['airport_arrival_name'] = $row['arrival_airport_name'];
			$temp_array['airport_arrival_city'] = $row['arrival_airport_city'];
			$temp_array['airport_arrival_country'] = $row['arrival_airport_country'];
          
			$airport_array[] = $temp_array;
		}

		return $airport_array;
	}

    /**
     * Gets all arrival airports of the airplanes that have flown over based on a pilot
     *
     * @param $pilot
     * @param array $filters
     * @return array the airport list
     */
	public function countAllArrivalAirportsByPilot($pilot,$filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$pilot = filter_var($pilot,FILTER_SANITIZE_STRING);
		$query  = "SELECT DISTINCT spotter_output.arrival_airport_icao, COUNT(spotter_output.arrival_airport_icao) AS airport_arrival_icao_count, spotter_output.arrival_airport_name, spotter_output.arrival_airport_city, spotter_output.arrival_airport_country, airport.latitude, airport.longitude 
		    FROM spotter_output,airport".$filter_query." spotter_output.arrival_airport_name <> '' AND spotter_output.arrival_airport_icao <> 'NA' AND spotter_output.arrival_airport_icao <> '' AND (spotter_output.pilot_name = :pilot OR spotter_output.pilot_id = :pilot) AND airport.icao = spotter_output.arrival_airport_icao
		    GROUP BY spotter_output.arrival_airport_icao, spotter_output.arrival_airport_name, spotter_output.arrival_airport_city, spotter_output.arrival_airport_country, airport.latitude, airport.longitude
		    ORDER BY airport_arrival_icao_count DESC";
		$sth = $this->db->prepare($query);
		$sth->execute(array(':pilot' => $pilot));
		$airport_array = array();
		$temp_array = array();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airport_arrival_icao'] = $row['arrival_airport_icao'];
			$temp_array['airport_arrival_icao_count'] = $row['airport_arrival_icao_count'];
			$temp_array['airport_arrival_name'] = $row['arrival_airport_name'];
			$temp_array['airport_arrival_city'] = $row['arrival_airport_city'];
			$temp_array['airport_arrival_country'] = $row['arrival_airport_country'];
			$temp_array['airport_arrival_latitude'] = $row['latitude'];
			$temp_array['airport_arrival_longitude'] = $row['longitude'];
			$airport_array[] = $temp_array;
		}
		return $airport_array;
	}

    /**
     * Gets all arrival airports by country of the airplanes that have flown over based on a callsign/ident
     *
     * @param $ident
     * @param array $filters
     * @return array the airport list
     */
	public function countAllArrivalAirportCountriesByIdent($ident, $filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$ident = filter_var($ident,FILTER_SANITIZE_STRING);
		$query  = "SELECT DISTINCT spotter_output.arrival_airport_country, COUNT(spotter_output.arrival_airport_country) AS airport_arrival_country_count, countries.iso3 AS arrival_airport_country_iso3 
			FROM countries,spotter_output".$filter_query." countries.name = spotter_output.arrival_airport_country AND spotter_output.arrival_airport_country <> '' AND spotter_output.ident = :ident 
			GROUP BY spotter_output.arrival_airport_country, countries.iso3
			ORDER BY airport_arrival_country_count DESC";
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':ident' => $ident));
		$airport_array = array();
		$temp_array = array();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['arrival_airport_country'] = $row['arrival_airport_country'];
			$temp_array['arrival_airport_country_iso3'] = $row['arrival_airport_country_iso3'];
			$temp_array['airport_arrival_country_count'] = $row['airport_arrival_country_count'];
			$airport_array[] = $temp_array;
		}
		return $airport_array;
	}

    /**
     * Gets all arrival airports by country of the airplanes that have flown over based on a owner
     *
     * @param $owner
     * @param array $filters
     * @return array the airport list
     */
	public function countAllArrivalAirportCountriesByOwner($owner, $filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$owner = filter_var($owner,FILTER_SANITIZE_STRING);
		$query  = "SELECT DISTINCT spotter_output.arrival_airport_country, COUNT(spotter_output.arrival_airport_country) AS airport_arrival_country_count, countries.iso3 AS airport_arrival_country_iso3 
			FROM countries, spotter_output".$filter_query." spotter_output.arrival_airport_country <> '' AND spotter_output.owner_name = :owner AND countries.name = spotter_output.arrival_airport_country 
			GROUP BY spotter_output.arrival_airport_country, countries.iso3
			ORDER BY airport_arrival_country_count DESC";
		$sth = $this->db->prepare($query);
		$sth->execute(array(':owner' => $owner));
		return $sth->fetchAll(PDO::FETCH_ASSOC);
	}

    /**
     * Gets all arrival airports by country of the airplanes that have flown over based on a pilot
     *
     * @param $pilot
     * @param array $filters
     * @return array the airport list
     */
	public function countAllArrivalAirportCountriesByPilot($pilot, $filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$pilot = filter_var($pilot,FILTER_SANITIZE_STRING);
		$query  = "SELECT DISTINCT spotter_output.arrival_airport_country, COUNT(spotter_output.arrival_airport_country) AS airport_arrival_country_count, countries.iso3 AS arrival_airport_country_iso3 
			FROM countries, spotter_output".$filter_query." spotter_output.arrival_airport_country <> '' AND (spotter_output.pilot_name = :pilot OR spotter_output.pilot_id = :pilot) AND countries.name = spotter_output.arrival_airport_country 
			GROUP BY spotter_output.arrival_airport_country, countries.iso3
			ORDER BY airport_arrival_country_count DESC";
		$sth = $this->db->prepare($query);
		$sth->execute(array(':pilot' => $pilot));
		return $sth->fetchAll(PDO::FETCH_ASSOC);
	}


    /**
     * Gets all arrival airports of the airplanes that have flown over based on a country
     *
     * @param $country
     * @param array $filters
     * @return array the airport list
     */
	public function countAllArrivalAirportsByCountry($country,$filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$country = filter_var($country,FILTER_SANITIZE_STRING);
		$query  = "SELECT DISTINCT spotter_output.arrival_airport_icao, COUNT(spotter_output.arrival_airport_icao) AS airport_arrival_icao_count, spotter_output.arrival_airport_name, spotter_output.arrival_airport_city, spotter_output.arrival_airport_country, airport.latitude, airport.longitude 
			    FROM spotter_output,airport".$filter_query." (((spotter_output.departure_airport_country = :country) OR (spotter_output.arrival_airport_country = :country)) OR spotter_output.airline_country = :country) AND airport.icao = spotter_output.arrival_airport_icao 
			    GROUP BY spotter_output.arrival_airport_icao, spotter_output.arrival_airport_name, spotter_output.arrival_airport_city, spotter_output.arrival_airport_country, airport.latitude, airport.longitude 
			    ORDER BY airport_arrival_icao_count DESC";
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':country' => $country));
		$airport_array = array();
		$temp_array = array();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airport_arrival_icao'] = $row['arrival_airport_icao'];
			$temp_array['airport_arrival_icao_count'] = $row['airport_arrival_icao_count'];
			$temp_array['airport_arrival_name'] = $row['arrival_airport_name'];
			$temp_array['airport_arrival_city'] = $row['arrival_airport_city'];
			$temp_array['airport_arrival_country'] = $row['arrival_airport_country'];
			$temp_array['airport_arrival_latitude'] = $row['latitude'];
			$temp_array['airport_arrival_longitude'] = $row['longitude'];
			$airport_array[] = $temp_array;
		}
		return $airport_array;
	}


    /**
     * Gets all arrival airports by country of the airplanes that have flown over based on a country
     *
     * @param $country
     * @param array $filters
     * @return array the airport list
     */
	public function countAllArrivalAirportCountriesByCountry($country,$filters = array())
	{
		global $globalDBdriver;
		$filter_query = $this->getFilter($filters,true,true);
		$country = filter_var($country,FILTER_SANITIZE_STRING);
		$query  = "SELECT DISTINCT spotter_output.arrival_airport_country, COUNT(spotter_output.arrival_airport_country) AS airport_arrival_country_count, countries.iso3 AS arrival_airport_country_iso3 
			FROM countries,spotter_output".$filter_query." countries.name = spotter_output.arrival_airport_country AND spotter_output.arrival_airport_country <> '' AND ((spotter_output.departure_airport_country = :country) OR (spotter_output.arrival_airport_country = :country) OR spotter_output.airline_country = :country) 
			GROUP BY spotter_output.arrival_airport_country, countries.iso3
			ORDER BY airport_arrival_country_count DESC";
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':country' => $country));
		$airport_array = array();
		$temp_array = array();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['arrival_airport_country'] = $row['arrival_airport_country'];
			$temp_array['arrival_airport_country_iso3'] = $row['arrival_airport_country_iso3'];
			$temp_array['airport_arrival_country_count'] = $row['airport_arrival_country_count'];
			$airport_array[] = $temp_array;
		}
		return $airport_array;
	}


    /**
     * Counts all airport departure countries
     *
     * @param array $filters
     * @param string $year
     * @param string $month
     * @param string $day
     * @return array the airport departure list
     */
	public function countAllDepartureCountries($filters = array(),$year = '',$month = '', $day = '')
	{
		global $globalDBdriver;
		$filter_query = $this->getFilter($filters,true,true);
		$query  = "SELECT DISTINCT spotter_output.departure_airport_country, COUNT(spotter_output.departure_airport_country) AS airport_departure_country_count, countries.iso3 AS airport_departure_country_iso3 
				FROM countries,spotter_output".$filter_query." countries.name = spotter_output.departure_airport_country AND spotter_output.departure_airport_country <> '' AND spotter_output.departure_airport_icao <> 'NA' AND spotter_output.departure_airport_icao <> ''";
		$query_values = array();
		if ($year != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND YEAR(spotter_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			} else {
				$query .= " AND EXTRACT(YEAR FROM spotter_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			}
		}
		if ($month != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND MONTH(spotter_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			} else {
				$query .= " AND EXTRACT(MONTH FROM spotter_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			}
		}
		if ($day != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND DAY(spotter_output.date) = :day";
				$query_values = array_merge($query_values,array(':day' => $day));
			} else {
				$query .= " AND EXTRACT(DAY FROM spotter_output.date) = :day";
				$query_values = array_merge($query_values,array(':day' => $day));
			}
		}
		$query .= " GROUP BY spotter_output.departure_airport_country, countries.iso3
					ORDER BY airport_departure_country_count DESC
					LIMIT 10 OFFSET 0";
      
		
		$sth = $this->db->prepare($query);
		$sth->execute($query_values);
      
		$airport_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airport_departure_country_count'] = $row['airport_departure_country_count'];
			$temp_array['airport_departure_country'] = $row['departure_airport_country'];
			$temp_array['airport_departure_country_iso3'] = $row['airport_departure_country_iso3'];
          
			$airport_array[] = $temp_array;
		}

		return $airport_array;
	}


    /**
     * Counts all airport arrival countries
     *
     * @param bool $limit
     * @param array $filters
     * @param string $year
     * @param string $month
     * @param string $day
     * @return array the airport arrival list
     */
	public function countAllArrivalCountries($limit = true,$filters = array(),$year = '',$month = '',$day = '')
	{
		global $globalDBdriver;
		$filter_query = $this->getFilter($filters,true,true);
		$query  = "SELECT DISTINCT spotter_output.arrival_airport_country, COUNT(spotter_output.arrival_airport_country) AS airport_arrival_country_count, countries.iso3 AS airport_arrival_country_iso3 
			FROM countries, spotter_output".$filter_query." countries.name = spotter_output.arrival_airport_country AND spotter_output.arrival_airport_country <> '' AND spotter_output.arrival_airport_icao <> 'NA' AND spotter_output.arrival_airport_icao <> ''";
		$query_values = array();
		if ($year != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND YEAR(spotter_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			} else {
				$query .= " AND EXTRACT(YEAR FROM spotter_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			}
		}
		if ($month != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND MONTH(spotter_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			} else {
				$query .= " AND EXTRACT(MONTH FROM spotter_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			}
		}
		if ($day != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND DAY(spotter_output.date) = :day";
				$query_values = array_merge($query_values,array(':day' => $day));
			} else {
				$query .= " AND EXTRACT(DAY FROM spotter_output.date) = :day";
				$query_values = array_merge($query_values,array(':day' => $day));
			}
		}
		$query .= " GROUP BY spotter_output.arrival_airport_country, countries.iso3
					ORDER BY airport_arrival_country_count DESC";
		if ($limit) $query .= " LIMIT 10 OFFSET 0";
      
		
		$sth = $this->db->prepare($query);
		$sth->execute($query_values);
      
		$airport_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['airport_arrival_country_count'] = $row['airport_arrival_country_count'];
			$temp_array['airport_arrival_country'] = $row['arrival_airport_country'];
			$temp_array['airport_arrival_country_iso3'] = $row['airport_arrival_country_iso3'];
          
			$airport_array[] = $temp_array;
		}

		return $airport_array;
	}


    /**
     * Gets all route combinations
     *
     * @param array $filters
     * @return array the route list
     */
	public function countAllRoutes($filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$query  = "SELECT DISTINCT concat(spotter_output.departure_airport_icao, ' - ',  spotter_output.arrival_airport_icao) AS route, count(concat(spotter_output.departure_airport_icao, ' - ', spotter_output.arrival_airport_icao)) AS route_count, spotter_output.departure_airport_icao, spotter_output.departure_airport_name AS airport_departure_name, spotter_output.departure_airport_city AS airport_departure_city, spotter_output.departure_airport_country AS airport_departure_country, spotter_output.arrival_airport_icao, spotter_output.arrival_airport_name AS airport_arrival_name, spotter_output.arrival_airport_city AS airport_arrival_city, spotter_output.arrival_airport_country AS airport_arrival_country
		    FROM spotter_output".$filter_query." spotter_output.departure_airport_icao <> 'NA' AND spotter_output.departure_airport_icao <> '' AND spotter_output.arrival_airport_icao <> 'NA' AND spotter_output.arrival_airport_icao <> ''
                    GROUP BY route,spotter_output.departure_airport_icao, spotter_output.arrival_airport_icao,spotter_output.arrival_airport_name, spotter_output.arrival_airport_city, spotter_output.arrival_airport_country, spotter_output.departure_airport_name, spotter_output.departure_airport_city, spotter_output.departure_airport_country
                    ORDER BY route_count DESC
		    LIMIT 10 OFFSET 0";
		
		$sth = $this->db->prepare($query);
		$sth->execute();
		$routes_array = array();
		$temp_array = array();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['route_count'] = $row['route_count'];
			$temp_array['airport_departure_icao'] = $row['departure_airport_icao'];
			$temp_array['airport_departure_name'] = $row['airport_departure_name'];
			$temp_array['airport_departure_city'] = $row['airport_departure_city'];
			$temp_array['airport_departure_country'] = $row['airport_departure_country'];
			$temp_array['airport_arrival_icao'] = $row['arrival_airport_icao'];
			$temp_array['airport_arrival_name'] = $row['airport_arrival_name'];
			$temp_array['airport_arrival_city'] = $row['airport_arrival_city'];
			$temp_array['airport_arrival_country'] = $row['airport_arrival_country'];
			$routes_array[] = $temp_array;
		}
		return $routes_array;
	}


    /**
     * Gets all route combinations based on an aircraft
     *
     * @param $aircraft_icao
     * @param array $filters
     * @return array the route list
     */
	public function countAllRoutesByAircraft($aircraft_icao,$filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$aircraft_icao = filter_var($aircraft_icao,FILTER_SANITIZE_STRING);
		$query  = "SELECT DISTINCT concat(spotter_output.departure_airport_icao, ' - ',  spotter_output.arrival_airport_icao) AS route, count(concat(spotter_output.departure_airport_icao, ' - ', spotter_output.arrival_airport_icao)) AS route_count, spotter_output.departure_airport_icao, spotter_output.departure_airport_name AS airport_departure_name, spotter_output.departure_airport_city AS airport_departure_city, spotter_output.departure_airport_country AS airport_departure_country, spotter_output.arrival_airport_icao, spotter_output.arrival_airport_name AS airport_arrival_name, spotter_output.arrival_airport_city AS airport_arrival_city, spotter_output.arrival_airport_country AS airport_arrival_country
			    FROM spotter_output".$filter_query." spotter_output.ident <> '' AND spotter_output.aircraft_icao = :aircraft_icao 
			    GROUP BY route, spotter_output.departure_airport_icao, spotter_output.departure_airport_name, spotter_output.departure_airport_city, spotter_output.departure_airport_country, spotter_output.arrival_airport_icao, spotter_output.arrival_airport_name, spotter_output.arrival_airport_city, spotter_output.arrival_airport_country 
			    ORDER BY route_count DESC";
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':aircraft_icao' => $aircraft_icao));
      
		$routes_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['route_count'] = $row['route_count'];
			$temp_array['airport_departure_icao'] = $row['departure_airport_icao'];
			$temp_array['airport_departure_name'] = $row['airport_departure_name'];
			$temp_array['airport_departure_city'] = $row['airport_departure_city'];
			$temp_array['airport_departure_country'] = $row['airport_departure_country'];
			$temp_array['airport_arrival_icao'] = $row['arrival_airport_icao'];
			$temp_array['airport_arrival_name'] = $row['airport_arrival_name'];
			$temp_array['airport_arrival_city'] = $row['airport_arrival_city'];
			$temp_array['airport_arrival_country'] = $row['airport_arrival_country'];
          
			$routes_array[] = $temp_array;
		}

		return $routes_array;
	}


    /**
     * Gets all route combinations based on an aircraft registration
     *
     * @param $registration
     * @param array $filters
     * @return array the route list
     */
	public function countAllRoutesByRegistration($registration, $filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$registration = filter_var($registration, FILTER_SANITIZE_STRING);
		$query  = "SELECT DISTINCT concat(spotter_output.departure_airport_icao, ' - ',  spotter_output.arrival_airport_icao) AS route, count(concat(spotter_output.departure_airport_icao, ' - ', spotter_output.arrival_airport_icao)) AS route_count, spotter_output.departure_airport_icao, spotter_output.departure_airport_name AS airport_departure_name, spotter_output.departure_airport_city AS airport_departure_city, spotter_output.departure_airport_country AS airport_departure_country, spotter_output.arrival_airport_icao, spotter_output.arrival_airport_name AS airport_arrival_name, spotter_output.arrival_airport_city AS airport_arrival_city, spotter_output.arrival_airport_country AS airport_arrival_country
			FROM spotter_output".$filter_query." spotter_output.ident <> '' AND spotter_output.registration = :registration 
                    GROUP BY route
                    ORDER BY route_count DESC";
      
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':registration' => $registration));
      
		$routes_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['route_count'] = $row['route_count'];
			$temp_array['airport_departure_icao'] = $row['departure_airport_icao'];
			$temp_array['airport_departure_name'] = $row['airport_departure_name'];
			$temp_array['airport_departure_city'] = $row['airport_departure_city'];
			$temp_array['airport_departure_country'] = $row['airport_departure_country'];
			$temp_array['airport_arrival_icao'] = $row['arrival_airport_icao'];
			$temp_array['airport_arrival_name'] = $row['airport_arrival_name'];
			$temp_array['airport_arrival_city'] = $row['airport_arrival_city'];
			$temp_array['airport_arrival_country'] = $row['airport_arrival_country'];
          
			$routes_array[] = $temp_array;
		}

		return $routes_array;
	}


    /**
     * Gets all route combinations based on an airline
     *
     * @param $airline_icao
     * @param array $filters
     * @return array the route list
     */
	public function countAllRoutesByAirline($airline_icao, $filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$airline_icao = filter_var($airline_icao,FILTER_SANITIZE_STRING);
		$query  = "SELECT DISTINCT concat(spotter_output.departure_airport_icao, ' - ',  spotter_output.arrival_airport_icao) AS route, count(concat(spotter_output.departure_airport_icao, ' - ', spotter_output.arrival_airport_icao)) AS route_count, spotter_output.departure_airport_icao, spotter_output.departure_airport_name AS airport_departure_name, spotter_output.departure_airport_city AS airport_departure_city, spotter_output.departure_airport_country AS airport_departure_country, spotter_output.arrival_airport_icao, spotter_output.arrival_airport_name AS airport_arrival_name, spotter_output.arrival_airport_city AS airport_arrival_city, spotter_output.arrival_airport_country AS airport_arrival_country
			    FROM spotter_output".$filter_query." spotter_output.ident <> '' ";
		if ($airline_icao != '') $query .= "AND spotter_output.airline_icao = :airline_icao ";
		$query .= "GROUP BY route, spotter_output.departure_airport_icao, spotter_output.departure_airport_name, spotter_output.departure_airport_city, spotter_output.departure_airport_country, spotter_output.arrival_airport_icao, spotter_output.arrival_airport_name, spotter_output.arrival_airport_city, spotter_output.arrival_airport_country 
			    ORDER BY route_count DESC";

		$sth = $this->db->prepare($query);
		if ($airline_icao != '') $sth->execute(array(':airline_icao' => $airline_icao));
		else $sth->execute();
      
		$routes_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['route_count'] = $row['route_count'];
			$temp_array['airport_departure_icao'] = $row['departure_airport_icao'];
			$temp_array['airport_departure_name'] = $row['airport_departure_name'];
			$temp_array['airport_departure_city'] = $row['airport_departure_city'];
			$temp_array['airport_departure_country'] = $row['airport_departure_country'];
			$temp_array['airport_arrival_icao'] = $row['arrival_airport_icao'];
			$temp_array['airport_arrival_name'] = $row['airport_arrival_name'];
			$temp_array['airport_arrival_city'] = $row['airport_arrival_city'];
			$temp_array['airport_arrival_country'] = $row['airport_arrival_country'];
          
			$routes_array[] = $temp_array;
		}

		return $routes_array;
	}


    /**
     * Gets all route combinations based on an airport
     *
     * @param $airport_icao
     * @param array $filters
     * @return array the route list
     */
	public function countAllRoutesByAirport($airport_icao, $filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$airport_icao = filter_var($airport_icao,FILTER_SANITIZE_STRING);
		$query  = "SELECT DISTINCT concat(spotter_output.departure_airport_icao, ' - ',  spotter_output.arrival_airport_icao) AS route, count(concat(spotter_output.departure_airport_icao, ' - ', spotter_output.arrival_airport_icao)) AS route_count, spotter_output.departure_airport_icao, spotter_output.departure_airport_name AS airport_departure_name, spotter_output.departure_airport_city AS airport_departure_city, spotter_output.departure_airport_country AS airport_departure_country, spotter_output.arrival_airport_icao, spotter_output.arrival_airport_name AS airport_arrival_name, spotter_output.arrival_airport_city AS airport_arrival_city, spotter_output.arrival_airport_country AS airport_arrival_country
			    FROM spotter_output".$filter_query." spotter_output.ident <> '' AND (spotter_output.departure_airport_icao = :airport_icao OR spotter_output.arrival_airport_icao = :airport_icao)
			    GROUP BY route, spotter_output.departure_airport_icao, spotter_output.departure_airport_name, spotter_output.departure_airport_city, spotter_output.departure_airport_country, spotter_output.arrival_airport_icao, spotter_output.arrival_airport_name, spotter_output.arrival_airport_city, spotter_output.arrival_airport_country 
			    ORDER BY route_count DESC";
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':airport_icao' => $airport_icao));
      
		$routes_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['route_count'] = $row['route_count'];
			$temp_array['airport_departure_icao'] = $row['departure_airport_icao'];
			$temp_array['airport_departure_name'] = $row['airport_departure_name'];
			$temp_array['airport_departure_city'] = $row['airport_departure_city'];
			$temp_array['airport_departure_country'] = $row['airport_departure_country'];
			$temp_array['airport_arrival_icao'] = $row['arrival_airport_icao'];
			$temp_array['airport_arrival_name'] = $row['airport_arrival_name'];
			$temp_array['airport_arrival_city'] = $row['airport_arrival_city'];
			$temp_array['airport_arrival_country'] = $row['airport_arrival_country'];
          
			$routes_array[] = $temp_array;
		}

		return $routes_array;
	}


    /**
     * Gets all route combinations based on an country
     *
     * @param $country
     * @param array $filters
     * @return array the route list
     */
	public function countAllRoutesByCountry($country, $filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$country = filter_var($country,FILTER_SANITIZE_STRING);
		$query  = "SELECT DISTINCT concat(spotter_output.departure_airport_icao, ' - ',  spotter_output.arrival_airport_icao) AS route, count(concat(spotter_output.departure_airport_icao, ' - ', spotter_output.arrival_airport_icao)) AS route_count, spotter_output.departure_airport_icao, spotter_output.departure_airport_name AS airport_departure_name, spotter_output.departure_airport_city AS airport_departure_city, spotter_output.departure_airport_country AS airport_departure_country, spotter_output.arrival_airport_icao, spotter_output.arrival_airport_name AS airport_arrival_name, spotter_output.arrival_airport_city AS airport_arrival_city, spotter_output.arrival_airport_country AS airport_arrival_country
			    FROM spotter_output".$filter_query." spotter_output.ident <> '' AND ((spotter_output.departure_airport_country = :country) OR (spotter_output.arrival_airport_country = :country)) OR spotter_output.airline_country = :country 
			    GROUP BY route, spotter_output.departure_airport_icao, spotter_output.departure_airport_name, spotter_output.departure_airport_city, spotter_output.departure_airport_country, spotter_output.arrival_airport_icao, spotter_output.arrival_airport_name, spotter_output.arrival_airport_city, spotter_output.arrival_airport_country 
			    ORDER BY route_count DESC";
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':country' => $country));
      
		$routes_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['route_count'] = $row['route_count'];
			$temp_array['airport_departure_icao'] = $row['departure_airport_icao'];
			$temp_array['airport_departure_name'] = $row['airport_departure_name'];
			$temp_array['airport_departure_city'] = $row['airport_departure_city'];
			$temp_array['airport_departure_country'] = $row['airport_departure_country'];
			$temp_array['airport_arrival_icao'] = $row['arrival_airport_icao'];
			$temp_array['airport_arrival_name'] = $row['airport_arrival_name'];
			$temp_array['airport_arrival_city'] = $row['airport_arrival_city'];
			$temp_array['airport_arrival_country'] = $row['airport_arrival_country'];
          
			$routes_array[] = $temp_array;
		}

		return $routes_array;
	}


    /**
     * Gets all route combinations based on an date
     *
     * @param $date
     * @param array $filters
     * @return array the route list
     */
	public function countAllRoutesByDate($date, $filters = array())
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
			$query  = "SELECT DISTINCT concat(spotter_output.departure_airport_icao, ' - ',  spotter_output.arrival_airport_icao) AS route, count(concat(spotter_output.departure_airport_icao, ' - ', spotter_output.arrival_airport_icao)) AS route_count, spotter_output.departure_airport_icao, spotter_output.departure_airport_name AS airport_departure_name, spotter_output.departure_airport_city AS airport_departure_city, spotter_output.departure_airport_country AS airport_departure_country, spotter_output.arrival_airport_icao, spotter_output.arrival_airport_name AS airport_arrival_name, spotter_output.arrival_airport_city AS airport_arrival_city, spotter_output.arrival_airport_country AS airport_arrival_country
				    FROM spotter_output".$filter_query." spotter_output.ident <> '' AND DATE(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) = :date  
				    GROUP BY route, spotter_output.departure_airport_icao, spotter_output.departure_airport_name, spotter_output.departure_airport_city, spotter_output.departure_airport_country, spotter_output.arrival_airport_icao, spotter_output.arrival_airport_name, spotter_output.arrival_airport_city, spotter_output.arrival_airport_country
				    ORDER BY route_count DESC";
		} else {
			$query  = "SELECT DISTINCT concat(spotter_output.departure_airport_icao, ' - ',  spotter_output.arrival_airport_icao) AS route, count(concat(spotter_output.departure_airport_icao, ' - ', spotter_output.arrival_airport_icao)) AS route_count, spotter_output.departure_airport_icao, spotter_output.departure_airport_name AS airport_departure_name, spotter_output.departure_airport_city AS airport_departure_city, spotter_output.departure_airport_country AS airport_departure_country, spotter_output.arrival_airport_icao, spotter_output.arrival_airport_name AS airport_arrival_name, spotter_output.arrival_airport_city AS airport_arrival_city, spotter_output.arrival_airport_country AS airport_arrival_country
				    FROM spotter_output".$filter_query." spotter_output.ident <> '' AND to_char(spotter_output.date AT TIME ZONE INTERVAL :offset,'YYYY-mm-dd') = :date  
				    GROUP BY route, spotter_output.departure_airport_icao, spotter_output.departure_airport_name, spotter_output.departure_airport_city, spotter_output.departure_airport_country, spotter_output.arrival_airport_icao, spotter_output.arrival_airport_name, spotter_output.arrival_airport_city, spotter_output.arrival_airport_country
				    ORDER BY route_count DESC";
		}
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':date' => $date, ':offset' => $offset));
      
		$routes_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['route_count'] = $row['route_count'];
			$temp_array['airport_departure_icao'] = $row['departure_airport_icao'];
			$temp_array['airport_departure_name'] = $row['airport_departure_name'];
			$temp_array['airport_departure_city'] = $row['airport_departure_city'];
			$temp_array['airport_departure_country'] = $row['airport_departure_country'];
			$temp_array['airport_arrival_icao'] = $row['arrival_airport_icao'];
			$temp_array['airport_arrival_name'] = $row['airport_arrival_name'];
			$temp_array['airport_arrival_city'] = $row['airport_arrival_city'];
			$temp_array['airport_arrival_country'] = $row['airport_arrival_country'];
          
			$routes_array[] = $temp_array;
		}

		return $routes_array;
	}


    /**
     * Gets all route combinations based on an ident/callsign
     *
     * @param $ident
     * @param array $filters
     * @return array the route list
     */
	public function countAllRoutesByIdent($ident, $filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$ident = filter_var($ident,FILTER_SANITIZE_STRING);
		$query  = "SELECT DISTINCT concat(spotter_output.departure_airport_icao, ' - ',  spotter_output.arrival_airport_icao) AS route, count(concat(spotter_output.departure_airport_icao, ' - ', spotter_output.arrival_airport_icao)) AS route_count, spotter_output.departure_airport_icao, spotter_output.departure_airport_name AS airport_departure_name, spotter_output.departure_airport_city AS airport_departure_city, spotter_output.departure_airport_country AS airport_departure_country, spotter_output.arrival_airport_icao, spotter_output.arrival_airport_name AS airport_arrival_name, spotter_output.arrival_airport_city AS airport_arrival_city, spotter_output.arrival_airport_country AS airport_arrival_country
		    FROM spotter_output".$filter_query." spotter_output.ident <> '' AND spotter_output.ident = :ident   
                    GROUP BY route, spotter_output.departure_airport_icao, spotter_output.departure_airport_name, spotter_output.departure_airport_city, spotter_output.departure_airport_country, spotter_output.arrival_airport_icao, spotter_output.arrival_airport_name, spotter_output.arrival_airport_city, spotter_output.arrival_airport_country
                    ORDER BY route_count DESC";
      
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':ident' => $ident));
      
		$routes_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['route_count'] = $row['route_count'];
			$temp_array['airport_departure_icao'] = $row['departure_airport_icao'];
			$temp_array['airport_departure_name'] = $row['airport_departure_name'];
			$temp_array['airport_departure_city'] = $row['airport_departure_city'];
			$temp_array['airport_departure_country'] = $row['airport_departure_country'];
			$temp_array['airport_arrival_icao'] = $row['arrival_airport_icao'];
			$temp_array['airport_arrival_name'] = $row['airport_arrival_name'];
			$temp_array['airport_arrival_city'] = $row['airport_arrival_city'];
			$temp_array['airport_arrival_country'] = $row['airport_arrival_country'];
          
			$routes_array[] = $temp_array;
		}

		return $routes_array;
	}

    /**
     * Gets all route combinations based on an owner
     *
     * @param $owner
     * @param array $filters
     * @return array the route list
     */
	public function countAllRoutesByOwner($owner,$filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$owner = filter_var($owner,FILTER_SANITIZE_STRING);
		$query  = "SELECT DISTINCT concat(spotter_output.departure_airport_icao, ' - ',  spotter_output.arrival_airport_icao) AS route, count(concat(spotter_output.departure_airport_icao, ' - ', spotter_output.arrival_airport_icao)) AS route_count, spotter_output.departure_airport_icao, spotter_output.departure_airport_name AS airport_departure_name, spotter_output.departure_airport_city AS airport_departure_city, spotter_output.departure_airport_country AS airport_departure_country, spotter_output.arrival_airport_icao, spotter_output.arrival_airport_name AS airport_arrival_name, spotter_output.arrival_airport_city AS airport_arrival_city, spotter_output.arrival_airport_country AS airport_arrival_country
		    FROM spotter_output".$filter_query." spotter_output.ident <> '' AND spotter_output.owner_name = :owner 
                    GROUP BY route, spotter_output.departure_airport_icao, spotter_output.departure_airport_name, spotter_output.departure_airport_city, spotter_output.departure_airport_country, spotter_output.arrival_airport_icao, spotter_output.arrival_airport_name, spotter_output.arrival_airport_city, spotter_output.arrival_airport_country
                    ORDER BY route_count DESC";
      
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':owner' => $owner));
      
		$routes_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['route_count'] = $row['route_count'];
			$temp_array['airport_departure_icao'] = $row['departure_airport_icao'];
			$temp_array['airport_departure_name'] = $row['airport_departure_name'];
			$temp_array['airport_departure_city'] = $row['airport_departure_city'];
			$temp_array['airport_departure_country'] = $row['airport_departure_country'];
			$temp_array['airport_arrival_icao'] = $row['arrival_airport_icao'];
			$temp_array['airport_arrival_name'] = $row['airport_arrival_name'];
			$temp_array['airport_arrival_city'] = $row['airport_arrival_city'];
			$temp_array['airport_arrival_country'] = $row['airport_arrival_country'];
          
			$routes_array[] = $temp_array;
		}

		return $routes_array;
	}

    /**
     * Gets all route combinations based on a pilot
     *
     * @param $pilot
     * @param array $filters
     * @return array the route list
     */
	public function countAllRoutesByPilot($pilot,$filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$pilot = filter_var($pilot,FILTER_SANITIZE_STRING);
		$query  = "SELECT DISTINCT concat(spotter_output.departure_airport_icao, ' - ',  spotter_output.arrival_airport_icao) AS route, count(concat(spotter_output.departure_airport_icao, ' - ', spotter_output.arrival_airport_icao)) AS route_count, spotter_output.departure_airport_icao, spotter_output.departure_airport_name AS airport_departure_name, spotter_output.departure_airport_city AS airport_departure_city, spotter_output.departure_airport_country AS airport_departure_country, spotter_output.arrival_airport_icao, spotter_output.arrival_airport_name AS airport_arrival_name, spotter_output.arrival_airport_city AS airport_arrival_city, spotter_output.arrival_airport_country AS airport_arrival_country
		    FROM spotter_output".$filter_query." spotter_output.ident <> '' AND (spotter_output.pilot_name = :pilot OR spotter_output.pilot_id = :pilot) 
                    GROUP BY route, spotter_output.departure_airport_icao, spotter_output.departure_airport_name, spotter_output.departure_airport_city, spotter_output.departure_airport_country, spotter_output.arrival_airport_icao, spotter_output.arrival_airport_name, spotter_output.arrival_airport_city, spotter_output.arrival_airport_country
                    ORDER BY route_count DESC";
      
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':pilot' => $pilot));
      
		$routes_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['route_count'] = $row['route_count'];
			$temp_array['airport_departure_icao'] = $row['departure_airport_icao'];
			$temp_array['airport_departure_name'] = $row['airport_departure_name'];
			$temp_array['airport_departure_city'] = $row['airport_departure_city'];
			$temp_array['airport_departure_country'] = $row['airport_departure_country'];
			$temp_array['airport_arrival_icao'] = $row['arrival_airport_icao'];
			$temp_array['airport_arrival_name'] = $row['airport_arrival_name'];
			$temp_array['airport_arrival_city'] = $row['airport_arrival_city'];
			$temp_array['airport_arrival_country'] = $row['airport_arrival_country'];
          
			$routes_array[] = $temp_array;
		}

		return $routes_array;
	}


    /**
     * Gets all route combinations based on an manufacturer
     *
     * @param $aircraft_manufacturer
     * @param array $filters
     * @return array the route list
     */
	public function countAllRoutesByManufacturer($aircraft_manufacturer, $filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$aircraft_manufacturer = filter_var($aircraft_manufacturer,FILTER_SANITIZE_STRING);
		$query  = "SELECT DISTINCT concat(spotter_output.departure_airport_icao, ' - ',  spotter_output.arrival_airport_icao) AS route, count(concat(spotter_output.departure_airport_icao, ' - ', spotter_output.arrival_airport_icao)) AS route_count, spotter_output.departure_airport_icao, spotter_output.departure_airport_name AS airport_departure_name, spotter_output.departure_airport_city AS airport_departure_city, spotter_output.departure_airport_country AS airport_departure_country, spotter_output.arrival_airport_icao, spotter_output.arrival_airport_name AS airport_arrival_name, spotter_output.arrival_airport_city AS airport_arrival_city, spotter_output.arrival_airport_country AS airport_arrival_country
		    FROM spotter_output".$filter_query." spotter_output.ident <> '' AND spotter_output.aircraft_manufacturer = :aircraft_manufacturer   
                    GROUP BY route
                    ORDER BY route_count DESC";
      
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':aircraft_manufacturer' => $aircraft_manufacturer));
      
		$routes_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['route_count'] = $row['route_count'];
			$temp_array['airport_departure_icao'] = $row['departure_airport_icao'];
			$temp_array['airport_departure_name'] = $row['airport_departure_name'];
			$temp_array['airport_departure_city'] = $row['airport_departure_city'];
			$temp_array['airport_departure_country'] = $row['airport_departure_country'];
			$temp_array['airport_arrival_icao'] = $row['arrival_airport_icao'];
			$temp_array['airport_arrival_name'] = $row['airport_arrival_name'];
			$temp_array['airport_arrival_city'] = $row['airport_arrival_city'];
			$temp_array['airport_arrival_country'] = $row['airport_arrival_country'];
          
			$routes_array[] = $temp_array;
		}

		return $routes_array;
	}


    /**
     * Gets all route combinations with waypoints
     *
     * @param array $filters
     * @return array the route list
     */
	public function countAllRoutesWithWaypoints($filters = array())
	{
		$filter_query = $this->getFilter($filters,true,true);
		$query  = "SELECT DISTINCT spotter_output.waypoints AS route, count(spotter_output.waypoints) AS route_count, spotter_output.spotter_id, spotter_output.departure_airport_icao, spotter_output.departure_airport_name AS airport_departure_name, spotter_output.departure_airport_city AS airport_departure_city, spotter_output.departure_airport_country AS airport_departure_country, spotter_output.arrival_airport_icao, spotter_output.arrival_airport_name AS airport_arrival_name, spotter_output.arrival_airport_city AS airport_arrival_city, spotter_output.arrival_airport_country AS airport_arrival_country
		    FROM spotter_output".$filter_query." spotter_output.ident <> '' AND spotter_output.waypoints <> '' 
                    GROUP BY route, spotter_output.spotter_id, spotter_output.departure_airport_icao, spotter_output.departure_airport_name, spotter_output.departure_airport_city, spotter_output.departure_airport_country, spotter_output.arrival_airport_icao, spotter_output.arrival_airport_name, spotter_output.arrival_airport_city, spotter_output.arrival_airport_country
                    ORDER BY route_count DESC
		    LIMIT 10 OFFSET 0";
      
		
		$sth = $this->db->prepare($query);
		$sth->execute();
      
		$routes_array = array();
		$temp_array = array();
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$temp_array['spotter_id'] = $row['spotter_id'];
			$temp_array['route_count'] = $row['route_count'];
			$temp_array['airport_departure_icao'] = $row['departure_airport_icao'];
			$temp_array['airport_departure_name'] = $row['airport_departure_name'];
			$temp_array['airport_departure_city'] = $row['airport_departure_city'];
			$temp_array['airport_departure_country'] = $row['airport_departure_country'];
			$temp_array['airport_arrival_icao'] = $row['arrival_airport_icao'];
			$temp_array['airport_arrival_name'] = $row['airport_arrival_name'];
			$temp_array['airport_arrival_city'] = $row['airport_arrival_city'];
			$temp_array['airport_arrival_country'] = $row['airport_arrival_country'];
          
			$routes_array[] = $temp_array;
		}

		return $routes_array;
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
		$query  = "SELECT DISTINCT spotter_output.ident, COUNT(spotter_output.ident) AS callsign_icao_count, spotter_output.airline_name, spotter_output.airline_icao  
                    FROM spotter_output".$filter_query." spotter_output.ident <> ''";
		 if ($olderthanmonths > 0) {
			if ($globalDBdriver == 'mysql') $query .= ' AND date < DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.$olderthanmonths.' MONTH)';
			else $query .= " AND spotter_output.date < CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$olderthanmonths." MONTHS'";
		}
		if ($sincedate != '') {
			if ($globalDBdriver == 'mysql') $query .= " AND spotter_output.date > '".$sincedate."'";
			else $query .= " AND spotter_output.date > CAST('".$sincedate."' AS TIMESTAMP)";
		}
		$query_values = array();
		if ($year != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND YEAR(spotter_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			} else {
				$query .= " AND EXTRACT(YEAR FROM spotter_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			}
		}
		if ($month != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND MONTH(spotter_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			} else {
				$query .= " AND EXTRACT(MONTH FROM spotter_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			}
		}
		if ($day != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND DAY(spotter_output.date) = :day";
				$query_values = array_merge($query_values,array(':day' => $day));
			} else {
				$query .= " AND EXTRACT(DAY FROM spotter_output.date) = :day";
				$query_values = array_merge($query_values,array(':day' => $day));
			}
		}
		$query .= " GROUP BY spotter_output.ident, spotter_output.airline_name, spotter_output.airline_icao ORDER BY callsign_icao_count DESC";
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
     * Gets all callsigns that have flown over
     *
     * @param bool $limit
     * @param int $olderthanmonths
     * @param string $sincedate
     * @param array $filters
     * @return array the callsign list
     */
	public function countAllCallsignsByAirlines($limit = true, $olderthanmonths = 0, $sincedate = '', $filters = array())
	{
		global $globalDBdriver;
		$filter_query = $this->getFilter($filters,true,true);
		$query  = "SELECT DISTINCT spotter_output.airline_icao, spotter_output.ident, COUNT(spotter_output.ident) AS callsign_icao_count, spotter_output.airline_name  
                    FROM spotter_output".$filter_query." spotter_output.ident <> ''  AND spotter_output.airline_icao <> '' ";
		 if ($olderthanmonths > 0) {
			if ($globalDBdriver == 'mysql') $query .= 'AND date < DATE_SUB(UTC_TIMESTAMP(),INTERVAL '.$olderthanmonths.' MONTH) ';
			else $query .= "AND spotter_output.date < CURRENT_TIMESTAMP AT TIME ZONE 'UTC' - INTERVAL '".$olderthanmonths." MONTHS' ";
		}
		if ($sincedate != '') {
			if ($globalDBdriver == 'mysql') $query .= "AND spotter_output.date > '".$sincedate."' ";
			else $query .= "AND spotter_output.date > CAST('".$sincedate."' AS TIMESTAMP) ";
		}
		$query .= "GROUP BY spotter_output.airline_icao, spotter_output.ident, spotter_output.airline_name, spotter_output.airline_icao ORDER BY callsign_icao_count DESC";
		if ($limit) $query .= " LIMIT 10 OFFSET 0";
      		
		$sth = $this->db->prepare($query);
		$sth->execute();
      
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
			$query  = "SELECT DATE(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) AS date_name, count(*) as date_count
								FROM spotter_output";
			$query .= $this->getFilter($filters);
			$query .= " GROUP BY date_name 
								ORDER BY date_count DESC
								LIMIT 10 OFFSET 0";
		} else {
			$query  = "SELECT to_char(spotter_output.date AT TIME ZONE INTERVAL :offset,'YYYY-mm-dd') AS date_name, count(*) as date_count
								FROM spotter_output";
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
     * Counts all dates
     *
     * @param array $filters
     * @return array the date list
     */
	public function countAllDatesByAirlines($filters = array())
	{
		global $globalTimezone, $globalDBdriver;
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime();
			$offset = $datetime->format('P');
		} else $offset = '+00:00';
		$filter_query = $this->getFilter($filters,true,true);
		if ($globalDBdriver == 'mysql') {
			$query  = "SELECT spotter_output.airline_icao, DATE(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) AS date_name, count(*) as date_count
								FROM spotter_output".$filter_query." spotter_output.airline_icao <> '' 
								GROUP BY spotter_output.airline_icao, date_name 
								ORDER BY date_count DESC
								LIMIT 10 OFFSET 0";
		} else {
			$query  = "SELECT spotter_output.airline_icao, to_char(spotter_output.date AT TIME ZONE INTERVAL :offset,'YYYY-mm-dd') AS date_name, count(*) as date_count
								FROM spotter_output 
								WHERE spotter_output.airline_icao <> '' 
								GROUP BY spotter_output.airline_icao, date_name 
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
			$temp_array['airline_icao'] = $row['airline_icao'];

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
			$query  = "SELECT DATE(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) AS date_name, count(*) as date_count
								FROM spotter_output".$filter_query." spotter_output.date >= DATE_SUB(UTC_TIMESTAMP(),INTERVAL 7 DAY)";
			$query .= " GROUP BY date_name 
								ORDER BY spotter_output.date ASC";
			$query_data = array(':offset' => $offset);
		} else {
			$query  = "SELECT to_char(spotter_output.date AT TIME ZONE INTERVAL :offset,'YYYY-mm-dd') AS date_name, count(*) as date_count
								FROM spotter_output".$filter_query." spotter_output.date >= CURRENT_TIMESTAMP AT TIME ZONE INTERVAL :offset - INTERVAL '7 DAYS'";
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
			$query  = "SELECT DATE(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) AS date_name, count(*) as date_count
								FROM spotter_output".$filter_query." spotter_output.date >= DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 MONTH)";
			$query .= " GROUP BY date_name 
								ORDER BY date_name ASC";
			$query_data = array(':offset' => $offset);
		} else {
			$query  = "SELECT to_char(spotter_output.date AT TIME ZONE INTERVAL :offset,'YYYY-mm-dd') AS date_name, count(*) as date_count
								FROM spotter_output".$filter_query." spotter_output.date >= CURRENT_TIMESTAMP AT TIME ZONE INTERVAL :offset - INTERVAL '1 MONTHS'";
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
	public function countAllDatesLastMonthByAirlines($filters = array())
	{
		global $globalTimezone, $globalDBdriver;
		$filter_query = $this->getFilter($filters,true,true);
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime();
			$offset = $datetime->format('P');
		} else $offset = '+00:00';
		
		if ($globalDBdriver == 'mysql') {
			$query  = "SELECT spotter_output.airline_icao, DATE(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) AS date_name, count(*) as date_count
								FROM spotter_output".$filter_query." spotter_output.airline_icao <> '' AND spotter_output.date >= DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 MONTH)
								GROUP BY spotter_output.airline_icao, date_name 
								ORDER BY spotter_output.date ASC";
			$query_data = array(':offset' => $offset);
		} else {
			$query  = "SELECT spotter_output.airline_icao, to_char(spotter_output.date AT TIME ZONE INTERVAL :offset,'YYYY-mm-dd') AS date_name, count(*) as date_count
								FROM spotter_output".$filter_query." spotter_output.airline_icao <> '' AND spotter_output.date >= CURRENT_TIMESTAMP AT TIME ZONE INTERVAL :offset - INTERVAL '1 MONTHS'
								GROUP BY spotter_output.airline_icao, date_name 
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
			$temp_array['airline_icao'] = $row['airline_icao'];
          
			$date_array[] = $temp_array;
		}

		return $date_array;
	}


    /**
     * Counts all month
     *
     * @param array $filters
     * @param string $sincedate
     * @return array the month list
     */
	public function countAllMonths($filters = array(),$sincedate = '')
	{
		global $globalTimezone, $globalDBdriver;
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime();
			$offset = $datetime->format('P');
		} else $offset = '+00:00';

		if ($globalDBdriver == 'mysql') {
			$query  = "SELECT YEAR(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) AS year_name,MONTH(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) AS month_name, count(*) as date_count
				FROM spotter_output";
			if ($sincedate == '') {
				$query .= $this->getFilter($filters);
			} else {
				$query .= $this->getFilter($filters,true,true);
				$query .= " spotter_output.date > '".$sincedate."'";
			}
			$query .= " GROUP BY year_name, month_name ORDER BY date_count DESC";
		} else {
			$query  = "SELECT EXTRACT(YEAR FROM spotter_output.date AT TIME ZONE INTERVAL :offset) AS year_name,EXTRACT(MONTH FROM spotter_output.date AT TIME ZONE INTERVAL :offset) AS month_name, count(*) as date_count
								FROM spotter_output";
			if ($sincedate == '') {
				$query .= $this->getFilter($filters);
			} else {
				$query .= $this->getFilter($filters,true,true);
				$query .= " spotter_output.date > '".$sincedate."'";
			}
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
     * Counts all month
     *
     * @param array $filters
     * @param string $sincedate
     * @return array the month list
     */
	public function countAllMonthsByAirlines($filters = array(),$sincedate = '')
	{
		global $globalTimezone, $globalDBdriver;
		$filter_query = $this->getFilter($filters,true,true);
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime();
			$offset = $datetime->format('P');
		} else $offset = '+00:00';

		if ($globalDBdriver == 'mysql') {
			$query  = "SELECT spotter_output.airline_icao, YEAR(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) AS year_name,MONTH(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) AS month_name, count(*) as date_count
								FROM spotter_output".$filter_query." spotter_output.airline_icao <> ''";
			if ($sincedate != '') $query .= " AND spotter_output.date > '".$sincedate."'";
			$query .= " GROUP BY spotter_output.airline_icao, year_name, month_name 
								ORDER BY date_count DESC";
		} else {
			$query  = "SELECT spotter_output.airline_icao, EXTRACT(YEAR FROM spotter_output.date AT TIME ZONE INTERVAL :offset) AS year_name,EXTRACT(MONTH FROM spotter_output.date AT TIME ZONE INTERVAL :offset) AS month_name, count(*) as date_count
								FROM spotter_output 
								WHERE spotter_output.airline_icao <> ''";
			if ($sincedate != '') $query .= " AND spotter_output.date > '".$sincedate."'";
			$query .= " GROUP BY spotter_output.airline_icao, year_name, month_name 
								ORDER BY date_count DESC";
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
			$temp_array['airline_icao'] = $row['airline_icao'];

			$date_array[] = $temp_array;
		}

		return $date_array;
	}

    /**
     * Counts all military month
     *
     * @param array $filters
     * @param string $sincedate
     * @return array the month list
     */
	public function countAllMilitaryMonths($filters = array(), $sincedate = '')
	{
		global $globalTimezone, $globalDBdriver;
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime();
			$offset = $datetime->format('P');
		} else $offset = '+00:00';
		$filter_query = $this->getFilter($filters,true,true);
		if ($globalDBdriver == 'mysql') {
			$query  = "SELECT YEAR(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) AS year_name,MONTH(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) AS month_name, count(*) as date_count
								FROM spotter_output".$filter_query." spotter_output.airline_type = 'military'";
			if ($sincedate != '') $query .= " AND spotter_output.date > '".$sincedate."'";
			$query .= " GROUP BY year_name, month_name 
								ORDER BY date_count DESC";
		} else {
			$query  = "SELECT EXTRACT(YEAR FROM spotter_output.date AT TIME ZONE INTERVAL :offset) AS year_name,EXTRACT(MONTH FROM spotter_output.date AT TIME ZONE INTERVAL :offset) AS month_name, count(*) as date_count
								FROM spotter_output".$filter_query." spotter_output.airline_type = 'military'";
			if ($sincedate != '') $query .= " AND spotter_output.date > '".$sincedate."'";
			$query .= " GROUP BY year_name, month_name 
								ORDER BY date_count DESC";
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
     * Counts all month owners
     *
     * @param array $filters
     * @param string $sincedate
     * @return array the month list
     */
	public function countAllMonthsOwners($filters = array(), $sincedate = '')
	{
		global $globalTimezone, $globalDBdriver;
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime();
			$offset = $datetime->format('P');
		} else $offset = '+00:00';
		$filter_query = $this->getFilter($filters,true,true);

		if ($globalDBdriver == 'mysql') {
			$query  = "SELECT YEAR(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) AS year_name,MONTH(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) AS month_name, count(distinct owner_name) as date_count
								FROM spotter_output".$filter_query." owner_name <> ''";
			if ($sincedate != '') $query .= " AND spotter_output.date > '".$sincedate."'";
			$query .= " GROUP BY year_name, month_name ORDER BY date_count DESC";
		} else {
			$query  = "SELECT EXTRACT(YEAR FROM spotter_output.date AT TIME ZONE INTERVAL :offset) AS year_name,EXTRACT(MONTH FROM spotter_output.date AT TIME ZONE INTERVAL :offset) AS month_name, count(distinct owner_name) as date_count
								FROM spotter_output".$filter_query." owner_name <> ''";
			if ($sincedate != '') $query .= " AND spotter_output.date > '".$sincedate."'";
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
     * Counts all month owners
     *
     * @param array $filters
     * @param string $sincedate
     * @return array the month list
     */
	public function countAllMonthsOwnersByAirlines($filters = array(),$sincedate = '')
	{
		global $globalTimezone, $globalDBdriver;
		$filter_query = $this->getFilter($filters,true,true);
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime();
			$offset = $datetime->format('P');
		} else $offset = '+00:00';

		if ($globalDBdriver == 'mysql') {
			$query  = "SELECT spotter_output.airline_icao, YEAR(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) AS year_name,MONTH(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) AS month_name, count(distinct owner_name) as date_count
								FROM spotter_output".$filter_query." owner_name <> '' AND spotter_output.airline_icao <> ''";
			if ($sincedate != '') $query .= " AND spotter_output.date > '".$sincedate."'";
			$query .= " GROUP BY spotter_output.airline_icao, year_name, month_name ORDER BY date_count DESC";
		} else {
			$query  = "SELECT spotter_output.airline_icao, EXTRACT(YEAR FROM spotter_output.date AT TIME ZONE INTERVAL :offset) AS year_name,EXTRACT(MONTH FROM spotter_output.date AT TIME ZONE INTERVAL :offset) AS month_name, count(distinct owner_name) as date_count
								FROM spotter_output".$filter_query." owner_name <> '' AND spotter_output.airline_icao <> ''";
			if ($sincedate != '') $query .= " AND spotter_output.date > '".$sincedate."'";
			$query .= " GROUP BY spotter_output.airline_icao, year_name, month_name ORDER BY date_count DESC";
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
			$temp_array['airline_icao'] = $row['airline_icao'];

			$date_array[] = $temp_array;
		}

		return $date_array;
	}

    /**
     * Counts all month pilot
     *
     * @param array $filters
     * @param string $sincedate
     * @return array the month list
     */
	public function countAllMonthsPilots($filters = array(), $sincedate = '')
	{
		global $globalTimezone, $globalDBdriver;
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime();
			$offset = $datetime->format('P');
		} else $offset = '+00:00';
		$filter_query = $this->getFilter($filters,true,true);

		if ($globalDBdriver == 'mysql') {
			$query  = "SELECT YEAR(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) AS year_name,MONTH(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) AS month_name, count(distinct pilot_id) as date_count
								FROM spotter_output".$filter_query." pilot_id <> '' AND pilot_id IS NOT NULL";
			if ($sincedate != '') $query .= " AND spotter_output.date > '".$sincedate."'";
			$query .= " GROUP BY year_name, month_name ORDER BY date_count DESC";
		} else {
			$query  = "SELECT EXTRACT(YEAR FROM spotter_output.date AT TIME ZONE INTERVAL :offset) AS year_name,EXTRACT(MONTH FROM spotter_output.date AT TIME ZONE INTERVAL :offset) AS month_name, count(distinct pilot_id) as date_count
								FROM spotter_output".$filter_query." pilot_id <> '' AND pilot_id IS NOT NULL";
			if ($sincedate != '') $query .= " AND spotter_output.date > '".$sincedate."'";
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
     * Counts all month pilot
     *
     * @param array $filters
     * @param string $sincedate
     * @return array the month list
     */
	public function countAllMonthsPilotsByAirlines($filters = array(), $sincedate = '')
	{
		global $globalTimezone, $globalDBdriver;
		$filter_query = $this->getFilter($filters,true,true);
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime();
			$offset = $datetime->format('P');
		} else $offset = '+00:00';

		if ($globalDBdriver == 'mysql') {
			$query  = "SELECT spotter_output.airline_icao, YEAR(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) AS year_name,MONTH(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) AS month_name, count(distinct pilot_id) as date_count
								FROM spotter_output".$filter_query." spotter_output.airline_icao <> '' AND pilot_id <> '' AND pilot_id IS NOT NULL";
			if ($sincedate != '') $query .= " AND spotter_output.date > '".$sincedate."'";
			$query .= " GROUP BY spotter_output.airline_icao,year_name, month_name ORDER BY date_count DESC";
		} else {
			$query  = "SELECT spotter_output.airline_icao, EXTRACT(YEAR FROM spotter_output.date AT TIME ZONE INTERVAL :offset) AS year_name,EXTRACT(MONTH FROM spotter_output.date AT TIME ZONE INTERVAL :offset) AS month_name, count(distinct pilot_id) as date_count
								FROM spotter_output".$filter_query." spotter_output.airline_icao <> '' AND pilot_id <> '' AND pilot_id IS NOT NULL";
			if ($sincedate != '') $query .= " AND spotter_output.date > '".$sincedate."'";
			$query .= " GROUP BY spotter_output.airline_icao, year_name, month_name ORDER BY date_count DESC";
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
			$temp_array['airline_icao'] = $row['airline_icao'];

			$date_array[] = $temp_array;
		}

		return $date_array;
	}

    /**
     * Counts all month airline
     *
     * @param array $filters
     * @param string $sincedate
     * @return array the month list
     */
	public function countAllMonthsAirlines($filters = array(), $sincedate = '')
	{
		global $globalTimezone, $globalDBdriver;
		$filter_query = $this->getFilter($filters,true,true);
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime();
			$offset = $datetime->format('P');
		} else $offset = '+00:00';

		if ($globalDBdriver == 'mysql') {
			$query  = "SELECT YEAR(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) AS year_name,MONTH(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) AS month_name, count(distinct airline_icao) as date_count
								FROM spotter_output".$filter_query." airline_icao <> ''";
			if ($sincedate != '') $query .= " AND spotter_output.date > '".$sincedate."'";
			$query .= " GROUP BY year_name, month_name ORDER BY date_count DESC";
		} else {
			$query  = "SELECT EXTRACT(YEAR FROM spotter_output.date AT TIME ZONE INTERVAL :offset) AS year_name,EXTRACT(MONTH FROM spotter_output.date AT TIME ZONE INTERVAL :offset) AS month_name, count(distinct airline_icao) as date_count
								FROM spotter_output".$filter_query." airline_icao <> ''";
			if ($sincedate != '') $query .= " AND spotter_output.date > '".$sincedate."'";
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
     * Counts all month aircraft
     *
     * @param array $filters
     * @param string $sincedate
     * @return array the month list
     */
	public function countAllMonthsAircrafts($filters = array(),$sincedate = '')
	{
		global $globalTimezone, $globalDBdriver;
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime();
			$offset = $datetime->format('P');
		} else $offset = '+00:00';
		$filter_query = $this->getFilter($filters,true,true);

		if ($globalDBdriver == 'mysql') {
			$query  = "SELECT YEAR(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) AS year_name,MONTH(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) AS month_name, count(distinct aircraft_icao) as date_count
								FROM spotter_output".$filter_query." aircraft_icao <> ''";
			if ($sincedate != '') $query .= " AND spotter_output.date > '".$sincedate."'";
			$query .= " GROUP BY year_name, month_name ORDER BY date_count DESC";
		} else {
			$query  = "SELECT EXTRACT(YEAR FROM spotter_output.date AT TIME ZONE INTERVAL :offset) AS year_name,EXTRACT(MONTH FROM spotter_output.date AT TIME ZONE INTERVAL :offset) AS month_name, count(distinct aircraft_icao) as date_count
								FROM spotter_output".$filter_query." aircraft_icao <> ''";
			if ($sincedate != '') $query .= " AND spotter_output.date > '".$sincedate."'";
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
     * Counts all month aircraft
     *
     * @param array $filters
     * @param string $sincedate
     * @return array the month list
     */
	public function countAllMonthsAircraftsByAirlines($filters = array(), $sincedate = '')
	{
		global $globalTimezone, $globalDBdriver;
		$filter_query = $this->getFilter($filters,true,true);
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime();
			$offset = $datetime->format('P');
		} else $offset = '+00:00';

		if ($globalDBdriver == 'mysql') {
			$query  = "SELECT spotter_output.airline_icao,YEAR(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) AS year_name,MONTH(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) AS month_name, count(distinct aircraft_icao) as date_count
								FROM spotter_output".$filter_query." aircraft_icao <> ''  AND spotter_output.airline_icao <> ''";
			if ($sincedate != '') $query .= " AND spotter_output.date > '".$sincedate."'";
			$query .= " GROUP BY spotter_output.airline_icao, year_name, month_name ORDER BY date_count DESC";
		} else {
			$query  = "SELECT spotter_output.airline_icao, EXTRACT(YEAR FROM spotter_output.date AT TIME ZONE INTERVAL :offset) AS year_name,EXTRACT(MONTH FROM spotter_output.date AT TIME ZONE INTERVAL :offset) AS month_name, count(distinct aircraft_icao) as date_count
								FROM spotter_output".$filter_query." aircraft_icao <> '' AND spotter_output.airline_icao <> ''";
			if ($sincedate != '') $query .= " AND spotter_output.date > '".$sincedate."'";
			$query .= " GROUP BY spotter_output.airline_icao, year_name, month_name ORDER BY date_count DESC";
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
			$temp_array['airline_icao'] = $row['airline_icao'];

			$date_array[] = $temp_array;
		}

		return $date_array;
	}

    /**
     * Counts all month real arrival
     *
     * @param array $filters
     * @param string $sincedate
     * @return array the month list
     */
	public function countAllMonthsRealArrivals($filters = array(), $sincedate = '')
	{
		global $globalTimezone, $globalDBdriver;
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime();
			$offset = $datetime->format('P');
		} else $offset = '+00:00';
		$filter_query = $this->getFilter($filters,true,true);

		if ($globalDBdriver == 'mysql') {
			$query  = "SELECT YEAR(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) AS year_name,MONTH(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) AS month_name, count(real_arrival_airport_icao) as date_count
								FROM spotter_output".$filter_query." real_arrival_airport_icao <> ''";
			if ($sincedate != '') $query .= " AND spotter_output.date > '".$sincedate."'";
			$query .= " GROUP BY year_name, month_name ORDER BY date_count DESC";
		} else {
			$query  = "SELECT EXTRACT(YEAR FROM spotter_output.date AT TIME ZONE INTERVAL :offset) AS year_name,EXTRACT(MONTH FROM spotter_output.date AT TIME ZONE INTERVAL :offset) AS month_name, count(real_arrival_airport_icao) as date_count
								FROM spotter_output".$filter_query." real_arrival_airport_icao <> ''";
			if ($sincedate != '') $query .= " AND spotter_output.date > '".$sincedate."'";
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
     * Counts all month real arrival
     *
     * @param array $filters
     * @param string $sincedate
     * @return array the month list
     */
	public function countAllMonthsRealArrivalsByAirlines($filters = array(),$sincedate = '')
	{
		global $globalTimezone, $globalDBdriver;
		$filter_query = $this->getFilter($filters,true,true);
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime();
			$offset = $datetime->format('P');
		} else $offset = '+00:00';

		if ($globalDBdriver == 'mysql') {
			$query  = "SELECT spotter_output.airline_icao, YEAR(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) AS year_name,MONTH(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) AS month_name, count(real_arrival_airport_icao) as date_count
								FROM spotter_output".$filter_query." real_arrival_airport_icao <> '' AND spotter_output.airline_icao <> ''";
			if ($sincedate != '') $query .= " AND spotter_output.date > '".$sincedate."'";
			$query .= " GROUP BY spotter_output.airline_icao, year_name, month_name ORDER BY date_count DESC";
		} else {
			$query  = "SELECT spotter_output.airline_icao, EXTRACT(YEAR FROM spotter_output.date AT TIME ZONE INTERVAL :offset) AS year_name,EXTRACT(MONTH FROM spotter_output.date AT TIME ZONE INTERVAL :offset) AS month_name, count(real_arrival_airport_icao) as date_count
								FROM spotter_output".$filter_query." real_arrival_airport_icao <> '' AND spotter_output.airline_icao <> ''";
			if ($sincedate != '') $query .= " AND spotter_output.date > '".$sincedate."'";
			$query .= " GROUP BY spotter_output.airline_icao, year_name, month_name ORDER BY date_count DESC";
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
			$temp_array['airline_icao'] = $row['airline_icao'];

			$date_array[] = $temp_array;
		}

		return $date_array;
	}


    /**
     * Counts all dates during the last year
     *
     * @param array $filters
     * @param string $sincedate
     * @return array the date list
     */
	public function countAllMonthsLastYear($filters = array(), $sincedate = '')
	{
		global $globalTimezone, $globalDBdriver;
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime();
			$offset = $datetime->format('P');
		} else $offset = '+00:00';
		$filter_query = $this->getFilter($filters,true,true);
		if ($globalDBdriver == 'mysql') {
			$query  = "SELECT MONTH(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) AS month_name, YEAR(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) AS year_name, count(*) as date_count
								FROM spotter_output".$filter_query." spotter_output.date >= DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 YEAR)";
			if ($sincedate != '') $query .= " AND spotter_output.date > '".$sincedate."'";
			$query .= " GROUP BY year_name, month_name
								ORDER BY year_name, month_name ASC";
			$query_data = array(':offset' => $offset);
		} else {
			$query  = "SELECT EXTRACT(MONTH FROM spotter_output.date AT TIME ZONE INTERVAL :offset) AS month_name, EXTRACT(YEAR FROM spotter_output.date AT TIME ZONE INTERVAL :offset) AS year_name, count(*) as date_count
								FROM spotter_output".$filter_query." spotter_output.date >= CURRENT_TIMESTAMP AT TIME ZONE INTERVAL :offset - INTERVAL '1 YEARS'";
			if ($sincedate != '') $query .= " AND spotter_output.date > '".$sincedate."'";
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
			$query  = "SELECT HOUR(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) AS hour_name, count(*) as hour_count
								FROM spotter_output";
			$query .= $this->getFilter($filters);
			$query .= " GROUP BY hour_name 
								".$orderby_sql;

/*		$query  = "SELECT HOUR(spotter_output.date) AS hour_name, count(*) as hour_count
								FROM spotter_output 
								GROUP BY hour_name 
								".$orderby_sql."
								LIMIT 10 OFFSET 00";
  */    
		$query_data = array(':offset' => $offset);
		} else {
			$query  = "SELECT EXTRACT(HOUR FROM spotter_output.date AT TIME ZONE INTERVAL :offset) AS hour_name, count(*) as hour_count
								FROM spotter_output";
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
     * Counts all hours
     *
     * @param $orderby
     * @param array $filters
     * @return array the hour list
     */
	public function countAllHoursByAirlines($orderby, $filters = array())
	{
		global $globalTimezone, $globalDBdriver;
		$filter_query = $this->getFilter($filters,true,true);
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
			$query  = "SELECT spotter_output.airline_icao, HOUR(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) AS hour_name, count(*) as hour_count
								FROM spotter_output".$filter_query." spotter_output.airline_icao <> '' 
								GROUP BY spotter_output.airline_icao, hour_name 
								".$orderby_sql;

/*		$query  = "SELECT HOUR(spotter_output.date) AS hour_name, count(*) as hour_count
								FROM spotter_output 
								GROUP BY hour_name 
								".$orderby_sql."
								LIMIT 10 OFFSET 00";
  */    
		$query_data = array(':offset' => $offset);
		} else {
			$query  = "SELECT spotter_output.airline_icao, EXTRACT(HOUR FROM spotter_output.date AT TIME ZONE INTERVAL :offset) AS hour_name, count(*) as hour_count
								FROM spotter_output".$filter_query." spotter_output.airline_icao <> '' 
								GROUP BY spotter_output.airline_icao, hour_name 
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
			$temp_array['airline_icao'] = $row['airline_icao'];
          
			$hour_array[] = $temp_array;
		}

		return $hour_array;
	}


    /**
     * Counts all hours by airline
     *
     * @param $airline_icao
     * @param array $filters
     * @return array the hour list
     */
	public function countAllHoursByAirline($airline_icao, $filters = array())
	{
		global $globalTimezone, $globalDBdriver;
		if ($airline_icao != '') $filter_query = $this->getFilter($filters,true,true);
		else $filter_query = $this->getFilter($filters);
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime();
			$offset = $datetime->format('P');
		} else $offset = '+00:00';

		$airline_icao = filter_var($airline_icao,FILTER_SANITIZE_STRING);

		if ($globalDBdriver == 'mysql') {
			$query  = "SELECT HOUR(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) AS hour_name, count(*) as hour_count
				FROM spotter_output".$filter_query;
			if ($airline_icao != '') $query .= " spotter_output.airline_icao = :airline_icao";
			$query .= " GROUP BY hour_name ORDER BY hour_name ASC";
		} else {
			$query  = "SELECT EXTRACT(HOUR FROM spotter_output.date AT TIME ZONE INTERVAL :offset) AS hour_name, count(*) as hour_count
				FROM spotter_output".$filter_query;
			if ($airline_icao != '') $query .= " spotter_output.airline_icao = :airline_icao";
			$query .= " GROUP BY hour_name ORDER BY hour_name ASC";
		}

		$sth = $this->db->prepare($query);
		if ($airline_icao) $sth->execute(array(':airline_icao' => $airline_icao,':offset' => $offset));
		else $sth->execute(array(':offset' => $offset));
      
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
     * Counts all hours by aircraft
     *
     * @param $aircraft_icao
     * @param array $filters
     * @return array the hour list
     */
	public function countAllHoursByAircraft($aircraft_icao, $filters = array())
	{
		global $globalTimezone, $globalDBdriver;
		$filter_query = $this->getFilter($filters,true,true);
		$aircraft_icao = filter_var($aircraft_icao,FILTER_SANITIZE_STRING);
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime();
			$offset = $datetime->format('P');
		} else $offset = '+00:00';

		if ($globalDBdriver == 'mysql') {
			$query  = "SELECT HOUR(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) AS hour_name, count(*) as hour_count
								FROM spotter_output".$filter_query." spotter_output.aircraft_icao = :aircraft_icao
								GROUP BY hour_name 
								ORDER BY hour_name ASC";
		} else {
			$query  = "SELECT EXTRACT(HOUR FROM spotter_output.date AT TIME ZONE INTERVAL :offset) AS hour_name, count(*) as hour_count
								FROM spotter_output".$filter_query." spotter_output.aircraft_icao = :aircraft_icao
								GROUP BY hour_name 
								ORDER BY hour_name ASC";
		}
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':aircraft_icao' => $aircraft_icao,':offset' => $offset));
      
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
     * Counts all hours by aircraft registration
     *
     * @param $registration
     * @param array $filters
     * @return array the hour list
     */
	public function countAllHoursByRegistration($registration, $filters = array())
	{
		global $globalTimezone, $globalDBdriver;
		$filter_query = $this->getFilter($filters,true,true);
		$registration = filter_var($registration,FILTER_SANITIZE_STRING);
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime();
			$offset = $datetime->format('P');
		} else $offset = '+00:00';

		if ($globalDBdriver == 'mysql') {
			$query  = "SELECT HOUR(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) AS hour_name, count(*) as hour_count
								FROM spotter_output".$filter_query." spotter_output.registration = :registration
								GROUP BY hour_name 
								ORDER BY hour_name ASC";
		} else {
			$query  = "SELECT EXTRACT(HOUR FROM spotter_output.date AT TIME ZONE INTERVAL :offset) AS hour_name, count(*) as hour_count
								FROM spotter_output".$filter_query." spotter_output.registration = :registration
								GROUP BY hour_name 
								ORDER BY hour_name ASC";
		}
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':registration' => $registration,':offset' => $offset));
      
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
     * Counts all hours by airport
     *
     * @param $airport_icao
     * @param array $filters
     * @return array the hour list
     */
	public function countAllHoursByAirport($airport_icao, $filters = array())
	{
		global $globalTimezone, $globalDBdriver;
		$filter_query = $this->getFilter($filters,true,true);
		$airport_icao = filter_var($airport_icao,FILTER_SANITIZE_STRING);
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime();
			$offset = $datetime->format('P');
		} else $offset = '+00:00';

		if ($globalDBdriver == 'mysql') {
			$query  = "SELECT HOUR(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) AS hour_name, count(*) as hour_count
								FROM spotter_output".$filter_query." (spotter_output.departure_airport_icao = :airport_icao OR spotter_output.arrival_airport_icao = :airport_icao)
								GROUP BY hour_name 
								ORDER BY hour_name ASC";
		} else {
			$query  = "SELECT EXTRACT(HOUR FROM spotter_output.date AT TIME ZONE INTERVAL :offset) AS hour_name, count(*) as hour_count
								FROM spotter_output".$filter_query." (spotter_output.departure_airport_icao = :airport_icao OR spotter_output.arrival_airport_icao = :airport_icao)
								GROUP BY hour_name 
								ORDER BY hour_name ASC";
		}
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':airport_icao' => $airport_icao,':offset' => $offset));
      
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
     * Counts all hours by manufacturer
     *
     * @param $aircraft_manufacturer
     * @param array $filters
     * @return array the hour list
     */
	public function countAllHoursByManufacturer($aircraft_manufacturer,$filters =array())
	{
		global $globalTimezone, $globalDBdriver;
		$filter_query = $this->getFilter($filters,true,true);
		$aircraft_manufacturer = filter_var($aircraft_manufacturer,FILTER_SANITIZE_STRING);
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime();
			$offset = $datetime->format('P');
		} else $offset = '+00:00';

		if ($globalDBdriver == 'mysql') {
			$query  = "SELECT HOUR(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) AS hour_name, count(*) as hour_count
								FROM spotter_output".$filter_query." spotter_output.aircraft_manufacturer = :aircraft_manufacturer
								GROUP BY hour_name 
								ORDER BY hour_name ASC";
		} else {
			$query  = "SELECT EXTRACT(HOUR FROM spotter_output.date AT TIME ZONE INTERVAL :offset) AS hour_name, count(*) as hour_count
								FROM spotter_output".$filter_query." spotter_output.aircraft_manufacturer = :aircraft_manufacturer
								GROUP BY hour_name 
								ORDER BY hour_name ASC";
		}
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':aircraft_manufacturer' => $aircraft_manufacturer,':offset' => $offset));
      
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
			$query  = "SELECT HOUR(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) AS hour_name, count(*) as hour_count
								FROM spotter_output".$filter_query." DATE(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) = :date
								GROUP BY hour_name 
								ORDER BY hour_name ASC";
		} else {
			$query  = "SELECT EXTRACT(HOUR FROM spotter_output.date AT TIME ZONE INTERVAL :offset) AS hour_name, count(*) as hour_count
								FROM spotter_output".$filter_query." to_char(spotter_output.date AT TIME ZONE INTERVAL :offset, 'YYYY-mm-dd') = :date
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
			$query  = "SELECT HOUR(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) AS hour_name, count(*) as hour_count
								FROM spotter_output".$filter_query." spotter_output.ident = :ident 
								GROUP BY hour_name 
								ORDER BY hour_name ASC";
		} else {
			$query  = "SELECT EXTRACT(HOUR FROM spotter_output.date AT TIME ZONE INTERVAL :offset) AS hour_name, count(*) as hour_count
								FROM spotter_output".$filter_query." spotter_output.ident = :ident 
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
     * Counts all hours by a owner
     *
     * @param $owner
     * @param array $filters
     * @return array the hour list
     */
	public function countAllHoursByOwner($owner, $filters = array())
	{
		global $globalTimezone, $globalDBdriver;
		$filter_query = $this->getFilter($filters,true,true);
		$owner = filter_var($owner,FILTER_SANITIZE_STRING);
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime();
			$offset = $datetime->format('P');
		} else $offset = '+00:00';

		if ($globalDBdriver == 'mysql') {
			$query  = "SELECT HOUR(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) AS hour_name, count(*) as hour_count
								FROM spotter_output".$filter_query." spotter_output.owner_name = :owner 
								GROUP BY hour_name 
								ORDER BY hour_name ASC";
		} else {
			$query  = "SELECT EXTRACT(HOUR FROM spotter_output.date AT TIME ZONE INTERVAL :offset) AS hour_name, count(*) as hour_count
								FROM spotter_output".$filter_query." spotter_output.owner_name = :owner 
								GROUP BY hour_name 
								ORDER BY hour_name ASC";
		}
      
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':owner' => $owner,':offset' => $offset));
      
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
     * Counts all hours by a pilot
     *
     * @param $pilot
     * @param array $filters
     * @return array the hour list
     */
	public function countAllHoursByPilot($pilot, $filters = array())
	{
		global $globalTimezone, $globalDBdriver;
		$filter_query = $this->getFilter($filters,true,true);
		$pilot = filter_var($pilot,FILTER_SANITIZE_STRING);
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime();
			$offset = $datetime->format('P');
		} else $offset = '+00:00';

		if ($globalDBdriver == 'mysql') {
			$query  = "SELECT HOUR(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) AS hour_name, count(*) as hour_count
								FROM spotter_output".$filter_query." (spotter_output.pilot_name = :pilot OR spotter_output.pilot_id = :pilot) 
								GROUP BY hour_name 
								ORDER BY hour_name ASC";
		} else {
			$query  = "SELECT EXTRACT(HOUR FROM spotter_output.date AT TIME ZONE INTERVAL :offset) AS hour_name, count(*) as hour_count
								FROM spotter_output".$filter_query." (spotter_output.pilot_name = :pilot OR spotter_output.pilot_id = :pilot) 
								GROUP BY hour_name 
								ORDER BY hour_name ASC";
		}
      
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':pilot' => $pilot,':offset' => $offset));
      
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
     * Counts all hours by route
     *
     * @param $departure_airport_icao
     * @param $arrival_airport_icao
     * @param array $filters
     * @return array the hour list
     */
	public function countAllHoursByRoute($departure_airport_icao, $arrival_airport_icao, $filters =array())
	{
		global $globalTimezone, $globalDBdriver;
		$filter_query = $this->getFilter($filters,true,true);
		$departure_airport_icao = filter_var($departure_airport_icao,FILTER_SANITIZE_STRING);
		$arrival_airport_icao = filter_var($arrival_airport_icao,FILTER_SANITIZE_STRING);
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime();
			$offset = $datetime->format('P');
		} else $offset = '+00:00';

		if ($globalDBdriver == 'mysql') {
			$query  = "SELECT HOUR(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) AS hour_name, count(*) as hour_count
								FROM spotter_output".$filter_query." (spotter_output.departure_airport_icao = :departure_airport_icao) AND (spotter_output.arrival_airport_icao = :arrival_airport_icao)
								GROUP BY hour_name 
								ORDER BY hour_name ASC";
		} else {
			$query  = "SELECT EXTRACT(HOUR FROM spotter_output.date AT TIME ZONE INTERVAL :offset) AS hour_name, count(*) as hour_count
								FROM spotter_output".$filter_query." (spotter_output.departure_airport_icao = :departure_airport_icao) AND (spotter_output.arrival_airport_icao = :arrival_airport_icao)
								GROUP BY hour_name 
								ORDER BY hour_name ASC";
		}
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':departure_airport_icao' => $departure_airport_icao,':arrival_airport_icao' => $arrival_airport_icao,':offset' => $offset));
      
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
     * Counts all hours by country
     *
     * @param $country
     * @param array $filters
     * @return array the hour list
     */
	public function countAllHoursByCountry($country, $filters = array())
	{
		global $globalTimezone, $globalDBdriver;
		$filter_query = $this->getFilter($filters,true,true);
		$country = filter_var($country,FILTER_SANITIZE_STRING);
		if ($globalTimezone != '') {
			date_default_timezone_set($globalTimezone);
			$datetime = new DateTime();
			$offset = $datetime->format('P');
		} else $offset = '+00:00';

		if ($globalDBdriver == 'mysql') {
			$query  = "SELECT HOUR(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) AS hour_name, count(*) as hour_count
								FROM spotter_output".$filter_query." ((spotter_output.departure_airport_country = :country) OR (spotter_output.arrival_airport_country = :country)) OR spotter_output.airline_country = :country
								GROUP BY hour_name 
								ORDER BY hour_name ASC";
		} else {
			$query  = "SELECT EXTRACT(HOUR FROM spotter_output.date AT TIME ZONE INTERVAL :offset) AS hour_name, count(*) as hour_count
								FROM spotter_output".$filter_query." ((spotter_output.departure_airport_country = :country) OR (spotter_output.arrival_airport_country = :country)) OR spotter_output.airline_country = :country
								GROUP BY hour_name 
								ORDER BY hour_name ASC";
		}
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':country' => $country,':offset' => $offset));
      
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
     * Counts all aircraft that have flown over
     *
     * @param array $filters
     * @param string $year
     * @param string $month
     * @return Integer the number of aircrafts
     */
	public function countOverallAircrafts($filters = array(),$year = '',$month = '')
	{
		global $globalDBdriver;
		$filter_query = $this->getFilter($filters,true,true);
		$query  = "SELECT COUNT(DISTINCT spotter_output.aircraft_icao) AS aircraft_count  
                    FROM spotter_output".$filter_query." spotter_output.ident <> ''";
		$query_values = array();
		if ($year != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND YEAR(spotter_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			} else {
				$query .= " AND EXTRACT(YEAR FROM spotter_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			}
		}
		if ($month != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND MONTH(spotter_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			} else {
				$query .= " AND EXTRACT(MONTH FROM spotter_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			}
		}

		$sth = $this->db->prepare($query);
		$sth->execute($query_values);
		return $sth->fetchColumn();
	}

    /**
     * Counts all flight that really arrival
     *
     * @param array $filters
     * @param string $year
     * @param string $month
     * @return Integer the number of aircrafts
     */
	public function countOverallArrival($filters = array(),$year = '',$month = '')
	{
		global $globalDBdriver;
		$filter_query = $this->getFilter($filters,true,true);
		$query  = "SELECT COUNT(spotter_output.real_arrival_airport_icao) AS arrival_count  
                    FROM spotter_output".$filter_query." spotter_output.arrival_airport_icao <> ''";
		$query_values = array();
		if ($year != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND YEAR(spotter_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			} else {
				$query .= " AND EXTRACT(YEAR FROM spotter_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			}
		}
		if ($month != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND MONTH(spotter_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			} else {
				$query .= " AND EXTRACT(MONTH FROM spotter_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			}
		}
		
		$sth = $this->db->prepare($query);
		$sth->execute($query_values);
		return $sth->fetchColumn();
	}

    /**
     * Counts all pilots that have flown over
     *
     * @param array $filters
     * @param string $year
     * @param string $month
     * @return Integer the number of pilots
     */
	public function countOverallPilots($filters = array(),$year = '',$month = '')
	{
		global $globalDBdriver;
		$filter_query = $this->getFilter($filters,true,true);
		$query  = "SELECT COUNT(DISTINCT spotter_output.pilot_id) AS pilot_count  
                    FROM spotter_output".$filter_query." spotter_output.pilot_id <> ''";
		$query_values = array();
		if ($year != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND YEAR(spotter_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			} else {
				$query .= " AND EXTRACT(YEAR FROM spotter_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			}
		}
		if ($month != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND MONTH(spotter_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			} else {
				$query .= " AND EXTRACT(MONTH FROM spotter_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			}
		}
		$sth = $this->db->prepare($query);
		$sth->execute($query_values);
		return $sth->fetchColumn();
	}

    /**
     * Counts all owners that have flown over
     *
     * @param array $filters
     * @param string $year
     * @param string $month
     * @return Integer the number of owners
     */
	public function countOverallOwners($filters = array(),$year = '',$month = '')
	{
		global $globalDBdriver;
		$filter_query = $this->getFilter($filters,true,true);
		$query  = "SELECT COUNT(DISTINCT spotter_output.owner_name) AS owner_count  
                    FROM spotter_output".$filter_query." spotter_output.owner_name <> ''";
		$query_values = array();
		if ($year != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND YEAR(spotter_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			} else {
				$query .= " AND EXTRACT(YEAR FROM spotter_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			}
		}
		if ($month != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND MONTH(spotter_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			} else {
				$query .= " AND EXTRACT(MONTH FROM spotter_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			}
		}
		$sth = $this->db->prepare($query);
		$sth->execute($query_values);
		return $sth->fetchColumn();
	}


    /**
     * Counts all flights that have flown over
     *
     * @param array $filters
     * @param string $year
     * @param string $month
     * @return Integer the number of flights
     */
	public function countOverallFlights($filters = array(),$year = '',$month = '')
	{
		global $globalDBdriver;
		$queryi  = "SELECT COUNT(spotter_output.spotter_id) AS flight_count FROM spotter_output";
		$query_values = array();
		$query = '';
		if ($year != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND YEAR(spotter_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			} else {
				$query .= " AND EXTRACT(YEAR FROM spotter_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			}
		}
		if ($month != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND MONTH(spotter_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			} else {
				$query .= " AND EXTRACT(MONTH FROM spotter_output.date) = :month";
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
     * Counts all military flights that have flown over
     *
     * @param array $filters
     * @param string $year
     * @param string $month
     * @return Integer the number of flights
     */
	public function countOverallMilitaryFlights($filters = array(),$year = '',$month = '')
	{
		global $globalDBdriver;
		$filter_query = $this->getFilter($filters,true,true);
		$query  = "SELECT COUNT(spotter_output.spotter_id) AS flight_count  
                    FROM airlines,spotter_output".$filter_query." spotter_output.airline_icao = airlines.icao AND airlines.type = 'military'";
		$query_values = array();
		if ($year != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND YEAR(spotter_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			} else {
				$query .= " AND EXTRACT(YEAR FROM spotter_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			}
		}
		if ($month != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND MONTH(spotter_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			} else {
				$query .= " AND EXTRACT(MONTH FROM spotter_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			}
		}
      
		$sth = $this->db->prepare($query);
		$sth->execute($query_values);
		return $sth->fetchColumn();
	}


    /**
     * Counts all airlines that have flown over
     *
     * @param array $filters
     * @param string $year
     * @param string $month
     * @return Integer the number of airlines
     */
	public function countOverallAirlines($filters = array(),$year = '',$month = '')
	{
		global $globalDBdriver;
		$queryi  = "SELECT COUNT(DISTINCT spotter_output.airline_name) AS airline_count 
							FROM spotter_output";
      
		$query_values = array();
		$query = '';
		if ($year != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND YEAR(spotter_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			} else {
				$query .= " AND EXTRACT(YEAR FROM spotter_output.date) = :year";
				$query_values = array_merge($query_values,array(':year' => $year));
			}
		}
		if ($month != '') {
			if ($globalDBdriver == 'mysql') {
				$query .= " AND MONTH(spotter_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			} else {
				$query .= " AND EXTRACT(MONTH FROM spotter_output.date) = :month";
				$query_values = array_merge($query_values,array(':month' => $month));
			}
		}
                if ($query == '') $queryi .= $this->getFilter($filters);
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
			$query  = "SELECT HOUR(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) AS hour_name, count(*) as hour_count
								FROM spotter_output".$filter_query." DATE(CONVERT_TZ(spotter_output.date,'+00:00', :offset)) = CURDATE()
								GROUP BY hour_name 
								ORDER BY hour_name ASC";
		} else {
			$query  = "SELECT EXTRACT(HOUR FROM spotter_output.date AT TIME ZONE INTERVAL :offset) AS hour_name, count(*) as hour_count
								FROM spotter_output".$filter_query." to_char(spotter_output.date AT TIME ZONE INTERVAL :offset,'YYYY-mm-dd') = CAST(NOW() AS date)
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
     * Gets all the spotter information based on calculated upcoming flights
     *
     * @param string $limit
     * @param string $sort
     * @param array $filters
     * @return array the spotter information
     */
	public function getUpcomingFlights($limit = '', $sort = '', $filters = array())
	{
		global $global_query, $globalDBdriver, $globalTimezone;
		$filter_query = $this->getFilter($filters,true,true);
		date_default_timezone_set('UTC');
		$limit_query = '';
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
		$currentHour = date("G");
		$next3Hours = date("G", strtotime("+3 hour"));
		//if the next 3 hours is already equal to/past midnight, we limit it to stay there, otherwise the query will fail
		if ($currentHour >= 21 && $next3Hours >= 00)
		{
			$next3Hours = 24;
		}
		$currentDayofWeek = date("l");
		if ($globalDBdriver == 'mysql') {
			if ($sort != "")
			{
				$search_orderby_array = $this->getOrderBy();
				$orderby_query = $search_orderby_array[$sort]['sql'];
			} else {
				$orderby_query = " ORDER BY HOUR(spotter_output.date) ASC";
			}
/*
			$query = "SELECT spotter_output.*, count(spotter_output.ident) as ident_count
			    FROM spotter_output
			    WHERE DAYNAME(spotter_output.date) = '$currentDayofWeek' AND HOUR(spotter_output.date) >= '$currentHour' AND HOUR(spotter_output.date) <= '$next3Hours' AND spotter_output.ident <> '' AND format_source <> 'aprs'
			    GROUP BY spotter_output.ident,spotter_output.spotter_id, spotter_output.flightaware_id, spotter_output.registration,spotter_output.airline_name,spotter_output.airline_icao,spotter_output.airline_country,spotter_output.airline_type,spotter_output.aircraft_icao,spotter_output.aircraft_name,spotter_output.aircraft_manufacturer,spotter_output.departure_airport_icao,spotter_output.departure_airport_name,spotter_output.departure_airport_city,spotter_output.departure_airport_country,spotter_output.departure_airport_time,spotter_output.arrival_airport_icao,spotter_output.arrival_airport_name,spotter_output.arrival_airport_city,spotter_output.arrival_airport_country,spotter_output.arrival_airport_time,spotter_output.route_stop,spotter_output.date,spotter_output.latitude,spotter_output.longitude,spotter_output.waypoints,spotter_output.altitude,spotter_output.heading,spotter_output.ground_speed,spotter_output.highlight,spotter_output.squawk,spotter_output.ModeS,spotter_output.pilot_id,spotter_output.pilot_name,spotter_output.verticalrate,spotter_output.owner_name,spotter_output.format_source,spotter_output.source_name,spotter_output.ground,spotter_output.last_ground,spotter_output.last_seen,spotter_output.last_latitude,spotter_output.last_longitude,spotter_output.last_altitude,spotter_output.last_ground_speed,spotter_output.real_arrival_airport_icao,spotter_output.real_arrival_airport_time HAVING count(spotter_output.ident) > 10 $orderby_query";
*/
/*			$query = "SELECT spotter_output.ident, spotter_output.registration,spotter_output.airline_name,spotter_output.airline_icao,spotter_output.airline_country,spotter_output.airline_type,spotter_output.aircraft_icao,spotter_output.aircraft_name,spotter_output.aircraft_manufacturer,spotter_output.departure_airport_icao,spotter_output.departure_airport_name,spotter_output.departure_airport_city,spotter_output.departure_airport_country,spotter_output.departure_airport_time,spotter_output.arrival_airport_icao,spotter_output.arrival_airport_name,spotter_output.arrival_airport_city,spotter_output.arrival_airport_country,spotter_output.arrival_airport_time,spotter_output.route_stop,spotter_output.date,spotter_output.latitude,spotter_output.longitude,spotter_output.waypoints,spotter_output.altitude,spotter_output.heading,spotter_output.ground_speed,spotter_output.highlight,spotter_output.squawk,spotter_output.ModeS,spotter_output.pilot_id,spotter_output.pilot_name,spotter_output.verticalrate,spotter_output.owner_name,spotter_output.format_source,spotter_output.source_name,spotter_output.ground,spotter_output.last_ground,spotter_output.last_seen,spotter_output.last_latitude,spotter_output.last_longitude,spotter_output.last_altitude,spotter_output.last_ground_speed,spotter_output.real_arrival_airport_icao,spotter_output.real_arrival_airport_time, count(spotter_output.ident) as ident_count
			    FROM spotter_output
			    WHERE DAYNAME(spotter_output.date) = '$currentDayofWeek' AND HOUR(spotter_output.date) >= '$currentHour' AND HOUR(spotter_output.date) <= '$next3Hours' AND spotter_output.ident <> '' AND format_source <> 'aprs'
			    GROUP BY spotter_output.ident,spotter_output.spotter_id, spotter_output.flightaware_id, spotter_output.registration,spotter_output.airline_name,spotter_output.airline_icao,spotter_output.airline_country,spotter_output.airline_type,spotter_output.aircraft_icao,spotter_output.aircraft_name,spotter_output.aircraft_manufacturer,spotter_output.departure_airport_icao,spotter_output.departure_airport_name,spotter_output.departure_airport_city,spotter_output.departure_airport_country,spotter_output.departure_airport_time,spotter_output.arrival_airport_icao,spotter_output.arrival_airport_name,spotter_output.arrival_airport_city,spotter_output.arrival_airport_country,spotter_output.arrival_airport_time,spotter_output.route_stop,spotter_output.date,spotter_output.latitude,spotter_output.longitude,spotter_output.waypoints,spotter_output.altitude,spotter_output.heading,spotter_output.ground_speed,spotter_output.highlight,spotter_output.squawk,spotter_output.ModeS,spotter_output.pilot_id,spotter_output.pilot_name,spotter_output.verticalrate,spotter_output.owner_name,spotter_output.format_source,spotter_output.source_name,spotter_output.ground,spotter_output.last_ground,spotter_output.last_seen,spotter_output.last_latitude,spotter_output.last_longitude,spotter_output.last_altitude,spotter_output.last_ground_speed,spotter_output.real_arrival_airport_icao,spotter_output.real_arrival_airport_time HAVING count(spotter_output.ident) > 10 $orderby_query";
*/
			$query = "SELECT spotter_output.ident, spotter_output.airline_name,spotter_output.airline_icao,spotter_output.airline_country,spotter_output.airline_type,spotter_output.departure_airport_icao,spotter_output.departure_airport_name,spotter_output.departure_airport_city,spotter_output.departure_airport_country,spotter_output.departure_airport_time,spotter_output.arrival_airport_icao,spotter_output.arrival_airport_name,spotter_output.arrival_airport_city,spotter_output.arrival_airport_country,spotter_output.arrival_airport_time, count(spotter_output.ident) as ident_count 
			    FROM spotter_output".$filter_query." DAYNAME(spotter_output.date) = '$currentDayofWeek' AND HOUR(spotter_output.date) >= '$currentHour' AND HOUR(spotter_output.date) <= '$next3Hours' AND spotter_output.ident <> '' AND format_source <> 'aprs'
			    GROUP BY spotter_output.ident,spotter_output.airline_name,spotter_output.airline_icao,spotter_output.airline_country,spotter_output.airline_type,spotter_output.departure_airport_icao,spotter_output.departure_airport_name,spotter_output.departure_airport_city,spotter_output.departure_airport_country,spotter_output.departure_airport_time,spotter_output.arrival_airport_icao,spotter_output.arrival_airport_name,spotter_output.arrival_airport_city,spotter_output.arrival_airport_country,spotter_output.arrival_airport_time
			    HAVING count(spotter_output.ident) > 5$orderby_query";

			$spotter_array = $this->getDataFromDB($query.$limit_query);
		} else {
			if ($sort != "")
			{
				$search_orderby_array = $this->getOrderBy();
				$orderby_query = $search_orderby_array[$sort]['sql'];
			} else {
				$orderby_query = " ORDER BY to_char(spotter_output.date,'HH') ASC";
			}
			$query = "SELECT spotter_output.ident, spotter_output.airline_name,spotter_output.airline_icao,spotter_output.airline_country,spotter_output.airline_type,spotter_output.departure_airport_icao,spotter_output.departure_airport_name,spotter_output.departure_airport_city,spotter_output.departure_airport_country,spotter_output.departure_airport_time,spotter_output.arrival_airport_icao,spotter_output.arrival_airport_name,spotter_output.arrival_airport_city,spotter_output.arrival_airport_country,spotter_output.arrival_airport_time, count(spotter_output.ident) as ident_count, to_char(spotter_output.date,'HH') 
			    FROM spotter_output".$filter_query." DATE_PART('dow', spotter_output.date) = DATE_PART('dow', date 'now' AT TIME ZONE :timezone) AND EXTRACT (HOUR FROM spotter_output.date AT TIME ZONE :timezone) >= '$currentHour' AND EXTRACT (HOUR FROM spotter_output.date AT TIME ZONE :timezone) <= '$next3Hours' AND ident <> '' 
			    GROUP BY spotter_output.ident,spotter_output.airline_name,spotter_output.airline_icao,spotter_output.airline_country,spotter_output.airline_type,spotter_output.departure_airport_icao,spotter_output.departure_airport_name,spotter_output.departure_airport_city,spotter_output.departure_airport_country,spotter_output.departure_airport_time,spotter_output.arrival_airport_icao,spotter_output.arrival_airport_name,spotter_output.arrival_airport_city,spotter_output.arrival_airport_country,spotter_output.arrival_airport_time, to_char(spotter_output.date,'HH')
			    HAVING count(spotter_output.ident) > 5$orderby_query";
			//echo $query;
			$spotter_array = $this->getDataFromDB($query.$limit_query,array(':timezone' => $globalTimezone));
			/*
			$sth = $this->db->prepare($query);
			$sth->execute(array(':timezone' => $globalTimezone));
			return $sth->fetchAll(PDO::FETCH_ASSOC);
			*/
		}
		return $spotter_array;
	}


    /**
     * Gets the Barrie Spotter ID based on the FlightAware ID
     *
     * @param $flightaware_id
     * @return Integer the Barrie Spotter ID
     */
	public function getSpotterIDBasedOnFlightAwareID($flightaware_id)
	{
		$flightaware_id = filter_var($flightaware_id,FILTER_SANITIZE_STRING);

		$query  = "SELECT spotter_output.spotter_id
								FROM spotter_output 
								WHERE spotter_output.flightaware_id = '".$flightaware_id."'";
        
		
		$sth = $this->db->prepare($query);
		$sth->execute();

		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			return $row['spotter_id'];
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
	* Gets the aircraft registration
	*
	* @param String $flightaware_id the flight aware id
	* @return String the aircraft registration
	*
	*/
	
	public function getAircraftRegistration($flightaware_id)
	{
		global $globalFlightAwareUsername, $globalFlightAwarePassword;
        
		$options = array(
			'trace' => true,
			'exceptions' => 0,
			'login' => $globalFlightAwareUsername,
			'password' => $globalFlightAwarePassword,
		);
		$client = new SoapClient('http://flightxml.flightaware.com/soap/FlightXML2/wsdl', $options);
		
		$params = array('faFlightID' => $flightaware_id);
		$result = $client->AirlineFlightInfo($params);
		
		if (isset($result->AirlineFlightInfoResult))
		{
			$registration = $result->AirlineFlightInfoResult->tailnumber;
		} else return '';
		
		$registration = $this->convertAircraftRegistration($registration);
		
		return $registration;
	}


    /**
     * Gets the aircraft registration from ModeS
     *
     * @param String $aircraft_modes the flight ModeS in hex
     * @param string $source_type
     * @return String the aircraft registration
     */
	public function getAircraftRegistrationBymodeS($aircraft_modes, $source_type = '')
	{
		$aircraft_modes = filter_var($aircraft_modes,FILTER_SANITIZE_STRING);
		$source_type = filter_var($source_type,FILTER_SANITIZE_STRING);
		if ($source_type == '' || $source_type == 'modes') {
			$query  = "SELECT aircraft_modes.Registration FROM aircraft_modes WHERE aircraft_modes.ModeS = :aircraft_modes AND aircraft_modes.source_type = 'modes' ORDER BY FirstCreated DESC LIMIT 1";
		} else {
			$query  = "SELECT aircraft_modes.Registration FROM aircraft_modes WHERE aircraft_modes.ModeS = :aircraft_modes AND aircraft_modes.source_type = 'flarm' ORDER BY FirstCreated DESC LIMIT 1";
		}
		$sth = $this->db->prepare($query);
		$sth->execute(array(':aircraft_modes' => $aircraft_modes));
    
		$row = $sth->fetch(PDO::FETCH_ASSOC);
		$sth->closeCursor();
		if (is_array($row) && count($row) > 0) {
		    //return $row['Registration'];
		    return $row['registration'];
		} elseif ($source_type == 'flarm') {
			return $this->getAircraftRegistrationBymodeS($aircraft_modes);
		} else return '';
	
	}

	/**
	* Gets the aircraft type from ModeS
	*
	* @param String $aircraft_modes the flight ModeS in hex
	* @return String the aircraft type
	*
	*/
	public function getAircraftTypeBymodeS($aircraft_modes,$source_type = '')
	{
		$aircraft_modes = filter_var($aircraft_modes,FILTER_SANITIZE_STRING);
		$source_type = filter_var($source_type,FILTER_SANITIZE_STRING);
		if ($source_type == '' || $source_type == 'modes') {
			$query  = "SELECT aircraft_modes.type_flight FROM aircraft_modes WHERE aircraft_modes.ModeS = :aircraft_modes AND aircraft_modes.source_type = 'modes' ORDER BY FirstCreated DESC LIMIT 1";
		} else {
			$query  = "SELECT aircraft_modes.type_flight FROM aircraft_modes WHERE aircraft_modes.ModeS = :aircraft_modes AND aircraft_modes.source_type = 'flarm' ORDER BY FirstCreated DESC LIMIT 1";
		}
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':aircraft_modes' => $aircraft_modes));
    
		$row = $sth->fetch(PDO::FETCH_ASSOC);
		$sth->closeCursor();
		if (is_array($row) && count($row) > 0) {
			if ($row['type_flight'] == null) return '';
			else return $row['type_flight'];
		} elseif ($source_type == 'flarm') {
			return $this->getAircraftTypeBymodeS($aircraft_modes);
		} else return '';
	
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
		global $globalDBdriver, $globalDebug;
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
	* @return String the countrie
	*/
	public function getCountryFromISO2($iso2)
	{
		global $globalDBdriver, $globalDebug;
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
	* converts the registration code using the country prefix
	*
	* @param String $registration the aircraft registration
	* @return String the aircraft registration
	*
	*/
	public function convertAircraftRegistration($registration)
	{
		$registration = filter_var($registration,FILTER_SANITIZE_STRING);
		$registration_prefix = '';
		$registration_1 = substr($registration, 0, 1);
		$registration_2 = substr($registration, 0, 2);

		//first get the prefix based on two characters
		$query  = "SELECT aircraft_registration.registration_prefix FROM aircraft_registration WHERE registration_prefix = :registration_2";
      
		
		$sth = $this->db->prepare($query);
		$sth->execute(array(':registration_2' => $registration_2));
        
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$registration_prefix = $row['registration_prefix'];
		}

		//if we didn't find a two chracter prefix lets just search the one with one character
		if ($registration_prefix == '')
		{
			$query  = "SELECT aircraft_registration.registration_prefix FROM aircraft_registration WHERE registration_prefix = :registration_1";
			$sth = $this->db->prepare($query);
			$sth->execute(array(':registration_1' => $registration_1));
	        
			while($row = $sth->fetch(PDO::FETCH_ASSOC))
			{
				$registration_prefix = $row['registration_prefix'];
			}
		}

		//determine which characters are being used and convert the registration code appropiately
		if (strlen($registration_prefix) == 1)
		{
			if (0 === strpos($registration, 'N')) {
				$registration = preg_replace("/^(.{1})/", "$1", $registration);
			} else {
				$registration = preg_replace("/^(.{1})/", "$1-", $registration);
			}
		} else if(strlen($registration_prefix) == 2){
			if (0 === strpos($registration, 'N')) {
				$registration = preg_replace("/^(.{2})/", "$1", $registration);
			} else {
				$registration = preg_replace("/^(.{2})/", "$1-", $registration);
			}
		}
		return $registration;
	}

	/**
	* Country from the registration code
	*
	* @param String $registration the aircraft registration
	* @return String the country
	*
	*/
	public function countryFromAircraftRegistration($registration)
	{
		$registration = filter_var($registration,FILTER_SANITIZE_STRING);
		
		$registration_prefix = '';
		$registration_test = explode('-',$registration);
		$country = '';
		if ($registration_test[0] != $registration) {
			$query  = "SELECT aircraft_registration.registration_prefix, aircraft_registration.country FROM aircraft_registration WHERE registration_prefix = :registration_1 LIMIT 1";
	      
			$sth = $this->db->prepare($query);
			$sth->execute(array(':registration_1' => $registration_test[0]));
			while($row = $sth->fetch(PDO::FETCH_ASSOC))
			{
				//$registration_prefix = $row['registration_prefix'];
				$country = $row['country'];
			}
		} else {
    			$registration_1 = substr($registration, 0, 1);
		        $registration_2 = substr($registration, 0, 2);

			$country = '';
			//first get the prefix based on two characters
			$query  = "SELECT aircraft_registration.registration_prefix, aircraft_registration.country FROM aircraft_registration WHERE registration_prefix = :registration_2 LIMIT 1";
      
			
			$sth = $this->db->prepare($query);
			$sth->execute(array(':registration_2' => $registration_2));
        
			while($row = $sth->fetch(PDO::FETCH_ASSOC))
			{
				$registration_prefix = $row['registration_prefix'];
				$country = $row['country'];
			}

			//if we didn't find a two chracter prefix lets just search the one with one character
			if ($registration_prefix == "")
			{
				$query  = "SELECT aircraft_registration.registration_prefix, aircraft_registration.country FROM aircraft_registration WHERE registration_prefix = :registration_1 LIMIT 1";
	      
				$sth = $this->db->prepare($query);
				$sth->execute(array(':registration_1' => $registration_1));
	        
				while($row = $sth->fetch(PDO::FETCH_ASSOC))
				{
					//$registration_prefix = $row['registration_prefix'];
					$country = $row['country'];
				}
			}
		}
    
		return $country;
	}

	/**
	* Registration prefix from the registration code
	*
	* @param String $registration the aircraft registration
	* @return String the registration prefix
	*
	*/
	public function registrationPrefixFromAircraftRegistration($registration)
	{
		$registration = filter_var($registration,FILTER_SANITIZE_STRING);
		
		$registration_prefix = '';
		$registration_test = explode('-',$registration);
		//$country = '';
		if ($registration_test[0] != $registration) {
			$query  = "SELECT aircraft_registration.registration_prefix, aircraft_registration.country FROM aircraft_registration WHERE registration_prefix = :registration_1 LIMIT 1";
	      
			$sth = $this->db->prepare($query);
			$sth->execute(array(':registration_1' => $registration_test[0]));
			while($row = $sth->fetch(PDO::FETCH_ASSOC))
			{
				$registration_prefix = $row['registration_prefix'];
				//$country = $row['country'];
			}
		} else {
    			$registration_1 = substr($registration, 0, 1);
		        $registration_2 = substr($registration, 0, 2);

			//first get the prefix based on two characters
			$query  = "SELECT aircraft_registration.registration_prefix, aircraft_registration.country FROM aircraft_registration WHERE registration_prefix = :registration_2 LIMIT 1";
      
			
			$sth = $this->db->prepare($query);
			$sth->execute(array(':registration_2' => $registration_2));
        
			while($row = $sth->fetch(PDO::FETCH_ASSOC))
			{
				$registration_prefix = $row['registration_prefix'];
				//$country = $row['country'];
			}

			//if we didn't find a two chracter prefix lets just search the one with one character
			if ($registration_prefix == "")
			{
				$query  = "SELECT aircraft_registration.registration_prefix, aircraft_registration.country FROM aircraft_registration WHERE registration_prefix = :registration_1 LIMIT 1";
	      
				$sth = $this->db->prepare($query);
				$sth->execute(array(':registration_1' => $registration_1));
	        
				while($row = $sth->fetch(PDO::FETCH_ASSOC))
				{
					$registration_prefix = $row['registration_prefix'];
					//$country = $row['country'];
				}
			}
		}
    
		return $registration_prefix;
	}


	/**
	* Country from the registration code
	*
	* @param String $registration the aircraft registration
	* @return String the country
	*
	*/
	public function countryFromAircraftRegistrationCode($registration)
	{
		$registration = filter_var($registration,FILTER_SANITIZE_STRING);
		
		$country = '';
		$query  = "SELECT aircraft_registration.registration_prefix, aircraft_registration.country FROM aircraft_registration WHERE registration_prefix = :registration LIMIT 1";
		$sth = $this->db->prepare($query);
		$sth->execute(array(':registration' => $registration));
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$country = $row['country'];
		}
		return $country;
	}
	
	/**
	* Set a new highlight value for a flight
	*
	* @param String $flightaware_id flightaware_id from spotter_output table
	* @param String $highlight New highlight value
	*/
	public function setHighlightFlight($flightaware_id,$highlight) {
		
		$query  = "UPDATE spotter_output SET highlight = :highlight WHERE flightaware_id = :flightaware_id";
		$sth = $this->db->prepare($query);
		$sth->execute(array(':flightaware_id' => $flightaware_id, ':highlight' => $highlight));
	}

	/**
	* Set a new highlight value for a flight by Registration
	*
	* @param String $registration Registration of the aircraft
	* @param String $date Date of spotted aircraft
	* @param String $highlight New highlight value
	*/
	public function setHighlightFlightByRegistration($registration,$highlight, $date = '') {
		if ($date == '') {
			$query  = "UPDATE spotter_output SET highlight = :highlight WHERE spotter_id IN (SELECT MAX(spotter_id) FROM spotter_output WHERE registration = :registration) LIMIT 1";
			$query_values = array(':registration' => $registration, ':highlight' => $highlight);
		} else {
			$query  = "UPDATE spotter_output SET highlight = :highlight WHERE registration = :registration AND date(date) = :date";
			$query_values = array(':registration' => $registration, ':highlight' => $highlight,':date' => $date);
		}
		$sth = $this->db->prepare($query);
		$sth->execute($query_values);
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
		$orderby = array("aircraft_asc" => array("key" => "aircraft_asc", "value" => "Aircraft Type - ASC", "sql" => "ORDER BY spotter_output.aircraft_icao ASC"), "aircraft_desc" => array("key" => "aircraft_desc", "value" => "Aircraft Type - DESC", "sql" => "ORDER BY spotter_output.aircraft_icao DESC"),"manufacturer_asc" => array("key" => "manufacturer_asc", "value" => "Aircraft Manufacturer - ASC", "sql" => "ORDER BY spotter_output.aircraft_manufacturer ASC"), "manufacturer_desc" => array("key" => "manufacturer_desc", "value" => "Aircraft Manufacturer - DESC", "sql" => "ORDER BY spotter_output.aircraft_manufacturer DESC"),"airline_name_asc" => array("key" => "airline_name_asc", "value" => "Airline Name - ASC", "sql" => "ORDER BY spotter_output.airline_name ASC"), "airline_name_desc" => array("key" => "airline_name_desc", "value" => "Airline Name - DESC", "sql" => "ORDER BY spotter_output.airline_name DESC"), "ident_asc" => array("key" => "ident_asc", "value" => "Ident - ASC", "sql" => "ORDER BY spotter_output.ident ASC"), "ident_desc" => array("key" => "ident_desc", "value" => "Ident - DESC", "sql" => "ORDER BY spotter_output.ident DESC"), "airport_departure_asc" => array("key" => "airport_departure_asc", "value" => "Departure Airport - ASC", "sql" => "ORDER BY spotter_output.departure_airport_city ASC"), "airport_departure_desc" => array("key" => "airport_departure_desc", "value" => "Departure Airport - DESC", "sql" => "ORDER BY spotter_output.departure_airport_city DESC"), "airport_arrival_asc" => array("key" => "airport_arrival_asc", "value" => "Arrival Airport - ASC", "sql" => "ORDER BY spotter_output.arrival_airport_city ASC"), "airport_arrival_desc" => array("key" => "airport_arrival_desc", "value" => "Arrival Airport - DESC", "sql" => "ORDER BY spotter_output.arrival_airport_city DESC"), "date_asc" => array("key" => "date_asc", "value" => "Date - ASC", "sql" => "ORDER BY spotter_output.date ASC"), "date_desc" => array("key" => "date_desc", "value" => "Date - DESC", "sql" => "ORDER BY spotter_output.date DESC"),"distance_asc" => array("key" => "distance_asc","value" => "Distance - ASC","sql" => "ORDER BY distance ASC"),"distance_desc" => array("key" => "distance_desc","value" => "Distance - DESC","sql" => "ORDER BY distance DESC"));
		
		return $orderby;
		
	}
    
/*
	public function importFromFlightAware()
	{
		global $globalFlightAwareUsername, $globalFlightAwarePassword, $globalLatitudeMax, $globalLatitudeMin, $globalLongitudeMax, $globalLongitudeMin, $globalAirportIgnore;
		$Spotter = new Spotter($this->db);
		$SpotterLive = new SpotterLive($this->db);
		$options = array(
		            'trace' => true,
		            'exceptions' => 0,
		            'login' => $globalFlightAwareUsername,
		            'password' => $globalFlightAwarePassword,
		);
		$client = new SoapClient('http://flightxml.flightaware.com/soap/FlightXML2/wsdl', $options);
		$params = array('query' => '{range lat '.$globalLatitudeMin.' '.$globalLatitudeMax.'} {range lon '.$globalLongitudeMax.' '.$globalLongitudeMin.'} {true inAir}', 'howMany' => '15', 'offset' => '0');
		$result = $client->SearchBirdseyeInFlight($params);
		$dataFound = false;
		$ignoreImport = false;
		if (isset($result->SearchBirdseyeInFlightResult))
		{
			if (is_array($result->SearchBirdseyeInFlightResult->aircraft))
			{
				foreach($result->SearchBirdseyeInFlightResult->aircraft as $aircraft)
				{
					if (!strstr($aircraft->origin, 'L ') && !strstr($aircraft->destination, 'L '))
					{
						foreach($globalAirportIgnore as $airportIgnore)
						{
							if ($aircraft->origin == $airportIgnore || $aircraft->destination == $airportIgnore)
							{
								$ignoreImport = true; 
							}
						}
						if ($ignoreImport == false)
						{
							$flightaware_id = $aircraft->faFlightID;
							$ident = $aircraft->ident;
							$aircraft_type = $aircraft->type;
							$departure_airport = $aircraft->origin;
							$arrival_airport = $aircraft->destination;
							$latitude = $aircraft->latitude;
							$longitude = $aircraft->longitude;
							$waypoints = $aircraft->waypoints;
							$altitude = $aircraft->altitude;
							$heading = $aircraft->heading;
							$groundspeed = $aircraft->groundspeed;
							$dataFound = true;
							//gets the callsign from the last hour
							$last_hour_ident = $this->getIdentFromLastHour($ident);
							//change the departure/arrival airport to NA if its not available
							if ($departure_airport == "" || $departure_airport == "---" || $departure_airport == "ZZZ" || $departure_airport == "ZZZZ") { $departure_airport = "NA"; }
							if ($arrival_airport == "" || $arrival_airport == "---" || $arrival_airport == "ZZZ" || $arrival_airport == "ZZZZ") { $arrival_airport = "NA"; }
							//if there was no aircraft with the same callsign within the last hour and go post it into the archive
							if($last_hour_ident == "")
							{
								//adds the spotter data for the archive
								$Spotter->addSpotterData($flightaware_id, $ident, $aircraft_type, $departure_airport, $arrival_airport, $latitude, $longitude, $waypoints, $altitude, $heading, $groundspeed);
							}

							//adds the spotter LIVE data
							$SpotterLive->addLiveSpotterData($flightaware_id, $ident, $aircraft_type, $departure_airport, $arrival_airport, $latitude, $longitude, $waypoints, $altitude, $heading, $groundspeed);
						}
					}
					$ignoreImport = false;
				}
			} else {
				if (!strstr($result->SearchBirdseyeInFlightResult->aircraft->origin, 'L ') && !strstr($result->SearchBirdseyeInFlightResult->aircraft->destination, 'L '))
				{
					foreach($globalAirportIgnore as $airportIgnore)
					{
						foreach($globalAirportIgnore as $airportIgnore)
						{
							if ($aircraft->origin == $airportIgnore || $aircraft->destination == $airportIgnore)
							{
								$ignoreImport = true; 
							}
						}
						if ($ignoreImport == false)
						{
							$flightaware_id = $result->SearchBirdseyeInFlightResult->aircraft->faFlightID;
							$ident = $result->SearchBirdseyeInFlightResult->aircraft->ident;
							$aircraft_type = $result->SearchBirdseyeInFlightResult->aircraft->type;
							$departure_airport = $result->SearchBirdseyeInFlightResult->aircraft->origin;
							$arrival_airport = $result->SearchBirdseyeInFlightResult->aircraft->destination;
							$latitude = $result->SearchBirdseyeInFlightResult->aircraft->latitude;
							$longitude = $result->SearchBirdseyeInFlightResult->aircraft->longitude;
							$waypoints = $result->SearchBirdseyeInFlightResult->aircraft->waypoints;
							$altitude = $result->SearchBirdseyeInFlightResult->aircraft->altitude;
							$heading = $result->SearchBirdseyeInFlightResult->aircraft->heading;
							$groundspeed = $result->SearchBirdseyeInFlightResult->aircraft->groundspeed;
							$dataFound = true;
							//gets the callsign from the last hour
							$last_hour_ident = $this->getIdentFromLastHour($ident);
							//change the departure/arrival airport to NA if its not available
							if ($departure_airport == "" || $departure_airport == "---" || $departure_airport == "ZZZ" || $departure_airport == "ZZZZ") { $departure_airport = "NA"; }
							if ($arrival_airport == "" || $arrival_airport == "---" || $arrival_airport == "ZZZ" || $arrival_airport == "ZZZZ") { $arrival_airport = "NA"; }
							//if there was no aircraft with the same callsign within the last hour and go post it into the archive
							if($last_hour_ident == "")
							{
								//adds the spotter data for the archive
								$Spotter->addSpotterData($flightaware_id, $ident, $aircraft_type, $departure_airport, $arrival_airport, $latitude, $longitude, $waypoints, $altitude, $heading, $groundspeed);
							}
							//adds the spotter LIVE data
							$SpotterLive->addLiveSpotterData($flightaware_id, $ident, $aircraft_type, $departure_airport, $arrival_airport, $latitude, $longitude, $waypoints, $altitude, $heading, $groundspeed);
						}
						$ignoreImport = false;
					}
				}
			}
		} 
	}
*/

	// Update flights data when new data in DB
	public function updateFieldsFromOtherTables()
	{
		global $globalDebug, $globalDBdriver;
		$Image = new Image($this->db);
		

		// routes
		if ($globalDebug) print "Routes...\n";
		if ($globalDBdriver == 'mysql') {
			$query = "SELECT spotter_output.spotter_id, routes.FromAirport_ICAO, routes.ToAirport_ICAO FROM spotter_output, routes WHERE spotter_output.ident = routes.CallSign AND ( spotter_output.departure_airport_icao != routes.FromAirport_ICAO OR spotter_output.arrival_airport_icao != routes.ToAirport_ICAO) AND routes.FromAirport_ICAO != '' AND spotter_output.date >= DATE_SUB(UTC_TIMESTAMP(),INTERVAL 15 DAY)";
		} else {
			$query = "SELECT spotter_output.spotter_id, routes.FromAirport_ICAO, routes.ToAirport_ICAO FROM spotter_output, routes WHERE spotter_output.ident = routes.CallSign AND ( spotter_output.departure_airport_icao != routes.FromAirport_ICAO OR spotter_output.arrival_airport_icao != routes.ToAirport_ICAO) AND routes.FromAirport_ICAO != '' AND spotter_output.date >= now() AT TIME ZONE 'UTC' - INTERVAL '15 DAYS'";
		}
		$sth = $this->db->prepare($query);
		$sth->execute();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$departure_airport_array = $this->getAllAirportInfo($row['fromairport_icao']);
			$arrival_airport_array = $this->getAllAirportInfo($row['toairport_icao']);
			if (count($departure_airport_array) > 0 && count($arrival_airport_array) > 0) {
				$update_query="UPDATE spotter_output SET departure_airport_icao = :fromicao, arrival_airport_icao = :toicao, departure_airport_name = :departure_airport_name, departure_airport_city = :departure_airport_city, departure_airport_country = :departure_airport_country, arrival_airport_name = :arrival_airport_name, arrival_airport_city = :arrival_airport_city, arrival_airport_country = :arrival_airport_country WHERE spotter_id = :spotter_id";
				$sthu = $this->db->prepare($update_query);
				$sthu->execute(array(':fromicao' => $row['fromairport_icao'],':toicao' => $row['toairport_icao'],':spotter_id' => $row['spotter_id'],':departure_airport_name' => $departure_airport_array[0]['name'],':departure_airport_city' => $departure_airport_array[0]['city'],':departure_airport_country' => $departure_airport_array[0]['country'],':arrival_airport_name' => $arrival_airport_array[0]['name'],':arrival_airport_city' => $arrival_airport_array[0]['city'],':arrival_airport_country' => $arrival_airport_array[0]['country']));
			}
		}
		
		if ($globalDebug) print "Airlines...\n";
		//airlines
		if ($globalDBdriver == 'mysql') {
			$query  = "SELECT spotter_output.spotter_id, spotter_output.ident FROM spotter_output WHERE (spotter_output.airline_name = '' OR spotter_output.airline_name = 'Not Available') AND spotter_output.date >= DATE_SUB(UTC_TIMESTAMP(),INTERVAL 15 DAY)";
		} elseif ($globalDBdriver == 'pgsql') {
			$query  = "SELECT spotter_output.spotter_id, spotter_output.ident FROM spotter_output WHERE (spotter_output.airline_name = '' OR spotter_output.airline_name = 'Not Available') AND spotter_output.date >= now() AT TIME ZONE 'UTC' - INTERVAL '15 DAYS'";
		}
		$sth = $this->db->prepare($query);
		$sth->execute();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			if (is_numeric(substr($row['ident'], -1, 1)))
			{
				$fromsource = NULL;
				if (isset($row['format_source']) && $row['format_source'] == 'vatsimtxt') $fromsource = 'vatsim';
				elseif (isset($row['format_source']) && $row['format_source'] == 'whazzup') $fromsource = 'ivao';
				elseif (isset($globalVATSIM) && $globalVATSIM) $fromsource = 'vatsim';
				elseif (isset($globalIVAO) && $globalIVAO) $fromsource = 'ivao';
				$airline_array = $this->getAllAirlineInfo(substr($row['ident'], 0, 3),$fromsource);
				if (isset($airline_array[0]['name'])) {
					$update_query  = "UPDATE spotter_output SET spotter_output.airline_name = :airline_name, spotter_output.airline_icao = :airline_icao, spotter_output.airline_country = :airline_country, spotter_output.airline_type = :airline_type WHERE spotter_output.spotter_id = :spotter_id";
					$sthu = $this->db->prepare($update_query);
					$sthu->execute(array(':airline_name' => $airline_array[0]['name'],':airline_icao' => $airline_array[0]['icao'], ':airline_country' => $airline_array[0]['country'], ':airline_type' => $airline_array[0]['type'], ':spotter_id' => $row['spotter_id']));
				}
			}
		}

		if ($globalDebug) print "Remove Duplicate in aircraft_modes...\n";
		//duplicate modes
		$query = "DELETE aircraft_modes FROM aircraft_modes LEFT OUTER JOIN (SELECT max(`AircraftID`) as `AircraftID`,`ModeS` FROM `aircraft_modes` group by ModeS) as KeepRows ON aircraft_modes.AircraftID = KeepRows.AircraftID WHERE KeepRows.AircraftID IS NULL";
		$sth = $this->db->prepare($query);
		$sth->execute();
		
		if ($globalDebug) print "Aircraft...\n";
		//aircraft
		if ($globalDBdriver == 'mysql') {
			$query  = "SELECT spotter_output.spotter_id, spotter_output.aircraft_icao, spotter_output.registration FROM spotter_output WHERE (spotter_output.aircraft_name = '' OR spotter_output.aircraft_name = 'Not Available') AND spotter_output.date >= DATE_SUB(UTC_TIMESTAMP(),INTERVAL 7 DAY)";
		} elseif ($globalDBdriver == 'pgsql') {
			$query  = "SELECT spotter_output.spotter_id, spotter_output.aircraft_icao, spotter_output.registration FROM spotter_output WHERE (spotter_output.aircraft_name = '' OR spotter_output.aircraft_name = 'Not Available') AND spotter_output.date >= now() AT TIME ZONE 'UTC' - INERVAL '15 DAYS'";
		}
		$sth = $this->db->prepare($query);
		$sth->execute();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			if ($row['aircraft_icao'] != '') {
				$aircraft_name = $this->getAllAircraftInfo($row['aircraft_icao']);
				if ($row['registration'] != ""){
					$image_array = $Image->getSpotterImage($row['registration']);
					if (!isset($image_array[0]['registration'])) {
						$Image->addSpotterImage($row['registration']);
					}
				}
				if (count($aircraft_name) > 0) {
					$update_query  = "UPDATE spotter_output SET spotter_output.aircraft_name = :aircraft_name, spotter_output.aircraft_manufacturer = :aircraft_manufacturer WHERE spotter_output.spotter_id = :spotter_id";
					$sthu = $this->db->prepare($update_query);
					$sthu->execute(array(':aircraft_name' => $aircraft_name[0]['type'], ':aircraft_manufacturer' => $aircraft_name[0]['manufacturer'], ':spotter_id' => $row['spotter_id']));
				}
			}
		}
	}	

	// Update arrival airports for data already in DB
	public function updateArrivalAirports()
	{
		global $globalDebug, $globalDBdriver, $globalClosestMinDist;
		$query = "SELECT spotter_output.spotter_id, spotter_output.last_latitude, spotter_output.last_longitude, spotter_output.last_altitude, spotter_output.arrival_airport_icao, spotter_output.real_arrival_airport_icao FROM spotter_output";
		$sth = $this->db->prepare($query);
		$sth->execute();
		while($row = $sth->fetch(PDO::FETCH_ASSOC))
		{
			if ($row['last_latitude'] != '' && $row['last_longitude'] != '') {
				$closestAirports = $this->closestAirports($row['last_latitude'],$row['last_longitude'],$globalClosestMinDist);
				$airport_icao = '';
				 if (isset($closestAirports[0])) {
					if ($row['arrival_airport_icao'] == $closestAirports[0]['icao']) {
						$airport_icao = $closestAirports[0]['icao'];
						if ($globalDebug) echo "\o/ 1st ---++ Find arrival airport. airport_icao : ".$airport_icao."\n";
					} elseif (count($closestAirports > 1) && $row['arrival_airport_icao'] != '' && $row['arrival_airport_icao'] != 'NA') {
						foreach ($closestAirports as $airport) {
							if ($row['arrival_airport_icao'] == $airport['icao']) {
								$airport_icao = $airport['icao'];
								if ($globalDebug) echo "\o/ try --++ Find arrival airport. airport_icao : ".$airport_icao."\n";
								break;
							}
						}
					} elseif ($row['last_altitude'] == 0 || ($row['last_altitude'] != '' && ($closestAirports[0]['altitude'] <= $row['last_altitude']*100+1000 && $row['last_altitude']*100 < $closestAirports[0]['altitude']+5000))) {
						$airport_icao = $closestAirports[0]['icao'];
						if ($globalDebug) echo "\o/ NP --++ Find arrival airport. Airport ICAO : ".$airport_icao." !  Latitude : ".$row['last_latitude'].' - Longitude : '.$row['last_longitude'].' - MinDist : '.$globalClosestMinDist." - Airport altitude : ".$closestAirports[0]['altitude'].' - flight altitude : '.($row['last_altitude']*100)."\n";
					} else {
						if ($globalDebug) echo "----- Can't find arrival airport. Latitude : ".$row['last_latitude'].' - Longitude : '.$row['last_longitude'].' - MinDist : '.$globalClosestMinDist." - Airport altitude : ".$closestAirports[0]['altitude'].' - flight altitude : '.($row['last_altitude']*100)."\n";
					}
				} else {
					if ($globalDebug) echo "----- No Airport near last coord. Latitude : ".$row['last_latitude'].' - Longitude : '.$row['last_longitude'].' - MinDist : '.$globalClosestMinDist."\n";
				}
				if ($row['real_arrival_airport_icao'] != $airport_icao) {
					if ($globalDebug) echo "Updating airport to ".$airport_icao."...\n";
					$update_query="UPDATE spotter_output SET real_arrival_airport_icao = :airport_icao WHERE spotter_id = :spotter_id";
					$sthu = $this->db->prepare($update_query);
					$sthu->execute(array(':airport_icao' => $airport_icao,':spotter_id' => $row['spotter_id']));
				}
			}
		}
	}

    /**
     * @param $origLat
     * @param $origLon
     * @param int $dist
     * @return array
     */
    public function closestAirports($origLat, $origLon, $dist = 10) {
		global $globalDBdriver;
		$dist = number_format($dist*0.621371,2,'.',''); // convert km to mile
/*
		$query="SELECT name, icao, latitude, longitude, altitude, 3956 * 2 * ASIN(SQRT( POWER(SIN(($origLat - abs(latitude))*pi()/180/2),2)+COS( $origLat *pi()/180)*COS(abs(latitude)*pi()/180)*POWER(SIN(($origLon-longitude)*pi()/180/2),2))) as distance 
                      FROM airport WHERE longitude between ($origLon-$dist/abs(cos(radians($origLat))*69)) and ($origLon+$dist/abs(cos(radians($origLat))*69)) and latitude between ($origLat-($dist/69)) and ($origLat+($dist/69)) 
                      having distance < $dist ORDER BY distance limit 100;";
*/
		if ($globalDBdriver == 'mysql') {
			$query="SELECT name, icao, latitude, longitude, altitude, 3956 * 2 * ASIN(SQRT( POWER(SIN(($origLat - latitude)*pi()/180/2),2)+COS( $origLat *pi()/180)*COS(latitude*pi()/180)*POWER(SIN(($origLon-longitude)*pi()/180/2),2))) as distance 
	                      FROM airport WHERE longitude between ($origLon-$dist/cos(radians($origLat))*69) and ($origLon+$dist/cos(radians($origLat)*69)) and latitude between ($origLat-($dist/69)) and ($origLat+($dist/69)) 
	                      AND (3956 * 2 * ASIN(SQRT( POWER(SIN(($origLat - latitude)*pi()/180/2),2)+COS( $origLat *pi()/180)*COS(latitude*pi()/180)*POWER(SIN(($origLon-longitude)*pi()/180/2),2)))) < $dist ORDER BY distance limit 100;";
                } else {
			$query="SELECT name, icao, latitude, longitude, altitude, 3956 * 2 * ASIN(SQRT( POWER(SIN(($origLat - CAST(latitude as double precision))*pi()/180/2),2)+COS( $origLat *pi()/180)*COS(CAST(latitude as double precision)*pi()/180)*POWER(SIN(($origLon-CAST(longitude as double precision))*pi()/180/2),2))) as distance 
	                      FROM airport WHERE CAST(longitude as double precision) between ($origLon-$dist/cos(radians($origLat))*69) and ($origLon+$dist/cos(radians($origLat))*69) and CAST(latitude as double precision) between ($origLat-($dist/69)) and ($origLat+($dist/69)) 
	                      AND (3956 * 2 * ASIN(SQRT( POWER(SIN(($origLat - CAST(latitude as double precision))*pi()/180/2),2)+COS( $origLat *pi()/180)*COS(CAST(latitude as double precision)*pi()/180)*POWER(SIN(($origLon-CAST(longitude as double precision))*pi()/180/2),2)))) < $dist ORDER BY distance limit 100;";
    		}
		$sth = $this->db->prepare($query);
		$sth->execute();
		return $sth->fetchAll(PDO::FETCH_ASSOC);
	}
	
	/**
	* Deletes old aprs data
	*
	* @return String success or false
	*
	*/
	public function deleteOldAPRSData()
	{
		global $globalDBdriver;
		if ($globalDBdriver == 'mysql') {
			$query  = "DELETE FROM spotter_output WHERE DATE_SUB(UTC_TIMESTAMP(),INTERVAL 23 HOUR) >= spotter_output.date AND format_source = 'aprs'";
		} else {
			$query  = "DELETE FROM spotter_output WHERE NOW() AT TIME ZONE 'UTC' - INTERVAL '23 HOURS' >= spotter_ouput.date AND format_source = 'aprs'";
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
/*
$Spotter = new Spotter();
print_r($Spotter->closestAirports('-19.9813','-47.8286',10));
*/
/*
$Spotter = new Spotter();
$da = $Spotter->countAllDetectedArrivalAirports(true,0,'',true);
$aa = $Spotter->countAllArrivalAirports(true,0,'',true);
print_r($da);
print_r($aa);
print_r(array_merge($da,$aa));
*/
?>