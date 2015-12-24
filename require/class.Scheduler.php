<?php
require_once('libs/simple_html_dom.php');
require_once('settings.php');
require_once('class.Connection.php');
require_once('class.Translation.php');
require_once('class.Common.php');
require_once('libs/uagent/uagent.php');
// FIXME : timezones ?!

class Schedule {
	protected $cookies = array();
        public $db;
	function __construct($dbc = null) {
		if ($dbc === null) {
			$Connection = new Connection();
			$this->db = $Connection->db;
                } else $this->db = $dbc;
        }
	
	/**
	* Add schedule data to database
	* @param String $ident aircraft ident
	* @param String $departure_airport_icao departure airport icao
	* @param String $departure_airport_time departure airport time
	* @param String $arrival_airport_icao arrival airport icao
	* @param String $arrival_airport_time arrival airport time
	/ @param String $source source of data
	*/
	
	public function addSchedule($ident,$departure_airport_icao,$departure_airport_time,$arrival_airport_icao,$arrival_airport_time,$source = 'website') {
		date_default_timezone_set('UTC');
		$date = date("Y-m-d H:i:s",time());
	        //if ($departure_airport_time == '' && $arrival_airport_time == '') exit;
	        //$query = "SELECT COUNT(*) FROM schedule WHERE ident = :ident";
	        $query = "SELECT COUNT(*) FROM routes WHERE CallSign = :ident";
	        $query_values = array(':ident' => $ident);
		 try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
		if ($sth->fetchColumn() > 0) {
			if ($departure_airport_time == '' && $arrival_airport_time == '') {
			    $query = "SELECT COUNT(*) FROM routes WHERE CallSign = :ident AND FromAirport_ICAO = :departure_airport_icao AND ToAirport_ICAO = :arrival_airport_icao";
			    $query_values = array(':ident' => $ident,':departure_airport_icao' => $departure_airport_icao,':arrival_airport_icao' => $arrival_airport_icao);
			} elseif ($arrival_airport_time == '') {
			    $query = "SELECT COUNT(*) FROM routes WHERE CallSign = :ident AND FromAirport_ICAO = :departure_airport_icao AND FromAirport_Time = :departure_airport_time AND ToAirport_ICAO = :arrival_airport_icao";
			    $query_values = array(':ident' => $ident,':departure_airport_icao' => $departure_airport_icao,':departure_airport_time' => $departure_airport_time,':arrival_airport_icao' => $arrival_airport_icao);
			} elseif ($departure_airport_time == '') {
			    $query = "SELECT COUNT(*) FROM routes WHERE CallSign = :ident AND FromAirport_ICAO = :departure_airport_icao AND ToAirport_ICAO = :arrival_airport_icao AND ToAirport_Time = :arrival_airport_time";
			    $query_values = array(':ident' => $ident,':departure_airport_icao' => $departure_airport_icao,':arrival_airport_icao' => $arrival_airport_icao,':arrival_airport_time' => $arrival_airport_time);
			} else {
			    //$query = "SELECT COUNT(*) FROM schedule WHERE ident = :ident AND departure_airport_icao = :departure_airport_icao AND departure_airport_time = :departure_airport_time AND arrival_airport_icao = :arrival_airport_icao AND arrival_airport_time = :arrival_airport_time";
			    $query = "SELECT COUNT(*) FROM routes WHERE CallSign = :ident AND FromAirport_ICAO = :departure_airport_icao AND FromAirport_Time = :departure_airport_time AND ToAirport_ICAO = :arrival_airport_icao AND ToAirport_Time = :arrival_airport_time";
			    $query_values = array(':ident' => $ident,':departure_airport_icao' => $departure_airport_icao,':departure_airport_time' => $departure_airport_time,':arrival_airport_icao' => $arrival_airport_icao,':arrival_airport_time' => $arrival_airport_time);
			}
			try {
				$sth = $this->db->prepare($query);
				$sth->execute($query_values);
			} catch(PDOException $e) {
				return "error : ".$e->getMessage();
			}
			if ($sth->fetchColumn() == 0) {
				//$query = 'UPDATE schedule SET departure_airport_icao = :departure_airport_icao, departure_airport_time = :departure_airport_time, arrival_airport_icao = :arrival_airport_icao, arrival_airport_time = :arrival_airport_time, date_modified = :date, source = :source WHERE ident = :ident';
				if ($departure_airport_time == '' && $arrival_airport_time == '') {
                            	    $query = 'UPDATE routes SET FromAirport_ICAO = :departure_airport_icao, ToAirport_ICAO = :arrival_airport_icao, date_modified = :date, Source = :source WHERE CallSign = :ident';
				    $query_values = array(':ident' => $ident,':departure_airport_icao' => $departure_airport_icao,':arrival_airport_icao' => $arrival_airport_icao, ':date' => $date, ':source' => $source);
				} elseif ($arrival_airport_time == '') {
                            	    $query = 'UPDATE routes SET FromAirport_ICAO = :departure_airport_icao, FromAiport_Time = :departure_airport_time, ToAirport_ICAO = :arrival_airport_icao, date_modified = :date, Source = :source WHERE CallSign = :ident';
				    $query_values = array(':ident' => $ident,':departure_airport_icao' => $departure_airport_icao,':departure_airport_time' => $departure_airport_time,':arrival_airport_icao' => $arrival_airport_icao, ':date' => $date, ':source' => $source);
				} elseif ($departure_airport_time == '') {
                            	    $query = 'UPDATE routes SET FromAirport_ICAO = :departure_airport_icao, ToAirport_ICAO = :arrival_airport_icao, ToAirport_Time = :arrival_airport_time, date_modified = :date, Source = :source WHERE CallSign = :ident';
				    $query_values = array(':ident' => $ident,':departure_airport_icao' => $departure_airport_icao,':arrival_airport_icao' => $arrival_airport_icao,':arrival_airport_time' => $arrival_airport_time, ':date' => $date, ':source' => $source);
				} else {
                            	    $query = 'UPDATE routes SET FromAirport_ICAO = :departure_airport_icao, FromAiport_Time = :departure_airport_time, ToAirport_ICAO = :arrival_airport_icao, ToAirport_Time = :arrival_airport_time, date_modified = :date, Source = :source WHERE CallSign = :ident';
				    $query_values = array(':ident' => $ident,':departure_airport_icao' => $departure_airport_icao,':departure_airport_time' => $departure_airport_time,':arrival_airport_icao' => $arrival_airport_icao,':arrival_airport_time' => $arrival_airport_time, ':date' => $date, ':source' => $source);
				}
				 try {
					$sth = $this->db->prepare($query);
					$sth->execute($query_values);
				} catch(PDOException $e) {
					return "error : ".$e->getMessage();
				}
			} else {
				//$query = 'UPDATE schedule SET date_lastseen = :date WHERE ident = :ident';
				$query = 'UPDATE routes SET date_lastseen = :date WHERE CallSign = :ident';
				$query_values = array(':ident' => $ident,':date' => $date);
				 try {
					$sth = $this->db->prepare($query);
					$sth->execute($query_values);
				} catch(PDOException $e) {
					return "error : ".$e->getMessage();
				}
			}
		} else {
			//$query = 'INSERT INTO  schedule (ident,departure_airport_icao, departure_airport_time, arrival_airport_icao, arrival_airport_time,date_added,source)  VALUES (:ident,:departure_airport_icao,:departure_airport_time,:arrival_airport_icao,:arrival_airport_time,:date,:source)';
			$query = 'INSERT INTO  routes (CallSign,FromAirport_ICAO, FromAirport_Time, ToAirport_ICAO, ToAirport_Time,date_added,source)  VALUES (:ident,:departure_airport_icao,:departure_airport_time,:arrival_airport_icao,:arrival_airport_time,:date,:source)';
			$query_values = array(':ident' => $ident,':departure_airport_icao' => $departure_airport_icao,':departure_airport_time' => $departure_airport_time,':arrival_airport_icao' => $arrival_airport_icao,':arrival_airport_time' => $arrival_airport_time, ':date' => $date, ':source' => $source);
			 try {
				$sth = $this->db->prepare($query);
				$sth->execute($query_values);
			} catch(PDOException $e) {
				return "error : ".$e->getMessage();
			}
			// FIXME : add to routes
		}
        
	}

	public function getSchedule($ident) {
	        //$query = "SELECT * FROM schedule WHERE ident = :ident LIMIT 1";
	        $Translation = new Translation();
	        $operator = $Translation->checkTranslation($ident,false);
	        if ($ident != $operator) {
	    		$query = "SELECT FromAirport_ICAO as departure_airport_icao, ToAirport_ICAO as arrival_airport_icao, FromAirport_Time as departure_airport_time, ToAirport_Time as arrival_airport_time FROM routes WHERE CallSign = :operator OR CallSign = :ident LIMIT 1";
	    		$query_values = array(':ident' => $ident,'operator' => $operator);
	    	} else {
		        $query = "SELECT FromAirport_ICAO as departure_airport_icao, ToAirport_ICAO as arrival_airport_icao, FromAirport_Time as departure_airport_time, ToAirport_Time as arrival_airport_time FROM routes WHERE CallSign = :ident LIMIT 1";
	    		$query_values = array(':ident' => $ident);
	    	}
		 try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
		$row = $sth->fetch(PDO::FETCH_ASSOC);
		if (count($row) > 0) {
			return $row;
		} else return array();
	}

	public function checkSchedule($ident) {
	
	        //$query = "SELECT COUNT(*) as nb FROM schedule WHERE ident = :ident AND date_added > DATE_SUB(CURDATE(), INTERVAL 8 DAY) - 8 LIMIT 1";
	        $query = "SELECT COUNT(*) as nb FROM routes WHERE CallSign = :ident AND ((date_added BETWEEN DATE(DATE_SUB(CURDATE(), INTERVAL 15 DAY)) AND DATE(NOW()) and date_modified IS NULL) OR (date_modified BETWEEN DATE(DATE_SUB(CURDATE(), INTERVAL 15 DAY)) AND DATE(NOW()))) LIMIT 1";
	        $query_values = array(':ident' => $ident);
		 try {
			$sth = $this->db->prepare($query);
			$sth->execute($query_values);
		} catch(PDOException $e) {
			return "error : ".$e->getMessage();
		}
		$row = $sth->fetch(PDO::FETCH_ASSOC);
		return $row['nb'];
	}

	/**
	* Get flight info from Air France
	* @param String $callsign The callsign
	* @param String $date date we want flight number info
	* @param String $carrier IATA code
	* @return Flight departure and arrival airports and time
	*/
	private function getAirFrance($callsign, $date = 'NOW',$carrier = 'AF') {
		$Common = new Common();
		$check_date = new Datetime($date);
		$numvol = preg_replace('/^[A-Z]*/','',$callsign);
		if (!filter_var($numvol,FILTER_VALIDATE_INT)) return array();
		$url = "http://www.airfrance.fr/cgi-bin/AF/FR/fr/local/resainfovol/infovols/detailsVolJson.do?codeCompagnie[0]=".$carrier."&numeroVol[0]=".$numvol."&dayFlightDate=".$check_date->format('d')."&yearMonthFlightDate=".$check_date->format('Ym');
		$json = $Common->getData($url);
	
		$parsed_json = json_decode($json);
		if (property_exists($parsed_json,'errors') === false) {
			$originLong = $parsed_json->{'flightsList'}[0]->{'segmentsList'}[0]->{'originLong'};
			$originShort = $parsed_json->{'flightsList'}[0]->{'segmentsList'}[0]->{'originShort'};
			$departureDateMedium = $parsed_json->{'flightsList'}[0]->{'segmentsList'}[0]->{'departureDateMedium'};
			$departureTime = $parsed_json->{'flightsList'}[0]->{'segmentsList'}[0]->{'departureTime'};
			$destinationLong = $parsed_json->{'flightsList'}[0]->{'segmentsList'}[0]->{'destinationLong'};
			$destinationShort = $parsed_json->{'flightsList'}[0]->{'segmentsList'}[0]->{'destinationShort'};
			$arrivalDateMedium = $parsed_json->{'flightsList'}[0]->{'segmentsList'}[0]->{'arrivalDateMedium'};
			$arrivalTime = $parsed_json->{'flightsList'}[0]->{'segmentsList'}[0]->{'arrivalTime'};

			preg_match('/\((.*?)\)/',$originShort,$originiata);
			$DepartureAirportIata = $originiata[1];
			preg_match('/\((.*?)\)/',$destinationShort,$destinationiata);
			$ArrivalAirportIata = $destinationiata[1];

			/*
			date_default_timezone_set('Europe/Paris');
			$departureTime = gmdate('H:i',strtotime($departureTime));
			$arrivalTime = gmdate('H:i',strtotime($arrivalTime));
			*/
		
			return array('DepartureAirportIATA' => $DepartureAirportIata,'DepartureTime' => $departureTime,'ArrivalAirportIATA' => $ArrivalAirportIata,'ArrivalTime' => $arrivalTime,'Source' => 'website_airfrance');
		} else return array();
	}

	/**
	* Get flight info from EasyJet
	* @param String $callsign The callsign
	* @param String $date date we want flight number info
	* @return Flight departure and arrival airports and time
	*/
	private function getEasyJet($callsign, $date = 'NOW') {
		global $globalTimezone;
		$Common = new Common();
		date_default_timezone_set($globalTimezone);
		$check_date = new Datetime($date);
		$numvol = preg_replace('/^[A-Z]*/','',$callsign);
		if (!filter_var($numvol,FILTER_VALIDATE_INT)) return array();
		$url = "http://www.easyjet.com/ft/api/flights?date=".$check_date->format('Y-m-d')."&fn=".$callsign;
		$json = $Common->getData($url);
		$parsed_json = json_decode($json);

		$flights = $parsed_json->{'flights'};
		if (count($flights) > 0) {
			$DepartureAirportIata = $parsed_json->{'flights'}[0]->{'airports'}->{'pda'}->{'iata'}; //name
			$ArrivalAirportIata = $parsed_json->{'flights'}[0]->{'airports'}->{'paa'}->{'iata'}; //name
			$departureTime = $parsed_json->{'flights'}[0]->{'dates'}->{'fstd'};
			$arrivalTime = $parsed_json->{'flights'}[0]->{'dates'}->{'fsta'};

			return array('DepartureAirportIATA' => $DepartureAirportIata,'DepartureTime' => $departureTime,'ArrivalAirportIATA' => $ArrivalAirportIata,'ArrivalTime' => $arrivalTime,'Source' => 'website_easyjet');
		} else return array();
	}

	/**
	* Get flight info from Ryanair
	* @param String $callsign The callsign
	* @return Flight departure and arrival airports and time
	*/
	private function getRyanair($callsign) {
		$Common = new Common();
		$numvol = preg_replace('/^[A-Z]*/','',$callsign);
		if (!filter_var($numvol,FILTER_VALIDATE_INT)) return array();
		$url = "http://www.ryanair.com/fr/api/2/flight-info/0/50/";
		$post = '{"flight":"'.$numvol.'","minDepartureTime":"00:00","maxDepartureTime":"23:59"}';
		$headers = array('Content-Type: application/json','Content-Length: ' . strlen($post));
		$json = $Common->getData($url,'post',$post,$headers);
		$parsed_json = json_decode($json);
		if (isset($parsed_json->{'flightInfo'})) {
			$flights = $parsed_json->{'flightInfo'};
			if (count($flights) > 0) {
				$DepartureAirportIata = $parsed_json->{'flightInfo'}[0]->{'departureAirport'}->{'iata'}; //name
				$ArrivalAirportIata = $parsed_json->{'flightInfo'}[0]->{'arrivalAirport'}->{'iata'}; //name
				$departureTime = $parsed_json->{'flightInfo'}[0]->{'departureTime'};
				$arrivalTime = $parsed_json->{'flightInfo'}[0]->{'arrivalTime'};
				return array('DepartureAirportIATA' => $DepartureAirportIata,'DepartureTime' => $departureTime,'ArrivalAirportIATA' => $ArrivalAirportIata,'ArrivalTime' => $arrivalTime, 'Source' => 'website_ryanair');
			} else return array();
		} else return array();
	}

	/**
	* Get flight info from Swiss
	* @param String $callsign The callsign
	* @return Flight departure and arrival airports and time
	*/
	private function getSwiss($callsign) {
		$Common = new Common();
		$numvol = preg_replace('/^[A-Z]*/','',$callsign);
		if (!filter_var($numvol,FILTER_VALIDATE_INT)) return array();
		$url = "http://www.world-of-swiss.com/fr/routenetwork.json";
		$json = $Common->getData($url);
		$parsed_json = json_decode($json);


		$flights = $parsed_json->{'flights'};
		if (count($flights) > 0) {
			foreach ($flights as $flight) {
				if ($flight->{'no'} == "Vol LX ".$numvol) {
					$DepartureAirportIata = $flight->{'from'}->{'code'}; //city
					$ArrivalAirportIata = $flight->{'to'}->{'code'}; //city
					$departureTime = substr($flight->{'from'}->{'hour'},0,5);
					$arrivalTime = substr($flight->{'to'}->{'hour'},0,5);
				}
			}
			if (isset($DepartureAirportIata)) {
				return array('DepartureAirportIATA' => $DepartureAirportIata,'DepartureTime' => $departureTime,'ArrivalAirportIATA' => $ArrivalAirportIata,'ArrivalTime' => $arrivalTime,'Source' => 'website_swiss');
			} else return array();
		} else return array();
	}
	
	/**
	* Get flight info from British Airways API
	* @param String $callsign The callsign
	* @param String $date date we want flight number info
	* @return Flight departure and arrival airports and time
	*/
	private function getBritishAirways($callsign, $date = 'NOW') {
		global $globalBritishAirwaysKey;
		$Common = new Common();
		$check_date = new Datetime($date);
		$numvol = sprintf('%04d',preg_replace('/^[A-Z]*/','',$callsign));
		if (!filter_var(preg_replace('/^[A-Z]*/','',$callsign),FILTER_VALIDATE_INT)) return array();
		if ($globalBritishAirwaysKey == '') return array();
		$url = "https://api.ba.com/rest-v1/v1/flights;flightNumber=".$numvol.";scheduledDepartureDate=".$check_date->format('Y-m-d').".json";
		$headers = array('Client-Key: '.$globalBritishAirwaysKey);
		$json = $Common->getData($url,'get','',$headers);
		if ($json == '') return array();
		$parsed_json = json_decode($json);
		$flights = $parsed_json->{'FlightsResponse'};
		if (count($flights) > 0) {
			$DepartureAirportIata = $parsed_json->{'FlightsResponse'}->{'Flight'}->{'Sector'}->{'DepartureAirport'};
			$ArrivalAirportIata = $parsed_json->{'FlightsResponse'}->{'Flight'}->{'Sector'}->{'ArrivalAirport'};
			$departureTime = date('H:i',strtotime($parsed_json->{'FlightsResponse'}->{'Flight'}->{'Sector'}->{'ScheduledDepartureDateTime'}));
			$arrivalTime = date('H:i',strtotime($parsed_json->{'FlightsResponse'}->{'Flight'}->{'Sector'}->{'ScheduledArrivalDateTime'}));
			return array('DepartureAirportIATA' => $DepartureAirportIata,'DepartureTime' => $departureTime,'ArrivalAirportIATA' => $ArrivalAirportIata,'ArrivalTime' => $arrivalTime,'Source' => 'website_britishairways');
		} else return array();
	}

	/**
	* Get flight info from Lutfhansa API
	* @param String $callsign The callsign
	* @param String $date date we want flight number info
	* @return Flight departure and arrival airports and time
	*/
	private function getLufthansa($callsign, $date = 'NOW') {
		global $globalLufthansaKey;
		$Common = new Common();
		$check_date = new Datetime($date);
		$numvol = sprintf('%04d',preg_replace('/^[A-Z]*/','',$callsign));
		if (!filter_var(preg_replace('/^[A-Z]*/','',$callsign),FILTER_VALIDATE_INT)) return array();
		if (!isset($globalLufthansaKey) || $globalLufthansaKey == '' || !isset($globalLufthansaKey['key']) || $globalLufthansaKey['key'] == '') return array();
		$url = "https://api.lufthansa.com/v1/oauth/token";
		$post = array('client_id' => $globalLufthansaKey['key'],'client_secret' => $globalLufthansaKey['secret'],'grant_type' => 'client_credentials');
		$data = $Common->getData($url,'post',$post);
		$parsed_data = json_decode($data);
		$token = $parsed_data->{'access_token'};
		
		$url = "https://api.lufthansa.com/v1/operations/flightstatus/LH".$numvol."/".$check_date->format('Y-m-d');
		$headers = array('Authorization: Bearer '.$token,'Accept: application/json');
		$json = $Common->getData($url,'get','',$headers);
		if ($json == '') return array();
		$parsed_json = json_decode($json);
		if (isset($parsed_json->{'FlightStatusResource'}) && count($parsed_json->{'FlightStatusResource'}) > 0) {
			$DepartureAirportIata = $parsed_json->{'FlightStatusResource'}->{'Flights'}->{'Flight'}->{'Departure'}->{'AirportCode'};
			$departureTime = date('H:i',strtotime($parsed_json->{'FlightStatusResource'}->{'Flights'}->{'Flight'}->{'Departure'}->{'ScheduledTimeLocal'}->{'DateTime'}));
			$ArrivalAirportIata = $parsed_json->{'FlightStatusResource'}->{'Flights'}->{'Flight'}->{'Arrival'}->{'AirportCode'};
			$arrivalTime = date('H:i',strtotime($parsed_json->{'FlightStatusResource'}->{'Flights'}->{'Flight'}->{'Arrival'}->{'ScheduledTimeLocal'}->{'DateTime'}));
			return array('DepartureAirportIATA' => $DepartureAirportIata,'DepartureTime' => $departureTime,'ArrivalAirportIATA' => $ArrivalAirportIata,'ArrivalTime' => $arrivalTime,'Source' => 'website_lufthansa');
		} else return array();
	}

	/**
	* Get flight info from Transavia API
	* @param String $callsign The callsign
	* @param String $date date we want flight number info
	* @return Flight departure and arrival airports and time
	*/
	private function getTransavia($callsign, $date = 'NOW') {
		global $globalTransaviaKey;
		$Common = new Common();
		$check_date = new Datetime($date);
		$numvol = sprintf('%04d',preg_replace('/^[A-Z]*/','',$callsign));
		if (!filter_var(preg_replace('/^[A-Z]*/','',$callsign),FILTER_VALIDATE_INT)) return array();
		if ($globalTransaviaKey == '') return array();
		$url = "https://tst.api.transavia.com/v1/flightstatus/departuredate/".$check_date->format('Ymd').'/flightnumber/HV'.$numvol;
		$headers = array('apikey: '.$globalTransaviaKey);
		$json = $Common->getData($url,'get','',$headers);
		if ($json == '') return array();
		$parsed_json = json_decode($json);
		
		if (isset($parsed_json->{'data'}[0])) {
			$DepartureAirportIata = $parsed_json->{'data'}[0]->{'flight'}->{'departureAirport'}->{'locationCode'};
			$departureTime = date('H:i',strtotime($parsed_json->{'data'}[0]->{'flight'}->{'departureDateTime'}));
			$ArrivalAirportIata = $parsed_json->{'data'}[0]->{'flight'}->{'arrivalAirport'}->{'locationCode'};
			$arrivalTime = date('H:i',strtotime($parsed_json->{'data'}[0]->{'flight'}->{'arrivalDateTime'}));
			return array('DepartureAirportIATA' => $DepartureAirportIata,'DepartureTime' => $departureTime,'ArrivalAirportIATA' => $ArrivalAirportIata,'ArrivalTime' => $arrivalTime,'Source' => 'website_transavia');
		} else return array();
	}

	/**
	* Get flight info from Tunisair
	* @param String $callsign The callsign
	* @return Flight departure and arrival airports and time
	*/
	private function getTunisair($callsign) {
		$Common = new Common();
		$numvol = preg_replace('/^[A-Z]*/','',$callsign);
		if (!filter_var($numvol,FILTER_VALIDATE_INT)) return array();
		$url = "http://www.tunisair.com/site/publish/module/Volj/fr/Flight_List.asp";
		$data = $Common->getData($url);
		$table = $Common->table2array($data);
		foreach ($table as $flight) {
			if (isset($flight[1]) && $flight[1] == "TU ".sprintf('%04d',$numvol)) {
				return array('DepartureAirportIATA' => $flight[2],'DepartureTime' => str_replace('.',':',$flight[5]),'ArrivalAirportIATA' => $flight[3],'ArrivalTime' => str_replace('.',':',$flight[6]),'Source' => 'website_tunisair');
			}
		}
		return array();
	}

	/**
	* Get flight info from Vueling
	* @param String $callsign The callsign
	* @return Flight departure and arrival airports and time
	*/
	private function getVueling($callsign) {
		$Common = new Common();
		$numvol = preg_replace('/^[A-Z]*/','',$callsign);
		if (!filter_var($numvol,FILTER_VALIDATE_INT)) return array();
		$url = "https://www.vueling.com/Base/BaseProxy/RenderMacro/?macroalias=DailyFlights&OriginSelected=&DestinationSelected=&idioma=en-GB&pageid=30694&ItemsByPage=50&FlightNumberFilter=".$numvol;
		$data = $Common->getData($url);
		if ($data != '') {
			$table = $Common->table2array($data);
			foreach ($table as $flight) {
				if (count($flight) > 0 && $flight[0] == "VY".$numvol && isset($flight[13])) {
					preg_match('/flightOri=[A-Z]{3}/',$flight[13],$result);
					$DepartureAirportIata = str_replace('flightOri=','',$result[0]);
					preg_match('/flightDest=[A-Z]{3}/',$flight[13],$result);
					$ArrivalAirportIata = str_replace('flightDest=','',$result[0]);
					return array('DepartureAirportIATA' => $DepartureAirportIata,'DepartureTime' => $flight[3],'ArrivalAirportIATA' => $ArrivalAirportIata,'ArrivalTime' => $flight[4],'Source' => 'website_vueling');
				}
			}
		}
		return array();
	}

	/**
	* Get flight info from Iberia
	* @param String $callsign The callsign
	* @param String $date date we want flight number info
	* @return Flight departure and arrival airports and time
	*/
	private function getIberia($callsign, $date = 'NOW') {
		$Common = new Common();
		$numvol = preg_replace('/^[A-Z]*/','',$callsign);
		$check_date = new Datetime($date);
		if (!filter_var($numvol,FILTER_VALIDATE_INT)) return array();
		$url = "https://www.iberia.com/web/flightDetail.do";
		$post = array('numvuelo' => $numvol,'fecha' => $check_date->format('Ymd'),'airlineID' => 'IB');
		$data = $Common->getData($url,'post',$post);
		if ($data != '') {
			$table = $Common->table2array($data);
			//print_r($table);
			if (count($table) > 0) {
				$flight = $table;
				preg_match('/([A-Z]{3})/',$flight[3][0],$DepartureAirportIataMatch);
				preg_match('/([A-Z]{3})/',$flight[5][0],$ArrivalAirportIataMatch);
				$DepartureAirportIata = $DepartureAirportIataMatch[0];
				$ArrivalAirportIata = $ArrivalAirportIataMatch[0];
				$departureTime = substr(trim(str_replace(' lunes','',str_replace('&nbsp;','',$flight[3][2]))),0,5);
				$arrivalTime = trim(str_replace(' lunes','',str_replace('&nbsp;','',$flight[5][1])));
				if ($arrivalTime == 'Hora estimada de llegada') {
					$arrivalTime = substr(trim(str_replace(' lunes','',str_replace('&nbsp;','',$flight[5][2]))),0,5);
				} else $arrivalTime = substr($arrivalTime,0,5);
				return array('DepartureAirportIATA' => $DepartureAirportIata,'DepartureTime' => $departureTime,'ArrivalAirportIATA' => $ArrivalAirportIata,'ArrivalTime' => $arrivalTime,'Source' => 'website_iberia');
			}
		}
		return array();
	}

	/**
	* Get flight info from Star Alliance
	* @param String $callsign The callsign
	* @param String $date date we want flight number info
	* @return Flight departure and arrival airports and time
	*/
	private function getStarAlliance($callsign, $date = 'NOW',$carrier = '') {
		$Common = new Common();
		$numvol = preg_replace('/^[A-Z]*/','',$callsign);
		$check_date = new Datetime($date);
		if (!filter_var($numvol,FILTER_VALIDATE_INT)) return array();
		$url = "http://www.staralliance.com/flifoQueryAction.do?myAirline=&airlineCode=".$carrier."&flightNo=".$numvol."&day=".$check_date->format('d')."&month=".$check_date->format('m')."&year=".$check_date->format('Y')."&departuredate=".$check_date->format('d-M-Y');
		$data = $Common->getData($url);
		if ($data != '') {
			$table = $Common->table2array($data);
			if (count($table) > 0) {
				$flight = $table;
				//print_r($table);
				if (isset($flight[25]) && isset($flight[29])) {
					preg_match('/([A-Z]{3})/',$flight[25][1],$DepartureAirportIataMatch);
					preg_match('/([A-Z]{3})/',$flight[25][3],$ArrivalAirportIataMatch);
					$DepartureAirportIata = $DepartureAirportIataMatch[0];
					$ArrivalAirportIata = $ArrivalAirportIataMatch[0];
					$departureTime = substr(trim(str_replace('Scheduled: ','',$flight[29][0])),0,5);
					$arrivalTime = substr(trim(str_replace('Scheduled: ','',$flight[29][1])),0,5);
					return array('DepartureAirportIATA' => $DepartureAirportIata,'DepartureTime' => $departureTime,'ArrivalAirportIATA' => $ArrivalAirportIata,'ArrivalTime' => $arrivalTime,'Source' => 'website_staralliance');
				} else return array();
			}
			

		}
		return array();
	}

	/**
	* Get flight info from Alitalia
	* @param String $callsign The callsign
	* @param String $date date we want flight number info
	* @return Flight departure and arrival airports and time
	*/
	private function getAlitalia($callsign, $date = 'NOW') {
		$Common = new Common();
		$numvol = preg_replace('/^[A-Z]*/','',$callsign);
		$check_date = new Datetime($date);
		$url= "http://booking.alitalia.com/FlightStatus/fr_fr/FlightInfo?Brand=az&NumeroVolo=".$numvol."&DataCompleta=".$check_date->format('d/m/Y');
		if (!filter_var($numvol,FILTER_VALIDATE_INT)) return array();
		$data = $Common->getData($url);
		if ($data != '') {
			$table = $Common->text2array($data);
			$DepartureAirportIata = '';
			$ArrivalAirportIata = '';
			$departureTime = $table[4];
			$arrivalTime = $table[5];
			return array('DepartureAirportIATA' => $DepartureAirportIata,'DepartureTime' => $departureTime,'ArrivalAirportIATA' => $ArrivalAirportIata,'ArrivalTime' => $arrivalTime,'Source' => 'website_alitalia');
		}
	}

	/**
	* Get flight info from Brussels airlines
	* @param String $callsign The callsign
	* @param String $date date we want flight number info
	* @return Flight departure and arrival airports and time
	*/
	private function getBrussels($callsign, $date = 'NOW') {
		$Common = new Common();
		$numvol = preg_replace('/^[A-Z]*/','',$callsign);
		$check_date = new Datetime($date);
		$url= "http://www.brusselsairlines.com/api/flightstatus/getresults?from=NA&to=NA&date=".$check_date->format('d/m/Y')."&hour=NA&lookup=flightnumber&flightnumber=".$numvol."&publicationID=302";
		//http://www.brusselsairlines.com/fr-fr/informations-pratiques/statut-de-votre-vol/resultat.aspx?flightnumber=".$numvol."&date=".$check_date->format('d/m/Y')."&lookup=flightnumber";
		if (!filter_var($numvol,FILTER_VALIDATE_INT)) return array();
		$data = $Common->getData($url);
		if ($data != '') {
		    //echo $data;
		    $parsed_json = json_decode($data);
		    if (isset($parsed_json[0]->FromAirportCode)) {
			$DepartureAirportIata = $parsed_json[0]->FromAirportCode;
			$ArrivalAirportIata = $parsed_json[0]->ToAirportCode;
			$departureTime = date('H:i',strtotime($parsed_json[0]->ScheduledDepatureDate));
			$arrivalTime = date('H:i',strtotime($parsed_json[0]->ScheduledArrivalDate));
			return array('DepartureAirportIATA' => $DepartureAirportIata,'DepartureTime' => $departureTime,'ArrivalAirportIATA' => $ArrivalAirportIata,'ArrivalTime' => $arrivalTime,'Source' => 'website_brussels');
		    }
		}
	}

	/**
	* Get flight info from FlightRadar24
	* @param String $callsign The callsign
	* @param String $date date we want flight number info
	* @return Flight departure and arrival airports and time
	*/
	public function getFlightRadar24($callsign, $date = 'NOW') {
		$Common = new Common();
		$url= "http://arn.data.fr24.com/zones/fcgi/feed.js?flight=".$callsign;
		$data = $Common->getData($url);
		if ($data != '') {
			$parsed_json = get_object_vars(json_decode($data));
			if (count($parsed_json) > 2) {
				$info = array_splice($parsed_json,2,1);
				$fr24id = current(array_keys($info));
				$urldata = "http://krk.data.fr24.com/_external/planedata_json.1.4.php?f=".$fr24id;
				$datapl = $Common->getData($urldata);
				if ($datapl != '') {
					$parsed_jsonpl = json_decode($datapl);
					if (isset($parsed_jsonpl->from_iata)) {
						$DepartureAirportIata = $parsed_jsonpl->from_iata;
						$ArrivalAirportIata = $parsed_jsonpl->to_iata;
						$departureTime = date('H:i',$parsed_jsonpl->dep_schd);
						$arrivalTime = date('H:i',$parsed_jsonpl->arr_schd);
						return array('DepartureAirportIATA' => $DepartureAirportIata,'DepartureTime' => $departureTime,'ArrivalAirportIATA' => $ArrivalAirportIata,'ArrivalTime' => $arrivalTime,'Source' => 'website_flightradar24');
					}
				}
			}
		}
		return array();
	}

	/**
	* Get flight info from Lufthansa
	* @param String $callsign The callsign
	* @param String $date date we want flight number info
	* @return Flight departure and arrival airports and time
	*/

/*	private function getLufthansa($callsign, $date = 'NOW') {
		$Common = new Common();
		*/
		//$numvol = preg_replace('/^[A-Z]*/','',$callsign);
/*
		$url= "http://www.lufthansa.com/fr/fr/Arrivees-Departs-fonction";
		$check_date = new Datetime($date);
		if (!filter_var($numvol,FILTER_VALIDATE_INT)) return array();

		$post = array('flightNumber' => $numvol, 'date' => $check_date->format('Y-m-d'),'time' => '12:00','timezoneOffset' => '0','selection' => '0','arrivalDeparture' => 'D');
		$data = $Common->getData($url,'post',$post);
		if ($data != '') {
			$table = $Common->table2array($data);
			$departureTime = trim(str_replace($check_date->format('d.m.Y'),'',$table[25][3]));
		}

		$post = array('flightNumber' => $numvol, 'date' => $check_date->format('Y-m-d'),'time' => '12:00','timezoneOffset' => '0','selection' => '0','arrivalDeparture' => 'A');
		$data = $Common->getData($url,'post',$post);
		if ($data != '') {
			$table = $Common->table2array($data);
			$arrivalTime = trim(str_replace($check_date->format('d.m.Y'),'',$table[25][3]));
		}
		return array('DepartureAirportIATA' => '','DepartureTime' => $departureTime,'ArrivalAirportIATA' => '','ArrivalTime' => $arrivalTime,'Source' => 'website_lufthansa');
	}
  */
	/**
	* Get flight info from flytap
	* @param String $callsign The callsign
	* @param String $date date we want flight number info
	* @return Flight departure and arrival airports and time
	*/
	private function getFlyTap($callsign, $date = 'NOW') {
		$Common = new Common();
		$numvol = preg_replace('/^[A-Z]*/','',$callsign);
		$url= "http://www.flytap.com/France/fr/PlanifierEtReserver/Outils/DepartsEtArrivees";
		$check_date = new Datetime($date);
		if (!filter_var($numvol,FILTER_VALIDATE_INT)) return array();
		$post = array('arrivalsdepartures_content' => 'number','arrivalsdepartures_tp' => $numvol,'arrivalsdepartures_trk' => 'ARR','arrivalsdepartures_date_trk' => '1','aptCode' => '','arrivalsdepartures' => 'DEP','arrivalsdepartures_date' => '1','aptCodeFrom' => '','aptCodeTo' => '','arrivalsdepartures2' => 'DEP','arrivalsdepartures_date2' => '1');
		$data = $Common->getData($url,'post',$post);
		if ($data != '') {
			$table = $Common->table2array($data);
			$departureTime = trim(substr($table[15][0],0,5));
			$arrivalTime = trim(substr($table[35][0],0,5));
			preg_match('/([A-Z]{3})/',$table[11][0],$DepartureAirportIataMatch);
			preg_match('/([A-Z]{3})/',$table[31][0],$ArrivalAirportIataMatch);
			$DepartureAirportIata = $DepartureAirportIataMatch[0];
			$ArrivalAirportIata = $ArrivalAirportIataMatch[0];
			return array('DepartureAirportIATA' => $DepartureAirportIata,'DepartureTime' => $departureTime,'ArrivalAirportIATA' => $ArrivalAirportIata,'ArrivalTime' => $arrivalTime,'Source' => 'website_flytap');
		}
		return array();
	}

	/**
	* Get flight info from flightmapper
	* @param String $callsign The callsign
	* @param String $date date we want flight number info
	* @return Flight departure and arrival airports and time
	*/
	public function getFlightMapper($callsign, $date = 'NOW') {
		$Common = new Common();
		if (!is_numeric(substr($callsign, 0, 3)))
		{
			if (is_numeric(substr(substr($callsign, 0, 3), -1, 1))) {
				$airline_icao = substr($callsign, 0, 2);
			} elseif (is_numeric(substr(substr($callsign, 0, 4), -1, 1))) {
				$airline_icao = substr($callsign, 0, 3);
			} 
		}
		$numvol = preg_replace('/^[A-Z]*/','',$callsign);
		$url= "http://info.flightmapper.net/flight/".$airline_icao.'_'.$numvol;
		$check_date = new Datetime($date);
		if (!filter_var($numvol,FILTER_VALIDATE_INT)) return array();
		$data = $Common->getData($url);
		if ($data != '') {
			$table = $Common->table2array($data);
			if (isset($table[5][0])) {
				$sched = $table[5][0];
				$n = sscanf($sched,'%*s %5[0-9:] %*[^()] (%3[A-Z]) %5[0-9:] %*[^()] (%3[A-Z])',$dhour,$darr,$ahour,$aarr);
				if ($n == 7) {
				    $departureTime = $dhour;
				    $arrivalTime = $ahour;
				    $DepartureAirportIata = str_replace(array('(',')'),'',$darr);
				    $ArrivalAirportIata = str_replace(array('(',')'),'',$aarr);
				    return array('DepartureAirportIATA' => $DepartureAirportIata,'DepartureTime' => $departureTime,'ArrivalAirportIATA' => $ArrivalAirportIata,'ArrivalTime' => $arrivalTime,'Source' => 'website_flightmapper');
				}
			}
		}
		return array();
	}

	/**
	* Get flight info from flightaware
	* @param String $callsign The callsign
	* @param String $date date we want flight number info
	* @return Flight departure and arrival airports and time
	*/
	public function getFlightAware($callsign, $date = 'NOW') {
		$Common = new Common();
		if (!is_numeric(substr($callsign, 0, 3)))
		{
			if (is_numeric(substr(substr($callsign, 0, 3), -1, 1))) {
				$airline_icao = substr($callsign, 0, 2);
			} elseif (is_numeric(substr(substr($callsign, 0, 4), -1, 1))) {
				$airline_icao = substr($callsign, 0, 3);
			} 
		}
		$numvol = preg_replace('/^[A-Z]*/','',$callsign);
		$url= "http://fr.flightaware.com/live/flight/".$callsign;
		$check_date = new Datetime($date);
		if (!filter_var($numvol,FILTER_VALIDATE_INT)) return array();
		$data = $Common->getData($url);
		if ($data != '') {
			$table = $Common->table2array($data);
			if (isset($table[11][0])) {
				$departureTime = str_replace('h',':',substr($table[15][1],0,5));
				$arrivalTime = str_replace('h',':',substr($table[15][4],0,5));
				sscanf($table[11][1],'%*[^(] (%*4[A-Z]&nbsp;/&nbsp;%3[A-Z])',$DepartureAirportIata);
				sscanf($table[11][0],'%*[^(] (%*4[A-Z]&nbsp;/&nbsp;%3[A-Z])',$ArrivalAirportIata);
				return array('DepartureAirportIATA' => $DepartureAirportIata,'DepartureTime' => $departureTime,'ArrivalAirportIATA' => $ArrivalAirportIata,'ArrivalTime' => $arrivalTime,'Source' => 'website_flightaware');
			}
		}
		return array();
	}

	/**
	* Get flight info from CostToTravel
	* @param String $callsign The callsign
	* @param String $date date we want flight number info
	* @return Flight departure and arrival airports and time
	*/
	private function getCostToTravel($callsign, $date = 'NOW') {
		$Common = new Common();
		$url= "http://www.costtotravel.com/flight-number/".$callsign;
		$check_date = new Datetime($date);
		//if (!filter_var($numvol,FILTER_VALIDATE_INT)) return array();
		$data = $Common->getData($url);
		if ($data != '') {
			$table = $Common->table2array($data);
			//print_r($table);
			if (isset($table[11][1])) {
				$departureTime = substr($table[11][1],0,5);
				$arrivalTime = substr($table[17][1],0,5);
				$DepartureAirportIata = substr($table[13][1],0,3);
				$ArrivalAirportIata = substr($table[15][1],0,3);
				return array('DepartureAirportIATA' => $DepartureAirportIata,'DepartureTime' => $departureTime,'ArrivalAirportIATA' => $ArrivalAirportIata,'ArrivalTime' => $arrivalTime,'Source' => 'website_costtotravel');
			}
		}
		return array();
	}

	/**
	* Get flight info from Air Canada
	* @param String $callsign The callsign
	* @param String $date date we want flight number info
	* @return Flight departure and arrival airports and time
	*/
	private function getAirCanada($callsign,$date = 'NOW') {
		$Common = new Common();
		date_default_timezone_set('UTC');
		$check_date = new Datetime($date);
		$numvol = preg_replace('/^[A-Z]*/','',$callsign);
		$url= "http://services.aircanada.com/portal/rest/getFlightsByFlightNumber?forceTimetable=true&flightNumber=".$numvol."&carrierCode=AC&date=".$check_date->format('m-d-Y')."&app_key=AE919FDCC80311DF9BABC975DFD72085&cache=74249";
		if (!filter_var($numvol,FILTER_VALIDATE_INT)) return array();
		$data = $Common->getData($url);
		$dom = new DomDocument();
		$dom->loadXML($data);
		if ($dom->getElementsByTagName('DepartureStationInfo')->length == 0) return array();
		$departure = $dom->getElementsByTagName('DepartureStationInfo')->item(0);
		if (isset($departure->getElementsByTagName('Airport')->item(0)->firstChild->nodeValue)) {
			$DepartureAirportIata = $departure->getElementsByTagName('Airport')->item(0)->firstChild->nodeValue;
			$departureTime = date('H:i',strtotime($departure->getElementsByTagName('ScheduledTime')->item(0)->firstChild->nodeValue));
			$arrival = $dom->getElementsByTagName('ArrivalStationInfo')->item(0);
			$ArrivalAirportIata = $arrival->getElementsByTagName('Airport')->item(0)->firstChild->nodeValue;
			$arrivalTime = date('H:i',strtotime($arrival->getElementsByTagName('ScheduledTime')->item(0)->firstChild->nodeValue));
			return array('DepartureAirportIATA' => $DepartureAirportIata,'DepartureTime' => $departureTime,'ArrivalAirportIATA' => $ArrivalAirportIata,'ArrivalTime' => $arrivalTime,'Source' => 'website_aircanada');
		} else return array();
	}

	/**
	* Get flight info from Vietnam Airlines
	* @param String $callsign The callsign
	* @param String $date date we want flight number info
	* @return Flight departure and arrival airports and time
	*/
	private function getVietnamAirlines($callsign, $date = 'NOW') {
		$Common = new Common();
		$numvol = preg_replace('/^[A-Z]*/','',$callsign);
		$check_date = new Datetime($date);
		$url= "https://cat.sabresonicweb.com/SSWVN/meridia?posid=VNVN&page=flifoFlightInfoDetailsMessage_learn&action=flightInfoDetails&airline=VN&language=fr&depDay=".$check_date->format('j')."&depMonth=".strtoupper($check_date->format('M'))."&=&flight=".$numvol."&";
		if (!filter_var($numvol,FILTER_VALIDATE_INT)) return array();
		$data = $Common->getData($url);
		if ($data != '') {
			$table = $Common->table2array($data);
			$flight = $table;
			preg_match('/([A-Z]{3})/',$flight[3][0],$DepartureAirportIataMatch);
			preg_match('/([A-Z]{3})/',$flight[21][0],$ArrivalAirportIataMatch);
			$DepartureAirportIata = $DepartureAirportIataMatch[0];
			$ArrivalAirportIata = $ArrivalAirportIataMatch[0];
			$departureTime = $flight[5][1];
			$arrivalTime = $flight[23][1];
			return array('DepartureAirportIATA' => $DepartureAirportIata,'DepartureTime' => $departureTime,'ArrivalAirportIATA' => $ArrivalAirportIata,'ArrivalTime' => $arrivalTime,'Source' => 'website_vietnamairlines');
		}
	}

	/**
	* Get flight info from Air Berlin
	* @param String $callsign The callsign
	* @param String $date date we want flight number info
	* @param String $carrier IATA code
	* @return Flight departure and arrival airports and time
	*/
	private function getAirBerlin($callsign, $date = 'NOW', $carrier = 'AB') {
		$Common = new Common();
		date_default_timezone_set('UTC');
		//AB = airberlin, HG/NLY = NIKI, 4T/BHP = Belair 
		$numvol = preg_replace('/^[A-Z]*/','',$callsign);
		$check_date = new Datetime($date);
		$url= "http://www.airberlin.com/en-US/site/aims.php";
		if (!filter_var($numvol,FILTER_VALIDATE_INT)) return array();
		$post = array('type' => 'departure','searchFlightNo' => '1','requestsent' => 'true', 'flightno' => $numvol,'date' => $check_date->format('Y-m-d'),'carrier' => 'AB');
		$data = $Common->getData($url,'post',$post);
		//echo $data;
		$DepartureAirportIata = '';
		$ArrivalAirportIata = '';
		
		if ($data != '') {
			$table = $Common->table2array($data);
			$flight = $table;
			if (isset($flight[5][4])) $departureTime = $flight[5][4];
			else $departureTime = '';
			if (isset($flight[5][2])) $departureAirport = $flight[5][2];
			else $departureAirport = '';
		}
		$post = array('type' => 'arrival','searchFlightNo' => '1','requestsent' => 'true', 'flightno' => $numvol,'date' => $check_date->format('Y-m-d'),'carrier' => 'AB');
		$data = $Common->getData($url,'post',$post);
		if ($data != '') {
			$table = $Common->table2array($data);
			$flight = $table;
			if (isset($flight[5][4])) {
			    $arrivalTime = $flight[5][4];
			    $arrivalAirport = $flight[5][3];
			} else {
			    $arrivalTime = '';
			    $arrivalAirport = '';
			}
		}
		$url = 'http://www.airberlin.com/en-US/site/json/suggestAirport.php?searchfor=departures&searchflightid=0&departures%5B%5D=&suggestsource%5B0%5D=activeairports&withcountries=0&withoutroutings=0&promotion%5Bid%5D=&promotion%5Btype%5D=&routesource%5B0%5D=airberlin&routesource%5B1%5D=partner';
		$json = $Common->getData($url);
		if ($json == '') return array();
		$parsed_json = json_decode($json);
		$airports = $parsed_json->{'suggestList'};
		if (count($airports) > 0) {
			foreach ($airports as $airinfo) {
				if ($airinfo->{'name'} == $departureAirport) {
					$DepartureAirportIata = $airinfo->{'code'};
				}
				if ($airinfo->{'name'} == $arrivalAirport) {
					$ArrivalAirportIata = $airinfo->{'code'};
				}
			}
		}
		if (isset($DepartureAirportIata)) {
			return array('DepartureAirportIATA' => $DepartureAirportIata,'DepartureTime' => $departureTime,'ArrivalAirportIATA' => $ArrivalAirportIata,'ArrivalTime' => $arrivalTime,'Source' => 'website_airberlin');
		} else return array();
	}


	
	public function fetchSchedule($ident,$date = 'NOW') {
		global $globalSchedulesSources, $globalSchedulesFetch;
		$Common = new Common();
		if (!$globalSchedulesFetch) return array();
		$airline_icao = '';
		if (!is_numeric(substr($ident, 0, 3)))
		{
			if (is_numeric(substr(substr($ident, 0, 3), -1, 1))) {
				$airline_icao = substr($ident, 0, 2);
			} elseif (is_numeric(substr(substr($ident, 0, 4), -1, 1))) {
				$airline_icao = substr($ident, 0, 3);
			} 
		}
		if ($airline_icao != '') {
			switch ($airline_icao) {
/*
				// Adria Airways
				case "ADR":
				case "JP":
					return Schedule->getStarAlliance($ident,$date,'JP');
					break;
				// Aegean Airlines
				case "AEE":
				case "A3":
					return Schedule->getStarAlliance($ident,$date,'A3');
					break;
				// Air Canada
				case "ACA":
				case "AC":
					return Schedule->getStarAlliance($ident,$date,'AC');
					break;
				// Air China
				case "CCA":
				case "CA":
					return Schedule->getStarAlliance($ident,$date,'CA');
					break;
				// Air India
				case "AIC":
				case "AI":
					return Schedule->getStarAlliance($ident,$date,'AI');
					break;
				// Air New Zealand
				case "ANZ":
				case "NZ":
					return Schedule->getStarAlliance($ident,$date,'NZ');
					break;
				// All Nippon Airways
				case "ANA":
				case "NH":
					return Schedule->getStarAlliance($ident,$date,'NH');
					break;
				// Asiana Airlines
				case "AAR":
				case "OZ":
					return Schedule->getStarAlliance($ident,$date,'OZ');
					break;
				// Austrian
				case "AUA":
				case "OS":
					return Schedule->getStarAlliance($ident,$date,'OS');
					break;
				// Avianca
				case "AVA":
				case "AV":
					return Schedule->getStarAlliance($ident,$date,'AV');
					break;
*/
				// Brussels Airlines
				case "BEL":
				case "SN":
					return $this->getBrussels($ident,$date,'SN');
					break;
/*
				// Copa Airlines
				case "CMP":
				case "CM":
					return Schedule->getStarAlliance($ident,$date,'CM');
					break;
				// Croatia Airlines
				case "CTN":
				case "OU":
					return Schedule->getStarAlliance($ident,$date,'OU');
					break;
				// Egyptair
				case "MSR":
				case "MS":
					return Schedule->getStarAlliance($ident,$date,'MS');
					break;
				// Ethiopian Airlines
				case "ETH":
				case "ET":
					return Schedule->getStarAlliance($ident,$date,'ET');
					break;
				// Eva Air
				case "EVA":
				case "BR":
					return Schedule->getStarAlliance($ident,$date,'BR');
					break;
				// LOT Polish Airlines
				case "LOT":
				case "LO":
					return Schedule->getStarAlliance($ident,$date,'LO');
					break;
				// Scandinavian Airlines
				case "SAS":
				case "SK":
					return Schedule->getStarAlliance($ident,$date,'SK');
					break;
				// Shenzhen Airlines
				case "CSZ":
				case "ZH":
					return Schedule->getStarAlliance($ident,$date,'ZH');
					break;
				// Singapore Airlines
				case "SIA":
				case "SQ":
					return Schedule->getStarAlliance($ident,$date,'SQ');
					break;
				// South African Airways
				case "SAA":
				case "SA":
					return Schedule->getStarAlliance($ident,$date,'SA');
					break;
*/
				// SWISS
				case "SWR":
				case "LX":
					return $this->getSwiss($ident);
					break;

				// TAP Portugal
				case "TAP":
				case "TP":
					return $this->getFlyTap($ident,$date);
					break;
/*
				// Thai Airways International
				case "THA":
				case "TG":
					return Schedule->getStarAlliance($ident,$date,'TG');
					break;
				// Turkish Airlines
				case "THY":
				case "TK":
					return Schedule->getStarAlliance($ident,$date,'TK');
					break;
				// United
				case "UAL":
				case "UA":
					return Schedule->getStarAlliance($ident,$date,'UA');
					break;
*/
				// Air France
				case "AF":
				case "AFR":
					return $this->getAirFrance($ident,$date,'AF');
					break;
				// HOP
				case "A5":
				case "HOP":
					return $this->getAirFrance($ident,$date,'A5');
					break;
				// EasyJet
				case "U2":
				case "DS":
				case "EZY":
				case "EZS":
					return $this->getEasyJet($ident,$date);
					break;
				// Ryanair
				case "FR":
				case "RYR":
					return $this->getRyanair($ident);
					break;
				// British Airways
				case "BA":
				case "SHT":
				case "BAW":
					return $this->getBritishAirways($ident);
					break;
				// Tunisair
				case "TUI":
				case "TAR":
				case "TU":
					return $this->getTunisair($ident);
					break;
				// Vueling
				case "VLG":
				case "VY":
					return $this->getVueling($ident);
					break;
				// Alitalia
				case "AZ":
				case "AZA":
					return $this->getAlitalia($ident);
					break;
				// Air Canada
				case "ACA":
				case "AC":
					return $this->getAirCanada($ident);
					break;
				// Lufthansa
				case "DLH":
				case "LH":
					return $this->getLufthansa($ident);
					break;
				// Transavia
				case "TRA":
				case "HV":
					return $this->getTransavia($ident);
					break;
					
/*
				case "DLH":
				case "LH":
					return $this->getStarAlliance($ident,$date,'LH');
					break;
*/
				// Iberia
				case "IBE":
				case "IB":
					return $this->getIberia($ident);
					break;
				// Vietnam Airlines
				case "HVN":
					return $this->getVietnamAirlines($ident,$date);
					break;
				// Air Berlin
				case "AB":
				case "BER":
					return $this->getAirBerlin($ident,$date,'AB');
					break;
				// NIKI
				case "HG":
				case "NLY":
					return $this->getAirBerlin($ident,$date,'HG');
					break;
				// BelAir
				case "4T":
				case "BHP":
					return $this->getAirBerlin($ident,$date,'4T');
					break;
				default:
					// Randomly use a generic function to get hours
					if (strlen($airline_icao) == 2) {
						if (!isset($globalSchedulesSources)) $globalSchedulesSources = array('flightmapper','costtotravel','flightradar24','flightaware');
						if (count($globalSchedulesSources) > 0) {
							$rand = mt_rand(0,count($globalSchedulesSources)-1);
							$source = $globalSchedulesSources[$rand];
							if ($source == 'flightmapper') return $this->getFlightMapper($ident,$date);
							elseif ($source == 'costtotravel') return $this->getCostToTravel($ident,$date);
							elseif ($source == 'flightradar24') return $this->getFlightRadar24($ident,$date);
							elseif ($source == 'flightaware') return $this->getFlightAware($ident,$date);
						}
					}
			}
		}
	        return array();
	}
}

//$Schedule = new Schedule();
//print_r($Schedule->fetchSchedule('HV5661'));
?>