<?php
/**
 * This class is part of FlightAirmap. It's used for APRS
 *
 * Copyright (c) Ycarus (Yannick Chabanois) at Zugaina <support@flightairmap.com>
 * Licensed under AGPL license.
 * For more information see: https://www.flightairmap.com/
*/

require_once(dirname(__FILE__).'/settings.php');
require_once(dirname(__FILE__).'/class.Common.php');
require_once(dirname(__FILE__).'/class.GeoidHeight.php');

class aprs {
	private $socket;
	private $connected = false;

	protected $symbols = array('/!' => 'Police',
		'/#' => 'DIGI',
		'/$' => 'Phone',
		'/%' => 'DX Cluster',
		'/&' => 'HF Gateway',
		"/'" => 'Aircraft (small)',
		'/(' => 'Mobile Satellite Station',
		'/)' => 'WheelChair',
		'/*' => 'Snowmobile',
		'/+' => 'Red Cross',
		'/,' => 'Reverse L Shape',
		'/-' => 'House QTH (VHF)',
		'/.' => 'X',
		'//' => 'Dot',
		'/0' => '0',
		'/1' => '1',
		'/2' => '2',
		'/3' => '3',
		'/4' => '4',
		'/5' => '5',
		'/6' => '6',
		'/7' => '7',
		'/8' => '8',
		'/9' => '9',
		'/:' => 'Fire',
		'/;' => 'Campground',
		'/<' => 'Motorcycle',
		'/=' => 'Railroad Engine',
		'/>' => 'Car',
		'/?' => 'Server for Files',
		'/@' => 'HC Future Predict',
		'/A' => 'Aid Station',
		'/B' => 'BBS',
		'/C' => 'Canoe',
		'/E' => 'Eyeball',
		'/G' => 'Grid Square',
		'/H' => 'Hotel',
		'/I' => 'TCP-IP',
		'/K' => 'School',
		'/M' => 'MacAPRS',
		'/N' => 'NTS Station',
		'/O' => 'Balloon',
		'/P' => 'Police',
		'/Q' => 'T.B.D.',
		'/R' => 'Recreational Vehicle',
		'/S' => 'Shuttle',
		'/T' => 'SSTV',
		'/U' => 'Bus',
		'/V' => 'ATV',
		'/W' => 'National Weather Service Site',
		'/X' => 'Helicopter',
		'/Y' => 'Yacht (Sail)',
		'/Z' => 'WinAPRS',
		'/[' => 'Jogger',
		'/]' => 'PBBS',
		'/^' => 'Large Aircraft',
		'/_' => 'Weather Station',
		'/`' => 'Dish Antenna',
		'/a' => 'Ambulance',
		'/b' => 'Bike',
		'/c' => 'T.B.D.',
		'/d' => 'Dial Garage (Fire Department)',
		'/e' => 'Horse (Equestrian)',
		'/f' => 'Firetruck',
		'/g' => 'Glider',
		'/h' => 'Hospital',
		'/i' => 'IOTA (Islands On The Air)',
		'/j' => 'Jeep',
		'/k' => 'Truck',
		'/l' => 'Laptop',
		'/m' => 'Mic-Repeater',
		'/n' => 'Node',
		'/o' => 'EOC',
		'/p' => 'Rover (Puppy)',
		'/q' => 'Grid SQ Shown Above 128 Miles',
		'/r' => 'Antenna',
		'/s' => 'Ship (Power Boat)',
		'/t' => 'Truck Stop',
		'/u' => 'Truck (18 Wheeler)',
		'/v' => 'Van',
		'/w' => 'Water Station',
		'/x' => 'xAPRS (UNIX)',
		'/y' => 'Yagi At QTH',
		'\!' => 'Emergency',
		'\#' => 'No. Digi',
		'\$' => 'Bank',
		'\&' => "No. Diam'd",
		"\'" => 'Crash site',
		'\(' => 'Cloudy',
		'\)' => 'MEO',
		'\*' => 'Snow',
		'\+' => 'Church',
		'\,' => 'Girl Scout',
		'\-' => 'Home (HF)',
		'\.' => 'Unknown Position',
		'\/' => 'Destination',
		'\0' => 'No. Circle',
		'\9' => 'Petrol Station',
		'\:' => 'Hail',
		'\;' => 'Park',
		'\<' => 'Gale Fl',
		'\>' => 'No. Car',
		'\?' => 'Info Kiosk',
		'\@' => 'Hurricane',
		'\A' => 'No. Box',
		'\B' => 'Snow blowing',
		'\C' => 'Cost Guard',
		'\D' => 'Drizzle',
		'\E' => 'Smoke',
		'\F' => 'Freeze Rain',
		'\G' => 'Snow Shower',
		'\H' => 'Haze',
		'\I' => 'Rain Shower',
		'\J' => 'Lightning',
		'\K' => 'Kenwood',
		'\L' => 'Lighthouse',
		'\N' => 'Nav Buoy',
		'\O' => 'Rocket',
		'\P' => 'Parking',
		'\Q' => 'Quake',
		'\R' => 'Restaurant',
		'\S' => 'Sat/Pacsat',
		'\T' => 'Thunderstorm',
		'\U' => 'Sunny',
		'\V' => 'VORTAC',
		'\W' => 'No. WXS',
		'\X' => 'Pharmacy',
		'\[' => 'Wall Cloud',
		'\^' => 'No. Plane',
		'\_' => 'No. WX Stn',
		'\`' => 'Rain',
		'\a' => 'No. Diamond',
		'\b' => 'Dust Blowing',
		'\c' => 'No. CivDef',
		'\d' => 'DX Spot',
		'\e' => 'Sleet',
		'\f' => 'Funnel Cld',
		'\g' => 'Gale',
		'\h' => 'HAM store',
		'\i' => 'No. Black Box',
		'\j' => 'WorkZone',
		'\k' => 'SUV',
		'\l' => 'Aera Locations',
		'\m' => 'Milepost',
		'\n' => 'No. Triang',
		'\o' => 'Circle sm',
		'\p' => 'Part Cloud',
		'\r' => 'Restrooms',
		'\s' => 'No. Boat',
		'\t' => 'Tornado',
		'\u' => 'No. Truck',
		'\v' => 'No. Van',
		'\w' => 'Flooding',
		'\y' => 'Sky Warn',
		'\z' => 'No. Shelter',
		'\{' => 'Fog',
		'\|' => 'TNC Stream SW',
		'\~' => 'TNC Stream SW');

	private function urshift($n, $s) {
		return ($n >= 0) ? ($n >> $s) :
		    (($n & 0x7fffffff) >> $s) | 
		    (0x40000000 >> ($s - 1));
	}

	/*
	 * Parse APRS line
	 * @param String $input APRS data
	 * @return Array Return parsed APRS data
	*/
	public function parse($input) {
		global $globalDebug;
		$debug = false;
		$result = array();
		$input_len = strlen($input);
		
		/* Find the end of header checking for NULL bytes while doing it. */
		$splitpos = strpos($input,':');
		
		/* Check that end was found and body has at least one byte. */
		if ($splitpos == 0 || $splitpos + 1 == $input_len || $splitpos === FALSE) {
			if ($globalDebug) echo '!!! APRS invalid : '.$input."\n";
			return false;
		}
		
		if ($debug) echo 'input : '.$input."\n";
		/* Save header and body. */
		$body = substr($input,$splitpos+1,$input_len);
		$body_len = strlen($body);
		$header = substr($input,0,$splitpos);
		if ($debug) echo 'header : '.$header."\n";
		
		/* Parse source, target and path. */
		//FLRDF0A52>APRS,qAS,LSTB
		if (preg_match('/^([A-Z0-9\\-]{1,9})>(.*)$/',$header,$matches)) {
			$ident = $matches[1];
			$all_elements = $matches[2];
			if ($ident == 'AIRCRAFT') {
				$result['format_source'] = 'famaprs';
				$result['source_type'] = 'modes';
			} elseif ($ident == 'MARINE') {
				$result['format_source'] = 'famaprs';
				$result['source_type'] = 'ais';
			} else {
				if ($debug) echo 'ident : '.$ident."\n";
				$result['ident'] = $ident;
			}
		} else {
			if ($debug) 'No ident'."\n";
			return false;
		}
		$elements = explode(',',$all_elements);
		$source = end($elements);
		$result['source'] = $source;
		foreach ($elements as $element) {
			if (preg_match('/^([a-zA-Z0-9-]{1,9})([*]?)$/',$element)) {
			//if ($element == 'TCPIP*') return false;
			} elseif (!preg_match('/^([0-9A-F]{32})$/',$element)) {
				if ($debug) echo 'element : '.$element."\n";
				return false;
			}
			/*
			} elseif (preg_match('/^([0-9A-F]{32})$/',$element)) {
				//echo "ok";
			} else {
				return false;
			}
			*/
		}
		
		$type = substr($body,0,1);
		if ($debug) echo 'type : '.$type."\n";
		if ($type == ';') {
			if (isset($result['source_type']) && $result['source_type'] == 'modes') {
				$result['address'] = trim(substr($body,1,9));
			} elseif (isset($result['source_type']) && $result['source_type'] == 'ais') {
				$result['mmsi'] = trim(substr($body,1,9));
			} else $result['ident'] = trim(substr($body,1,9));
		} elseif ($type == ',') {
			// Invalid data or test data
			return false;
		}
		
		// Check for Timestamp
		$find = false;
		$body_parse = substr($body,1);
		if (preg_match('/^;(.){9}\*/',$body,$matches)) {
			$body_parse = substr($body_parse,10);
			$find = true;
		}
		if (preg_match('/^`(.*)\//',$body,$matches)) {
			$body_parse = substr($body_parse,strlen($matches[1])-1);
			$find = true;
		}
		if (preg_match("/^'(.*)\//",$body,$matches)) {
			$body_parse = substr($body_parse,strlen($matches[1])-1);
			$find = true;
		}
		if (preg_match('/^([0-9]{2})([0-9]{2})([0-9]{2})([zh\\/])/',$body_parse,$matches)) {
			$find = true;
			$timestamp = $matches[0];
			if ($matches[4] == 'h') {
				$timestamp = strtotime(date('Ymd').' '.$matches[1].':'.$matches[2].':'.$matches[3]);
				/*
				if (time() + 3900 < $timestamp) $timestamp -= 86400;
				elseif (time() - 82500 > $timestamp) $timestamp += 86400;
				*/
			} elseif ($matches[4] == 'z' || $matches[4] == '/') {
				// This work or not ?
				$timestamp = strtotime(date('Ym').$matches[1].' '.$matches[2].':'.$matches[3]);
			}
			$body_parse = substr($body_parse,7);
			$result['timestamp'] = $timestamp;
		}
		if (preg_match('/^([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})/',$body_parse,$matches)) {
			$find = true;
			$timestamp = strtotime(date('Y').$matches[1].$matches[2].' '.$matches[3].':'.$matches[4]);
			$body_parse = substr($body_parse,8);
			$result['timestamp'] = $timestamp;
		}
		//if (strlen($body_parse) > 19) {
		if (preg_match('/^([0-9]{2})([0-7 ][0-9 ]\\.[0-9 ]{2})([NnSs])(.)([0-9]{3})([0-7 ][0-9 ]\\.[0-9 ]{2})([EeWw])(.)/',$body_parse,$matches)) {
			$find = true;
			// 4658.70N/00707.78Ez
			$sind = strtoupper($matches[3]);
			$wind = strtoupper($matches[7]);
			$lat_deg = $matches[1];
			$lat_min = $matches[2];
			$lon_deg = $matches[5];
			$lon_min = $matches[6];
			$symbolll = $matches[4];
			//$symbol_table = $matches[4];
			$lat = intval($lat_deg);
			$lon = intval($lon_deg);
			if ($lat > 89 || $lon > 179) return false;
			/*
			    $tmp_5b = str_replace('.','',$lat_min);
			    if (preg_match('/^([0-9]{0,4})( {0,4})$/',$tmp_5b,$matches)) {
			        print_r($matches);
			    }
			    */
			$latitude = $lat + floatval($lat_min)/60;
			$longitude = $lon + floatval($lon_min)/60;
			if ($sind == 'S') $latitude = 0-$latitude;
			if ($wind == 'W') $longitude = 0-$longitude;
			$result['latitude'] = $latitude;
			$result['longitude'] = $longitude;
			$body_parse = substr($body_parse,18);
			$body_parse_len = strlen($body_parse);
		}
		$body_parse_len = strlen($body_parse);
		if ($body_parse_len > 0) {
			/*
			if (!isset($result['timestamp']) && !isset($result['latitude'])) {
				$body_split = str_split($body);
				$symbol_code = $body_split[0];
				$body_parse = substr($body,1);
				$body_parse_len = strlen($body_parse);
			} else { 
			if ($find === false) {
				$body_split = str_split($body);
				$symbol_code = $body_split[0];
				$body_parse = substr($body,1);
				$body_parse_len = strlen($body_parse);
			} else { 
			*/
			if ($find) {
				$body_split = str_split($body_parse);
				$symbol_code = $body_split[0];
				if (!isset($symbolll) || $symbolll == '/') $symbol_code = '/'.$symbol_code;
				else $symbol_code = '\\'.$symbol_code;
				//'
				//if ($type != ';' && $type != '>') {
				if ($type != '') {
					$body_parse = substr($body_parse,1);
					$body_parse_len = strlen($body_parse);
					$result['symbol_code'] = $symbol_code;
					if (isset($this->symbols[$symbol_code])) $result['symbol'] = $this->symbols[$symbol_code];
					if ($symbol_code != '_') {
					}
					if ($body_parse_len >= 7) {
						if (preg_match('/^([0-9\\. ]{3})\\/([0-9\\. ]{3})/',$body_parse)) {
							$course = substr($body_parse,0,3);
							$tmp_s = intval($course);
							if ($tmp_s >= 1 && $tmp_s <= 360) $result['heading'] = intval($course);
							$speed = substr($body_parse,4,3);
							if ($speed != '...') {
								$result['speed'] = intval($speed);
							}
							$body_parse = substr($body_parse,7);
						}
						// Check PHGR, PHG, RNG
					} 
					/*
					else if ($body_parse_len > 0) {
						$rest = $body_parse;
					}
					*/
					if (strlen($body_parse) > 0) {
						if (preg_match('/\\/A=(-[0-9]{5}|[0-9]{6})/',$body_parse,$matches)) {
							$altitude = intval($matches[1]);
							$result['altitude'] = $altitude;
							//$body_parse = trim(substr($body_parse,strlen($matches[0])));
							$body_parse = trim(preg_replace('/\\/A=(-[0-9]{5}|[0-9]{6})/','',$body_parse));
						}
					}
					// Telemetry
					/*
					if (preg_match('/^([0-9]+),(-?)([0-9]{1,6}|[0-9]+\\.[0-9]+|\\.[0-9]+)?,(-?)([0-9]{1,6}|[0-9]+\\.[0-9]+|\\.[0-9]+)?,(-?)([0-9]{1,6}|[0-9]+\\.[0-9]+|\\.[0-9]+)?,(-?)([0-9]{1,6}|[0-9]+\\.[0-9]+|\\.[0-9]+)?,(-?)([0-9]{1,6}|[0-9]+\\.[0-9]+|\\.[0-9]+)?,([01]{0,8})/',$body_parse,$matches)) {
						// Nothing yet...
					}
					*/
					// DAO
					if (preg_match('/^!([0-9A-Z]{3})/',$body_parse,$matches)) {
						$dao = $matches[1];
						if (preg_match('/^([A-Z])([0-9]{2})/',$dao)) {
							$dao_split = str_split($dao);
							$lat_off = (($dao_split[1])-48.0)*0.001/60.0;
							$lon_off = (($dao_split[2])-48.0)*0.001/60.0;
							if ($result['latitude'] < 0) $result['latitude'] -= $lat_off;
							else $result['latitude'] += $lat_off;
							if ($result['longitude'] < 0) $result['longitude'] -= $lon_off;
							else $result['longitude'] += $lon_off;
						}
						$body_parse = substr($body_parse,6);
					}
					if (preg_match('/CS=([0-9A-Z_]*)/',$body_parse,$matches)) {
						$result['ident'] = str_replace('_',' ',$matches[1]);
					}
					if (preg_match('/SQ=([0-9]{4})/',$body_parse,$matches)) {
						$result['squawk'] = $matches[1];
					}
					if (preg_match('/AI=([0-9A-Z]{4})/',$body_parse,$matches)) {
						$result['aircraft_icao'] = $matches[1];
					}
					if (preg_match('/VR=([-0-9]*)/',$body_parse,$matches)) {
						$result['verticalrate'] = $matches[1];
					}
					if (preg_match('/TI=([0-9]*)/',$body_parse,$matches)) {
						$result['typeid'] = $matches[1];
					}
					if (preg_match('/SI=([0-9]*)/',$body_parse,$matches)) {
						$result['statusid'] = $matches[1];
					}
					if (preg_match('/IMO=([0-9]{7})/',$body_parse,$matches)) {
						$result['imo'] = $matches[1];
					}
					if (preg_match('/AD=([0-9]*)/',$body_parse,$matches)) {
						$result['arrival_date'] = $matches[1];
					}
					if (preg_match('/AC=([0-9A-Z_]*)/',$body_parse,$matches)) {
						$result['arrival_code'] = str_replace('_',' ',$matches[1]);
					}
					// OGN comment
					//if (preg_match('/^id([0-9A-F]{8}) ([+-])([0-9]{3,4})fpm ([+-])([0-9.]{3,4})rot (.*)$/',$body_parse,$matches)) {
					if (preg_match('/^id([0-9A-F]{8})/',$body_parse,$matches)) {
						$id = $matches[1];
						//$mode = substr($id,0,2);
						$address = substr($id,2);
						$addressType = (intval(substr($id,0,2),16))&3;
						if ($addressType == 0) $result['addresstype'] = "RANDOM";
						elseif ($addressType == 1) $result['addresstype'] = "ICAO";
						elseif ($addressType == 2) $result['addresstype'] = "FLARM";
						elseif ($addressType == 3) $result['addresstype'] = "OGN";
						$aircraftType = $this->urshift(((intval(substr($id,0,2),16)) & 0b1111100),2);
						$result['aircrafttype_code'] = $aircraftType;
						if ($aircraftType == 0) $result['aircrafttype'] = "UNKNOWN";
						elseif ($aircraftType == 1) $result['aircrafttype'] = "GLIDER";
						elseif ($aircraftType == 2) $result['aircrafttype'] = "TOW_PLANE";
						elseif ($aircraftType == 3) $result['aircrafttype'] = "HELICOPTER_ROTORCRAFT";
						elseif ($aircraftType == 4) $result['aircrafttype'] = "PARACHUTE";
						elseif ($aircraftType == 5) $result['aircrafttype'] = "DROP_PLANE";
						elseif ($aircraftType == 6) $result['aircrafttype'] = "HANG_GLIDER";
						elseif ($aircraftType == 7) $result['aircrafttype'] = "PARA_GLIDER";
						elseif ($aircraftType == 8) $result['aircrafttype'] = "POWERED_AIRCRAFT";
						elseif ($aircraftType == 9) $result['aircrafttype'] = "JET_AIRCRAFT";
						elseif ($aircraftType == 10) $result['aircrafttype'] = "UFO";
						elseif ($aircraftType == 11) $result['aircrafttype'] = "BALLOON";
						elseif ($aircraftType == 12) $result['aircrafttype'] = "AIRSHIP";
						elseif ($aircraftType == 13) $result['aircrafttype'] = "UAV";
						elseif ($aircraftType == 15) $result['aircrafttype'] = "STATIC_OBJECT";
						$stealth = (intval(substr($id,0,2), 16) & 0b10000000) != 0;
						$result['stealth'] = $stealth;
						$result['address'] = $address;
					}
					//Comment
					$result['comment'] = trim($body_parse);
					// parse weather
					if (preg_match('/^_{0,1}([0-9 \\.\\-]{3})\\/([0-9 \\.]{3})g([0-9 \\.]+)t(-{0,1}[0-9 \\.]+)/',$body_parse,$matches)) {
						$result['wind_dir'] = intval($matches[1]);
						$result['wind_speed'] = round(intval($matches[2])*1.60934,1);
						$result['wind_gust'] = round(intval($matches[3])*1.60934,1);
						$result['temp'] = round(5/9*((intval($matches[4]))-32),1);
						$body_parse = substr($body_parse,strlen($matches[0])+1);
					} elseif (preg_match('/^_{0,1}c([0-9 \\.\\-]{3})s([0-9 \\.]{3})g([0-9 \\.]+)t(-{0,1}[0-9 \\.]+)/',$body_parse,$matches)) {
						$result['wind_dir'] = intval($matches[1]);
						$result['wind_speed'] = round($matches[2]*1.60934,1);
						$result['wind_gust'] = round($matches[3]*1.60934,1);
						$result['temp'] = round(5/9*(($matches[4])-32),1);
						$body_parse = substr($body_parse,strlen($matches[0])+1);
					} elseif (preg_match('/^_{0,1}([0-9 \\.\\-]{3})\\/([0-9 \\.]{3})t(-{0,1}[0-9 \\.]+)/',$body_parse,$matches)) {
						$result['wind_dir'] = intval($matches[1]);
						$result['wind_speed'] = round($matches[2]*1.60934,1);
						$result['wind_gust'] = round($matches[3]*1.60934,1);
						$body_parse = substr($body_parse,strlen($matches[0])+1);
					} elseif (preg_match('/^_{0,1}([0-9 \\.\\-]{3})\\/([0-9 \\.]{3})g([0-9 \\.]+)/',$body_parse,$matches)) {
						$result['wind_dir'] = intval($matches[1]);
						$result['wind_speed'] = round($matches[2]*1.60934,1);
						$result['wind_gust'] = round($matches[3]*1.60934,1);
						$body_parse = substr($body_parse,strlen($matches[0])+1);
					}
					// temperature
					//g012t088r000p000P000h38b10110
					//g011t086r000p000P000h29b10198
					if (!isset($result['temp']) && strlen($body_parse) > 0 && preg_match('/^g([0-9 \\.]{3})t([0-9 \\.]{3,4})r([0-9 \\.]{3})p([0-9 \\.]{3})P([0-9 \\.]{3})h([0-9 \\.]{2,3})b([0-9 \\.]{5})/',$body_parse,$matches)) {
						if ($matches[1] != '...') $result['wind_gust'] = round($matches[1]*1.60934,1);
						if ($matches[2] != '...') $result['temp'] = round(5/9*((intval($matches[2]))-32),1);
						if ($matches[3] != '...') $result['rain'] = round((intval($matches[3])/100)*25.1,1);
						if ($matches[4] != '...') $result['precipitation'] = round((intval($matches[4])/100)*25.1,1);
						if ($matches[5] != '...') $result['precipitation24h'] = round((intval($matches[5])/100)*25.1,1);
						if ($matches[6] != '...') $result['humidity'] = intval($matches[6]);
						if ($matches[7] != '...') $result['pressure'] = round((intval($matches[7])/10),1);
						$body_parse = substr($body_parse,strlen($matches[0]));
					} elseif (!isset($result['temp']) && strlen($body_parse) > 0 && preg_match('/^g([0-9 \\.]{3})t([0-9 \\.]{3,4})r([0-9 \\.]{3})P([0-9 \\.]{3})p([0-9 \\.]{3})h([0-9 \\.]{2,3})b([0-9 \\.]{5})/',$body_parse,$matches)) {
						if ($matches[1] != '...') $result['wind_gust'] = round($matches[1]*1.60934,1);
						if ($matches[2] != '...') $result['temp'] = round(5/9*((intval($matches[2]))-32),1);
						if ($matches[3] != '...') $result['rain'] = round((intval($matches[3])/100)*25.1,1);
						if ($matches[5] != '...') $result['precipitation'] = round((intval($matches[5])/100)*25.1,1);
						if ($matches[4] != '...') $result['precipitation24h'] = round((intval($matches[4])/100)*25.1,1);
						if ($matches[6] != '...') $result['humidity'] = intval($matches[6]);
						if ($matches[7] != '...') $result['pressure'] = round((intval($matches[7])/10),1);
						$body_parse = substr($body_parse,strlen($matches[0]));
					} elseif (!isset($result['temp']) && strlen($body_parse) > 0 && preg_match('/^g([0-9 \\.]{3})t([0-9 \\.]{3})r([0-9 \\.]{3})p([0-9 \\.]{3})P([0-9 \\.]{3})b([0-9 \\.]{5})h([0-9 \\.]{2})/',$body_parse,$matches)) {
						if ($matches[1] != '...') $result['wind_gust'] = round($matches[1]*1.60934,1);
						if ($matches[2] != '...') $result['temp'] = round(5/9*((intval($matches[2]))-32),1);
						if ($matches[3] != '...') $result['rain'] = round((intval($matches[3])/100)*25.1,1);
						if ($matches[4] != '...') $result['precipitation'] = round((intval($matches[4])/100)*25.1,1);
						if ($matches[5] != '...') $result['precipitation24h'] = round((intval($matches[5])/100)*25.1,1);
						if ($matches[7] != '...') $result['humidity'] = intval($matches[7]);
						if ($matches[6] != '...') $result['pressure'] = round((intval($matches[6])/10),1);
						$body_parse = substr($body_parse,strlen($matches[0]));
					} elseif (!isset($result['temp']) && strlen($body_parse) > 0 && preg_match('/^g([0-9 \\.]{3})t([0-9 \\.]{3})r([0-9 \\.]{3})P([0-9 \\.]{3})b([0-9 \\.]{5})h([0-9 \\.]{2})/',$body_parse,$matches)) {
						if ($matches[1] != '...') $result['wind_gust'] = round($matches[1]*1.60934,1);
						if ($matches[2] != '...') $result['temp'] = round(5/9*((intval($matches[2]))-32),1);
						if ($matches[3] != '...') $result['rain'] = round((intval($matches[3])/100)*25.1,1);
						if ($matches[4] != '...') $result['precipitation24h'] = round((intval($matches[4])/100)*25.1,1);
						if ($matches[6] != '...') $result['humidity'] = intval($matches[6]);
						if ($matches[5] != '...') $result['pressure'] = round((intval($matches[5])/10),1);
						$body_parse = substr($body_parse,strlen($matches[0]));
					} elseif (!isset($result['temp']) && strlen($body_parse) > 0 && preg_match('/^g([0-9 \\.]{3})t([0-9 \\.]{3})r([0-9 \\.]{3})p([0-9 \\.]{3})b([0-9 \\.]{5})h([0-9 \\.]{2})/',$body_parse,$matches)) {
						if ($matches[1] != '...') $result['wind_gust'] = round($matches[1]*1.60934,1);
						if ($matches[2] != '...') $result['temp'] = round(5/9*((intval($matches[2]))-32),1);
						if ($matches[3] != '...') $result['rain'] = round((intval($matches[3])/100)*25.1,1);
						if ($matches[4] != '...') $result['precipitation'] = round((intval($matches[4])/100)*25.1,1);
						if ($matches[6] != '...') $result['humidity'] = intval($matches[6]);
						if ($matches[5] != '...') $result['pressure'] = round((intval($matches[5])/10),1);
						$body_parse = substr($body_parse,strlen($matches[0]));
					} elseif (!isset($result['temp']) && strlen($body_parse) > 0 && preg_match('/^g([0-9 \\.]{3})t([0-9 \\.]{3})r([0-9 \\.]{3})p([0-9 \\.]{3})h([0-9 \\.]{2})b([0-9 \\.]{5})/',$body_parse,$matches)) {
						if ($matches[1] != '...') $result['wind_gust'] = round($matches[1]*1.60934,1);
						if ($matches[2] != '...') $result['temp'] = round(5/9*((intval($matches[2]))-32),1);
						if ($matches[3] != '...') $result['rain'] = round((intval($matches[3])/100)*25.1,1);
						if ($matches[4] != '...') $result['precipitation'] = round((intval($matches[4])/100)*25.1,1);
						if ($matches[5] != '...') $result['humidity'] = intval($matches[5]);
						if ($matches[6] != '...') $result['pressure'] = round((intval($matches[6])/10),1);
						$body_parse = substr($body_parse,strlen($matches[0]));
					} elseif (!isset($result['temp']) && strlen($body_parse) > 0 && preg_match('/^g([0-9 \\.]{3})t([0-9 \\.]{3})h([0-9 \\.]{2})b([0-9 \\.]{5})/',$body_parse,$matches)) {
						if ($matches[1] != '...') $result['wind_gust'] = round($matches[1]*1.60934,1);
						if ($matches[2] != '...') $result['temp'] = round(5/9*((intval($matches[2]))-32),1);
						if ($matches[2] != '...') $result['humidity'] = intval($matches[3]);
						if ($matches[4] != '...') $result['pressure'] = round((intval($matches[4])/10),1);
						$body_parse = substr($body_parse,strlen($matches[0]));
					} elseif (!isset($result['temp']) && strlen($body_parse) > 0 && preg_match('/^g([0-9 \\.]{3})t([0-9 \\.]{3})r([0-9 \\.]{2,3})h([0-9 \\.]{2})b([0-9 \\.]{5})/',$body_parse,$matches)) {
						if ($matches[1] != '...') $result['wind_gust'] = round($matches[1]*1.60934,1);
						if ($matches[2] != '...') $result['temp'] = round(5/9*((intval($matches[2]))-32),1);
						if ($matches[3] != '...') $result['rain'] = round((intval($matches[3])/100)*25.1,1);
						if ($matches[4] != '...') $result['humidity'] = intval($matches[4]);
						if ($matches[5] != '...') $result['pressure'] = round((intval($matches[5])/10),1);
						$body_parse = substr($body_parse,strlen($matches[0]));
					}
					$result['comment'] = trim($body_parse);
				}
			} else $result['comment'] = trim($body_parse);
		}
		if (isset($result['latitude'])) $result['latitude'] = round($result['latitude'],4);
		if (isset($result['longitude'])) $result['longitude'] = round($result['longitude'],4);
		if ($debug) print_r($result);
		return $result;
	}

	/*
	 * Connect to APRS server
	*/
	public function connect() {
		global $globalAPRSversion, $globalServerAPRSssid, $globalServerAPRSpass,$globalName, $globalServerAPRShost, $globalServerAPRSport;
		$aprs_connect = 0;
		$aprs_keep = 120;
		$aprs_last_tx = time();
		if (isset($globalAPRSversion)) $aprs_version = $globalAPRSversion;
		else $aprs_version = 'FlightAirMap '.str_replace(' ','_',$globalName);
		if (isset($globalServerAPRSssid)) $aprs_ssid = $globalServerAPRSssid;
		else $aprs_ssid = substr('FAM'.strtoupper(str_replace(' ','_',$globalName)),0,8);
		if (isset($globalServerAPRSpass)) $aprs_pass = $globalServerAPRSpass;
		else $aprs_pass = '-1';
		$aprs_filter  = '';
		$aprs_login = "user {$aprs_ssid} pass {$aprs_pass} vers {$aprs_version}\n";
		$Common = new Common();
		$s = $Common->create_socket($globalServerAPRShost,$globalServerAPRSport,$errno,$errstr);
		if ($s !== false) {
			echo 'Connected to APRS server! '."\n";
			$authstart = time();
			$this->socket = $s;
			$send = socket_send( $this->socket  , $aprs_login , strlen($aprs_login) , 0 );
			socket_set_option($this->socket,SOL_SOCKET,SO_KEEPALIVE,1);
			while ($msgin = socket_read($this->socket, 1000,PHP_NORMAL_READ)) {
				if (strpos($msgin, "$aprs_ssid verified") !== FALSE) {
					echo 'APRS user verified !'."\n";
					$this->connected = true;
					return true;
					break;
				}
				if (time()-$authstart > 5) {
					echo 'APRS timeout'."\n";
					break;
				}
			}
		}
	}

	/*
	 * Disconnect from APRS server
	*/
	public function disconnect() {
		socket_close($this->socket);
	}

	/*
	 * Send data to APRS server
	 * @param String $data Data to send
	*/
	public function send($data) {
		global $globalDebug;
		if ($this->connected === false) $this->connect();
		$send = socket_send( $this->socket  , $data , strlen($data),0);
		if ($send === FALSE) {
			if ($globalDebug) echo 'Reconnect...';
			socket_close($this->socket);
			$this->connect();
		}
	}
}

class APRSSpotter extends APRS {
	public function addLiveSpotterData($id,$ident,$aircraft_icao,$departure_airport,$arrival_airport,$latitude,$longitude,$waypoints,$altitude,$altitude_real,$heading,$speed,$datetime,$departure_airport_time,$arrival_airport_time,$squawk,$route_stop,$hex,$putinarchive,$registration,$pilot_id,$pilot_name, $verticalrate, $noarchive, $ground,$format_source,$source_name,$over_country) {
		$Common = new Common();
		date_default_timezone_set('UTC');
		if ($latitude != '' && $longitude != '') {
			$lat = $latitude;
			$long = $longitude;
			$latitude = $Common->convertDM($latitude,'latitude');
			$longitude = $Common->convertDM($longitude,'longitude');
			$coordinate = sprintf("%02d",$latitude['deg']).str_pad(number_format($latitude['min'],2,'.',''),5,'0',STR_PAD_LEFT).$latitude['NSEW'].'/'.sprintf("%03d",$longitude['deg']).str_pad(number_format($longitude['min'],2,'.',''),5,'0',STR_PAD_LEFT).$longitude['NSEW'];
			$w1 = abs(ceil(($latitude['min'] - number_format($latitude['min'],2,'.',''))*1000));
			$w2 = abs(ceil(($longitude['min'] - number_format($longitude['min'],2,'.',''))*1000));
			$w = $w1.$w2;
			//$w = '00';
			$custom = '';
			if ($ident != '') {
				if ($custom != '') $custom .= '/';
				$custom .= 'CS='.$ident;
			}
			if ($squawk != '') {
				if ($custom != '') $custom .= '/';
				$custom .= 'SQ='.$squawk;
			}
			if ($verticalrate != '') {
				if ($custom != '') $custom .= '/';
				$custom .= 'VR='.$verticalrate;
			}
			if ($aircraft_icao != '' && $aircraft_icao != 'NA') {
				if ($custom != '') $custom .= '/';
				$custom .= 'AI='.$aircraft_icao;
			}
			if ($custom != '') $custom = ' '.$custom;
			/*
			// Use AMSL altitude
			$GeoidClass = new GeoidHeight();
			$geoid= round($GeoidClass->get($lat,$long)*3.28084,2);
			$altitude_real = round($altitude_real + $geoid);
			*/
			$this->send('AIRCRAFT>APRS,TCPIP*:;'.$hex.'   *'.date('His',strtotime($datetime)).'h'.$coordinate.'^'.str_pad($heading,3,'0',STR_PAD_LEFT).'/'.str_pad($speed,3,'0',STR_PAD_LEFT).'/A='.str_pad($altitude_real,6,'0',STR_PAD_LEFT).' !W'.$w.'!'.$custom."\n");
		}
	}
}
class APRSMarine extends APRS {
	public function addLiveMarineData($id, $ident, $latitude, $longitude, $heading, $speed,$datetime, $putinarchive,$mmsi,$type,$typeid,$imo,$callsign,$arrival_code,$arrival_date,$status,$statusid,$noarchive,$format_source,$source_name,$over_country) {
		$Common = new Common();
		date_default_timezone_set('UTC');
		if ($latitude != '' && $longitude != '') {
			$latitude = $Common->convertDM($latitude,'latitude');
			$longitude = $Common->convertDM($longitude,'longitude');
			$coordinate = sprintf("%02d",$latitude['deg']).str_pad(number_format($latitude['min'],2,'.',''),5,'0',STR_PAD_LEFT).$latitude['NSEW'].'/'.sprintf("%03d",$longitude['deg']).str_pad(number_format($longitude['min'],2,'.',''),5,'0',STR_PAD_LEFT).$longitude['NSEW'];
			$w1 = abs(ceil(($latitude['min'] - number_format($latitude['min'],2,'.',''))*1000));
			$w2 = abs(ceil(($longitude['min'] - number_format($longitude['min'],2,'.',''))*1000));
			$w = $w1.$w2;
			//$w = '00';
			$custom = '';
			if ($ident != '') {
				if ($custom != '') $custom .= '/';
				$custom .= 'CS='.str_replace(' ','_',$ident);
			}
			if ($typeid != '') {
				if ($custom != '') $custom .= '/';
				$custom .= 'TI='.$typeid;
			}
			if ($statusid != '') {
				if ($custom != '') $custom .= '/';
				$custom .= 'SI='.$statusid;
			}
			if ($imo != '') {
				if ($custom != '') $custom .= '/';
				$custom .= 'IMO='.$imo;
			}
			if ($arrival_date != '') {
				if ($custom != '') $custom .= '/';
				$custom .= 'AD='.strtotime($arrival_date);
			}
			if ($arrival_code != '') {
				if ($custom != '') $custom .= '/';
				$custom .= 'AC='.str_replace(' ','_',$arrival_code);
			}
			if ($custom != '') $custom = ' '.$custom;
			$altitude = 0;
			$this->send('MARINE>APRS,TCPIP*:;'.$mmsi.'*'.date('His',strtotime($datetime)).'h'.$coordinate.'s'.str_pad($heading,3,'0',STR_PAD_LEFT).'/'.str_pad($speed,3,'0',STR_PAD_LEFT).'/A='.str_pad($altitude,6,'0',STR_PAD_LEFT).' !W'.$w.'!'.$custom."\n");
		}
	}
}
//$aprs = new aprs();
//print_r($aprs->parse('MARINE>APRS,TCPIP*,qAS,FAMAIS-1:;366577000*145838h4739.48N/12222.14Ws222/000/A=000000 !W23! SI=5'));
//print_r($aprs->parse('MARINE>APRS,TCPIP*,qAS,FAMAIS-1:;413905111*121816h7959.29S/02626.78Es105/001/A=000000 !W15! CS=GUIPINGNANHUO5599/TI=70'));

?>