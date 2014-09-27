<?php
require_once('libs/simple_html_dom.php');
require_once('settings.php');
// FIXME : timezones ?!

class Schedule {
	protected $cookies = array();
	
	/**
	* Get data from form result
	* @param String $url form URL
	* @param String $type type of submit form method (get or post)
	* @param String or Array $data values form post method
	* @param Array $headers header to submit with the form
	* @return String the result
	*/
	private static function getData($url, $type = 'get', $data = '', $headers = '',$cookie = '') {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.1.2) Gecko/20090729 Firefox/3.5.2 GTB5');
		curl_setopt($ch, CURLOPT_HEADERFUNCTION, array('Schedule',"curlResponseHeaderCallback"));
		if ($type == 'post') {
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
			if (is_array($data)) {
				curl_setopt($ch, CURLOPT_POST, count($data));
				$data_string = '';
				foreach($data as $key=>$value) { $data_string .= $key.'='.$value.'&'; }
				rtrim($data_string, '&');
				curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
			} else {
				curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			}
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		}
		if ($headers != '') {
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		}
		if ($cookie != '') {
			curl_setopt($ch, CURLOPT_COOKIE, implode($cookie,';'));
		}
		return curl_exec($ch);
	}
	
	private static function curlResponseHeaderCallback($ch, $headerLine) {
		global $cookies;
		if (preg_match('/^Set-Cookie:\s*([^;]*)/mi', $headerLine, $cookie) == 1)
			$cookies[] = $cookie;
		return strlen($headerLine); // Needed by curl
	}
	
	/**
	* Convert a HTML table to an array
	* @param String $data HTML page
	* @return Array array of the tables in HTML page
	*/
	private static function table2array($data) {
		$html = str_get_html($data);
		$tabledata=array();
		foreach($html->find('tr') as $element)
		{
			$td = array();
			foreach( $element->find('th') as $row)
			{
				$td [] = trim($row->plaintext);
			}
			$td=array_filter($td);
			$tabledata[] = $td;

			$td = array();
			$tdi = array();
			foreach( $element->find('td') as $row)
			{
				$td [] = trim($row->plaintext);
				$tdi [] = trim($row->innertext);
			}
			$td=array_filter($td);
			$tdi=array_filter($tdi);
			$tabledata[]=array_merge($td,$tdi);
		}
		return(array_filter($tabledata));
	}
	
	/**
	* Convert <p> part of a HTML page to an array
	* @param String $data HTML page
	* @return Array array of the <p> in HTML page
	*/
	private static function text2array($data) {
		$html = str_get_html($data);
		$tabledata=array();
		foreach($html->find('p') as $element)
		{
			$tabledata [] = trim($element->plaintext);
		}
		return(array_filter($tabledata));
	}

	
	/**
	* Get flight info from Air France
	* @param String $callsign The callsign
	* @param String $date date we want flight number info
	* @param String $carrier IATA code
	* @return Flight departure and arrival airports and time
	*/
	private static function getAirFrance($callsign, $date = 'NOW',$carrier = 'AF') {
		$check_date = new Datetime($date);
		$numvol = preg_replace('/^[A-Z]*/','',$callsign);
		if (!filter_var($numvol,FILTER_VALIDATE_INT)) return array();
		$url = "http://www.airfrance.fr/cgi-bin/AF/FR/fr/local/resainfovol/infovols/detailsVolJson.do?codeCompagnie[0]=".$carrier."&numeroVol[0]=".$numvol."&dayFlightDate=".$check_date->format('d')."&yearMonthFlightDate=".$check_date->format('Ym');
		$json = Schedule::getData($url);
	
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
		
			return array('DepartureAirportIATA' => $DepartureAirportIata,'DepartureTime' => $departureTime,'ArrivalAirportIATA' => $ArrivalAirportIata,'ArrivalTime' => $arrivalTime);
		} else return array();
	}

	/**
	* Get flight info from EasyJet
	* @param String $callsign The callsign
	* @param String $date date we want flight number info
	* @return Flight departure and arrival airports and time
	*/
	private static function getEasyJet($callsign, $date = 'NOW') {
		$check_date = new Datetime($date);
		$numvol = preg_replace('/^[A-Z]*/','',$callsign);
		if (!filter_var($numvol,FILTER_VALIDATE_INT)) return array();
		$url = "http://www.easyjet.com/ft/api/flights?date=".$check_date->format('Y-m-d')."&fn=".$callsign;
		$json = Schedule::getData($url);
		$parsed_json = json_decode($json);

		$flights = $parsed_json->{'flights'};
		if (count($flights) > 0) {
			$DepartureAirportIata = $parsed_json->{'flights'}[0]->{'airports'}->{'pda'}->{'iata'}; //name
			$ArrivalAirportIata = $parsed_json->{'flights'}[0]->{'airports'}->{'paa'}->{'iata'}; //name
			$departureTime = $parsed_json->{'flights'}[0]->{'dates'}->{'fstd'};
			$arrivalTime = $parsed_json->{'flights'}[0]->{'dates'}->{'fsta'};

			return array('DepartureAirportIATA' => $DepartureAirportIata,'DepartureTime' => $departureTime,'ArrivalAirportIATA' => $ArrivalAirportIata,'ArrivalTime' => $arrivalTime);
		} else return array();
	}

	/**
	* Get flight info from Ryanair
	* @param String $callsign The callsign
	* @return Flight departure and arrival airports and time
	*/
	private static function getRyanair($callsign) {
		$numvol = preg_replace('/^[A-Z]*/','',$callsign);
		if (!filter_var($numvol,FILTER_VALIDATE_INT)) return array();
		$url = "http://www.ryanair.com/fr/api/2/flight-info/0/50/";
		$post = '{"flight":"'.$numvol.'","minDepartureTime":"00:00","maxDepartureTime":"23:59"}';
		$headers = array('Content-Type: application/json','Content-Length: ' . strlen($post));
		$json = Schedule::getData($url,'post',$post,$headers);
		$parsed_json = json_decode($json);

		$flights = $parsed_json->{'flightInfo'};
		if (count($flights) > 0) {
			$DepartureAirportIata = $parsed_json->{'flightInfo'}[0]->{'departureAirport'}->{'iata'}; //name
			$ArrivalAirportIata = $parsed_json->{'flightInfo'}[0]->{'arrivalAirport'}->{'iata'}; //name
			$departureTime = $parsed_json->{'flightInfo'}[0]->{'departureTime'};
			$arrivalTime = $parsed_json->{'flightInfo'}[0]->{'arrivalTime'};

			return array('DepartureAirportIATA' => $DepartureAirportIata,'DepartureTime' => $departureTime,'ArrivalAirportIATA' => $ArrivalAirportIata,'ArrivalTime' => $arrivalTime);
		} else return array();
	
	}

	/**
	* Get flight info from Swiss
	* @param String $callsign The callsign
	* @return Flight departure and arrival airports and time
	*/
	private static function getSwiss($callsign) {
		$numvol = preg_replace('/^[A-Z]*/','',$callsign);
		if (!filter_var($numvol,FILTER_VALIDATE_INT)) return array();
		$url = "http://www.world-of-swiss.com/fr/routenetwork.json";
		$json = Schedule::getData($url);
		$parsed_json = json_decode($json);


		$flights = $parsed_json->{'flights'};
		if (count($flights) > 0) {
			foreach ($flights as $flight) {
				if ($flight->{'no'} == "Vol LX ".$numvol) {
					$DepartureAirportIata = $flight->{'from'}->{'code'}; //city
					$ArrivalAirportIata = $flight->{'to'}->{'code'}; //city
					$departureTime = $flight->{'from'}->{'hour'};
					$arrivalTime = $flight->{'to'}->{'hour'};
				}
			}
			if (isset($DepartureAirportIata)) {
				return array('DepartureAirportIATA' => $DepartureAirportIata,'DepartureTime' => $departureTime,'ArrivalAirportIATA' => $ArrivalAirportIata,'ArrivalTime' => $arrivalTime);
			} else return array();
		} else return array();
	
	}
	
	/**
	* Get flight info from British Airways API
	* @param String $callsign The callsign
	* @param String $date date we want flight number info
	* @return Flight departure and arrival airports and time
	*/
	private static function getBritishAirways($callsign, $date = 'NOW') {
		global $globalBritishAirwaysKey;
		$check_date = new Datetime($date);
		$numvol = sprintf('%04d',preg_replace('/^[A-Z]*/','',$callsign));
		if (!filter_var(preg_replace('/^[A-Z]*/','',$callsign),FILTER_VALIDATE_INT)) return array();
		if ($globalBritishAirwaysKey == '') return array();
		$url = "https://api.ba.com/rest-v1/v1/flights;flightNumber=".$numvol.";scheduledDepartureDate=".$check_date->format('Y-m-d').".json";
		$headers = array('Client-Key: '.$globalBritishAirwaysKey);

		$json = Schedule::getData($url,'get','',$headers);
		if ($json == '') return array();
		$parsed_json = json_decode($json);
		$flights = $parsed_json->{'FlightsResponse'};
		if (count($flights) > 0) {
			$DepartureAirportIata = $parsed_json->{'FlightsResponse'}->{'Flight'}->{'Sector'}->{'DepartureAirport'};
			$ArrivalAirportIata = $parsed_json->{'FlightsResponse'}->{'Flight'}->{'Sector'}->{'ArrivalAirport'};
			$departureTime = date('H:i',strtotime($parsed_json->{'FlightsResponse'}->{'Flight'}->{'Sector'}->{'ScheduledDepartureDateTime'}));
			$arrivalTime = date('H:i',strtotime($parsed_json->{'FlightsResponse'}->{'Flight'}->{'Sector'}->{'ScheduledArrivalDateTime'}));
			return array('DepartureAirportIATA' => $DepartureAirportIata,'DepartureTime' => $departureTime,'ArrivalAirportIATA' => $ArrivalAirportIata,'ArrivalTime' => $arrivalTime);
		} else return array();
	}

	/**
	* Get flight info from Tunisair
	* @param String $callsign The callsign
	* @return Flight departure and arrival airports and time
	*/
	private static function getTunisair($callsign) {
		$numvol = preg_replace('/^[A-Z]*/','',$callsign);
		if (!filter_var($numvol,FILTER_VALIDATE_INT)) return array();
		$url = "http://www.tunisair.com/site/publish/module/Volj/fr/Flight_List.asp";
		$data = Schedule::getData($url);
		$table = Schedule::table2array($data);
		foreach ($table as $flight) {
			if (isset($flight[1]) && $flight[1] == "TU ".sprintf('%04d',$numvol)) {
				return array('DepartureAirportIATA' => $flight[2],'DepartureTime' => str_replace('.',':',$flight[5]),'ArrivalAirportIATA' => $flight[3],'ArrivalTime' => str_replace('.',':',$flight[6]));
			}
		}
		return array();
	}

	/**
	* Get flight info from Vueling
	* @param String $callsign The callsign
	* @return Flight departure and arrival airports and time
	*/
	private static function getVueling($callsign) {
		$numvol = preg_replace('/^[A-Z]*/','',$callsign);
		if (!filter_var($numvol,FILTER_VALIDATE_INT)) return array();
		$url = "https://www.vueling.com/Base/BaseProxy/RenderMacro/?macroalias=DailyFlights&OriginSelected=&DestinationSelected=&idioma=en-GB&pageid=30694&ItemsByPage=50&FlightNumberFilter=".$numvol;
		$data = Schedule::getData($url);
		if ($data != '') {
			$table = Schedule::table2array($data);
			foreach ($table as $flight) {
				if (count($flight) > 0 && $flight[0] == "VY".$numvol) {
					preg_match('/flightOri=[A-Z]{3}/',$flight[13],$result);
					$DepartureAirportIata = str_replace('flightOri=','',$result[0]);
					preg_match('/flightDest=[A-Z]{3}/',$flight[13],$result);
					$ArrivalAirportIata = str_replace('flightDest=','',$result[0]);
					return array('DepartureAirportIATA' => $DepartureAirportIata,'DepartureTime' => $flight[3],'ArrivalAirportIATA' => $ArrivalAirportIata,'ArrivalTime' => $flight[4]);
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
	private static function getIberia($callsign, $date = 'NOW') {
		$numvol = preg_replace('/^[A-Z]*/','',$callsign);
		$check_date = new Datetime($date);
		if (!filter_var($numvol,FILTER_VALIDATE_INT)) return array();
		$url = "https://www.iberia.com/web/flightDetail.do";
		$post = array('numvuelo' => $numvol,'fecha' => $check_date->format('Ymd'),'airlineID' => 'IB');
		$data = Schedule::getData($url,'post',$post);
		if ($data != '') {
			$table = Schedule::table2array($data);
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
				return array('DepartureAirportIATA' => $DepartureAirportIata,'DepartureTime' => $departureTime,'ArrivalAirportIATA' => $ArrivalAirportIata,'ArrivalTime' => $arrivalTime);
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
	private static function getAlitalia($callsign, $date = 'NOW') {
		$numvol = preg_replace('/^[A-Z]*/','',$callsign);
		$check_date = new Datetime($date);
		$url= "http://booking.alitalia.com/FlightStatus/fr_fr/FlightInfo?Brand=az&NumeroVolo=".$numvol."&DataCompleta=".$check_date->format('d/m/Y');
		if (!filter_var($numvol,FILTER_VALIDATE_INT)) return array();
		$data = Schedule::getData($url);
		if ($data != '') {
			$table = Schedule::text2array($data);
			$DepartureAirportIata = '';
			$ArrivalAirportIata = '';
			$departureTime = $table[4];
			$arrivalTime = $table[5];
			return array('DepartureAirportIATA' => $DepartureAirportIata,'DepartureTime' => $departureTime,'ArrivalAirportIATA' => $ArrivalAirportIata,'ArrivalTime' => $arrivalTime);

		}
	}

	/**
	* Get flight info from Lufthansa
	* @param String $callsign The callsign
	* @param String $date date we want flight number info
	* @return Flight departure and arrival airports and time
	*/
	private static function getLufthansa($callsign, $date = 'NOW') {
		$numvol = preg_replace('/^[A-Z]*/','',$callsign);
		$url= "http://www.lufthansa.com/fr/fr/Arrivees-Departs-fonction";
		$check_date = new Datetime($date);
		if (!filter_var($numvol,FILTER_VALIDATE_INT)) return array();

		$post = array('flightNumber' => $numvol, 'date' => $check_date->format('Y-m-d'),'time' => '12:00','timezoneOffset' => '0','selection' => '0','arrivalDeparture' => 'D');
		$data = Schedule::getData($url,'post',$post);
		if ($data != '') {
			$table = Schedule::table2array($data);
			$departureTime = trim(str_replace($check_date->format('d.m.Y'),'',$table[25][3]));
		}

		$post = array('flightNumber' => $numvol, 'date' => $check_date->format('Y-m-d'),'time' => '12:00','timezoneOffset' => '0','selection' => '0','arrivalDeparture' => 'A');
		$data = Schedule::getData($url,'post',$post);
		if ($data != '') {
			$table = Schedule::table2array($data);
			$arrivalTime = trim(str_replace($check_date->format('d.m.Y'),'',$table[25][3]));
		}
		return array('DepartureAirportIATA' => '','DepartureTime' => $departureTime,'ArrivalAirportIATA' => '','ArrivalTime' => $arrivalTime);
	}

	/**
	* Get flight info from Air Canada
	* @param String $callsign The callsign
	* @param String $date date we want flight number info
	* @return Flight departure and arrival airports and time
	*/
	private static function getAirCanada($callsign,$date = 'NOW') {
		date_default_timezone_set('UTC');
		$check_date = new Datetime($date);
		$numvol = preg_replace('/^[A-Z]*/','',$callsign);
		$url= "http://services.aircanada.com/portal/rest/getFlightsByFlightNumber?forceTimetable=true&flightNumber=".$numvol."&carrierCode=AC&date=".$check_date->format('m-d-Y')."&app_key=AE919FDCC80311DF9BABC975DFD72085&cache=74249";
		if (!filter_var($numvol,FILTER_VALIDATE_INT)) return array();
		$data = Schedule::getData($url);
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
			return array('DepartureAirportIATA' => $DepartureAirportIata,'DepartureTime' => $departureTime,'ArrivalAirportIATA' => $ArrivalAirportIata,'ArrivalTime' => $arrivalTime);
		} else return array();
	}

	/**
	* Get flight info from Vietnam Airlines
	* @param String $callsign The callsign
	* @param String $date date we want flight number info
	* @return Flight departure and arrival airports and time
	*/
	private static function getVietnamAirlines($callsign, $date = 'NOW') {
		$numvol = preg_replace('/^[A-Z]*/','',$callsign);
		$check_date = new Datetime($date);
		$url= "https://cat.sabresonicweb.com/SSWVN/meridia?posid=VNVN&page=flifoFlightInfoDetailsMessage_learn&action=flightInfoDetails&airline=VN&language=fr&depDay=".$check_date->format('j')."&depMonth=".strtoupper($check_date->format('M'))."&=&flight=".$numvol."&";
		if (!filter_var($numvol,FILTER_VALIDATE_INT)) return array();
		$data = Schedule::getData($url);
		if ($data != '') {
			$table = Schedule::table2array($data);
			$flight = $table;
			preg_match('/([A-Z]{3})/',$flight[3][0],$DepartureAirportIataMatch);
			preg_match('/([A-Z]{3})/',$flight[21][0],$ArrivalAirportIataMatch);
			$DepartureAirportIata = $DepartureAirportIataMatch[0];
			$ArrivalAirportIata = $ArrivalAirportIataMatch[0];
			$departureTime = $flight[5][1];
			$arrivalTime = $flight[23][1];
			return array('DepartureAirportIATA' => $DepartureAirportIata,'DepartureTime' => $departureTime,'ArrivalAirportIATA' => $ArrivalAirportIata,'ArrivalTime' => $arrivalTime);
		}
	}

	/**
	* Get flight info from Air Berlin
	* @param String $callsign The callsign
	* @param String $date date we want flight number info
	* @param String $carrier IATA code
	* @return Flight departure and arrival airports and time
	*/
	private static function getAirBerlin($callsign, $date = 'NOW', $carrier = 'AB') {
		//AB = airberlin, HG/NLY = NIKI, 4T/BHP = Belair 
		$numvol = preg_replace('/^[A-Z]*/','',$callsign);
		$check_date = new Datetime($date);
		$url= "http://www.airberlin.com/fr-FR/site/aims.php";
		if (!filter_var($numvol,FILTER_VALIDATE_INT)) return array();
		$post = array('type' => 'departure','searchFlightNo' => '1','requestsent' => 'true', 'flightno' => $numvol,'date' => $check_date->format('Y-m-d'),'carrier' => 'AB');
		$data = Schedule::getData($url,'post',$post);
		if ($data != '') {
			$table = Schedule::table2array($data);
			$flight = $table;
//			print_r($table);
			$departureTime = $flight[5][4];
		}
		$post = array('type' => 'arrival','searchFlightNo' => '1','requestsent' => 'true', 'flightno' => $numvol,'date' => $check_date->format('Y-m-d'),'carrier' => 'AB');
		$data = Schedule::getData($url,'post',$post);
		if ($data != '') {
			$table = Schedule::table2array($data);
			$flight = $table;
//			print_r($table);
			$arrivalTime = $flight[5][4];
		}
		return array('DepartureAirportIATA' => '','DepartureTime' => $departureTime,'ArrivalAirportIATA' => '','ArrivalTime' => $arrivalTime);
	}


	
	public static function getSchedule($ident,$date = 'NOW') {
		
		$airline_icao = '';
		if (!is_numeric(substr($ident, 0, 3)))
		{
			if (is_numeric(substr(substr($ident, 0, 3), -1, 1))) {
				$airline_icao = substr($ident, 0, 2);
			} elseif (is_numeric(substr(substr($ident, 0, 4), -1, 1))) {
				$airline_icao = substr($ident, 0, 3);
			} 
		} else echo "Alors la j'ai ça : ".$ident;
		if ($airline_icao != '') {
			switch ($airline_icao) {
				// Air France
				case "AF":
				case "AFR":
					return Schedule::getAirFrance($ident,$date,'AF');
					break;
				// HOP
				case "A5":
				case "HOP":
					return Schedule::getAirFrance($ident,$date,'A5');
					break;
				// EasyJet
				case "U2":
				case "DS":
				case "EZY":
				case "EZS":
					return Schedule::getEasyJet($ident,$date);
					break;
				// Ryanair
				case "FR":
				case "RYR":
					return Schedule::getRyanair($ident);
					break;
				// Swiss
				case "LX":
				case "SWR":
					return Schedule::getSwiss($ident);
					break;
				// British Airways
				case "BA":
				case "SHT":
				case "BAW":
					return Schedule::getBritishAirways($ident);
					break;
				// Tunisair
				case "TUI":
				case "TU":
					return Schedule::getTunisair($ident);
					break;
				// Vueling
				case "VLG":
				case "VY":
					return Schedule::getVueling($ident);
					break;
				// Alitalia
				case "AZ":
				case "AZA":
					return Schedule::getAlitalia($ident);
					break;
				// Air Canada
				case "ACA":
				case "AC":
					return Schedule::getAirCanada($ident);
					break;
				// Lufthansa
				case "DLH":
					return Schedule::getLufthansa($ident);
					break;
				// Iberia
				case "IBE":
				case "IB":
					return Schedule::getIberia($ident);
					break;
				// Vietnam Airlines
				case "HVN":
					return Schedule::getVietnamAirlines($ident,$date);
					break;
				// Air Berlin
				case "AB":
				case "BER":
					return Schedule::getAirBerlin($ident,$date,'AB');
					break;
				// NIKI
				case "HG":
				case "NLY":
					return Schedule::getAirBerlin($ident,$date,'HG');
					break;
				// BelAir
				case "4T":
				case "BHP":
					return Schedule::getAirBerlin($ident,$date,'4T');
					break;
			}
		}
	        return array();
	}
}

//print_r(Schedule::getSchedule('AFR3840'));
//print_r(Schedule::getSchedule('EZY2681'));
//print_r(Schedule::getSchedule('RYR5156'));
//print_r(Schedule::getSchedule('SWR349'));
//print_r(Schedule::getSchedule('BAW548'));
//print_r(Schedule::getSchedule('TUI216'));
//print_r(Schedule::getSchedule('VLG1898'));
//print_r(Schedule::getSchedule('IBE3143'));
//print_r(Schedule::getSchedule('DLH1316'));
//print_r(Schedule::getSchedule('UAL19'));
//print_r(Schedule::getSchedule('ACA834'));
//print_r(Schedule::getSchedule('TSC302'));
//print_r(Schedule::getSchedule('AZA211'));
//print_r(Schedule::getSchedule('EIN451'));
//print_r(Schedule::getSchedule('MSR799'));
//print_r(Schedule::getSchedule('HVN16'));
//print_r(Schedule::getSchedule('BER2295'));
//print_r(Schedule::getSchedule('AAL207'));
//print_r(Schedule::getSchedule('QTR104'));

?>